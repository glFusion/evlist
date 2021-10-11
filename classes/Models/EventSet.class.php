<?php
/**
 * Class to handle retrieving event sets.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Models;
use Evlist\DateFunc;
use Evlist\Cache;


/**
 * Class for retrieving sets of events.
 * @package evlist
 */
class EventSet
{
    /** Specific Event ID to retrieve, default is all.
     * @var string */
    private $eid = '';

    /** Specific instance ID to retrieve, default is all.
     * @var integer */
    private $rp_id = 0;

    /** Category ID to show, default is all.
     * @var integer */
    private $cat = 0;

    /** Calendar ID to show, default is all.
     * @var integer */
    private $cal = 0;

    /** Nonzero if showing upcoming events only.
     * @var integer */
    private $show_upcoming = 0;

    /** Limit the number of events returned, default is all.
     * @var integer */
    private $limit = 0;

    /** Page number to show, based on the limit. Default is first page.
     * @var integer */
    private $page = 0;

    /** Starting date in YYYY-MM-DD format.
     * @var string */
    private $start = '';

    /** Ending date in YYYY-MM-DD format.
     * @var string */
    private $end = '';

    /** Return iCal-enabled events/calendars only?
     * Default is all, 1=ical, 0=non-ical.
     * @var integer */
     private $ical = -1;

    /** Order of events, either ASC or DESC.
     * @var string */
    private $order = 'ASC';

    /** Fields to return.
     * @var string */
    private $selection = '';

    /** Group by for results.
     * @var string */
    private $grp_by = 'rep.rp_id';

    /** Showing upcoming events? TODO, duplicate of show_upcoming?
     * @var string */
    private $upcoming = false;

    /** Set required status, default is "enabled".
     * @var integer */
    private $status = Status::ENABLED;


    /**
     * Create a new EventSet.
     *
     * @return  object      EventSet object
     */
    public static function create()
    {
        return new self;
    }


    /**
     * Set specific event to retrieve.
     *
     * @param   string  $eid    Event ID
     * @return  object  $this
     */
    public function withEvent($eid)
    {
        $this->eid = $eid;
        return $this;
    }

    /**
     * Set the specific instance to retrieve.
     *
     * @param   integer $rp_id  Instance ID
     * @return  object  $this
     */
    public function withRepeat($rp_id)
    {
        $this->rp_id = (int)$rp_id;
        return $this;
    }


    /**
     * Set the category to limit results.
     *
     * @param   integer $cat_id Category ID
     * @return  object  $this
     */
    public function withCategory($cat_id)
    {
        $this->cat = (int)$cat_id;
        return $this;
    }


    /**
     * Set the calendar to limit results.
     *
     * @param   integer $cal_id Calender ID
     * @return  object  $this
     */
    public function withCalendar($cal_id)
    {
        $this->cal = (int)$cal_id;
        return $this;
    }


    /**
     * Set the flag to indicate that only upcoming events are being shown.
     *
     * @param   boolean $flag   True = show upcoming, False = show all
     * @return  object  $this
     */
    public function withUpcoming($flag)
    {
        $this->show_upcoming = $flag ? 1 : 0;
        $this->upcoming = $flag ? 1 : 0;
        return $this;
    }


