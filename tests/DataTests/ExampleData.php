<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\DataTests;

use OpenAPITools\Representation\Representation;
use PHPUnit\Framework\Assert;

final class ExampleData
{
    private const array EXAMPLE_DATA = [
//        null,
        'generated',
        'generated',
        '999-99-9999',
        '4ccda740-74c3-4cfa-8571-ebf83c8f300a',
        'https://example.com/',
        'hi@example.com',
        '1970-01-01T00:00:00+00:00',
        '127.0.0.1',
        '::1',
        false,
        3,
        0.5,
        1.5,
        [
            'generated',
            'generated',
        ],
        [
            1.6,
            1.7,
        ],
    ];

    /** @api */
    public static function assert(Representation $representation): void
    {
        Assert::assertCount(2, $representation->schemas);
        foreach (self::EXAMPLE_DATA as $index => $data) {
            Assert::assertSame($data, $representation->schemas['Basic']->properties[$index]->example->raw);
        }
    }
}
