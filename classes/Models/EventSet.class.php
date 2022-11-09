<?php
/**
 * Class to handle retrieving event sets.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.8
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Models;
use glFusion\Database\Database;
use glFusion\Log\Log;
use Evlist\DateFunc;
use Evlist\Cache;
use Evlist\Config;


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

    /** Nonzero if showing events for centerblock only.
     * @var integer */
    private $show_cb = 0;

    /** Limit the number of events returned, default is all.
     * @var integer */
    private $limit = 0;

    /** Page number to show, based on the limit. Default is first page.
     * @var integer */
    private $page = 1;

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
     * @var array */
    private $selection = array();

    /** Extra fields to return.
     * @var string */
    private $extra_fields = array();

    /** Group by for results.
     * @var string */
    //private $grp_by = 'rep.rp_id';

    /** Showing upcoming events? TODO, duplicate of show_upcoming?
     * @var string */
    private $upcoming = false;

    /** Set required status, default is "enabled".
     * @var integer */
    private $status = Status::ENABLED;

    /** Optionally override the user making the query. Default = current user.
     * @var integer */
    private $uid = 0;


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
     * Set the flag to indicate this search is for a centerblock.
     *
     * @param   boolean $flag   True = show only centerblock-eligible events
     * @return  object  $this
     */
    public function withCenterblock(bool $flag) : self
    {
        $this->show_cb = $flag ? 1 : 0;
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
    public function withActiveOnly(bool $val=true) : self
    {
        return $this->withStatus(Status::ENABLED);
    }


    /**
     * Set the fields to be selected in the query.
     *
     * @param   array   $selection  Array of fields to retrieve
     * @return  object  $this
     */
    public function withSelection(array $selection) : self
    {
        $this->selection = $selection;
        return $this;
    }


    /**
     * Add additional database fields to retrieve.
     *
     * @param   array   $flds   Array of field names
     * @return  object  $this
     */
    public function withFields(array $flds) : self
    {
        $this->extra_fields = $flds;
        return $this;
    }


    /**
     * Set field used to detect unique events.
     *
     * @param   boolean $flag   True for event ID, False for repeat ID
     * @return  object  $this
     */
    public function withUnique(bool $flag) : self
    {
        $this->grp_by = $flag ? 'ev.id' : 'rep.rp_id';
        return $this;
    }


    /**
     * Set the user ID making the query.
     * Normally the current user, but for feeds use the anonymous user ID
     *
     * @param   integer $uid    User ID for forcing access check
     * @return  object  $this
     */
    public function withUid(int $uid) : self
    {
        $this->uid = (int)$uid;
        return $this;
    }


    /**
     * Create the SQL query to get all events that fall within a range.
     *
     * @return string          SQL query to retrieve events
     */
    public function getQueryBuilder() : \Doctrine\DBAL\Query\QueryBuilder
    {
        global $_TABLES, $_CONF, $_USER;

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();

        $today = $_CONF['_now']->format('Y-m-d', true);
        // Set starting and ending dates if not set.
        if ($this->start == '') {
            $this->start = $today;
        }
        if ($this->end == '') {
            $this->withEnd($this->start);
        }

        // Split up the date parts and validate
        list($y, $m, $d) = explode('-', $this->start);
        if (!DateFunc::isValidDate($d, $m, $y)){
            $this->start = $today;
        }
        list($y, $m, $d) = explode('-', $this->end);
        if (!DateFunc::isValidDate($d, $m, $y)) {
            $this->end = $this->start;
        }

        // By default, get all fields that the caller could possibly want. If
        // a selection option is specified, then that is used instead. It's up
        // to the caller to request the value properly, including table prefix.
        if (empty($this->selection)) {
            $qb->select('ev.*', 'rep.*', 'det.*', 'cal.bgcolor', 'cal.fgcolor', 'cal.cal_icon');
        } else {
            foreach ($this->selection as $fld) {
                $qb->addSelect($fld);
            }
        }
        if (!empty($this->extra_fields)) {
            foreach ($this->extra_fields as $fld) {
                $qb->addSelect($fld);
            }
        }
        $qb->from($_TABLES['evlist_repeat'], 'rep')
           ->leftJoin('rep', $_TABLES['evlist_events'], 'ev', 'ev.id = rep.rp_ev_id')
           ->leftJoin('rep', $_TABLES['evlist_detail'], 'det', 'det.det_id = rep.rp_det_id')
           ->leftJoin('ev', $_TABLES['evlist_calendars'], 'cal', 'cal.cal_id = ev.cal_id')
           ->where('cal.cal_status = 1 OR cal.cal_status IS NULL')
           ->andWhere($db->getPermSql('', $this->uid, 2, 'ev'))
           ->andWhere($db->getPermSql('', $this->uid, 2, 'cal'))
           ->orderBy('rep.rp_start', 'ASC');
        if ($this->limit > 0) {
            if ($this->page > 1) {
                $qb->setFirstResult(($this->page - 1) * $this->limit);
            }
            $qb->setMaxResults($this->limit);
        }

        // Create the SQL elements from the properties
        if ($this->cal > 0) {
            $qb->andWhere('cal.cal_id = :cal_id')
               ->setParameter('cal_id', $this->cal, Database::INTEGER);
        }
        if ($this->eid != '') {
            $qb->andWhere('ev.id = :eid')
               ->setParameter('eid', $this->eid, Database::STRING);
        }
        if ($this->rp_id > 0) {
            $qb->andWhere('rep.rp_id = :rp_id')
               ->setParameter('rp_id', $this->rp_id, Database::INTEGER);
        }
        if ($this->ical > -1) {
            $qb->andWhere('cal.cal_ena_ical = :ena_ical')
               ->setParameter('ena_ical', $this->ical, Database::INTEGER);
        }
        if ($this->cat > 0) {
            $qb->andWhere('l.cid = :cat_id AND cat.status = 1')
               ->setParameter('cat_id', $this->cat, Database::INTEGER)
               ->leftJoin('ev', $_TABLES['evlist_lookup'], 'l', 'l.eid = ev.id');
        }
        if ($this->show_upcoming) {
            $qb->andWhere('ev.show_upcoming = 1 AND cal.cal_show_upcoming = 1');
            // Alters the date range based on the setting for upcoming
            // events.
            switch (Config::get('event_passing')) {
            case TimeRange::START_TIME_PASSED:  // include if start time has not passed
                $qb->andWhere('rep.rp_start >= :rp_start')
                   ->setParameter('rp_start', $_CONF['_now']->toMySQL(true), Database::STRING);
                break;
            case TimeRange::START_DATE_PASSED:  // include if start date has not passed
                $qb->andWhere('rep.rp_start >= :rp_start')
                   ->setParameter('rp_start', $today, Database::STRING);
                break;
            case TimeRange::END_TIME_PASSED:    // include if end time has not passed
                $qb->andWhere('rep.rp_end >= :rp_end')
                   ->setParameter('rp_end', $_CONF['_now']->toMySQL(true), Database::STRING);
                break;
            case TimeRange::END_DATE_PASSED:    // include if end date has not passed
                $qb->andWhere('rep.rp_end >= :rp_end')
                   ->setParameter('rp_end', $today, Database::STRING);
                break;
            }
            // Always limit to events starting before the specified end date.
            $qb->andWhere('rep.rp_start <= :end')
               ->setParameter('end', $this->end, Database::STRING);
        } else {
            $qb->andWhere('rep.rp_start <= :end')
               ->andWhere('rep.rp_end >= :start')
               ->setParameter('end', $this->end)
               ->setParameter('start', $this->start);
        }
        if ($this->show_cb) {
            // Showing only centerblock items, limit the calendar selection.
            $qb->andWhere('cal.cal_show_cb = 1');
        }
        if ($this->status < Status::ALL) {
            // Limit by event status if requested
            $qb->andWhere('rep.rp_status = :status')
               ->setParameter('status', $this->status, Database::INTEGER);
        }
        //var_dump($qb->getSQL());die;

        return $qb;
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
        global $_USER;

        $qb = $this->getQueryBuilder();
        $key = md5(json_encode($qb->getParameters()));
        $events = Cache::get($key);

        if (is_null($events)) {     // not found in cache, read from DB
            $events = array();
            try {
                $stmt = $qb->execute();
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $stmt = false;
            }
            if ($stmt) {
                while ($A = $stmt->fetchAssociative()) {
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
                    if (
                        $A['rp_date_start'] == $A['rp_date_end'] ||
                        ($this->ical == 1 && $A['allday'])
                    ) {
                        // Single-day repeats and allday repeats for ical feeds
                        // just get added to the array.
                        $events[$A['rp_date_start']][] = $A;
                    } else {
                        // Multi-day repeats get a record for each day up to the event end
                        // or limit, whichever comes first.  For timed repeats on ical feeds,
                        // each instance gets a new record with the start and end
                        // dates overridden.
                        $end_date = min($A['rp_date_end'], $this->end);
                        $newdate = max($A['rp_date_start'], $this->start);
                        $count = 0;
                        $rp_id = $A['rp_id'];
                        while ($newdate <= $end_date) {
                            if ($this->ical == 1 && !$A['allday']) {
                                // Override the id and dates for non-allday ical events
                                $A['rp_id'] = $rp_id . '.' . $count++;
                                $A['rp_date_start'] = $newdate;
                                $A['rp_date_end'] = $newdate;
                                $A['rp_start'] = $newdate . ' ' . $A['rp_time_start1'];
                                $A['rp_end'] = $newdate . ' ' . $A['rp_time_end1'];
                            }
                            if (!array_key_exists($newdate, $events)) {
                                $events[$newdate] = array();
                            }
                            $events[$newdate][] = $A;
                            list($y, $m, $d) = explode('-', $newdate);
                            $newdate = DateFunc::nextDay($d, $m, $y)->format('Y-m-d');
                        }
                    }
                }   // while
            }
            Cache::set($key, $events, array('events', 'calendars'));
        }
        return $events;
    }

}
