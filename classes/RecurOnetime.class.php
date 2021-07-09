<?php
/**
 * Class to create a single recurrences for the evList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.4.6
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;


/**
 * Class for single-occurence events.
 * @package evlist
 */
class RecurOnetime extends Recurrence
{
    /**
     * Create the single occurrence.
     *
     * @see     Recur::storeEvent()
     * @return  array   Array of event dates and times
     */
    public function MakeRecurrences()
    {
        // Single non-repeating event
        $this->events = array(
            array(
                'dt_start'  => $this->date_start,
                'dt_end'    => $this->date_end,
                'tm_start1' => $this->time_start1,
                'tm_end1'   => $this->time_end1,
                'tm_start2' => $this->time_start2,
                'tm_end2'   => $this->time_end2,
            ),
        );
        return $this;
    }

}
