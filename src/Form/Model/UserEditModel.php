<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\User;

class UserEditModel
{
    private string $timezone;
    private string $dateFormat;
    private string $durationFormat;

    public static function fromEntity(User $user): self
    {
        $model = new UserEditModel();
        $model->setTimezone($user->getTimezone());
        $model->setDateFormat($user->getDateFormat());
        $model->setDurationFormat($user->getDurationFormat());

        return $model;
    }

    public function __construct() {

    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function getDurationFormat(): string
    {
        return $this->durationFormat;
    }

    public function setDurationFormat(string $durationFormat): self
    {
        $this->durationFormat = $durationFormat;
        return $this;
    }
}
