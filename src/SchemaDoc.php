<?php

namespace onamfc\EloquentJsonSchema;

class SchemaDoc
{
    public function __construct(
        public string $schema = 'https://json-schema.org/draft/2020-12/schema',
        public string $id = '',
        public string $title = '',
        public string $type = 'object',
        public array $properties = [],
        public array $required = [],
        public array $definitions = [],
        public array $additionalMetadata = []
    ) {}

    public function addProperty(string $name, array $definition): void
    {
        $this->properties[$name] = $definition;
    }

    public function addRequired(string $field): void
    {
        if (!in_array($field, $this->required)) {
            $this->required[] = $field;
        }
    }

    public function removeRequired(string $field): void
    {
        $this->required = array_values(array_filter($this->required, fn($f) => $f !== $field));
    }

    public function setPropertyAttribute(string $property, string $attribute, mixed $value): void
    {
        if (!isset($this->properties[$property])) {
            $this->properties[$property] = [];
        }
        $this->properties[$property][$attribute] = $value;
    }

    public function toArray(): array
    {
        $schema = [
            '$schema' => $this->schema,
            '$id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'properties' => $this->properties,
        ];

        if (!empty($this->required)) {
            $schema['required'] = array_values($this->required);
        }

        if (!empty($this->definitions)) {
            $schema['$defs'] = $this->definitions;
        }

        foreach ($this->additionalMetadata as $key => $value) {
            $schema[$key] = $value;
        }

        return $schema;
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->toArray(), $flags);
    }
}