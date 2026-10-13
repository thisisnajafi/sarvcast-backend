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
            if ($app->environment('testing') || in_array((string) config('sms.driver'), ['log', 'fake', 'array'], true)) {
                return new class {
                    public function __call(string $name, array $arguments): never
                    {
                        throw new \RuntimeException(
                            'Melipayamak client is disabled during tests (SMS driver is log). Refused '.$name.'().'
                        );
                    }
                };
            }

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
