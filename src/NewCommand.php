<?php

namespace Laravel\Installer\Console;

use GuzzleHttp\Client;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use ZipArchive;

class NewCommand extends Command
{
    /** @var string */
    private $downloadLink = 'https://github.com/ChrGriffin/template-collection-macro/archive/master.zip';

    /** @var string */
    private $macroName;

    /** @var string */
    private $macroNameUcFirst;

    /** @var string */
    private $packageName;

    /** @var string */
    private $packageNamespace;

    protected function configure(): void
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Laravel Collection macro package.')
            ->addArgument('macro_name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! extension_loaded('zip')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }

        $this->macroName = strtolower($input->getArgument('macro_name'));
        $this->macroNameUcFirst = ucfirst($this->macroName);
        $this->packageName = "collection-macro-{$this->macroName}";
        $this->packageNamespace = "{$this->macroNameUcFirst}CollectionMacro";

        $directory = getcwd() . '/' . $this->packageName;
        $this->verifyApplicationDoesntExist($directory);

        $output->writeln('<info>Creating package...</info>');

        $this->download($zipFile = $this->makeFileName())
             ->extract($zipFile, $directory)
             ->cleanUp($zipFile)
            ->replaceAllPlaceholderText($directory);

        $composer = $this->findComposer();

        $commands = [
            $composer.' install'
        ];

        $process = Process::fromShellCommandline(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: '.$e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        if ($process->isSuccessful()) {
            $output->writeln('<comment>Package ready!</comment>');
        }

        return 0;
    }

    protected function verifyApplicationDoesntExist(string $directory): void
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Package already exists!');
        }
    }

    protected function makeFilename(): string
    {
        return getcwd().'/collection_'.md5(time().uniqid()).'.zip';
    }

    protected function download(string $zipFile): NewCommand
    {
        $response = (new Client)->get($this->downloadLink);
        file_put_contents($zipFile, $response->getBody());
        return $this;
    }

    protected function extract(string $zipFile, string $directory): NewCommand
    {
        $archive = new ZipArchive;
        $response = $archive->open($zipFile, ZipArchive::CHECKCONS);

        if ($response === ZipArchive::ER_NOZIP) {
            throw new RuntimeException('The zip file could not download. Verify that you are able to access: http://cabinet.laravel.com/latest.zip');
        }

        $archive->extractTo('.');
        $archive->close();

        rename('template-collection-macro-master', $directory);

        return $this;
    }

    protected function cleanUp(string $zipFile): NewCommand
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);

        return $this;
    }

    protected function replaceAllPlaceholderText(string $directory): void
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($rii as $file) {
            if (!$file->isDir()){
                $this->replacePlaceholderInFileContents($file);
            }

            if($file->getFilename() !== '..' && $file->getFilename() !== '.') {
                $this->replacePlaceholderInFilename($file);
            }
        }
    }

    protected function replacePlaceholderInFileContents(\SplFileInfo $file): void
    {
        file_put_contents(
            $file->getPathname(),
            preg_replace(
                ['/<MACRO_NAME>/', '/<MACRO_NAME_UCFIRST>/', '/<PACKAGE_NAME>/', '/<PACKAGE_NAMESPACE>/'],
                [$this->macroName, $this->macroNameUcFirst, $this->packageName, $this->packageNamespace],
                file_get_contents($file->getPathname())
            )
        );
    }

    protected function replacePlaceholderInFilename(\SplFileInfo $file): void
    {
        rename(
            $file->getPathname(),
            preg_replace(
                ['/<MACRO_NAME>/', '/<MACRO_NAME_UCFIRST>/', '/<PACKAGE_NAME>/', '/<PACKAGE_NAMESPACE>/'],
                [$this->macroName, $this->macroNameUcFirst, $this->packageName, $this->packageNamespace],
                $file->getPathname()
            )
        );
    }

    protected function findComposer(): string
    {
        $composerPath = getcwd().'/composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }
}
