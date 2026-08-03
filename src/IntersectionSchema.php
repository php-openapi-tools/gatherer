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

        foreach ($baseProperty->allOf ?? [] as $allOfSchema) {
            $schema             = OpenApiSpec::schema($allOfSchema);
            $gatheredProperties = [];
            foreach ($schema->properties ?? [] as $propertyName => $property) {
                $propertySchema   = OpenApiSpec::schema($property);
                $gatheredProperty = $gatheredProperties[(string) $propertyName] = Property::gather(
                    $className,
                    (string) $propertyName,
                    in_array(
                        (string) $propertyName,
                        OpenApiSpec::requiredProperties($schema),
                        true,
                    ),
                    $propertySchema,
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
                        OpenApiSpec::requiredProperties($schema),
                        true,
                    )
                ) {
                    continue;
                }

                if (
                    in_array(
                        (string) $propertyName,
                        OpenApiSpec::requiredProperties($baseProperty),
                        true,
                    )
                ) {
                    continue;
                }

                unset($example[$gatheredProperty->sourceName]);
            }

            $contracts[] = new Contract(
                $contractRegistry->get($schema, 'Contract\\' . $className . '\\' . ($schema->title ?? '')),
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
            OpenApiSpec::schemaTypeList($baseProperty),
            [],
        );
    }
}
