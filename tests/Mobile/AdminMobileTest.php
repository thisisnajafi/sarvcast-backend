<?php

namespace Tests\Mobile;

use Tests\DuskTestCase;
use App\Models\User;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AdminMobileTest extends DuskTestCase
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
    public function admin_panel_is_responsive_on_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667) // iPhone size
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertPresent('.mobile-menu-toggle')
                    ->assertPresent('.mobile-sidebar')
                    ->assertPresent('.mobile-header');
        });
    }

    /** @test */
    public function admin_forms_work_properly_on_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/coins/create')
                    ->assertSee('افزودن تراکنش سکه')
                    ->assertPresent('input[type="number"]')
                    ->assertPresent('select')
                    ->assertPresent('textarea')
                    ->assertPresent('button[type="submit"]');
        });
    }

    /** @test */
    public function admin_tables_are_responsive_on_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertPresent('.table-responsive')
                    ->assertPresent('.mobile-table-view')
                    ->assertPresent('.mobile-card-view');
        });
    }

    /** @test */
    public function admin_navigation_works_on_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/dashboard')
                    ->click('.mobile-menu-toggle')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertSee('مدیریت کدهای تخفیف')
                    ->assertSee('مدیریت پرداخت‌های کمیسیون')
                    ->click('مدیریت سکه‌ها')
                    ->assertPathIs('/admin/coins');
        });
    }

    /** @test */
    public function admin_modals_work_properly_on_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->click('@delete-button')
                    ->whenAvailable('.modal', function ($modal) {
                        $modal->assertSee('تایید')
                              ->assertSee('لغو')
                              ->assertPresent('.modal-content')
                              ->assertPresent('.modal-header')
                              ->assertPresent('.modal-body')
                              ->assertPresent('.modal-footer');
                    });
        });
    }

    /** @test */
    public function admin_buttons_are_touch_friendly()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertPresent('button[class*="touch-friendly"]')
                    ->assertPresent('button[style*="min-height: 44px"]')
                    ->assertPresent('button[style*="min-width: 44px"]');
        });
    }

    /** @test */
    public function admin_inputs_are_mobile_optimized()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/coins/create')
                    ->assertAttribute('input[type="number"]', 'inputmode', 'numeric')
                    ->assertAttribute('input[type="email"]', 'inputmode', 'email')
                    ->assertAttribute('input[type="tel"]', 'inputmode', 'tel')
                    ->assertAttribute('input[type="url"]', 'inputmode', 'url');
        });
    }

    /** @test */
    public function admin_pages_have_proper_viewport_meta()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertPresent('meta[name="viewport"]')
                    ->assertAttribute('meta[name="viewport"]', 'content', 'width=device-width, initial-scale=1.0');
        });
    }

    /** @test */
    public function admin_pages_work_on_tablet()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(768, 1024) // iPad size
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertPresent('.tablet-sidebar')
                    ->assertPresent('.tablet-header')
                    ->assertPresent('.tablet-content');
        });
    }

    /** @test */
    public function admin_pages_work_on_large_mobile()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(414, 896) // iPhone Pro Max size
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertPresent('.large-mobile-sidebar')
                    ->assertPresent('.large-mobile-header')
                    ->assertPresent('.large-mobile-content');
        });
    }

    /** @test */
    public function admin_pages_have_proper_touch_gestures()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertPresent('[data-swipe-left]')
                    ->assertPresent('[data-swipe-right]')
                    ->assertPresent('[data-pinch-zoom]')
                    ->assertPresent('[data-tap]');
        });
    }

    /** @test */
    public function admin_pages_have_proper_mobile_loading()
    {
        $this->browse(function (Browser $browser) {
            $browser->resize(375, 667)
                    ->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertPresent('.mobile-loading')
                    ->assertAttribute('.mobile-loading', 'aria-label', 'در حال بارگذاری...')
                    ->assertAttribute('.mobile-loading', 'role', 'status');
        });
    }
}
