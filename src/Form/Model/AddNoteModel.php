<?php

declare(strict_types=1);

namespace App\Form\Model;

class AddNoteModel
{
    private string $title;
    private string $content;

    public function __construct(string $name = '', string $content = '')
    {
        $this->title = $name;
        $this->content = $content;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title): AddNoteModel
    {
        if (is_null($title)) {
            $title = '';
        }

        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        if (is_null($content)) {
            $content = '';
        }

        $this->content = $content;
        return $this;
    }
}
