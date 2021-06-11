<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\User;

class TransferUser
{
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
        $transferUser = new TransferUser();
        $transferUser->email = $user->getEmail();
        $transferUser->username = $user->getUsername();
        $transferUser->enabled = $user->isEnabled();
        $transferUser->password = $user->getPassword();
        $transferUser->isVerified = $user->isVerified();
        $transferUser->createdAt = $user->getCreatedAt()->getTimestamp();
        $transferUser->timezone = $user->getTimezone();
        $transferUser->dateFormat = $user->getDateFormat();
        $transferUser->todayDateFormat = $user->getTodayDateFormat();
        $transferUser->durationFormat = $user->getDurationFormat();

        return $transferUser;
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
}
