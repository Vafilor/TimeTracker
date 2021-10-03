<?php

declare(strict_types=1);

namespace App\Form\Model;

class AddTimestampModel
{
    private string $tagIds;

    public function __construct()
    {
        $this->tagIds = '';
    }

    public function getTagIds(): string
    {
        return $this->tagIds;
    }

    public function setTagIds(string $tagIds): self
    {
        $this->tagIds = $tagIds;
        return $this;
    }
}
