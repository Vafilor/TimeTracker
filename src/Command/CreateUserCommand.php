<?php

namespace App\Command;

use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a user',
)]
class CreateUserCommand extends Command
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $io = new SymfonyStyle($input, $output);

        if (is_null($input->getArgument('email'))) {
            $email = $io->ask('Email');
            $input->setArgument('email', $email);
        }

        if (is_null($input->getArgument('username'))) {
            $username = $io->ask('Username');
            $input->setArgument('username', $username);
        }

        if (is_null($input->getArgument('password'))) {
            $passwordsMatch = false;

            while (!$passwordsMatch) {
                $password = $io->askHidden('Password');
                $password2 = $io->askHidden('Confirm password');

                if ($password === $password2) {
                    $input->setArgument('password', $password);
                    $passwordsMatch = true;
                } else {
                    $io->writeln('Passwords do not match');
                }
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        $this->userManager->createUser($email, $username, $password);

        $io->success("User $username has been created.");

        return Command::SUCCESS;
    }
}
