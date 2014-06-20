<?php
/**
*   Class to create recurrences for the evList plugin.
*   Each class is derived from evRecurBase, and should override either
*   MakeRecurrences() or incrementDate().
*   MakeRecurrences is the only public function and the only one that is 
*   required.  Derived classes may also implement the base MakeRecurrences()
*   function, in which case they should at least provide their own 
*   incrementDate().
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.3.2
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*              GNU Public License v2 or later
*   @filesource
*/


/**
*   Class for event recurrence calculations.
*   Override this class for specific recurrence types
*   @package evlist
*/
class evRecurBase
{
    /** Recurring data
    *   @var array */
    //var $rec_data = array();
    var $event;
    var $events;

    var $dt_start;
    var $dt_end;
    var $duration;
    var $freq;
    var $skip;

    /**
    *   Constructor.
    *
    *   @param  object  $event  Event object
    */
    function __construct($event)
    {
        global $_EV_CONF;

        $this->event = $event;

        // Initialize array of events to be loaded
        $this->events = array();
        $this->freq = isset($event->rec_data['freq']) ? 
                (int)$event->rec_data['freq'] : 1;
        if ($this->freq < 1) $this->freq = 1;
        $this->skip = isset($event->rec_data['skip']) ?
                (int)$event->rec_data['skip'] : 0;

        $this->dt_start = $this->event->dt_start1 == '' ? 
                    $this->event->date_start1 : $_EV_CONF['_today'];
        $this->dt_end = $this->event->dt_end1 > $this->event->dt_start1 ? 
                    $this->event->dt_end1 : $this->event->dt_start1;

        if ($this->dt_start != $this->dt_end) {
            list($syear, $smonth, $sday) = explode('-', $this->dt_start);
            list($eyear, $emonth, $eday) = explode('-', $this->dt_end);
            // Need to get the number of days the event lasts
            $this->duration = Date_Calc::dateDiff($eday, $emonth, $eyear,
                        $sday, $smonth,$syear);
        } else {
            $this->duration = 0;      // single day event
        }

    }


    /**
    *   Find the next date, based on the current day, month & year
    *
    *   @param  integer $d  current day
    *   @param  integer $m  current month
    *   @paam   integer $y  current year
    *   @return array           array of (scheduled, actual) dates
    */
    private function GetNextDate($d, $m, $y)
    {
        $newdate = array();

        $newdate[0] = $this->incrementDate($d, $m, $y);
        $newdate[1] = $newdate[0];      // normally, scheduled = actual

        if ($this->skip > 0) {
            $newdate[1] = $this->SkipWeekend($newdate[0]);
        }

        return $newdate;

    }   // function GetNextDate()


    /**
    *   Skip a weekend date, if configured.
    *
    *   @param  string  $occurrence     Date being checked
    *   @return string      Original or new date
    */
    protected function SkipWeekend($occurrence)
    {
        // Figure out the next day if we're supposed to skip one.
        // We don't need to do this if we're just going to continue
        // the frequency loop to the next instance.
        if ($this->skip > 0) {
            // Split out the components of the new working date.
            list($y, $m, $d) = explode('-', $occurrence);

            $dow = Date_Calc::dayOfWeek($d, $m, $y);
            if ($dow == 6 || $dow == 0) {
                if ($this->skip == 2) {
                    // Skip to the next weekday
                    $occurrence = Date_Calc::nextWeekday($d, $m, $y);
                } elseif ($dow == 0) {
                    // Skip must = 1, so just jump to the next occurrence.
                    $occurrence = $this->incrementDate($d, $m, $y);
                }
            }
        }

        return $occurrence;

    }   // function SkipWeekend


