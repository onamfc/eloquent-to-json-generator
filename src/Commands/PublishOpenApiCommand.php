<?php

namespace onamfc\EloquentJsonSchema\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishOpenApiCommand extends Command
{
    protected $signature = 'schema:publish-openapi';
    protected $description = 'Export schemas as OpenAPI components';

    public function handle(): int
    {
        $outputDir = storage_path(config('laravel-schema.output_directory'));
        $version = config('laravel-schema.version');
        $modelsDir = "{$outputDir}/{$version}/models";

        if (!File::exists($modelsDir)) {
            $this->error('No schemas found. Run php artisan schema:generate first.');
            return self::FAILURE;
        }

        $components = $this->buildOpenApiComponents($modelsDir);
        $componentsPath = "{$outputDir}/{$version}/openapi.components.json";

        File::put($componentsPath, json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("OpenAPI components exported to: {$componentsPath}");
        $this->line('You can now include these in your OpenAPI specification.');

        return self::SUCCESS;
    }

    private function buildOpenApiComponents(string $modelsDir): array
    {
        $components = ['schemas' => []];
        $files = File::files($modelsDir);

        foreach ($files as $file) {
            $content = json_decode(File::get($file->getPathname()), true);
            $filename = $file->getFilenameWithoutExtension();
            
            // Convert file name to OpenAPI component name
            $componentName = str_replace('.', '', ucwords($filename, '.'));
            $components['schemas'][$componentName] = $content;
        }

        return $components;
    }
}