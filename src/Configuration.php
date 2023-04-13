<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Destination;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\SubSplit;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Templates;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Voter;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Schemas;
use ApiClients\Tools\OpenApiClientGenerator\Contract\ContentType;
use EventSauce\ObjectHydrator\MapFrom;

final readonly class Configuration
{
    /**
     * @param array<class-string<ContentType>>|null $contentType
     */
    public function __construct(
        public string $spec,
        public Templates $templates,
        public Namespace_ $namespace,
        public Destination $destination,
        #[MapFrom('contentType')]
        public ?array $contentType,
        #[MapFrom('subSplit')]
        public ?SubSplit $subSplit,
        public ?Schemas $schemas,
        public ?Voter $voter,
    ) {
    }
}