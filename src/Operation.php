<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\Operation as openAPIOperation;
use CodeInc\HttpReasonPhraseLookup\HttpReasonPhraseLookup;
use Jawira\CaseConverter\Convert;
use OpenAPITools\Registry;
use OpenAPITools\Representation;
use OpenAPITools\Utils\Utils;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function array_filter;
use function array_unique;
use function count;
use function implode;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function lcfirst;
use function preg_replace;
use function str_replace;
use function strlen;
use function strtoupper;
use function trim;
use function ucfirst;

final class Operation
{
    /** @param array<string, mixed> $metaData */
    public static function gather(
        string $className,
        string $matchMethod,
        string $method,
        string $path,
        array $metaData,
        openAPIOperation $operation,
        Registry\ThrowableSchema $throwableSchemaRegistry,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
    ): Representation\Operation {
        $returnType = [];
        $parameters = [];
        $empties    = [];
        foreach ($operation->parameters ?? [] as $parameter) {
            $parameter = OpenApiSpec::parameter($parameter);
            if ($parameter->schema === null) {
                throw new RuntimeException('Parameter is missing schema: ' . $parameter->name);
            }

            $parameterSchema = OpenApiSpec::schema($parameter->schema);
            $types           = is_array($parameterSchema->type) ? $parameterSchema->type : [$parameterSchema->type];
            if (count($parameterSchema->oneOf ?? []) > 0) {
                $types = [];
                foreach ($parameterSchema->oneOf as $oneOfSchema) {
                    $oneOfSchema = OpenApiSpec::schema($oneOfSchema);
                    foreach (is_array($oneOfSchema->type) ? $oneOfSchema->type : [$oneOfSchema->type] as $oost) {
                        $types[] = $oost;
                    }
                }
            }

            $parameterType = str_replace([
                'integer',
                'any',
                'boolean',
            ], [
                'int',
                'string|object',
                'bool',
            ], implode('|', $types));

            $parameters[] = new Representation\Parameter(
                new Convert($parameter->name)->toCamel(),
                $parameter->name,
                $parameter->description ?? '',
                $parameterType,
                $parameterSchema->format,
                $parameter->in,
                $parameterSchema->default,
                ExampleData::scalarData($parameter->name === 'page' ? 1 : strlen($parameter->name), $parameterType, $parameterSchema->format ?? ''),
            );
        }

        $classNameSanitized = str_replace('/', '\\', Utils::className($className));
        $requestBody        = [];
        if ($operation->requestBody !== null) {
            /** @var array<string, MediaType> $requestBodyContent */
            $requestBodyContent = $operation->requestBody->content ?? [];

            foreach ($requestBodyContent as $contentType => $requestBodyDetails) {
                if ($requestBodyDetails->schema === null) {
                    continue;
                }

                $requestBodySchema    = OpenApiSpec::schema($requestBodyDetails->schema);
                $requestBodyClassname = $schemaRegistry->get(
                    $requestBodySchema,
                    $classNameSanitized . '\\Request\\' . Utils::className(str_replace('/', '_', $contentType)),
                );
                $requestBody[]        = new Representation\Operation\RequestBody(
                    $contentType,
                    Schema::gather($requestBodyClassname, $requestBodySchema, $schemaRegistry, $contractRegistry),
                );
            }
        }

        $response = [];
        foreach ($operation->responses ?? [] as $code => $spec) {
            $responseCode = is_int($code) ? $code : (is_numeric($code) ? (int) $code : (is_string($code) ? $code : 'unknown'));
            $isError      = $code === 'default' || (is_numeric($code) && (int) $code >= 400);
            $contentCount = 0;
            foreach ($spec->content ?? [] as $contentType => $contentTypeMediaType) {
                if ($contentTypeMediaType->schema === null) {
                    continue;
                }

                $contentCount++;
                $responseSchema    = OpenApiSpec::schema($contentTypeMediaType->schema);
                $reasonPhraseCode  = is_numeric($code) ? (int) $code : 0;
                $responseClassname = $schemaRegistry->get(
                    $responseSchema,
                    'Operations\\' . $classNameSanitized . '\\Response\\' . Utils::className(
                        str_replace(
                            '/',
                            '_',
                            (string) $contentType,
                        ) . '\\' . ($code === 'default' ? 'Default' : (HttpReasonPhraseLookup::getReasonPhrase($reasonPhraseCode) ?? 'Unknown')),
                    ),
                );

                $response[] = new Representation\Operation\Response(
                    $responseCode,
                    (string) $contentType,
                    $spec->description,
                    Type::gather(
                        $responseClassname,
                        (string) $contentType,
                        $responseSchema,
                        true,
                        $schemaRegistry,
                        $contractRegistry,
                    ),
                );
                if ($isError) {
                    $throwableSchemaRegistry->add('Schema\\' . $responseClassname);
                    continue;
                }

                $returnType[] = $responseClassname;
            }

            if ($contentCount !== 0) {
                continue;
            }

            $headers = [];
            foreach ($spec->headers ?? [] as $headerName => $headerSpec) {
                $resolvedHeaderSpec = OpenApiSpec::parameter($headerSpec);
                if ($resolvedHeaderSpec->schema === null) {
                    continue;
                }

                $headerSchema         = OpenApiSpec::schema($resolvedHeaderSpec->schema);
                $headers[$headerName] = new Representation\Header($headerName, Schema::gather(
                    $schemaRegistry->get(
                        $headerSchema,
                        'WebHookHeader\\' . ucfirst((string) preg_replace('/\PL/u', '', (string) $headerName)),
                    ),
                    $headerSchema,
                    $schemaRegistry,
                    $contractRegistry,
                ), ExampleData::determiteType($resolvedHeaderSpec->example));
            }

            $empties[] = new Representation\Operation\EmptyResponse(is_numeric($code) ? (int) $code : 0, $spec->description, $headers);
        }

        if (count($returnType) === 0) {
            $returnType[] = '\\' . ResponseInterface::class;
        }

        $name  = lcfirst(trim(Utils::basename($className), '\\'));
        $group = trim(trim(Utils::dirname($className), '\\'), '.') !== '' ? trim(str_replace('\\', '', Utils::dirname($className)), '\\') : null;

        return new Representation\Operation(
            'Internal\\Operation\\' . Utils::fixKeyword($className),
            $classNameSanitized,
            'Internal\\Operator\\' . Utils::fixKeyword($className),
            lcfirst(
                str_replace(
                    ['\\'],
                    ['👷'],
                    Utils::fixKeyword($className),
                ),
            ),
            $name,
            new Convert($name)->toCamel(),
            $group,
            $group === null ? null : new Convert($group)->toCamel(),
            $operation->operationId,
            strtoupper($matchMethod),
            strtoupper($method),
            $operation->summary,
            $operation->externalDocs,
            $path,
            $metaData,
            array_unique($returnType),
            [
                ...array_filter($parameters, static fn (Representation\Parameter $parameter): bool => $parameter->default === null),
                ...array_filter($parameters, static fn (Representation\Parameter $parameter): bool => $parameter->default !== null),
            ],
            $requestBody,
            $response,
            $empties,
        );
    }
}
