<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataSets\NestedSchema;

use OpenAPITools\Tests\Gatherer\SpecLoader;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function basename;

final class Test extends AsyncTestCase
{
    /** @test */
    public function basic(): void
    {
        $representation = SpecLoader::loadSpec(basename(__DIR__));

        self::assertCount(3, $representation->schemas);
        self::assertSame('Schema\\Basic\\Name', $representation->schemas[1]->className);
    }
}
