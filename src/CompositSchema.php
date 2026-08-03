<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\Schema as baseSchema;
use OpenAPITools\Registry;
use OpenAPITools\Representation;
use OpenAPITools\Utils\Utils;

use function array_key_exists;
use function in_array;
use function is_array;
use function property_exists;

/** @api */
final class CompositSchema
{
    public static function gather(
        string $className,
        baseSchema $schema,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
    ): Representation\Schema {
        $className    = Utils::className($className);
        $isArray      = $schema->type === 'array';
        $objectSchema = OpenApiSpec::objectSchema($schema);
        $properties   = [];
        $example      = [];

        foreach ($objectSchema->properties ?? [] as $propertyName => $property) {
            $propertySchema   = OpenApiSpec::schema($property);
            $gatheredProperty = Property::gather(
                $className,
                (string) $propertyName,
                in_array(
                    (string) $propertyName,
                    OpenApiSpec::requiredProperties($objectSchema),
                    true,
                ),
                $propertySchema,
                $schemaRegistry,
                $contractRegistry,
            );
            $properties[]     = $gatheredProperty;

            $example[$gatheredProperty->sourceName] = $gatheredProperty->example->raw;

            foreach (['examples', 'example'] as $examplePropertyName) {
                if (array_key_exists($gatheredProperty->sourceName, $example)) {
                    break;
                }

                if (! property_exists($objectSchema, $examplePropertyName) || ! is_array($objectSchema->$examplePropertyName) || ! array_key_exists($gatheredProperty->sourceName, $objectSchema->$examplePropertyName)) {
                    continue;
                }

                $example[$gatheredProperty->sourceName] = $objectSchema->$examplePropertyName[$gatheredProperty->sourceName];
            }

            foreach ($propertySchema->enum ?? [] as $value) {
                $example[$gatheredProperty->sourceName] = $value;
                break;
            }

            if ($example[$gatheredProperty->sourceName] !== null) {
                continue;
            }

            if (
                in_array(
                    (string) $propertyName,
                    OpenApiSpec::requiredProperties($objectSchema),
                    true,
                )
            ) {
                continue;
            }

            unset($example[$gatheredProperty->sourceName]);
        }

        return new Representation\Schema(
            'Schema\\' . $className,
            [
                new Representation\Contract(
                    $contractRegistry->get($objectSchema, 'Contract\\' . $className),
                    $properties,
                ),
            ],
            'Error\\' . $className,
            'ErrorSchemas\\' . $className,
            $objectSchema->title ?? '',
            $objectSchema->description ?? '',
            $example,
            $properties,
            $schema,
            $isArray,
            OpenApiSpec::schemaTypeList($objectSchema),
            [],
        );
    }
}
