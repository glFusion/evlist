<?php
/**
*   Class to create monthly recurrences for the evList plugin.
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

/**
*   Class to handle monthly recurrences.
*   @package evlist
*/
class RecurMonthly extends Recur
{

    public function MakeRecurrences()
    {
        global $_EV_CONF;

        $days_on = $this->event->rec_data['listdays'];
        if (!is_array($days_on)) return false;

        $occurrence = $this->dt_start;

        //$num_intervals = count($days_on);
        //$last_interval = $days_on[$num_intervals - 1];

        // Start by reducing the starting date by one day. Then the for
        // loop can handle all the events.
        list($y, $m, $d) = explode('-', $occurrence);
        //$occurrence = Date_Calc::prevDay($d, $m, $y);
        //$count = 1;
        $count = 0;
        while ($occurrence <= $this->event->rec_data['stop'] &&
                    //$occurrence >= '1971-01-01' &&
                    $count < $_EV_CONF['max_repeats']) {

            $lastday = \Date_Calc::daysInMonth($m, $y); // last day in month

            foreach ($days_on as $dom) {

                if ($dom == 32) {
                    $dom = $lastday;
                } elseif ($dom > $lastday) {
                    break;
                }

                $occurrence = sprintf("%d-%02d-%02d", $y, $m, $dom);

                // We might pick up some earlier instances, skip them
                if ($occurrence < $this->dt_start) continue;

                // Stop when we hit the stop date
                if ($occurrence > $this->event->rec_data['stop']) break;

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
            $m += $this->event->rec_data['freq'];
            if ($m > 12) {      // Roll over to next year
                $y += 1;
                $m = $m - 12;
            }

        }   // while not at stop date

        return $this->events;

    }   // function MakeRecurrences


    protected function SkipWeekend($occurrence)
    {
        // Figure out the next day if we're supposed to skip one.
        // We don't need to do this if we're just going to continue
        // the frequency loop to the next instance.
        if ($this->skip > 0) {
            // Split out the components of the new working date.
            list($y, $m, $d) = explode('-', $occurrence);

            $dow = \Date_Calc::dayOfWeek($d, $m, $y);
            if ($dow == 6 || $dow == 0) {
                if ($this->skip == 2) {
                    // Skip to the next weekday
                    $occurrence = \Date_Calc::nextWeekday($d, $m, $y);
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


    private function incrementDate($d, $m, $y)
    {
        $newdate = date('Y-m-d', mktime(0, 0, 0, ($m + $this->freq), $d, $y));
        return $newdate;
    }

}   // class RecurMonthly

?>
