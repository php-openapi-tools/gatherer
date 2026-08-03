<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer;

use cebe\openapi\Reader;
use OpenAPITools\Configuration\Gathering;
use OpenAPITools\Gatherer\Gatherer;
use OpenAPITools\Representation\Representation;
use OpenAPITools\TestData\DataSet;
use OpenAPITools\TestData\Provider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function call_user_func;
use function class_exists;
use function method_exists;

final class GatherTest extends AsyncTestCase
{
    #[Test]
    #[DataProviderExternal(Provider::class, 'sets')]
    public function gather(DataSet $dataSet): void
    {
        $representation = $this->loadSpec($dataSet->fileName);

        $testClassName = '\OpenAPITools\Tests\Gatherer\DataTests\\' . $dataSet->name;
        self::assertTrue(class_exists($testClassName));
        self::assertTrue(method_exists($testClassName, 'assert'));

        // @phpstan-ignore argument.type
        call_user_func($testClassName . '::assert', $representation);
    }

    private function loadSpec(string $dataSetName): Representation
    {
        return Gatherer::gather(
            Reader::readFromYamlFile($dataSetName),
            new Gathering(
                $dataSetName,
                null,
                new Gathering\Schemas(
                    true,
                    true,
                ),
            ),
        );
    }
}
