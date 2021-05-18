<?php

namespace Klik\SimpleProcess;

use Illuminate\Support\ServiceProvider;
use Klik\SimpleProcess\Console\DumpProcess;

class SimpleProcessServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DumpProcess::class,
            ]);
        }
    }
}
