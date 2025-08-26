<?php

namespace onamfc\EloquentJsonSchema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SchemaGenerator
{
    public function __construct(
        private SchemaPipeline $pipeline
    ) {}

    public function generateForModel(string $modelClass): array
    {
        $requestSchema = $this->pipeline->build($modelClass, 'request');
        $responseSchema = $this->pipeline->build($modelClass, 'response');

        return [
            'request' => $requestSchema,
            'response' => $responseSchema,
        ];
    }

    public function generateAll(): array
    {
        $models = $this->discoverModels();
        $schemas = [];

        foreach ($models as $modelClass) {
            $schemas[$modelClass] = $this->generateForModel($modelClass);
        }

        return $schemas;
    }

    public function saveSchemas(array $schemas): void
    {
        $outputDir = storage_path(config('laravel-schema.output_directory'));
        $version = config('laravel-schema.version');
        $versionedDir = "{$outputDir}/{$version}/models";

        // Ensure directory exists
        File::ensureDirectoryExists($versionedDir);

        foreach ($schemas as $modelClass => $schemaSet) {
            $modelName = class_basename($modelClass);

            // Save request schema
            $requestPath = "{$versionedDir}/{$modelName}.request.schema.json";
            File::put($requestPath, $schemaSet['request']->toJson());

            // Save response schema
            $responsePath = "{$versionedDir}/{$modelName}.response.schema.json";
            File::put($responsePath, $schemaSet['response']->toJson());
        }
    }

    private function discoverModels(): array
    {
        $modelsPath = config('laravel-schema.models_directory');
        $models = [];

        if (!File::exists($modelsPath)) {
            return $models;
        }

        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = $file->getRelativePathname();
                $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

                if (class_exists($className) && is_subclass_of($className, Model::class)) {
                    $models[] = $className;
                }
            }
        }

        return $models;
    }
}