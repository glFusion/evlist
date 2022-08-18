<?php
/**
 * Class to create monthly recurrences for the evList plugin.
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
use Evlist\DateFunc;


/**
 * Class to handle monthly recurrences.
 * @package evlist
 */
class Monthly extends \Evlist\Recurrence
{
    /**
     * Create the recurrences for a monthly event.
     * Also sets `$this->events` with the recurring data.
     *
     * @return  array   Array of event start/end dates and times.
     */
    public function MakeRecurrences()
    {
        global $_EV_CONF;

        $days_on = $this->rec_data['listdays'];

        if (!is_array($days_on)) return false;

        $occurrence = $this->date_start;

        //$num_intervals = count($days_on);
        //$last_interval = $days_on[$num_intervals - 1];

        // Start by reducing the starting date by one day. Then the for
        // loop can handle all the events.
        list($y, $m, $d) = explode('-', $occurrence);
        $count = 0;
        while (
            $occurrence <= $this->rec_data['stop'] &&
            $count < $_EV_CONF['max_repeats']
        ) {
            $lastday = DateFunc::cal_days_in_month($m, $y);

            foreach ($days_on as $dom) {
                if ($dom == 32) {
                    $dom = $lastday;
                } elseif ($dom > $lastday) {
                    break;
                }

                $occurrence = sprintf("%d-%02d-%02d", $y, $m, $dom);

                // We might pick up some earlier instances, skip them
                if ($occurrence < $this->date_start) continue;

                // Stop when we hit the stop date
                if ($occurrence > $this->rec_data['stop']) break;

                if ($this->skip > 0) {
                    $occurrence = $this->SkipWeekend($occurrence);
                }
                if ($occurrence !== NULL) {
                    $this->storeEvent($occurrence);
                    $count++;
                }

                if ($count > $_EV_CONF['max_repeats']) break;

            }   // foreach days_on

            // Increment the month
            $m += $this->rec_data['freq'];
            if ($m > 12) {      // Roll over to next year
                $y += 1;
                $m = $m - 12;
            }

        }   // while not at stop date

        return $this;
    }   // function MakeRecurrences


    /**
     * Skip a weekend day according to the event setting.
     *
     * @param   string  $occurrence     Date string of the current occurrence
     * @return  string      Date of next occurrence
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
                if ($this->skip == 2) {
                    // Skip to the next weekday
                    $occurrence = DateFunc::nextWeekday($d, $m, $y);
                } else {
                    // Monthly recurrences are on specific dates, so don't
                    // just skip to the next one- return NULL so the
                    // calling function knows to ignore this instance
                    $occurrence = NULL;
                }
            }
        }
        return $occurrence;
    }   // function SkipWeekend


    /**
     * Get the next date from the supplied parameters, based on the frequency.
     *
     * @param   integer $d  Current Day Number
     * @param   integer $m  Current Month Number
     * @param   integer $y  Current Year Number
     * @return  string      Next date formatted as "YYYY-MM-DD"
     */
    private function incrementDate($d, $m, $y)
    {
        $newdate = date('Y-m-d', mktime(0, 0, 0, ($m + $this->freq), $d, $y));
        return $newdate;
    }

}
