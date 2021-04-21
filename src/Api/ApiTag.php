<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Tag;

class ApiTag
{
    public string $name;
    public string $color;

    public static function fromEntity(Tag $tag): ApiTag
    {
        return new ApiTag($tag->getName(), $tag->getColor());
    }

    public function __construct(string $name, string $color) {
        $this->name = $name;
        $this->color = $color;
    }
}
