<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\OpenApi;
use OpenAPITools\Representation;

use function strlen;

final class Client
{
    public static function gather(
        OpenApi $spec,
        Representation\Path ...$paths,
    ): Representation\Client {
        $baseUrl = null;
        foreach ($spec->servers ?? [] as $server) {
            if (strlen($server->url) === 0) {
                continue;
            }

            $baseUrl = $server->url;
            break;
        }

        return new Representation\Client(
            $baseUrl,
            $paths,
        );
    }
}
