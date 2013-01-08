<?php
/*
* This file is part of Backupper.
* (c) Steffen Zeidler <sigma_z@sigma-scripts.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace File;

/**
 * @author Steffen Zeidler <sigma_z@sigma-scripts.de>
 * Date: 03.01.13
 */
class BufferedCopy
{

    private $sourceFile = '';
    private $targetFile = '';
    /**
     * @var resource
     */
    private $sourceHandle = false;
    /**
     * @var resource
     */
    private $targetHandle = false;


    public function __construct($source, $target)
    {
        $this->sourceFile = $source;
        $this->targetFile = $target;
    }


    public function copyBuffered($bufferSize = 1024)
    {
        $sourceHandle = $this->getSourceHandle();
        $targetHandle = $this->getTargetHandle();
        if (!feof($sourceHandle)) {
            return fwrite($targetHandle, fread($sourceHandle, $bufferSize), $bufferSize);
        }
        fclose($targetHandle);
        $fileStat = fstat($sourceHandle);
        $modifiedTime = self::getSummertimeAware($fileStat['mtime']);
        $accessedTime = self::getSummertimeAware($fileStat['atime']);
        touch($this->targetFile, $modifiedTime, $accessedTime);
        return 0;
    }


    private static function getSummertimeAware($t)
    {
        $year = date('Y', $t);
        $initDay = 31 - (floor(5 * $year / 4) + 4) % 7;
        $endDay = 31 - (floor(5 * $year / 4) + 1) % 7;
        // Calculate timestamps for CEST init and end day
        $initTime = mktime(2, 0, 0, 3, $initDay, $year);
        $endTime = mktime(2, 0, 0, 10, $endDay, $year);
        // Are we in summer time?
        if ($t > $initTime && $t < $endTime) {
            // We are in summer time, so add an hour to the timeStamp
            return $t + 3600;
        }
        // We are NOT in summer time, so leave the Timestamp as is
        return $t;
    }


    private function getSourceHandle()
    {
        if ($this->sourceHandle === false) {
            $this->sourceHandle = self::openFileHandle($this->sourceFile, 'rb');
        }
        return $this->sourceHandle;
    }


    private function getTargetHandle()
    {
        if ($this->targetHandle === false) {
            $this->targetHandle = self::openFileHandle($this->targetFile, 'wb');
        }
        return $this->targetHandle;
    }


    private static function openFileHandle($file, $mode)
    {
        $handle = fopen($file, $mode);
        if (!$handle) {
            switch ($mode[0]) {
                case 'r': $modeAsText = 'read'; break;
                case 'w': $modeAsText = 'write'; break;
                case 'a': $modeAsText = 'append'; break;
                default: $modeAsText = $mode;
            }
            throw new \Exception("Could not open file file '$file' for $modeAsText!");
        }
        return $handle;
    }

}
