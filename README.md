# Eloquent JSON Schema

Generate JSON Schema files from your Eloquent models for Laravel applications.

## Features

- **Single Source of Truth**: Infer schema from model casts, database columns, PHPDoc types, and PHP Attributes
- **Request & Response Schemas**: Generate different schemas for API requests and responses
- **Composable Resolvers**: Modular system for extracting schema information
- **PHP Attributes**: Clean metadata injection with custom attributes
- **Validation Integration**: Middleware for automatic request validation
- **OpenAPI Support**: Export schemas for Swagger/Redoc documentation
- **Versioning**: Track schema changes with version management
- **Artisan Commands**: Easy generation, diffing, and publishing

## Installation

```bash
composer require onamfc/eloquent-json-schema
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="onamfc\EloquentJsonSchema\LaravelSchemaServiceProvider"
```

## Quick Start

### 1. Add Attributes to Your Models

```php
use App\Schemas as S;

#[S\SchemaName('User')]
#[S\RequestOnly(['password'])]
class User extends Model {
    #[S\Title('User ID')]
    #[S\Format('uuid')]
    public $id;

    #[S\Description('Primary email')]
    #[S\Format('email')]
    public $email;

    #[S\Enum(['basic','pro','enterprise'])]
    public $plan;
}
```

### 2. Generate Schemas

```bash
# Generate all model schemas
php artisan schema:generate

# Generate specific model
php artisan schema:generate User
```

### 3. Use in Validation

```php
Route::post('/users', [UserController::class, 'store'])
    ->middleware('schema:User.request');
```

## Generated Output

Schemas are saved to `storage/api-schemas/{version}/models/`:

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://schemas.example.com/v1/models/User.response.schema.json",
  "title": "User",
  "type": "object",
  "properties": {
    "id": { "type": "string", "format": "uuid", "readOnly": true },
    "email": { "type": "string", "format": "email" },
    "plan": { "type": "string", "enum": ["basic","pro","enterprise"] }
  },
  "required": ["id","email","plan"]
}
```

## Available Attributes

- `#[SchemaName('CustomName')]` - Override model name in schema
- `#[Title('Field Title')]` - Add title to property
- `#[Description('Field description')]` - Add description to property
- `#[Format('email|uuid|date-time')]` - Set format constraint
- `#[Enum(['value1', 'value2'])]` - Define enum values
- `#[RequestOnly(['field1'])]` - Fields only in request schema
- `#[ResponseOnly(['field1'])]` - Fields only in response schema

## Commands

```bash
# Generate schemas
php artisan schema:generate

# Show differences between versions
php artisan schema:diff --from=v1 --to=v2

# Export OpenAPI components
php artisan schema:publish-openapi
```

## Configuration

Key configuration options in `config/laravel-schema.php`:

- **version**: Schema version identifier
- **output_directory**: Where to save generated schemas
- **type_mapping**: How to map database types to JSON Schema
- **relationship_depth**: Whether to embed relationships or use ID references
- **decimal_as**: Handle decimals as 'number' or 'string'

## Validation Middleware

Register the middleware in your HTTP kernel:

```php
protected $middlewareAliases = [
    'schema' => \onamfc\EloquentJsonSchema\Http\Middleware\ValidateWithSchemaMiddleware::class,
];
```

Use in routes:

```php
Route::post('/api/users', [UserController::class, 'store'])
    ->middleware('schema:User.request');
```

## OpenAPI Integration

Export schemas as OpenAPI components:

```bash
php artisan schema:publish-openapi
```

Then reference in your OpenAPI spec:

```yaml
paths:
  /users:
    post:
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/UserRequest'
      responses:
        201:
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/UserResponse'
```

## License

MIT License