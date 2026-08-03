<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\PathItem;
use OpenAPITools\Registry;
use OpenAPITools\Representation;
use OpenAPITools\Utils\Utils;
use RuntimeException;

use function count;
use function explode;
use function iterator_to_array;
use function preg_replace;
use function str_replace;
use function ucfirst;

final class WebHook
{
    public static function gather(
        PathItem $webhook,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
    ): Representation\WebHook {
        $webHookPost = $webhook->post;

        /**
         * Spec objects keep their fields behind __get rather than declaring
         * them, so property_exists() never sees one. Ask for the value instead.
         */
        /** @var array<string, MediaType> $requestBodyContent */
        $requestBodyContent = $webHookPost?->requestBody->content ?? [];
        if ($webHookPost?->requestBody === null || count($requestBodyContent) === 0) {
            throw new RuntimeException('Missing request body content to deal with');
        }

        [$event] = explode('/', $webHookPost->operationId);

        $headers = [];
        foreach ($webHookPost->parameters ?? [] as $header) {
            $header = OpenApiSpec::parameter($header);
            if ($header->in !== 'header' || $header->schema === null) {
                continue;
            }

            $headerSchema = OpenApiSpec::schema($header->schema);
            $headers[]    = new Representation\Header($header->name, Schema::gather(
                $schemaRegistry->get(
                    $headerSchema,
                    'WebHookHeader\\' . ucfirst((string) preg_replace('/\PL/u', '', $header->name)),
                ),
                $headerSchema,
                $schemaRegistry,
                $contractRegistry,
            ), ExampleData::determiteType($header->example));
        }

        /** @var array<string, Representation\Schema> $requestSchemas */
        $requestSchemas = iterator_to_array((static function (array $content, string $operationId, Registry\Schema $schemaRegistry, Registry\Contract $contractRegistry): iterable {
            /** @var array<string, MediaType> $content */
            foreach ($content as $type => $mediaType) {
                if ($mediaType->schema === null) {
                    continue;
                }

                $requestSchema = OpenApiSpec::schema($mediaType->schema);
                yield $type => Schema::gather(
                    $schemaRegistry->get(
                        $requestSchema,
                        'WebHook\\' . Utils::className($operationId) . '\\Request\\' . Utils::className(str_replace('/', '_', $type)),
                    ),
                    $requestSchema,
                    $schemaRegistry,
                    $contractRegistry,
                );
            }
        })($requestBodyContent, $webHookPost->operationId, $schemaRegistry, $contractRegistry), true);

        return new Representation\WebHook(
            $event,
            $webHookPost->summary ?? '',
            $webHookPost->description ?? '',
            $webHookPost->operationId,
            $webHookPost->externalDocs->url ?? '',
            $headers,
            $requestSchemas,
        );
    }
}
