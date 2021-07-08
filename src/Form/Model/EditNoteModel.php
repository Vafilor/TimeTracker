<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Note;

class NoteEditModel
{
    private ?string $title;
    private ?string $content;

    public static function fromEntity(Note $note): self
    {
        return new NoteEditModel($note->getTitle(), $note->getContent());
    }

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    public function hasTitle(): bool
    {
        return !is_null($this->title);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function hasContent(): bool
    {
        return !is_null($this->content);
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }
}
