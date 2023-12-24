<?php

declare(strict_types=1);

namespace OpenAPITools\Gatherer;

use cebe\openapi\spec\PathItem;
use OpenAPITools\Configuration\Gathering\Voter;
use OpenAPITools\Registry;
use OpenAPITools\Representation;
use OpenAPITools\Utils\Utils;

use function is_array;
use function strlen;

final class Path
{
    public static function gather(
        string $className,
        string $path,
        PathItem $pathItem,
        Registry\Schema $schemaRegistry,
        Registry\Contract $contractRegistry,
        Registry\ThrowableSchema $throwableSchemaRegistry,
        Voter|null $voters,
    ): Representation\Path {
        $className  = Utils::fixKeyword($className);
        $operations = [];

        foreach ($pathItem->getOperations() as $method => $operation) {
            $operationClassName = Utils::className($operation->operationId);
            if (strlen($operationClassName) === 0) {
                continue;
            }

            $operations[] = $opp = Operation::gather(
                $operationClassName,
                $method,
                $method,
                $path,
                [],
                $operation,
                $throwableSchemaRegistry,
                $schemaRegistry,
                $contractRegistry,
            );

//            if ($voters !== null && is_array($voters->listOperation)) {
//                $shouldStream = false;
//                $voter        = null;
//                foreach ($voters->listOperation as $voter) {
//                    if ($voter::list($opp)) {
//                        $shouldStream = true;
//                        break;
//                    }
//                }
//
//                if ($voter !== null && $shouldStream) {
//                    $operations[] = Operation::gather(
//                        $operationClassName . 'Listing',
//                        'LIST',
//                        $method,
//                        $path,
//                        [
//                            'listOperation' => [
//                                'key' => $voter::incrementorKey(),
//                                'initialValue' => $voter::incrementorInitialValue(),
//                                'keys' => $voter::keys(),
//                            ],
//                        ],
//                        $operation,
//                        $throwableSchemaRegistry,
//                        $schemaRegistry,
//                        $contractRegistry,
//                    );
//                }
//            }

            if ($voters === null || ! is_array($voters->streamOperation)) {
                continue;
            }

//            $shouldStream = false;
//            foreach ($voters->streamOperation as $voter) {
//                if ($voter::stream($opp)) {
//                    $shouldStream = true;
//                    break;
//                }
//            }

//            if (! $shouldStream) {
//                continue;
//            }

//            $operations[] = Operation::gather(
//                $operationClassName . 'Streaming',
//                'STREAM',
//                $method,
//                $path,
//                [],
//                $operation,
//                $throwableSchemaRegistry,
//                $schemaRegistry,
//                $contractRegistry,
//            );
        }

        return new Representation\Path(
            $className,
            OperationHydrator::gather(
                $className,
                ...$operations,
            ),
            $operations,
        );
    }
}
