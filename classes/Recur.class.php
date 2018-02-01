<?php
/**
*   Class to create recurrences for the evList plugin.
*   Each class is derived from Recur, and should override either
*   MakeRecurrences() or incrementDate().
*   MakeRecurrences is the only public function and the only one that is
*   required.  Derived classes may also implement the base MakeRecurrences()
*   function, in which case they should at least provide their own
*   incrementDate().
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011-2017 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;

/**
*   Class for event recurrence calculations.
*   Override this class for specific recurrence types
*   @package evlist
*/
class Recur
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
    public function __construct($event)
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

        $this->dt_start = $this->event->date_start1 != '' ?
                    $this->event->date_start1 : $_EV_CONF['_today'];
        $this->dt_end = $this->event->date_end1 > $this->event->date_start1 ?
                    $this->event->date_end1 : $this->event->date_start1;

        if ($this->dt_start != $this->dt_end) {
            list($syear, $smonth, $sday) = explode('-', $this->dt_start);
            list($eyear, $emonth, $eday) = explode('-', $this->dt_end);
            // Need to get the number of days the event lasts
            $this->duration = \Date_Calc::dateDiff($eday, $emonth, $eyear,
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
    private function getNextDate($d, $m, $y)
    {
        $newdate = array();
        $newdate[0] = $this->incrementDate($d, $m, $y);
        if ($this->skip > 0) {
            $newdate[1] = $this->SkipWeekend($newdate[0]);
        } else {
            $newdate[1] = $newdate[0];      // normally, scheduled = actual
        }
        return $newdate;
    }


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

            $dow = \Date_Calc::dayOfWeek($d, $m, $y);
            if ($dow == 6 || $dow == 0) {
                if ($this->skip == 2) {
                    // Skip to the next weekday
                    $occurrence = \Date_Calc::nextWeekday($d, $m, $y);
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
        $thedate = \Date_Calc::dateFormat($day, $month, $year, '%Y-%m-%d');
        $occurrence = array($thedate, $thedate);

        // Get any occurrences before our stop.  Keep these.
        $count = 0;
        while ($occurrence[1] <= $this->event->rec_data['stop'] &&
                $occurrence[1] >= '1971-01-01' &&
                $count < $_EV_CONF['max_repeats']) {
            $this->storeEvent($occurrence[1]);
            $count++;

            $occurrence = $this->getNextDate($day, $month, $year);
            while ($occurrence[1] === NULL) {
                if ($occurrence === NULL) {
                    break 2;
                }
                list($year, $month, $day) = explode('-', $occurrence[0]);
                $occurrence = $this->getNextDate($day, $month, $year);
            }
            list($year, $month, $day) = explode('-', $occurrence[0]);
        }

        return $this->events;
    }


    /**
    *   Store an event in the array.
    *   Figures out the ending date based on the duration.
    *   The events array is keyed by start date to avoid duplicates.
    *
    *   @param  string  $start  Starting date in Y-m-d format
    */
    public function storeEvent($start)
    {
        global $_CONF;

        if ($this->duration > 0) {
            $d = new \Date($start, $_CONF['timezone']);
            $e = $d->add(new \DateInterval("P{$this->duration}D"));
            $enddate = $e->format('Y-m-d', true);
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

}   // class Recur

?>
