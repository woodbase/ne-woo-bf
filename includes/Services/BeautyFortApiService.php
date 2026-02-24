<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) exit;

/**
 * Handles BeautyFort SOAP stockfile requests.
 */
class BeautyFortApiService
{
    /**
     * Create an order in BeautyFort.
     *
     * @param string $type Supported values: Wholesale, Direct Dispatch.
     * @param string $yourOrderReference Optional client side order reference.
     *
     * @return array<string, mixed>|\WP_Error
     */
    public function create_order(string $type, string $yourOrderReference = '')
    {
        $username = get_option('nebf_username', get_option('nebf_api_username', ''));
        $secret   = get_option('nebf_api_key', get_option('nebf_api_secret', ''));
        $testmode = get_option('nebf_api_testmode', '0') === '1' ? 'true' : 'false';

        $steps = [];
        $steps[] = [
            'key' => 'init',
            'label' => 'Init request',
            'status' => 'ok',
            'details' => 'Starting CreateOrder request flow.',
        ];

        if (!$username || !$secret) {
            $steps[] = [
                'key' => 'credentials',
                'label' => 'Validate credentials',
                'status' => 'error',
                'details' => 'Missing API credentials in plugin settings.',
            ];
            $this->store_create_order_trace('', '', null, [
                'success' => false,
                'error' => 'Missing API credentials.',
                'steps' => $steps,
            ]);
            return new \WP_Error('nebf_missing_credentials', __('Missing API credentials. Please check Settings.', 'nebf-mvc'));
        }

        $steps[] = [
            'key' => 'credentials',
            'label' => 'Validate credentials',
            'status' => 'ok',
            'details' => 'Credentials found in settings.',
        ];

        $validTypes = ['Wholesale', 'Direct Dispatch'];
        if (!in_array($type, $validTypes, true)) {
            $steps[] = [
                'key' => 'validate_type',
                'label' => 'Validate order type',
                'status' => 'error',
                'details' => 'Invalid order type was provided.',
            ];
            $this->store_create_order_trace('', '', null, [
                'success' => false,
                'error' => 'Invalid order type.',
                'steps' => $steps,
            ]);
            return new \WP_Error('nebf_invalid_order_type', __('Invalid order type for CreateOrder request.', 'nebf-mvc'));
        }

        $steps[] = [
            'key' => 'validate_type',
            'label' => 'Validate order type',
            'status' => 'ok',
            'details' => 'Order type is valid: ' . $type,
        ];

        $nonce   = uniqid();
        $created = date('c');
        $password = base64_encode(sha1($nonce . $created . $secret));

        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:bf="http://www.beautyfort.com/api/">'
            . '<soap:Header><bf:AuthHeader>'
            . '<bf:Username>' . esc_xml($username) . '</bf:Username>'
            . '<bf:Nonce>' . esc_xml($nonce) . '</bf:Nonce>'
            . '<bf:Created>' . esc_xml($created) . '</bf:Created>'
            . '<bf:Password>' . esc_xml($password) . '</bf:Password>'
            . '</bf:AuthHeader></soap:Header>'
            . '<soap:Body><bf:CreateOrderRequest>'
            . '<bf:TestMode>' . $testmode . '</bf:TestMode>'
            . '<bf:Type>' . esc_xml($type) . '</bf:Type>';

        if ($yourOrderReference !== '') {
            $xml .= '<bf:YourOrderReference>' . esc_xml($yourOrderReference) . '</bf:YourOrderReference>';
        }

        $xml .= '</bf:CreateOrderRequest></soap:Body></soap:Envelope>';

        $steps[] = [
            'key' => 'build_request',
            'label' => 'Build SOAP request',
            'status' => 'ok',
            'details' => 'SOAP XML generated for CreateOrder.',
        ];

        $response = wp_remote_post('https://www.beautyfort.com/api/soap', [
            'headers' => [
                'Content-Type' => 'text/xml; charset=UTF-8',
                'Accept'       => 'text/xml',
            ],
            'body'    => $xml,
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            $steps[] = [
                'key' => 'send_request',
                'label' => 'Send request',
                'status' => 'error',
                'details' => $response->get_error_message(),
            ];
            $this->store_create_order_trace($xml, '', null, [
                'success' => false,
                'error' => $response->get_error_message(),
                'steps' => $steps,
            ]);
            return $response;
        }

        $steps[] = [
            'key' => 'send_request',
            'label' => 'Send request',
            'status' => 'ok',
            'details' => 'Request sent to BeautyFort endpoint.',
        ];

        $body = (string) wp_remote_retrieve_body($response);
        $httpCode = (int) wp_remote_retrieve_response_code($response);
        $steps[] = [
            'key' => 'receive_response',
            'label' => 'Receive response',
            'status' => 'ok',
            'details' => 'HTTP status: ' . $httpCode,
        ];

        $parsed = $this->parse_create_order_response($body);

        if (is_wp_error($parsed)) {
            $steps[] = [
                'key' => 'parse_response',
                'label' => 'Parse SOAP response',
                'status' => 'error',
                'details' => $parsed->get_error_message(),
            ];

            $this->store_create_order_trace($xml, $body, $httpCode, [
                'success' => false,
                'error' => $parsed->get_error_message(),
                'steps' => $steps,
            ]);

            return $parsed;
        }

        $steps[] = [
            'key' => 'parse_response',
            'label' => 'Parse SOAP response',
            'status' => 'ok',
            'details' => 'SOAP response parsed into normalized model.',
        ];

        $steps[] = [
            'key' => 'result',
            'label' => 'CreateOrder result',
            'status' => !empty($parsed['success']) ? 'ok' : 'warning',
            'details' => !empty($parsed['success'])
                ? 'Order created successfully.'
                : 'Order not created. Check errors in response.',
        ];

        $parsed['steps'] = $steps;
        $this->store_create_order_trace($xml, $body, $httpCode, $parsed);

        return $parsed;
    }

    /**
     * Retrieve last CreateOrder request/response trace from options.
     *
     * @return array<string, mixed>
     */
    public function get_last_create_order_trace(): array
    {
        $trace = get_option('nebf_last_create_order_trace', []);
        return is_array($trace) ? $trace : [];
    }

    /**
     * Request stockfile from BeautyFort and return decoded stock XML.
     *
     * @return \SimpleXMLElement|\WP_Error
     */
    public function request_stockfile()
    {
        $username = get_option('nebf_username', get_option('nebf_api_username', ''));
        $secret   = get_option('nebf_api_key', get_option('nebf_api_secret', ''));
        $testmode = get_option('nebf_api_testmode', '0') === '1' ? 'true' : 'false';

        $trace = [
            'stage' => 'init',
            'time' => gmdate('c'),
            'endpoint' => 'https://www.beautyfort.com/api/soap',
            'test_mode' => $testmode,
        ];

        if (!$username || !$secret) {
            $trace['stage'] = 'missing_credentials';
            $this->store_debug_trace($trace);
            return new \WP_Error('nebf_missing_credentials', __('Missing API credentials. Please check Settings.', 'nebf-mvc'));
        }

        $nonce   = uniqid();
        $created = date('c');
        $password = base64_encode(sha1($nonce . $created . $secret));

        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:bf="http://www.beautyfort.com/api/">'
            . '<soap:Header><bf:AuthHeader>'
            . '<bf:Username>' . esc_xml($username) . '</bf:Username>'
            . '<bf:Nonce>' . esc_xml($nonce) . '</bf:Nonce>'
            . '<bf:Created>' . esc_xml($created) . '</bf:Created>'
            . '<bf:Password>' . esc_xml($password) . '</bf:Password>'
            . '</bf:AuthHeader></soap:Header>'
            . '<soap:Body><bf:GetStockFileRequest>'
            . '<bf:TestMode>' . $testmode . '</bf:TestMode>'
            . '<bf:StockFileFormat>XML</bf:StockFileFormat>'
            . '<bf:StockFileFields>'
            . '<bf:StockFileField>Barcode</bf:StockFileField>'
            . '<bf:StockFileField>Brand</bf:StockFileField>'
            . '<bf:StockFileField>BreakBulkReference</bf:StockFileField>'
            . '<bf:StockFileField>Category</bf:StockFileField>'
            . '<bf:StockFileField>Collection</bf:StockFileField>'
            . '<bf:StockFileField>Description</bf:StockFileField>'
            . '<bf:StockFileField>FullName</bf:StockFileField>'
            . '<bf:StockFileField>Gender</bf:StockFileField>'
            . '<bf:StockFileField>HighResImageUrl</bf:StockFileField>'
            . '<bf:StockFileField>ImageLastUpdated</bf:StockFileField>'
            . '<bf:StockFileField>LastPurchasedDate</bf:StockFileField>'
            . '<bf:StockFileField>LastPurchasedPrice</bf:StockFileField>'
            . '<bf:StockFileField>Price</bf:StockFileField>'
            . '<bf:StockFileField>Quantity</bf:StockFileField>'
            . '<bf:StockFileField>Size</bf:StockFileField>'
            . '<bf:StockFileField>StockCode</bf:StockFileField>'
            . '<bf:StockFileField>StockLevel</bf:StockFileField>'
            . '<bf:StockFileField>ThumbnailImageUrl</bf:StockFileField>'
            . '<bf:StockFileField>Type</bf:StockFileField>'
            . '<bf:StockFileField>YourRating</bf:StockFileField>'
            . '<bf:StockFileField>YourStockCode</bf:StockFileField>'
            . '</bf:StockFileFields>'
            . '<bf:SortBy>FullName</bf:SortBy>'
            . '<bf:StockFileEncoding>UTF-8</bf:StockFileEncoding>'
            . '</bf:GetStockFileRequest></soap:Body></soap:Envelope>';

        // Send SOAP request to BeautyFort endpoint.
        $response = wp_remote_post('https://www.beautyfort.com/api/soap', [
            'headers' => [
                'Content-Type' => 'text/xml; charset=UTF-8',
                'Accept'       => 'text/xml',
            ],
            'body'    => $xml,
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            $trace['stage'] = 'http_error';
            $trace['error'] = $response->get_error_message();
            $this->store_debug_trace($trace);
            return $response;
        }

        $trace['http_code'] = (int) wp_remote_retrieve_response_code($response);
        $trace['response_headers'] = wp_remote_retrieve_headers($response);

        $body = wp_remote_retrieve_body($response);
        $trace['body_preview'] = substr((string) $body, 0, 600);
        $this->store_raw_response_snapshot($trace, (string) $body);

        // Legacy payload path: API can return trueXML/trueJSON + base64 data.
        $trace['step'] = 'legacy_probe';
        $legacyStockXml = $this->parse_legacy_base64_response((string) $body);
        if (!is_wp_error($legacyStockXml)) {
            $trace['stage'] = 'success_legacy_base64';
            $trace['step'] = 'legacy_probe_success';
            $this->store_debug_trace($trace);
            return $legacyStockXml;
        }
        $trace['legacy_error_code'] = $legacyStockXml->get_error_code();
        $trace['legacy_error_message'] = $legacyStockXml->get_error_message();

        $trace['step'] = 'soap_parse';
        libxml_use_internal_errors(true);

        $soapXml = simplexml_load_string($body);
        if (!$soapXml) {
            $trace['stage'] = 'invalid_soap_xml';
            $trace['libxml_errors'] = $this->collect_libxml_errors();
            $trace['body_head_hex'] = bin2hex(substr((string) $body, 0, 32));
            $this->store_debug_trace($trace);
            return new \WP_Error('nebf_invalid_soap_xml', __('Could not parse SOAP XML response.', 'nebf-mvc'));
        }

        $trace['step'] = 'soap_namespaces';
        $soapXml->registerXPathNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
        $soapXml->registerXPathNamespace('bf', 'http://www.beautyfort.com/api/');

        $trace['step'] = 'soap_response_node';
        $soapBodies = $soapXml->xpath('//SOAP-ENV:Body');
        if (empty($soapBodies)) {
            $trace['stage'] = 'missing_soap_body';
            $this->store_debug_trace($trace);
            return new \WP_Error('no_response', __('Could not locate SOAP Body in response.', 'nebf-mvc'));
        }

        $soapBody = $soapBodies[0];
        $stockResponses = $soapBody->xpath('.//bf:GetStockFileResponse');
        if (empty($stockResponses)) {
            $trace['stage'] = 'missing_getstockfileresponse';
            $this->store_debug_trace($trace);
            return new \WP_Error('no_response', __('Could not find GetStockFileResponse in SOAP response.', 'nebf-mvc'));
        }

        $stockResponse = $stockResponses[0];

        $trace['step'] = 'soap_file_decode';
        $encodedFile = $this->extract_file_payload($stockResponse, 'http://www.beautyfort.com/api/');
        if ($encodedFile === '') {
            $trace['stage'] = 'missing_file_node';
            $this->store_debug_trace($trace);
            return new \WP_Error('no_file', __('SOAP response did not contain file payload.', 'nebf-mvc'));
        }

        $decodedXml = base64_decode($encodedFile, true);
        if ($decodedXml === false || $decodedXml === '') {
            $trace['stage'] = 'base64_decode_failed';
            $trace['encoded_file_preview'] = substr($encodedFile, 0, 120);
            $this->store_debug_trace($trace);
            return new \WP_Error('nebf_xml_error', __('Could not decode Base64 XML from BeautyFort.', 'nebf-mvc'));
        }

        $trace['step'] = 'stock_xml_parse';
        $stockXml = simplexml_load_string($decodedXml);
        if (!$stockXml) {
            $trace['stage'] = 'invalid_stock_xml';
            $trace['decoded_xml_preview'] = substr((string) $decodedXml, 0, 600);
            $trace['libxml_errors'] = $this->collect_libxml_errors();
            $this->store_debug_trace($trace);
            return new \WP_Error('xml_error', __('Could not parse stock XML payload.', 'nebf-mvc'));
        }

        $trace['stage'] = 'success';
        $this->store_debug_trace($trace);
        return $stockXml;
    }

    /**
     * Extract stock file payload from SOAP response, including namespaced <ns1:File>.
     */
    private function extract_file_payload(\SimpleXMLElement $stockResponse, string $bfNs): string
    {
        // Try direct child first
        $direct = trim((string) ($stockResponse->File ?? ''));
        if ($direct !== '') {
            return $direct;
        }

        // Try with namespace
        $nsChildren = $stockResponse->children($bfNs);
        if ($nsChildren instanceof \SimpleXMLElement) {
            $namespaced = trim((string) ($nsChildren->File ?? ''));
            if ($namespaced !== '') {
                return $namespaced;
            }
        }

        // Try XPath with local-name
        $nodes = $stockResponse->xpath('.//*[local-name()="File"]');
        if (is_array($nodes) && !empty($nodes)) {
            $xpathValue = trim((string) $nodes[0]);
            if ($xpathValue !== '') {
                return $xpathValue;
            }
        }

        return '';
    }

    /**
     * Collect and clear libxml parser errors for debug tracing.
     *
     * @return array<int, string>
     */
    private function collect_libxml_errors(): array
    {
        $errors = [];
        foreach (libxml_get_errors() as $error) {
            $errors[] = trim((string) $error->message);
        }
        libxml_clear_errors();
        return $errors;
    }

    /**
     * Persist sanitized API debug trace in options for support/debugging.
     */
    private function store_debug_trace(array $trace): void
    {
        $safe = $trace;
        if (!empty($safe['response_headers'])) {
            $safe['response_headers'] = (array) $safe['response_headers'];
        }
        update_option('nebf_last_api_debug', $safe, false);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NEBF API DEBUG: ' . wp_json_encode($safe));
        }
    }

    /**
     * Parse legacy non-SOAP responses like:
     *   trueXML{base64(xml)} or trueJSON{base64(json)}
     *
     * @return \SimpleXMLElement|\WP_Error
     */
    private function parse_legacy_base64_response(string $body)
    {
        $posXml = stripos($body, 'trueXML');
        $posJson = stripos($body, 'trueJSON');

        if ($posXml === false && $posJson === false) {
            return new \WP_Error('nebf_not_legacy_payload', 'Not a legacy base64 payload.');
        }

        $useJson = $posJson !== false && ($posXml === false || $posJson < $posXml);
        $prefix = $useJson ? 'trueJSON' : 'trueXML';
        $start = $useJson ? $posJson : $posXml;
        $payload = trim(substr($body, $start + strlen($prefix)));

        if ($payload === '') {
            return new \WP_Error('nebf_legacy_empty', __('Legacy response payload was empty.', 'nebf-mvc'));
        }

        $normalized = strtr($payload, '-_', '+/');
        $normalized = preg_replace('/\s+/', '', $normalized);
        if (!is_string($normalized) || $normalized === '') {
            return new \WP_Error('nebf_legacy_normalize_failed', __('Legacy payload normalization failed.', 'nebf-mvc'));
        }

        $mod = strlen($normalized) % 4;
        if ($mod > 0) {
            $normalized .= str_repeat('=', 4 - $mod);
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false || $decoded === '') {
            $decoded = base64_decode($normalized, false);
        }
        if (!is_string($decoded) || $decoded === '') {
            return new \WP_Error('nebf_legacy_decode_failed', __('Could not decode legacy base64 payload.', 'nebf-mvc'));
        }

        $decoded = trim($decoded);
        if (strncmp($decoded, "\xEF\xBB\xBF", 3) === 0) {
            $decoded = substr($decoded, 3);
        }

        if ($useJson) {
            return $this->json_payload_to_stock_xml($decoded);
        }

        libxml_use_internal_errors(true);
        $stockXml = simplexml_load_string($decoded);
        if (!$stockXml) {
            return new \WP_Error('nebf_legacy_xml_parse_failed', __('Could not parse decoded legacy XML payload.', 'nebf-mvc'));
        }

        return $stockXml;
    }

    /**
     * Convert decoded JSON payload to stockfile-like XML root/items.
     *
     * @return \SimpleXMLElement|\WP_Error
     */
    private function json_payload_to_stock_xml(string $decodedJson)
    {
        $rows = json_decode($decodedJson, true);
        if (!is_array($rows)) {
            return new \WP_Error('nebf_legacy_json_parse_failed', __('Could not parse decoded legacy JSON payload.', 'nebf-mvc'));
        }

        if (isset($rows['items']) && is_array($rows['items'])) {
            $rows = $rows['items'];
        } elseif (isset($rows['stockfile']) && is_array($rows['stockfile'])) {
            $rows = $rows['stockfile'];
            if (isset($rows['item']) && is_array($rows['item'])) {
                $rows = $rows['item'];
            }
        } elseif (array_keys($rows) !== range(0, count($rows) - 1)) {
            $rows = [$rows];
        }

        $xmlString = '<stockfile>';
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $xmlString .= '<item>';
            foreach ($row as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    continue;
                }
                $safeKey = preg_replace('/[^A-Za-z0-9_:-]/', '', (string) $key);
                if ($safeKey === '') {
                    continue;
                }
                $xmlString .= '<' . $safeKey . '>' . esc_xml((string) $value) . '</' . $safeKey . '>';
            }
            $xmlString .= '</item>';
        }
        $xmlString .= '</stockfile>';

