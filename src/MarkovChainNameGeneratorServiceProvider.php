<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use Illuminate\Support\ServiceProvider;

class MarkovChainNameGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MarkovChainNameGenerator::class, function ($app) {
            return new MarkovChainNameGenerator;
        });
    }
}
