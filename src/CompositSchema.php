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

final class CompositSchema
{
    public static function gather(
        string $className,
        baseSchema $schema,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
    ): Representation\Schema {
        $className  = Utils::className($className);
        $isArray    = $schema->type === 'array';
        $properties = [];
        $example    = [];

        if ($isArray) {
            $schema = $schema->items;
        }

        foreach ($schema->properties as $propertyName => $property) {
            $gatheredProperty = Property::gather(
                $className,
                (string) $propertyName,
                in_array(
                    (string) $propertyName,
                    $schema->required ?? [],
                    false,
                ),
                $property,
                $schemaRegistry,
                $contractRegistry,
            );
            $properties[]     = $gatheredProperty;

            $example[$gatheredProperty->sourceName] = $gatheredProperty->example->raw;

            foreach (['examples', 'example'] as $examplePropertyName) {
                if (array_key_exists($gatheredProperty->sourceName, $example)) {
                    break;
                }

                if (! property_exists($schema, $examplePropertyName) || ! is_array($schema->$examplePropertyName) || ! array_key_exists($gatheredProperty->sourceName, $schema->$examplePropertyName)) {
                    continue;
                }

                $example[$gatheredProperty->sourceName] = $schema->$examplePropertyName[$gatheredProperty->sourceName];
            }

            foreach ($property->enum ?? [] as $value) {
                $example[$gatheredProperty->sourceName] = $value;
                break;
            }

            if ($example[$gatheredProperty->sourceName] !== null) {
                continue;
            }

            if (
                in_array(
                    (string) $propertyName,
                    $schema->required ?? [],
                    false,
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
                    $contractRegistry->get($schema, 'Contract\\' . $className),
                    $properties,
                ),
            ],
            'Error\\' . $className,
            'ErrorSchemas\\' . $className,
            $schema->title ?? '',
            $schema->description ?? '',
            $example,
            $properties,
            $schema,
            $isArray,
            ($schema->type === null ? ['object'] : (is_array($schema->type) ? $schema->type : [$schema->type])),
            [],
        );
    }
}
