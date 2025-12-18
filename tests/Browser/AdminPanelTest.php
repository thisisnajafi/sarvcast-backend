<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\CoinTransaction;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AdminPanelTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);
    }

    /** @test */
    public function admin_can_login_and_access_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->type('email', 'admin@test.com')
                    ->type('password', 'password')
                    ->press('ورود')
                    ->assertPathIs('/admin/dashboard')
                    ->assertSee('داشبورد مدیریت')
                    ->assertSee('کل کاربران')
                    ->assertSee('کل داستان‌ها');
        });
    }

    /** @test */
    public function admin_can_navigate_to_coin_management()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/dashboard')
                    ->clickLink('مدیریت سکه‌ها')
                    ->assertPathIs('/admin/coins')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertSee('افزودن سکه')
                    ->assertSee('جستجو بر اساس کاربر...');
        });
    }

    /** @test */
    public function admin_can_create_coin_transaction()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->clickLink('افزودن سکه')
                    ->assertPathIs('/admin/coins/create')
                    ->select('user_id', $user->id)
                    ->type('amount', '1000')
                    ->select('type', 'earned')
                    ->type('description', 'Test coin transaction')
                    ->press('ذخیره')
                    ->assertPathIs('/admin/coins')
                    ->assertSee('سکه با موفقیت اضافه شد.')
                    ->assertSee('1000');
        });
    }

    /** @test */
    public function admin_can_edit_coin_transaction()
    {
        $user = User::factory()->create(['role' => 'user']);
        $transaction = CoinTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'earned'
        ]);

        $this->browse(function (Browser $browser) use ($transaction) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->click('@edit-transaction-' . $transaction->id)
                    ->assertPathIs('/admin/coins/' . $transaction->id . '/edit')
                    ->clear('amount')
                    ->type('amount', '1500')
                    ->select('type', 'gift')
                    ->press('ذخیره')
                    ->assertPathIs('/admin/coins')
                    ->assertSee('تراکنش سکه با موفقیت به‌روزرسانی شد.')
                    ->assertSee('1500');
        });
    }

    /** @test */
    public function admin_can_delete_coin_transaction()
    {
        $user = User::factory()->create(['role' => 'user']);
        $transaction = CoinTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 500
        ]);

        $this->browse(function (Browser $browser) use ($transaction) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->click('@delete-transaction-' . $transaction->id)
                    ->whenAvailable('.modal', function ($modal) {
                        $modal->press('تایید')
                              ->assertDismissed();
                    })
                    ->assertSee('تراکنش سکه با موفقیت حذف شد.')
                    ->assertDontSee('500');
        });
    }

    /** @test */
    public function admin_can_perform_bulk_actions()
    {
        $user = User::factory()->create(['role' => 'user']);
        $transactions = CoinTransaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $this->browse(function (Browser $browser) use ($transactions) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->check('@select-all')
                    ->select('bulk_action', 'approve')
                    ->press('اجرای عملیات گروهی')
                    ->whenAvailable('.modal', function ($modal) {
                        $modal->press('تایید')
                              ->assertDismissed();
                    })
                    ->assertSee('تراکنش‌های انتخاب شده با موفقیت تایید شدند.');
        });
    }

    /** @test */
    public function admin_can_search_and_filter_transactions()
    {
        $user1 = User::factory()->create(['name' => 'John Doe', 'role' => 'user']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'role' => 'user']);
        
        CoinTransaction::factory()->create([
            'user_id' => $user1->id,
            'type' => 'earned',
            'status' => 'completed'
        ]);
        
        CoinTransaction::factory()->create([
            'user_id' => $user2->id,
            'type' => 'purchased',
            'status' => 'pending'
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->type('search', 'John')
                    ->press('جستجو')
                    ->assertSee('John Doe')
                    ->assertDontSee('Jane Smith')
                    ->clear('search')
                    ->select('type', 'earned')
                    ->press('فیلتر')
                    ->assertSee('John Doe')
                    ->assertDontSee('Jane Smith');
        });
    }

    /** @test */
    public function admin_can_view_transaction_details()
    {
        $user = User::factory()->create(['role' => 'user']);
        $transaction = CoinTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'type' => 'earned',
            'description' => 'Test transaction'
        ]);

        $this->browse(function (Browser $browser) use ($transaction) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->click('@view-transaction-' . $transaction->id)
                    ->assertPathIs('/admin/coins/' . $transaction->id)
                    ->assertSee('جزئیات تراکنش سکه')
                    ->assertSee('1000')
                    ->assertSee('Test transaction')
                    ->assertSee($user->name);
        });
    }

    /** @test */
    public function admin_can_export_transactions()
    {
        CoinTransaction::factory()->count(5)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->clickLink('صادرات گزارش')
                    ->assertSee('گزارش تراکنش‌های سکه آماده دانلود است.');
        });
    }

    /** @test */
    public function admin_can_view_statistics()
    {
        CoinTransaction::factory()->count(10)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->clickLink('آمار و گزارش‌ها')
                    ->assertPathIs('/admin/coins/statistics')
                    ->assertSee('آمار تراکنش‌های سکه')
                    ->assertSee('کل تراکنش‌ها')
                    ->assertSee('سکه‌های کسب شده')
                    ->assertSee('سکه‌های خریداری شده');
        });
    }

    /** @test */
    public function admin_can_navigate_sidebar_menu()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/dashboard')
                    ->clickLink('مدیریت سکه‌ها')
                    ->assertPathIs('/admin/coins')
                    ->clickLink('مدیریت کدهای تخفیف')
                    ->assertPathIs('/admin/coupons')
                    ->clickLink('مدیریت پرداخت‌های کمیسیون')
                    ->assertPathIs('/admin/commission-payments')
                    ->clickLink('مدیریت برنامه وابسته')
                    ->assertPathIs('/admin/affiliate')
                    ->clickLink('مدیریت پلن‌های اشتراک')
                    ->assertPathIs('/admin/subscription-plans')
                    ->clickLink('مدیریت نقش‌ها')
                    ->assertPathIs('/admin/roles');
        });
    }

    /** @test */
    public function admin_can_use_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->resize(375, 667) // iPhone size
                    ->visit('/admin/coins')
                    ->assertSee('مدیریت سکه‌ها')
                    ->click('@mobile-menu-toggle')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertSee('مدیریت کدهای تخفیف')
                    ->resize(1920, 1080) // Desktop size
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertSee('افزودن سکه');
        });
    }

    /** @test */
    public function admin_can_handle_form_validation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins/create')
                    ->press('ذخیره')
                    ->assertSee('فیلد کاربر الزامی است')
                    ->assertSee('فیلد مبلغ الزامی است')
                    ->assertSee('فیلد نوع الزامی است')
                    ->type('amount', '-100')
                    ->press('ذخیره')
                    ->assertSee('مبلغ باید بزرگتر از 0 باشد');
        });
    }

    /** @test */
    public function admin_can_handle_loading_states()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->clickLink('افزودن سکه')
                    ->assertSee('افزودن تراکنش سکه')
                    ->type('amount', '1000')
                    ->press('ذخیره')
                    ->waitForText('سکه با موفقیت اضافه شد', 10)
                    ->assertSee('سکه با موفقیت اضافه شد');
        });
    }

    /** @test */
    public function admin_can_handle_error_states()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins/create')
                    ->select('user_id', '999999') // Non-existent user
                    ->type('amount', '1000')
                    ->select('type', 'earned')
                    ->press('ذخیره')
                    ->assertSee('کاربر انتخاب شده معتبر نیست');
        });
    }

    /** @test */
    public function admin_can_use_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->keys('body', ['{tab}']) // Tab to first focusable element
                    ->assertFocused('@search-input')
                    ->keys('@search-input', ['{tab}'])
                    ->assertFocused('@type-filter')
                    ->keys('@type-filter', ['{tab}'])
                    ->assertFocused('@status-filter');
        });
    }

    /** @test */
    public function admin_can_handle_pagination()
    {
        // Create more transactions than fit on one page
        CoinTransaction::factory()->count(25)->create();

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertSee('نمایش 1 تا 20 از 25 نتیجه')
                    ->clickLink('2')
                    ->assertSee('نمایش 21 تا 25 از 25 نتیجه')
                    ->clickLink('قبلی')
                    ->assertSee('نمایش 1 تا 20 از 25 نتیجه');
        });
    }
}
