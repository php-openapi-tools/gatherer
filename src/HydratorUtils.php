<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use OpenAPITools\Representation\Property\Type;
use OpenAPITools\Representation\Schema;

final class HydratorUtils
{
    /** @return iterable<Schema> */
    public static function listSchemas(Schema $schema): iterable
    {
        yield $schema;

        foreach ($schema->properties as $property) {
            yield from self::listSchemasFromPropertyType($property->type);
        }
    }

    /** @return iterable<Schema> */
    private static function listSchemasFromPropertyType(Type $propertyType): iterable
    {
        if ($propertyType->payload instanceof Schema) {
            yield from self::listSchemas($propertyType->payload);
        } elseif ($propertyType->payload instanceof Type) {
            yield from self::listSchemasFromPropertyType($propertyType->payload);
        }
    }
}
