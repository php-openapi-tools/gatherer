<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataSets\TripleNestedSchema;

use OpenAPITools\Representation\Schema;
use OpenAPITools\Tests\Gatherer\SpecLoader;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function basename;

final class Test extends AsyncTestCase
{
    /** @test */
    public function tiple(): void
    {
        $representation = SpecLoader::loadSpec(basename(__DIR__));

        self::assertSame('Schema\\Name', $representation->schemas[0]->className);
        self::assertSame('Schema\\Basic', $representation->schemas[1]->className);

        self::assertInstanceOf(Schema::class, $representation->schemas[1]->properties[1]->type->payload);
        self::assertSame('Schema\\Basic\\Name', $representation->schemas[1]->properties[1]->type->payload->className);

        self::assertInstanceOf(Schema::class, $representation->schemas[1]->properties[1]->type->payload->properties[0]->type->payload);
        self::assertSame('Schema\\Basic\\Name\\Preferred', $representation->schemas[1]->properties[1]->type->payload->properties[0]->type->payload->className);

        self::assertInstanceOf(Schema::class, $representation->schemas[1]->properties[1]->type->payload->properties[1]->type->payload);
        self::assertSame('Schema\\Basic\\Name\\Full', $representation->schemas[1]->properties[1]->type->payload->properties[1]->type->payload->className);

        self::assertInstanceOf(Schema::class, $representation->schemas[1]->properties[1]->type->payload->properties[1]->type->payload->properties[0]->type->payload);
        self::assertSame('Schema\\Basic\\Name\\Full\\First', $representation->schemas[1]->properties[1]->type->payload->properties[1]->type->payload->properties[0]->type->payload->className);

        self::assertInstanceOf(Schema::class, $representation->schemas[1]->properties[1]->type->payload->properties[1]->type->payload->properties[1]->type->payload);
        self::assertSame('Schema\\Basic\\Name\\Full\\Middle', $representation->schemas[1]->properties[1]->type->payload->properties[1]->type->payload->properties[1]->type->payload->className);

        self::assertInstanceOf(Schema::class, $representation->schemas[1]->properties[1]->type->payload->properties[1]->type->payload->properties[2]->type->payload);
        self::assertSame('Schema\\Basic\\Name\\Full\\Last', $representation->schemas[1]->properties[1]->type->payload->properties[1]->type->payload->properties[2]->type->payload->className);
    }
}
