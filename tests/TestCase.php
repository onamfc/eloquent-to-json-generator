<?php

namespace onamfc\EloquentJsonSchema\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use onamfc\EloquentJsonSchema\LaravelSchemaServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelSchemaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}