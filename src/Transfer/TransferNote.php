<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Note;
use App\Entity\User;
use App\Util\DateTimeUtil;
use Ramsey\Uuid\Uuid;

class TransferNote
{
    public string $id;
    public int $createdAt;
    public int $updatedAt;
    public string $title;
    public string $content;
    public string $assignedTo;

    /**
     * @var TransferTagLink[]
     */
    public array $tags = [];

    public static function fromEntity(Note $entity): self
    {
        $transfer = new TransferNote();

        $transfer->id = $entity->getIdString();
        $transfer->createdAt = $entity->getCreatedAt()->getTimestamp();
        $transfer->updatedAt = $entity->getUpdatedAt()->getTimestamp();
        $transfer->title = $entity->getTitle();
        $transfer->content = $entity->getContent();
        $transfer->assignedTo = $entity->getAssignedTo()->getUsername();
        $transfer->tags = TransferTagLink::fromTags($entity->getTags());

        return $transfer;
    }

    /**
     * @param Note[]|iterable $entities
     * @return self[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function toEntity(User $assignedTo): Note
    {
        $note = new Note($assignedTo, $this->title, $this->content);
        $note->setId(Uuid::fromString($this->id));
        $note->setCreatedAt(DateTimeUtil::dateFromTimestamp($this->createdAt));
        $note->setUpdatedAt(DateTimeUtil::dateFromTimestamp($this->updatedAt));

        return $note;
    }
}
