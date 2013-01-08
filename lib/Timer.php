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
class Timer
{

    private static $units = array(
        'd' => 86400,
        'h' => 3600,
        'm' => 60,
        's' => 1
    );

    private $start = .0;


    public static function startTimer()
    {
        $timer = new self;
        $timer->start();
        return $timer;
    }


    public function start()
    {
        $this->start = microtime(true);
        return $this->getStartTime();
    }


    public function getStartTime()
    {
        return $this->start;
    }


    public function reset()
    {
        $this->start = microtime(true);
    }


    public function getElapsed()
    {
        return microtime(true) - $this->start;
    }


    public function getElapsedHumanReadable()
    {
        $elapsed = $this->getElapsed();
        $string = '';

        foreach (self::$units as $unit => $factor) {
            $value = floor($elapsed / $factor);
            if ($value >= 1) {
                $elapsed -= $value * $factor;
                $string .= sprintf('%d', $value) . $unit . ' ';
            }
        }
        if ($string == '') {
            $string = '0s';
        }

        return $string;
    }

}
