<?php
/*
* This file is part of Backupper.
* (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 30.12.12
 */

define('VERSION', '1.0');

echo 'Backupper ' . VERSION . ' by Steffen Zeidler.' . "\n\n";

require __DIR__ . '/lib/File/BufferedCopy.php';
require __DIR__ . '/lib/File/FileIterator.php';
require __DIR__ . '/lib/Backupper.php';
require __DIR__ . '/lib/Timer.php';

$args       = array_slice($_SERVER['argv'], 1);
$arguments  = getArguments($args);
$backupName = isset($arguments['name']) ? $arguments['name'] : 'default';
$mode       = isset($arguments['mode']) ? $arguments['mode'] : \Backupper::MODE_FULL;
$configFile = __DIR__ . "/config/$backupName.php";

if (!file_exists($configFile) || !is_readable($configFile)) {
    echo "Could not read config file '$configFile'!\n";
    exit;
}

$config = include $configFile;
if (empty($config['sources'])) {
    echo "Missing sources for backup with name '$backupName'!\n";
    exit;
}

if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', '0777');
}
$lastBackupDataFile = __DIR__ . '/data/' . $backupName . '.ser';
$lastBackups = getLastBackupTimestamps($lastBackupDataFile);

$globalExcludeNames = array();
if (!empty($config['excludeNames'])) {
    $globalExcludeNames = $config['excludeNames'];
}

$backupTimer = Timer::startTimer();
$totalWrittenMegaBytes = .0;
$totalNumOfFiles = 0;

foreach ($config['sources'] as $source => $backupData) {
    $excludeNames = isset($backupData['excludeNames'])
        ? array_merge($globalExcludeNames, $backupData['excludeNames'])
        : $globalExcludeNames;

    $excludePaths = isset($backupData['excludePaths']) ? $backupData['excludePaths'] : array();

    $lastBackupsForSource = isset($lastBackups[$source]) ? $lastBackups[$source] : array();
    $backupper = new \Backupper($mode, $lastBackupsForSource);
    $backupper->setExcludes($excludeNames, $excludePaths);
    $timer = $backupper->getTimer();

    $target = $backupData['target'];
    $targetFolder = $backupper->getTargetFolder($target);


    echo "Starting backup of $source to $targetFolder\n";
    // running backup
    $backupper->run($source, $target);


    $writtenMegaBytes = $backupper->getBackupInMegaBytes();
    $totalWrittenMegaBytes += $writtenMegaBytes;

    $numOfFiles = $backupper->getNumberOfFiles();
    $totalNumOfFiles += $numOfFiles;

    $timer = $backupper->getTimer();
    echo "\n";
    echo 'Runtime    : ' . $timer->getElapsedHumanReadable() . "\n";
    echo 'Backup size: ' . sprintf('%.2f', $writtenMegaBytes) . " MBytes\n";
    echo 'Files      : ' . $numOfFiles . "\n\n";

    $lastBackups[$source][$mode] = floor($timer->getStartTime());
    file_put_contents($lastBackupDataFile, serialize($lastBackups));
}

if (count($config['sources']) > 1) {
    echo "\n\n";
    echo "SUMMARY\n";
    echo "-------\n";
    echo 'Total runtime    : ' . $backupTimer->getElapsedHumanReadable() . "\n";
    echo 'Total backup size: ' . sprintf('%.2f', $totalWrittenMegaBytes) . " MBytes\n";
    echo 'Total files      : ' . $totalNumOfFiles . "\n";
}

echo "\nBACKUP FINISHED\n";


function getArguments(array $args)
{
    $arguments = array();
    foreach ($args as $arg) {
        if (false !== strpos($arg, '=', 1)) {
            list($argName, $argValue) = explode('=', $arg, 2);
        }
        else {
            $argName = $arg;
            $argValue = true;
        }
        $arguments[$argName] = $argValue;
    }
    return $arguments;
}


function getLastBackupTimestamps($lastBackupDataFile)
{
    if (is_file($lastBackupDataFile)) {
        $lastBackupsFileContent = file_get_contents($lastBackupDataFile);
        if (!empty($lastBackupsFileContent)) {
            return unserialize($lastBackupsFileContent);
        }
    }
    return array();
}
