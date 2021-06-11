<?php

namespace App\Command;

use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Transfer\TransferTag;
use App\Transfer\TransferUser;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ExportDataCommand extends Command
{
    protected static $defaultName = 'app:data:export';
    protected static string $defaultDescription = 'Export the data in the database to several files';

    private Serializer $serializer;
    private TagRepository $tagRepository;
    private UserRepository $userRepository;

    public function __construct(
        string $name = null,
        SerializerInterface $serializer,
        TagRepository $tagRepository,
        UserRepository $userRepository
    ) {
        parent::__construct($name);
        $this->serializer = $serializer;
        $this->tagRepository = $tagRepository;
        $this->userRepository = $userRepository;
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

        $io->writeln("Exporting Tags...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportTags($outputPath));

        $io->writeln("Exporting Users...");
        $fileExportOrder = array_merge($fileExportOrder, $this->exportUsers($outputPath));

        $fileExportOrderPath = $outputPath . DIRECTORY_SEPARATOR . 'order.json';

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
            $content = $this->serializer->serialize($transferItems, 'json');
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
}
