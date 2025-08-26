<?php

namespace onamfc\EloquentJsonSchema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Enum
{
    public function __construct(
        public readonly array $values
    ) {}
}