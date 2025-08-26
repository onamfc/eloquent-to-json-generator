<?php

namespace onamfc\EloquentJsonSchema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Format
{
    public function __construct(
        public readonly string $format
    ) {}
}