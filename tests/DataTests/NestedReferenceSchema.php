<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataTests;

use OpenAPITools\Representation\Representation;
use PHPUnit\Framework\Assert;

final class NestedReferenceSchema
{
    /** @api */
    public static function assert(Representation $representation): void
    {
        Assert::assertCount(4, $representation->schemas);
        Assert::assertSame('Schema\\Basic\\Name', $representation->schemas['Basic\\Name']->className);
        Assert::assertSame(
            'Schema\\Operations\\Root\\Response\\ApplicationJson\\Ok',
            $representation->schemas['Operations\\Root\\Response\\ApplicationJson\\Ok']->className,
        );
    }
}
