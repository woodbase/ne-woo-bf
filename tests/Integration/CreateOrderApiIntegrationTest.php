<?php

use NEBF\Services\BeautyFortApiService;
use PHPUnit\Framework\TestCase;

class CreateOrderApiIntegrationTest extends TestCase
{
    private BeautyFortApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['nebf_test_options'] = [
            'nebf_username' => 'integration-user',
            'nebf_api_key' => 'integration-secret',
            'nebf_api_testmode' => '1',
        ];
        $GLOBALS['nebf_test_last_remote_request'] = null;
        $GLOBALS['nebf_test_remote_handler'] = null;

        $this->service = new BeautyFortApiService();
    }

    protected function tearDown(): void
    {
        $GLOBALS['nebf_test_options'] = [];
        $GLOBALS['nebf_test_last_remote_request'] = null;
        $GLOBALS['nebf_test_remote_handler'] = null;

        parent::tearDown();
    }

    public function test_create_order_success_returns_expected_response_model(): void
    {
        $GLOBALS['nebf_test_remote_handler'] = static function (): array {
            return [
                'response' => ['code' => 200],
                'headers' => ['content-type' => 'text/xml'],
                'body' => '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:bf="http://www.beautyfort.com/api/"><soap:Body>
    <bf:CreateOrderResponse>
        <bf:TestMode>true</bf:TestMode>
        <bf:OrderReference>321654</bf:OrderReference>
        <bf:YourOrderReference>WC-ORDER-1001</bf:YourOrderReference>
    </bf:CreateOrderResponse>
</soap:Body></soap:Envelope>',
            ];
        };

        $result = $this->service->create_order('Wholesale', 'WC-ORDER-1001');

        $this->assertFalse(is_wp_error($result));
        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_mode', $result);
        $this->assertArrayHasKey('order_reference', $result);
        $this->assertArrayHasKey('your_order_reference', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('success', $result);

        $this->assertTrue($result['test_mode']);
        $this->assertSame(321654, $result['order_reference']);
        $this->assertSame('WC-ORDER-1001', $result['your_order_reference']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['warnings']);
        $this->assertTrue($result['success']);
    }

    public function test_create_order_request_contains_mandatory_fields(): void
    {
        $GLOBALS['nebf_test_remote_handler'] = static function (): array {
            return [
                'response' => ['code' => 200],
                'headers' => [],
                'body' => '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:bf="http://www.beautyfort.com/api/"><soap:Body>
    <bf:CreateOrderResponse>
        <bf:TestMode>true</bf:TestMode>
        <bf:OrderReference>111</bf:OrderReference>
    </bf:CreateOrderResponse>
</soap:Body></soap:Envelope>',
            ];
        };

        $this->service->create_order('Wholesale', 'WC-ORDER-1002');

        $request = $GLOBALS['nebf_test_last_remote_request'];
        $this->assertNotNull($request);
        $this->assertSame('https://www.beautyfort.com/api/soap', $request['url']);

        $body = $request['args']['body'];
        $this->assertStringContainsString('<bf:Username>integration-user</bf:Username>', $body);
        $this->assertMatchesRegularExpression('/<bf:Nonce>[^<]+<\/bf:Nonce>/', $body);
        $this->assertMatchesRegularExpression('/<bf:Created>[^<]+<\/bf:Created>/', $body);
        $this->assertMatchesRegularExpression('/<bf:Password>[^<]+<\/bf:Password>/', $body);
        $this->assertStringContainsString('<bf:TestMode>true</bf:TestMode>', $body);
        $this->assertStringContainsString('<bf:Type>Wholesale</bf:Type>', $body);
    }

    public function test_create_order_success_flag_false_when_api_returns_error(): void
    {
        $GLOBALS['nebf_test_remote_handler'] = static function (): array {
            return [
                'response' => ['code' => 200],
                'headers' => ['content-type' => 'text/xml'],
                'body' => '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:bf="http://www.beautyfort.com/api/"><soap:Body>
    <bf:CreateOrderResponse>
        <bf:TestMode>true</bf:TestMode>
        <bf:Errors>
            <bf:Error>
                <bf:Code>4001</bf:Code>
                <bf:Description>Invalid order type.</bf:Description>
            </bf:Error>
        </bf:Errors>
        <bf:OrderReference>0</bf:OrderReference>
    </bf:CreateOrderResponse>
</soap:Body></soap:Envelope>',
            ];
        };

        $result = $this->service->create_order('Wholesale', 'WC-ORDER-1003');

        $this->assertFalse(is_wp_error($result));
        $this->assertFalse($result['success']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame(4001, $result['errors'][0]['code']);
        $this->assertSame('Invalid order type.', $result['errors'][0]['description']);
    }
}
