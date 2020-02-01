<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use Exception;

class MarkovChainNameGenerator
{
    /**
     * @param array $names
     * @param array $nameSuffixes
     * @param int $count
     * @return array
     * @throws Exception
     */
    public function generateNames(array $names, array $nameSuffixes= [], int $count = 1): array
    {
        $suffixes = collect(range(0, count($nameSuffixes) * 2 - 1))->map(function ($value, $key) use ($nameSuffixes) {
            if ($key < count($nameSuffixes)) {
                return $nameSuffixes[$key];
            }

            return '';
        })->shuffle()->toArray();

        $totalSyllables = 0;
        $syllables = [];

        foreach ($names as $n) {
            $lex = explode('-', $n);
            $totalSyllables += count($lex);

            foreach ($lex as $l) {
                if (!array_search($l, $syllables)) {
                    $syllables[] = $l;
                }
            }
        }

        $divIndex = count($syllables) / $totalSyllables;

        $size = count($syllables) + 1;

        $freq = collect(array_fill(0, $size, []))->map(function ($f) use ($size) {
            return array_fill(0, $size, 0);
        })->toArray();

        foreach ($names as $n) {
            $lex = explode('-', $n);
            $i = 0;

            while ($i < count($lex) - 1) {
                $keyA = array_search($lex[$i], $syllables);
                $keyB = array_search($lex[$i + 1], $syllables);

                $freq[$keyA][$keyB] += 1;
                $i += 1;
            }

            $freq[array_search($lex[count($lex) - 1], $syllables)][$size - 1] += 1;
        }

        $nameCount = 0;
        $name = '';

        $generatedNames = [];

        while ($nameCount < $count) {
            $length = random_int(2, 3);
            $initial = random_int(0, $size - 2);

            while ($length > 0) {
                while (!array_search(1, $freq[$initial])) {
                    $initial = random_int(0, $size - 2);
                }

                $name .= $syllables[$initial];
                $initial = array_search(1, $freq[$initial]);
                $length -= 1;
            }

            $suffixIndex = random_int(0, count($suffixes) - 1);
            $name .= ' ';
            $name .= $suffixes[$suffixIndex];

            $generatedNames[] = $name;

            $name = '';
            $nameCount += 1;
        }

        return array_map(function ($name) {
            return ucwords(trim($name));
        }, $generatedNames);
    }
}
