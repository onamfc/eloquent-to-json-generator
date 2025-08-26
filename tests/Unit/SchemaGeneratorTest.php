<?php

namespace onamfc\EloquentJsonSchema\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use onamfc\EloquentJsonSchema\LaravelSchemaServiceProvider;
use onamfc\EloquentJsonSchema\SchemaGenerator;
use onamfc\EloquentJsonSchema\Examples\User;

class SchemaGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [LaravelSchemaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-schema.models_directory', __DIR__ . '/../Examples');
    }

    public function test_generates_request_and_response_schemas(): void
    {
        $generator = app(SchemaGenerator::class);
        $schemas = $generator->generateForModel(User::class);

        $this->assertArrayHasKey('request', $schemas);
        $this->assertArrayHasKey('response', $schemas);

        $requestSchema = $schemas['request']->toArray();
        $responseSchema = $schemas['response']->toArray();

        // Request schema should include password
        $this->assertArrayHasKey('password', $requestSchema['properties']);
        
        // Response schema should not include password (hidden)
        $this->assertArrayNotHasKey('password', $responseSchema['properties']);

        // Both should have basic properties
        $this->assertArrayHasKey('email', $requestSchema['properties']);
        $this->assertArrayHasKey('email', $responseSchema['properties']);
    }

    public function test_applies_validation_rules(): void
    {
        $generator = app(SchemaGenerator::class);
        $schemas = $generator->generateForModel(User::class);

        $requestSchema = $schemas['request']->toArray();

        // Check required fields
        $this->assertContains('email', $requestSchema['required']);
        $this->assertContains('name', $requestSchema['required']);

        // Check email format
        $this->assertEquals('email', $requestSchema['properties']['email']['format']);

        // Check string constraints
        $this->assertEquals(2, $requestSchema['properties']['name']['minLength']);
        $this->assertEquals(255, $requestSchema['properties']['name']['maxLength']);

        // Check enum values
        $this->assertEquals(['basic', 'pro', 'enterprise'], $requestSchema['properties']['plan']['enum']);
    }

    public function test_handles_attributes(): void
    {
        $generator = app(SchemaGenerator::class);
        $schemas = $generator->generateForModel(User::class);

        $responseSchema = $schemas['response']->toArray();

        // Check custom title from attribute
        $this->assertEquals('User ID', $responseSchema['properties']['id']['title']);

        // Check custom description
        $this->assertEquals('Primary email address', $responseSchema['properties']['email']['description']);

        // Check custom format
        $this->assertEquals('uuid', $responseSchema['properties']['id']['format']);
    }

    public function test_marks_readonly_fields(): void
    {
        $generator = app(SchemaGenerator::class);
        $schemas = $generator->generateForModel(User::class);

        $responseSchema = $schemas['response']->toArray();

        // ID should be readonly in response
        $this->assertTrue($responseSchema['properties']['id']['readOnly'] ?? false);

        // Timestamps should be readonly
        $this->assertTrue($responseSchema['properties']['created_at']['readOnly'] ?? false);
        $this->assertTrue($responseSchema['properties']['updated_at']['readOnly'] ?? false);
    }
}