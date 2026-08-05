<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer\ExampleData;

use OpenAPITools\Gatherer\ExampleData;
use OpenAPITools\Representation\Property\Type;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReverseRegex\Exception as ReverseRegexException;

final class ReverseRegexTest extends TestCase
{
    #[Test]
    #[DataProvider('patternProvider')]
    public function scalarDataGeneratesStringMatchingPattern(string $pattern, string $expected): void
    {
        $exampleData = ExampleData::scalarData(0, 'string', '', $pattern);

        self::assertSame($expected, $exampleData->raw);
        self::assertMatchesRegularExpression('#' . $pattern . '#', $exampleData->raw);
        self::assertInstanceOf(String_::class, $exampleData->node);
    }

    /** @return iterable<string, array{string, string}> */
    public static function patternProvider(): iterable
    {
        yield 'social security number' => ['^\d{3}-\d{2}-\d{4}$', '999-99-9999'];
        yield 'lowercase letters' => ['^[a-z]+$', 'hhhhhhhh'];
        yield 'digits only' => ['^\d+$', '44444'];
    }

    #[Test]
    public function scalarDataPrefersPatternOverFormat(): void
    {
        $exampleData = ExampleData::scalarData(0, 'string', 'uuid', '^\d{3}-\d{2}-\d{4}$');

        self::assertSame('999-99-9999', $exampleData->raw);
    }

    #[Test]
    public function scalarDataWrapsPatternMatchInArrayType(): void
    {
        $exampleData = ExampleData::scalarData(0, 'array', '', '^\d{3}-\d{2}-\d{4}$');

        self::assertSame(['999-99-9999'], $exampleData->raw);
    }

    #[Test]
    public function gatherUsesPatternWhenExampleDataIsNull(): void
    {
        $exampleData = ExampleData::gather(
            null,
            new Type(
                'scalar',
                null,
                '^\d{3}-\d{2}-\d{4}$',
                'string',
                false,
            ),
            'regexp',
        );

        self::assertSame('999-99-9999', $exampleData->raw);
        self::assertInstanceOf(String_::class, $exampleData->node);
    }

    #[Test]
    public function scalarDataThrowsWhenPatternIsInvalid(): void
    {
        self::expectException(ReverseRegexException::class);

        ExampleData::scalarData(0, 'string', '', '[invalid');
    }
}
