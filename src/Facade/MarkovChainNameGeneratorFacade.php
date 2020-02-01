<?php

namespace Kaishiyoku\MarkovChainNameGenerator\Facade;

use Illuminate\Support\Facades\Facade;
use Kaishiyoku\MarkovChainNameGenerator\MarkovChainNameGenerator;

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
