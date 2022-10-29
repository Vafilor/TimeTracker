<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class TagManager
{
    private TagRepository $tagRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(TagRepository $tagRepository, EntityManagerInterface $entityManager)
    {
        $this->tagRepository = $tagRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * parseFromString will take a comma delimited string of tag names and return an array of them.
     * Leading and trailing whitesapce from each name is removed.
     *
     * @return string[]
     */
    public function parseFromString(string $names): array
    {
        $tagNames = explode(',', $names);

        return array_map(
            fn ($name) => trim($name),
            $tagNames
        );
    }

    /**
     * Finds a tag for a given name/user. If it does not exist, it is created and persisted to the database.
     */
    public function findOrCreateByName(string $name, User $assignedTo): Tag
    {
        $tag = $this->tagRepository->findOneBy(['name' => $name, 'assignedTo' => $assignedTo]);
        if (is_null($tag)) {
            $tag = new Tag($assignedTo, $name);
            $this->entityManager->persist($tag);
        }

        return $tag;
    }

    /**
     * Given an array of names, this will find all of the existing Tag entities in the database
     * with those names. If the names do not exist, they will be created and persisted (but not flushed)
     * to the database.
     *
     * @param string[] $names
     * @param User     $user  the creator of the tags
     *
     * @return Tag[]
     */
    public function findOrCreateByNames(array $names, User $user): array
    {
        $nameMap = [];
        foreach ($names as $name) {
            $nameMap[$name] = true;
        }

        $tags = $this->tagRepository->findByKeysQuery('name', $names, 'tag')
            ->andWhere('tag.assignedTo = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;

        foreach ($tags as $existingTag) {
            if (array_key_exists($existingTag->getName(), $nameMap)) {
                unset($nameMap[$existingTag->getName()]);
            }
        }

        foreach ($nameMap as $name => $value) {
            $newTag = new Tag($user, $name);
            $tags[] = $newTag;

            $this->entityManager->persist($newTag);
        }

        return $tags;
    }
}
