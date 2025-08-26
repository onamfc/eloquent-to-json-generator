<?php

namespace onamfc\EloquentJsonSchema\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use onamfc\EloquentJsonSchema\Contracts\SchemaContributor;
use onamfc\EloquentJsonSchema\SchemaDoc;

class RelationsResolver implements SchemaContributor
{
    public function contribute(Model $model, SchemaDoc $doc, string $schemaType): void
    {
        $relations = $this->getModelRelations($model);

        foreach ($relations as $relationName => $relation) {
            $this->processRelation($relationName, $relation, $doc, $schemaType);
        }
    }

    private function getModelRelations(Model $model): array
    {
        $relations = [];
        $methods = get_class_methods($model);

        foreach ($methods as $method) {
            if ($method === 'relations' || str_starts_with($method, '_')) {
                continue;
            }

            try {
                $reflection = new \ReflectionMethod($model, $method);
                if ($reflection->isPublic() && !$reflection->isStatic() && $reflection->getNumberOfParameters() === 0) {
                    $result = $model->$method();
                    if ($result instanceof Relations\Relation) {
                        $relations[$method] = $result;
                    }
                }
            } catch (\Throwable) {
                // Skip methods that can't be called
                continue;
            }
        }

        return $relations;
    }

    private function processRelation(string $relationName, Relations\Relation $relation, SchemaDoc $doc, string $schemaType): void
    {
        $relatedModel = $relation->getRelated();
        $relatedClassName = get_class($relatedModel);
        $relatedName = class_basename($relatedClassName);

        $relationshipDepth = config('laravel-schema.relationship_depth', 'id');

        if ($relationshipDepth === 'id') {
            $this->processAsIdReference($relationName, $relation, $doc);
        } else {
            $this->processAsFullReference($relationName, $relatedName, $relation, $doc);
        }
    }

    private function processAsIdReference(string $relationName, Relations\Relation $relation, SchemaDoc $doc): void
    {
        if ($relation instanceof Relations\HasMany || $relation instanceof Relations\BelongsToMany) {
            $doc->addProperty($relationName, [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'format' => 'uuid'
                ]
            ]);
        } else {
            $doc->addProperty($relationName, [
                'type' => 'string',
                'format' => 'uuid'
            ]);
        }
    }

    private function processAsFullReference(string $relationName, string $relatedName, Relations\Relation $relation, SchemaDoc $doc): void
    {
        $baseUri = config('laravel-schema.base_uri');
        $version = config('laravel-schema.version');
        $schemaRef = "{$baseUri}/{$version}/models/{$relatedName}.response.schema.json";

        if ($relation instanceof Relations\HasMany || $relation instanceof Relations\BelongsToMany) {
            $doc->addProperty($relationName, [
                'type' => 'array',
                'items' => [
                    '$ref' => $schemaRef
                ]
            ]);
        } elseif ($relation instanceof Relations\MorphTo) {
            // Handle polymorphic relationships with oneOf
            $doc->addProperty($relationName, [
                'oneOf' => [
                    ['type' => 'null'],
                    ['$ref' => $schemaRef]
                ]
            ]);
        } else {
            $doc->addProperty($relationName, [
                '$ref' => $schemaRef
            ]);
        }
    }
}