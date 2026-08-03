<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\OpenApi;
use OpenAPITools\Configuration\Gathering;
use OpenAPITools\Configuration\Gathering\Voter;
use OpenAPITools\Registry;
use OpenAPITools\Representation;
use OpenAPITools\Utils\Utils;
use RuntimeException;

use function array_map;
use function array_unique;
use function assert;
use function count;
use function is_string;
use function trim;

/** @api */
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

        $schemas                 = [];
        $throwableSchemaRegistry = new Registry\ThrowableSchema();
        $components              = $spec->components;
        if ($components !== null && count($components->schemas ?? []) > 0) {
            /**
             * Do this loop twice to ensure we added all schemas to the schema registry BEFORE we start to gather them
             * which will trigger looking up schemas as properties and end up with weird naming.
             */
            foreach ($components->schemas as $name => $schema) {
                assert($schema instanceof \cebe\openapi\spec\Schema);
                $schemaRegistry->addClassName(Utils::className((string) $name), $schema);
            }

            /**
             * Gather all the schemas now that we've added all of them to the schema registry.
             */
            foreach ($components->schemas as $name => $openApiSchema) {
                assert($openApiSchema instanceof \cebe\openapi\spec\Schema);
                $className           = Utils::className((string) $name);
                $gatheredSchema      = Schema::gather($className, $openApiSchema, $schemaRegistry, $contractRegistry);
                $schemas[$className] = $gatheredSchema;
            }
        }

        /** @var array<Representation\WebHookEvent> $webHooks */
        $webHooks = [];
        if (count($spec->webhooks ?? []) > 0) {
            /** @var array<string, array<Representation\WebHook>> $webHooksPerEvent */
            $webHooksPerEvent = [];
            foreach ($spec->webhooks as $webHook) {
                try {
                    $gatheredWebHook = WebHook::gather($webHook, $schemaRegistry, $contractRegistry);
                } catch (RuntimeException) {
                    // @ignoreException
                    continue;
                }

                $webHooksPerEvent[$gatheredWebHook->event][] = $gatheredWebHook;
            }

            foreach ($webHooksPerEvent as $event => $eventWebHooks) {
                $webHooks[] = new Representation\WebHookEvent(
                    $event,
                    WebHookHydrator::gather($event, ...$eventWebHooks),
                    $eventWebHooks,
                );
            }
        }

        $paths = [];
        if (count($spec->paths ?? []) > 0) {
            foreach ($spec->paths as $path => $pathItem) {
                if (! is_string($path)) {
                    continue;
                }

                $pathString = $path;
                if ($pathString === '/') {
                    $pathClassName = 'Root';
                } else {
                    $pathClassName = trim(Utils::className($pathString), '\\');
                }

                if ($pathClassName === '') {
                    continue;
                }

                $paths[] = Path::gather(
                    $pathClassName,
                    $pathString,
                    $pathItem,
                    $schemaRegistry,
                    $contractRegistry,
                    $throwableSchemaRegistry,
                    $configuration->voter ?? new Voter(null, null),
                );
            }
        }

        do {
            foreach ($schemaRegistry->unknownSchemas() as $schema) {
                $className           = Utils::className($schema->className);
                $schemas[$className] = Schema::gather($className, $schema->schema, $schemaRegistry, $contractRegistry);
            }
        } while ($schemaRegistry->hasUnknownSchemas());

        return new Representation\Representation(
            new Representation\Client(
                null,
                $paths,
            ),
            $webHooks,
            array_map(static fn (Representation\Schema $schema): Representation\Schema => new Representation\Schema(
                $schema->className,
                $schema->contracts,
                $schema->errorClassName,
                $schema->errorClassNameAliased,
                $schema->title,
                $schema->description,
                $schema->example,
                $schema->properties,
                $schema->schema,
                $schema->isArray,
                $schema->type,
                array_unique([...$schemaRegistry->aliasesForClassName($schema->className)]),
            ), $schemas),
        );
    }
}
