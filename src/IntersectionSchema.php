<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\Schema as baseSchema;
use OpenAPITools\Registry;
use OpenAPITools\Representation\Contract;
use OpenAPITools\Representation\Schema;
use OpenAPITools\Utils\Utils;

use function array_key_exists;
use function in_array;
use function is_array;
use function property_exists;

final class IntersectionSchema
{
    public static function gather(
        string $className,
        baseSchema $baseProperty,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
    ): Schema {
        $className  = Utils::className($className);
        $contracts  = [];
        $properties = [];
        $example    = [];

        foreach ($baseProperty->allOf as $schema) {
            $gatheredProperties = [];
            foreach ($schema->properties as $propertyName => $property) {
                $gatheredProperty = $gatheredProperties[(string) $propertyName]                            = Property::gather(
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

                if (
                    in_array(
                        (string) $propertyName,
                        $baseProperty->required ?? [],
                        false,
                    )
                ) {
                    continue;
                }

                unset($example[$gatheredProperty->sourceName]);
            }

            $contracts[] = new Contract(
                $contractRegistry->get($schema, 'Contract\\' . $className . '\\' . $schema->title),
                $gatheredProperties,
            );

            $properties = [...$properties, ...$gatheredProperties];
        }

        return new Schema(
            'Schema\\' . $className,
            $contracts,
            'Error\\' . $className,
            'ErrorSchemas\\' . $className,
            $baseProperty->title ?? '',
            $baseProperty->description ?? '',
            $example,
            $properties,
            $baseProperty,
            false,
            ($baseProperty->type === null ? ['object'] : (is_array($baseProperty->type) ? $baseProperty->type : [$baseProperty->type])),
            [],
        );
    }
}
