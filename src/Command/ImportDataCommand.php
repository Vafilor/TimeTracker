<?php

namespace App\Command;

use App\Entity\TagLink;
use App\Entity\Task;
use App\Repository\NoteRepository;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use App\Repository\TimestampRepository;
use App\Repository\UserRepository;
use App\Traits\FindByKeysInterface;
use App\Transfer\RepositoryKeyCache;
use App\Transfer\TransferNote;
use App\Transfer\TransferStatistic;
use App\Transfer\TransferStatisticValue;
use App\Transfer\TransferTag;
use App\Transfer\TransferTask;
use App\Transfer\TransferTimeEntry;
use App\Transfer\TransferTimestamp;
use App\Transfer\TransferUser;
use App\Util\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:data:import',
    description: 'Import the data in to the database from the files generated by the export command',
)]
class ImportDataCommand extends Command
{
    private Serializer $serializer;

    private TagRepository $tagRepository;

    private TimestampRepository $timestampRepository;

    private TaskRepository $taskRepository;

    private TimeEntryRepository $timeEntryRepository;

    private StatisticRepository $statisticRepository;

    private EntityManagerInterface $entityManager;

    private RepositoryKeyCache $userLoader;

    private RepositoryKeyCache $tagLoader;

    private RepositoryKeyCache $taskLoader;

    private RepositoryKeyCache $statisticLoader;

    private RepositoryKeyCache $timeEntryLoader;

    private RepositoryKeyCache $timestampLoader;

    private StatisticValueRepository $statisticValueRepository;

    private NoteRepository $noteRepository;

    public function __construct(
        SerializerInterface $serializer,
        TagRepository $tagRepository,
        TimestampRepository $timestampRepository,
        TaskRepository $taskRepository,
        TimeEntryRepository $timeEntryRepository,
        StatisticRepository $statisticRepository,
        StatisticValueRepository $statisticValueRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        NoteRepository $noteRepository
    ) {
        $this->serializer = $serializer;
        $this->tagRepository = $tagRepository;
        $this->timestampRepository = $timestampRepository;
        $this->taskRepository = $taskRepository;
        $this->timeEntryRepository = $timeEntryRepository;
        $this->statisticRepository = $statisticRepository;
        $this->statisticValueRepository = $statisticValueRepository;
        $this->entityManager = $entityManager;

        $this->userLoader = new RepositoryKeyCache($userRepository);
        $this->tagLoader = new RepositoryKeyCache($tagRepository);
        $this->taskLoader = new RepositoryKeyCache($taskRepository);
        $this->statisticLoader = new RepositoryKeyCache($statisticRepository);
        $this->timeEntryLoader = new RepositoryKeyCache($timeEntryRepository);
        $this->timestampLoader = new RepositoryKeyCache($timestampRepository);
        $this->noteRepository = $noteRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to the input files.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $inputPath = $input->getArgument('path');

        if (is_null($inputPath)) {
            $inputPath = __DIR__.DIRECTORY_SEPARATOR.'export';
        }

        while (strlen($inputPath) > 0 && str_ends_with($inputPath, DIRECTORY_SEPARATOR)) {
            $inputPath = substr($inputPath, 0, strlen($inputPath) - 1);
        }

        if (0 === strlen($inputPath)) {
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
            $absoluteFilePath = $inputPath.DIRECTORY_SEPARATOR.$filePath;

            $file = new File($absoluteFilePath);
            $fileName = $file->getFilename();
            $content = $file->getContent();

            $io->writeln("Processing file '$filePath'");

            if (str_starts_with($fileName, 'users')) {
                $data = $this->serializer->deserialize($content, TransferUser::class.'[]', 'json');
                $this->importUsers($io, $data);
            } elseif (str_starts_with($fileName, 'tags')) {
                $data = $this->serializer->deserialize($content, TransferTag::class.'[]', 'json');
                $this->importTags($io, $data);
            } elseif (str_starts_with($fileName, 'timestamps')) {
                $data = $this->serializer->deserialize($content, TransferTimestamp::class.'[]', 'json');
                $this->importTimestamps($io, $data);
            } elseif (str_starts_with($fileName, 'tasks')) {
                $data = $this->serializer->deserialize($content, TransferTask::class.'[]', 'json');
                $this->importTasks($io, $data);
            } elseif (str_starts_with($fileName, 'time_entries')) {
                $data = $this->serializer->deserialize($content, TransferTimeEntry::class.'[]', 'json');
                $this->importTimeEntries($io, $data);
            } elseif (str_starts_with($fileName, 'statistics')) {
                $data = $this->serializer->deserialize($content, TransferStatistic::class.'[]', 'json');
                $this->importStatistics($io, $data);
            } elseif (str_starts_with($fileName, 'statistic_values')) {
                $data = $this->serializer->deserialize($content, TransferStatisticValue::class.'[]', 'json');
                $this->importStatisticValues($io, $data);
            } elseif (str_starts_with($fileName, 'notes')) {
                $data = $this->serializer->deserialize($content, TransferNote::class.'[]', 'json');
                $this->importNotes($io, $data);
            } else {
                $io->error("Unsupported import file '{$filePath}'");

                return Command::FAILURE;
            }
        }

        $io->success('Data successfully imported');

        return Command::SUCCESS;
    }

