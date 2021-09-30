<?php
/**
 * Class to create day-of-month recurrences for the evList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Recurrences;
use Evlist\DateFunc;


/**
 * Class for handling recurrence by day of month.
 * @package evlist
 */
class DOM extends \Evlist\Recurrence
{
    /**
     * Create the recurring dates.
     *
     * @see     Recur::storeEvent()
     * @return  array   Array of event dates and times
     */
    public function MakeRecurrences()
    {
        global $_EV_CONF;

        $intervalA = $this->rec_data['interval'];
        if (!is_array($intervalA)) {
            $intervalA = array($intervalA);
        }
        if (!isset($this->rec_data['weekday'])) {   // Missing day of week
            return $this;
        }

        $occurrence = $this->date_start;
        list($y, $m, $d) = explode('-', $occurrence);

        $count = 0;
        // reduce the weekday number, since evlist uses Sun=1 while
        // DateFunc uses Sun=0
        $datecalc_weekday = (int)$this->rec_data['weekday'] - 1;

        while (
            $occurrence <= $this->rec_data['stop'] &&
            $occurrence >= '1971-01-01' &&
            $count < $_EV_CONF['max_repeats']
        ) {
            foreach ($intervalA as $interval) {

                $occurrence = DateFunc::NWeekdayOfMonth(
                    (int)$interval,
                    $datecalc_weekday,
                    $m, $y
                );

                // Skip any dates earlier than the starting date
                if ($occurrence < $this->date_start) {
                    continue;
                }

                // If the new date goes past the end of month, and we're looking
                // for the last (5th) week, then re-adjust to use the 4th week.
                // If we already have a 4th, this will just overwrite it
                if ($occurrence == -1 && $interval == 5) {
                    $occurrence = DateFunc::NWeekdayOfMonth(4, $datecalc_weekday, $m, $y);
                }

                // Stop when we hit the stop date
                if ($occurrence > $this->rec_data['stop']) {
                    break;
                }

                // This occurrence is ok, save it
                $this->storeEvent($occurrence);
                $count++;

                list($y, $m, $d) = explode('-', $occurrence);

            }   // foreach intervalA

            // We've gone through all the intervals this month, now
            // increment the month
            $m += $this->rec_data['freq'];
            if ($m > 12) {      // Roll over to next year
                $y += 1;
                $m = $m - 12;
            }

        }   // while not at stop date

        return $this;
    }

}
