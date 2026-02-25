<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) exit;

class BeautyFortApiService
{
    private string $endpoint = 'https://www.beautyfort.com/api/soap';
    private string $bfNs = 'http://www.beautyfort.com/api/';
    private string $soapNs = 'http://schemas.xmlsoap.org/soap/envelope/';

    /* ============================================================
     * CREATE ORDER
     * ============================================================ */

    public function create_order(string $type, string $yourOrderReference = '')
    {
        $trace = $this->init_trace();

        $trace['steps'][] = $this->step('validate_type', 'Validate order type');

        $validTypes = ['Wholesale', 'Direct Dispatch'];

        if (!in_array($type, $validTypes, true)) {
            return $this->fail_trace(
                $trace,
                'Invalid order type.',
                'nebf_invalid_order_type'
            );
        }

        $trace['steps'][] = $this->step_ok('validate_type');

        $bodyFragment =
            '<bf:CreateOrderRequest>'
            . '<bf:TestMode>' . $this->get_testmode() . '</bf:TestMode>'
            . '<bf:Type>' . esc_xml($type) . '</bf:Type>';

        if ($yourOrderReference !== '') {
            $bodyFragment .= '<bf:YourOrderReference>' . esc_xml($yourOrderReference) . '</bf:YourOrderReference>';
        }

        $bodyFragment .= '</bf:CreateOrderRequest>';

        return $this->execute_with_trace(
            $trace,
            $bodyFragment,
            'CreateOrderResponse',
            'nebf_last_create_order_trace'
        );
    }

    /* ============================================================
     * ADD ORDER ITEM
     * ============================================================ */

    public function add_order_item(int $orderReference, string $stockCode, int $quantity)
    {
        $trace = $this->init_trace();

        if ($orderReference <= 0) {
            return $this->fail_trace($trace, 'Invalid order reference.', 'nebf_invalid_order_reference');
        }

        if ($quantity <= 0) {
            return $this->fail_trace($trace, 'Quantity must be greater than zero.', 'nebf_invalid_quantity');
        }

        $bodyFragment =
            '<bf:AddOrderItemRequest>'
            . '<bf:TestMode>' . $this->get_testmode() . '</bf:TestMode>'
            . '<bf:OrderReference>' . (int) $orderReference . '</bf:OrderReference>'
            . '<bf:StockCode>' . esc_xml($stockCode) . '</bf:StockCode>'
            . '<bf:Quantity>' . (int) $quantity . '</bf:Quantity>'
            . '</bf:AddOrderItemRequest>';

        return $this->execute_with_trace(
            $trace,
            $bodyFragment,
            'AddOrderItemResponse',
            'nebf_last_add_order_item_trace'
        );
    }

    /* ============================================================
     * EDIT ORDER ITEM
     * ============================================================ */

    public function edit_order_item(int $orderReference, int $orderItemReference, int $quantity)
    {
        $trace = $this->init_trace();

        if ($quantity <= 0) {
            return $this->fail_trace($trace, 'Quantity must be greater than zero.', 'nebf_invalid_quantity');
        }

        $bodyFragment =
            '<bf:EditOrderItemRequest>'
            . '<bf:TestMode>' . $this->get_testmode() . '</bf:TestMode>'
            . '<bf:OrderReference>' . (int) $orderReference . '</bf:OrderReference>'
            . '<bf:OrderItemReference>' . (int) $orderItemReference . '</bf:OrderItemReference>'
            . '<bf:Quantity>' . (int) $quantity . '</bf:Quantity>'
            . '</bf:EditOrderItemRequest>';

        return $this->execute_with_trace(
            $trace,
            $bodyFragment,
            'EditOrderItemResponse',
            'nebf_last_edit_order_item_trace'
        );
    }

    /* ============================================================
     * CORE EXECUTOR
     * ============================================================ */

    private function execute_with_trace(array $trace, string $bodyFragment, string $responseTag, string $optionKey)
    {
        $trace['steps'][] = $this->step('build_request', 'Build SOAP request');

        $request = $this->send_soap_request($bodyFragment);

        if (is_wp_error($request)) {
            return $this->fail_trace($trace, $request->get_error_message(), $request->get_error_code());
        }

        $trace['steps'][] = $this->step_ok('build_request');

        $trace['steps'][] = $this->step('parse_response', 'Parse SOAP response');

        $parsed = $this->parse_generic_response($request['body'], $responseTag);

        if (is_wp_error($parsed)) {
            return $this->fail_trace($trace, $parsed->get_error_message(), $parsed->get_error_code());
        }

        $trace['steps'][] = $this->step_ok('parse_response');

        $trace['http_code'] = $request['http_code'];
        $trace['request_xml'] = substr($request['request'], 0, 12000);
        $trace['response_body'] = substr($request['body'], 0, 12000);
        $trace['parsed'] = $parsed;

        update_option($optionKey, $trace, false);

        return $parsed;
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

        $nonce = uniqid();
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
            'headers' => ['Content-Type' => 'text/xml'],
            'body' => $xml,
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return [
            'http_code' => wp_remote_retrieve_response_code($response),
            'body' => wp_remote_retrieve_body($response),
            'request' => $xml,
        ];
    }

    /* ============================================================
     * PARSER
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

        $errors = [];
        foreach ($xpath->query('.//bf:Error', $node) as $errorNode) {
            $errors[] = [
                'code' => (int) $xpath->query('.//bf:Code', $errorNode)->item(0)->nodeValue,
                'description' => $xpath->query('.//bf:Description', $errorNode)->item(0)->nodeValue,
            ];
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
        ];
    }

    /* ============================================================
     * TRACE HELPERS
     * ============================================================ */

    private function init_trace(): array
    {
        return [
            'time' => gmdate('c'),
            'endpoint' => $this->endpoint,
            'steps' => [],
        ];
    }

    private function step(string $key, string $label): array
    {
        return ['key' => $key, 'label' => $label, 'status' => 'processing'];
    }

    private function step_ok(string $key): array
    {
        return ['key' => $key, 'status' => 'ok'];
    }

    private function fail_trace(array $trace, string $message, string $code)
    {
        $trace['error'] = $message;
        update_option('nebf_last_error_trace', $trace, false);

        return new \WP_Error($code, $message);
    }

    private function get_testmode(): string
    {
        return get_option('nebf_api_testmode', '0') === '1' ? 'true' : 'false';
    }
}