<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use ReverseRegex\Random\GeneratorInterface;

// phpcs:disable
final readonly class IntegerReturnerPretendingToBeARandomNumberGenerator implements GeneratorInterface
{
    public function __construct(private int $randomNumber)
    {

    }

    /** @phpstan-ignore ergebnis.noParameterWithNullDefaultValue (third-party GeneratorInterface requires null default) */
    public function generate(mixed $min = 0, mixed $max = null): int
    {
        $maxValue = $max ?? \PHP_INT_MAX;

        return $this->randomNumber > $maxValue ? $maxValue : $this->randomNumber;
    }

    public function seed(mixed $seed = 0): int
    {
        return $this->randomNumber;
    }

    public function max(): float
    {
        return (float) $this->randomNumber;
    }
}
