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
 * Date: 04.01.13
 */
class Backupper
{

    const MODE_FULL         = 'full';
    const MODE_DIFFERENTIAL = 'differential';
    const MODE_INCREMENTAL  = 'incremental';

    private static $supportedModes = array(
        self::MODE_INCREMENTAL,
        self::MODE_DIFFERENTIAL,
        self::MODE_FULL
    );

    private $mode       = array();
    private $filesCnt   = 0;
    private $megaBytes  = .0;
    private $timer      = null;
    private $lastBackup = '';

    private $excludeNames = array();
    private $excludePaths = array();


    public function __construct($mode = self::MODE_FULL, array $lastBackups = array())
    {
        if (!in_array($mode, self::$supportedModes)) {
            $mode = self::MODE_FULL;
        }
        $this->lastBackup = $this->getLastBackupDate($mode, $lastBackups);
        if (empty($this->lastBackup)) {
            $mode = self::MODE_FULL;
        }
        $this->mode = $mode;
    }


    private function getLastBackupDate($mode, array $lastBackups)
    {
        $lastBackup = '';

        switch ($mode) {
            case self::MODE_DIFFERENTIAL:
                if (isset($lastBackups[self::MODE_FULL])) {
                    $lastBackup = $lastBackups[self::MODE_FULL];
                }
                break;

            case self::MODE_INCREMENTAL:
                foreach (self::$supportedModes as $mode) {
                    if (!isset($lastBackups[$mode]) || $lastBackup > $lastBackups[$mode]) {
                        continue;
                    }
                    $lastBackup = $lastBackups[$mode];
                }
                break;
        }

        return $lastBackup;
    }


    public function setExcludes(array $excludeNames, array $excludePaths)
    {
        $this->excludeNames = $excludeNames;
        $this->excludePaths = $excludePaths;
    }


    public function run($source, $target)
    {
        $this->filesCnt = 0;
        $this->megaBytes = 0;
        $this->startTimer();

        $this->backupFolder($source, $target);
    }


    public function getTargetFolder($target)
    {
        return str_replace(array('%date%', '%mode%'), array(date('Y-m-d'), $this->mode), $target);
    }


    private function backupFolder($source, $target)
    {
        // cache for created files
        $createdFolders = array();
        $timer = $this->getTimer();
        $source = rtrim(realpath($source), DIRECTORY_SEPARATOR);
        $target = $this->getTargetFolder($target);

        if (!is_dir($target)) {
            mkdir($target, 0777, true);
        }

        $fileIterator = new \File\FileIterator($source);
        $fileIterator->setExcludeNames($this->excludeNames);
        $fileIterator->setExcludePaths($this->excludePaths);

        $startTime = microtime(true);
        $i = 0;
        $writtenBytes = 0;

        /** @var SplFileInfo $item */
        foreach ($fileIterator as $item) {
            if ($this->mode != self::MODE_FULL && !$this->fileChanged($item)) {
                continue;
            }
            if ($item->isDir()) {
                continue;
            }

            $file = $item->getPathname();
            $relativeFile = trim(substr($file, strlen($source)), DIRECTORY_SEPARATOR);
            $targetFile = $target . '/' . $relativeFile;
            if (is_file($targetFile)) {
                $targetFileInfo = new \SplFileInfo($targetFile);
                if ($item->getMTime() <= $targetFileInfo->getMTime()) {
                    continue;
                }
            }

            $this->filesCnt++;
            $path = $target . '/' . dirname($relativeFile);
            if (!isset($createdFolders[$path])) {
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                $createdFolders[$path] = true;
            }
            $currentFileWrittenSize = 0;
            try {
                $shortFileName = strlen($relativeFile) > 36
                    ? substr($relativeFile, 0, 17) . '...' . substr($relativeFile, -17)
                    : $relativeFile;

                $bufferedCopy = new \File\BufferedCopy($item->getPathname(), $targetFile);
                while (($size = $bufferedCopy->copyBuffered())) {
                    $i++;
                    $writtenBytes += $size;
                    $currentFileWrittenSize += $size;
                    $this->megaBytes += ($size / (1024 * 1024));
                    if ($i % 200 == 0) {
                        $endTime = microtime(true);
                        $col1 = sprintf("%.1f", $this->megaBytes) . 'MB';
                        $col2 = sprintf("%.2f", (($writtenBytes / ($endTime - $startTime)) / 1024)) . 'kB/s';
                        $col3 = $timer->getElapsedHumanReadable();
                        $col1 .= str_repeat(' ', 12 - strlen($col1));
                        $col2 .= str_repeat(' ', 16 - strlen($col2));
                        $col3 .= str_repeat(' ', 10 - strlen($col3));
                        $col4 = $shortFileName;
                        echo "\r";
                        echo "$col1 $col2 $col3 $col4";
                        $writtenBytes = 0;
                        $startTime = $endTime;
                    }
                }
            }
            catch (\Exception $e) {
                $this->megaBytes -= $currentFileWrittenSize;
                continue;
            }
        }
    }


    private function fileChanged(\SplFileInfo $file)
    {
        return $this->lastBackup < $file->getMTime();
    }


    public function getBackupInMegaBytes()
    {
        return $this->megaBytes;
    }


    public function getNumberOfFiles()
    {
        return $this->filesCnt;
    }


    public function setTimer(\Timer $timer)
    {
        $this->timer = $timer;
    }


    public function getTimer()
    {
        if ($this->timer === null) {
            $this->timer = new \Timer();
        }
        return $this->timer;
    }


    private function startTimer()
    {
        $this->getTimer()->start();
    }

}
