<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\User;
use App\Util\DateTimeUtil;
use Ramsey\Uuid\Uuid;

class TransferUser
{
    public string $id;
    public ?string $email;
    public string $username;
    public bool $enabled;
    public string $password; // Hashed.
    public bool $isVerified;
    public int $createdAt;
    public string $timezone;
    public string $dateFormat;
    public string $todayDateFormat;
    public string $durationFormat;

    public static function fromEntity(User $user): TransferUser
    {
        $transfer = new TransferUser();

        $transfer->id = $user->getIdString();
        $transfer->email = $user->getEmail();
        $transfer->username = $user->getUsername();
        $transfer->enabled = $user->isEnabled();
        $transfer->password = $user->getPassword();
        $transfer->isVerified = $user->isVerified();
        $transfer->createdAt = $user->getCreatedAt()->getTimestamp();
        $transfer->timezone = $user->getTimezone();
        $transfer->dateFormat = $user->getDateTimeFormat();
        $transfer->todayDateFormat = $user->getTodayDateTimeFormat();
        $transfer->durationFormat = $user->getDurationFormat();

        return $transfer;
    }

    /**
     * @param User[]|iterable $entities
     * @return TransferUser[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function toEntity(): User
    {
        $user = new User(DateTimeUtil::dateFromTimestamp($this->createdAt));

        $user->setId(Uuid::fromString($this->id));
        $user->setEmail($this->email);
        $user->setUsername($this->username);
        $user->setEnabled($this->enabled);
        $user->setPassword($this->password);
        $user->setIsVerified($this->isVerified);
        $user->setTimezone($this->timezone);
        $user->setDateTimeFormat($this->dateFormat);
        $user->setTodayDateTimeFormat($this->todayDateFormat);
        $user->setDurationFormat($this->durationFormat);

        return $user;
    }
}
