<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\User;

/**
 * Trait AssignableTrait.
 *
 * Provides convenience methods for any Entity that is assignedTo a user.
 * Required: the entity has a property called $assignedTo.
 *
 * @property User $assignedTo
 */
trait AssignableToUserTrait
{
    public function getAssignedTo(): User
    {
        return $this->assignedTo;
    }

    public function assignTo(User $assignee): static
    {
        $this->assignedTo = $assignee;

        return $this;
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assignedTo->equalIds($user);
    }
}
