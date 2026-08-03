<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataTests;

use OpenAPITools\Representation\Representation;
use PHPUnit\Framework\Assert;

final class Basic
{
    /** @api */
    public static function assert(Representation $representation): void
    {
        Assert::assertCount(0, $representation->webHooks);
        Assert::assertCount(2, $representation->schemas);
        Assert::assertNull($representation->client->baseUrl);
        Assert::assertCount(1, $representation->client->paths);
        Assert::assertCount(1, $representation->client->paths[0]->operations);
        Assert::assertSame('/', $representation->client->paths[0]->operations[0]->path);
        Assert::assertSame('Internal\Operation\Root', $representation->client->paths[0]->operations[0]->className);
        Assert::assertSame('root', $representation->client->paths[0]->operations[0]->operationId);
    }
}
