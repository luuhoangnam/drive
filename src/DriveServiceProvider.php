<?php

namespace Namest\Drive;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Namest\Drive\Contracts\Drive as DriveContract;

/**
 * Class DriveServiceProvider
 *
 * @author  Nam Hoang Luu <nam@mbearvn.com>
 * @package Namest\Drive
 *
 */
class DriveServiceProvider extends ServiceProvider
{
    /**
     * Boot up resources
     */
    public function boot()
    {
        // Publish a config file
        $this->publishes([
            __DIR__ . '/../config/drive.php' => config_path('drive.php')
        ], 'config');

        // Publish your migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => base_path('/database/migrations')
        ], 'migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('drive', function () {
            return $this->app->make(Drive::class);
        });

        $this->app->singleton(DriveContract::class, function () {
            return $this->app->make('drive');
        });
    }
}
