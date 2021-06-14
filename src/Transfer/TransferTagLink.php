<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Tag;

class TransferTagLink
{
    public string $id = '';
    public string $name = '';
    public string $assignedTo = '';

    /**
     * @param iterable|Tag[] $tags
     * @return TransferTagLink[]
     */
    public static function fromTags(iterable $tags): array
    {
        $items = [];
        foreach ($tags as $tag) {
            $item = new TransferTagLink();
            $item->id = $tag->getIdString();
            $item->name = $tag->getName();
            $item->assignedTo = $tag->getAssignedTo()->getUsername();

            $items[] = $item;
        }

        return $items;
    }
}
