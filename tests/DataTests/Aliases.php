<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataTests;

use OpenAPITools\Representation\Representation;
use PHPUnit\Framework\Assert;

final class Aliases
{
    /** @api */
    public static function assert(Representation $representation): void
    {
        Assert::assertCount(4, $representation->schemas);
        Assert::assertSame('Schema\\String_', $representation->schemas['String_']->className);
        Assert::assertSame('Schema\\Basic', $representation->schemas['Basic']->className);
        Assert::assertSame('Schema\\Basic\\Name', $representation->schemas['Basic\\Name']->className);
        Assert::assertSame('Schema\\Basic\\Name\\Preferred', $representation->schemas['Basic\\Name\\Preferred']->className);
        Assert::assertSame('Schema\\Basic\\Name\\First', $representation->schemas['Basic\\Name\\Preferred']->alias[0]);
        Assert::assertSame('Schema\\Basic\\Name\\Middle', $representation->schemas['Basic\\Name\\Preferred']->alias[1]);
        Assert::assertSame('Schema\\Basic\\Name\\Last', $representation->schemas['Basic\\Name\\Preferred']->alias[2]);

        Assert::assertCount(1, $representation->client->paths);

        /**
         * The response resolves to Schema\Basic, so the operation hydrator collects
         * that schema plus every nested schema reachable from it: Basic, Basic\Name,
         * and the four aliased name variants.
         */
        Assert::assertCount(6, $representation->client->paths[0]->hydrator->schemas);
        Assert::assertSame('Schema\\Basic', $representation->client->paths[0]->hydrator->schemas[0]->className);
    }
}
