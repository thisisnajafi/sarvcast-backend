<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use App\Helpers\JalaliHelper;
use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Story;
use App\Models\User;
use App\Observers\EpisodeActivityLogObserver;
use App\Observers\EpisodeObserver;
use App\Observers\MediaAssetActivityLogObserver;
use App\Observers\StoryActivityLogObserver;
use App\Observers\StoryObserver;
use App\Observers\UserActivityLogObserver;

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
        if ($this->app->environment('production')) {
            $appUrl = config('app.url');
            if (is_string($appUrl) && $appUrl !== '') {
                URL::forceRootUrl(rtrim($appUrl, '/'));
            }
            URL::forceScheme('https');
        }

        // Disable all caching if configured
        if (env('DISABLE_ALL_CACHING', false)) {
            $this->disableAllCaching();
        }

        // Set Carbon locale to Persian
        Carbon::setLocale('fa');
        
        // Set default timezone to Tehran
        date_default_timezone_set('Asia/Tehran');

        // Register observers
        Episode::observe(EpisodeObserver::class);
        Story::observe(StoryObserver::class);
        Story::observe(StoryActivityLogObserver::class);
        Episode::observe(EpisodeActivityLogObserver::class);
        User::observe(UserActivityLogObserver::class);
        MediaAsset::observe(MediaAssetActivityLogObserver::class);

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

    /**
     * Disable all Laravel caching mechanisms
     */
    private function disableAllCaching(): void
    {
        // Disable config caching
        config(['cache.default' => 'array']);
        
        // Disable view caching
        config(['view.compiled' => storage_path('framework/views')]);
        
        // Disable route caching
        config(['route.cache.enabled' => false]);
        
        // Disable event caching
        config(['event.cache.enabled' => false]);
        
        // Force clear all caches
        try {
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            \Artisan::call('cache:clear');
        } catch (\Exception $e) {
            // Ignore errors during boot
        }
    }
}
