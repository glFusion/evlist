<?php
/**
 * Class to create yearly recurrences for the evList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2016 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.3
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;

/**
 * Class to handle annual recurrences.
 * @package evlist
 */
class RecurYearly extends Recurrence
{
    /**
     * Increment the date by one year.
     *
     * @param   integer $d  Current day
     * @param   integer $m  Current month
     * @param   integer $y  Current year
     * @return  string      New date as YYYY-MM-DD
     */
    protected function incrementDate($d, $m, $y)
    {
        $newdate = date('Y-m-d', mktime(0, 0, 0, $m, $d, ($y + $this->freq)));
        return $newdate;
    }

}   // class RecurYearly

?>
