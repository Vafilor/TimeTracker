<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Task;
use App\Entity\User;
use App\Util\DateFormatType;

class ApiTask
{
    public string $id;
    public string $name;
    public string $description;
    public string $createdAt;
    public ?string $completedAt = null;
    public ?string $url = null;

    public static function fromEntity(Task $task, User $user, string $format = DateFormatType::DATE_TIME): ApiTask
    {
        $apiTask = new ApiTask($task->getIdString(), $task->getName());
        $apiTask->createdAt = ApiDateTime::formatUserDate($task->getCreatedAt(), $user, $format);
        $apiTask->description = $task->getDescription();
        if ($task->completed()) {
            $completedAtString = ApiDateTime::formatUserDate($task->getCompletedAt(), $user, $format);
            $apiTask->completedAt = $completedAtString;
        }

        return $apiTask;
    }

    /**
     * @param Task[] $entities
     * @param User $user
     * @param string $format
     * @return ApiTask[]
     */
    public static function fromEntities(iterable $entities, User $user, string $format = DateFormatType::DATE_TIME): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity, $user, $format);
        }

        return $items;
    }

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = '';
        $this->completedAt = null;
        $this->url = null;
    }
}
