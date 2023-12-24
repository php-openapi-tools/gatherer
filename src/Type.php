<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\Schema as baseSchema;
use NumberToWords\NumberToWords;
use OpenAPITools\Registry;
use OpenAPITools\Representation\Property\Type as PropertyType;
use OpenAPITools\Utils\Utils;

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
        $type     = $property->type;
        $nullable = ! $required;

        if (is_array($property->allOf) && count($property->allOf) > 0) {
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

        if (is_array($property->oneOf) && count($property->oneOf) > 0) {
            // Check if nullable
            if (
                count($property->oneOf) === 2 &&
                count(array_filter($property->oneOf, static fn (BaseSchema $schema): bool => $schema->type === 'null')) === 1
            ) {
                return self::gather(
                    $className,
                    $propertyName,
                    current(array_filter($property->oneOf, static fn (BaseSchema $schema): bool => $schema->type !== 'null')),
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
                        foreach ($properties as $index => $property) {
                            yield self::gather(
                                $className,
                                $propertyName . '\\' . NumberToWords::transformNumber('en', $index),
                                $property,
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

        if (is_array($property->anyOf) && count($property->anyOf) > 0) {
            // Check if nullable
            if (
                count($property->anyOf) === 2 &&
                count(array_filter($property->anyOf, static fn (BaseSchema $schema): bool => $schema->type === 'null')) === 1
            ) {
                return self::gather(
                    $className,
                    $propertyName,
                    current(array_filter($property->anyOf, static fn (BaseSchema $schema): bool => $schema->type !== 'null')),
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
                        foreach ($properties as $index => $property) {
                            yield self::gather(
                                $className,
                                $propertyName . '\\' . NumberToWords::transformNumber('en', $index),
                                $property,
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

        if (
            is_array($type) &&
            count($type) === 2 &&
            (
                in_array(null, $type, false) ||
                in_array('null', $type, false)
            )
        ) {
            foreach ($type as $pt) {
                /** @phpstan-ignore-next-line */
                if ($pt !== null && $pt !== 'null') {
                    $type = $pt;
                    break;
                }
            }

            $nullable = true;
        }

        if ($type === 'array') {
            $arrayItems = [];

            foreach (range(0, ($property->maxItems ?? $property->minItems ?? 2) - 1) as $index) {
                $arrayItems[] = self::gather(
                    $className,
                    $propertyName,
                    $property->items,
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

        if (is_string($type)) {
            $type = str_replace([
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
            ], $type);
        } else {
            $type = '';
        }

        if ($type === '') {
            return new PropertyType(
                'scalar',
                null,
                null,
                'string',
                false,
            );
        }

        if ($type === 'object') {
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
            $type,
            $nullable,
        );
    }
}
