<?php

namespace onamfc\EloquentJsonSchema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ResponseOnly
{
    public function __construct(
        public readonly array $fields
    ) {}
}