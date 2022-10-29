<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\User;

class EditUserModel
{
    private string $timezone;
    private string $dateFormat;
    private string $dateTimeFormat;
    private string $todayDateTimeFormat;
    private string $durationFormat;

    public static function fromEntity(User $user): self
    {
        $model = new EditUserModel();

        $model->setDateFormat($user->getDateFormat());
        $model->setTimezone($user->getTimezone());
        $model->setDateTimeFormat($user->getDateTimeFormat());
        $model->setDurationFormat($user->getDurationFormat());
        $model->setTodayDateTimeFormat($user->getTodayDateTimeFormat());

        return $model;
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

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    public function setDateTimeFormat(string $dateTimeFormat): self
    {
        $this->dateTimeFormat = $dateTimeFormat;

        return $this;
    }

    public function getTodayDateTimeFormat(): string
    {
        return $this->todayDateTimeFormat;
    }

    public function setTodayDateTimeFormat(string $todayDateTimeFormat): self
    {
        $this->todayDateTimeFormat = $todayDateTimeFormat;

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
