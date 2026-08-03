<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\OpenApi;
use OpenAPITools\Representation;

/** @api */
final class Client
{
    public static function gather(
        OpenApi $spec,
        Representation\Path ...$paths,
    ): Representation\Client {
        $baseUrl = null;
        foreach ($spec->servers ?? [] as $server) {
            if ($server->url === '') {
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