        libxml_use_internal_errors(true);
        $stockXml = simplexml_load_string($xmlString);
        if (!$stockXml) {
            return new \WP_Error('nebf_legacy_json_to_xml_failed', __('Could not convert legacy JSON payload to XML.', 'nebf-mvc'));
        }

        return $stockXml;
    }

    /**
     * Persist a bounded snapshot of the raw API response body.
     */
    private function store_raw_response_snapshot(array $trace, string $body): void
    {
        $snapshot = [
            'time' => $trace['time'] ?? gmdate('c'),
            'endpoint' => $trace['endpoint'] ?? '',
            'http_code' => $trace['http_code'] ?? null,
            'response_headers' => isset($trace['response_headers']) ? (array) $trace['response_headers'] : [],
            // Keep size bounded to avoid huge option payloads.
            'body' => substr($body, 0, 100000),
        ];

        update_option('nebf_last_api_raw_response', $snapshot, false);
    }

    /**
     * Persist request/response trace for CreateOrder in options.
     *
     * @param array<string, mixed> $parsed
     */
    private function store_create_order_trace(string $requestXml, string $responseBody, ?int $httpCode, array $parsed): void
    {
        $snapshot = [
            'time' => gmdate('c'),
            'endpoint' => 'https://www.beautyfort.com/api/soap',
            'http_code' => $httpCode,
            'request_xml' => substr($requestXml, 0, 12000),
            'response_body' => substr($responseBody, 0, 12000),
            'parsed' => $parsed,
        ];

        update_option('nebf_last_create_order_trace', $snapshot, false);
    }

