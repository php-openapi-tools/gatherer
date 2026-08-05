<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer;

use OpenAPITools\Gatherer\IntegerReturnerPretendingToBeARandomNumberGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IntegerReturnerPretendingToBeARandomNumberGeneratorTest extends TestCase
{
    #[Test]
    public function returnsConfiguredValue(): void
    {
        $generator = new IntegerReturnerPretendingToBeARandomNumberGenerator(19);

        self::assertSame(19, $generator->generate());
        self::assertSame(19, $generator->generate(0, 100));
        self::assertSame(19, $generator->seed());
        self::assertSame(19.0, $generator->max());
    }

    #[Test]
    public function capsValueAtMax(): void
    {
        $generator = new IntegerReturnerPretendingToBeARandomNumberGenerator(19);

        self::assertSame(10, $generator->generate(0, 10));
    }
}
