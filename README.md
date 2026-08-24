# gatherer

OpenAPI spec gathering for [OpenAPI Tools](https://github.com/php-openapi-tools) code generators. Parses a `cebe/php-openapi` spec and produces a [`representation`](https://github.com/php-openapi-tools/representation) value object ready for code generation.

![Continuous Integration](https://github.com/php-openapi-tools/gatherer/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/openapi-tools/gatherer/v/stable.png)](https://packagist.org/packages/openapi-tools/gatherer)
[![Total Downloads](https://poser.pugx.org/openapi-tools/gatherer/downloads.png)](https://packagist.org/packages/openapi-tools/gatherer/stats)
[![License](https://poser.pugx.org/openapi-tools/gatherer/license.png)](https://packagist.org/packages/openapi-tools/gatherer)

## Installation

To install via [Composer](https://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require openapi-tools/gatherer
```

## Components

| Class | Purpose |
| --- | --- |
| `Gatherer` | Main entry point; gathers paths, webhooks, and schemas into a `Representation` |
| `Client` | Gathers client metadata (base URL) and path collection |
| `Path` | Gathers a path item with its operations and hydrator |
| `Operation` | Gathers an HTTP operation with parameters, request body, and responses |
| `WebHook` | Gathers a webhook definition from a path item |
| `Schema` | Gathers an object schema with properties, contracts, and example data |
| `CompositSchema` | Gathers composite schemas with the same shape as `Schema` |
| `IntersectionSchema` | Gathers `allOf` schemas as intersection types |
| `Property` | Gathers a schema property with resolved type and example data |
| `Type` | Resolves OpenAPI property types to representation property types |
| `ExampleData` | Builds example values and corresponding AST nodes for code generation |
| `Hydrator` | Builds hydrator metadata from schema classes |
| `OperationHydrator` | Builds hydrators from operation response schemas |
| `WebHookHydrator` | Builds hydrators from webhook payload schemas |
| `HydratorUtils` | Walks schema trees to collect nested schemas for hydrators |
| `OpenApiSpec` | OpenAPI spec helpers (schema resolution, required properties, type lists) |
| `IntegerReturnerPretendingToBeARandomNumberGenerator` | Deterministic random generator for reverse-regex example data |

## Usage

### Gather a spec

Load an OpenAPI document and pass it to `Gatherer::gather()` with a gathering configuration:

```php
use cebe\openapi\Reader;
use OpenAPITools\Configuration\Gathering;
use OpenAPITools\Configuration\Gathering\Schemas;
use OpenAPITools\Gatherer\Gatherer;

$representation = Gatherer::gather(
    Reader::readFromYamlFile('openapi.yaml'),
    new Gathering(
        spec: 'openapi.yaml',
        voter: null,
        schemas: new Schemas(
            allowDuplication: true,
            useAliasesForDuplication: true,
        ),
    ),
);
```

The returned `Representation` contains the client (paths), webhook events, and all discovered schemas. Pass it to downstream generators or resolve class names with `Representation::namespace()`.

### Schema gathering

Individual schemas can be gathered when extending or testing the gathering pipeline:

```php
use OpenAPITools\Gatherer\Schema;
use OpenAPITools\Registry\Contract;
use OpenAPITools\Registry\Schema as SchemaRegistry;

$schemaRegistry   = new SchemaRegistry(allowDuplication: false, useAliasesForDuplication: false);
$contractRegistry = new Contract();

$schema = Schema::gather(
    'User',
    $openApiSchema,
    $schemaRegistry,
    $contractRegistry,
);
```

Schemas referenced during gathering but not declared in `components.schemas` are resolved automatically once all known schemas have been registered.

### Example data

`ExampleData` turns OpenAPI examples, enums, and formats into raw values paired with `nikic/php-parser` AST nodes suitable for emitting test fixtures:

```php
use OpenAPITools\Gatherer\ExampleData;
use OpenAPITools\Representation\Property\Type;

$example = ExampleData::gather(
    exampleData: null,
    type: new Type(
        type: 'scalar',
        format: 'uuid',
        pattern: null,
        payload: 'string',
        nullable: false,
    ),
    propertyName: 'id',
);

$example->raw;  // '4ccda740-74c3-4cfa-8571-ebf83c8f300a'
$example->node; // PhpParser\Node\Scalar\String_
```

When no example is provided, scalar types receive deterministic placeholder values. String patterns are satisfied via reverse-regex generation.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT)

Copyright (c) 2026 Cees-Jan Kiewiet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