    /**
    *   Create recurrences.
    *   This is a common function for the most common recurrence types:
    *   once per week/year, etc.
    */
    public function MakeRecurrences()
    {
        global $_EV_CONF;

        list($year, $month, $day) = explode('-', $this->dt_start);

        //  Get the date of this occurrence.  The date is stored as two
        //  values: 0 = the scheduled date for this occurrence, 1 = the
        //  actual date in case it's rescheduled due to a weekend.
        //  Keeping the occurrence is based on (1), scheduling the next
        //  occurrence is based on (0).
        $thedate = Date_Calc::dateFormat($day, $month, $year, '%Y-%m-%d');
        $occurrence = array($thedate, $thedate);

        // Get any occurrences before our stop.  Keep these.
        $count = 0;
        while ($occurrence[1] <= $this->event->rec_data['stop'] && 
                $occurrence[1] >= '1971-01-01' &&
                $count < $_EV_CONF['max_repeats']) {
            $this->storeEvent($occurrence[1]);
            $count++;

            $occurrence = $this->GetNextDate($day, $month, $year);
            while ($occurrence[1] === NULL) {
                if ($occurrence === NULL) {
                    break 2;
                }
                list($year, $month, $day) = explode('-', $occurrence[0]);
                $occurrence = $this->GetNextDate($day, $month, $year);
            }
            list($year, $month, $day) = explode('-', $occurrence[0]);
        }

        return $this->events;
    }


    /**
    *   Store an event in the array.
    *   Figures out the ending date based on the duration.
    *   The events array is keyed by start date to avoid duplicates.
    */
    function storeEvent($start)
    {
        if ($this->duration > 0) {
            $enddate = strtotime("+$duration day" , strtotime($start)) ;
        } else {
            $enddate = $start;
        }

        // Add this occurance to our array.  The first selected date is
        // always added
        $this->events[$start] = array(
                        'dt_start'  => $start,
                        'dt_end'    => $enddate,
                        'tm_start1'  => $this->event->time_start1,
                        'tm_end1'    => $this->event->time_end1,
                        'tm_start2'  => $this->event->time_start2,
                        'tm_end2'    => $this->event->time_end2,
        );

    }   // function storeEvent()


}   // class evRecurBase


/**
*   Class for events recurring by user-specified dates.
*   @package evlist
*/
class evRecurDates extends evRecurBase
{
    public function MakeRecurrences()
    {
        if (!is_array($this->event->rec_data['custom']))
            return $this->events;

        foreach($this->event->rec_data['custom'] as $occurrence) {
            list($y, $m, $d) = explode('-', $occurrence);
            if (Date_Calc::isValidDate($d, $m, $y)) {
                $this->storeEvent($occurrence);
            }
        }

        return $this->events;
    }

}   // class evRecurDates


/**
*   Class for handling recurrence by day of month.
*   @package evlist
*/
class evRecurDOM extends evRecurBase
{
    public function MakeRecurrences()
    {
        global $_EV_CONF;

        $intervalA = $this->event->rec_data['interval'];
        if (!is_array($intervalA)) {
            $intervalA = array($intervalA);
        }

        if (!isset($this->event->rec_data['weekday']))   // Missing day of week
            return $this->events;

        $occurrence = $this->dt_start;
        list($y, $m, $d) = explode('-', $occurrence);

        $num_intervals = count($intervalA);
        $last_interval = $intervalA[$num_intervals - 1];

        $count = 0;
        // reduce the weekday number, since evlist uses Sun=1 while 
        // Date_Calc uses Sun=0
        $datecalc_weekday = (int)$this->event->rec_data['weekday'] - 1;

        while ($occurrence <= $this->event->rec_data['stop'] && 
                    $occurrence >= '1971-01-01' &&
                    $count < $_EV_CONF['max_repeats']) {

            foreach ($intervalA as $interval) {

                $occurrence = Date_Calc::NWeekdayOfMonth(
                            (int)$interval, $datecalc_weekday,
                            $m, $y, '%Y-%m-%d'
                );

                // Skip any dates earlier than the starting date
                if ($occurrence < $this->dt_start) continue;

                // If the new date goes past the end of month, and we're looking
                // for the last (5th) week, then re-adjust to use the 4th week.
                // If we already have a 4th, this will just overwrite it
                if ($occurrence == -1 && $interval == 5) {
                    $occurrence = Date_Calc::NWeekdayOfMonth( 
                                4, $datecalc_weekday,
                                $m, $y, '%Y-%m-%d');
                }

                // Stop when we hit the stop date
                if ($occurrence > $this->event->rec_data['stop']) break;

                // This occurrence is ok, save it
                $this->storeEvent($occurrence);
                $count++;

                list($y, $m, $d) = explode('-', $occurrence);
 
            }   // foreach intervalA

            // We've gone through all the intervals this month, now
            // increment the month
            $m += $this->event->rec_data['freq'];
            if ($m > 12) {      // Roll over to next year
                $y += 1;
                $m = $m - 12;
            }

        }   // while not at stop date

        return $this->events;

    }   // function MakeRecurrences

}   // class evRecurDOM


