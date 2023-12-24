<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\Schema as baseSchema;
use Jawira\CaseConverter\Convert;
use NumberToWords\NumberToWords;
use OpenAPITools\Registry;
use OpenAPITools\Representation;
use PhpParser\Node;

use function array_filter;
use function array_key_exists;
use function array_values;
use function assert;
use function count;
use function is_array;
use function is_string;
use function preg_replace_callback;
use function property_exists;
use function str_pad;
use function str_replace;
use function strlen;

final class Property
{
    public static function gather(
        string $className,
        string $sourcePropertyName,
        bool $required,
        baseSchema $property,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
    ): Representation\Property {
        $enum        = [];
        $exampleData = null;

        if (property_exists($property, 'examples') && count($property->examples ?? []) > 0) {
            $examples = array_values(array_filter($property->examples, static fn (mixed $value): bool => $value !== null));
            // Main reason we're doing this is so we cause more variety in the example data when a list of examples is provided, but also consistently pick the same item so we do don't cause code churn
            /** @phpstan-ignore-next-line */
            $exampleData = $examples[strlen($sourcePropertyName) % 2 ? 0 : count($examples) - 1];
        }

        if ($exampleData === null && property_exists($property, 'example') && $property->example !== null) {
            $exampleData = $property->example;
        }

        if ($exampleData === null && property_exists($property, 'enum') && count($property->enum ?? []) > 0) {
            $enum  = $property->enum;
            $enums = array_values(array_filter($property->enum, static fn (mixed $value): bool => $value !== null));
            // Main reason we're doing this is so we cause more variety in the enum based example data, but also consistently pick the same item so we do don't cause code churn
            /** @phpstan-ignore-next-line */
            $exampleData = $enums[strlen($sourcePropertyName) % 2 ? 0 : count($enums) - 1];
        }

        $propertyName = str_replace([
            '@',
            '+',
            '-',
            '$',
        ], [
            '_AT_',
            '_PLUS_',
            '_MIN_',
            '_DOLLAR_',
        ], $sourcePropertyName);
        $propertyName = preg_replace_callback(
            '/[0-9]+/',
            static function ($matches) {
                return '_' . str_replace(['-', ' '], '_', NumberToWords::transformNumber('en', (int) $matches[0])) . '_';
            },
            $propertyName,
        );
        assert(is_string($propertyName));

        $type = Type::gather(
            $className,
            $propertyName,
            $property,
            $required,
            $schemaRegistry,
            $contractRegistry,
        );

        if ($property->type === 'array' && is_array($type->payload)) {
            $arrayItemsRaw  = [];
            $arrayItemsNode = [];

            foreach ($type->payload as $index => $arrayItem) {
                $arrayItemExampleData = ExampleData::gather(
                    $exampleData,
                    $arrayItem->type === 'union' ? $arrayItem->payload[(array_key_exists($index, $arrayItem->payload) ? $index : 0)] : $arrayItem, /** @phpstan-ignore-line */
                    $propertyName . str_pad('', $index + 1, '_'),
                );
                $arrayItemsRaw[]      = $arrayItemExampleData->raw;
                $arrayItemsNode[]     = new Node\Expr\ArrayItem($arrayItemExampleData->node);
            }

            $exampleData = new Representation\ExampleData($arrayItemsRaw, new Node\Expr\Array_($arrayItemsNode));
        } elseif ($type->type === 'union') {
            foreach (is_array($type->payload) ? $type->payload : [$type->payload] as $index => $arrayItem) {
                if (is_string($arrayItem)) {
                    continue;
                }

                if (property_exists($arrayItem, 'payload') && $arrayItem->payload instanceof Representation\Property\Type) {
                    $exampleData = ExampleData::gather(
                        $exampleData,
                        $arrayItem->payload,
                        $propertyName . str_pad('', $index + 1, '_'),
                    );
                    continue;
                }

                if (! ($arrayItem instanceof Representation\Property\Type)) {
                    continue;
                }

                $exampleData = ExampleData::gather(
                    null,
                    $arrayItem,
                    $propertyName . str_pad('', $index + 1, '_'),
                );
            }
        } else {
            $exampleData = ExampleData::gather($exampleData, $type, $propertyName);
        }

        return new Representation\Property(
            (new Convert($propertyName))->toCamel(),
            $sourcePropertyName,
            $property->description ?? '',
            $exampleData,
            $type,
            $type->nullable,
            $enum,
        );
    }
}
