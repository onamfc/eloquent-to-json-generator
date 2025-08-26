<?php

namespace onamfc\EloquentJsonSchema;

use Illuminate\Support\ServiceProvider;
use onamfc\EloquentJsonSchema\Commands\GenerateSchemaCommand;
use onamfc\EloquentJsonSchema\Commands\SchemaDiffCommand;
use onamfc\EloquentJsonSchema\Commands\PublishOpenApiCommand;

class LaravelSchemaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-schema.php', 'laravel-schema');

        $this->app->singleton(SchemaGenerator::class);
        $this->app->singleton(SchemaRegistry::class);

        // Add this binding for SchemaPipeline
        $this->app->singleton(SchemaPipeline::class, function ($app) {
            $resolvers = config('laravel-schema.resolvers', []);
            $contributors = [];
            foreach ($resolvers as $resolverClass) {
                if (class_exists($resolverClass)) {
                    $contributors[] = $app->make($resolverClass);
                }
            }
            return new SchemaPipeline($contributors);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-schema.php' => config_path('laravel-schema.php'),
            ], 'config');

            $this->commands([
                GenerateSchemaCommand::class,
                SchemaDiffCommand::class,
                PublishOpenApiCommand::class,
            ]);
        }
    }
}