<?php

namespace onamfc\EloquentJsonSchema\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use onamfc\EloquentJsonSchema\Contracts\SchemaContributor;
use onamfc\EloquentJsonSchema\SchemaDoc;

class DatabaseResolver implements SchemaContributor
{
    public function contribute(Model $model, SchemaDoc $doc, string $schemaType): void
    {
        $tableName = $model->getTable();
        $columns = Schema::getColumns($tableName);

        foreach ($columns as $column) {
            $this->processColumn($column, $doc, $schemaType);
        }
    }

    private function processColumn(array $column, SchemaDoc $doc, string $schemaType): void
    {
        $name = $column['name'];
        $type = $column['type_name'];
        $nullable = $column['nullable'];
        $default = $column['default'];

        // Map database type to JSON Schema type
        $typeMapping = config('laravel-schema.type_mapping');
        $schemaType = $typeMapping[$type] ?? ['type' => 'string'];

        $property = $schemaType;

        // Handle nullability
        if ($nullable) {
            $property['type'] = [$property['type'], 'null'];
        }

        // Add default value if present
        if ($default !== null) {
            $property['default'] = $this->castDefault($default, $property['type']);
        }

        // Handle length constraints
        if (isset($column['length']) && in_array($type, ['varchar', 'char', 'string'])) {
            $property['maxLength'] = (int) $column['length'];
        }

        $doc->addProperty($name, $property);

        // Add to required if not nullable and no default
        if (!$nullable && $default === null && $schemaType === 'request') {
            $doc->addRequired($name);
        }
    }

    private function castDefault(mixed $default, string|array $type): mixed
    {
        if (is_array($type)) {
            $type = $type[0]; // Get first type if union
        }

        return match ($type) {
            'integer' => (int) $default,
            'number' => (float) $default,
            'boolean' => (bool) $default,
            default => $default,
        };
    }
}