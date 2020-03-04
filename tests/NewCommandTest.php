<?php

namespace Laravel\Installer\Console\Tests;

use Laravel\Installer\Console\NewCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class NewCommandTest extends TestCase
{
    public function testItCanScaffoldANewCollectionMacroPackage(): void
    {
        $scaffoldDirectory = __DIR__ . '/../collection-macro-test';

        if (file_exists($scaffoldDirectory)) {
            (new Filesystem)->remove($scaffoldDirectory);
        }

        $app = new Application('Collection Macro Generator');
        $app->add(new NewCommand);

        $tester = new CommandTester($app->find('new'));
        $statusCode = $tester->execute(['macro_name' => 'test']);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($scaffoldDirectory);
        $this->assertDirectoryExists($scaffoldDirectory . '/vendor');
    }
}
