<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use OpenAPITools\Representation;
use OpenAPITools\Utils\Utils;

final class WebHookHydrator
{
    public static function gather(
        string $event,
        Representation\WebHook ...$webHooks,
    ): Representation\Hydrator {
        $schemaClasses = [];
        foreach ($webHooks as $webHook) {
            foreach ($webHook->schema as $webHookSchema) {
                foreach (HydratorUtils::listSchemas($webHookSchema) as $schema) {
                    $schemaClasses[] = $schema;
                }
            }
        }

        return Hydrator::gather(
            'WebHook\\' . Utils::className($event),
            '🪝',
            ...$schemaClasses,
        );
    }
}
