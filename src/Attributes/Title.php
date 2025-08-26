<?php

namespace onamfc\EloquentJsonSchema\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Title
{
    public function __construct(
        public readonly string $title
    ) {}
}