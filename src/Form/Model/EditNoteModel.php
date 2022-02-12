<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Note;
use DateTime;

class EditNoteModel
{
    private ?string $title;
    private ?string $content;
    private ?DateTime $forDate;

    public static function fromEntity(Note $note): self
    {
        $model = new EditNoteModel($note->getTitle(), $note->getContent());
        $model->forDate = $note->getForDate();

        return $model;
    }

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
        $this->forDate = null;
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

    public function getForDate(): ?DateTime
    {
        return $this->forDate;
    }

    public function setForDate(?DateTime $forDate): self
    {
        $this->forDate = $forDate;
        return $this;
    }
}
