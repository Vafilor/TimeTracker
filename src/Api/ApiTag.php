<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Tag;

class ApiTag
{
    public string $name;

    public static function fromEntity(Tag $tag): ApiTag
    {
        return new ApiTag($tag->getName());
    }

    public function __construct(string $name) {
        $this->name = $name;
    }
}
