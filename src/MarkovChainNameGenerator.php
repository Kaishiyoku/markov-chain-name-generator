<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use Closure;

class MarkovChainNameGenerator
{
    /**
     * @var string The syllable delimiter
     */
    private $delimiter = '-';

    /**
     * @var int The maximum syllable length a generated name will have
     */
    private $maxNumberOfSyllables = 3;

    /**
     * @var int Multiplier determining how many empty suffixes should be generated
     */
    private $emptySuffixesMultiplier = 2;

    /**
     * @param array $seedNames
     * @param array $seedNameSuffixes
     * @param int $numberOfNames
     * @return array
     */
    public function generateNames(array $seedNames, array $seedNameSuffixes = [], int $numberOfNames = 1): array
    {
        $generator = $this->generator($this->getDelimiter(), $this->getMaxNumberOfSyllables(), $this->getEmptySuffixesMultiplier());

        return $generator($seedNames, $seedNameSuffixes, $numberOfNames);
    }

    /**
     * @param string $delimiter
     * @param int $maxNumberOfSyllables
     * @param int $emptySuffixesMultiplier
     * @return Closure
     */
    private function generator(string $delimiter, int $maxNumberOfSyllables, int $emptySuffixesMultiplier): Closure
    {
        return function (array $seedNames, array $seedNameSuffixes = [], int $numberOfNames = 1) use ($delimiter, $maxNumberOfSyllables, $emptySuffixesMultiplier) {
            $names = collect($seedNames);
            $syllables = collect($seedNames)
                ->map(function ($seedName) use ($delimiter) {
                    return explode($delimiter, $seedName);
                })
                ->flatten()
                ->unique();

            // preserve empty suffixes, too, so that not every name has a suffix
            $suffixes = collect(range(0, count($seedNameSuffixes) * $emptySuffixesMultiplier))
                ->map(function ($value, $key) use ($seedNameSuffixes) {
                    if ($key < count($seedNameSuffixes)) {
                        return $seedNameSuffixes[$key];
                    }

                    return '';
                })
                ->shuffle();

            $matrix = $syllables->mapWithKeys(function ($syllable, $key) use ($syllables) {
                return [$syllable => $syllables->mapWithKeys(function ($syllable, $key) {
                    return [$syllable => 0];
                })];
            });

            $matrix = $matrix->map(function ($a) {
                return $a->toArray();
            })->toArray();

            $names->each(function ($name) use (&$matrix, $syllables, $delimiter) {
                $lex = explode($delimiter, $name);
                $i = 0;

                while ($i < count($lex) - 1) {
                    $syllableA = $lex[$i];
                    $syllableB = $lex[$i + 1];

                    $matrix[$syllableA][$syllableB] += 1;
                    ++$i;
                }

                $keyA = $lex[count($lex) - 1];
                $keyB = $syllables->last();

                $matrix[$keyA][$keyB] += 1;
            });

            $generatedNames = collect(range(1, $numberOfNames))
                ->map(function ($i) use ($syllables, $matrix, $suffixes, $maxNumberOfSyllables) {
                    $name = '';
                    $length = random_int(2, $maxNumberOfSyllables);
                    $initial = $syllables->random();

                    while ($length > 0) {
                        while (!in_array(1, $matrix[$initial], true)) {
                            $initial = $syllables->random();
                        }

                        $name .= strtolower($initial);
                        $initial = array_search(1, $matrix[$initial], true);
                        --$length;
                    }

                    $suffixIndex = random_int(0, $suffixes->count() - 1);
                    $name .= ' ';
                    $name .= $suffixes->get($suffixIndex);

                    return $name;
                });

            return $generatedNames
                ->map(function ($generatedName) {
                    return ucwords(trim($generatedName));
                })
                ->toArray();
        };
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return int
     */
    public function getMaxNumberOfSyllables(): int
    {
        return $this->maxNumberOfSyllables;
    }

    /**
     * @param int $maxNumberOfSyllables
     */
    public function setMaxNumberOfSyllables(int $maxNumberOfSyllables): void
    {
        $this->maxNumberOfSyllables = $maxNumberOfSyllables;
    }

    /**
     * @return int
     */
    public function getEmptySuffixesMultiplier(): int
    {
        return $this->emptySuffixesMultiplier;
    }

    /**
     * @param int $emptySuffixesMultiplier
     */
    public function setEmptySuffixesMultiplier(int $emptySuffixesMultiplier): void
    {
        $this->emptySuffixesMultiplier = $emptySuffixesMultiplier;
    }
}
