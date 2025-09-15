<?php

namespace App\Providers;

use App\Services\FileService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FileService::class, function ($app) {
            return new FileService();
        });
        
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService();
        });
        
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
        
        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
