<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema Version
    |--------------------------------------------------------------------------
    |
    | Version identifier for generated schemas. Can be semantic version,
    | date-based, or git SHA.
    |
    */
    'version' => env('SCHEMA_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Output Directory
    |--------------------------------------------------------------------------
    |
    | Directory where generated schemas will be stored relative to storage_path.
    |
    */
    'output_directory' => 'api-schemas',

    /*
    |--------------------------------------------------------------------------
    | Models Directory
    |--------------------------------------------------------------------------
    |
    | Directory to scan for Eloquent models.
    |
    */
    'models_directory' => app_path('Models'),

    /*
    |--------------------------------------------------------------------------
    | Schema Base URI
    |--------------------------------------------------------------------------
    |
    | Base URI for schema $id generation.
    |
    */
    'base_uri' => env('SCHEMA_BASE_URI', 'https://schemas.example.com'),

    /*
    |--------------------------------------------------------------------------
    | Resolvers
    |--------------------------------------------------------------------------
    |
    | Pipeline of resolvers to extract schema information.
    | Order matters - earlier resolvers have lower priority.
    |
    */
    'resolvers' => [
        onamfc\EloquentJsonSchema\Resolvers\DatabaseResolver::class,
        onamfc\EloquentJsonSchema\Resolvers\CastsResolver::class,
        onamfc\EloquentJsonSchema\Resolvers\RulesResolver::class,
        onamfc\EloquentJsonSchema\Resolvers\AttributesResolver::class,
        onamfc\EloquentJsonSchema\Resolvers\RelationsResolver::class,
        onamfc\EloquentJsonSchema\Resolvers\VisibilityFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Type Mapping
    |--------------------------------------------------------------------------
    |
    | How to map database/PHP types to JSON Schema types.
    |
    */
    'type_mapping' => [
        'string' => ['type' => 'string'],
        'text' => ['type' => 'string'],
        'varchar' => ['type' => 'string'],
        'char' => ['type' => 'string'],
        'uuid' => ['type' => 'string', 'format' => 'uuid'],
        'json' => ['type' => 'object'],
        'jsonb' => ['type' => 'object'],
        'integer' => ['type' => 'integer'],
        'bigint' => ['type' => 'integer', 'format' => 'int64'],
        'smallint' => ['type' => 'integer'],
        'decimal' => ['type' => 'number'],
        'float' => ['type' => 'number'],
        'double' => ['type' => 'number'],
        'boolean' => ['type' => 'boolean'],
        'date' => ['type' => 'string', 'format' => 'date'],
        'datetime' => ['type' => 'string', 'format' => 'date-time'],
        'timestamp' => ['type' => 'string', 'format' => 'date-time'],
        'time' => ['type' => 'string', 'format' => 'time'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Decimal Handling
    |--------------------------------------------------------------------------
    |
    | How to handle decimal fields: 'number' or 'string'
    |
    */
    'decimal_as' => 'number',

    /*
    |--------------------------------------------------------------------------
    | Include Timestamps
    |--------------------------------------------------------------------------
    |
    | Whether to include created_at/updated_at in schemas.
    |
    */
    'include_timestamps' => true,

    /*
    |--------------------------------------------------------------------------
    | Relationship Depth
    |--------------------------------------------------------------------------
    |
    | How deep to embed relationships: 'id' or 'embed'
    |
    */
    'relationship_depth' => 'id',

    /*
    |--------------------------------------------------------------------------
    | FormRequest Rules
    |--------------------------------------------------------------------------
    |
    | Whether to automatically detect and use FormRequest rules.
    |
    */
    'use_form_request_rules' => true,
];