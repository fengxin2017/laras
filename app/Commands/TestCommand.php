<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected function configure()
    {
        $this->setName('test');
        $this->setDescription('Laras test command');
        $this->setHelp(sprintf('%s ./bin/maker test', PHP_BINARY));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=yellow;bg=black>meet your mark neo~</>');

        return 0;
    }
}