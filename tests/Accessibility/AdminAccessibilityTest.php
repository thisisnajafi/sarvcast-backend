<?php

namespace Tests\Accessibility;

use Tests\DuskTestCase;
use App\Models\User;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AdminAccessibilityTest extends DuskTestCase
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
    public function admin_pages_have_proper_heading_structure()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertPresent('h1')
                    ->assertPresent('h2')
                    ->assertPresent('h3');
        });
    }

    /** @test */
    public function admin_forms_have_proper_labels()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins/create')
                    ->assertPresent('label[for="user_id"]')
                    ->assertPresent('label[for="amount"]')
                    ->assertPresent('label[for="type"]')
                    ->assertPresent('label[for="description"]');
        });
    }

    /** @test */
    public function admin_buttons_have_proper_aria_labels()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertAttribute('@add-coin-button', 'aria-label')
                    ->assertAttribute('@search-button', 'aria-label')
                    ->assertAttribute('@filter-button', 'aria-label');
        });
    }

    /** @test */
    public function admin_tables_have_proper_headers()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertPresent('th[scope="col"]')
                    ->assertPresent('thead')
                    ->assertPresent('tbody');
        });
    }

    /** @test */
    public function admin_forms_have_proper_error_messages()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins/create')
                    ->press('ذخیره')
                    ->assertPresent('.error-message')
                    ->assertAttribute('.error-message', 'role', 'alert')
                    ->assertAttribute('.error-message', 'aria-live', 'polite');
        });
    }

    /** @test */
    public function admin_pages_have_proper_focus_management()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->keys('body', ['{tab}'])
                    ->assertFocused('@search-input')
                    ->keys('@search-input', ['{tab}'])
                    ->assertFocused('@type-filter')
                    ->keys('@type-filter', ['{tab}'])
                    ->assertFocused('@status-filter');
        });
    }

    /** @test */
    public function admin_modals_have_proper_aria_attributes()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->click('@delete-button')
                    ->whenAvailable('.modal', function ($modal) {
                        $modal->assertAttribute('role', 'dialog')
                              ->assertAttribute('aria-modal', 'true')
                              ->assertAttribute('aria-labelledby')
                              ->assertAttribute('aria-describedby');
                    });
        });
    }

    /** @test */
    public function admin_pages_have_proper_color_contrast()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertSee('مدیریت سکه‌ها')
                    ->assertPresent('.text-gray-900')
                    ->assertPresent('.bg-white')
                    ->assertPresent('.text-blue-600');
        });
    }

    /** @test */
    public function admin_pages_have_proper_keyboard_navigation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->keys('body', ['{tab}'])
                    ->assertFocused('@search-input')
                    ->keys('@search-input', ['{enter}'])
                    ->assertSee('جستجو')
                    ->keys('body', ['{tab}'])
                    ->assertFocused('@add-coin-button')
                    ->keys('@add-coin-button', ['{enter}'])
                    ->assertPathIs('/admin/coins/create');
        });
    }

    /** @test */
    public function admin_pages_have_proper_screen_reader_support()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->assertPresent('[aria-label]')
                    ->assertPresent('[aria-describedby]')
                    ->assertPresent('[role="button"]')
                    ->assertPresent('[role="navigation"]')
                    ->assertPresent('[role="main"]');
        });
    }

    /** @test */
    public function admin_pages_have_proper_loading_states()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/admin/coins')
                    ->click('@add-coin-button')
                    ->assertPresent('.loading-spinner')
                    ->assertAttribute('.loading-spinner', 'aria-label', 'در حال بارگذاری...')
                    ->assertAttribute('.loading-spinner', 'role', 'status');
        });
    }
}
