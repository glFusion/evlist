<?php
/**
 * Class to create daily recurrences for the evList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.4.3
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Recurrences;


/**
 * Class to handle daily recurrences.
 * @package evlist
 */
class Daily extends \Evlist\Recurrence
{
    /**
     * Increment the given date according to the frequency value.
     *
     * @param   integer $d  Day number
     * @param   integer $m  Month number
     * @param   integer $y  Year number
     * @return  string      New date formatted as YYYY-MM-DD
     */
    protected function incrementDate($d, $m, $y)
    {
        $newdate = date('Y-m-d', mktime(0, 0, 0, $m, ($d + $this->freq), $y));
        return $newdate;
    }


    /**
     * Skip a weekend date, if configured.
     * For daily events, the only real option is to just skip the weekend
     * completely.
     *
     * @param   string  $occurrence     Date being checked
     * @return  string      Original or new date
     */
    protected function SkipWeekend($occurrence)
    {
        // Figure out the next day if we're supposed to skip one.
        // We don't need to do this if we're just going to continue
        // the frequency loop to the next instance.
        if ($this->skip > 0) {
            // Split out the components of the new working date.
            list($y, $m, $d) = explode('-', $occurrence);

            $dow = DateFunc::dayOfWeek($d, $m, $y);
            if ($dow == 6 || $dow == 0) {
                return NULL;
            }
        }
        return $occurrence;
    }

}
