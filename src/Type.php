<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\Schema as baseSchema;
use NumberToWords\NumberToWords;
use OpenAPITools\Registry;
use OpenAPITools\Representation\Property\Type as PropertyType;
use OpenAPITools\Utils\Utils;
use RuntimeException;

use function array_filter;
use function count;
use function current;
use function in_array;
use function is_array;
use function is_string;
use function range;
use function str_replace;

final class Type
{
    public static function gather(
        string $className,
        string $propertyName,
        baseSchema $property,
        bool $required,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
    ): PropertyType {
        $propertyTypeValue = $property->type;
        $nullable          = ! $required;

        if (count($property->allOf ?? []) > 0) {
            return new PropertyType(
                'object',
                null,
                null,
                IntersectionSchema::gather(
                    $schemaRegistry->get(
                        $property,
                        Utils::className($className . '\\' . $propertyName),
                    ),
                    $property,
                    $schemaRegistry,
                    $contractRegistry,
                ),
                $nullable,
            );
        }

        if (count($property->oneOf ?? []) > 0) {
            // Check if nullable
            if (
                count($property->oneOf) === 2 &&
                count(array_filter($property->oneOf, static fn (mixed $schema): bool => OpenApiSpec::schema($schema)->type === 'null')) === 1
            ) {
                $nonNullSchema = current(array_filter($property->oneOf, static fn (mixed $schema): bool => OpenApiSpec::schema($schema)->type !== 'null'));
                if ($nonNullSchema === false) {
                    throw new RuntimeException('Expected at least one non-null oneOf schema');
                }

                return self::gather(
                    $className,
                    $propertyName,
                    OpenApiSpec::schema($nonNullSchema),
                    false,
                    $schemaRegistry,
                    $contractRegistry,
                );
            }

            return new PropertyType(
                'union',
                null,
                null,
                [
                    ...(static function (
                        string $className,
                        string $propertyName,
                        array $properties,
                        bool $required,
                        Registry\Schema $schemaRegistry,
                        Registry\Contract $contractRegistry,
                    ): iterable {
                        foreach ($properties as $index => $oneOfProperty) {
                            yield self::gather(
                                $className,
                                $propertyName . '\\' . NumberToWords::transformNumber('en', $index),
                                OpenApiSpec::schema($oneOfProperty),
                                $required,
                                $schemaRegistry,
                                $contractRegistry,
                            );
                        }
                    })(
                        $className,
                        $propertyName,
                        $property->oneOf,
                        $required,
                        $schemaRegistry,
                        $contractRegistry,
                    ),
                ],
                $nullable,
            );
        }

        if (count($property->anyOf ?? []) > 0) {
            // Check if nullable
            if (
                count($property->anyOf) === 2 &&
                count(array_filter($property->anyOf, static fn (mixed $schema): bool => OpenApiSpec::schema($schema)->type === 'null')) === 1
            ) {
                $nonNullSchema = current(array_filter($property->anyOf, static fn (mixed $schema): bool => OpenApiSpec::schema($schema)->type !== 'null'));
                if ($nonNullSchema === false) {
                    throw new RuntimeException('Expected at least one non-null anyOf schema');
                }

                return self::gather(
                    $className,
                    $propertyName,
                    OpenApiSpec::schema($nonNullSchema),
                    false,
                    $schemaRegistry,
                    $contractRegistry,
                );
            }

            return new PropertyType(
                'union',
                null,
                null,
                [
                    ...(static function (
                        string $className,
                        string $propertyName,
                        array $properties,
                        bool $required,
                        Registry\Schema $schemaRegistry,
                        Registry\Contract $contractRegistry,
                    ): iterable {
                        foreach ($properties as $index => $anyOfProperty) {
                            yield self::gather(
                                $className,
                                $propertyName . '\\' . NumberToWords::transformNumber('en', $index),
                                OpenApiSpec::schema($anyOfProperty),
                                $required,
                                $schemaRegistry,
                                $contractRegistry,
                            );
                        }
                    })(
                        $className,
                        $propertyName,
                        $property->anyOf,
                        $required,
                        $schemaRegistry,
                        $contractRegistry,
                    ),
                ],
                $nullable,
            );
        }

        $resolvedPropertyTypeValue = $propertyTypeValue;

        if (
            is_array($propertyTypeValue) &&
            count($propertyTypeValue) === 2 &&
            (
                in_array(null, $propertyTypeValue, true) ||
                in_array('null', $propertyTypeValue, true)
            )
        ) {
            $resolvedScalarType = 'object';
            foreach ($propertyTypeValue as $pt) {
                if ($pt !== 'null') {
                    $resolvedScalarType = $pt;
                    break;
                }
            }

            $resolvedPropertyTypeValue = $resolvedScalarType;
            $nullable                  = true;
        }

        if ($resolvedPropertyTypeValue === 'array') {
            $arrayItems = [];

            foreach (range(0, ($property->maxItems ?? $property->minItems ?? 2) - 1) as $index) {
                if ($property->items === null) {
                    throw new RuntimeException('Array property is missing items');
                }

                $arrayItems[] = self::gather(
                    $className,
                    $propertyName,
                    OpenApiSpec::schema($property->items),
                    $required,
                    $schemaRegistry,
                    $contractRegistry,
                );
            }

            return new PropertyType(
                'array',
                null,
                null,
                $arrayItems,
                $nullable,
            );
        }

        $scalarType = is_string($resolvedPropertyTypeValue)
            ? str_replace([
                'integer',
                'number',
                'any',
                'null',
                'boolean',
            ], [
                'int',
                'int|float',
                '',
                '',
                'bool',
            ], $resolvedPropertyTypeValue)
            : '';

        if ($scalarType === '') {
            return new PropertyType(
                'scalar',
                null,
                null,
                'string',
                false,
            );
        }

        if ($scalarType === 'object') {
            return new PropertyType(
                'object',
                null,
                null,
                Schema::gather(
                    $schemaRegistry->get(
                        $property,
                        Utils::className($className . '\\' . $propertyName),
                    ),
                    $property,
                    $schemaRegistry,
                    $contractRegistry,
                ),
                $nullable,
            );
        }

        return new PropertyType(
            'scalar',
            $property->format ?? null,
            $property->pattern ?? null,
            $scalarType,
            $nullable,
        );
    }
}