/**
*   Class to handle daily recurrences.
*   @package evlist
*/
class evRecurDaily extends evRecurBase
{

    protected function incrementDate($d, $m, $y)
    {
        $newdate = date('Y-m-d', mktime(0, 0, 0, $m, ($d + $this->freq), $y));
        return $newdate;
    }


    /**
    *   Skip a weekend date, if configured.
    *   For daily events, the only real option is to just skip the weekend
    *   completely.
    *
    *   @param  string  $occurrence     Date being checked
    *   @return string      Original or new date
    */
    protected function SkipWeekend($occurrence)
    {
        // Figure out the next day if we're supposed to skip one.
        // We don't need to do this if we're just going to continue
        // the frequency loop to the next instance.
        if ($this->skip > 0) {
            // Split out the components of the new working date.
            list($y, $m, $d) = explode('-', $occurrence);

            $dow = Date_Calc::dayOfWeek($d, $m, $y);
            if ($dow == 6 || $dow == 0) {
                return NULL;
            }
        }

        return $occurrence;

    }   // function SkipWeekend


}   // class evRecurDaily


/**
*   Class to handle monthly recurrences.
*   @package evlist
*/
class evRecurMonthly extends evRecurBase
{

    public function MakeRecurrences()
    {
        global $_EV_CONF;

        $days_on = $this->event->rec_data['listdays'];
        if (!is_array($days_on)) return $this->events;

        $occurrence = $this->dt_start;

        $num_intervals = count($days_on);
        $last_interval = $days_on[$num_intervals - 1];

        // Start by reducing the starting date by one day. Then the for
        // loop can handle all the events.
        list($y, $m, $d) = explode('-', $occurrence);
        //$occurrence = Date_Calc::prevDay($d, $m, $y);
        //$count = 1;
        $count = 0;
        while ($occurrence <= $this->event->rec_data['stop'] && 
                    //$occurrence >= '1971-01-01' &&
                    $count < $_EV_CONF['max_repeats']) {

            $lastday = Date_Calc::daysInMonth($m, $y); // last day in month

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
                if ($occurrence != NULL) {
                    $this->storeEvent($occurrence);
                    $count++;
                }

                if ($count > $_EV_CONF['max_repeats']) break;

                //list($y, $m, $d) = explode('-', $occurrence);

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

            $dow = Date_Calc::dayOfWeek($d, $m, $y);
            if ($dow == 6 || $dow == 0) {
                if ($this->skip == 2) {
                    // Skip to the next weekday
                    $occurrence = Date_Calc::nextWeekday($d, $m, $y);
                } else {
                    // Monthly recurrences are on specific dates, so don't
                    // just stip to the next one- return NULL so the
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

}   // class evRecurMonthly


/**
*   Class to handle annual recurrences.
*   @package evlist
*/
class evRecurYearly extends evRecurBase
{

    protected function incrementDate($d, $m, $y)
    {
        $newdate = date('Y-m-d', mktime(0, 0, 0, $m, $d, ($y + $this->freq)));
        return $newdate;
    }

}   // class evRecurYearly


/**
*   Class to handle weekly recurrences.
*   This handles multiple occurrences per week, specified by day number.
*   @package evlist
*/
class evRecurWeekly extends evRecurBase
{
    public function MakeRecurrences()
    {
        global $_EV_CONF;

        $days_on = $this->event->rec_data['listdays'];
        $occurrence = $this->dt_start;

        $num_intervals = count($days_on);
        $last_interval = $days_on[$num_intervals - 1];

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

}   // class evRecurWeekly


?>
