<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

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
