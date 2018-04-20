<?php
/**
*   Class to create weekly recurrences for the evList plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011-2016 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;
use LGLib\Date_Calc;

/**
*   Class to handle weekly recurrences.
*   This handles multiple occurrences per week, specified by day number.
*
*   @package evlist
*   @return mixed   Array of events on success, False on failure
*/
class RecurWeekly extends Recur
{
    public function MakeRecurrences()
    {
        global $_EV_CONF;

        $days_on = $this->event->rec_data['listdays'];
        if (empty($days_on)) return false;

        $occurrence = $this->dt_start;

        //$num_intervals = count($days_on);
        //$last_interval = $days_on[$num_intervals - 1];

        // Start by reducing the starting date by one day. Then the for
        // loop can handle all the events.
        list($y, $m, $d) = explode('-', $occurrence);
        $occurrence = Date_Calc::prevDay($d, $m, $y);
        $count = 1;
        while ($occurrence <= $this->event->rec_data['stop'] &&
                    $occurrence >= '1971-01-01' &&
                    $count < $_EV_CONF['max_repeats']) {

            foreach ($days_on as $dow) {

                list($y, $m, $d) = explode('-', $occurrence);
                $occurrence = Date_Calc::nextDayOfWeek($dow-1, $d, $m, $y);

                // Stop when we hit the stop date
                if ($occurrence > $this->event->rec_data['stop']) break;

                $this->storeEvent($occurrence);

                $count++;
                if ($count > $_EV_CONF['max_repeats']) break;

            }   // foreach days_on

            if ($this->freq > 1) {
                // Get the beginning of this week, and add $freq weeks to it
                $occurrence = Date_Calc::beginOfWeek($d + (7 * $this->freq), $m, $y);
            }

        }   // while not at stop date

        return $this->events;

    }   // function MakeRecurrences

}   // class RecurWeekly

?>
