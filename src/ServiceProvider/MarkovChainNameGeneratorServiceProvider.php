<?php

namespace Kaishiyoku\MarkovChainNameGenerator\ServiceProvider;

use Kaishiyoku\MarkovChainNameGenerator\MarkovChainNameGenerator;

class MarkovChainNameGeneratorServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MarkovChainNameGenerator::class, function ($app) {
            return new MarkovChainNameGenerator();
        });
    }
}
