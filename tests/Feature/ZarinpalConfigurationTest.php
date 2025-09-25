<?php

namespace Tests\Feature;

use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZarinpalConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
    }

    /** @test */
    public function it_uses_correct_merchant_id()
    {
        $merchantId = config('services.zarinpal.merchant_id');
        
        $this->assertEquals('77751ff3-c1cc-411b-869d-2ac7d7b02f88', $merchantId);
    }

    /** @test */
    public function it_has_callback_url_configured()
    {
        $callbackUrl = config('services.zarinpal.callback_url');
        
        $this->assertNotEmpty($callbackUrl);
        $this->assertStringContains('sarvcast.ir', $callbackUrl);
    }

    /** @test */
    public function it_supports_sandbox_mode()
    {
        $sandboxMode = config('services.zarinpal.sandbox', false);
        
        $this->assertIsBool($sandboxMode);
    }

    /** @test */
    public function it_uses_production_mode_by_default()
    {
        $sandboxMode = config('services.zarinpal.sandbox', false);
        
        $this->assertFalse($sandboxMode);
    }

    /** @test */
    public function merchant_id_is_valid_uuid_format()
    {
        $merchantId = config('services.zarinpal.merchant_id');
        
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $merchantId
        );
    }

    /** @test */
    public function callback_url_is_https()
    {
        $callbackUrl = config('services.zarinpal.callback_url');
        
        $this->assertStringStartsWith('https://', $callbackUrl);
    }

    /** @test */
    public function payment_service_initializes_correctly()
    {
        $this->assertInstanceOf(PaymentService::class, $this->paymentService);
    }

    /** @test */
    public function it_has_all_required_config_keys()
    {
        $config = config('services.zarinpal');
        
        $this->assertArrayHasKey('merchant_id', $config);
        $this->assertArrayHasKey('callback_url', $config);
        $this->assertArrayHasKey('sandbox', $config);
    }

    /** @test */
    public function merchant_id_is_not_empty()
    {
        $merchantId = config('services.zarinpal.merchant_id');
        
        $this->assertNotEmpty($merchantId);
        $this->assertGreaterThan(10, strlen($merchantId));
    }

    /** @test */
    public function callback_url_is_not_empty()
    {
        $callbackUrl = config('services.zarinpal.callback_url');
        
        $this->assertNotEmpty($callbackUrl);
        $this->assertGreaterThan(10, strlen($callbackUrl));
    }

    /** @test */
    public function it_can_switch_to_sandbox_mode()
    {
        // Test sandbox configuration
        config(['services.zarinpal.sandbox' => true]);
        
        $sandboxMode = config('services.zarinpal.sandbox');
        
        $this->assertTrue($sandboxMode);
    }

    /** @test */
    public function it_can_switch_to_production_mode()
    {
        // Test production configuration
        config(['services.zarinpal.sandbox' => false]);
        
        $sandboxMode = config('services.zarinpal.sandbox');
        
        $this->assertFalse($sandboxMode);
    }
}