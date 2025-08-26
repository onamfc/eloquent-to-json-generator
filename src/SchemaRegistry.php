<?php

namespace onamfc\EloquentJsonSchema;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class SchemaRegistry
{
    public function get(string $schemaKey): array
    {
        return Cache::remember("schema.{$schemaKey}", 3600, function () use ($schemaKey) {
            return $this->loadSchema($schemaKey);
        });
    }

    public function forget(string $schemaKey): void
    {
        Cache::forget("schema.{$schemaKey}");
    }

    public function flush(): void
    {
        Cache::flush();
    }

    private function loadSchema(string $schemaKey): array
    {
        [$modelName, $type] = explode('.', $schemaKey);
        
        $outputDir = storage_path(config('laravel-schema.output_directory'));
        $version = config('laravel-schema.version');
        $schemaPath = "{$outputDir}/{$version}/models/{$modelName}.{$type}.schema.json";

        if (!File::exists($schemaPath)) {
            throw new \InvalidArgumentException("Schema not found: {$schemaKey}");
        }

        return json_decode(File::get($schemaPath), true);
    }
}