    /**
     * Goes through a collection of items and yields all of its tags, one by one.
     *
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
     * @return \Generator
     */
    private function pluckTasks(iterable $sources)
    {
        foreach ($sources as $source) {
            if ($source->task) {
                yield $source->task;
            }
        }
    }

    /**
     * Takes an iterable of transfer objects that have a method toEntity(User).
     * Returns an array where the key is the id, and the entry is the Entity of the transfer object,
     * constructed by loading the User from the database.
     *
     * TODO can psalm allow us to specify this? I guess it's something like
     * implements interface<T>.... and then the input object is anything that implements interface, with any T.
     * and return is array<string, T>
     */
    private function makeEntityMap(iterable $transfers): array
    {
        $usernames = Collections::pluckNoDuplicates($transfers, 'assignedTo');
        $this->userLoader->loadByKey('username', $usernames);

        $entities = [];
        foreach ($transfers as $transfer) {
            $user = $this->userLoader->findOneByKeyOrException('username', $transfer->assignedTo);
            $entity = $transfer->toEntity($user);
            $entities[$transfer->id] = $entity;
        }

        return $entities;
    }

    /**
     * Removes all items that are already in the database, identified by the id.
     */
    private function filterOutById(array $transferItems, FindByKeysInterface $repository): array
    {
        $ids = Collections::pluck($transferItems, 'id');
        $existingItems = $repository->findByKeys('id', $ids);
        $idToItem = Collections::mapByKeyUnique($existingItems, 'idString');

        $result = [];
        foreach ($transferItems as $transferItem) {
            if (array_key_exists($transferItem->id, $idToItem)) {
                continue;
            }

            $result[$transferItem->id] = $transferItem;
        }

        return $result;
    }

    private function getFileImportOrder(string $directoryPath): array
    {
        $path = $directoryPath.DIRECTORY_SEPARATOR.'order.json';

        $fileContents = file_get_contents($path);

        return json_decode($fileContents, true);
    }