    /**
     * Limit the SQL result set.
     * This limits the SQL query results, the actual results returned may be
     * further limited by removing duplicates or otherwise discarding events.
     *
     * @param   integer $limit  Number of results to retrieve
     * @return  object  $this
     */
    public function withLimit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }


    /**
     * Set the page number for SQL>
     *
     * @param   integer $page   Page number, 0 = all
     * @return  object  $this
     */
    public function withPage($page)
    {
        $this->page = (int)$page;
        return $this;
    }


    /**
     * Set starting date.
     *
     * @param   string  $dt     Date string (YYYY-MM-DD)
     * @return  object  $this
     */
    public function withStart($dt)
    {
        $this->start = $dt;
        return $this;
    }


    /**
     * Set ending date.
     *
     * @param   string  $dt     Date string (YYYY-MM-DD)
     * @return  object  $this
     */
    public function withEnd($dt)
    {
        $this->end = $dt;
        return $this;
    }


    /**
     * Set the ical-only flag.
     *
     * @param   integer $val    Flag value
     * @return  object  $this
     */
    public function withIcal($val)
    {
        $this->ical = $val ? 1 : 0;
        return $this;
    }


    /**
     * Set the status of events to include, NULL for all.
     *
     * @param   integer|null    $val    Value for status flag
     * @return  object  $this
     */
    public function withStatus($val)
    {
        $this->status = $val;
        return $this;
    }


    /**
     * Set the flag to show only active events.
     *
     * @param   boolean $val    False to show all events, True to show only active
     * @return  object  $this
     */
    public function withActiveOnly($val=true)
    {
        return $this->withStatus(Status::ENABLED);
    }


    /**
     * Set the fields to be selected in the query.
     *
     * @param   string  $selection  SQL fields to retrieve
     * @return  object  $this
     */
    public function withSelection($selection)
    {
        $this->selection = $selection;
        return $this;
    }


    /**
     * Set field used to detect unique events.
     *
     * @param   boolean $flag   True for event ID, False for repeat ID
     * @return  object  $this
     */
    public function withUnique($flag)
    {
        $this->grp_by = $flag ? 'ev.id' : 'rep.rp_id';
        return $this;
    }


    /**
     * Create the SQL query to get all events that fall within a range.
     *
     * @return string          SQL query to retrieve events
     */
    public function getSql()
    {
        global $_TABLES, $_EV_CONF, $_CONF, $_USER;

        // Set starting and ending dates if not set.
        if ($this->start == '') {
            $this->start = $_EV_CONF['_today'];
        }
        if ($this->end == '') {
            $this->withEnd($this->start);
        }

        // Split up the date parts and validate
        list($y, $m, $d) = explode('-', $this->start);
        if (!DateFunc::isValidDate($d, $m, $y)){
            $this->start = $_EV_CONF['_today'];
        }
        list($y, $m, $d) = explode('-', $this->end);
        if (!DateFunc::isValidDate($d, $m, $y)) {
            $this->end = $this->start;
        }
        $db_start = DB_escapeString($this->start . ' 00:00:00');
        $db_end = DB_escapeString($this->end . ' 23:59:59');

        // Set up other search options.
        //$selection = '';
        $opt_select = '';
        //$opt_order = 'ASC';
        $orderby = 'rep.rp_start';
        $grp_by = 'rep.rp_id';
        //$limit = 0;
        //$page = 0;
        $cat_status = '';
        $cat_join = '';
        //$cat_status = ' AND (cat.status = 1 OR cat.status IS NULL)';
        // default date range for fixed calendars, "upcoming" may be different
        $dt_sql = "rep.rp_start <= '$db_end' AND rep.rp_end >= '$db_start' AND rep.rp_end <= '$db_end'";
        $ands = array();

        // Create the SQL elements from the properties
        if ($this->cal > 0) {
            $ands[] = ' cal.cal_id = ' . $this->cal;
        }
        if ($this->eid != '') {
            $ands[] = " ev.id = '" . DB_escapeString($this->eid) . "'";
        }
        if ($this->rp_id > 0) {
            $ands[] = ' rep.rp_id = ' . $this->rp_id;
        }
        if ($this->ical > -1) {
            $ands[] = ' cal.cal_ena_ical = ' . $this->ical;
        }
        if ($this->cat > 0) {
            //$opt_select .= ', cat.name AS cat_name';
            $ands[] = " (l.cid = '{$this->cat}' AND cat.status = 1) ";
            $cat_join = "LEFT JOIN {$_TABLES['evlist_lookup']} l ON l.eid = ev.id " .
                "LEFT JOIN {$_TABLES['evlist_categories']} cat ON cat.id = l.cid ";
        }
        if ($this->show_upcoming) {
            $ands[] = ' ev.show_upcoming = 1 AND cal.cal_show_upcoming = 1 ';
            // Alters the date range based on the setting for upcoming
            // events.
            switch ($_EV_CONF['event_passing']) {
            case 1:     // include if start time has not passed
                $dt_sql = "rep.rp_start >= '" . $_EV_CONF['_now']->toMySQL(true) . "'";
                break;
            case 2:     // include if start date has not passed
                $dt_sql = "rep.rp_start >= '{$_EV_CONF['_today']}'";
                break;
            case 3:     // include if end time has not passed
                $dt_sql = "rep.rp_end >= '" . $_EV_CONF['_now']->toMySQL(true) . "'";
                break;
            case 4:     // include if end date has not passed
                $dt_sql = "rep.rp_end >= '{$_EV_CONF['_today']}'";
                break;
            }
            // Always limit to events starting before the specified end date
            $dt_sql .= " AND rep.rp_start <= '{$this->end}'";
            $dt_sql = ' (' . $dt_sql . ') ';
        }
        if ($this->status < Status::ALL) {
            // Limit by event status if requested
            $ands[] = " rep.rp_status = {$this->status} ";
        }

        // By default, get all fields that the caller could possibly want.  If
        // a selection option is specified, then that is used instead.  It's up
        // to the caller to request the value properly, including table prefix.
        if ($this->selection == '') {
            $this->selection = "rep.*, det.*, cal.*, ev.* $opt_select";
        }
        if (!empty($ands)) {
            $ands = ' AND ' . implode(' AND ', $ands);
        } else {
            $ands = '';
        }

        // All the "*" queries may be ineffecient, but we need to read all
        // fields that might be wanted by whoever calls this function
        $sql = "SELECT {$this->selection}
            FROM {$_TABLES['evlist_repeat']} rep
            LEFT JOIN {$_TABLES['evlist_events']} ev
                ON ev.id = rep.rp_ev_id
            LEFT JOIN {$_TABLES['evlist_detail']} det
                ON det.det_id = rep.rp_det_id
            LEFT JOIN {$_TABLES['evlist_calendars']} cal
                ON cal.cal_id = ev.cal_id
            $cat_join
            WHERE (cal.cal_status = 1 OR cal.cal_status IS NULL)
            AND ($dt_sql)
            $ands
            $cat_status " .
            COM_getPermSQL('AND', 0, 2, 'ev') . ' ' .
            COM_getPermSQL('AND', 0, 2, 'cal') .
            " ORDER BY $orderby {$this->order}";
        if ($this->limit > 0) {
            if ($this->page > 1) {
                $sql .= ' LIMIT ' . (($this->page - 1) * $this->limit) . ',' . $this->limit;
            } else {
                // page 1 or 0, no starting offset
                $sql .= " LIMIT {$this->limit}";
            }
        }
        return $sql;
    }


    /**
     * Get all events that fall within a range.
     * This is in functions.inc so it can be used by the feed update
     * functions without having to load evlist_functions.inc.php.
     *
     * @return array           Array of matching events, keyed by date
     */
    public function getEvents()
    {
        global $_EV_CONF, $_USER;

        $sql = $this->getSql();
        $key = md5($sql);
        $events = Cache::get($key);

        if (is_null($events)) {     // not found in cache, read from DB
            $events = array();
            $result = DB_query($sql, 1);
            if ($result && !DB_error()) {
                while ($A = DB_fetchArray($result, false))  {
                    if (!isset($events[$A['rp_date_start']])) {
                        $events[$A['rp_date_start']] = array();
                    }
                    $A['options'] = @unserialize($A['options']);
                    $A['rec_data'] = @unserialize($A['rec_data']);
                    // Set a valid foreground and background color
                    if (empty($A['fgcolor'])) {
                        $A['fgcolor'] = 'inherit';
                    }
                    if (empty($A['bgcolor'])) {
                        $A['bgcolor'] = 'inherit';
                    }
                    if ($A['rp_date_start'] == $A['rp_date_end']) {
                        // Single-day event just gets added to the array
                        $events[$A['rp_date_start']][] = $A;
                    } else {
                        // Multi-day events get a record for each day up to the event end
                        // or limit, whichever comes first
                        $end_date = min($A['rp_date_end'], $this->end);
                        $newdate = max($A['rp_date_start'], $this->start);
                        while ($newdate <= $end_date) {
                            if (!isset($events[$newdate]))
                                $events[$newdate] = array();
                            $events[$newdate][] = $A;
                            list($y, $m, $d) = explode('-', $newdate);
                            $newdate = DateFunc::nextDay($d, $m, $y);
                        }
                    }
                }   // while
            }
            Cache::set($key, $events, array('events', 'calendars'));
        }
        return $events;
    }

}
