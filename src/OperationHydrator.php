<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use OpenAPITools\Representation;

use function property_exists;

final class OperationHydrator
{
    public static function gather(
        string $className,
        Representation\Operation ...$operations,
    ): Representation\Hydrator {
        $schemaClasses = [];

        foreach ($operations as $operation) {
            foreach ($operation->response as $response) {
                if (! property_exists($response->content, 'payload') || ! ($response->content->payload instanceof Representation\Schema)) {
                    continue;
                }

                foreach (HydratorUtils::listSchemas($response->content->payload) as $schema) {
                    $schemaClasses[] = $schema;
                }
            }
        }

        return Hydrator::gather(
            'Operation\\' . $className,
            '🌀',
            ...$schemaClasses,
        );
    }
}
