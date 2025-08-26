<?php

namespace onamfc\EloquentJsonSchema\Resolvers;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use onamfc\EloquentJsonSchema\Attributes;
use onamfc\EloquentJsonSchema\Contracts\SchemaContributor;
use onamfc\EloquentJsonSchema\SchemaDoc;

class AttributesResolver implements SchemaContributor
{
    public function contribute(Model $model, SchemaDoc $doc, string $schemaType): void
    {
        $reflection = new ReflectionClass($model);
        
        // Process class-level attributes
        $this->processClassAttributes($reflection, $doc, $schemaType);
        
        // Process property-level attributes
        $this->processPropertyAttributes($reflection, $doc);
    }

    private function processClassAttributes(ReflectionClass $reflection, SchemaDoc $doc, string $schemaType): void
    {
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            match (get_class($instance)) {
                Attributes\SchemaName::class => $doc->title = $instance->name,
                Attributes\RequestOnly::class => $this->handleRequestOnly($instance, $doc, $schemaType),
                Attributes\ResponseOnly::class => $this->handleResponseOnly($instance, $doc, $schemaType),
                default => null,
            };
        }
    }

    private function processPropertyAttributes(ReflectionClass $reflection, SchemaDoc $doc): void
    {
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $attributes = $property->getAttributes();
            $propertyName = $property->getName();

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();

                match (get_class($instance)) {
                    Attributes\Title::class => $doc->setPropertyAttribute($propertyName, 'title', $instance->title),
                    Attributes\Description::class => $doc->setPropertyAttribute($propertyName, 'description', $instance->description),
                    Attributes\Format::class => $doc->setPropertyAttribute($propertyName, 'format', $instance->format),
                    Attributes\Enum::class => $doc->setPropertyAttribute($propertyName, 'enum', $instance->values),
                    default => null,
                };
            }
        }
    }

    private function handleRequestOnly(Attributes\RequestOnly $attribute, SchemaDoc $doc, string $schemaType): void
    {
        if ($schemaType === 'response') {
            // Remove these fields from response schema
            foreach ($attribute->fields as $field) {
                unset($doc->properties[$field]);
                $doc->removeRequired($field);
            }
        }
    }

    private function handleResponseOnly(Attributes\ResponseOnly $attribute, SchemaDoc $doc, string $schemaType): void
    {
        if ($schemaType === 'request') {
            // Remove these fields from request schema
            foreach ($attribute->fields as $field) {
                unset($doc->properties[$field]);
                $doc->removeRequired($field);
            }
        }
    }
}