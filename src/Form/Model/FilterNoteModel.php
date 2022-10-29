<?php

declare(strict_types=1);

namespace App\Form\Model;

class FilterNoteModel
{
    private ?string $tags;
    private ?string $content;

    public function __construct()
    {
        $this->tags = null;
        $this->content = null;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function hasTags(): bool
    {
        return !is_null($this->tags) && '' !== $this->tags;
    }

    /**
     * @return string[]
     */
    public function getTagsArray(): array
    {
        if (is_null($this->tags) || '' === $this->tags) {
            return [];
        }

        $results = explode(',', $this->tags);

        $results = array_map(
            fn ($tagRaw) => str_replace(' ', '', $tagRaw),
            $results
        );

        return $results;
    }

    public function setTags(?string $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function hasContent(): bool
    {
        return !is_null($this->content) && '' !== $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
