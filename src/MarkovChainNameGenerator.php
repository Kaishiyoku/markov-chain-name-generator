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
     * @var string[]
     */
    private array $seedNames = [];

    /**
     * @var string[]
     */
    private array $seedNameSuffixes = [];

    public function setSeed(Collection|array $seedNames, Collection|array $seedNameSuffixes = [])
    {
        $this->seedNames = $seedNames instanceof Collection ? $seedNames->toArray() : $seedNames;
        $this->seedNameSuffixes = $seedNameSuffixes instanceof Collection ? $seedNameSuffixes->toArray() : $seedNameSuffixes;
    }

    /**
     * @return string[]
     */
    public function generate(int $numberOfNames = 1): array
    {
//        if (count($this->seedNames) === 0) {
//            throw new InvalidArgumentException('You must provide at least one seed name.');
//        }

        $generator = $this->generator(
            $this->delimiter,
            $this->minNumberOfSyllables,
            $this->maxNumberOfSyllables,
            $this->emptySuffixesMultiplier
        );

        return $generator($numberOfNames);
    }

    private function generateEmptyMatrix(Collection $syllables): array
    {
        return $syllables
            ->mapWithKeys(fn ($syllable, $key) => [
                $syllable => $syllables->mapWithKeys(fn (
                    $syllable,
                    $key
                ) => [$syllable => 0])
            ])
            ->map(fn ($value) => $value->toArray())
            ->toArray();
    }

    private function fillMatrix(array $matrix, Collection $names, Collection $syllables, string $delimiter): array
    {
        $names->each(function ($name) use (&$matrix, $syllables, $delimiter) {
            $lex = collect(explode($delimiter, $name))->map(fn ($l) => strtolower($l));

            if ($lex->count() < 2) {
                throw new InvalidArgumentException('At least two syllables must be present.');
            }

            collect(range(0, $lex->count() - 2))->each(function ($i) use ($lex, &$matrix) {
                $syllableA = $lex->get($i);
                $syllableB = $lex->get($i + 1);

                $matrix[$syllableA][$syllableB] += 1;
            });

            $keyA = $lex->get($lex->count() - 1);
            $keyB = $syllables->last();

            $matrix[$keyA][$keyB] += 1;
        });

        return $matrix;
    }

    private function generator(
        string $delimiter,
        int $minNumberOfSyllables,
        int $maxNumberOfSyllables,
        float $emptySuffixesMultiplier
    ): Closure {
        if ($maxNumberOfSyllables < $minNumberOfSyllables) {
            throw new InvalidArgumentException('Maxmimum number of syllables must be greater or equal than the minimum number.');
        }

        return function (int $numberOfNames = 1) use (
            $delimiter,
            $minNumberOfSyllables,
            $maxNumberOfSyllables,
            $emptySuffixesMultiplier
        ) {
            if ($numberOfNames < 1) {
                throw new InvalidArgumentException('Number of names must be greater than 0.');
            }

            $names = collect($this->seedNames);
            $syllables = collect($this->seedNames)
                ->map(fn ($seedName) => explode($delimiter, $seedName))
                ->flatten()
                ->map(fn ($syllable) => strtolower($syllable))
                ->unique();

            // preserve empty suffixes, too, so that not every name has a suffix
            $suffixes = collect(range(0, count($this->seedNameSuffixes) * $emptySuffixesMultiplier))
                ->map(fn ($value, $key) => $key < count($this->seedNameSuffixes) ? $this->seedNameSuffixes[$key] : '')
                ->shuffle();

            $matrix = $this->fillMatrix($this->generateEmptyMatrix($syllables), $names, $syllables, $delimiter);

            return $this->generateNamesFromMatrix($matrix, $syllables, $suffixes, $numberOfNames, $minNumberOfSyllables,
                $maxNumberOfSyllables);
        };
    }

    private function generateSyllables(
        string $name,
        string $initial,
        array $matrix,
        Collection $syllables,
        int $length
    ): string {
        if ($length === 0) {
            return $name;
        }

        $foundSyllable = strtolower($this->searchForSyllable($initial, $matrix, $syllables));
        $newInitial = array_search(1, $matrix[$foundSyllable], true);
        $newName = $name.$foundSyllable;

        return $this->generateSyllables($newName, $newInitial, $matrix, $syllables, $length - 1);
    }

    private function searchForSyllable(string $initial, array $matrix, Collection $syllables): string
    {
        if (! in_array(1, $matrix[$initial], true)) {
            return $this->searchForSyllable($syllables->random(), $matrix, $syllables);
        }

        return $initial;
    }

    private function generateNamesFromMatrix(
        array $matrix,
        Collection $syllables,
        Collection $suffixes,
        int $numberOfNames,
        int $minNumberOfSyllables,
        int $maxNumberOfSyllables
    ): array {
        return collect(range(1, $numberOfNames))
            ->map(function () use ($syllables, $matrix, $suffixes, $minNumberOfSyllables, $maxNumberOfSyllables) {
                $initial = $syllables->random();
                $length = random_int($minNumberOfSyllables, $maxNumberOfSyllables);

                $generatedName = $this->generateSyllables('', $initial, $matrix, $syllables, $length);

                return ucwords(trim($generatedName.$this->generateSuffix($suffixes)));
            })
            ->toArray();
    }

    /**
     * @param  Collection<string>  $suffixes
     * @throws Exception
     */
    private function generateSuffix(Collection $suffixes): string
    {
        return " {$suffixes->get(random_int(0, $suffixes->count() - 1))}";
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
