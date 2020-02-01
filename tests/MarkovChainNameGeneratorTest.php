<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use PHPUnit\Framework\TestCase;

/**
 * @covers HeraRssCrawler
 */
class MarkovChainNameGeneratorTest extends TestCase
{
    public function testGenerateNames()
    {
        $names = [
            'Ap-pi-us',
            'Au-lus',
            'De-ci-mus',
            'Gai-us',
            'Gna-eus',
            'Ka-e-so',
            'Lu-ci-us',
            'Ma-mer-cus',
            'Ma-ni-us',
            'Mar-cus',
            'Nu-me-ri-us',
            'Pu-bli-us',
            'Quin-tus',
            'Ser-vi-us',
            'Spu-ri-us',
            'Ti-be-ri-us',
            'Ti-tus',
        ];

        $suffixes = [
            'I',
            'II',
            'III'
        ];

        $markovChainNameGenerator = new MarkovChainNameGenerator();
        $generatedNames = $markovChainNameGenerator->generateNames($names, $suffixes, 10);

        $this->assertCount(10, $generatedNames);

        $name = 'AAA';

        $this->assertRegExp('/^Aa*$/', $markovChainNameGenerator->generateNames([$name])[0]);
    }
}
