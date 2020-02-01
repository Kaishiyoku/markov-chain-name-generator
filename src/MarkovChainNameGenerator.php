<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use Exception;

class MarkovChainNameGenerator
{
    /**
     * @param array $seedNames
     * @param array $seedNameSuffixes
     * @param int $count
     * @return array
     * @throws Exception
     */
    public function generateNames(array $seedNames, array $seedNameSuffixes= [], int $count = 1): array
    {
        $names = collect($seedNames);
        $suffixes = collect(range(0, count($seedNameSuffixes) * 2 - 1))->map(function ($value, $key) use ($seedNameSuffixes) {
            if ($key < count($seedNameSuffixes)) {
                return $seedNameSuffixes[$key];
            }

            return '';
        })->shuffle()->toArray();

        $totalSyllables = 0;
        $syllables = [];

        $names->each(function ($name) use (&$totalSyllables, &$syllables) {
            $lex = collect(explode('-', $name));
            $totalSyllables += count($lex);

            $lex->each(function ($l) use (&$syllables) {
                if (!in_array($l, $syllables, true)) {
                    $syllables[] = $l;
                }
            });
        });

        $divIndex = count($syllables) / $totalSyllables;

        $size = count($syllables) + 1;

        $freq = collect(array_fill(0, $size, []))->map(function ($f) use ($size) {
            return array_fill(0, $size, 0);
        })->toArray();

        foreach ($names as $n) {
            $lex = explode('-', $n);
            $i = 0;

            while ($i < count($lex) - 1) {
                $keyA = array_search($lex[$i], $syllables, true);
                $keyB = array_search($lex[$i + 1], $syllables, true);

                $freq[$keyA][$keyB] += 1;
                ++$i;
            }

            $freq[array_search($lex[count($lex) - 1], $syllables, true)][$size - 1] += 1;
        }

        $nameCount = 0;
        $name = '';

        $generatedNames = collect();

        while ($nameCount < $count) {
            $length = random_int(2, 3);
            $initial = random_int(0, $size - 2);

            while ($length > 0) {
                while (!in_array(1, $freq[$initial], true)) {
                    $initial = random_int(0, $size - 2);
                }

                $name .= strtolower($syllables[$initial]);
                $initial = in_array(1, $freq[$initial], true);
                --$length;
            }

            $suffixIndex = random_int(0, count($suffixes) - 1);
            $name .= ' ';
            $name .= $suffixes[$suffixIndex];

            $generatedNames->add($name);

            $name = '';
            ++$nameCount;
        }

        return $generatedNames->map(function ($name) {
            return ucwords(trim($name));
        })->toArray();
    }
}
