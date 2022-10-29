<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Tag;

class EditTagModel
{
    private string $color;

    public static function fromEntity(Tag $tag)
    {
        return new EditTagModel($tag->getColor());
    }

    public function __construct(string $color)
    {
        $this->setColor($color);
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }
}
