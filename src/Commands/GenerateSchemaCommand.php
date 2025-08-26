<?php

namespace onamfc\EloquentJsonSchema\Commands;

use Illuminate\Console\Command;
use onamfc\EloquentJsonSchema\SchemaGenerator;

class GenerateSchemaCommand extends Command
{
    protected $signature = 'schema:generate {model?}';
    protected $description = 'Generate JSON Schema files from Eloquent models';

    public function handle(SchemaGenerator $generator): int
    {
        $modelClass = $this->argument('model');

        if ($modelClass) {
            $this->generateSingleModel($generator, $modelClass);
        } else {
            $this->generateAllModels($generator);
        }

        return self::SUCCESS;
    }

    private function generateSingleModel(SchemaGenerator $generator, string $modelClass): void
    {
        if (!str_contains($modelClass, '\\')) {
            $modelClass = "App\\Models\\{$modelClass}";
        }

        if (!class_exists($modelClass)) {
            $this->error("Model {$modelClass} not found.");
            return;
        }

        $this->info("Generating schema for {$modelClass}...");

        $schemas = [$modelClass => $generator->generateForModel($modelClass)];
        $generator->saveSchemas($schemas);

        $this->info("Schema generated successfully for {$modelClass}");
    }

    private function generateAllModels(SchemaGenerator $generator): void
    {
        $this->info('Scanning for Eloquent models...');

        $schemas = $generator->generateAll();

        if (empty($schemas)) {
            $this->warn('No models found.');
            return;
        }

        $this->info('Generating schemas for ' . count($schemas) . ' models...');

        $generator->saveSchemas($schemas);

        foreach (array_keys($schemas) as $modelClass) {
            $this->line("  ✓ " . class_basename($modelClass));
        }

        $this->info('All schemas generated successfully!');
    }
}