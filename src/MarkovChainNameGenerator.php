<?php

namespace Kaishiyoku\MarkovChainNameGenerator;

use Closure;
use Exception;
use Illuminate\Support\Collection;

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
        $generator = $this->generator(
            $this->getDelimiter(),
            $this->getMaxNumberOfSyllables(),
            $this->getEmptySuffixesMultiplier()
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

        return $matrix;
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

            $matrix = $this->fillMatrix($this->generateEmptyMatrix($syllables), $names, $syllables, $delimiter);

            return $this->generateNamesFromMatrix($matrix, $syllables, $suffixes, $numberOfNames, $maxNumberOfSyllables);
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
     * @param int $maxNumberOfSyllables
     * @return array
     */
    private function generateNamesFromMatrix(array $matrix, Collection $syllables, Collection $suffixes, int $numberOfNames, int $maxNumberOfSyllables): array
    {
        return collect(range(1, $numberOfNames))
            ->map(function ($i) use ($syllables, $matrix, $suffixes, $maxNumberOfSyllables) {
                $initial = $syllables->random();
                $length = random_int(2, $maxNumberOfSyllables);

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
