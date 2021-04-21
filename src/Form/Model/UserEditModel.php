<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\User;

class UserEditModel
{
    private string $timezone;

    public static function fromEntity(User $user): self
    {
        $model = new UserEditModel();
        $model->setTimezone($user->getTimezone());

        return $model;
    }

    public function __construct() {

    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): UserEditModel
    {
        $this->timezone = $timezone;
        return $this;
    }
}
