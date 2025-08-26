<?php

namespace onamfc\EloquentJsonSchema\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use onamfc\EloquentJsonSchema\Http\Middleware\ValidateWithSchemaMiddleware;
use onamfc\EloquentJsonSchema\LaravelSchemaServiceProvider;
use onamfc\EloquentJsonSchema\SchemaRegistry;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [LaravelSchemaServiceProvider::class];
    }

    public function test_validates_request_against_schema(): void
    {
        // Mock schema registry
        $registry = $this->createMock(SchemaRegistry::class);
        $registry->method('get')
            ->with('User.request')
            ->willReturn([
                'type' => 'object',
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'name' => ['type' => 'string', 'minLength' => 2],
                ],
                'required' => ['email', 'name']
            ]);

        $middleware = new ValidateWithSchemaMiddleware($registry);

        // Valid request
        $validRequest = Request::create('/test', 'POST', [], [], [], [], json_encode([
            'email' => 'test@example.com',
            'name' => 'John Doe'
        ]));
        $validRequest->headers->set('Content-Type', 'application/json');

        $response = $middleware->handle($validRequest, fn($req) => response('success'), 'User.request');
        $this->assertEquals('success', $response->getContent());

        // Invalid request
        $invalidRequest = Request::create('/test', 'POST', [], [], [], [], json_encode([
            'email' => 'invalid-email',
            'name' => 'J' // Too short
        ]));
        $invalidRequest->headers->set('Content-Type', 'application/json');

        $response = $middleware->handle($invalidRequest, fn($req) => response('success'), 'User.request');
        $this->assertEquals(422, $response->getStatusCode());
    }
}