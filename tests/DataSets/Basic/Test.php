<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataSets\Basic;

use OpenAPITools\Tests\Gatherer\SpecLoader;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function basename;

final class Test extends AsyncTestCase
{
    /** @test */
    public function basic(): void
    {
        $representation = SpecLoader::loadSpec(basename(__DIR__));

        self::assertCount(0, $representation->webHooks);
        self::assertCount(2, $representation->schemas);
        self::assertNull($representation->client->baseUrl);
        self::assertCount(1, $representation->client->paths);
        self::assertCount(1, $representation->client->paths[0]->operations);
        self::assertSame('/', $representation->client->paths[0]->operations[0]->path);
        self::assertSame('Internal\Operation\Root', $representation->client->paths[0]->operations[0]->className);
        self::assertSame('root', $representation->client->paths[0]->operations[0]->operationId);
    }
}
