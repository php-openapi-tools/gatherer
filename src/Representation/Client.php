<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Representation;

final class Client
{
    public function __construct(
        public readonly ?string $baseUrl,
        /**
         * @var array<Path> $paths
         */
        public readonly array $paths,
    ) {
    }
}
