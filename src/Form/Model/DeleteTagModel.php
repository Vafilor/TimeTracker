<?php

declare(strict_types=1);

namespace App\Form\Model;

class DeleteTagModel
{
    private ?string $replacementTag = null;

    public function getReplacementTag(): ?string
    {
        return $this->replacementTag;
    }

    public function setReplacementTag(?string $replacementTag): self
    {
        $this->replacementTag = $replacementTag;

        return $this;
    }
}
