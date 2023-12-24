<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\OpenApi;
use OpenAPITools\Configuration\Gathering;
use OpenAPITools\Registry;
use OpenAPITools\Representation;
use OpenAPITools\Utils\Utils;
use RuntimeException;

use function assert;
use function count;
use function strlen;
use function trim;

final class Gatherer
{
    public static function gather(
        OpenApi $spec,
        Gathering $configuration,
    ): Representation\Representation {
        $schemaRegistry   = new Registry\Schema(
            $configuration->schemas->allowDuplication ?? false,
            $configuration->schemas->useAliasesForDuplication ?? false,
        );
        $contractRegistry = new Registry\Contract();

        $contracts               = [];
        $schemas                 = [];
        $throwableSchemaRegistry = new Registry\ThrowableSchema();
        if (count($spec->components->schemas ?? []) > 0) {
            /**
             * Do this loop twice to ensure we added all schemas to the schema registry BEFORE we start to gather them
             * which will trigger looking up schemas as properties and end up with weird naming.
             *
             * @phpstan-ignore-next-line
             */
            foreach ($spec->components->schemas as $name => $schema) {
                assert($schema instanceof \cebe\openapi\spec\Schema);
                $schemaRegistry->addClassName(Utils::className($name), $schema);
            }

            /**
             * Gather all the schemas now that we've added all of them to the schema registry.
             *
             * @phpstan-ignore-next-line
             */
            foreach ($spec->components->schemas as $name => $schema) {
                assert($schema instanceof \cebe\openapi\spec\Schema);
                $schema    = Schema::gather(Utils::className($name), $schema, $schemaRegistry, $contractRegistry);
                $schemas[] = $schema;
                $contracts = [...$contracts, ...$schema->contracts];
            }
        }

        /** @var array<Representation\WebHook> $webHooks */
        $webHooks = [];
        if (count($spec->webhooks ?? []) > 0) {
            foreach ($spec->webhooks as $webHook) {
                try {
                    $webHooks[] = WebHook::gather($webHook, $schemaRegistry, $contractRegistry);
                    /** @phpstan-ignore-next-line */
                } catch (RuntimeException) {
                    // @ignoreException
                }
            }
        }

        $paths = [];
        if (count($spec->paths ?? []) > 0) {
            foreach ($spec->paths as $path => $pathItem) {
                if ($path === '/') {
                    $pathClassName = 'Root';
                } else {
                    $pathClassName = trim(Utils::className($path), '\\');
                }

                if (strlen($path) === 0 || strlen($pathClassName) === 0) {
                    continue;
                }

                $paths[] = Path::gather(
                    $pathClassName,
                    $path,
                    $pathItem,
                    $schemaRegistry,
                    $contractRegistry,
                    $throwableSchemaRegistry,
                    $configuration->voter,
                );
            }
        }

        return new Representation\Representation(
            new Representation\Client(
                null,
                $paths,
            ),
            $webHooks,
            $schemas,
        );
    }
}
