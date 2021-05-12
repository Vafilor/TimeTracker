<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Tag;
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

    public function findOrCreateByName($name): Tag
    {
        $tag = $this->tagRepository->findOneBy(['name' => $name]);
        if (is_null($tag)) {
            $tag = new Tag($name);
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
     * @return Tag[]
     */
    public function findOrCreateByNames(array $names): array
    {
        $nameMap = [];
        foreach($names as $name) {
            $nameMap[$name] = true;
        }

        $tags = $this->tagRepository->findByKeys('name', $names);
        foreach($tags as $existingTag) {
            if (array_key_exists($existingTag->getName(), $nameMap)) {
                unset($nameMap[$existingTag->getName()]);
            }
        }

        foreach($nameMap as $name => $value) {
            $newTag = new Tag($name);
            $tags[] = $newTag;

            $this->entityManager->persist($newTag);
        }

        return $tags;
    }
}

