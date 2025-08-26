<?php

namespace onamfc\EloquentJsonSchema;

use Illuminate\Database\Eloquent\Model;
use onamfc\EloquentJsonSchema\Contracts\SchemaContributor;

class SchemaPipeline
{
    /**
     * @param SchemaContributor[] $contributors
     */
    public function __construct(
        private array $contributors
    ) {}

    public function build(string $modelClass, string $schemaType = 'response'): SchemaDoc
    {
        $model = new $modelClass;
        $doc = new SchemaDoc();

        // Set basic metadata
        $doc->title = class_basename($modelClass);
        $doc->id = $this->generateSchemaId($modelClass, $schemaType);

        // Run all contributors
        foreach ($this->contributors as $contributor) {
            $contributor->contribute($model, $doc, $schemaType);
        }

        return $doc;
    }

    private function generateSchemaId(string $modelClass, string $schemaType): string
    {
        $baseUri = config('laravel-schema.base_uri');
        $version = config('laravel-schema.version');
        $modelName = class_basename($modelClass);

        return "{$baseUri}/{$version}/models/{$modelName}.{$schemaType}.schema.json";
    }
}