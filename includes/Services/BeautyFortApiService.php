<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) exit;

class BeautyFortApiService
{
    private string $endpoint = 'https://www.beautyfort.com/api/soap';
    private string $bfNs = 'http://www.beautyfort.com/api/';
    private string $soapNs = 'http://schemas.xmlsoap.org/soap/envelope/';

    /* ============================================================
     * PUBLIC API METHODS
     * ============================================================ */

    public function create_order(string $type, string $yourOrderReference = '')
    {
        $validTypes = ['Wholesale', 'Direct Dispatch'];

        if (!in_array($type, $validTypes, true)) {
            return new \WP_Error('nebf_invalid_order_type', 'Invalid order type.');
        }

        $body =
            '<bf:CreateOrderRequest>'
            . '<bf:TestMode>' . $this->get_testmode() . '</bf:TestMode>'
            . '<bf:Type>' . esc_xml($type) . '</bf:Type>';

        if ($yourOrderReference !== '') {
            $body .= '<bf:YourOrderReference>' . esc_xml($yourOrderReference) . '</bf:YourOrderReference>';
        }

        $body .= '</bf:CreateOrderRequest>';

        return $this->execute_with_trace($body, 'CreateOrderResponse');
    }

    public function add_order_item(int $orderReference, string $stockCode, int $quantity)
    {
        if ($orderReference <= 0) {
            return new \WP_Error('nebf_invalid_order_reference', 'Invalid order reference.');
        }

        if ($quantity <= 0) {
            return new \WP_Error('nebf_invalid_quantity', 'Quantity must be greater than zero.');
        }

        $body =
            '<bf:AddOrderItemRequest>'
            . '<bf:TestMode>' . $this->get_testmode() . '</bf:TestMode>'
            . '<bf:OrderReference>' . $orderReference . '</bf:OrderReference>'
            . '<bf:StockCode>' . esc_xml($stockCode) . '</bf:StockCode>'
            . '<bf:Quantity>' . $quantity . '</bf:Quantity>'
            . '</bf:AddOrderItemRequest>';

        return $this->execute_with_trace($body, 'AddOrderItemResponse');
    }

    public function edit_order_item(int $orderReference, int $orderItemReference, int $quantity)
    {
        if ($quantity <= 0) {
            return new \WP_Error('nebf_invalid_quantity', 'Quantity must be greater than zero.');
        }

        $body =
            '<bf:EditOrderItemRequest>'
            . '<bf:TestMode>' . $this->get_testmode() . '</bf:TestMode>'
            . '<bf:OrderReference>' . $orderReference . '</bf:OrderReference>'
            . '<bf:OrderItemReference>' . $orderItemReference . '</bf:OrderItemReference>'
            . '<bf:Quantity>' . $quantity . '</bf:Quantity>'
            . '</bf:EditOrderItemRequest>';

        return $this->execute_with_trace($body, 'EditOrderItemResponse');
    }

    /* ============================================================
     * CORE EXECUTION + TRACE
     * ============================================================ */

    private function execute_with_trace(string $bodyFragment, string $responseTag)
    {
        $trace = [
            'time' => gmdate('c'),
            'endpoint' => $this->endpoint,
        ];

        $request = $this->send_soap_request($bodyFragment);

        if (is_wp_error($request)) {
            return $this->fail_trace($trace, $request->get_error_message(), $request->get_error_code());
        }

        if ($responseTag === 'CreateOrderResponse') {
    $parsed = $this->parse_create_order_response($request['body']);
} else {
    $parsed = $this->parse_generic_response($request['body'], $responseTag);
}

        if (is_wp_error($parsed)) {
            return $this->fail_trace($trace, $parsed->get_error_message(), $parsed->get_error_code());
        }

        $trace['http_code'] = $request['http_code'];
        $trace['request_xml'] = substr($request['request'], 0, 12000);
        $trace['response_body'] = substr($request['body'], 0, 12000);
        $trace['parsed'] = $parsed;

        $traceService = new TraceService();

        if ($responseTag === 'CreateOrderResponse') {
            $traceService->save('create', $trace);
        } elseif ($responseTag === 'AddOrderItemResponse') {
            $traceService->save('add', $trace);
        } elseif ($responseTag === 'EditOrderItemResponse') {
            $traceService->save('edit', $trace);
        }

        return $parsed;
    }

    private function fail_trace(array $trace, string $message, string $code)
    {
        $trace['error'] = $message;

        $traceService = new TraceService();
        $traceService->save('error', $trace);

        return new \WP_Error($code, $message);
    }

    /* ============================================================
     * SOAP ENGINE
     * ============================================================ */

