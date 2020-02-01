<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use Illuminate\Support\Facades\Facade;

class MarkovChainNameGeneratorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return MarkovChainNameGenerator::class;
    }
}
