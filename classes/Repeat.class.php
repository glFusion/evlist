<?php
/**
 * Class to manage event repeats or single instances for the EvList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;
use Evlist\Models\Status;


/**
 * Class for events.
 * @package evlist
 */
class Repeat
{
    /** Event record ID.
     * @var string */
    private $ev_id = '';

    /** Repeat record ID.
     * @var integer */
    private $rp_id = 0;

    /** Detail record ID.
     * @var integer */
    private $det_id = 0;

    /** User ID.
     * @var integer */
    private $uid = 0;

    /** Starting date.
     * @var string */
    private $date_start = '';

    /** Ending date.
     * @var string */
    private $date_end = '';

    /** Timezone ID.
     * @var string */
    private $tzid = '';

    /** First starting time.
     * @var string */
    private $time_start1 = '00:00:00';

    /** First ending time.
     * @var string */
    private $time_end1 = '23:59:59';

    /** Second starting time, for split events.
     * @var string */
    private $time_start2 = '00:00:00';

    /** Second ending time, for split events.
     * @var string */
    private $time_end2 = '23:59:59';

    /** First starting date/time object.
     * @var object */
    private $dtStart1 = NULL;

    /** First ending date/time object.
     * @var object */
    private $dtEnd1 = NULL;

    /** Second starting date/time object, for split events.
     * @var object */
    private $dtStart2 = NULL;

    /** Second ending date/time object, for split events.
     * @var object */
    private $dtEnd2 = NULL;

    /** Status (enabled, disabled, cancelled).
     * @var integer */
    private $rp_status = 1;

    /** Associated event.
     * @var object */
    private $Event = NULL;

    /** Associated event detail (title, location, summary, etc.
     * @var object */
    private $Detail = NULL;

    /** Indicate if admin access is granted to this event/repeat.
     * @var boolean */
    private $isAdmin = false;

    /** Array of error messages when saving.
     * @var array */
    private $Errors = array();

    /** Query string used in search.
     * @var string */
    //private $_qs = '';

    /** Template name for rendering the event view.
     * @var string */
    //private $_tpl = '';

    /** Comment mode used for event display.
     * @var string */
    //private $_cmtmode = 'nested';

    /** Comment ordering for event display.
     * @var string */
    //private $_cmtorder = 'ASC';


    /**
     *  Constructor.
     *  Reads in the specified event repeat, if $rp_id is set.
     *  If $id is zero, then a new entry is being created.
     *
     *  @param integer $rp_id   Optional repeat ID
     */
    public function __construct($rp_id=0)
    {
        global $_USER, $_CONF;

        if ($rp_id > 0) {
            $this->rp_id = (int)$rp_id;
            if (!$this->Read()) {
                $this->rp_id = 0;
            } else {
                // This gets used a few places, so save on function calls.
                $this->isAdmin = plugin_ismoderator_evlist();
            }
        }

        // this gets used a few times, might as well sanitize it here
        $this->uid = (int)$_USER['uid'];
    }


    /**
     * Get an instance of a repeat
     * Saves instances in a static array to speed multiple calls.
     *
     * @param   integer $rp_id  Repeat ID
     * @return  object          Repeat object
     */
    public static function getInstance($rp_id)
    {
        static $repeats = array();
        if (!isset($repeats[$rp_id])) {
            $key = 'repeat_' . $rp_id;
            $repeats[$rp_id] = Cache::get($key);
            if ($repeats[$rp_id] === NULL) {
                $repeats[$rp_id] = new self($rp_id);
                $tags = array(
                    'events',
                    'repeats',
                    'event_' . $repeats[$rp_id]->getEventID(),
                );
                Cache::set($key, $repeats[$rp_id], $tags);
            }
        }
        return $repeats[$rp_id];
    }


    /**
     * Create a repeat from an array of values, normally from a DB record.
     *
     * @param   array   $A      Array of key=>value pairs
     * @param   bool    $fromDB True if from a DB record
     * @return  object  Repeat object
     */
    public static function fromArray($A, $fromDB=true)
    {
        $retval = new self;
        $retval->setVars($A, $fromDB);
        return $retval;
    }


    /**
     * Check if the current user is an administrator.
     *
     * @return  boolean     True for administrators, False for regular users
     */
    public function isAdmin()
    {
        return $this->isAdmin ? true : false;
    }


    /**
     * Set the first starting date/time object.
     *
     * @param   string  $dt_tm  MySQL-formatted datetime string
     * @return  object  $this
     */
    public function setDateStart1($dt_tm)
    {
        global $_CONF;
        $this->dtStart1 = new \Date($dt_tm, $_CONF['timezone']);
        return $this;
    }


    /**
     * Set the first ending date/time object.
     *
     * @param   string  $dt_tm  MySQL-formatted datetime string
     * @return  object  $this
     */
    public function setDateEnd1($dt_tm)
    {
        global $_CONF;
        $this->dtEnd1 = new \Date($dt_tm, $_CONF['timezone']);
        return $this;
    }


    /**
     * Set the starting date string.
     *
     * @param   string  $dt     Date as YYYY-MM-DD
     * @return  self
     */
    public function setDateStart($dt)
    {
        $this->date_start = $dt;
        return $this;
    }


    /**
     * Set the ending date string.
     *
     * @param   string  $dt     Date as YYYY-MM-DD
     * @return  self
     */
    public function setDateEnd($dt)
    {
        $this->date_end = $dt;
        return $this;
    }


    /**
     * Set the first starting time string.
     *
     * @param   string  $tm     Time as HH:mm
     * @return  self
     */
    public function setTimeStart1($tm)
    {
        if (!empty($tm)) {
            $this->time_start1 = $tm;
        }
        return $this;
    }


    /**
     * Set the first ending time string.
     *
     * @param   string  $tm     Time as HH:mm
     * @return  self
     */
    public function setTimeEnd1($tm)
    {
        if (!empty($tm)) {
            $this->time_end1 = $tm;
        }
        return $this;
    }


    /**
     * Set the second starting time string.
     *
     * @param   string  $tm     Time as HH:mm
     * @return  self
     */
    public function setTimeStart2($tm)
    {
        if (!empty($tm)) {
            $this->time_start2 = $tm;
        }
        return $this;
    }


    /**
     * Set the second ending time string.
     *
     * @param   string  $tm     Time as HH:mm
     * @return  self
     */
    public function setTimeEnd2($tm)
    {
        if (!empty($tm)) {
            $this->time_end2 = $tm;
        }
        return $this;
    }


    /**
     * Set the second starting date/time object.
     *
     * @param   string  $dt_tm  MySQL-formatted datetime string
     * @return  object  $this
     */
    private function setDateStart2($dt_tm)
    {
        global $_CONF;
        $this->dtStart2 = new \Date($dt_tm, $_CONF['timezone']);
    }


    /**
     * Set the second ending date/time object.
     *
     * @param   string  $dt_tm  MySQL-formatted datetime string
     * @return  object  $this
     */
    private function setDateEnd2($dt_tm)
    {
        global $_CONF;
        $this->dtEnd2 = new \Date($dt_tm, $_CONF['timezone']);
    }


