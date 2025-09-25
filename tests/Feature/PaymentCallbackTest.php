<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentCallbackController;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;
    protected PaymentCallbackController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
        $this->controller = new PaymentCallbackController($this->paymentService);
    }

    /** @test */
    public function payment_success_page_loads_correctly()
    {
        $response = $this->get('/payment/success');
        
        $response->assertStatus(200);
        $response->assertViewIs('payment.success');
    }

    /** @test */
    public function payment_failure_page_loads_correctly()
    {
        $response = $this->get('/payment/failure');
        
        $response->assertStatus(200);
        $response->assertViewIs('payment.failure');
    }

    /** @test */
    public function payment_retry_route_redirects_to_failure()
    {
        $response = $this->get('/payment/retry');
        
        $response->assertRedirect(route('payment.failure'));
        $response->assertSessionHas('message');
    }

    /** @test */
    public function success_page_displays_payment_details_when_payment_id_provided()
    {
        // Create test data
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'status' => 'completed',
            'amount' => 50000,
            'transaction_id' => 'TEST123456'
        ]);

        $response = $this->get('/payment/success?payment_id=' . $payment->id);
        
        $response->assertStatus(200);
        $response->assertViewIs('payment.success');
        $response->assertViewHas('payment', $payment);
    }

    /** @test */
    public function success_page_works_without_payment_id()
    {
        $response = $this->get('/payment/success');
        
        $response->assertStatus(200);
        $response->assertViewIs('payment.success');
        $response->assertViewMissing('payment');
    }

    /** @test */
    public function payment_callback_routes_are_registered()
    {
        $this->assertTrue(\Route::has('payment.success'));
        $this->assertTrue(\Route::has('payment.failure'));
    }

    /** @test */
    public function payment_success_page_contains_expected_elements()
    {
        $response = $this->get('/payment/success');
        
        $response->assertSee('عالی! پرداخت موفق بود');
        $response->assertSee('شروع گوش دادن به داستان‌ها');
        $response->assertSee('بازگشت به اپلیکیشن');
    }

    /** @test */
    public function payment_failure_page_contains_expected_elements()
    {
        $response = $this->get('/payment/failure');
        
        $response->assertSee('متأسفیم! پرداخت انجام نشد');
        $response->assertSee('تلاش مجدد برای پرداخت');
        $response->assertSee('تماس با پشتیبانی');
        $response->assertSee('support@sarvcast.ir');
    }

    /** @test */
    public function payment_success_page_displays_payment_amount_correctly()
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 75000
        ]);

        $response = $this->get('/payment/success?payment_id=' . $payment->id);
        
        $response->assertSee('75,000 تومان');
    }

    /** @test */
    public function payment_success_page_displays_subscription_details()
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'type' => '1month'
        ]);
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id
        ]);

        $response = $this->get('/payment/success?payment_id=' . $payment->id);
        
        $response->assertSee('1month');
    }

    /** @test */
    public function payment_failure_page_contains_helpful_tips()
    {
        $response = $this->get('/payment/failure');
        
        $response->assertSee('بررسی کنید که کارت بانکی شما فعال باشد');
        $response->assertSee('موجودی کافی در حساب داشته باشید');
        $response->assertSee('اتصال اینترنت شما پایدار باشد');
    }

    /** @test */
    public function payment_failure_page_contains_support_information()
    {
        $response = $this->get('/payment/failure');
        
        $response->assertSee('support@sarvcast.ir');
        $response->assertSee('@sarvcast_support');
        $response->assertSee('۹ صبح تا ۶ عصر');
    }

    /** @test */
    public function payment_success_page_has_correct_meta_tags()
    {
        $response = $this->get('/payment/success');
        
        $response->assertSee('<title>پرداخت موفق - سروکست</title>');
        $response->assertSee('lang="fa"');
        $response->assertSee('dir="rtl"');
    }

    /** @test */
    public function payment_failure_page_has_correct_meta_tags()
    {
        $response = $this->get('/payment/failure');
        
        $response->assertSee('<title>پرداخت ناموفق - سروکست</title>');
        $response->assertSee('lang="fa"');
        $response->assertSee('dir="rtl"');
    }

    /** @test */
    public function payment_callback_controller_has_required_methods()
    {
        $this->assertTrue(method_exists($this->controller, 'success'));
        $this->assertTrue(method_exists($this->controller, 'failure'));
        $this->assertTrue(method_exists($this->controller, 'retry'));
        $this->assertTrue(method_exists($this->controller, 'zarinpalCallback'));
    }
}