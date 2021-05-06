<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Timestamp;
use DateTime;

class TimestampEditModel
{
    private DateTime $createdAt;

    public static function fromEntity(Timestamp $timestamp): self
    {
        $model = new TimestampEditModel();
        $model->setCreatedAt($timestamp->getCreatedAt());

        return $model;
    }

    public function __construct() {

    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
