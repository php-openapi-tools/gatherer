<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\PathItem;
use OpenAPITools\Registry;
use OpenAPITools\Representation;
use RuntimeException;

use function explode;
use function iterator_to_array;
use function property_exists;
use function Safe\preg_replace;
use function time;
use function ucfirst;

final class WebHook
{
    public static function gather(
        PathItem $webhook,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
    ): Representation\WebHook {
        $webHookPost = $webhook->post;
        if ($webHookPost?->requestBody === null || ! property_exists($webHookPost->requestBody, 'content')) {
            throw new RuntimeException('Missing request body content to deal with');
        }

        [$event] = explode('/', $webHookPost->operationId);

        $headers = [];
        foreach ($webHookPost->parameters ?? [] as $header) {
            if ($header->in !== 'header') {
                continue;
            }

            $headers[] = new Representation\Header($header->name, Schema::gather(
                $schemaRegistry->get(
                    $header->schema,
                    'WebHookHeader\\' . ucfirst(preg_replace('/\PL/u', '', $header->name)),
                ),
                $header->schema,
                $schemaRegistry,
                $contractRegistry,
            ), ExampleData::determiteType($header->example));
        }

        return new Representation\WebHook(
            $event,
            $webHookPost->summary ?? '',
            $webHookPost->description ?? '',
            $webHookPost->operationId,
            $webHookPost->externalDocs->url ?? '',
            $headers,
            iterator_to_array((static function (array $content, Registry\Schema $schemaRegistry, Registry\Contract $contractRegistry): iterable {
                foreach ($content as $type => $schema) {
                    yield $type => Schema::gather(
                        $schemaRegistry->get($schema->schema, 'T' . time()),
                        $schema->schema,
                        $schemaRegistry,
                        $contractRegistry,
                    );
                }
            })($webHookPost->requestBody->content, $schemaRegistry, $contractRegistry)),
        );
    }
}
