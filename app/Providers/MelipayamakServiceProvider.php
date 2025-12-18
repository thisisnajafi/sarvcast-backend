<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Melipayamak\MelipayamakApi;

class MelipayamakServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('melipayamak', function ($app) {
            $username = config('services.melipayamk.token');
            $password = config('services.melipayamk.token');
            
            return new MelipayamakApi($username, $password);
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
