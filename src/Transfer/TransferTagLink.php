<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Tag;

class TransferTagLink
{
    public string $name;
    public string $createdBy;

    /**
     * @param iterable|Tag[] $tags
     * @return TransferTagLink[]
     */
    public static function fromTags(iterable $tags): array
    {
        $items = [];
        foreach ($tags as $tag) {
            $items[] = new TransferTagLink($tag->getName(), $tag->getCreatedBy()->getUsername());
        }

        return $items;
    }

    public function __construct(string $name, string $createdBy)
    {
        $this->name = $name;
        $this->createdBy = $createdBy;
    }
}
