<?php

declare(strict_types=1);

namespace OpenAPITools\Tests\Gatherer;

use cebe\openapi\Reader;
use OpenAPITools\Configuration\Gathering;
use OpenAPITools\Gatherer\Gatherer;
use OpenAPITools\Representation\Representation;

final class SpecLoader
{
    public static function loadSpec(string $dataSetName): Representation
    {
        $specPath = __DIR__ . '/DataSets/' . $dataSetName . '/spec.yaml';

        return Gatherer::gather(
            Reader::readFromYamlFile($specPath),
            new Gathering(
                $specPath,
                null,
                new Gathering\Schemas(
                    true,
                    true,
                ),
            ),
        );
    }
}
