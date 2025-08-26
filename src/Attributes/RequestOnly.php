<?php

namespace onamfc\EloquentJsonSchema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RequestOnly
{
    public function __construct(
        public readonly array $fields
    ) {}
}