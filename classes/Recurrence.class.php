<?php
/**
 * Class to create recurrences for the evList plugin.
 * Each class is derived from this one, and should override either
 * MakeRecurrences() or incrementDate().
 * MakeRecurrences is the only public function and the only one that is
 * required.  Derived classes may also implement the base MakeRecurrences()
 * function, in which case they should at least provide their own
 * incrementDate().
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2017 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.3
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;


/**
 * Class for event recurrence calculations.
 * Override this class for specific recurrence types
 * @package evlist
 */
class Recurrence
{
    /** Event object.
     * @var object */
    protected $Event = NULL;

    /** Recurrence information for the event, for convenience.
     * @var array */
    protected $rec_data = array();

    /** Array of event dates and times.
     * @var array */
    protected $events = array();

    /** Starting date for the event.
     * @var string */
    protected $date_start = '';

    /** Ending date for the event.
     * @var string */
    protected $date_end = '';

    /** First or only starting time.
     * @var string */
    protected $time_start1 = '';

    /** First or only ending time.
     * @var string */
    protected $time_end1 = '';

    /** Second starting time, if any.
     * @var string */
    protected $time_start2 = '';

    /** Second ending time, if any.
     * @var string */
    protected $time_end2 = '';

    /** First or only starting time
    /** Number of days the event runs.
     * @var integer */
    protected $duration = 0;

    /** Frequency.
     * @var integer */
    protected $freq = 0;

    /** Skip-weekends setting.
     * @var integer */
    protected $skip = 0;


    /**
     * Constructor.
     *
     * @param   object  $event  Event object
     */
    public function __construct($event)
    {
        global $_EV_CONF;

        $this->Event = $event;
        $this->rec_data = $this->Event->getRecData();

        // Initialize array of events to be loaded
        $this->Events = array();
        $this->freq = isset($this->rec_data['freq']) ?
                (int)$this->rec_data['freq'] : 1;
        if ($this->freq < 1) {
            $this->freq = 1;
        }
        $this->skip = isset($this->rec_data['skip']) ?
                (int)$this->rec_data['skip'] : 0;

        $this->date_start = $this->Event->getDateStart1() != '' ?
            $this->Event->getDateStart1() : $_EV_CONF['_today'];
        $this->date_end = $this->Event->getDateEnd1() > $this->Event->getDateStart1() ?
            $this->Event->getDateEnd1() : $this->Event->getDateStart1();

        if ($this->date_start != $this->date_end) {
            list($syear, $smonth, $sday) = explode('-', $this->date_start);
            list($eyear, $emonth, $eday) = explode('-', $this->date_end);
            // Need to get the number of days the event lasts
            $this->duration = DateFunc::dateDiff(
                $eday, $emonth, $eyear,
                $sday, $smonth,$syear
            );
        } else {
            $this->duration = 0;      // single day event
        }
    }


    /**
     * Get a recurrence instance based on event data.
     *
     * @param   array   $Event  Event object
     * @return  object      Recurrence object
     */
    public static function getInstance($Event)
    {
        switch ((int)$Event->getRecData()['type']) {
        case EV_RECUR_ONETIME:
            $Rec = new RecurOnetime($Event);
            break;
        case EV_RECUR_DATES:
            // Specific dates.  Simple handling.
            $Rec = new RecurDates($Event);
            break;
        case EV_RECUR_DOM:
            // Recurs on one or more days each month-
            // e.g. first and third Tuesday
            $Rec = new RecurDOM($Event);
            break;
        case EV_RECUR_DAILY:
            // Recurs daily for a number of days
            $Rec = new RecurDaily($Event);
            break;
        case EV_RECUR_WEEKLY:
            // Recurs on one or more days each week-
            // e.g. Tuesday and Thursday
            $Rec = new RecurWeekly($Event);
            break;
        case EV_RECUR_MONTHLY:
            // Recurs on the same date(s) each month
            $Rec = new RecurMonthly($Event);
            break;
        case EV_RECUR_YEARLY:
            // Recurs once each year
            $Rec = new RecurYearly($Event);
            break;
        }
        return $Rec;
    }


    /**
     * Find the next date, based on the current day, month & year.
     *
     * @param   integer $d  current day
     * @param   integer $m  current month
     * @param   integer $y  current year
     * @return  array           array of (scheduled, actual) dates
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
     * Skip a weekend date, if configured in the event.
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
                if ($this->skip == 2) {
                    // Skip to the next weekday
                    $occurrence = DateFunc::nextWeekday($d, $m, $y);
                } elseif ($dow == 0) {
                    // Skip must = 1, so just jump to the next occurrence.
                    $occurrence = $this->incrementDate($d, $m, $y);
                }
            }
        }
        return $occurrence;
    }   // function SkipWeekend


    /**
     * Create recurrences.
     * This is a common function for the most common recurrence types:
     * once per week/year, etc.
     * Also sets `$this->Events` with the recurring data.
     *
     * @return  array   Array of event start/end dates and times.
     */
    public function MakeRecurrences()
    {
        global $_EV_CONF;

        list($year, $month, $day) = explode('-', $this->date_start);

        //  Get the date of this occurrence.  The date is stored as two
        //  values: 0 = the scheduled date for this occurrence, 1 = the
        //  actual date in case it's rescheduled due to a weekend.
        //  Keeping the occurrence is based on (1), scheduling the next
        //  occurrence is based on (0).
        $thedate = DateFunc::dateFormat($day, $month, $year);
        $occurrence = array($thedate, $thedate);

        // Get any occurrences before our stop.  Keep these.
        $count = 0;
        while (
            $occurrence[1] <= $this->rec_data['stop'] &&
            $occurrence[1] >= '1971-01-01' &&
            $count < $_EV_CONF['max_repeats']
        ) {
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
        return $this;
    }


    /**
     * Store an event in the array.
     * Figures out the ending date based on the duration.
     * The events array is keyed by start date to avoid duplicates.
     *
     * @param   string  $start  Starting date in Y-m-d format
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
        $this->Events[$start] = array(
            'dt_start'  => $start,
            'dt_end'    => $enddate,
            'tm_start1'  => $this->Event->getTimeStart1(),
            'tm_end1'    => $this->Event->getTimeEnd1(),
            'tm_start2'  => $this->Event->getTimeStart2(),
            'tm_end2'    => $this->Event->getTimeEnd2(),
        );
        return $this;
    }


    /**
     * Return the array of event dates & times.
     *
     * @return  array   Array of event dates and times
     */
    public function getEvents()
    {
        return $this->Events;
    }

}   // class Recurrence

?>