/**
 * Parse SOAP CreateOrder response into a normalized model.
 *
 * @return array<string, mixed>|\WP_Error
 */
private function parse_create_order_response(string $body)
{
    // Use DOMDocument instead of SimpleXML for better namespace handling
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = false;
    
    if (!$dom->loadXML($body)) {
        return new \WP_Error('nebf_invalid_soap_xml', __('Could not parse SOAP XML response.', 'nebf-mvc'));
    }

    $xpath = new \DOMXPath($dom);
    
    // Register namespaces
    $xpath->registerNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
    $xpath->registerNamespace('ns1', 'http://www.beautyfort.com/api/');

    // Find the CreateOrderResponse element
    $responses = $xpath->query('//SOAP-ENV:Body/ns1:CreateOrderResponse');
    
    if ($responses->length === 0) {
        return new \WP_Error('nebf_missing_createorder_response', __('Could not find CreateOrderResponse in SOAP response.', 'nebf-mvc'));
    }

    $responseNode = $responses->item(0);

    // Extract TestMode
    $testModeNodes = $xpath->query('.//ns1:TestMode', $responseNode);
    $testMode = ($testModeNodes->length > 0) && ($testModeNodes->item(0)->nodeValue === 'true');

    // Extract OrderReference
    $orderRefNodes = $xpath->query('.//ns1:OrderReference', $responseNode);
    $orderReference = ($orderRefNodes->length > 0) ? (int) $orderRefNodes->item(0)->nodeValue : 0;

    // Extract YourOrderReference
    $yourRefNodes = $xpath->query('.//ns1:YourOrderReference', $responseNode);
    $yourRef = ($yourRefNodes->length > 0) ? $yourRefNodes->item(0)->nodeValue : null;

    // Extract Errors
    $errors = [];
    $errorNodes = $xpath->query('.//ns1:Error', $responseNode);
    foreach ($errorNodes as $errorNode) {
        $codeNodes = $xpath->query('.//ns1:Code', $errorNode);
        $descNodes = $xpath->query('.//ns1:Description', $errorNode);
        
        $errors[] = [
            'code' => ($codeNodes->length > 0) ? (int) $codeNodes->item(0)->nodeValue : 0,
            'description' => ($descNodes->length > 0) ? $descNodes->item(0)->nodeValue : '',
        ];
    }

    // Extract Warnings
    $warnings = [];
    $warningNodes = $xpath->query('.//ns1:Warning', $responseNode);
    foreach ($warningNodes as $warningNode) {
        $codeNodes = $xpath->query('.//ns1:Code', $warningNode);
        $descNodes = $xpath->query('.//ns1:Description', $warningNode);
        
        $warnings[] = [
            'code' => ($codeNodes->length > 0) ? (int) $codeNodes->item(0)->nodeValue : 0,
            'description' => ($descNodes->length > 0) ? $descNodes->item(0)->nodeValue : '',
        ];
    }

    return [
        'test_mode' => $testMode,
        'order_reference' => $orderReference,
        'your_order_reference' => $yourRef,
        'errors' => $errors,
        'warnings' => $warnings,
        'success' => empty($errors) && $orderReference > 0,
    ];
}
}