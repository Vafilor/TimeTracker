<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\Tag;
use App\Entity\TagLink;
use Doctrine\Common\Collections\Collection;

/**
 * Provides convenience methods for any Entity that has tags.
 * Required: the entity has a property called tagLinks.
 *
 * @property $tagLinks
 *
 * Trait TaggableTrait
 * @package App\Traits
 */
trait TaggableTrait
{
    /**
     * @return Collection|TagLink[]
     */
    public function getTagLinks(): Collection
    {
        return $this->tagLinks;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): array|Collection
    {
        $tags = [];

        foreach ($this->getTagLinks() as $tagLink) {
            $tags[] = $tagLink->getTag();
        }

        usort($tags, fn (Tag $a, Tag $b) => strcmp($a->getName(), $b->getName()));

        return $tags;
    }

    /**
     * @return string the tag names separated by commas
     */
    public function getTagNames(): string
    {
        $tagNames = array_map(fn (Tag $tag) => $tag->getName(), $this->getTags());

        return implode(',', $tagNames);
    }
}
