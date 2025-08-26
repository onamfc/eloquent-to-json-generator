<?php

namespace onamfc\EloquentJsonSchema\Contracts;

use Illuminate\Database\Eloquent\Model;
use onamfc\EloquentJsonSchema\SchemaDoc;

interface SchemaContributor
{
    public function contribute(Model $model, SchemaDoc $doc, string $schemaType): void;
}