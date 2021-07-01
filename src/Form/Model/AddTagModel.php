<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class AddTagModel
{
    /**
     * @Assert\NotBlank(normalizer="trim", message="Tag name can not be blank")
     */
    private ?string $name;

    public function __construct()
    {
        $this->name = '';
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
