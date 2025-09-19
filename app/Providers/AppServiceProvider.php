<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Carbon\Carbon;
use App\Helpers\JalaliHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set Carbon locale to Persian
        Carbon::setLocale('fa');
        
        // Set default timezone to Tehran
        date_default_timezone_set('Asia/Tehran');

        // Register Blade directives for Jalali dates
        Blade::directive('jalali', function ($expression) {
            return "<?php echo \\App\\Helpers\\JalaliHelper::formatForDisplay($expression); ?>";
        });

        Blade::directive('jalaliWithMonth', function ($expression) {
            return "<?php echo \\App\\Helpers\\JalaliHelper::formatWithPersianMonth($expression); ?>";
        });

        Blade::directive('jalaliWithMonthAndTime', function ($expression) {
            return "<?php echo \\App\\Helpers\\JalaliHelper::formatWithPersianMonthAndTime($expression); ?>";
        });

        Blade::directive('jalaliRelative', function ($expression) {
            return "<?php echo \\App\\Helpers\\JalaliHelper::getRelativeTime($expression); ?>";
        });
    }
}
