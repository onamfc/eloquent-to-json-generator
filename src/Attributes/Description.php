<?php

namespace onamfc\EloquentJsonSchema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Description
{
    public function __construct(
        public readonly string $description
    ) {}
}