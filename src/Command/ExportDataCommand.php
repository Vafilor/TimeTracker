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
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ExportDataCommand extends Command
{
    protected static $defaultName = 'app:data:export';
    protected static $defaultDescription = 'Export the data in the database to several files';

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
        string $name = null,
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
        parent::__construct($name);
        $this->serializer = $serializer;
        $this->tagRepository = $tagRepository;
        $this->userRepository = $userRepository;
        $this->timestampRepository = $timestampRepository;
        $this->taskRepository = $taskRepository;
        $this->timeEntryRepository = $timeEntryRepository;
        $this->statisticRepository = $statisticRepository;
        $this->statisticValueRepository = $statisticValueRepository;
        $this->noteRepository = $noteRepository;
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

    private function exportChunk(string $path, QueryBuilder $queryBuilder, callable $transformer): array
    {
        $chunk = 1;
        $chunkSize = 500;
        $newFilePaths = [];

        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults($chunkSize);

        $results = $queryBuilder->getQuery()->getResult();
        while (count($results) !== 0) {
            $filePath = "{$path}_{$chunk}.json";
            $transferItems = $transformer($results);

            $content = $this->serializer->serialize($transferItems, 'json', [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true
            ]);

            file_put_contents($filePath, $content);

            $queryBuilder = $queryBuilder->setFirstResult($chunk * $chunkSize);
            $results = $queryBuilder->getQuery()->getResult();
            $chunk++;

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

    private function exportTasks(string $path): array
    {
        $queryBuilder = $this->taskRepository->createDefaultQueryBuilder()
                                             ->orderBy('task.createdAt')
        ;

        $filePrefix = $path . DIRECTORY_SEPARATOR . 'tasks';

        return $this->exportChunk($filePrefix, $queryBuilder, fn ($items) => TransferTask::fromEntities($items));
    }

    private function exportTimeEntries(string $path): array
    {
        $queryBuilder = $this->timeEntryRepository->createDefaultQueryBuilder()
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
