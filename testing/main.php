<?php

require_once(__DIR__ . '/settings.php');
require_once(__DIR__ . '/vendor/autoload.php');

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

$testFiles = \iRAP\CoreLibs\Filesystem::getDirContents(__DIR__ . '/tests');

foreach ($testFiles as $testFile)
{
    $baseName = basename($testFile);
    $testName = str_replace(".php", "", $baseName);
    
    try
    {
        $test = new $testName();
        $test->run();
        print "{$testName}: PASSED" . PHP_EOL;
    } 
    catch (Exception $ex) 
    {
        print "{$testName}: FAILED - {$ex->getMessage()}" . PHP_EOL;
    }
}