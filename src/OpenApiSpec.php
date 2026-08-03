<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema as OpenApiSchema;
use RuntimeException;

use function array_values;
use function is_array;

final class OpenApiSpec
{
    /** @throws RuntimeException */
    public static function schema(Reference|OpenApiSchema $schema): OpenApiSchema
    {
        if ($schema instanceof OpenApiSchema) {
            return $schema;
        }

        throw new RuntimeException('Expected OpenAPI schema, got ' . $schema::class);
    }

    /** @throws RuntimeException */
    public static function objectSchema(Reference|OpenApiSchema $schema): OpenApiSchema
    {
        $schema = self::schema($schema);

        if ($schema->type === 'array') {
            if ($schema->items === null) {
                throw new RuntimeException('Array schema is missing items');
            }

            return self::schema($schema->items);
        }

        return $schema;
    }

    /** @throws RuntimeException */
    public static function parameter(Parameter|Reference $parameter): Parameter
    {
        if ($parameter instanceof Parameter) {
            return $parameter;
        }

        throw new RuntimeException('Expected OpenAPI parameter, got ' . $parameter::class);
    }

    /** @return list<string> */
    public static function requiredProperties(OpenApiSchema $schema): array
    {
        return array_values($schema->required ?? []);
    }

    /** @return list<string> */
    public static function schemaTypeList(OpenApiSchema $schema): array
    {
        $type = $schema->type;

        if (is_array($type)) {
            return array_values($type);
        }

        if ($type !== '') {
            return [$type];
        }

        return ['object'];
    }
}
