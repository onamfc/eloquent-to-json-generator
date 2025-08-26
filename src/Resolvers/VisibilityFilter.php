<?php

namespace onamfc\EloquentJsonSchema\Resolvers;

use Illuminate\Database\Eloquent\Model;
use onamfc\EloquentJsonSchema\Contracts\SchemaContributor;
use onamfc\EloquentJsonSchema\SchemaDoc;

class VisibilityFilter implements SchemaContributor
{
    public function contribute(Model $model, SchemaDoc $doc, string $schemaType): void
    {
        if ($schemaType === 'request') {
            $this->processRequestVisibility($model, $doc);
        } else {
            $this->processResponseVisibility($model, $doc);
        }
    }

    private function processRequestVisibility(Model $model, SchemaDoc $doc): void
    {
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();

        // If fillable is defined, only include fillable fields
        if (!empty($fillable)) {
            $properties = array_intersect_key($doc->properties, array_flip($fillable));
            $doc->properties = $properties;
            $doc->required = array_intersect($doc->required, $fillable);
        }

        // Remove guarded fields
        if (!empty($guarded) && !in_array('*', $guarded)) {
            foreach ($guarded as $field) {
                unset($doc->properties[$field]);
                $doc->removeRequired($field);
            }
        }

        // Remove timestamps from request if they're not fillable
        if (!$model->isFillable('created_at')) {
            unset($doc->properties['created_at']);
            $doc->removeRequired('created_at');
        }
        
        if (!$model->isFillable('updated_at')) {
            unset($doc->properties['updated_at']);
            $doc->removeRequired('updated_at');
        }
    }

    private function processResponseVisibility(Model $model, SchemaDoc $doc): void
    {
        $hidden = $model->getHidden();
        $visible = $model->getVisible();
        $appends = $model->getAppends();

        // Remove hidden fields
        foreach ($hidden as $field) {
            unset($doc->properties[$field]);
            $doc->removeRequired($field);
        }

        // If visible is defined, only include visible fields
        if (!empty($visible)) {
            $properties = array_intersect_key($doc->properties, array_flip($visible));
            $doc->properties = $properties;
            $doc->required = array_intersect($doc->required, $visible);
        }

        // Add appended attributes
        foreach ($appends as $field) {
            if (!isset($doc->properties[$field])) {
                // Try to infer type from accessor method
                $accessorMethod = 'get' . Str::studly($field) . 'Attribute';
                if (method_exists($model, $accessorMethod)) {
                    $doc->addProperty($field, [
                        'type' => 'string', // Default to string for computed properties
                        'readOnly' => true
                    ]);
                }
            }
        }

        // Mark certain fields as readOnly
        $readOnlyFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
        foreach ($readOnlyFields as $field) {
            if (isset($doc->properties[$field])) {
                $doc->setPropertyAttribute($field, 'readOnly', true);
            }
        }
    }
}