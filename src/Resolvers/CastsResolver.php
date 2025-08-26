<?php

namespace onamfc\EloquentJsonSchema\Resolvers;

use Illuminate\Database\Eloquent\Model;
use onamfc\EloquentJsonSchema\Contracts\SchemaContributor;
use onamfc\EloquentJsonSchema\SchemaDoc;

class CastsResolver implements SchemaContributor
{
    public function contribute(Model $model, SchemaDoc $doc, string $schemaType): void
    {
        $casts = $model->getCasts();

        foreach ($casts as $field => $cast) {
            $this->processCast($field, $cast, $doc);
        }
    }

    private function processCast(string $field, string $cast, SchemaDoc $doc): void
    {
        $schemaType = $this->mapCastToJsonSchema($cast);
        
        if ($schemaType) {
            $doc->setPropertyAttribute($field, 'type', $schemaType['type']);
            
            if (isset($schemaType['format'])) {
                $doc->setPropertyAttribute($field, 'format', $schemaType['format']);
            }
            
            if (isset($schemaType['items'])) {
                $doc->setPropertyAttribute($field, 'items', $schemaType['items']);
            }
        }
    }

    private function mapCastToJsonSchema(string $cast): ?array
    {
        return match ($cast) {
            'int', 'integer' => ['type' => 'integer'],
            'real', 'float', 'double' => ['type' => 'number'],
            'string' => ['type' => 'string'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'object' => ['type' => 'object'],
            'array' => ['type' => 'array'],
            'collection' => ['type' => 'array'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'datetime' => ['type' => 'string', 'format' => 'date-time'],
            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
            'json' => ['type' => 'object'],
            default => str_starts_with($cast, 'decimal:') 
                ? ['type' => config('laravel-schema.decimal_as', 'number')]
                : null,
        };
    }
}