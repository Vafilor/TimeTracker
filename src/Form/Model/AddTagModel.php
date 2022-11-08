<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class AddTagModel
{
    #[Assert\NotBlank(normalizer: 'trim', message: 'Tag name can not be blank')]
    private string $name;
    private ?string $color;

    public function __construct()
    {
        $this->name = '';
        $this->color = '#5d5d5d';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        if (!is_null($color)) {
            $color = '#5d5d5d';
        }

        $this->color = $color;

        return $this;
    }
}
