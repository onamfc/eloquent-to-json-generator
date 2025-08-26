<?php

namespace onamfc\EloquentJsonSchema\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use onamfc\EloquentJsonSchema\Contracts\SchemaContributor;
use onamfc\EloquentJsonSchema\SchemaDoc;

class RulesResolver implements SchemaContributor
{
    public function contribute(Model $model, SchemaDoc $doc, string $schemaType): void
    {
        $rules = $this->extractRules($model, $schemaType);

        foreach ($rules as $field => $fieldRules) {
            $this->processFieldRules($field, $fieldRules, $doc);
        }
    }

    private function extractRules(Model $model, string $schemaType): array
    {
        $rules = [];

        // Try to get rules from model method
        if (method_exists($model, 'rules')) {
            $rules = $model->rules($schemaType) ?? [];
        }

        // Try to find associated FormRequest classes
        if (config('laravel-schema.use_form_request_rules')) {
            $formRequestRules = $this->findFormRequestRules($model, $schemaType);
            $rules = array_merge($rules, $formRequestRules);
        }

        return $rules;
    }

    private function findFormRequestRules(Model $model, string $schemaType): array
    {
        $modelName = class_basename($model);
        $formRequestClasses = [
            "App\\Http\\Requests\\Store{$modelName}Request",
            "App\\Http\\Requests\\Update{$modelName}Request",
            "App\\Http\\Requests\\{$modelName}Request",
        ];

        foreach ($formRequestClasses as $className) {
            if (class_exists($className)) {
                $request = new $className;
                if (method_exists($request, 'rules')) {
                    return $request->rules();
                }
            }
        }

        return [];
    }

    private function processFieldRules(string $field, array|string $rules, SchemaDoc $doc): void
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $rule) {
            $this->processRule($field, $rule, $doc);
        }
    }

    private function processRule(string $field, string $rule, SchemaDoc $doc): void
    {
        [$ruleName, $parameters] = $this->parseRule($rule);

        match ($ruleName) {
            'required' => $doc->addRequired($field),
            'string' => $doc->setPropertyAttribute($field, 'type', 'string'),
            'integer' => $doc->setPropertyAttribute($field, 'type', 'integer'),
            'numeric' => $doc->setPropertyAttribute($field, 'type', 'number'),
            'boolean' => $doc->setPropertyAttribute($field, 'type', 'boolean'),
            'array' => $doc->setPropertyAttribute($field, 'type', 'array'),
            'email' => $this->setStringFormat($field, 'email', $doc),
            'url' => $this->setStringFormat($field, 'uri', $doc),
            'uuid' => $this->setStringFormat($field, 'uuid', $doc),
            'date' => $this->setStringFormat($field, 'date', $doc),
            'date_format' => $this->handleDateFormat($field, $parameters, $doc),
            'min' => $this->handleMin($field, $parameters, $doc),
            'max' => $this->handleMax($field, $parameters, $doc),
            'between' => $this->handleBetween($field, $parameters, $doc),
            'in' => $this->handleEnum($field, $parameters, $doc),
            'regex' => $this->handleRegex($field, $parameters, $doc),
            'unique' => $this->handleUnique($field, $parameters, $doc),
            default => null,
        };
    }

    private function parseRule(string $rule): array
    {
        if (!str_contains($rule, ':')) {
            return [$rule, []];
        }

        [$name, $params] = explode(':', $rule, 2);
        $parameters = explode(',', $params);

        return [$name, $parameters];
    }

    private function setStringFormat(string $field, string $format, SchemaDoc $doc): void
    {
        $doc->setPropertyAttribute($field, 'type', 'string');
        $doc->setPropertyAttribute($field, 'format', $format);
    }

    private function handleDateFormat(string $field, array $parameters, SchemaDoc $doc): void
    {
        $format = $parameters[0] ?? 'Y-m-d H:i:s';
        
        $doc->setPropertyAttribute($field, 'type', 'string');
        
        if ($format === 'Y-m-d') {
            $doc->setPropertyAttribute($field, 'format', 'date');
        } elseif (str_contains($format, 'H:i:s')) {
            $doc->setPropertyAttribute($field, 'format', 'date-time');
        }
    }

    private function handleMin(string $field, array $parameters, SchemaDoc $doc): void
    {
        $min = (int) ($parameters[0] ?? 0);
        $property = $doc->properties[$field] ?? [];
        
        if (($property['type'] ?? '') === 'string') {
            $doc->setPropertyAttribute($field, 'minLength', $min);
        } elseif (in_array($property['type'] ?? '', ['integer', 'number'])) {
            $doc->setPropertyAttribute($field, 'minimum', $min);
        }
    }

    private function handleMax(string $field, array $parameters, SchemaDoc $doc): void
    {
        $max = (int) ($parameters[0] ?? 0);
        $property = $doc->properties[$field] ?? [];
        
        if (($property['type'] ?? '') === 'string') {
            $doc->setPropertyAttribute($field, 'maxLength', $max);
        } elseif (in_array($property['type'] ?? '', ['integer', 'number'])) {
            $doc->setPropertyAttribute($field, 'maximum', $max);
        }
    }

    private function handleBetween(string $field, array $parameters, SchemaDoc $doc): void
    {
        $min = (int) ($parameters[0] ?? 0);
        $max = (int) ($parameters[1] ?? 0);
        
        $this->handleMin($field, [$min], $doc);
        $this->handleMax($field, [$max], $doc);
    }

    private function handleEnum(string $field, array $parameters, SchemaDoc $doc): void
    {
        $doc->setPropertyAttribute($field, 'enum', $parameters);
    }

    private function handleRegex(string $field, array $parameters, SchemaDoc $doc): void
    {
        $pattern = $parameters[0] ?? '';
        // Remove leading/trailing delimiters if present
        $pattern = trim($pattern, '/');
        
        $doc->setPropertyAttribute($field, 'pattern', $pattern);
    }

    private function handleUnique(string $field, array $parameters, SchemaDoc $doc): void
    {
        // JSON Schema doesn't natively support uniqueness constraints
        // We use a vendor extension
        $doc->setPropertyAttribute($field, 'x-unique', true);
    }
}