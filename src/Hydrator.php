<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use OpenAPITools\Representation;

use function lcfirst;
use function str_replace;

final class Hydrator
{
    public static function gather(
        string $className,
        string $nameSpaceSeperator,
        Representation\Schema ...$schemaClasses,
    ): Representation\Hydrator {
        return new Representation\Hydrator(
            'Internal\\Hydrator\\' . $className,
            str_replace(['\\', '/'], ['/', $nameSpaceSeperator], lcfirst($className)),
            $schemaClasses,
        );
    }
}
