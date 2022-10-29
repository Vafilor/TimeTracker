<?php

declare(strict_types=1);

namespace App\Form\Model;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

class AddNoteModel
{
    /**
     * @Assert\NotBlank()
     */
    private string $title;
    private string $content;
    private ?DateTime $forDate;

    public function __construct(string $title = '', string $content = '')
    {
        $this->title = $title;
        $this->content = $content;
        $this->forDate = null;
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