    /**
     * @param TransferUser[] $transferUsers
     */
    private function importUsers(SymfonyStyle $io, array $transferUsers)
    {
        $usernames = Collections::pluckNoDuplicates($transferUsers, 'username');
        $this->userLoader->loadByKey('username', $usernames);

        foreach ($transferUsers as $transferUser) {
            if ($this->userLoader->hasKey('username', $transferUser->username)) {
                continue;
            }

            $user = $transferUser->toEntity();

            $io->writeln("Importing User with username '{$transferUser->username}'");

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
    }

    /**
     * @param TransferTag[] $transferTags
     */
    private function importTags(SymfonyStyle $io, array $transferTags)
    {
        $usernames = Collections::pluckNoDuplicates($transferTags, 'assignedTo');
        $this->userLoader->loadByKey('username', $usernames);

        $tagNames = Collections::pluckNoDuplicates($transferTags, 'name');
        $exitingTags = $this->tagRepository->findByKeys('name', $tagNames);

        $tagKeysToTags = [];
        foreach ($exitingTags as $exitingTag) {
            $assignedToName = $exitingTag->getAssignedTo()->getUsername();
            $tagName = $exitingTag->getName();

            $key = "{$assignedToName}_{$tagName}";
            $tagKeysToTags[$key] = $exitingTag;
        }

        foreach ($transferTags as $transferTag) {
            $assignedTo = $this->userLoader->findOneByKeyOrException('username', $transferTag->assignedTo);

            $tagName = $transferTag->name;
            $assignedToName = $assignedTo->getUsername();
            $key = "{$assignedToName}_{$tagName}";
            if (array_key_exists($key, $tagKeysToTags)) {
                continue;
            }

            $tag = $transferTag->toEntity($assignedTo);
            $this->entityManager->persist($tag);
        }

        $this->entityManager->flush();
    }

    /**
     * @param TransferTimestamp[] $transferTimestamps
     */
    private function importTimestamps(SymfonyStyle $io, array $transferTimestamps)
    {
        /** @var TransferTimestamp[] $transferTimestamps */
        $transferTimestamps = $this->filterOutById($transferTimestamps, $this->timestampRepository);

        $timestamps = $this->makeEntityMap($transferTimestamps);

        $tagIds = Collections::pluckNoDuplicates($this->pluckTagLinks($transferTimestamps), 'id');

        $this->tagLoader->loadByIds($tagIds);

        foreach ($transferTimestamps as $id => $transferTimestamp) {
            $io->writeln("Importing Timestamp with id '$id'");

            $timestamp = $timestamps[$id];

            $this->entityManager->persist($timestamp);

            foreach ($transferTimestamp->tags as $transferTagLink) {
                $tag = $this->tagLoader->findByIdOrException($transferTagLink->id);
                $tagLink = new TagLink($timestamp, $tag);
                $this->entityManager->persist($tagLink);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param TransferTask[] $transferTasks
     */
    private function importTasks(SymfonyStyle $io, array $transferTasks)
    {
        /** @var TransferTask[] $transferTasks */
        $transferTasks = $this->filterOutById($transferTasks, $this->taskRepository);

        $tasks = $this->makeEntityMap($transferTasks);

        $tagIds = Collections::pluckNoDuplicates($this->pluckTagLinks($transferTasks), 'id');
        $this->tagLoader->loadByIds($tagIds);

        $parentTaskIds = Collections::pluckNoDuplicates($transferTasks, 'parentId');
        $this->taskLoader->loadByIds($parentTaskIds);

        foreach ($transferTasks as $id => $transferTask) {
            /** @var Task $task */
            $task = $tasks[$id];

            if ($transferTask->parentId) {
                $parentTask = $this->taskLoader->findByIdOrException($transferTask->parentId);
                $task->setParent($parentTask);
            }

            $this->entityManager->persist($task);

            $io->writeln("Importing Task with id '$id'");

            foreach ($transferTask->tags as $transferTagLink) {
                $tag = $this->tagLoader->findByIdOrException($transferTagLink->id);
                $tagLink = new TagLink($task, $tag);
                $this->entityManager->persist($tagLink);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param TransferTimeEntry[] $transferTimeEntries
     */
    private function importTimeEntries(SymfonyStyle $io, array $transferTimeEntries)
    {
        /** @var TransferTimeEntry[] $transferTimeEntries */
        $transferTimeEntries = $this->filterOutById($transferTimeEntries, $this->timeEntryRepository);

        $timeEntries = $this->makeEntityMap($transferTimeEntries);

        $tagIds = Collections::pluckNoDuplicates($this->pluckTagLinks($transferTimeEntries), 'id');
        $this->tagLoader->loadByIds($tagIds);

        $taskIds = Collections::pluckNoDuplicates($this->pluckTasks($transferTimeEntries), 'id');
        $this->taskLoader->loadByIds($taskIds);

        foreach ($transferTimeEntries as $id => $transferTimeEntry) {
            $timeEntry = $timeEntries[$id];

            if ($transferTimeEntry->task) {
                $task = $this->taskLoader->findByIdOrException($transferTimeEntry->task->id);
                $timeEntry->setTask($task);
            }

            $io->writeln("Importing TimeEntry with id '$id'");
            $this->entityManager->persist($timeEntry);

            foreach ($transferTimeEntry->tags as $transferTagLink) {
                $tag = $this->tagLoader->findByIdOrException($transferTagLink->id);
                $tagLink = new TagLink($timeEntry, $tag);
                $this->entityManager->persist($tagLink);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param TransferStatistic[] $transferStatistics
     */
    private function importStatistics(SymfonyStyle $io, array $transferStatistics)
    {
        /** @var TransferStatistic[] $transferStatistics */
        $transferStatistics = $this->filterOutById($transferStatistics, $this->statisticRepository);

        $statistics = $this->makeEntityMap($transferStatistics);

        $tagIds = Collections::pluckNoDuplicates($this->pluckTagLinks($transferStatistics), 'id');
        $this->tagLoader->loadByIds($tagIds);

        foreach ($transferStatistics as $id => $transferStatistic) {
            $statistic = $statistics[$id];

            $io->writeln("Importing Statistic with id '$id'");
            $this->entityManager->persist($statistic);

            foreach ($transferStatistic->tags as $transferTagLink) {
                $tag = $this->tagLoader->findByIdOrException($transferTagLink->id);
                $tagLink = new TagLink($statistic, $tag);
                $this->entityManager->persist($tagLink);
            }
        }

        $this->entityManager->flush();
    }

    private function importStatisticValues(SymfonyStyle $io, array $transferStatisticValues)
    {
        /** @var TransferStatisticValue[] $transferStatisticValues */
        $transferStatisticValues = $this->filterOutById($transferStatisticValues, $this->statisticValueRepository);

        $statisticIds = Collections::pluck($transferStatisticValues, 'statisticId');

        $this->statisticLoader->loadByIds($statisticIds);

        $timeEntryIds = [];
        $timestampIds = [];
        foreach ($transferStatisticValues as $transferStatisticValue) {
            if ($transferStatisticValue->timeEntryId) {
                $timeEntryIds[] = $transferStatisticValue->timeEntryId;
            } elseif ($transferStatisticValue->timestampId) {
                $timestampIds[] = $transferStatisticValue->timestampId;
            }
        }

        $this->timeEntryLoader->loadByIds($timeEntryIds);
        $this->timestampLoader->loadByIds($timestampIds);

        foreach ($transferStatisticValues as $transferStatisticValue) {
            $statistic = $this->statisticLoader->findByIdOrException($transferStatisticValue->statisticId);
            $statisticValue = $transferStatisticValue->toEntity($statistic);

            if ($transferStatisticValue->timeEntryId) {
                $timeEntry = $this->timeEntryLoader->findByIdOrException($transferStatisticValue->timeEntryId);
                $statisticValue->setTimeEntry($timeEntry);
            } elseif ($transferStatisticValue->timestampId) {
                $timestamp = $this->timestampLoader->findByIdOrException($transferStatisticValue->timestampId);
                $statisticValue->setTimestamp($timestamp);
            }

            $io->writeln("Importing Statistic Value with id '{$transferStatisticValue->id}'");
            $this->entityManager->persist($statisticValue);
        }

        $this->entityManager->flush();
    }

    /**
     * @param TransferNote[] $transferNotes
     */
    private function importNotes(SymfonyStyle $io, array $transferNotes)
    {
        /** @var TransferNote[] $transferNotes */
        $transferNotes = $this->filterOutById($transferNotes, $this->noteRepository);

        $notes = $this->makeEntityMap($transferNotes);

        $tagIds = Collections::pluckNoDuplicates($this->pluckTagLinks($transferNotes), 'id');
        $this->tagLoader->loadByIds($tagIds);

        foreach ($transferNotes as $id => $transferNote) {
            $note = $notes[$id];

            $io->writeln("Importing Note with id '$id'");
            $this->entityManager->persist($note);

            foreach ($transferNote->tags as $transferTagLink) {
                $tag = $this->tagLoader->findByIdOrException($transferTagLink->id);
                $tagLink = new TagLink($note, $tag);
                $this->entityManager->persist($tagLink);
            }
        }

        $this->entityManager->flush();
    }
}