    /**
     * Sets all variables to the matching values from $rows.
     *
     * @param   array   $row        Array of values, from DB or $_POST
     * @param   boolean $fromDB     True if read from DB, false if from $_POST
     */
    public function setVars($row, $fromDB=false)
    {
        if (!is_array($row)) return;

        $fields = array(
            'ev_id', 'det_id',
            'date_start', 'date_end',
            'time_start1', 'time_end1',
            'time_start2', 'time_end2',
        );
        foreach ($fields as $field) {
            if (isset($row['rp_' . $field])) {
                $this->$field = $row['rp_' . $field];
            }
        }
        if (isset($row['rp_id'])) {
            $this->rp_id = $row['rp_id'];
        }

        // Join or split the date values as needed
        if (!$fromDB) {     // Coming from the form
            $this->date_start = $row['date_start1'];
            $this->date_end = $row['date_end1'];

            // Ignore time entries & set to all day if flagged as such
            if (isset($row['allday']) && $row['allday'] == '1') {
                $this->time_start1 = '00:00:00';
                $this->time_end1 = '23:59:59';
                $this->time_start2 = NULL;
                $this->time_end2 = NULL;
            } else {
                $this->time_start1 = $row['time_start1'];
                $this->time_end1 = $row['time_end1'];
                if (isset($row['split']) && $row['split'] == '1') {
                    $this->time_start2 = $row['time_start2'];
                    $this->time_end2 = $row['time_end2'];
                } else {
                    $this->time_start2 = '00:00:00';
                    $this->time_end2   = '00:00:00';
                }
            }
        }
        $this->setDateStart1($this->date_start . ' ' . $this->time_start1);
        $this->setDateEnd1($this->date_start . ' ' . $this->time_end1);
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @param   integer $rp_id  Optional ID.  Current ID is used if zero.
     * @return  boolean     True if a record was read, False on failure.
     */
    public function Read($rp_id = 0)
    {
        global $_TABLES;

        if ($rp_id != 0) {
            $this->rp_id = (int)$rp_id;
        }

        $sql = "SELECT *
                FROM {$_TABLES['evlist_repeat']}
                WHERE rp_id='{$this->rp_id}'";
        $result = DB_query($sql);
        if (!$result || DB_numRows($result) != 1) {
            return false;
        } else {
            $A = DB_fetchArray($result, false);
            $this->ev_id        = $A['rp_ev_id'];
            $this->det_id       = (int)$A['rp_det_id'];
            $this->date_start   = $A['rp_date_start'];
            $this->date_end     = $A['rp_date_end'];
            $this->time_start1  = $A['rp_time_start1'];
            $this->time_end1    = $A['rp_time_end1'];
            $this->time_start2  = $A['rp_time_start2'];
            $this->time_end2    = $A['rp_time_end2'];
            $this->rp_status    = (int)$A['rp_status'];
            // This is used by Reminders so make sure it's set:
            $this->setDateStart1($this->date_start . ' ' . $this->time_start1);
            $this->setDateEnd1($this->date_end . ' ' . $this->time_end1);
            if ($this->time_start2 > '00:00:00') {
                $this->setDateStart2($this->date_end . ' ' . $this->time_start2);
            }
            if ($this->time_end2 > '00:00:00') {
                $this->setDateEnd2($this->date_end . ' ' . $this->time_end2);
            }
            $this->Event = Event::getInstance($this->ev_id, $this->det_id);
            $this->tzid = $this->Event->getTZID();
            return true;
        }
    }


    /**
     * Edit a single repeat.
     *
     * @see     Event::Edit()
     * @param   integer $rp_id      ID of instance to edit
     * @param   string  $edit_type  Type of repeat (repeat or futurerepeat)
     * @return  string      Editing form
     */
    public function Edit(int $rp_id = 0, ?string $edit_type='repeat') : string
    {
        if ($rp_id > 0) {
            $this->Read($rp_id);
        }

        // Set any errors into the Event object to be displayed
        // with the editing form.
        return $this->Event
                    ->setErrors($this->Errors)
                    ->Edit(
                        $this->ev_id,
                        $this->rp_id,
                        'save' . $edit_type
                    );
    }


    /**
     * Save this occurance info to the database.
     * Only updates can be performed since the original record must have
     * been created by the Event class.
     *
     * The incoming $A parameter will contain all the event info, so it can
     * be used to populate both the Detail and Repeat records.
     *
     * @param   array   $A      Optional array of values from $_POST
     * @return  boolean         True if no errors, False otherwise
     */
    public function Save(?array $A = NULL) : bool
    {
        global $_TABLES;

        if (is_array($A)) {
            $this->setVars($A);
        }

        if ($this->rp_id == 0) {
            return false;
        }

        /* If a form was submitted, check what to do with the submitted detail,
         * if it is different from the existing one. If no detail update, then
         * no action is needed.
         *
         * - Saving a single instance:
         *   - If the detail record is used only once, update it in-place.
	     *   - If the detail record is used by 2 or more repeats, create a new one.
         * - Saving all future repeats:
         *   - If the detail record is not used by any repeats prior to the one
         *     being saved, update it in-place.
         *   - If the detail record is used by more events than just all future ones,
         *     create a new detail.
         */
        if (is_array($A)) {
            // A form was submitted, check if the values are different.
            $Detail = $this->getDetail();
            $newDetail = Detail::fromArray($A);

            if (!$Detail->Matches($newDetail)) {
                $this->Detail = $newDetail;
                if ($A['save_type'] == 'saverepeat') {
                    // Saving a single occurrence.
                    if (self::countWithDetail($this->det_id) < 2) {
                        // If this repeat is the only one using this detail
                        // record, just update it in place.
                        $this->Detail->setID($this->det_id)->Save();
                    } else {
                        // If used by more than one repeat, create a new detail
                        // record and set its ID in this object.
                        $this->det_id = $this->Detail->Save();
                    }
                } elseif ($A['save_type'] == 'savefuturerepeat') {
                    // Saving all future repeats, see if the current detail
                    // is used by any repeats prior to this one.
                    if (self::countWithDetail($this->det_id, $this->date_end) < 2) {
                        // If this detail is not used by any prior repeats, then
                        // update the detail in place
                        $this->Detail->setID($this->det_id)->Save();
                    } else {
                        // New detail is used by prior repeats, creat a new one
                        // and propagate it to all future repeats.
                        $this->det_id = (int)$this->newDetail->Save();
                        $sql = "UPDATE {$_TABLES['evlist_repeat']}
                            SET rp_det_id = '{$this->det_id}'
                            WHERE rp_date_start >= '{$this->date_start}'
                            AND rp_ev_id = '{$this->ev_id}'";
                        DB_query($sql);
                    }
                }
            }
        }

        if (!$this->isValidRecord()) {
            return false;
        }

        $date_start = DB_escapeString($this->date_start);
        $date_end = DB_escapeString($this->date_end);
        $time_start1 = DB_escapeString($this->time_start1);
        $time_start2 = DB_escapeString($this->time_start2);
        $time_end1 = DB_escapeString($this->time_end1);
        $time_end2 = DB_escapeString($this->time_end2);
        if (substr($time_end2, 0, 5) != '00:00') {
            $t_end = $time_end2;
        } else {
            $t_end = $time_end1;
        }
        $sql = "UPDATE {$_TABLES['evlist_repeat']} SET
            rp_date_start = '$date_start',
            rp_date_end= '$date_end',
            rp_time_start1 = '$time_start1',
            rp_time_end1 = '$time_end1',
            rp_time_start2 = '$time_start2',
            rp_time_end2 = '$time_end2',
            rp_start = '$date_start $time_start1',
            rp_end = '$date_end $t_end',
            rp_det_id='" . (int)$this->det_id . "',
            rp_revision = rp_revision + 1,
            rp_status = " . (int)$this->rp_status . "
            WHERE rp_id = {$this->rp_id}";
        DB_query($sql);
        Cache::clear();
        PLG_itemSaved($this->rp_id, 'evlist');
        COM_rdfUpToDateCheck('evlist', 'events', $this->rp_id);
        return true;
    }


    /**
     * Delete the current instance from the database.
     *
     * @return  boolean     True on success, False on failure
     */
    public function Delete()
    {
        global $_TABLES, $_EV_CONF;

        if ($this->rp_id < 1 || !$this->Event->canEdit()) {
            // non-existent repeat ID or no edit access
            return false;
        }

        // Check if the related detail record is used by any other occurrences.
        // Only check if this is not using the master detail record. In that
        // case, leave the detail record alone as it will be needed for new
        // occurrences.
        if (
            $this->det_id != $this->Event->getDetailID() &&
            self::countWithDetail($this->det_id) == 1
        ) {
            Detail::getInstance($this->det_id)->Delete();
        }

        if ($_EV_CONF['purge_cancelled_days'] < 1) {
            DB_delete($_TABLES['evlist_repeat'], 'rp_id', (int)$this->rp_id);
        } else {
            $sql = "UPDATE {$_TABLES['evlist_repeat']}
                SET rp_status = " . Status::CANCELLED .
                ", rp_revision = rp_revision + 1
                WHERE rp_id = {$this->rp_id}";
            DB_query($sql);
        }
        Cache::clear();
        return true;
    }


    /**
     * Update field values for all occurrances of an event.
     *
     * @param   string  $ev_id  Event ID
     * @param   array   $args   Fieldname=>value pairs to update
     * @param   string  $ands   Additional WHERE conditions as "AND ... AND ..."
     */
    public static function updateEvent($ev_id, $args=array(), $ands='')
    {
        global $_TABLES;

        $sql_args = array();
        foreach ($args as $key=>$val) {
            if (is_string($val)) {
                $val = DB_escapeString($val);
            } elseif (is_integer($val)) {
                $val = (int)$val;
            }
            $sql_args[] = "$key = '$val'";
        }
        if (!empty($sql_args)) {
            $sql_args = implode(', ', $sql_args) . ',';
        } else {
            $sql_args = '';
        }
        $sql = "UPDATE {$_TABLES['evlist_repeat']} SET
            $sql_args
            rp_revision = rp_revision + 1
            WHERE rp_ev_id = '" . DB_escapeString($ev_id) . "' $ands";
        DB_query($sql);
        //Cache::clear('repeats', 'event_' . $ev_id);
    }


    /**
     * Update the status for all occurrances of an event.
     *
     * @param   string  $ev_id  Event ID
     * @param   integer $status New status value
     * @param   string  $ands   Additional WHERE conditions as "AND ... AND ..."
     */
    public static function updateEventStatus($ev_id, $status, $ands='')
    {
        global $_TABLES;

        $status = (int)$status;
        $Ev = Event::getInstance($ev_id);
        $master_det_id = (int)$Ev->getDetailID();
        if ($master_det_id > 0) {       // protect against invalid records
            Detail::updateEventStatus(
                $ev_id,
                $status,
                " AND det_status <> $status AND det_id <> " . $Ev->getDetailID()
            );
        }
        self::updateEvent(
            $ev_id,
            array('rp_status'=>$status),
            " AND rp_status <> $status $ands"
        );
    }


    /**
     * Delete cancelled events that have not been updated in some time.
     */
    public static function purgeCancelled()
    {
        global $_TABLES, $_EV_CONF;

        $days = (int)$_EV_CONF['purge_cancelled_days'];
        $sql = "DELETE FROM {$_TABLES['evlist_repeat']}
                WHERE rp_status = " . Status::CANCELLED .
                " AND rp_last_mod < DATE_SUB(NOW(), INTERVAL $days DAY)";
        DB_query($sql);
    }


    /**
     * Display the detail page for the event occurrence.
     *
     * @return  string      HTML for the page.
     */
    public function Render()
    {
        global $_CONF, $_USER, $_EV_CONF, $LANG_EVLIST, $LANG_WEEK,
                $LANG_LOCALE, $_SYSTEM, $LANG_EVLIST_HELP;
        echo "Repeat::Render DEPRECATED";die;

        $retval = '';
        $url = '';
        $location = '';
        $street = '';
        $city = '';
        $province = '';
        $country = '';
        $postal = '';
        $name = '';
        $email = '';
        $phone = '';

        if ($this->rp_id == 0) {
            return EVLIST_alertMessage($LANG_EVLIST['access_denied']);
        }

        $T = new \Template(EVLIST_PI_PATH . '/templates/');
        $T->set_file(array(
            'event' => $this->_tpl . '.thtml',
            //'editlinks' => 'edit_links.thtml',
            'datetime' => 'date_time.thtml',
            'address' => 'address.thtml',
            'contact' => 'contact.thtml',
        ) );

        USES_lib_social();
        $permalink = COM_buildUrl(EVLIST_URL . '/view.php?&rid=0&eid=' . $this->Event->getID());
        $ss = $this->getShareIcons($permalink);

        $Detail = $this->getDetail();
        // If plain text then replace newlines with <br> tags
        $summary = $Detail->getSummary();
        $full_description = $Detail->getDscp();
        $location = $Detail->getLocation();
        if ($this->Event->getPostMode() == '1') {       //plaintext
            $summary = nl2br($summary);
            $full_description = nl2br($summary);
            $location = nl2br($summary);
        }
        $title = $Detail->getTitle();
        if ($this->Event->getPostmode() != 'plaintext') {
            $summary = PLG_replaceTags($summary);
            $full_description = PLG_replaceTags($full_description);
            $location = $location != '' ? PLG_replaceTags($location) : '';
        }
        if ($this->_qs != '') {
            $title = COM_highlightQuery($title, $this->_qs);
            if (!empty($summary)) {
                $summary  = COM_highlightQuery($summary, $this->_qs);
            }
            if (!empty($full_description)) {
                $full_description = COM_highlightQuery($full_description, $this->_qs);
            }
            if (!empty($location)) {
                $location = COM_highlightQuery($location, $this->_qs);
            }
        }
        $this->setDateStart1($this->date_start . ' ' . $this->time_start1);
        $this->setDateEnd1($this->date_end . ' ' . $this->time_end1);
        $date_start = $this->dtStart1->format($_CONF['dateonly'], true);
        $date_end = $this->dtEnd1->format($_CONF['dateonly'], true);
        $time_start1 = '';
        $time_end1 = '';
        $time_start2 = '';
        $time_end2 = '';
        if ($date_end == $date_start) $date_end = '';
        if ($this->Event->isAllDay()) {
            $allday = '<br />' . $LANG_EVLIST['all_day_event'];
        } else {
            $allday = '';
            if ($this->time_start1 != '') {
                $time_start1 = $this->dtStart1->format($_CONF['timeonly'], true);
                $time_end1 =  $this->dtEnd1->format($_CONF['timeonly'], true);
            }
            //$time_period = $time_start . $time_end;
            if ($this->Event->isSplit()) {
                $this->setDateStart2($this->date_start . ' ' . $this->time_start2);
                $this->setDateEnd2($this->date_start . ' ' . $this->time_end2);
                $time_start2 = $this->dtStart2->format($_CONF['timeonly'], true);
                $time_end2 = $this->dtEnd2->format($_CONF['timeonly'], true);
            }
        }

        // Get the link to more info. If it's an external link, target a
        // new browser window.
        $url = $Detail->getUrl();
        if (!empty($url)) {
            $url = str_replace('%site_url%', $_CONF['site_url'], $url);
            if (strncasecmp($_CONF['site_url'], $url, strlen($_CONF['site_url']))) {
                $target = 'target="_blank"';
            } else {
                $target = '';       // Internal url
            }
            $more_info_link = sprintf($LANG_EVLIST['click_here'], $url, $target);
        } else {
            $more_info_link = '';
        }
        $street = $Detail->getStreet();
        $city = $Detail->getCity();
        $province = $Detail->getProvince();
        $postal = $Detail->getPostal();
        $country = $Detail->getCountry();

        // Now get the text description of the recurring interval, if any
        if (
            $this->Event->getRecurring() &&
            $this->Event->getRecData()['type'] < EV_RECUR_DATES
        ) {
            $rec_data = $this->Event->getRecData();
            $rec_string = $LANG_EVLIST['recur_freq_txt'] . ' ' .
                $this->Event->RecurDscp();
            //$Days = new Models\Days;
            switch ($rec_data['type']) {
            case EV_RECUR_WEEKLY:        // sequential days
                $weekdays = array();
                if (is_array($rec_data['listdays'])) {
                    foreach ($rec_data['listdays'] as $daynum) {
                        $weekdays[] = $LANG_WEEK[$daynum];
                        //$weekdays[] = $Days[$daynum];
                    }
                    $days_text = implode(', ', $weekdays);
                } else {
                    $days_text = '';
                }
                $rec_string .= ' '.sprintf($LANG_EVLIST['on_days'], $days_text);
                break;
            case EV_RECUR_DOM:
                $days = array();
                foreach($rec_data['interval'] as $key=>$day) {
                    $days[] = $LANG_EVLIST['rec_intervals'][$day];
                }
                $days_text = implode(', ', $days) . ' ' .
                    //$Days[$rec_data['weekday']];
                    $LANG_WEEK[$rec_data['weekday']];
                $rec_string .= ' ' . sprintf($LANG_EVLIST['on_the_days'],
                    $days_text);
                break;
            }
            if (
                $this->Event->getRecData()['stop'] != '' &&
                $this->Event->getRecData()['stop'] < EV_MAX_DATE
            ) {
                $stop_date = new \Date($this->Event->getRecData()['stop'], $this->tzid);
                $rec_string .= '<br />' . sprintf(
                    $LANG_EVLIST['recur_stop_desc'],
                    $stop_date->format($_CONF['dateonly'], true)
                );
            }
        } else {
            $rec_string = '';
        }

        $T->set_var(array(
            'lang_locale' => $_CONF['iso_lang'],
            'charset'   => COM_getCharset(),
            'direction' => (empty($LANG_DIRECTION) ? 'ltr' : $LANG_DIRECTION),
            'page_site_splitter' => empty($title) ? '' : ' - ',
            'pi_url'    => EVLIST_URL,
            'webcal_url' => EVLIST_URL,
            'rp_id'     => $this->rp_id,
            'ev_id'     => $this->ev_id,
            'title' => $title,
            'summary' => $summary,
            'full_description' => $full_description,
            'can_edit' => $this->Event->canEdit() ? 'true' : '',
            'start_time1' => $time_start1,
            'end_time1' => $time_end1,
            'start_time2' => $time_start2,
            'end_time2' => $time_end2,
            'start_date' => $date_start,
            'end_date' => $date_end,
            'start_datetime1' => $date_start . $time_start1,
            'end_datetime1' => $date_end . $time_end2,
            'allday_event' => $this->Event->isAllDay() ? 'true' : '',
            'is_recurring' => $this->Event->getRecurring(),
            'can_subscribe' => $this->Event->getCalendar()->isIcalEnabled(),
            'recurring_event'    => $rec_string,
            'owner_id'      => $this->Event->getOwnerID(),
            'cal_name'      => $this->Event->getCalendar()->getName(),
            'cal_id'        => $this->Event->getCalendarID(),
            'site_name'     => $_CONF['site_name'],
            'site_slogan'   => $_CONF['site_slogan'],
            'more_info_link' => $more_info_link,
            'show_tz'   => $this->tzid == 'local' ? '' : 'true',
            'timezone'  => $this->tzid,
            'tz_offset' => sprintf('%+d', $this->dtStart1->getOffsetFromGMT(true)),
            'social_icons'  => $ss,
            'icon_remove' => Icon::getIcon('delete'),
            'icon_edit' => Icon::getIcon('edit'),
            'icon_copy' => Icon::getIcon('copy'),
            'icon_subscribe' => Icon::getIcon('subscribe'),
            'icon_print' => Icon::getIcon('print'),
            'lang_prt_title' => $LANG_EVLIST_HELP['prt_tickets_btn'],
        ) );

        $outputHandle = \outputHandler::getInstance();
        $outputHandle->addLink('canonical', $permalink, HEADER_PRIO_NORMAL);
        $outputHandle->addMeta(
            'property',
            'og:site_name',
            $_CONF['site_name'],
            HEADER_PRIO_NORMAL
        );
        $outputHandle->addMeta(
            'property',
            'og:locale',
            isset($LANG_LOCALE) ? $LANG_LOCALE : 'en_US',
            HEADER_PRIO_NORMAL
        );
        $outputHandle->addMeta(
            'property',
            'og:title',
            $Detail->getTitle(),
            HEADER_PRIO_NORMAL
        );
        $outputHandle->addMeta(
            'property',
            'og:type',
            'event',
            HEADER_PRIO_NORMAL
        );
        $outputHandle->addMeta(
            'property',
            'og:url',
            $permalink,
            HEADER_PRIO_NORMAL
        );
        $outputHandle->AddMeta(
            'name',
            'description',
            $Detail->getSummary(),
            HEADER_PRIO_NORMAL
        );
        $outputHandle->AddMeta(
            'property',
            'og:description',
            $Detail->getSummary(),
            HEADER_PRIO_NORMAL
        );

        // Show the user comments. Moderators and event owners can delete comments
        if ($this->Event->commentsAllowed()) {
            USES_lib_comment();
            $T->set_var(
                'usercomments',
                CMT_userComments(
                    $this->rp_id,
                    $Detail->getTitle(),
                    'evlist',
                    $this->_cmtorder,
                    $this->_cmtmode,
                    0,
                    1,
                    false,
                    (plugin_ismoderator_evlist() || $this->Event->getOwnerID() == $_USER['uid']),
                    $this->Event->commentsEnabled()
                )
            );
        }

        if (
            $_EV_CONF['enable_rsvp'] == 1 &&
            $this->Event->getOption('use_rsvp') > 0
        ) {
            if (time() > $this->dtStart1->toUnix() - ((int)$this->Event->getOption('rsvp_cutoff') * 86400)) {
                $past_cutoff = true;
            } else {
                $past_cutoff = false;
            }
            if (COM_isAnonUser()) {
                // Just show a must-log-in message
                $T->set_var('login_to_register', 'true');
            } elseif (!$past_cutoff) {
                $num_free_tickets = $this->isRegistered(0, true);
                $total_tickets = $this->isRegistered(0, false);
                if ($num_free_tickets > 0) {
                    // If the user is already registered for any free tickets,
                    // show the cancel link
                    $T->set_var(array(
                        'unregister_link' => 'true',
                        'num_free_reg' => $num_free_tickets,
                    ) );
                }

                // Show the registration link
                if (
                    (
                        $this->Event->getOption('max_rsvp') == 0 ||
                        $this->Event->getOption('rsvp_waitlist') == 1 ||
                        $this->Event->getOption('max_rsvp') > $this->TotalRegistrations()
                    )
                    &&
                    (
                        $this->Event->getOption('max_user_rsvp') == 0 ||
                        $total_tickets < $this->Event->getOption('max_user_rsvp')
                    )
                ) {
                    if ($this->Event->getOption('rsvp_comments')) {
                        $T->set_var('rsvp_comments',  true);
                        $prompts = $this->Event->getOption('rsvp_cmt_prompts');
                        if (empty($prompts)) {
                            $prompts = array($LANG_EVLIST['comment']);
                        }
                        $T->set_block('event', 'rsvpComments', 'rsvpC');
                        foreach ($prompts as $prompt) {
                            $T->set_var('rsvp_cmt_prompt', $prompt);
                            $T->parse('rsvpC', 'rsvpComments', true);
                        }
                    }
                    $Ticks = TicketType::GetTicketTypes();
                    if (!empty($Ticks)) {
                        if ($this->Event->getOption('max_user_rsvp') > 0) {
                            $T->set_block('event', 'tickCntBlk', 'tcBlk');
                            $T->set_var('register_multi', true);
                            $avail_tickets = $this->Event->getOption('max_user_rsvp') - $total_tickets;
                            for ($i = 1; $i <= $avail_tickets; $i++) {
                                $T->set_var('tick_cnt', $i);
                                $T->parse('tcBlk', 'tickCntBlk', true);
                            }
                        } else {
                            $T->set_var('register_unltd', 'true');
                        }
                        $T->set_block('event', 'tickTypeBlk', 'tBlk');
                        foreach ($this->Event->getOption('tickets') as $tick_id=>$data) {
                            // Skip ticket types that may have been disabled
                            if (!array_key_exists($tick_id, $Ticks)) continue;
                            $status = LGLIB_invokeService(
                                'shop', 'formatAmount',
                                array('amount' => $data['fee']),
                                $pp_fmt_amt, $svc_msg
                            );
                            $fmt_amt = $status == PLG_RET_OK ?
                                    $pp_fmt_amt : COM_numberFormat($data['fee'], 2);
                            $T->set_var(array(
                                'tick_type' => $tick_id,
                                'tick_descr' => $Ticks[$tick_id]->getDscp(),
                                'tick_fee' => $data['fee'] > 0 ? $fmt_amt : $LANG_EVLIST['free_caps'],
                            ) );
                            $T->parse('tBlk', 'tickTypeBlk', true);
                        }
                        $T->set_var(array(
                            'register_link' => 'true',
                            'ticket_types_multi' => count($this->Event->getOption('tickets')) > 1 ? 'true' : '',
                        ) );
                    }
                }
            }

            // Show the user signups on the event page if authorized.
            if (SEC_inGroup($this->Event->getOption('rsvp_view_grp'))) {
                $T->set_var('user_signups', Ticket::userList_RSVP($this->rp_id));
            }

            // If ticket printing is enabled for this event, see if the
            // current user has any tickets to print.
            if ($this->Event->getOption('rsvp_print') > 0) {
                $paid = $this->Event->getOption('rsvp_print') == 1 ? 'paid' : '';
                $tickets = Ticket::GetTickets($this->ev_id, $this->rp_id, $this->uid, $paid);
                if (count($tickets) > 0) {
                    $tick_url = EVLIST_URL . "/tickets.php?ev_id={$this->ev_id}&rp_id={$this->rp_id}";
                    $T->set_var(array(
                        'have_tickets'  => 'true',
                        'ticket_url' => COM_buildUrl($tick_url),
                        'tic_rp_id' => $this->Event->getOption('use_rsvp') == EV_RSVP_REPEAT ? $this->rp_id : 0,
                    ) );
                }
            }

        }   // if enable_rsvp

        if (!empty($date_start) || !empty($date_end)) {
            $T->parse('datetime_info', 'datetime');
        }

        // Get coordinates for easy use in Weather and Locator blocks
        $lat = $Detail->getLatitude();
        $lng = $Detail->getLongitude();

        // Only process the location block if at least one element exists.
        // Don't want an empty block showing.
        if (
            !empty($location) ||
            !empty($street) ||
            !empty($city) ||
            !empty($province) ||
            !empty($postal)
        ) {
            /*$T->set_var(array(
                'location' => $location,
                'street' => $street,
                'city' => $city,
                'province' => $province,
                'country' => $country,
                'postal' => $postal,
            ) );
            $T->parse('address_info', 'address');*/

            // Get info from the Weather plugin, if configured and available
            // There has to be at least some location data for this to work.
            if ($_EV_CONF['use_weather']) {
                // Try coordinates first, if present
                if (!empty($lat) && !empty($lng)) {
                    $loc = array(
                        'parts' => array(
                            'lat' => $lat,
                            'lng' => $lng,
                        ),
                    );
                } else {
                    // The postal code works best, but not internationally.
                    // Try the regular address first.
                    if (!empty($city) && !empty($province)) {
                        $loc = array(
                            'city' => $city,
                            'province' => $province,
                            'country' => $country,
                        );
                    }
                    if (!empty($postal)) {
                        $loc['postal'] = $postal;
                    }
                }
                $weather = '';

                if (!empty($loc)) {
                    // Location info was found, get the weather
                    $s = LGLIB_invokeService(
                        'weather', 'embed',
                        array(
                            'loc' => $loc,
                        ),
                        $weather, $svc_msg
                    );
                    if (!empty($weather)) {
                        // Weather info was found
                        $T->set_var('weather', $weather);
                    }
                }
            }
        }

        // Get a map from the Locator plugin, if configured and available
        if ($_EV_CONF['use_locator'] == 1 && !empty($lat) && !empty($lng)) {
            $args = array(
                'lat'   => $lat,
                'lng'   => $lng,
                'text'  => $Detail->formatAddress(),
            );
            $status = LGLIB_invokeService('locator', 'getMap', $args, $map, $svc_msg);
            if ($status == PLG_RET_OK) {
                $T->set_var(array(
                    'map'   => $map,
                    'lat'   => EVLIST_coord2str($Detail->getLatitude()),
                    'lng'   => EVLIST_coord2str($Detail->getLongitude()),
                ) );
            }
        }

        //put contact info here: contact, email, phone#
        $name = $Detail->getContact() != '' ?
            COM_applyFilter($Detail->getContact()) : '';
        if ($Detail->getEmail() != '') {
            $email = COM_applyFilter($Detail->getEmail());
            $email = EVLIST_obfuscate($email);
        } else {
            $email = '';
        }
        $phone = $Detail->getPhone() != '' ?
            COM_applyFilter($Detail->getPhone()) : '';

        if (!empty($name) || !empty($email) || !empty($phone)) {
            $T->set_var(array(
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
            ) );
            $T->parse('contact_info', 'contact');
        }

        // TODO: Is the range needed?
        if (!empty($range)) {
            $andrange = '&amp;range=' . $range;
        } else {
            $andrange = '&amp;range=2';
        }

        if (!empty($cat)) {
            $andcat = '&amp;cat=' . $cat;
        } else {
            $andcat = '';
        }

        $cats = $this->Event->getCategories();
        $catcount = count($cats);
        if ($catcount > 0) {
            $catlinks = array();
            for ($i = 0; $i < $catcount; $i++) {
                $catname = str_replace(' ', '&nbsp;', $cats[$i]['name']);
                $catlinks[] = '<a href="' .
                EVLIST_URL . '/index.php?view=list' . $andrange .
                '&cat=' . $cats[$i]['id'] .
                '">' . $catname . '</a>&nbsp;';
            }
            $catlink = implode(' | ', $catlinks);
            $T->set_var('category_link', $catlink, true);
        }

        //  reminders must be enabled globally first and then per event in
        //  order to be active
        if (!isset($_EV_CONF['reminder_days'])) {
            $_EV_CONF['reminder_days'] = 1;
        }

        $reminder_msg = '';
        if (
            !COM_isAnonUser() &&
            $_EV_CONF['enable_reminders'] == '1' &&
            $this->Event->remindersEnabled() &&
            time() < strtotime(
                "-".$_EV_CONF['reminder_days']." days",
                strtotime($this->date_start)
            )
        ) {
            //form will not appear within XX days of scheduled event.
            $show_reminders = true;

            // Let's see if we have already asked for a reminder...
            if ($_USER['uid'] > 1) {
                $Reminder = new Reminder($this->rp_id, $_USER['uid']);
                if (!$Reminder->isNew()) {
                    $reminder_msg = sprintf($LANG_EVLIST['you_are_subscribed'], $Reminder->getDays());
                }
            }
        } else {
            $show_reminders = false;
        }

        if ($this->Event->getOption('contactlink') && $this->Event->getOwnerID() > 1) {
            $ownerlink = $_CONF['site_url'] . '/profiles.php?uid=' .
                    $this->Event->getOwnerID();
            $ownerlink = sprintf($LANG_EVLIST['contact_us'], $ownerlink);
        } else {
            $ownerlink = '';
        }
        $T->set_var(array(
            'owner_link' => $ownerlink,
            'reminder_msg' => $reminder_msg,
            'reminder_email' => isset($_USER['email']) ? $_USER['email'] : '',
            'notice' => (int)$_EV_CONF['reminder_days'],
            'rp_id' => $this->rp_id,
            'eid' => $this->ev_id,
            'show_reminderform' => $show_reminders ? 'true' : '',
            'address_info' =>$Detail->formatAddress(),
        ) );

        $tick_types = TicketType::GetTicketTypes();
        $T->set_block('event', 'registerBlock', 'rBlock');
        if (is_array($this->Event->getOption('tickets'))) {
            foreach ($this->Event->getOption('tickets') as $tic_type=>$info) {
                // Skip ticket types that may have been disabled
                if (!array_key_exists($tic_type, $tick_types)) continue;
                $T->set_var(array(
                    'tic_description' => $tick_types[$tic_type]->getDscp(),
                    'tic_fee' => COM_numberFormat($info['fee'], 2),
                ) );
                $T->parse('rBlock', 'registerBlock', true);
            }
        }

        // Show the "manage reservations" link to the event owner
        if (
            $_EV_CONF['enable_rsvp'] == 1 &&
            $this->Event->getOption('use_rsvp') > 0
        ) {
            if ($this->isAdmin || $this->Event->isOwner()) {
                $T->set_var(array(
                    'admin_rsvp'    => Ticket::adminList_RSVP($this->rp_id),
                    'rsvp_count'    => $this->TotalRegistrations(),
                ) );
            } elseif (SEC_inGroup($this->Event->getOption('rsvp_view_grp'))) {
                $T->set_var(array(
                    'admin_rsvp'    => Ticket::userList_RSVP($this->rp_id),
                    'rsvp_count'    => $this->TotalRegistrations(),
                ) );
            }
        }

        $T->set_var('adblock', PLG_displayAdBlock('evlist_event', 0));
        $T->parse ('output','event');
        $retval .= $T->finish($T->get_var('output'));

        // Apply output filters
        $retval = PLG_outputFilter($retval);

        return $retval;
    }


    /**
     * Register a user for an event.
     *
     * @param   integer $num_attendees  Number of attendees, default 1
     * @param   integer $tick_type      Id of ticket type
     * @param   integer $uid    User ID to register, 0 for current user
     * @param   string  $cmt    User-supplied comment for the ticket
     * @return  integer         Message code, zero for success
     */
    public function Register($num_attendees = 1, $tick_type = 0, $uid = 0, $cmt='')
    {
        global $_TABLES, $_USER, $_EV_CONF, $LANG_EVLIST;

        if ($_EV_CONF['enable_rsvp'] != 1) {
            return 0;
        }

        // Make sure that registrations are enabled and that the current user
        // has access to this event.  If $uid > 0, then this is an admin
        // registering another user, don't check access
        if (
            $this->Event->getOption('use_rsvp') == 0 ||
            ($uid == 0 && !$this->Event->hasAccess(2))
        ) {
            COM_setMsg($LANG_EVLIST['messages'][20]);
            return 20;
        } elseif ($this->Event->getOption('use_rsvp') == 1) {
            // Registering for entire event, all repeats
            $rp_id = 0;
        } else {
            // Registering for a single occurance
            $rp_id = $this->rp_id;
        }

        if (!isset($this->Event->getOption('tickets')[$tick_type])) {
            COM_setMsg($LANG_EVLIST['messages'][24]);
            return 24;
        }

        $uid = $uid == 0 ? (int)$_USER['uid'] : (int)$uid;
        $num_attendees = (int)$num_attendees;
        $fee = (float)$this->Event->getOption('tickets')[$tick_type]['fee'];
        $prompts = $this->Event->getOption('rsvp_cmt_prompts', array());
        if (empty($prompts)) {
            $prompts = array('Comment');
        }
        $comments = array();
        if (!is_array($cmt) || empty($cmt)) {
            $cmt = array();
        }
        foreach ($prompts as $key=>$prompt) {
            if (isset($cmt[$key]) && !empty($cmt[$key])) {
                $comments[$prompt] = $cmt[$key];
            } else {
                $comments[$prompt] = '';
            }
        }

        // Check that the current user isn't already registered
        // TODO: Allow registrations up to max count, or to waitlist
        //if ($this->isRegistered()) {
        //    return 21;
        //}
        // Check that the event isn't already full, or that
        // waitlisting is disabled
        $total_reg = $this->TotalRegistrations();
        $new_total = $total_reg + $num_attendees;
        $max_rsvp = $this->Event->getOption('max_rsvp');
        if ($max_rsvp > 0 && $max_rsvp < $new_total) {
            if ($this->Event->getOption('rsvp_waitlist') == 0 || $fee > 0) {
                // Event is full, no waiting list. Can't waitlist paid tickets.
                LGLIB_storeMessage($LANG_EVLIST['messages'][22]);
                return 22;
            } else {
                // Set message indicating the waiting list and continue to register
                $waitlist = $new_total - $max_rsvp;
                if ($waitlist >= $num_attendees) {
                    // All tickets are waitlisted
                    $str = $LANG_EVLIST['all'];
                } else {
                    $waitlist = $new_total - $max_rsvp;
                    $str = $waitlist;
                }
                LGLIB_storeMessage($LANG_EVLIST['messages']['22'] . ' ' .
                    sprintf($LANG_EVLIST['messages'][27], $str));
            }
        }

        if ($fee > 0) {
            // add tickets to the shopping cart. Tickets will be flagged
            // as unpaid.
            $this->AddToCart($tick_type, $num_attendees);
            COM_setMsg($LANG_EVLIST['messages']['24']);
            $status = LGLIB_invokeService(
                'shop', 'getURL',
                array('type'=>'checkout'),
                $url,
                $msg
            );
            if ($status == PLG_RET_OK) {
                LGLIB_storeMessage(
                    sprintf($LANG_EVLIST['messages']['26'],
                    $url
                ), '', true);
            }
        } else {
            LGLIB_storeMessage($LANG_EVLIST['messages'][24]);
        }

        // for free tickets, just create the ticket records
        $TickType = new TicketType($tick_type);
        if ($TickType->isEventPass()) {
            $t_rp_id = 0;
        } else {
            $t_rp_id = $this->rp_id;
        }
        $wl = 0;
        for ($i = 1; $i <= $num_attendees; $i++) {
            if ($max_rsvp > 0) {
                $wl = ($total_reg + $i) <= $max_rsvp ? 0 : 1;
            }
            $Tic = new Ticket;
            $Tic->withEventId($this->Event->getID())
                ->withUid($uid)
                ->withTypeId($tick_type)
                ->withRepeatId($t_rp_id)
                ->withFee($fee)
                ->setWaitlisted($wl)
                ->withComments($comments)
                ->Create();
            //Ticket::Create($this->Event->getID(), $tick_type, $t_rp_id, $fee, $uid, $wl, $comments);
        }
        return 0;
    }


    /**
     * Cancel a user's registration for an event.
     * Delete the newer records first, to preserve waitlist position for the user.
     *
     * @param   integer $uid    Optional User ID to remove, 0 for current user
     * @param   integer $num    Number of reservations to cancel, 0 for all
     */
    public function CancelRegistration($uid = 0, $num = 0)
    {
        global $_TABLES, $_USER, $_EV_CONF;

        if ($_EV_CONF['enable_rsvp'] != 1) return false;

        $num = (int)$num;
        $uid = $uid == 0 ? (int)$_USER['uid'] : (int)$uid;
        $sql = "DELETE FROM {$_TABLES['evlist_tickets']} WHERE
                ev_id = '" . $this->Event->getID() . "'
                AND uid = $uid
                AND fee = 0
                ORDER BY waitlist,dt DESC";
        if ($num > 0) $sql .= " LIMIT $num";
        DB_query($sql, 1);

        if (
            $this->Event->getOption('max_rsvp') > 0 &&
            $this->Event->getOption('rsvp_waitlist') == 1
        ) {
            // for free tickets, just create the ticket records
            Ticket::resetWaitlist($this->Event->getoption('max_rsvp'), $this->ev_id, $this->rp_id);
        }
        return DB_error() ? false : true;
    }


    /**
     * Determine if the user is registered for this event/repeat.
     *
     * @param   integer $uid    Optional user ID, current user by default
     * @param   boolean $free_only  True to get count of only free tickets
     * @return  mixed   Number of registrations, or False if rsvp disabled
     */
    public function isRegistered($uid = 0, $free_only = false)
    {
        global $_TABLES, $_USER, $_EV_CONF;

        static $counter = array();
        if ($_EV_CONF['enable_rsvp'] != 1) return false;

        $uid = $uid == 0 ? (int)$_USER['uid'] : (int)$uid;
        $key = $free_only ? 1 : 0;

        if (!isset($counter[$key])) {
            $counter[$key] = array();
        }
        if (!isset($counter[$key][$uid])) {
            $sql = "SELECT count(*) AS c FROM {$_TABLES['evlist_tickets']}
                    WHERE ev_id = '{$this->Event->getID()}'
                    AND (rp_id = 0 OR rp_id = {$this->rp_id})
                    AND uid = $uid";
            // check for fee = 0 if free_only is set
            if ($key == 1) $sql .= ' AND fee = 0';
            //echo $sql;die;
            $res = DB_query($sql);
            $A = DB_fetchArray($res, false);
            $counter[$key][$uid] = isset($A['c']) ? (int)$A['c'] : 0;
        }
        return $counter[$key][$uid];
    }


    /**
     * Get the total number of users registered for this event/repeat.
     * If provided, the $rp_id parameter will be considered an event ID or
     * a repeat ID, depending on the event's registration option.
     *
     * @return  integer         Total registrations for this instance
     */
    public function TotalRegistrations()
    {
        global $_TABLES, $_EV_CONF;

        static $count = array();

        if (!isset($count[$this->rp_id])) {
            $count[$this->rp_id] = 0;
            if ($_EV_CONF['enable_rsvp'] != 1) {
                // noop
            } elseif ($this->Event->getOption('use_rsvp') == EV_RSVP_EVENT) {
                $count[$this->rp_id] = (int)DB_count($_TABLES['evlist_tickets'], 'ev_id', $this->ev_id);
            } else {
                $sql = "SELECT count(*) AS cnt FROM {$_TABLES['evlist_tickets']} WHERE
                    ev_id = '" . DB_escapeString($this->ev_id) . "' AND (
                        rp_id = {$this->rp_id} OR rp_id = 0
                    )";
                $res = DB_query($sql);
                if ($res && DB_numRows($res) == 1) {
                    $A = DB_fetchArray($res, false);
                    $count[$this->rp_id] = (int)$A['cnt'];
                }
            }
        }
        return $count[$this->rp_id];
    }


    /**
     * Get all the users registered for this event.
     *
     * @return  array   Array of uid's and dates, sorted by date
     */
    public function Registrations()
    {
        global $_TABLES, $_EV_CONF;

        static $retval = NULL;

        if ($retval === NULL) {
            $retval = array();

            // Check that registrations are enabled
            if ($_EV_CONF['enable_rsvp'] == 1 &&
                    $this->Event->getOption('use_rsvp') != 0) {

                $sql = "SELECT uid, dt_reg
                        FROM {$_TABLES['evlist_tickets']}
                        WHERE ev_id = '{$this->ev_id}' ";

                if ($this->Event->getOption('use_rsvp') == EV_RSVP_REPEAT) {
                    $sql .= " AND rp_id = '{$this->rp_id}' ";
                }
                $sql .= ' ORDER BY dt_reg ASC';
                $res = DB_query($sql, 1);
                if ($res) {
                    while ($A = DB_fetchArray($res, false)) {
                        $retval[] = $A;
                    }
                }
            }
        }
        return $retval;
    }


    /**
     * Delete the current and all future occurrences of an event.
     * First, gather and delete all the detail records for custom instances.
     * Then, delete all the future repeat records. Finally, update the stop
     * date for the main event.
     */
    public function deleteFuture()
    {
        global $_TABLES, $_EV_CONF;

        if ($this->rp_id < 1 || !$this->Event->canEdit()) {
            // non-existent repeat ID or no edit access
            return false;
        }

        if ($this->date_start <= $this->Event->getStartDate1()) {
            // This is easy- we're deleting ALL repeats, so also
            // delete the event
            $this->Event->Delete();
        } else {
            // Find all custom detail records and delete them.
            $sql = "SELECT DISTINCT rp_det_id
                    FROM {$_TABLES['evlist_repeat']}
                    WHERE rp_ev_id='{$this->ev_id}'
                    AND rp_date_start >= '{$this->date_start}'
                    AND rp_det_id <> '{$this->Event->getDetailID()}'";
            //echo $sql;die;
            $res = DB_query($sql);
            $details = array();
            while ($A = DB_fetchArray($res, false)) {
                $details[] = (int)$A['rp_det_id'];
            }
            if (!empty($details)) {
                $detail_str = implode(',', $details);
                if ($_EV_CONF['purge_cancelled_days'] < 1) {
                    $sql = "DELETE FROM {$_TABLES['evlist_detail']}
                        WHERE det_id IN ($detail_str)";
                } else {
                    $sql = "UPDATE {$_TABLES['evlist_detail']}
                        SET det_status = " . Status::CANCELLED .
                        " WHERE det_id IN ($detail_str)";
                }
                //echo $sql;die;
                DB_query($sql);
            }

            // Now cancel or delete the repeats
            if ($_EV_CONF['purge_cancelled_days'] < 1) {
                $sql = "DELETE FROM {$_TABLES['evlist_repeat']}
                    WHERE rp_ev_id='{$this->ev_id}'
                    AND rp_date_start >= '{$this->date_start}'";
            } else {
                $sql = "UPDATE {$_TABLES['evlist_repeat']}
                    SET rp_status = " . Status::CANCELLED .
                    ", rp_revision = rp_revision + 1
                    WHERE rp_ev_id='{$this->ev_id}'
                    AND rp_date_start >= '{$this->date_start}'";
            }
            //echo $sql;die;
            DB_query($sql);

            // Now adjust the recurring stop date for the event.
            $new_stop = DB_getItem(
                $_TABLES['evlist_repeat'],
                'rp_date_start',
                "rp_ev_id='{$this->ev_id}' AND rp_status < " .
                    Status::CANCELLED . " ORDER BY rp_date_start DESC LIMIT 1"
            );
            if (!empty($new_stop)) {
                $this->Event->updateRecData('stop', $new_stop);
            }
            Cache::clear();
        }
    }   // function DeleteFuture()


    /**
     * Updates all future repeats from this one.
     * Sets the times to new values, but leaves the dates alone.
     *
     * @return  object  $this
     */
    public function updateFuture()
    {
        global $_TABLES;

        $time_end1 = DB_escapeString($this->time_end1);
        $time_end2 = DB_escapeString($this->time_end2);
        $t_end = $this->Event->isSplit() ? $time_end2 : $time_end1;

        $sql = "UPDATE {$_TABLES['evlist_repeat']} SET
            rp_det_id = '{$this->getDetailID()}',
            rp_time_start1 = '{$this->time_start1}',
            rp_time_end1 = '{$this->time_end1}',
            rp_time_start2 = '{$this->time_start2}',
            rp_time_end2 = '{$this->time_end2}',
            rp_start = CONCAT(rp_date_start, ' ', '{$this->time_start1}'),
            rp_end = CONCAT(rp_date_end, ' ', '{$t_end}'),
            rp_status = {$this->rp_status},
            rp_revision = rp_revision + 1
            WHERE rp_date_start >= '{$this->getDateStart1()->toMySQL(true)}'
            AND rp_ev_id = '{$this->getEventID()}'";
            //rp_show_upcoming = {$this->rp_show_upcoming}
        DB_query($sql);
        return $this;
    }


    /**
     * Get one or all occurences of an event.
     * If $rp_id is zero, return all repeats. Otherwise return only the
     * requested one.
     *
     * @param   string  $ev_id  Event ID
     * @param   integer $rp_id  Repeat ID
     * @return  array       Array of occurrences
     */
    public static function getRepeats($ev_id, $rp_id=0, $limit = 0)
    {
        global $_TABLES;

        $repeats = array();
        $where = array();
        $ev_id = DB_escapeString($ev_id);
        $rp_id = (int)$rp_id;
        $limit = (int)$limit;

        $where = "rp_ev_id = '$ev_id'";
        if ($rp_id > 0) {
            $where .= " AND rp_id = $rp_id";
        }
        if (!empty($where)) {
            $sql = "SELECT * FROM {$_TABLES['evlist_repeat']} WHERE $where";
            if ($limit > 0) {
                $sql .= " LIMIT $limit";
            }
            $res = DB_query($sql, 1);
            while ($A = DB_fetchArray($res, false)) {
                $repeats[$A['rp_id']] = new Repeat();
                $repeats[$A['rp_id']]->setVars($A, true);
            }
        }
        return $repeats;
    }


    /**
     * Add the event fee to the shopping cart.
     * No checking is done here to see if it's paid, that must be done
     * by the caller.
     *
     * @param   integer $tick_type  Ticket type
     * @param   integer $qty        Quantity of tickets
     * @return  array           Array of cart vars
     */
    public function AddToCart($tick_type, $qty=1)
    {
        global $LANG_EVLIST, $_CONF;

        $TickType = new TicketType($tick_type);
        $fee = $this->Event->getOption('tickets')[$tick_type]['fee'];
        $rp_id = $TickType->isEventPass() ? 0 : $this->rp_id;

        $evCart = array(
            'item_number' => 'evlist:eventfee:' . $this->Event->getID() . '/' .
                    $tick_type . '/' . $rp_id,
            'item_name' => $TickType->getDscp() . ': ' . $LANG_EVLIST['fee'] . ' - ' .
                    $this->getDetail()->getTitle() . ' ' . $this->date_start.
                    ' ' . $this->time_start1,
            'short_description' => $TickType->getDscp() . ': ' .
                    $this->getDetail()->getTitle() . ' ' . $this->date_start .
                    ' ' . $this->time_start1,

            'amount' => number_format((float)$fee, 2, '.', ''),
            'quantity' => $qty,
            'extras' => array('shipping' => 0),
        );
        LGLIB_invokeService(
            'shop', 'addCartItem',
            $evCart,
            $output, $msg
        );
        return $evCart;
    }


    /**
     * Get the ID of the next upcoming instance of a given event.
     *
     * @param   string  $ev_id  Event ID
     * @return  integer         ID of the next instance of this event
     */
    public static function getUpcoming($ev_id)
    {
        global $_TABLES, $_CONF;

        $sql_date = $_CONF['_now']->toMySQL(true);
        return DB_getItem($_TABLES['evlist_repeat'], 'rp_id',
                "rp_ev_id = '" . DB_escapeString($ev_id) . "'
                    AND rp_end >= '$sql_date'
                ORDER BY rp_start ASC
                LIMIT 1");
    }


    /**
     * Get the ID of the last instance of a given event.
     * Used to find an instance to display for events that have passed.
     *
     * @param   string  $ev_id  Event ID
     * @return  integer         ID of the next instance of this event
     */
    public static function getLast($ev_id)
    {
        global $_TABLES;

        return DB_getItem($_TABLES['evlist_repeat'], 'rp_id',
                "rp_ev_id = '" . DB_escapeString($ev_id) . "'
                ORDER BY rp_end DESC
                LIMIT 1");
    }


    /**
     * Get the ID of the first instance of a given event.
     *
     * @param   string  $ev_id  Event ID
     * @return  integer         ID of the first instance of this event
     */
    public static function getFirst($ev_id)
    {
        global $_TABLES;

        return (int)DB_getItem(
            $_TABLES['evlist_repeat'],
            'rp_id',
            "rp_ev_id = '" . DB_escapeString($ev_id) . "'
                ORDER BY rp_start ASC
                LIMIT 1"
            );
    }


    /**
     * Get the nearest event, upcoming or past.
     * Try first to get the closest upcoming instance, then try for the
     * most recent past instance.
     * Used when sharing social links to retrieve the instance that's most
     * likely of interest.
     *
     * @uses    self::getLast()
     * @uses    self::getUpcoming()
     * @param   string  $ev_id  Event ID
     * @return  mixed       Instance ID, or False on an or not found
     */
    public static function getNearest($ev_id)
    {
        $rp_id = self::getUpcoming($ev_id);
        if ($rp_id === NULL) {
            $rp_id = self::getLast($ev_id);
        }
        return $rp_id;
    }


    /**
     * Get the social sharing icons.
     *
     * @param   string  $permalink  URL to the specific event occurrence
     * @return  string  HTML for social sharing icons
     */
    public function getShareIcons(?string $permalink=NULL)
    {
        if ($permalink === NULL) {
            $permalink = COM_buildUrl(
                EVLIST_URL . '/view.php?&rid=0&eid=' . $this->getEventID()
            );
        }
        if (version_compare(GVERSION, '2.0.0', '<')) {
            $ss = SOC_getShareIcons(
                $this->getDetail()->getTitle(),
                $this->getDetail()->getSummary(),
                $permalink
            );
        } else {
            $ss = \glFusion\Social\Social::getShareIcons(
                $this->getDetail()->getTitle(),
                $this->getDetail()->getSummary(),
                $permalink
            );
        }
        return $ss;
    }


    /**
     * Get the record ID of the recurrance.
     *
     * @return  integer     Record ID
     */
    public function getID()
    {
        return (int)$this->rp_id;
    }


    /**
     * Get the ID of the related event record.
     *
     * @return  string      Event record ID
     */
    public function getEventID()
    {
        return $this->ev_id;
    }


    public function getDetailID()
    {
        return (int)$this->det_id;
    }


    /**
     * Get the event opject related to this repeat.
     *
     * @return  object      Event object
     */
    public function getEvent()
    {
        if ($this->Event === NULL) {
            $this->Event = Event::getInstance($this->ev_id);
        }
        return $this->Event;
    }


    /**
     * Get the Detail object related to this repeat.
     *
     * @return  object      Detail object
     */
    public function getDetail() : object
    {
        /*if ($this->det_id == $this->getEvent()->getDetailID()) {
            $this->Detail = $this->getEvent()->getDetail();
    } else {*/
            $this->Detail = Detail::getInstance($this->det_id);
        //}
        return $this->Detail;
    }


    /**
     * Get the first starting date/time object.
     *
     * @return  object      Starting date object
     */
    public function getDateStart1() : object
    {
        return $this->dtStart1;
    }


    /**
     * Get the first starting date/time object.
     *
     * @return  object      Starting date object
     */
    public function getDateEnd1() : object
    {
        return $this->dtEnd1;
    }


    /**
     * Get the second starting date/time object, for split events.
     *
     * @return  object      Starting date object
     */
    public function getDateStart2() : object
    {
        return $this->dtStart2;
    }


    /**
     * Get the first starting date/time object, for split events.
     *
     * @return  object      Starting date object
     */
    public function getDateEnd2() : object
    {
        return $this->dtEnd2;
    }


    /**
     * Get the first starting time.
     *
     * @return  string      Starting time HH:MM:SS
     */
    public function getTimeStart1()
    {
        return $this->dtStart1->format('H:i:s', true);
    }


    /**
     * Get the first ending time.
     *
     * @return  string      Ending time HH:MM:SS
     */
    public function getTimeEnd1()
    {
        return $this->dtEnd1->format('H:i:s', true);
    }


    /**
     * Get the second starting time.
     *
     * @return  string      Starting time HH:MM:SS
     */
    public function getTimeStart2()
    {
        if ($this->dtStart2) {
            return $this->dtStart2->format('H:i:s', true);
        } else {
            return '';
        }
    }


    /**
     * Get the second ending time.
     *
     * @return  string      Ending time HH:MM:SS
     */
    public function getTimeEnd2()
    {
        if ($this->dtEnd2) {
            return $this->dtEnd2->format('H:i:s', true);
        } else {
            return '';
        }
    }


    /**
     * Check if this is a new record.
     * Used to validate the retrieval of an instance.
     *
     * @return  boolean     True if new, False if existing
     */
    public function isNew()
    {
        return $this->rp_id == 0;
    }


    /**
     * Set the query string received to highlight words in the event.
     *
     * @param   string  $qs     Query string
     * @return  object  $this
     */
    public function withQuery($qs)
    {
        $this->qs = $qs;
        return $this;
    }


    /**
     * Set the override to use a different template for the event.
     * The parameter should be the part of the template name following `event_`.
     *
     * @param   string  $tpl    Templage name (partial), empty to reset
     * @return  object  $this
     */
    public function withTemplate($tpl = '')
    {
        $this->_tpl = 'event';
        if ($tpl != '') {
            $this->_tpl .= '_' . $tpl;
        }
        return $this;
    }


    /**
     * Set the comment mode to use with the event display.
     *
     * @param   string  $cmtmode    Comment mode (nested, etc.)
     * @return  object  $this
     */
    public function withCommentMode($cmtmode)
    {
        $this->_cmtmode = $cmtmode;
        return $this;
    }


    /**
     * Set the comment ordering for event display.
     *
     * @param   string  $cmtorder   Comment order (ASC or DESC)
     * @return  object  $this
     */
    public function withCommentOrder($cmtorder)
    {
        $this->_cmtorder = $cmtorder;
        return $this;
    }


    /**
     * Set the status value explicitely.
     *
     * @param   int     $status     New status value
     * @return  self
     */
    public function setStatus($status)
    {
        $this->rp_status = (int)$status;
        return $this;
    }


    /**
     * Make sure the current user has access to view this event.
     *
     * @return  boolean     True if allowed, False if not
     */
    public function canView()
    {
        if (
            $this->getID() == 0 ||      // indicates an invalid record
            $this->rp_status != Status::ENABLED ||    // indicates disabled or cancelled
            !$this->getEvent()->hasAccess(2)    // no access to the event
        ) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Get all the repeats for an event ID, optionally limiting by dates.
     *
     * @param   string  $ev_id  Event ID
     * @param   string  $min_dt Starting date as YYYY-MM-DD
     * @param   string  @max_dt End date as YYYY-MM-DD
     * @return  array       Array of Repeat objects
     */
    public static function getByEvent($ev_id, $min_dt = '', $max_dt = '')
    {
        global $_TABLES;

        $retval = array();
        $sql = "SELECT * FROM {$_TABLES['evlist_repeat']}
            WHERE rp_ev_id = '" . DB_escapeString($ev_id) . "'";
        if ($min_dt != '') {
            $sql .= " AND rp_date_start >= '$min_dt'";
        }
        if ($max_dt != '') {
            $sql .= " AND rp_date_start <= '$max_dt'";
        }
        $sql .= " ORDER BY rp_date_start ASC";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $retval[$A['rp_date_start']] = new self;
            $retval[$A['rp_date_start']]->setVars($A, true);
        }
        return $retval;
    }


    /**
     * Check if the supplied schedule array matches this repeat.
     * Checks start & end dates and both start & end time pairs.
     *
     * @param   array   $schedule   Schedule to verify
     * @param   int     $status     Optional status to match as well
     * @return  bool    True if matching, False if any element doesn't
     */
    public function matchesSchedule(array $schedule, ?int $status) : bool
    {
        if (
            $schedule['dt_start'] != $this->date_start ||
            $schedule['dt_end'] != $this->date_end ||
            $schedule['tm_start1'] . ':00' != $this->time_start1 ||
            $schedule['tm_end1'] . ':00' != $this->time_end1 ||
            $schedule['tm_start2'] . ':00' != $this->time_start2 ||
            $schedule['tm_end2'] . ':00' != $this->time_end2
        ) {
            return false;
        }
        if ($status !== NULL && $this->rp_status != $status) {
            return false;
        }
        return true;
    }


    /**
     * Determines if the current record is valid.
     *
     * @return  boolean     True if ok, False when first test fails.
     */
    private function isValidRecord()
    {
        global $LANG_EVLIST;

        // Check that basic required fields are filled in.  We don't
        // check the event ID since that will be created automatically if
        // it is.
        if ($this->getDetail()->getTitle() == '') {
            $this->Errors[] = $LANG_EVLIST['err_missing_title'];
        }

        if (
            $this->date_start . ' ' . $this->time_start1 >
            $this->date_end . ' ' . $this->time_end1
        ) {
            $this->Errors[] = $LANG_EVLIST['err_times'];
        }

        /*if ($this->split == 1 && $this->date_start1 == $this->date_end1) {
            if (
                $this->date_start . ' ' . $this->time_start2 >
                $this->date_start . ' ' . $this->time_end2
            ) {
                $this->Errors[] = $LANG_EVLIST['err_times'];
            }
        }*/

        if (!empty($this->Errors)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Get the admin list of occurrences for a specific event.
     *
     * @return  string      HTML for admin list
     */
    public static function adminList(?string $ev_id='') : string
    {
        global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

        USES_lib_admin();

        $header_arr = array(
            array(
                'text' => $LANG_EVLIST['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
            ),
            /*array(
                'text' => $LANG_EVLIST['copy'],
                'field' => 'copy',
                'sort' => false,
                'align' => 'center',
            ),*/
            array(
                'text' => $LANG_EVLIST['id'],
                'field' => 'rp_id',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['title'],
                'field' => 'title',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['start_date'],
                'field' => 'rp_date_start',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['status'],
                'field' => 'rp_status',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
            ),
        );

        $defsort_arr = array(
            'field' => 'rp_date_start',
            'direction' => 'DESC',
        );
        $options = array(
            'chkdelete' => 'true',
            'chkfield' => 'rp_id',
            'chkname' => 'delrepeat',
        );
        $text_arr = array(
            'has_menu'     => true,
            'has_extras'   => true,
            'form_url'     => EVLIST_ADMIN_URL . "/index.php?repeats&eid=$ev_id",
            'help_url'     => '',
        );

        // Select distinct to get only one entry per event.  We can only edit/modify
        // events here, not repeats
        $sql = "SELECT rp.*, det.title
                FROM {$_TABLES['evlist_repeat']} rp
                LEFT JOIN {$_TABLES['evlist_detail']} det
                    ON det.det_id = rp.rp_det_id";
        if ($ev_id != '') {
            $sql .= " WHERE ev_id = '" . DB_escapeString($ev_id) . "'";
        }

        $query_arr = array(
            'table' => 'evlist_repeat',
            'sql' => $sql,
            'query_fields' => array(),
        );
        $filter = COM_createLink(
            $LANG_EVLIST['back_to_event'],
            EVLIST_ADMIN_URL . '/index.php?edit=event&amp;eid=' . $ev_id . '&from=ev_repeats'
        );

        $retval = ADMIN_list(
            'evlist_event_admin',
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr,
            $query_arr, $defsort_arr, $filter, '', $options
        );
        return $retval;
    }


    /**
     * Return the display value for a field in the admin list.
     *
     * @param   string  $fieldname  Name of the field
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Name-value pairs for all fields
     * @param   array   $icon_arr   Array of system icons
     * @return  string      HTML to display for the field
     */
    public static function getAdminField(string $fieldname, string $fieldvalue, array $A, array $icon_arr) : string
    {
        global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;
        static $del_icon = NULL;
        $retval = '';

        switch($fieldname) {
        case 'edit':
            $retval = FieldList::edit(array(
                'url' => EVLIST_URL . '/event.php?edit=repeat&eid=' .
                    $A['rp_ev_id'] . '&rp_id=' . $A['rp_id'] . '&from=ev_repeats',
                array(
                    'title' => $LANG_EVLIST['edit_event'],
                ),
            ) );
            break;

        case 'rp_status':
            $fieldvalue = (int)$fieldvalue;
            $retval = FieldList::select(array(
                'name' => 'status[' . $A['rp_id'] . ']',
                'onchange' => "EVLIST_updateStatus(this, 'repeat', '{$A['rp_id']}', '{$fieldvalue}', '" . EVLIST_ADMIN_URL . "');",
                'options' => array(
                    $LANG_EVLIST['enabled'] => array(
                        'value' => '1',
                        'selected' => (1 == $fieldvalue),
                    ),
                    $LANG_EVLIST['cancelled'] => array(
                        'value' => '2',
                        'selected' => (2 == $fieldvalue),
                    ),
                    $LANG_EVLIST['disabled'] => array(
                        'value' => '0',
                        'selected' => (0 == $fieldvalue),
                    ),
                ),
            ) );
            break;

        case 'delete':
            // Enabled events get cancelled, others get immediately deleted.
            $url = EVLIST_ADMIN_URL. "/index.php?rp_id={$A['rp_id']}&ev_id={$A['rp_ev_id']}";
            if ($A['rp_status'] == Status::ENABLED) {
                $url .= '&cxrepeat';
            } else {
                $url .= '&delcxrepeat';
            }
            if (isset($_REQUEST['cal_id'])) {
                $url .= '&cal_id=' . (int)$_REQUEST['cal_id'];
            }
            $retval = FieldList::delete(array(
                'delete_url' => $url,
                array(
                    'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_event']}');",
                    'title' => $LANG_ADMIN['delete'],
                    'class' => 'tooltip',
                ),
            ) );
            break;
        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }


    /**
     * Delete orphaned occurrence records that have no matching Event.
     */
    public static function cleanOrphans() : void
    {
        global $_TABLES;

        $sql = "DELETE FROM {$_TABLES['evlist_repeat']} r
            LEFT JOIN {$_TABLES['evlist_events']} e ON r.rp_ev_id = e.id
            WHERE e.id IS NULL";
        DB_query($sql);
    }


    /**
     * Get a count of the repeats using a detail record.
     *
     * @param   int     $det_id     Detail record ID
     * @param   string  $before     Optional cutoff to count only earlier repeats
     * @return  int     Count of instances using the detail record
     */
    public static function countWithDetail(int $det_id, ?string $before = '') : int
    {
        global $_TABLES;

        if ($before == '') {
            // Get a count from all instances
            $count = DB_count($_TABLES['evlist_repeat'], 'rp_det_id', (int)$det_id);
        } else {
            // Get a count of instances prior to the "before" date
            $before = DB_escapeString($before);
            $count = DB_getItem(
                $_TABLES['evlist_repeat'],
                'count(*)',
                "rp_det_id = $det_id AND rp_date_end < '$before'"
            );
        }
        return $count;
    }


}
