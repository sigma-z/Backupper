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
 * Date: 30.12.12
 */
class FileIterator implements \Iterator
{

    /**
     * @var \DirectoryIterator[]
     */
    private $pathStack = array();
    /**
     * @var \DirectoryIterator
     */
    private $currentIterator;
    /**
     * @var int
     */
    private $key = 0;
    /**
     * @var array
     */
    private $excludeNames = array('.', '..');
    /**
     * @var array
     */
    private $excludePaths = array();


    public function __construct($folder)
    {
        $this->currentIterator = new \DirectoryIterator($folder);
    }


    public function setExcludeNames($excludes)
    {
        $this->excludeNames = array_merge($excludes, array('.', '..'));
    }


    public function setExcludePaths($excludes)
    {
        $this->excludePaths = $excludes;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return $this->currentIterator->current();
    }


    public function getDepth()
    {
        return count($this->pathStack) + 1;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        if (!$this->dirDown()) {
            $this->currentIterator->next();
            $this->dirUp();

            if (!$this->valid()) {
                return;
            }
        }
        $this->key++;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->key;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $current = $this->currentIterator->current();
        while ($current) {
            $pathName = $current->getPathname();
            if (DIRECTORY_SEPARATOR !== '/') {
                $pathName = str_replace(DIRECTORY_SEPARATOR, '/', $pathName);
            }

            if (!$current->isReadable()
                || $this->isExcludedByPath($pathName)
                || in_array($current->getFilename(), $this->excludeNames))
            {
                $this->currentIterator->next();
                $current = $this->currentIterator->current();
            }
            else {
                break;
            }
        }
        if ($this->currentIterator->valid()) {
            return true;
        }
        else if (!empty($this->pathStack)) {
            $this->dirUp();
        }
        return $this->currentIterator->valid();
    }


    private function isExcludedByPath($path)
    {
        $length = strlen($path);
        if ($length == 0) {
            return false;
        }
        foreach ($this->excludePaths as $excludedPath) {
            if ($length <= strlen($excludedPath) && substr($excludedPath, 0, $length) === $path) {
                return true;
            }
        }
        return false;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        if (isset($this->pathStack[0])) {
            $this->currentIterator = $this->pathStack[0];
        }
        $this->currentIterator->rewind();
        $this->key = 0;
    }


    private function dirDown()
    {
        /** @var \SplFileInfo $current */
        $current = $this->currentIterator->current();
        if ($current->isDir()) {
            $this->pathStack[] = $this->currentIterator;
            $this->currentIterator = new \DirectoryIterator($current->getPathname());
            return true;
        }
        return false;
    }


    private function dirUp()
    {
        while (!$this->currentIterator->valid() && !empty($this->pathStack)) {
            $this->currentIterator = array_pop($this->pathStack);
            $this->currentIterator->next();
        }
    }

}
