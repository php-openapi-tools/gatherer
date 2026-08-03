<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataTests;

use OpenAPITools\Representation\Representation;
use PHPUnit\Framework\Assert;

final class NestedSchema
{
    /** @api */
    public static function assert(Representation $representation): void
    {
        Assert::assertCount(3, $representation->schemas);
        Assert::assertSame('Schema\\Basic\\Name', $representation->schemas['Basic\\Name']->className);
    }
}
