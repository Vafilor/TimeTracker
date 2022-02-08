<?php

namespace App\Command;

use App\Repository\NoteRepository;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use App\Repository\TimestampRepository;
use App\Repository\UserRepository;
use App\Transfer\TransferNote;
use App\Transfer\TransferStatistic;
use App\Transfer\TransferStatisticValue;
use App\Transfer\TransferTag;
use App\Transfer\TransferTask;
use App\Transfer\TransferTimeEntry;
use App\Transfer\TransferTimestamp;
use App\Transfer\TransferUser;
use App\Util\Collections;
use Doctrine\ORM\QueryBuilder;
use Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:data:export',
    description: 'Export the data in the database to several files',
)]
class ExportDataCommand extends Command
{
    private Serializer $serializer;
    private TagRepository $tagRepository;
    private UserRepository $userRepository;
    private TimestampRepository $timestampRepository;
    private TaskRepository $taskRepository;
    private TimeEntryRepository $timeEntryRepository;
    private StatisticRepository $statisticRepository;
    private StatisticValueRepository $statisticValueRepository;
    private NoteRepository $noteRepository;

    public function __construct(
        SerializerInterface $serializer,
        TagRepository $tagRepository,
        UserRepository $userRepository,
        TimestampRepository $timestampRepository,
        TaskRepository $taskRepository,
        TimeEntryRepository $timeEntryRepository,
        StatisticRepository $statisticRepository,
        StatisticValueRepository $statisticValueRepository,
        NoteRepository $noteRepository
    ) {
        $this->serializer = $serializer;
        $this->tagRepository = $tagRepository;
        $this->userRepository = $userRepository;
        $this->timestampRepository = $timestampRepository;
        $this->taskRepository = $taskRepository;
        $this->timeEntryRepository = $timeEntryRepository;
        $this->statisticRepository = $statisticRepository;
        $this->statisticValueRepository = $statisticValueRepository;
        $this->noteRepository = $noteRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to output the files to. Files will be saved in a folder')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $outputPath = $input->getArgument('path');

        if (is_null($outputPath)) {
            $outputPath = __DIR__ . DIRECTORY_SEPARATOR . 'export';
        }

        while (strlen($outputPath) > 0 && str_ends_with($outputPath, DIRECTORY_SEPARATOR)) {
            $outputPath = substr($outputPath, 0, count($outputPath) - 1);
        }

        if (strlen($outputPath) === 0) {
            $io->error('path is not valid');
            return Command::FAILURE;
        }

        if (is_file($outputPath)) {
            $io->error("'$outputPath' refers to a file. Needs to be a directory.");
            return Command::FAILURE;
        }

        if (!is_dir($outputPath)) {
            if (!mkdir($outputPath, 0777, true)) {
                $io->error('Unable to create directory to output path');
                return Command::FAILURE;
            }
        }

        $fileExportOrder = [];

        $io->writeln("Exporting data to $outputPath");

        $io->writeln("Exporting Users...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportUsers($outputPath));

        $io->writeln("Exporting Tags...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportTags($outputPath));

        $io->writeln("Exporting Timestamps...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportTimestamps($outputPath));

        $io->writeln("Exporting Tasks...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportTasks($outputPath));

        $io->writeln("Exporting Time Entries...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportTimeEntries($outputPath));

        $io->writeln("Exporting Statistics...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportStatistics($outputPath));

        $io->writeln("Exporting Statistic Values...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportStatisticValues($outputPath));

        $io->writeln("Exporting Notes...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportNotes($outputPath));

        $fileExportOrderPath = $outputPath . DIRECTORY_SEPARATOR . 'order.json';

        // Make sure the paths in the fileExportOrder are relative
        $prefixLength = strlen($outputPath . DIRECTORY_SEPARATOR);
        $fileExportOrder = array_map(
            fn (string $path) => substr($path, $prefixLength),
            $fileExportOrder
        );

        file_put_contents($fileExportOrderPath, $this->serializer->serialize($fileExportOrder, 'json'));

        $io->success("Data successfully exported to $outputPath");

        return Command::SUCCESS;
    }

    /**
     * Paginate through through a query using a Generator.
     * You can use it in a foreach loop to paginate through the data, or use it as a Generator.
     *
     * Each result has key => value where key is the chunk index, starting from 1, and value is the results of the query.
     * No empty results are output.
     *
     * @param QueryBuilder $queryBuilder
     * @param int $chunkSize
     * @return Generator
     */
    public static function paginate(QueryBuilder $queryBuilder, int $chunkSize = 500): Generator
    {
        $chunk = 1;

        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults($chunkSize);

        $results = $queryBuilder->getQuery()->getResult();
        while (count($results) !== 0) {
            yield $chunk => $results;

            $queryBuilder = $queryBuilder->setFirstResult($chunk * $chunkSize);
            $results = $queryBuilder->getQuery()->getResult();
            $chunk++;
        }
    }

    private function writeObjectsToFile(string $path, mixed $items): false|int
    {
        $content = $this->serializer->serialize(
            $items,
            'json',
            [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]
        );

        return file_put_contents($path, $content);
    }

    private function exportChunk(string $path, QueryBuilder $queryBuilder, callable $transformer): array
    {
        $newFilePaths = [];

        foreach (self::paginate($queryBuilder) as $chunk => $results) {
            $filePath = "{$path}_{$chunk}.json";
            $transferItems = $transformer($results);

            $this->writeObjectsToFile($filePath, $transferItems);

            $newFilePaths[] = $filePath;
        }

        return $newFilePaths;
    }

    private function exportTags(string $path): array
    {
        $queryBuilder = $this->tagRepository->createDefaultQueryBuilder()
                                            ->orderBy('tag.createdAt')
        ;

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'tags';

        return $this->exportChunk($filePrefix, $queryBuilder, fn ($items) => TransferTag::fromEntities($items));
    }

    private function exportUsers(string $path): array
    {
        $queryBuilder = $this->userRepository->createDefaultQueryBuilder()
                                             ->orderBy('user.createdAt')
        ;

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'users';

        return $this->exportChunk($filePrefix, $queryBuilder, fn ($items) => TransferUser::fromEntities($items));
    }

    private function exportTimestamps(string $path): array
    {
        $queryBuilder = $this->timestampRepository->createDefaultQueryBuilder()
                                                  ->orderBy('timestamp.createdAt')
        ;

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'timestamps';

        return $this->exportChunk($filePrefix, $queryBuilder, fn ($items) => TransferTimestamp::fromEntities($items));
    }

    /**
     * exportTasks will output the tasks to json files.
     *
     * Each file is safe to import one after the other in order.
     *
     * The order of the tasks is important because we have sub-tasks, and they need to have a valid parent
     * to reference.
     *
     * Each file, in order, makes sure required parents are in the previous file.
     *
     * This does not mean the first few files are all parent-less tasks though.
     *
     * @param string $path
     * @return array
     */
    private function exportTasks(string $path): array
    {
        // This function is a little tricky. The general idea is a depth-first tree traversal.
        // Start with tasks that have no parents. We want to make sure we don't use too much memory,
        // so immediately find all tasks that are children of these - but paginate through them.
        // On the first pagination, immediately find all tasks that are children of those - also paginated.
        // Repeat until there are none, then being unwinding back up.
        //
        // This is accomplished by keeping track of each paginated query as a Generator

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'tasks';
        $newFilePaths = [];

        // Get tasks with no parents, sort by createdAt so there is a consistent ordering.
        $queryBuilder = $this->taskRepository->createDefaultQueryBuilder(true)
                                             ->andWhere('task.parent IS NULL')
                                             ->orderBy('task.createdAt')
        ;


        // Keep track of the chunk so we know what to number the files
        $chunk = 1;

        // Start with the no parents as a generator
        $generators = [self::paginate($queryBuilder)];
        while (count($generators) !== 0) {
            // Get the first generator, take it off the Queue as it may be finished.
            $generator = array_shift($generators);

            // It is finished, so don't add it back on
            if (!$generator->valid()) {
                continue;
            }

            $filePath = "{$filePrefix}_{$chunk}.json";
            $newFilePaths[] = $filePath;
            $tasks = $generator->current();
            $generator->next();
            $transferItems = TransferTask::fromEntities($tasks);
            $this->writeObjectsToFile($filePath, $transferItems);

            // We're storing the parent ids now. To preserve memory,
            // get the children and make that generator the next one we handle
            $parentIds = Collections::pluckNoDuplicates($tasks, 'idString');
            if (count($parentIds) !== 0) {
                $childTaskQueryBuilder = $this->taskRepository->findByKeysQuery('parent', $parentIds);
                $childTaskGenerator = self::paginate($childTaskQueryBuilder);
                array_unshift($generators, $childTaskGenerator);
            }

            // Put the old generator back on, but at the end so we do it later
            $generators[] = $generator;

            $chunk++;
        }

        return $newFilePaths;
    }

    private function exportTimeEntries(string $path): array
    {
        $queryBuilder = $this->timeEntryRepository->createDefaultQueryBuilder(true)
                                                  ->orderBy('time_entry.createdAt')
        ;

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'time_entries';

        return $this->exportChunk($filePrefix, $queryBuilder, fn ($items) => TransferTimeEntry::fromEntities($items));
    }

    private function exportStatistics(string $path): array
    {
        $queryBuilder = $this->statisticRepository->createDefaultQueryBuilder()
                                                  ->orderBy('statistic.createdAt')
        ;

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'statistics';

        return $this->exportChunk($filePrefix, $queryBuilder, fn ($items) => TransferStatistic::fromEntities($items));
    }

    private function exportStatisticValues(string $path): array
    {
        $queryBuilder = $this->statisticValueRepository->createDefaultQueryBuilder()
                                                       ->orderBy('statistic_value.createdAt')
        ;

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'statistic_values';

        return $this->exportChunk($filePrefix, $queryBuilder, fn ($items) => TransferStatisticValue::fromEntities($items));
    }

    private function exportNotes(string $path): array
    {
        $queryBuilder = $this->noteRepository->createDefaultQueryBuilder()
                                             ->orderBy('note.createdAt')
        ;

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'notes';

        return $this->exportChunk($filePrefix, $queryBuilder, fn ($items) => TransferNote::fromEntities($items));
    }
}
