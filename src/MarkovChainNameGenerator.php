<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use Closure;
use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MarkovChainNameGenerator
{
    /**
     * @var string The syllable delimiter
     */
    private $delimiter = '-';

    /**
     * @var int The minimum syllable length a generated name will have
     */
    private $minNumberOfSyllables = 2;

    /**
     * @var int The maximum syllable length a generated name will have
     */
    private $maxNumberOfSyllables = 3;

    /**
     * @var float Multiplier determining how many empty suffixes should be generated
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
        $generator = $this->generator(
            $this->delimiter,
            $this->minNumberOfSyllables,
            $this->maxNumberOfSyllables,
            $this->emptySuffixesMultiplier
        );

        return $generator($seedNames, $seedNameSuffixes, $numberOfNames);
    }

    /**
     * @param Collection $syllables
     * @return array
     */
    private function generateEmptyMatrix(Collection $syllables): array
    {
        return $syllables
            ->mapWithKeys(function ($syllable, $key) use ($syllables) {
                return [$syllable => $syllables->mapWithKeys(function ($syllable, $key) {
                    return [$syllable => 0];
                })];
            })
            ->map(function ($value) {
                return $value->toArray();
            })
            ->toArray();
    }

    /**
     * @param array $matrix
     * @param Collection $names
     * @param Collection $syllables
     * @param string $delimiter
     * @return array
     */
    private function fillMatrix(array $matrix, Collection $names, Collection $syllables, string $delimiter): array
    {
        $names->each(function ($name) use (&$matrix, $syllables, $delimiter) {
            $lex = collect(explode($delimiter, $name))->map(function ($l) {
                return strtolower($l);
            });
            $i = 0;

            while ($i < count($lex) - 1) {
                $syllableA = $lex->get($i);
                $syllableB = $lex->get($i + 1);

                $matrix[$syllableA][$syllableB] += 1;
                ++$i;
            }

            $keyA = $lex->get($lex->count() - 1);
            $keyB = $syllables->last();

            $matrix[$keyA][$keyB] += 1;
        });

        return $matrix;
    }

    /**
     * @param string $delimiter
     * @param int $minNumberOfSyllables
     * @param int $maxNumberOfSyllables
     * @param float $emptySuffixesMultiplier
     * @return Closure
     */
    private function generator(string $delimiter, int $minNumberOfSyllables, int $maxNumberOfSyllables, float $emptySuffixesMultiplier): Closure
    {
        if ($maxNumberOfSyllables < $minNumberOfSyllables) {
            throw new InvalidArgumentException('Maxmimum number of syllables must be greater or equal than the minimum number.');
        }

        return function (array $seedNames, array $seedNameSuffixes = [], int $numberOfNames = 1) use ($delimiter, $minNumberOfSyllables, $maxNumberOfSyllables, $emptySuffixesMultiplier) {
            if ($numberOfNames < 1) {
                throw new InvalidArgumentException('Number of names must be greater than 0.');
            }

            $names = collect($seedNames);
            $syllables = collect($seedNames)
                ->map(function ($seedName) use ($delimiter) {
                    return explode($delimiter, $seedName);
                })
                ->flatten()
                ->map(function ($syllable) {
                    return strtolower($syllable);
                })
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

            $matrix = $this->fillMatrix($this->generateEmptyMatrix($syllables), $names, $syllables, $delimiter);

            return $this->generateNamesFromMatrix($matrix, $syllables, $suffixes, $numberOfNames, $minNumberOfSyllables, $maxNumberOfSyllables);
        };
    }

    /**
     * @param string $name
     * @param string $initial
     * @param array $matrix
     * @param Collection $syllables
     * @param int $length
     * @return string
     */
    private function generateSyllables(string $name, string $initial, array $matrix, Collection $syllables, int $length): string
    {
        if ($length === 0) {
            return $name;
        }

        $foundSyllable = strtolower($this->searchForSyllable($initial, $matrix, $syllables));
        $newInitial = array_search(1, $matrix[$foundSyllable], true);
        $newName = $name . $foundSyllable;

        return $this->generateSyllables($newName, $newInitial, $matrix, $syllables, $length - 1);
    }

    /**
     * @param string $initial
     * @param array $matrix
     * @param Collection $syllables
     * @return string
     */
    private function searchForSyllable(string $initial, array $matrix, Collection $syllables): string
    {
        if (!in_array(1, $matrix[$initial], true)) {
            return $this->searchForSyllable($syllables->random(), $matrix, $syllables);
        }

        return $initial;
    }

    /**
     * @param array $matrix
     * @param int $numberOfNames
     * @param Collection $syllables
     * @param Collection $suffixes
     * @param int $minNumberOfSyllables
     * @param int $maxNumberOfSyllables
     * @return array
     */
    private function generateNamesFromMatrix(array $matrix, Collection $syllables, Collection $suffixes, int $numberOfNames, int $minNumberOfSyllables, int $maxNumberOfSyllables): array
    {
        return collect(range(1, $numberOfNames))
            ->map(function () use ($syllables, $matrix, $suffixes, $minNumberOfSyllables, $maxNumberOfSyllables) {
                $initial = $syllables->random();
                $length = random_int($minNumberOfSyllables, $maxNumberOfSyllables);

                $generatedName = $this->generateSyllables('', $initial, $matrix, $syllables, $length);

                return ucwords(trim($generatedName . $this->generateSuffix($suffixes)));
            })
            ->toArray();
    }

    /**
     * @param Collection $suffixes
     * @return string
     * @throws Exception
     */
    private function generateSuffix(Collection $suffixes): string
    {
        return ' ' . $suffixes->get(random_int(0, $suffixes->count() - 1));
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @param int $minNumberOfSyllables
     */
    public function setMinNumberOfSyllables(int $minNumberOfSyllables): void
    {
        if ($minNumberOfSyllables <= 0) {
            throw new InvalidArgumentException('Must be greater than 0.');
        }

        $this->minNumberOfSyllables = $minNumberOfSyllables;
    }

    /**
     * @param int $maxNumberOfSyllables
     */
    public function setMaxNumberOfSyllables(int $maxNumberOfSyllables): void
    {
        $this->maxNumberOfSyllables = $maxNumberOfSyllables;
    }

    /**
     * @param float $emptySuffixesMultiplier
     */
    public function setEmptySuffixesMultiplier(float $emptySuffixesMultiplier): void
    {
        $this->emptySuffixesMultiplier = $emptySuffixesMultiplier;
    }
}
