<?php

namespace onamfc\EloquentJsonSchema\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Opis\JsonSchema\Validator;
use onamfc\EloquentJsonSchema\SchemaRegistry;

class ValidateWithSchemaMiddleware
{
    public function __construct(
        private SchemaRegistry $registry
    ) {}

    public function handle(Request $request, Closure $next, string $schemaKey): mixed
    {
        try {
            $schema = $this->registry->get($schemaKey);
            $data = $request->json()->all();

            $validator = new Validator();
            $result = $validator->validate($data, $schema);

            if (!$result->isValid()) {
                $errors = [];
                foreach ($result->getErrors() as $error) {
                    $errors[] = [
                        'field' => $error->dataPointer(),
                        'message' => $error->message(),
                    ];
                }

                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Schema validation error: ' . $e->getMessage()
            ], 500);
        }

        return $next($request);
    }
}