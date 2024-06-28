<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataSets\Aliases;

use OpenAPITools\Tests\Gatherer\SpecLoader;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function basename;

final class Test extends AsyncTestCase
{
    /** @test */
    public function aliases(): void
    {
        $representation = SpecLoader::loadSpec(basename(__DIR__));

        self::assertCount(5, $representation->schemas);
        self::assertSame('Schema\\String', $representation->schemas[0]->className);
        self::assertSame('Schema\\Basic', $representation->schemas[1]->className);
        self::assertSame('Schema\\Basic\\Name', $representation->schemas[2]->className);
        self::assertSame('Schema\\Basic\\Name\\Preferred', $representation->schemas[3]->className);
        self::assertSame('Schema\\Basic\\Name\\First', $representation->schemas[3]->alias[0]);
        self::assertSame('Schema\\Basic\\Name\\Middle', $representation->schemas[3]->alias[1]);
        self::assertSame('Schema\\Basic\\Name\\Last', $representation->schemas[3]->alias[2]);
    }
}
