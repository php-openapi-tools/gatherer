<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataTests;

use OpenAPITools\Representation\Representation;
use OpenAPITools\Representation\Schema;
use PHPUnit\Framework\Assert;

final class TripleNestedSchema
{
    /** @api */
    public static function assert(Representation $representation): void
    {
        Assert::assertSame('Schema\\Name', $representation->schemas['Name']->className);
        Assert::assertSame('Schema\\Basic', $representation->schemas['Basic']->className);

        Assert::assertInstanceOf(Schema::class, $representation->schemas['Basic']->properties[1]->type->payload);
        Assert::assertSame('Schema\\Basic\\Name', $representation->schemas['Basic']->properties[1]->type->payload->className);

        Assert::assertInstanceOf(Schema::class, $representation->schemas['Basic']->properties[1]->type->payload->properties[0]->type->payload);
        Assert::assertSame('Schema\\Basic\\Name\\Preferred', $representation->schemas['Basic']->properties[1]->type->payload->properties[0]->type->payload->className);

        Assert::assertInstanceOf(Schema::class, $representation->schemas['Basic']->properties[1]->type->payload->properties[1]->type->payload);
        Assert::assertSame('Schema\\Basic\\Name\\Full', $representation->schemas['Basic']->properties[1]->type->payload->properties[1]->type->payload->className);

        Assert::assertInstanceOf(Schema::class, $representation->schemas['Basic']->properties[1]->type->payload->properties[1]->type->payload->properties[0]->type->payload);
        Assert::assertSame('Schema\\Basic\\Name\\Full\\First', $representation->schemas['Basic']->properties[1]->type->payload->properties[1]->type->payload->properties[0]->type->payload->className);

        Assert::assertInstanceOf(Schema::class, $representation->schemas['Basic']->properties[1]->type->payload->properties[1]->type->payload->properties[1]->type->payload);
        Assert::assertSame('Schema\\Basic\\Name\\Full\\Middle', $representation->schemas['Basic']->properties[1]->type->payload->properties[1]->type->payload->properties[1]->type->payload->className);

        Assert::assertInstanceOf(Schema::class, $representation->schemas['Basic']->properties[1]->type->payload->properties[1]->type->payload->properties[2]->type->payload);
        Assert::assertSame('Schema\\Basic\\Name\\Full\\Last', $representation->schemas['Basic']->properties[1]->type->payload->properties[1]->type->payload->properties[2]->type->payload->className);
    }
}
