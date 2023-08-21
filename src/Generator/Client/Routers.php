<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\Router;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\RouterClass;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\RouterClassMethod;
use Jawira\CaseConverter\Convert;
use PhpParser\Node;

use function rtrim;

final class Routers
{
    /** @var array<string, array<string, array<string, array{nodes: array<Node>, returnType: string, docBlockReturnType: string}>>> $operations */
    private array $operations = [];

    /** @param array<Node> $nodes */
    public function add(
        string $method,
        string|null $group,
        string $name,
        string $returnType,
        string $docBlockReturnType,
        array $nodes,
    ): Router {
        $this->operations[$method][$group ?? ''][$name] = [
            'nodes' => $nodes,
            'returnType' => $returnType,
            'docBlockReturnType' => $docBlockReturnType,
        ];

        return $this->createClassName($method, $group, $name);
    }

    /** @return iterable<RouterClass> */
    public function get(): iterable
    {
        foreach ($this->operations as $method => $groups) {
            foreach ($groups as $group => $methods) {
                $classMethods = [];
                foreach ($methods as $name => $op) {
                    $classMethods[] = new RouterClassMethod($name, $op['returnType'], $op['docBlockReturnType'], $op['nodes']);
                }

                yield new RouterClass(
                    $method,
                    $group,
                    $classMethods,
                );
            }
        }
    }

    public function createClassName(
        string $method,
        string|null $group,
        string $name,
    ): Router {
        return new Router(
            rtrim('Router\\' . (new Convert($method))->toPascal() . ($group === null ? '' : '\\' . (new Convert($group))->toPascal()), '\\'),
            (new Convert($name))->toCamel(),
        );
    }
}