    private function send_soap_request(string $bodyFragment)
    {
        $username = get_option('nebf_username', '');
        $secret   = get_option('nebf_api_key', '');

        if (!$username || !$secret) {
            return new \WP_Error('nebf_missing_credentials', 'Missing API credentials.');
        }

        $nonce   = uniqid();
        $created = date('c');
        $password = base64_encode(sha1($nonce . $created . $secret));

        $xml =
            '<?xml version="1.0"?>'
            . '<soap:Envelope xmlns:soap="' . $this->soapNs . '" xmlns:bf="' . $this->bfNs . '">'
            . '<soap:Header><bf:AuthHeader>'
            . '<bf:Username>' . esc_xml($username) . '</bf:Username>'
            . '<bf:Nonce>' . esc_xml($nonce) . '</bf:Nonce>'
            . '<bf:Created>' . esc_xml($created) . '</bf:Created>'
            . '<bf:Password>' . esc_xml($password) . '</bf:Password>'
            . '</bf:AuthHeader></soap:Header>'
            . '<soap:Body>'
            . $bodyFragment
            . '</soap:Body>'
            . '</soap:Envelope>';

        $response = wp_remote_post($this->endpoint, [
            'headers' => ['Content-Type' => 'text/xml; charset=UTF-8'],
            'body'    => $xml,
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return [
            'http_code' => (int) wp_remote_retrieve_response_code($response),
            'body'      => (string) wp_remote_retrieve_body($response),
            'request'   => $xml,
        ];
    }

    /* ============================================================
     * GENERIC PARSER
     * ============================================================ */

    private function parse_generic_response(string $body, string $responseTag)
{
    $dom = new \DOMDocument();

    if (!$dom->loadXML($body)) {
        return new \WP_Error('nebf_invalid_xml', 'Invalid SOAP XML.');
    }

    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('soap', $this->soapNs);
    $xpath->registerNamespace('bf', $this->bfNs);

    $nodes = $xpath->query('//soap:Body/bf:' . $responseTag);

    if ($nodes->length === 0) {
        return new \WP_Error('nebf_missing_response', $responseTag . ' not found.');
    }

    $node = $nodes->item(0);

    // Success
    $successNode = $xpath->query('.//bf:Success', $node)->item(0);
    $success = $successNode ? ($successNode->nodeValue === 'true') : false;

    // Errors
    $errors = [];
    foreach ($xpath->query('.//bf:Error', $node) as $errorNode) {
        $codeNode = $xpath->query('.//bf:Code', $errorNode)->item(0);
        $descNode = $xpath->query('.//bf:Description', $errorNode)->item(0);

        $errors[] = [
            'code' => $codeNode ? (int) $codeNode->nodeValue : 0,
            'description' => $descNode ? $descNode->nodeValue : '',
        ];
    }

    // Warnings
    $warnings = [];
    foreach ($xpath->query('.//bf:Warning', $node) as $warningNode) {
        $codeNode = $xpath->query('.//bf:Code', $warningNode)->item(0);
        $descNode = $xpath->query('.//bf:Description', $warningNode)->item(0);

        $warnings[] = [
            'code' => $codeNode ? (int) $codeNode->nodeValue : 0,
            'description' => $descNode ? $descNode->nodeValue : '',
        ];
    }

    return [
        'success'  => $success,
        'errors'   => $errors,
        'warnings' => $warnings,
    ];
}

    public function cancel_order(?int $orderReference = null, ?string $yourOrderReference = null)
{
    if (!$orderReference && !$yourOrderReference) {
        return new \WP_Error(
            'nebf_missing_identifier',
            'Either OrderReference or YourOrderReference is required.'
        );
    }

    $body =
        '<bf:CancelOrderRequest>'
        . '<bf:TestMode>' . $this->get_testmode() . '</bf:TestMode>';

    if ($orderReference) {
        $body .= '<bf:OrderReference>' . (int)$orderReference . '</bf:OrderReference>';
    }

    if ($yourOrderReference) {
        $body .= '<bf:YourOrderReference>' . esc_xml($yourOrderReference) . '</bf:YourOrderReference>';
    }

    $body .= '</bf:CancelOrderRequest>';

    return $this->execute_with_trace($body, 'CancelOrderResponse');
}

    /* ============================================================
     * HELPERS
     * ============================================================ */

    private function get_testmode(): string
    {
        return get_option('nebf_api_testmode', '0') === '1' ? 'true' : 'false';
    }

    private function parse_create_order_response(string $body)
{
    $dom = new \DOMDocument();

    if (!$dom->loadXML($body)) {
        return new \WP_Error('nebf_invalid_xml', 'Invalid SOAP XML.');
    }

    $xpath = new \DOMXPath($dom);

    // Namespace-agnostisk matchning
    $nodes = $xpath->query('//*[local-name()="CreateOrderResponse"]');

    if ($nodes->length === 0) {
        return new \WP_Error('nebf_missing_response', 'CreateOrderResponse not found.');
    }

    $node = $nodes->item(0);

    $orderRefNode = $xpath->query('.//*[local-name()="OrderReference"]', $node)->item(0);
    $yourRefNode  = $xpath->query('.//*[local-name()="YourOrderReference"]', $node)->item(0);

    $orderReference = $orderRefNode ? (int)$orderRefNode->nodeValue : 0;
    $yourReference  = $yourRefNode ? $yourRefNode->nodeValue : null;

    $errors = [];
    foreach ($xpath->query('.//*[local-name()="Error"]', $node) as $errorNode) {
        $codeNode = $xpath->query('.//*[local-name()="Code"]', $errorNode)->item(0);
        $descNode = $xpath->query('.//*[local-name()="Description"]', $errorNode)->item(0);

        $errors[] = [
            'code' => $codeNode ? (int)$codeNode->nodeValue : 0,
            'description' => $descNode ? $descNode->nodeValue : '',
        ];
    }

    $warnings = [];
    foreach ($xpath->query('.//*[local-name()="Warning"]', $node) as $warningNode) {
        $codeNode = $xpath->query('.//*[local-name()="Code"]', $warningNode)->item(0);
        $descNode = $xpath->query('.//*[local-name()="Description"]', $warningNode)->item(0);

        $warnings[] = [
            'code' => $codeNode ? (int)$codeNode->nodeValue : 0,
            'description' => $descNode ? $descNode->nodeValue : '',
        ];
    }

    return [
        'success' => empty($errors) && $orderReference > 0,
        'order_reference' => $orderReference,
        'your_order_reference' => $yourReference,
        'errors' => $errors,
        'warnings' => $warnings,
    ];
}
}