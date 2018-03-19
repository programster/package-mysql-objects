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

$testFiles = \iRAP\CoreLibs\Filesystem::getDirContents(__DIR__ . '/tests');

foreach ($testFiles as $testFile)
{
    $baseName = basename($testFile);
    $testName = str_replace(".php", "", $baseName);
    
    $test = new $testName();
    $test->run();
}