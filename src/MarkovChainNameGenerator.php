<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use Closure;
use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MarkovChainNameGenerator
{
    /**
     * The syllable delimiter
     */
    private string $delimiter = '-';

    /**
     * The minimum syllable length a generated name will have
     */
    private int $minNumberOfSyllables = 2;

    /**
     * The maximum syllable length a generated name will have
     */
    private int $maxNumberOfSyllables = 3;

    /**
     * Multiplier determining how many empty suffixes should be generated
     */
    private float $emptySuffixesMultiplier = 2;

    /**
     * @param string[] $seedNames
     * @param string[] $seedNameSuffixes
     * @param int $numberOfNames
     * @return string[]
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
            ->mapWithKeys(fn($syllable, $key) => [$syllable => $syllables->mapWithKeys(fn($syllable, $key) => [$syllable => 0])])
            ->map(fn($value) => $value->toArray())
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
            $lex = collect(explode($delimiter, $name))->map(fn($l) => strtolower($l));

            if ($lex->count() < 2) {
                throw new InvalidArgumentException('At least two syllables must be present.');
            }

            collect(range(0, $lex->count() - 2))->each(function ($i) use ($lex, &$matrix) {
                $syllableA = $lex->get($i);
                $syllableB = $lex->get($i + 1);

                try {
                    $matrix[$syllableA][$syllableB] += 1;
                } catch (\Throwable $e) {
                    print_r(compact('syllableA', 'syllableB'));
                }
            });

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
                ->map(fn($seedName) => explode($delimiter, $seedName))
                ->flatten()
                ->map(fn($syllable) => strtolower($syllable))
                ->unique();

            // preserve empty suffixes, too, so that not every name has a suffix
            $suffixes = collect(range(0, count($seedNameSuffixes) * $emptySuffixesMultiplier))
                ->map(fn($value, $key) => $key < count($seedNameSuffixes) ? $seedNameSuffixes[$key] : '')
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
     * @param Collection<string> $suffixes
     * @return string
     * @throws Exception
     */
    private function generateSuffix(Collection $suffixes): string
    {
        return ' ' . $suffixes->get(random_int(0, $suffixes->count() - 1));
    }

    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    public function setMinNumberOfSyllables(int $minNumberOfSyllables): void
    {
        if ($minNumberOfSyllables <= 0) {
            throw new InvalidArgumentException('Must be greater than 0.');
        }

        $this->minNumberOfSyllables = $minNumberOfSyllables;
    }

    public function setMaxNumberOfSyllables(int $maxNumberOfSyllables): void
    {
        $this->maxNumberOfSyllables = $maxNumberOfSyllables;
    }

    public function setEmptySuffixesMultiplier(float $emptySuffixesMultiplier): void
    {
        $this->emptySuffixesMultiplier = $emptySuffixesMultiplier;
    }
}
