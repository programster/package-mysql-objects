<?php

require_once(__DIR__ . '/settings.php');
require_once(__DIR__ . '/../vendor/autoload.php');

$autoloader = new \iRAP\Autoloader\Autoloader(array(
    __DIR__,
    __DIR__ . '/tests',
    __DIR__ . '/models',
));

$migrationManager = new iRAP\Migrations\MigrationManager(
    __DIR__ . '/migrations',
    ConnectionHandler::getDb()
);

$migrationManager->migrate();

$testFiles = \Programster\CoreLibs\Filesystem::getDirContents(__DIR__ . '/tests');

foreach ($testFiles as $testFile)
{
    $baseName = basename($testFile);
    $testName = str_replace(".php", "", $baseName);
    $boldGreen = "\033[1;32m";
    $boldRed = "\033[1;31m";
    $reset = "\033[0m";
    $boldCyan = "\033[1;36m";
    $coloredPassedMessage = "{$boldGreen}PASSED{$reset}";
    $coloredFailedMessage = "{$boldRed}FAILED{$reset}";

    try
    {
        $test = new $testName();
        $test->run();
        print "{$boldCyan}{$testName}{$reset}: {$coloredPassedMessage}" . PHP_EOL;
    }
    catch (Exception $ex)
    {
        print "{$boldCyan}{$testName}{$reset}: {$coloredFailedMessage} - {$ex->getMessage()}" . PHP_EOL;
    }
}