<?php

declare(strict_types=1);

namespace Joranski\Addressing;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Joranski\Addressing\Console\SyncCountriesCommand;
use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Verifiers\CachedVerifier;
use Joranski\Addressing\Verifiers\GoogleAddressVerifier;
use Joranski\Addressing\Verifiers\NullVerifier;

final class AddressingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/addressing.php'), 'addressing');

        $this->app->singleton(AddressVerifier::class, function (Application $app): AddressVerifier {
            return match ((string) config('addressing.verifier', 'google')) {
                'null' => new NullVerifier,
                'google' => new CachedVerifier(
                    inner: new GoogleAddressVerifier,
                    cache: Cache::store((string) config('addressing.cache.store', 'database')),
                    ttls: (array) config('addressing.cache.ttl', []),
                ),
                default => new CachedVerifier(
                    inner: new GoogleAddressVerifier,
                    cache: Cache::store((string) config('addressing.cache.store', 'database')),
                    ttls: (array) config('addressing.cache.ttl', []),
                ),
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));
        $this->loadViewsFrom($this->packagePath('resources/views'), 'addressing');

        $this->publishes([
            $this->packagePath('config/addressing.php') => config_path('addressing.php'),
        ], 'addressing-config');

        $this->publishes([
            $this->packagePath('database/migrations') => database_path('migrations/vendor/joranski/laravel-addressing'),
        ], 'addressing-migrations');

        $this->publishes([
            $this->packagePath('resources/views') => resource_path('views/vendor/addressing'),
        ], 'addressing-views');

        $this->publishes([
            $this->packagePath('resources/extras.json') => resource_path('addressing/extras.json'),
        ], 'addressing-resources');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->packagePath('stubs/AddressPolicy.php.stub') => app_path('Policies/AddressPolicy.php'),
            ], 'addressing-policy');

            $this->publishes([
                $this->packagePath('stubs/AddressPolicyShield.php.stub') => app_path('Policies/AddressPolicy.php'),
            ], 'addressing-policy-shield');

            $this->commands([
                SyncCountriesCommand::class,
            ]);
        }
    }

    private function packagePath(string $relative): string
    {
        return dirname(__DIR__).'/'.$relative;
    }
}
