<?php


namespace App\Commands;

use Illuminate\Encryption\Encrypter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeyGenerateCommand extends Command
{
    protected function configure()
    {
        $this->setName('key:generate');
        $this->setDescription('Set the application key');
        $this->setHelp(sprintf('%s artisan key:generate', PHP_BINARY));
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeNewEnvironmentFileWith($this->generateRandomKey());

        $output->writeln('<fg=green;bg=black>Application key set successfully.</>');

        return 0;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:' . base64_encode(Encrypter::generateKey('AES-256-CBC'));
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param string $key
     * @return void
     */
    protected function writeNewEnvironmentFileWith($key)
    {
        $path = ROOT_PATH . DIRECTORY_SEPARATOR . '.env';

        file_put_contents(
            $path,
            preg_replace(
                '/APP_KEY=(.*)/',
                'APP_KEY=' . $key,
                file_get_contents(ROOT_PATH . DIRECTORY_SEPARATOR . '.env')
            )
        );
    }
}