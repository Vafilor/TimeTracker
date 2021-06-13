<?php

namespace App\Command;

use App\Entity\TagLink;
use App\Entity\Task;
use App\Entity\TimeEntry;
use App\Entity\Timestamp;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use App\Repository\TimestampRepository;
use App\Repository\UserRepository;
use App\Transfer\TransferTag;
use App\Transfer\TransferTask;
use App\Transfer\TransferTimeEntry;
use App\Transfer\TransferTimestamp;
use App\Transfer\TransferUser;
use App\Util\Collections;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ImportDataCommand extends Command
{
    protected static $defaultName = 'app:data:import';
    protected static string $defaultDescription = 'Import the data in to the database from the files generated by the export command';

    private Serializer $serializer;
    private TagRepository $tagRepository;
    private UserRepository $userRepository;
    private TimestampRepository $timestampRepository;
    private TaskRepository $taskRepository;
    private TimeEntryRepository $timeEntryRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        string $name = null,
        SerializerInterface $serializer,
        TagRepository $tagRepository,
        UserRepository $userRepository,
        TimestampRepository $timestampRepository,
        TaskRepository $taskRepository,
        TimeEntryRepository $timeEntryRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($name);
        $this->serializer = $serializer;
        $this->tagRepository = $tagRepository;
        $this->userRepository = $userRepository;
        $this->timestampRepository = $timestampRepository;
        $this->taskRepository = $taskRepository;
        $this->timeEntryRepository = $timeEntryRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to the input files.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $inputPath = $input->getArgument('path');

        if (is_null($inputPath)) {
            $inputPath = __DIR__ . DIRECTORY_SEPARATOR . 'export';
        }

        while (strlen($inputPath) > 0 && str_ends_with($inputPath, DIRECTORY_SEPARATOR)) {
            $inputPath = substr($inputPath, 0, count($inputPath) - 1);
        }

        if (strlen($inputPath) === 0) {
            $io->error('path is not valid');
            return Command::FAILURE;
        }

        if (is_file($inputPath)) {
            $io->error("'$inputPath' refers to a file. Needs to be a directory.");
            return Command::FAILURE;
        }

        if (!is_dir($inputPath)) {
            $io->error("'$inputPath' does not point to a directory.");
            return Command::FAILURE;
        }

        $io->writeln("Using '$inputPath' to load data...");
        $io->writeln('Reading file import order...');

        $fileImportOrder = $this->getFileImportOrder($inputPath);

        foreach ($fileImportOrder as $filePath) {
            $file = new File($filePath);
            $fileName = $file->getFilename();
            $content = $file->getContent();

            $io->writeln("Processing file '$filePath'");

            if (str_starts_with($fileName, 'users')) {
                $data = $this->serializer->deserialize($content, TransferUser::class . '[]', 'json');
                $this->importUsers($io, $data);
            } elseif (str_starts_with($fileName, 'tags')) {
                $data = $this->serializer->deserialize($content, TransferTag::class . '[]', 'json');
                $this->importTags($io, $data);
            } elseif (str_starts_with($fileName, 'timestamps')) {
                $data = $this->serializer->deserialize($content, TransferTimestamp::class . '[]', 'json');
                $this->importTimestamps($io, $data);
            } elseif (str_starts_with($fileName, 'tasks')) {
                $data = $this->serializer->deserialize($content, TransferTask::class . '[]', 'json');
                $this->importTasks($io, $data);
            } elseif (str_starts_with($fileName, 'time_entries')) {
                $data = $this->serializer->deserialize($content, TransferTimeEntry::class . '[]', 'json');
                $this->importTimeEntries($io, $data);
            } else {
                $io->error("Unsupported import file '${filePath}'");
                return Command::FAILURE;
            }
        }

        $io->success("Data successfully imported");

        return Command::SUCCESS;
    }

    /**
     * Goes through a collection of items and yields all of its tags, one by one.
     *
     * @param iterable $sources
     * @return \Generator
     */
    private function pluckTagLinks(iterable $sources)
    {
        foreach ($sources as $source) {
            yield from $source->tags;
        }
    }

    /**
     * Goes through a collection of items and yields all of its tasks (if available) one by one.
     *
     * @param iterable $sources
     * @return \Generator
     */
    private function pluckTasks(iterable $sources)
    {
        foreach ($sources as $source) {
            if ($source->task) {
                echo $source->task->id;
                yield $source->task;
            }
        }
    }

    private function getFileImportOrder(string $directoryPath): array
    {
        $path = $directoryPath . DIRECTORY_SEPARATOR . 'order.json';

        $fileContents = file_get_contents($path);
        return json_decode($fileContents, true);
    }

    /**
     * @param SymfonyStyle $io
     * @param TransferUser[] $transferUsers
     */
    private function importUsers(SymfonyStyle $io, array $transferUsers)
    {
        $usernames = Collections::pluckNoDuplicates($transferUsers, 'username');
        $users = $this->userRepository->findByKeys('username', $usernames);
        $usernameToUsers = Collections::mapByKeyUnique($users, 'username');

        foreach ($transferUsers as $transferUser) {
            if (array_key_exists($transferUser->username, $usernameToUsers)) {
                continue;
            }

            $user = $transferUser->toEntity();

            $io->writeln("Importing User with username '{$transferUser->username}'");

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
    }

    /**
     * @param SymfonyStyle $io
     * @param TransferTag[] $transferTags
     */
    private function importTags(SymfonyStyle $io, array $transferTags)
    {
        $usernames = Collections::pluckNoDuplicates($transferTags, 'createdBy');
        $users = $this->userRepository->findByKeys('username', $usernames);
        $usernameToUsers = Collections::mapByKeyUnique($users, 'username');

        $tagNames = Collections::pluckNoDuplicates($transferTags, 'name');
        $exitingTags = $this->tagRepository->findByKeys('name', $tagNames);

        $tagKeysToTags = [];
        foreach ($exitingTags as $exitingTag) {
            $createdByName = $exitingTag->getCreatedBy()->getUsername();
            $tagName = $exitingTag->getName();

            $key = "{$createdByName}_{$tagName}";
            $tagKeysToTags[$key] = $exitingTag;
        }

        foreach ($transferTags as $transferTag) {
            $username = $transferTag->createdBy;
            if (!array_key_exists($username, $usernameToUsers)) {
                throw new InvalidArgumentException("Username '$username' does not exist. But Tag with id '{$transferTag->id}' references it");
            }

            /** @var User $createdBy */
            $createdBy = $usernameToUsers[$username];

            $tagName = $transferTag->name;
            $createdByName = $createdBy->getUsername();
            $key = "{$createdByName}_{$tagName}";
            if (array_key_exists($key, $tagKeysToTags)) {
                continue;
            }

            $tag = $transferTag->toEntity($createdBy);
            $this->entityManager->persist($tag);
        }

        $this->entityManager->flush();
    }

    /**
     * Returns TransferTimestamps that do not already exist in the database, identified by id.
     *
     * @param TransferTimestamp[] $transferTimestamps
     * @return TransferTimestamp[]
     */
    private function filterOutExistingTimestamps($transferTimestamps): array
    {
        $ids = Collections::pluck($transferTimestamps, 'id');
        $existingTimestamps = $this->timestampRepository->findByKeys('id', $ids);
        $idToTimestamp = Collections::mapByKeyUnique($existingTimestamps, 'idString');

        $result = [];
        foreach ($transferTimestamps as $transferTimestamp) {
            if (array_key_exists($transferTimestamp->id, $idToTimestamp)) {
                continue;
            }

            $result[$transferTimestamp->id] = $transferTimestamp;
        }

        return $result;
    }

    /**
     * @param TransferTimestamp[] $transferTimestamps
     * @return Timestamp[]
     */
    private function transferTimestampsToEntities(array $transferTimestamps): array
    {
        $usernames = Collections::pluckNoDuplicates($transferTimestamps, 'createdBy');
        $users = $this->userRepository->findByKeys('username', $usernames);
        $usernameToUsers = Collections::mapByKeyUnique($users, 'username');

        $timestamps = [];

        foreach ($transferTimestamps as $transferTimestamp) {
            $username = $transferTimestamp->createdBy;
            if (!array_key_exists($username, $usernameToUsers)) {
                throw new InvalidArgumentException("Username '$username' does not exist. But Timestamp with id '{$transferTimestamp->id}' references it");
            }

            /** @var User $createdBy */
            $createdBy = $usernameToUsers[$username];

            $timestamps[$transferTimestamp->id] =  $transferTimestamp->toEntity($createdBy);
        }

        return $timestamps;
    }

    /**
     * @param SymfonyStyle $io
     * @param TransferTimestamp[] $transferTimestamps
     */
    private function importTimestamps(SymfonyStyle $io, $transferTimestamps)
    {
        $transferTimestamps = $this->filterOutExistingTimestamps($transferTimestamps);
        $timestamps = $this->transferTimestampsToEntities($transferTimestamps);

        $tagIds = Collections::pluckNoDuplicates($this->pluckTagLinks($transferTimestamps), 'id');
        $tags = $this->tagRepository->findByKeys('id', $tagIds);
        $tagIdToTag = Collections::mapByKeyUnique($tags, 'idString');

        foreach ($transferTimestamps as $id => $transferTimestamp) {
            $io->writeln("Importing Timestamp with id '$id'");

            $timestamp = $timestamps[$id];

            $this->entityManager->persist($timestamp);

            foreach ($transferTimestamp->tags as $transferTagLink) {
                $tag = $tagIdToTag[$transferTagLink->id];
                $tagLink = new TagLink($timestamp, $tag);
                $this->entityManager->persist($tagLink);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Returns TransferTasks that do not already exist in the database, identified by id.
     *
     * @param TransferTask[] $transferTasks
     * @return TransferTask[]
     */
    private function filterOutExistingTasks(array $transferTasks): array
    {
        $ids = Collections::pluck($transferTasks, 'id');
        $existingTasks = $this->taskRepository->findByKeys('id', $ids);
        $idToTask = Collections::mapByKeyUnique($existingTasks, 'idString');

        $result = [];
        foreach ($transferTasks as $transferTask) {
            if (array_key_exists($transferTask->id, $idToTask)) {
                continue;
            }

            $result[$transferTask->id] = $transferTask;
        }

        return $result;
    }

    /**
     * @param TransferTask[] $transferTasks
     * @return Task[]
     */
    private function transferTasksToEntities(array $transferTasks): array
    {
        $usernames = Collections::pluckNoDuplicates($transferTasks, 'createdBy');
        $users = $this->userRepository->findByKeys('username', $usernames);
        $usernameToUsers = Collections::mapByKeyUnique($users, 'username');

        $tasks = [];

        foreach ($transferTasks as $transferTask) {
            $username = $transferTask->createdBy;
            if (!array_key_exists($username, $usernameToUsers)) {
                throw new InvalidArgumentException("Username '$username' does not exist. But Task with id '{$transferTask->id}' references it");
            }

            /** @var User $createdBy */
            $createdBy = $usernameToUsers[$username];

            $tasks[$transferTask->id] = $transferTask->toEntity($createdBy);
        }

        return $tasks;
    }

    private function importTasks(SymfonyStyle $io, $transferTasks)
    {
        $transferTasks = $this->filterOutExistingTasks($transferTasks);
        $tasks = $this->transferTasksToEntities($transferTasks);

        foreach ($tasks as $id => $task) {
            $io->writeln("Importing Task with id '$id'");
            $this->entityManager->persist($task);
        }

        $this->entityManager->flush();
    }

    /**
     * Returns TransferTimeEntries that do not already exist in the database, identified by id.
     *
     * @param TransferTimeEntry[] $transferTimeEntries
     * @return TransferTimeEntry[]
     */
    private function filterOutExistingTimeEntries(array $transferTimeEntries): array
    {
        $ids = Collections::pluck($transferTimeEntries, 'id');
        $existingTimeEntries = $this->timeEntryRepository->findByKeys('id', $ids);
        $idToTimeEntry = Collections::mapByKeyUnique($existingTimeEntries, 'idString');

        $result = [];
        foreach ($transferTimeEntries as $transferTimeEntry) {
            if (array_key_exists($transferTimeEntry->id, $idToTimeEntry)) {
                continue;
            }

            $result[$transferTimeEntry->id] = $transferTimeEntry;
        }

        return $result;
    }

    /**
     * @param TransferTimeEntry[] $transferTimeEntries
     * @return TimeEntry[]
     */
    private function transferTimeEntriesToEntities(array $transferTimeEntries): array
    {
        $usernames = Collections::pluckNoDuplicates($transferTimeEntries, 'createdBy');
        $users = $this->userRepository->findByKeys('username', $usernames);
        $usernameToUsers = Collections::mapByKeyUnique($users, 'username');

        $timeEntries = [];
        foreach ($transferTimeEntries as $transferTimeEntry) {
            $username = $transferTimeEntry->createdBy;
            if (!array_key_exists($username, $usernameToUsers)) {
                throw new InvalidArgumentException("Username '$username' does not exist. But TimeEntry with id '{$transferTimeEntry->id}' references it");
            }

            /** @var User $createdBy */
            $createdBy = $usernameToUsers[$username];

            $timeEntries[$transferTimeEntry->id] = $transferTimeEntry->toEntity($createdBy);
        }

        return $timeEntries;
    }

    /**
     * @param SymfonyStyle $io
     * @param TransferTimeEntry[] $transferTimeEntries
     */
    private function importTimeEntries(SymfonyStyle $io, $transferTimeEntries)
    {
        $transferTimeEntries = $this->filterOutExistingTimeEntries($transferTimeEntries);
        $timeEntries = $this->transferTimeEntriesToEntities($transferTimeEntries);

        $tagIds = Collections::pluckNoDuplicates($this->pluckTagLinks($transferTimeEntries), 'id');
        $tags = $this->tagRepository->findByKeys('id', $tagIds);
        $tagIdToTag = Collections::mapByKeyUnique($tags, 'idString');

        $taskIds = Collections::pluckNoDuplicates($this->pluckTasks($transferTimeEntries), 'id');
        $tasks = $this->taskRepository->findByKeys('id', $taskIds);
        $taskIdToTask = Collections::mapByKeyUnique($tasks, 'idString');

        foreach ($transferTimeEntries as $id => $transferTimeEntry) {
            $timeEntry = $timeEntries[$id];

            if ($transferTimeEntry->task) {
                $task = $taskIdToTask[$transferTimeEntry->task->id];
                $timeEntry->setTask($task);
            }

            $io->writeln("Importing TimeEntry with id '$id'");
            $this->entityManager->persist($timeEntry);

            foreach ($transferTimeEntry->tags as $transferTagLink) {
                $tag = $tagIdToTag[$transferTagLink->id];
                $tagLink = new TagLink($timeEntry, $tag);
                $this->entityManager->persist($tagLink);
            }
        }

        $this->entityManager->flush();
    }
}
