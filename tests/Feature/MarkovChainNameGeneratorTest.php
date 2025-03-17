<?php

use Kaishiyoku\MarkovChainNameGenerator\MarkovChainNameGenerator;

it('generates names', function () {
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
        'III',
    ];

    $markovChainNameGenerator = new MarkovChainNameGenerator();
    $markovChainNameGenerator->setSeed($names, $suffixes);
    $generatedNames = $markovChainNameGenerator->generate(10);
    expect($generatedNames)->toHaveCount(10);

    $markovChainNameGenerator->setSeed(['AAA-BBB']);
    expect($markovChainNameGenerator->generate()[0])->toMatch('/^[AaBb]*$/');

    $markovChainNameGenerator->setSeed(['AAA*BBB']);
    $markovChainNameGenerator->setDelimiter('*');
    $markovChainNameGenerator->setEmptySuffixesMultiplier(10);
    $markovChainNameGenerator->setMaxNumberOfSyllables(9);
    $markovChainNameGenerator->setMaxNumberOfSyllables(10);
    expect($markovChainNameGenerator->generate()[0])->toMatch('/^[AaBb]*$/');
});