<?php
/**
 * Class to manage events for the EvList plugin.
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
use glFusion\Database\Database;
use glFusion\Log\Log;
use Evlist\Models\Status;
//use Evlist\Models\Intervals;
use Evlist\Models\RecurData;
use Evlist\Models\EventOptions;


/**
 * Class for event records.
 * @package evlist
 */
class Event
{
    const MIN_DATETIME  = '1970-01-01 00:00';
    const MAX_DATETIME  = '2037-12-31 23:59';
    const MIN_DATE      = '1970-01-01';
    const MAX_DATE      = '2037-12-31';
    const MIN_TIME      = '00:00';
    const MAX_TIME      = '23:59';

    const RP_EXTEND         = 1;    // Extending the series
    const RP_TRUNCATE       = 2;    // Remove instances at the end of the series
    const RP_NEWTIME        = 4;    // Update the time and allday flag in repeats
    const RP_RECUR2SINGLE   = 8;    // Was recurring, convert to single
    const RP_SINGLEEDIT     = 16;   // Single event changed date or time
    const RP_NEWSCHEDULE    = 64;   // Completely new schedule

    const CMT_ENABLED   = 0;
    const CMT_CLOSED    = 1;
    const CMT_DISABLED  = -1;


    /** Event record ID.
     * @var string */
    private $id = '';

    /** Owner user ID.
     * @var integer */
    private $owner_id = 2;

    /** Group ID.
     * @var integer */
    private $group_id = 13;

    /** Owner permission.
     * @var integer */
    private $perm_owner = 3;

    /** Group permission.
     * @var integer */
    private $perm_group = 2;

    /** Logged-in user permission.
     * @var integer */
    private $perm_members = 2;

    /** Anonymous permission.
     * @var integer */
    private $perm_anon = 2;

    /** Starting year 1.
     * @var integer */
    //private $startyear1 = 0;

    /** Starting month 1.
     * @var integer */
    //private $startmonth1 = 0;

    /** Starting day 1.
     * @var integer */
    //private $startday1 = 0;

    /** Starting year 2.
     * @var integer */
    //private $startyear2 = 0;

    /** Starting month 2.
     * @var integer */
    //private $startmonth2 = 0;

    /** Starting day 2.
     * @var integer */
    //private $startday2 = 0;

    /** Ending year 1.
     * @var integer */
    //private $endyear1 = 0;

    /** Ending month 1.
     * @var integer */
    //private $endmonth1 = 0;

    /** Ending day 1.
     * @var integer */
    //private $endday1 = 0;

    /** Ending year 2.
     * @var integer */
    //private $endyear2 = 0;

    /** Ending month 2.
     * @var integer */
    //private $endmonth2 = 0;

    /** Ending day 2.
     * @var integer */
    //private $endday2 = 0;

    /** Calendar record ID.
     * @var integer */
    private $cal_id = 0;

    /** Comments enabled flag.
     * @var integer */
    private $enable_comments = 0;

    /** Recurrance type.
     * @var integer */
    private $recurring = 0;

    /** Starting date 1.
     * @var string */
    private $date_start1 = '';

    /** Ending date 1.
     * @var string */
    private $date_end1 = '';

    /** Postmode (HTML or plaintext).
     * @var string */
    private $postmode = 'plaintext';

    /** Timezone identifier.
     * @var string */
    private $tzid = 'local';

    /** Starting time 1.
     * @var string */
    private $time_start1 = '';

    /** Starting time 2 for split events.
     * @var string */
    private $time_start2 = '';

    /** Ending time 1.
     * @var string */
    private $time_end1 = '';

    /** Ending time 2 for split events.
     * @var string */
    private $time_end2 = '';

    /** Enabled status.
     * @var boolean */
    private $status = 1;

    /** Is this an all-day event?
     * @var boolean */
    private $allday = 0;

    /** Is this a split event?
     * @var boolean */
    private $split = 0;

    /** Are reminders enabled?
     * @var boolean */
    private $enable_reminders = 1;

    /** Show in upcoming events block?
     * @var boolean */
    private $show_upcoming = 1;

    /** Related category names.
     * @var array */
    private $categories = array();

    /** Indicate whether the current user is an administrator.
     * @var boolean */
    private $isAdmin = false;

    /** Flags a new event record.
     * @var boolean */
    private $isNew = true;

    /** Detail record ID
     * @var integer */
    private $det_id = 0;

    /** Recurring event data
     * @var array */
    private $rec_data = array();

    /** Other miscelaneous options
     * @var array */
    private $options = array();

    /** Original schedule.
     * Used to check if the schedule must be updated after saving.
     * @var array */
    private $old_schedule = array();

    /** Revision counter, used for iCal output.
     * @var integer */
    private $ev_revision = 0;

    /** Detail object.
     * @var object */
    private $Detail = NULL;

    /** Calendar object.
     * @var object */
    private $Calendar = NULL;

    /** DB table being used (production vs. submission)
     @var string */
    private $table = 'evlist_events';

    /** Array of error messages.
     * @var array */
    private $Errors = array();


    /**
     * Constructor.
     * Reads in the specified class, if $id is set.  If $id is zero,
     * then a new entry is being created.
     *
     * @param   string  $ev_id  Optional event ID
     * @param   integer $detail Optional detail record ID for single repeat
     */
    public function __construct($ev_id='', $detail=0)
    {
        global $_CONF, $_EV_CONF, $_USER;

        $this->isNew = true;
        $this->rec_data = new RecurData;    // Make sure it's a valid object
        $this->options = new EventOptions;

        if ($ev_id == '') {
            $this->owner_id = $_USER['uid'];
            $this->enable_comments = $_EV_CONF['commentsupport'] ? self::CMT_ENABLED : self::CMT_DISABLED;

            // Create dates & times based on individual URL parameters,
            // or defaults.
            // Start date/time defaults to now
            $startday1 = isset($_GET['day']) ? (int)$_GET['day'] : '';
            if ($startday1 < 1 || $startday1 > 31) {
                $startday1 = $_CONF['_now']->format('j', true);
            }
            $startmonth1 = isset($_GET['month']) ? (int)$_GET['month'] : '';
            if ($startmonth1 < 1 || $startmonth1 > 12) {
                $startmonth1 = $_CONF['_now']->format('n', true);
            }
            $startyear1 = isset($_GET['year']) ?
                    (int)$_GET['year'] : $_CONF['_now']->format('Y', true);
            $starthour1 = isset($_GET['hour']) ?
                    (int)$_GET['hour'] : $_CONF['_now']->format('H', true);
            $startminute1 = '0';

            // End date & time defaults to same day, 1 hour ahead
            $endday1 = $startday1;
            $endmonth1 = $startmonth1;
            $endyear1 = $startyear1;
            $endhour1 = $starthour1 != '' ? $starthour1 + 1 : '';
            $endminute1 = '0';

            // Second start & end times default to the same as the first.
            // They'll get reset if this ends up not being a split event.
            $starthour2 = $starthour1;
            $startminute2 = $startminute1;
            $endhour2 = $endhour1;
            $endminute2 = $endminute1;

            $this->date_start1 = sprintf(
                "%4d-%02d-%02d",
                $startyear1, $startmonth1, $startday1
            );
            $this->time_start1 = sprintf(
                "%02d:%02d",
                $starthour1, $startminute1
            );
            $this->time_start2 = sprintf(
                "%02d:%02d",
                $starthour2, $startminute2
            );
            $this->date_end1 = sprintf(
                "%4d-%02d-%02d",
                $endyear1, $endmonth1, $endday1
            );
            $this->time_end1 = sprintf("%02d:%02d", $endhour1, $endminute1);
            $this->time_end2 = sprintf("%02d:%02d", $endhour2, $endminute2);

            $this->perm_owner   = $_EV_CONF['default_permissions'][0];
            $this->perm_group   = $_EV_CONF['default_permissions'][1];
            $this->perm_members = $_EV_CONF['default_permissions'][2];
            $this->perm_anon    = $_EV_CONF['default_permissions'][3];
            if ($_EV_CONF['rsvp_print'] <= 1) { // default "no"
                $this->options['rsvp_print'] = 0;
            } else {
                $this->options['rsvp_print'] = $_EV_CONF['rsvp_print'] - 1;
            }

            $this->Detail = new Detail();

        } else {
            $this->id = $ev_id;
            if (!$this->Read()) {
                $this->id = '';
            } else {
                // Load the Detail object.  May need to load a special one
                // if we're editing a repeat instance.
                if ($detail > 0 && $detail != $this->det_id) {
                    $this->Detail = Detail::getInstance($detail);
                } else {
                    // Normal, load our own detail object
                    $this->Detail = Detail::getInstance($this->det_id);
                }
                $this->isNew = 0;
            }
        }
        $this->isAdmin = plugin_isadmin_evlist();
    }


    /**
     * Get an instance of an event.
     *
     * @param   string  $ev_id      Event ID
     * @param   integer $det_id     Optional specific detail record ID
     * @return  object              Event object
     */
    public static function getInstance(string $ev_id, int $det_id = 0) : self
    {
        static $records = array();

        if (!array_key_exists($ev_id, $records)) {
            $records[$ev_id] = new self($ev_id, $det_id);
        }
        return $records[$ev_id];
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
     * Sanitize and set the event ID property.
     *
     * @param   string  $id     Event record ID
     * @return  object  $this
     */
    public function setID($id)
    {
        $this->id = COM_sanitizeID($id);
        return $this;
    }


    /**
     * Get the event record ID.
     *
     * @return  string      Event ID
     */
    public function getID()
    {
        return $this->id;
    }


    /**
     * Set the category names into the property, creating array if needed.
     *
     * @param   string|array    $value  Comma-separated string or array
     * @return  object  $this
     */
    private function setCategories($value)
    {
        if (is_array($value)) {
            $this->categories= $value;
        } else {
            $this->categories = explode(',', $value);
        }
        return $this;
    }


    /**
     * Sanitize and set the owner ID.
     *
     * @param   integer $id     Owner's user ID, 0 for current user
     * @return  object  $this
     */
    public function setOwner(?int $id=NULL) : self
    {
        global $_USER;

        if ($id == NULL) $id = $_USER['uid'];
        $this->owner_id = (int)$id;
        return $this;
    }


    /**
     * Sanitize and set the group ID.
     *
     * @param   integer $id     Group ID
     * @return  object  $this
     */
    public function setGroup(int $id) : self
    {
        $this->group_id = (int)$id;
        return $this;
    }


    /**
     * Set the owner permission.
     *
     * @param   integer $perm   Permission value, NULL for default
     */
    public function setPermOwner(?int $perm=NULL) : self
    {
        global $_EV_CONF;

        if ($perm == NULL) $perm = $_EV_CONF['default_permissions'][0];
        $this->perm_owner = (int)$perm;
        return $this;
    }


    /**
     * Set the group permission.
     *
     * @param   integer $perm   Permission value, NULL for default
     */
    public function setPermGroup(?int $perm=NULL) : self
    {
        global $_EV_CONF;

        if ($perm == NULL) $perm = $_EV_CONF['default_permissions'][1];
        $this->perm_group = (int)$perm;
        return $this;
    }


    /**
     * Set the member permission.
     *
     * @param   integer $perm   Permission value, -1 for default
     */
    public function setPermMembers(?int $perm=NULL) : self
    {
        global $_EV_CONF;

        if ($perm == NULL) $perm = $_EV_CONF['default_permissions'][2];
        $this->perm_members = (int)$perm;
        return $this;
    }


    /**
     * Set the anonymous permission.
     *
     * @param   integer $perm   Permission value, -1 for default
     */
    public function setPermAnon(?int $perm=NULL) : self
    {
        global $_EV_CONF;

        if ($perm == NULL) $perm = $_EV_CONF['default_permissions'][3];
        $this->perm_anon = (int)$perm;
        return $this;
    }


    /**
     * Get a single option value, NULL if not set.
     *
     * @param   string  $key    Key to retrieve
     * @param   mixed   $default    Default value to return if not set
     * @return  mixed   Single value from the options array
     */
    public function getOption($key, $default=NULL)
    {
        if (isset($key, $this->options)) {
            return $this->options[$key];
        } else {
            return $default;
        }
    }


    /**
     * Check if this event is using the submission table vs. production.
     *
     * @return  boolean     True if submission, False if production
     */
    public function isSubmission()
    {
        return $this->table == 'evlist_submissions';
    }


    /**
     * Set the table to be used, either subsmissions or events.
     *
     * @param   boolean $submission True to use the submission table
     * @return  object  $this
     */
    public function asSubmission(?bool $submission=true) : self
    {
        $this->table = $submission ? 'evlist_submissions' : 'evlist_events';
        return $this;
    }


    /**
     * Get the starting date.
     *
     * @return  string  Starting date
     */
    public function getDateStart1() : string
    {
        return $this->date_start1;
    }


    /**
     * Get the ending date.
     *
     * @return  string  Ending date
     */
    public function getDateEnd1() : string
    {
        return $this->date_end1;
    }


    /**
     * Get the first starting time.
     *
     * @return  string  First starting time
     */
    public function getTimeStart1() : string
    {
        return $this->time_start1;
    }


    /**
     * Get the first ending time.
     *
     * @return  string  Ending time
     */
    public function getTimeEnd1() : string
    {
        return $this->time_end1;
    }


    /**
     * Get the second starting time, for split events.
     *
     * @return  string  Second starting time
     */
    public function getTimeStart2() : string
    {
        return $this->time_end2;
    }


    /**
     * Get the second ending time, for split events.
     *
     * @return  string  Second ending time
     */
    public function getTimeEnd2() : string
    {
        return $this->time_end2;
    }


    /**
     * Get the permissions array for the event.
     *
     * @return  array       Array of permissions
     */
    public function getPerms() : array
    {
        return array(
            'perm_owner' => $this->perm_owner,
            'perm_group' => $this->perm_group,
            'perm_members' => $this->perm_members,
            'perm_anon' => $this->perm_anon,
        );
    }

    public function getGroupID()
    {
        return (int)$this->group_id;
    }

    public function getStatus()
    {
        return (int)$this->status;
    }


    /**
     * Set the `isNew` flag to force this to be a new record.
     * Used when importing records from the Calendar plugin, amont other uses.
     *
     * @param   bool    $isNew  True to forced new (default)
     * @return  self
     */
    public function forceNew(bool $isNew = true) : self
    {
        $this->isNew = $isNew;
        return $this;
    }


    /**
     * Sets all variables to the matching values from $row.
     *
     * @param   array   $row        Array of values, from DB or $_POST
     * @param   boolean $fromDB     True if read from DB, false if from $_POST
     * @return  object  $this
     */
    public function setVars($row, $fromDB=false)
    {
        global $_EV_CONF;

        if (!is_array($row)) {
            return $this;
        }

        if (isset($row['date_start1']) && !empty($row['date_start1'])) {
            $this->date_start1 = $row['date_start1'];
        } else {
            $this->date_start1 = $_CONF['_now']->format('Y-m-d', true);
        }
        if (isset($row['date_end1']) && !empty($row['date_end1'])) {
            $this->date_end1 = $row['date_end1'];
        } else {
            $this->date_end1 = $this->date_start1;
        }
        $this->cal_id = $row['cal_id'];
        $this->show_upcoming = isset($row['show_upcoming']) ? (int)$row['show_upcoming'] : 0;
        $this->recurring = isset($row['recurring']) ? (int)$row['recurring'] : 0;
        if (isset($row['allday']) && $row['allday'] == 1) {
            $this->allday = 1;
            $this->split = 0;
        } else {
            $this->allday = 0;
            $this->split = isset($row['split']) && $row['split'] == 1 ? 1 : 0;
        }

        // Multi-day events can't be split
        if ($this->date_start1 != $this->date_end1) {
            $this->split = 0;
        }

        if (isset($row['status'])) {
            $this->status = (int)$row['status'];
        }
        $this->postmode = isset($row['postmode']) &&
                $row['postmode'] == 'html' ? 'html' : 'plaintext';
        $this->enable_reminders = isset($row['enable_reminders']) &&
                $row['enable_reminders'] == 1 ? 1 : 0;
        $this->setOwner(isset($row['owner_id']) ? $row['owner_id'] : 2);
        $this->setGroup(isset($row['group_id']) ? $row['group_id'] : 13);
        $this->enable_comments = isset($row['enable_comments']) ? $row['enable_comments'] : self::CMT_ENABLED;


        // Categores get added to the row during Read if from a DB, or as part
        // of the posted form.
        $this->setCategories(EV_getVar($row, 'categories', 'array', array()));

        // Join or split the date values as needed
        if ($fromDB) {
            // dates are YYYY-MM-DD
            $this->setID(isset($row['id']) ? $row['id'] : '');
            $this->setRecData($row['rec_data']);
            $this->setOptions($row['options']);
            $this->det_id = (int)$row['det_id'];
            $this->setPermOwner($row['perm_owner'])
                ->setPermGroup($row['perm_group'])
                ->setPermMembers($row['perm_members'])
                ->setPermAnon($row['perm_anon']);
            $this->time_start1 = substr($row['time_start1'], 0, 5);
            $this->time_end1 = substr($row['time_end1'], 0, 5);
            $this->time_start2 = substr($row['time_start2'], 0, 5);
            $this->time_end2 = substr($row['time_end2'], 0, 5);
            $this->tzid = $row['tzid'];
            $this->ev_revision = (int)$row['ev_revision'];
        } else {        // Coming from the form
            $this->id = isset($row['ev_id']) ? $row['ev_id'] : '';
            // Ignore time entries & set to all day if flagged as such
            if (isset($row['allday']) && $row['allday'] == '1') {
                $this->time_start1 = self::MIN_TIME;
                $this->time_end1 = self::MAX_TIME;
            } else {
                $this->time_start1  = date('H:i', strtotime($row['time_start1']));
                $this->time_end1    = date('H:i', strtotime($row['time_end1']));
                /*$start_ampm = isset($row['start1_ampm']) ? $row['start1_ampm'] : '';
                $end_ampm = isset($row['end1_ampm']) ? $row['end1_ampm'] : '';
                $tmp = EVLIST_12to24($row['starthour1'], $start_ampm);
                $this->time_start1 = sprintf('%02d:%02d:00',
                    $tmp, $row['startminute1']);
                $tmp = EVLIST_12to24($row['endhour1'], $end_ampm);
                $this->time_end1 = sprintf('%02d:%02d:00',
                    $tmp, $row['endminute1']);*/
            }

            // If split, record second time/date values.
            // Splits don't support allday events
            if ($this->split == 1) {
                $this->time_start2  = date('H:i', strtotime($row['time_start2']));
                $this->time_end2    = date('H:i', strtotime($row['time_end2']));
                /*$tmp = EVLIST_12to24($row['starthour2'], $row['start2_ampm']);
                $this->time_start2 = sprintf('%02d:%02d:00',
                    $tmp, $row['startminute2']);
                $tmp = EVLIST_12to24($row['endhour2'], $row['end2_ampm']);
                $this->time_end2 = sprintf('%02d:%02d:00',
                    $tmp, $row['endminute2']);*/
            } else {
                $this->time_start2 = self::MIN_TIME;
                $this->time_end2 = self::MIN_TIME;
            }

            if (isset($row['perm_owner'])) {
                $perms = SEC_getPermissionValues(
                    $row['perm_owner'], $row['perm_group'],
                    $row['perm_members'], $row['perm_anon']
                );
                $this->perm_owner   = $perms[0];
                $this->perm_group   = $perms[1];
                $this->perm_members = $perms[2];
                $this->perm_anon    = $perms[3];
            }

            $this->setOwner(isset($row['owner_id']) ? (int)$row['owner_id'] : 2);
            $this->setGroup(isset($row['group_id']) ? (int)$row['group_id'] : 13);
            $this->options['contactlink'] = isset($row['contactlink']) ? 1 : 0;

            $this->options['tickets'] = array();
            if ($_EV_CONF['enable_rsvp']) {
                $this->options['rsvp_comments'] = isset($row['rsvp_comments']) ? (int)$row['rsvp_comments'] : 0;
                if (isset($row['rsvp_cmt_prompts']) && !empty($row['rsvp_cmt_prompts'])) {
                    $this->options['rsvp_cmt_prompts'] = explode('|', $row['rsvp_cmt_prompts']);
                } else {
                    $this->options['rsvp_cmt_prompts'] = array();
                }
                $this->options['rsvp_signup_grp'] = isset($row['rsvp_signup_grp']) ? (int)$row['rsvp_signup_grp'] : 2;
                $this->options['rsvp_view_grp'] = isset($row['rsvp_view_grp']) ? (int)$row['rsvp_view_grp'] : 1;
                $this->options['use_rsvp'] = isset($row['use_rsvp']) ? (int)$row['use_rsvp'] : 0;
                $this->options['max_rsvp'] = isset($row['max_rsvp']) ? (int)$row['max_rsvp'] : 0;
                $this->options['rsvp_waitlist'] = isset($row['rsvp_waitlist']) ? 1 : 0;
                $this->options['rsvp_cutoff'] = isset($row['rsvp_cutoff']) ? (int)$row['rsvp_cutoff'] : 0;
                if ($this->options['max_rsvp'] < 0) $this->options['max_rsvp'] = 0;
                $this->options['max_user_rsvp'] = isset($row['max_user_rsvp']) ? (int)$row['max_user_rsvp'] : 0;
                if (!isset($row['tickets']) || !is_array($row['tickets'])) {
                    // if no ticket specified but rsvp is ensabled, make sure
                    // the general admission ticket is set for free
                    $row['tickets'] = array(1);
                    $row['tick_fees'] = array(0);
                }
                foreach ($row['tickets'] as $tick_id=>$tick_data) {
                    $tick_fee = isset($row['tick_fees'][$tick_id]) ?
                        (float)$row['tick_fees'][$tick_id] : 0;
                    $this->options->setTicket($tick_id, array('fee' => $tick_fee));
                    /*$this->options['tickets'][$tick_id] = array(
                        'fee' => $tick_fee,
                    );*/
                }
                $this->options['rsvp_print'] = isset($row['rsvp_print']) ? $row['rsvp_print'] : 0;
            } else {
                $this->options['use_rsvp'] = 0;
                $this->options['max_rsvp'] = 0;
                $this->options['rsvp_cutoff'] = 0;
                $this->options['rsvp_waitlist'] = 0;
                $this->options['max_user_rsvp'] = 1;
                $this->options['rsvp_print'] = 0;
            }
            if (isset($row['tz_local'])) {
                $this->tzid = 'local';
            } elseif (isset($row['tzid'])) {
                $this->tzid = $row['tzid'];
            } else {
                $this->tzid = 'local';
            }
        }
        return $this;
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @param   string  $ev_id  Optional ID.  Current ID is used if zero.
     * @return  boolean     True if a record was read, False on failure.
     */
    public function Read(string $ev_id = '') : bool
    {
        global $_TABLES;

        if ($ev_id != '') {
            $this->id = COM_sanitizeID($ev_id);
        }

        $sql = "SELECT * FROM {$_TABLES[$this->table]} WHERE id='$this->id'";
        $result = DB_query($sql);
        if (!$result || DB_numRows($result) != 1) {
            return false;
        } else {
            $row = DB_fetchArray($result, false);

            // We'll just stick the categories into $row before it gets
            // sent to SetVars().
            $row['categories'] = array();
            $cresult = DB_query("SELECT cid
                        FROM {$_TABLES['evlist_lookup']}
                        WHERE eid='{$this->id}'");
            if ($cresult) {
                while ($A = DB_fetchArray($cresult, false)) {
                    $row['categories'][] = $A['cid'];
                }
            }

            $this->SetVars($row, true);
            $this->isNew = false;

            $this->Detail = Detail::getInstance($this->det_id);
            $this->Calendar = Calendar::getInstance($this->cal_id);

            return true;
        }
    }


    /**
     * Update the recurring data.
     *
     * @param   string  $key    Key name, e.g. "stop", empty to just save as-is
     * @param   mixed   $val    New value for key
     * @return  object  $this
     */
    public function updateRecData(?string $key, $val=NULL) : self
    {
        global $_TABLES;

        if (!empty($key)) {
            if ($val === NULL) {
                $val = '';
            }
            $this->rec_data[$key] = $val;
        }
        try {
            Database::getInstance()->conn->executeStatement(
                "UPDATE {$_TABLES['evlist_events']} SET
                    rec_data = ?,
                    ev_revision = ev_revision + 1
                    WHERE id = ?",
                array(json_encode($this->rec_data), $this->id),
                array(Database::STRING, Database::STRING)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
        return $this;
    }


    /**
     * Save the current values to the database.
     * Appends error messages to the $Errors property.
     *
     * @param   array   $A      Optional array of values from $_POST
     * @return  string      Error text, or empty string on success
     */
    public function Save($A = '')
    {
        global $_TABLES, $LANG_EVLIST, $_EV_CONF, $_CONF;

        // This is a bit of a hack, but we're going to save the old schedule
        // first before changing our own values.  This is done so that we
        // can determine whether we have to update the repeats table, and
        // is only relevant for an existing record.
        if (!$this->isNew) {
            $this->old_schedule = array(
                'date_start1'   => $this->date_start1,
                'date_end1'     => $this->date_end1,
                'time_start1'   => $this->time_start1,
                'time_end1'     => $this->time_end1,
                'time_start2'   => $this->time_start2,
                'time_end2'     => $this->time_end2,
                'allday'        => $this->allday,
                'recurring'     => $this->recurring,
                'rec_data'      => $this->rec_data,
            );
        } else {
            // submit privilege required to submit new events
            if (!EVLIST_canSubmit()) {
                return false;
            }
            $this->old_schedule = array();
        }

        $old_status = $this->status;
        // Now we can update our main record with the new info
        if (is_array($A)) {
            $this->SetVars($A);
            $this->MakeRecData($A);
        }

        // Authorized to bypass the queue
        if (EVLIST_skipqueue()) {
            $this->asSubmission(false);
        }

        if ($this->id == '') {
            // If we allow users to create IDs, this could happen
            $this->id = COM_makesid();
        }

        $ev_id_DB = DB_escapeString($this->id);   // Used often, sanitize now

        // Insert or update the record, as appropriate
        if (!$this->isNew) {

            // Subject to the submission queue, can't edit even their
            // own events.
            if ($this->isSubmission()) {
                return false;
            }

            // Existing event, we already have a Detail object instantiated
            $this->getDetail()->setVars($A);
            //$this->getDetail()->setEventID($this->id);

            if (!$this->isValidRecord()) {
                return false;
            }
            // Delete the category lookups
            DB_delete($_TABLES['evlist_lookup'], 'eid', $this->id);

            // Save the main event record
            $sql1 = "UPDATE {$_TABLES[$this->table]} SET
                ev_revision = ev_revision + 1, ";
            $sql2 = " WHERE id='$ev_id_DB'";

            // Save the new detail record & get the ID
            $this->det_id = $this->getDetail()->Save();

            // Quit now if the detail record failed
            if ($this->det_id == 0) return false;

            // Determine if the schedule has changed so that we need to
            // update the repeat tables.  If we do, any customizations will
            // be lost.
            $rp_update = $this->needRepeatUpdate($A);
            if ($rp_update & self::RP_SINGLEEDIT) {
                // this is a one-time event, update the existing instance
                $t_end = $this->split ? $this->time_end2 : $this->time_end1;
                $sql = "UPDATE {$_TABLES['evlist_repeat']} SET
                        rp_date_start = '{$this->date_start1}',
                        rp_date_end = '{$this->date_end1}',
                        rp_time_start1 = '{$this->time_start1}',
                        rp_time_end1 = '{$this->time_end1}',
                        rp_time_start2 = '{$this->time_start2}',
                        rp_time_end2 = '{$this->time_end2}',
                        rp_start = CONCAT('{$this->date_start1}', ' ', '{$this->time_start1}'),
                        rp_end = CONCAT('{$this->date_end1}' , ' ' , '$t_end'),
                        rp_status = {$this->status},
                        rp_revision = rp_revision + 1
                    WHERE rp_ev_id = '{$this->id}'";
                DB_query($sql, 1);
            }

            if ($rp_update & self::RP_NEWSCHEDULE) {
                if ($this->old_schedule['recurring'] || $this->recurring) {
                    // This function sets the rec_data value.
                    if (!$this->UpdateRepeats()) {
                        return false;
                    }
                    // Cancel any instances that occur before the start date,
                    // just in case we're here due to a start date change.
                    Repeat::updateEventStatus(
                        $this->id,
                        Status::CANCELLED,
                        "AND rp_date_start < '" . $this->date_start1 . "'"
                    );

                    // No further updates needed after new schedule creation.
                    $rp_update = 0;
                }
            }

            if ($rp_update & self::RP_TRUNCATE) {
                // Truncating the series, we'll be deleting events from
                // one day after the new end through the old ending.
                $new_end = new \Date($this->rec_data['stop']);
                $new_end->add(new \DateInterval('P1D'));
                Repeat::updateEventStatus(
                    $this->id,
                    Status::CANCELLED,
                    "AND rp_date_start >= '" . $new_end->format('Y-m-d') ."'"
                );
            } elseif ($rp_update & self::RP_EXTEND) {
                // Get the old stop date. If an error was made, it's possible that
                // the stop date is < date_start. In that case, don't create events
                // prior to start_date.
                /*$old_end = new \Date($this->old_schedule['rec_data']['stop']);
                $old_end->add(new \DateInterval('P1D'));
                $old_end = max($this->date_start1, $old_end->format('Y-m-d'));

                // First enable any repeats between the old stop date and new stop
                Repeat::updateEventStatus(
                    $this->id,
                    Status::ENABLED,
                    " AND rp_date_start >= '$old_end'
                    AND rp_date_start <= '" . $this->rec_data['stop'] . "'"
                );
                $this->UpdateRepeats($old_end, $this->rec_data['stop']);*/
                $this->UpdateRepeats();
            }

            if ($rp_update & self::RP_RECUR2SINGLE) {
                // Switching from recurring to single instance.
                // Cancel all repeats after the first one.
                Repeat::updateEventStatus(
                    $this->id,
                    Status::CANCELLED,
                    "AND rp_date_start > '{$this->date_start1}'"
                );

                // Then cancel all custom detail records.
                Detail::updateEventStatus(
                    $this->id,
                    Status::CANCELLED,
                    "AND det_id <> '{$this->det_id}'"
                );
            }
            if ($rp_update & self::RP_NEWTIME) {
                // Update the start and end times for all event instances.
                Repeat::updateEvent(
                    $this->id,
                    array(
                        'rp_time_start1' => $this->time_start1,
                        'rp_time_end1' => $this->time_end1,
                        'rp_time_start2' => $this->time_start2,
                        'rp_time_end2' => $this->time_end2,
                    )
                );
            }

            // Update the repeat status only if the event status has changed.
            // If new repeats were created, the status is updated at that time.
            if ($old_status != $this->status) {
                Repeat::updateEventStatus($this->id, $this->status);
            }
        } else {
            // New event
            $this->asSubmission(!EVLIST_skipqueue());

            // Create a detail record
            $this->Detail = new Detail();
            $this->getDetail()->setVars($A);
            $this->getDetail()->setEventID($this->id);
            if (!$this->isValidRecord()) {
                return false;
            }

            // Save the new detail record & get the ID
            $this->det_id = $this->getDetail()->Save();

            // Quit now if the detail record failed
            if ($this->det_id == 0) return false;

            if (!$this->isSubmission()) {
                // This function gets the rec_data value.
                if (!$this->UpdateRepeats()) {
                    return false;
                }
            }

            $sql1 = "INSERT INTO {$_TABLES[$this->table]} SET
                    id = '" . DB_escapeString($this->id) . "', ";
            $sql2 = '';
        }

        // Now save the categories
        // First save the new category if one was submitted
        if (!is_array($this->categories)) $this->categories = array();
        if (isset($A['newcat']) && !empty($A['newcat'])) {
            $newcat = $this->saveCategory($A['newcat']);
            if ($newcat > 0) $this->categories[] = $newcat;
        }
        $tmp = array();
        foreach($this->categories as $cat_id) {
            $tmp[] = "('{$this->id}', '$cat_id')";
        }
        if (!empty($tmp)) {
            $sql = "INSERT INTO {$_TABLES['evlist_lookup']}
                    (eid, cid)
                    VALUES " . implode(',', $tmp);
            DB_query($sql);
        }

        $fld_sql = "date_start1 = '" . DB_escapeString($this->date_start1) . "',
            date_end1 = '" . DB_escapeString($this->date_end1) . "',
            time_start1 = '" . DB_escapeString($this->time_start1) . "',
            time_end1 = '" . DB_escapeString($this->time_end1) . "',
            time_start2 = '" . DB_escapeString($this->time_start2) . "',
            time_end2 = '" . DB_escapeString($this->time_end2) . "',
            recurring = '{$this->recurring}',
            rec_data = '" . DB_escapeString(json_encode($this->rec_data)) . "',
            allday = '{$this->allday}',
            split = '{$this->split}',
            status = '{$this->status}',
            postmode = '" . DB_escapeString($this->postmode) . "',
            enable_reminders = '{$this->enable_reminders}',
            enable_comments = '{$this->enable_comments}',
            owner_id = '{$this->owner_id}',
            group_id = '{$this->group_id}',
            perm_owner = '{$this->perm_owner}',
            perm_group = '{$this->perm_group}',
            perm_members = '{$this->perm_members}',
            perm_anon = '{$this->perm_anon}',
            det_id = '{$this->det_id}',
            cal_id = '{$this->cal_id}',
            show_upcoming = '{$this->show_upcoming}',
            tzid = '" . DB_escapeString($this->tzid) . "',
            options = '" . DB_escapeString(json_encode($this->options)) . "' ";
        //var_dump($this->options);die;

        $sql = $sql1 . $fld_sql . $sql2;
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            $this->Errors[] = $LANG_EVLIST['err_db_saving'];
            COM_errorLog($sql);
        } elseif (
            $this->isSubmission() &&
            isset($_CONF['notification']) &&
            in_array ('evlist', $_CONF['notification'])
        ) {
            $N = new \Template(EVLIST_PI_PATH . '/templates');
            $N->set_file('mail', 'notify_submission.thtml');
            $N->set_var(array(
                'title'     => $this->getDetail()->getTitle(),
                'summary'   => $this->getDetail()->getSummary(),
                'start_date' => $this->date_start1,
                'end_date'  => $this->date_end1,
                'start_time' => $this->time_start1,
                'end_time'  => $this->time_end1,
                'submitter' => COM_getDisplayName($this->owner_id),
            ) );
            $N->parse('output', 'mail');
            $mailbody = $N->finish($N->get_var('output'));
            $subject = $LANG_EVLIST['notify_subject'];
            $to = COM_formatEmailAddress('', $_CONF['site_mail']);
            COM_mail($to, $subject, $mailbody, '', true);
        }

        if (empty($this->Errors)) {
            /*if ($this->isNew) {
                $this->id = DB_insertID();
            }*/
            if (!$this->isSubmission()) {
                Cache::clear('events');
                PLG_itemSaved($this->id. ':', 'evlist');
                //PLG_itemSaved(Repeat::getFirst($this->id), 'evlist');
                COM_rdfUpToDateCheck('evlist', 'events', $this->id);
            }
            return true;
        } else {
            return false;
        }
    }


    /**
     * Delete the specified event record and all repeats.
     * Specify "false" for clearcache param if the cache will be clared
     * by the caller, e.g. when deleting events in bulk.
     *
     * @param   string  $eid    Event ID
     * @param   boolean $clearcache True to clear cache, false to not
     * @return      True on success, False on failure
     */
    public static function Delete($eid, $clearcache=true)
    {
        global $_TABLES, $_EV_CONF;

        if ($eid == '') {
            return false;
        }

        $force_delete = false;

        // Make sure the current user has access to delete this event.
        // Try to retrieve the event. If the event is found and the user does
        // not have write access, return false.
        // If the event is not found then the repeats are probably out of sync
        // so delete everything anyway.
        if ($clearcache) {  // leverage flag to consider $eid as valid
            $sql = "SELECT * FROM {$_TABLES['evlist_events']}
                    WHERE id='$eid'";
            $res = DB_query($sql);
            if ($res && DB_numRows($res) == 1) {    // found normal record
                $A = DB_fetchArray($res, false);
                $access = SEC_hasAccess(
                    $A['owner_id'], $A['group_id'],
                    $A['perm_owner'], $A['perm_group'], $A['perm_members'], $A['perm_anon']
                );
                if ($access < 3) {
                    return false;
                }
                if ($A['status'] == Status::CANCELLED) {
                    $force_delete = true;
                }
            }
        }

        if ($force_delete || $_EV_CONF['purge_cancelled_days'] < 1) {
            DB_delete($_TABLES['evlist_remlookup'], 'eid', $eid);
            DB_delete($_TABLES['evlist_lookup'], 'eid', $eid);
            DB_delete($_TABLES['evlist_tickets'], 'ev_id', $eid);
            DB_delete($_TABLES['evlist_repeat'], 'rp_ev_id', $eid);
            DB_delete($_TABLES['evlist_detail'], 'ev_id', $eid);
            DB_delete($_TABLES['evlist_events'], 'id', $eid);
        } else {
            Repeat::updateEventStatus($eid, Status::CANCELLED);
            DB_change($_TABLES['evlist_events'], 'status', Status::CANCELLED, 'id', $eid);
            DB_change($_TABLES['evlist_detail'], 'det_status', Status::CANCELLED, 'ev_id', $eid);
        }
        // Always delete reminders to avoid sending for cancelled events.
        DB_delete($_TABLES['evlist_remlookup'], 'eid', $eid);
        PLG_itemDeleted($eid, 'evlist');
        if ($clearcache) {
            Cache::clear();
        }
        return true;
    }


    /**
     * Delete cancelled events that have not been updated in some time.
     */
    public static function purgeCancelled()
    {
        global $_TABLES, $_EV_CONF;

        $days = (int)$_EV_CONF['purge_cancelled_days'];
        $sql = "SELECT id FROM {$_TABLES['evlist_events']}
                WHERE status = " . Status::CANCELLED .
                " AND ev_last_mod < DATE_SUB(NOW(), INTERVAL $days DAY)";
        $res = DB_query($sql);
        if ($res) {
            while ($A = DB_fetchArray($res, false)) {
                DB_delete($_TABLES['evlist_remlookup'], 'eid', $A['id']);
                DB_delete($_TABLES['evlist_lookup'], 'eid', $A['id']);
                DB_delete($_TABLES['evlist_tickets'], 'ev_id', $A['id']);
                DB_delete($_TABLES['evlist_events'], 'id', $A['id']);
             }
        }

        // Now delete any remaining cancelled occurrences, maybe from
        // modifying the schedule.
        Repeat::purgeCancelled();
        Detail::purgeCancelled();
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
            $this->date_start1 . ' ' . $this->time_start1 >
            $this->date_end1 . ' ' . $this->time_end1
        ) {
            $this->Errors[] = $LANG_EVLIST['err_times'];
        }

        if ($this->split == 1 && $this->date_start1 == $this->date_end1) {
            if (
                $this->date_start1 . ' ' . $this->time_start2 >
                $this->date_start1 . ' ' . $this->time_end2
            ) {
                $this->Errors[] = $LANG_EVLIST['err_times'];
            }
        }

        if (
            $this->recurring == EV_RECUR_WEEKLY &&
            empty($this->rec_data['listdays'])
        ) {
            $this->Errors[] = $LANG_EVLIST['err_missing_weekdays'];
        }

        if (!empty($this->Errors)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Toggles a field to the opposite of the existing value.
     *
     * @param   integer $oldvalue   Original value
     * @param   string  $varname    DB field name to toggle
     * @param   string  $ev_id      Event record ID
     * @return  integer     New value, or old value upon failure
     */
    private static function _toggle(int $oldvalue, string $varname, string $ev_id) : int
    {
        global $_TABLES;

        $ev_id = COM_sanitizeID($ev_id, false);
        if ($ev_id == '') return $oldvalue;
        $oldvalue = $oldvalue == 0 ? 0 : 1;
        $newvalue = $oldvalue == 1 ? 0 : 1;
        try {
            Database::getInstance()->conn->executeStatement(
                "UPDATE {$_TABLES['evlist_events']} SET
                $varname = ?,
                ev_revision = ev_revision + 1
                WHERE id = ?",
                array($newvalue, $ev_id),
                array(Database::INTEGER, Database::STRING)
            );
            Cache::clear('events');
            return $newvalue;
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return $oldvalue;
        }
    }


    /**
     * Sets the `enabled` field based on the existing value.
     *
     * @param  integer $oldvalue   Original value
     * @param  string  $ev_id      Event record ID
     * @return         New value, or old value upon failure
     */
    public static function toggleEnabled($oldvalue, $ev_id='')
    {
        return self::_toggle($oldvalue, 'status', $ev_id);
    }


    /**
     * Update the event status. Used from the admin list.
     *
     * @param   string  $ev_id      Event record ID
     * @param   int     $newval     New value to set
     * @param   int     $oldval     Old value for verification
     * @return  int         New value if successful, Old value if not
     */
    public static function setEventStatus(string $ev_id, int $newval, int $oldval, ?int $uid=NULL) : int
    {
        global $_TABLES;

        $newval = (int)$newval;

        // If a user ID is supplied, check that the user is the event owner.
        if ($uid) {
            $Ev = self::getInstance($ev_id);
            if ($uid != $Ev->getOwnerId()) {
                return $oldval;
            }
        }

        Repeat::updateEventStatus($ev_id, $newval);
        $sql = "UPDATE {$_TABLES['evlist_events']}
            SET status = $newval
            WHERE id = '" . DB_escapeString($ev_id) . "'";
        DB_query($sql);
        if (!DB_error()) {
            Cache::clear();
            return $newval;
        } else {
            return $oldval;
        }
    }


    /**
     * Set the error messages from an external source, e.g. a Repeat
     *
     * @param   array   $Errors     Array of error messages
     * @return  object  $this
     */
    public function setErrors(array $Errors) : self
    {
        $this->Errors = $Errors;
        return $this;
    }


    /**
     * Get the raw error messages for logging.
     *
     * @return  array   Array of error messages
     */
    public function getErrors() : array
    {
        return $this->Errors;
    }


    /**
     * Create the individual occurrances of a the current event.
     * If the event is not recurring, returns an array with only one element.
     *
     * @return  array       Array of matching events, keyed by date, or false
     */
    public function MakeRecurrences(?string $start=NULL, ?string $end=NULL) : array
    {
        return Recurrence::getInstance($this)
            ->withStartingDate($start)
            ->withEndingDate($end)
            ->MakeRecurrences()
            ->getEvents();
    }


    /**
     * Update all the repeats in the database.
     * Deletes all existing repeats, then creates new ones. Not very
     * efficient; it might make sense to check all related values, but there
     * are several.
     *
     * @return  boolean     True on success, False on failure
     */
    public function UpdateRepeats() : bool
    {
        global $_TABLES;

        // Sanitize some of the values in rec_data
        if (
            $this->rec_data['stop'] == '' ||
            $this->rec_data['stop'] > EV_MAX_DATE
        ) {
            $this->rec_data['stop'] = EV_MAX_DATE;
        }
        if ((int)$this->rec_data['freq'] < 1) {
            $this->rec_data['freq'] = 1;
        }

        // Get the actual repeat occurrences.
        $days = $this->MakeRecurrences();
        if ($days === false) {
            $this->Errors[] = $LANG_EVLIST['err_upd_repeats'];
            return false;
        }

        // Get all the existing repeats
        $Existing = array_values(Repeat::getByEvent($this->id));
        $ex_i = 0;
        $insert_vals = array();
        foreach($days as $event) {
            if (
                isset($Existing[$ex_i]) &&
                $event['dt_start'] >= $Existing[$ex_i]->getDateStart1()->format('Y-m-d')
            ) {
                $Ex = $Existing[$ex_i];
                if (!$Ex->matchesSchedule($event, $this->status)) {
                    // Update the existing repeat with a new date
                    // if it doesn't already match.
                    $Ex->setDateStart1($event['dt_start'] . ' ' . $event['tm_start1'])
                       ->setDateEnd1($event['dt_end'] . ' ' . $event['tm_end1'])
                       ->setDateStart($event['dt_start'])
                       ->setDateEnd($event['dt_end'])
                       ->setTimeStart1($event['tm_start1'])
                       ->setTimeEnd1($event['tm_end1'])
                       ->setTimeStart2($event['tm_start2'])
                       ->setTimeEnd2($event['tm_end2'])
                       ->setStatus($this->status);
                    $Ex->Save();
                }
                $ex_i++;
            } else {
                // New start date is less than the existing date at the
                // start, or we ran out of existing events at the end.
                // Create new instances where needed.
                $t_end = $this->split ? $this->time_end2 : $this->time_end1;
                $insert_vals[] = "(
                    '{$this->id}', '{$this->det_id}',
                    '{$event['dt_start']}', '{$event['dt_end']}',
                    '{$this->time_start1}', '{$this->time_end1}',
                    '{$this->time_start2}', '{$this->time_end2}',
                    '{$event['dt_start']} {$this->time_start1}',
                    '{$event['dt_end']} {$t_end}',
                    {$this->status}
                )";
            }
        }

        // Now remove any old existing entries in case the ending date
        // was moved up. Accumulating the record IDs to delete in a
        // single SQL query.
        $count = count($Existing);
        $del_rp_ids = array();
        $del_dt_ids = array();
        for (; $ex_i < $count; $ex_i++) {
            $del_rp_ids[] = $Existing[$ex_i]->getID();
            if ($Existing[$ex_i]->getDetailID() != $this->getDetailID()) {
                $del_dt_ids[] = $Existing[$ex_i]->getDetailID();
            }
        }
        if (!empty($del_rp_ids)) {
            Repeat::updateEventStatus(
                $this->id,
                Status::CANCELLED,
                "AND rp_id IN (" . implode(',', $del_rp_ids) . ")"
            );
        }
        if (!empty($del_det_ids)) {
            Detail::updateEventStatus(
                $this->id,
                Status::CANCELLED,
                "AND det_id IN (" . implode(',', $del_dt_ids) . ")"
            );
        }
        if (!empty($insert_vals)) {
            $vals = implode(',', $insert_vals);
            $sql = "INSERT INTO {$_TABLES['evlist_repeat']} (
                rp_ev_id, rp_det_id, rp_date_start, rp_date_end,
                rp_time_start1, rp_time_end1,
                rp_time_start2, rp_time_end2,
                rp_start, rp_end,
                rp_status
                ) VALUES $vals";
                DB_query($sql, 1);
        }

        return true;
    }


    /**
     * Create a formatted display-ready version of the error messages.
     *
     * @return  string      Formatted error messages.
     */
    public function PrintErrors()
    {
        $retval = '';
        foreach($this->Errors as $key=>$msg) {
            $retval .= "<li>$msg</li>" . LB;
        }
        return $retval;
    }


    /**
     * Break up a date & time into component parts.
     *
     * @param   string  $date   SQL-formatted date
     * @param   string  $time   Time (HH:MM)
     * @return  array   Array of values.
     */
    public function DateParts($date, $time)
    {
        $month = '';
        $day = '';
        $year = '';
        $hour = '';
        $minute = '';

        if ($date != '' && $date != '0000-00-00') {
            list($year, $month, $day) = explode('-', $date);

            //no time if no date
            if ($time != '') {
                $parts = explode(':', $time);
                $hour = $parts[0];
                $minute = $parts[1];
                if (isset($parts[2])) {
                    $second = $parts[2];
                } else {
                    $second = 0;
                }
            } else {
                $hour = '';
                $minute = '';
            }
        }

        return array($month, $day, $year, $hour, $minute);
    }


    /**
     * Determine whether the current user has access to this event.
     *
     * @param   integer $level  Access level required
     * @return  boolean         True = has sufficient access, False = not
     */
    public function hasAccess(int $level=3) : bool
    {
        $retval = true;

        if ($this->isAdmin) {
            // Admin & editor has all rights
            $retval = true;
        } elseif (!EVLIST_canView()) {
            // If anonymous and anon not allowed, no need to check perms
            $retval = false;
        } else {
            $ev_access = SEC_hasAccess(
                $this->owner_id, $this->group_id,
                $this->perm_owner, $this->perm_group,
                $this->perm_members, $this->perm_anon
            );
            if (
                $ev_access < $level ||
                $this->Calendar->getSecAccess() < $level
            ) {
                $retval = false;
            }
        }
        return $retval;
    }


    /**
     * Get the categories currently tied to this event.
     * Uses Category::getAll() to leverage caching.
     *
     * @uses    Category::getall()
     * @return  array   Array of (id, name)
     */
    public function getCategories() : array
    {
        $retval = array();
        if (!is_array($this->categories)) {
            return $retval;
        }
        $Cats = Category::getAll();
        foreach ($this->categories as $cat_id) {
            $retval[] = array(
                'id'    => $cat_id,
                'name'  => $Cats[$cat_id]->getName(),
            );
        }
        return $retval;
    }


    /**
     * Save a new category submitted with the event.
     * Returns the ID of the newly-added category, or of the existing
     * catgory if $cat_name is a duplicate.
     *
     * @param   string  $cat_name   New category name.
     * @return  integer     ID of category
     */
    public function saveCategory(string $cat_name) : int
    {
        $Cat = new Category();
        $Cat->setName($cat_name);
        $id = $Cat->Save();
        return $id;
    }


    /**
     * Determine if an update to the repeat table is needed.
     * Checks all the dates & times, and the recurring settings to see
     * if any have changed.
     * Uses the old_schedule variable, which must be set first.
     *
     * @uses    self::_arrayDiffAssocRecursive()
     * @param   array   $A  Array of values (e.g. $_POST)
     * @return  string      String indicating type of update needed
     */
    public function needRepeatUpdate($A)
    {
        $retval = 0;
        $old_rec = $this->old_schedule['rec_data'];
        $new_rec = $this->rec_data;

        if (
            $this->old_schedule['date_start1'] != $this->date_start1 ||
            $this->old_schedule['date_end1'] != $this->date_end1
        ) {
            // Begining date changed, may need to add or remove instances.
            // Ending date_end is still part of the first event if multiday.
            $retval |= self::RP_NEWSCHEDULE;
        }
        if ($old_rec['stop'] > $new_rec['stop']) {
            // Stop date for instances changed, may need to add or remove some.
            $retval |= self::RP_TRUNCATE;
        } elseif ($old_rec['stop'] < $new_rec['stop']) {
            // Extending the series
            $retval |= self::RP_EXTEND;
        }

        if ($this->time_start2 == '') {
            $this->time_start2 = self::MIN_TIME;
        }
        if ($this->time_end2 == '') {
            $this->time_end2 = self::MAX_TIME;
        }
        if (
            $this->old_schedule['time_start1'] != $this->time_start1 ||
            $this->old_schedule['time_end1'] != $this->time_end1 ||
            $this->old_schedule['time_start2'] != $this->time_start2 ||
            $this->old_schedule['time_end2'] != $this->time_end2 ||
            $this->old_schedule['allday'] != $this->allday
        ) {
            // Only the time of day has changed, existing repeats
            // will be updated
            $retval |= self::RP_NEWTIME;
        }

        // Recurrence Possibilities:
        //  - was not recurring, is now.  Return true at this point.
        //  - was recurring, isn't now.  Return true at this point.
        //  - wasn't recurring, still isn't, old_schedule['rec_data'] will
        //      be empty, ignore.
        //  - was recurring, still is.  Have to check old and new rec_data
        //      arrays.
        if ($this->old_schedule['recurring'] != $this->recurring) {
            if ($this->recurring == 0) {
                // Was recurring, now is not.
                // Just need to delete the recurrences.
                $retval |= self::RP_RECUR2SINGLE;
            } else {
                // Recurrence type changed, need to rebuild the schedule.
                $retval |= self::RP_NEWSCHEDULE;
            }
        } elseif ($this->recurring == 0) {
            // Was not and still isn't recurring, update the single instance.
            $retval |= self::RP_SINGLEEDIT;
        } elseif (!empty($this->old_schedule['rec_data'])) {
            // Check the recurring event options
            // Have to descend into sub-arrays manually.  Old and/or new
            // values may not be arrays if the recurrence type was changed.
            foreach (array('listdays', 'interval', 'custom') as $key) {
                $oldA = (isset($old_rec[$key]) && is_array($old_rec[$key])) ?
                    $old_rec[$key] : array();
                $newA = (isset($new_rec[$key]) && is_array($new_rec[$key])) ?
                    $new_rec[$key] : array();
                $diff = self::_arrayDiffAssocRecursive($oldA, $newA);
                if (!empty($diff)) {
                    $retval |= self::RP_NEWSCHEDULE;
                }
            }
        } else {
            // Even non-recurring events should have some empty array for
            // old schedule data, so go ahead & rebuild the repeats.
            $retval |= self::RP_NEWSCHEDULE;
        }

        return $retval;
    }


    /**
     * Recursively check two arrays for differences.
     * From http://nl3.php.net/manual/en/function.array-diff-assoc.php#73972
     *
     * @see     self::needRepeatUpdate()
     * @param   array   $array1     First array
     * @param   array   $array2     Second array
     * @return  mixed       Array of differences, or 0 if none.
     */
    private static function _arrayDiffAssocRecursive($array1, $array2)
    {
        $difference = array();
        foreach($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key])) {
                    $difference[$key] = $value;
                } elseif (!is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else  {
                    $new_diff = self::_arrayDiffAssocRecursive($value, $array2[$key]);
                    if ($new_diff !== 0) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!isset($array2[$key]) || $array2[$key] != $value) {
                $difference[$key] = $value;
            }
        }
        return empty($difference) ? 0 : $difference;
    }


    /**
     * Creates the rec_data array.
     * This holds the recurrence frequency, type, start, end, etc.
     *
     * @param   array   $A      Array of data, default to $_POST
     * @return  object  $this
     */
    public function MakeRecData($A = '')
    {
        if ($A == '') $A = $_POST;

        // Re-initialize the array, and make sure this is really a
        // recurring event
        $this->rec_data = array();
        if ($this->recurring == 0) {
            $this->rec_data['type'] = 0;
            $this->rec_data['stop'] = EV_MAX_DATE;
            $this->rec_data['freq'] = 1;
            return $this;
        }

        $this->rec_data['type'] = $this->recurring;
        $this->rec_data['freq'] = isset($A['rec_freq']) ? (int)$A['rec_freq'] : 1;
        if ($this->rec_data['freq'] < 1) $this->rec_data['freq'] = 1;

        // Validate the user-supplied stopdate
        if (!empty($A['stopdate'])) {
            list($stop_y, $stop_m, $stop_d) = explode('-', $A['stopdate']);
            if (DateFunc::isValidDate($stop_d, $stop_m, $stop_y)) {
                $this->rec_data['stop'] = $A['stopdate'];
            }
        } else {
            $this->rec_data['stop'] = EV_MAX_DATE;
        }

                //switch ($this->rec_data['type']) {
        switch ($this->recurring) {
        case EV_RECUR_WEEKLY:
            if (isset($A['listdays']) && is_array($A['listdays'])) {
                $this->rec_data['listdays'] = array();
                foreach ($A['listdays'] as $day) {
                    $this->rec_data['listdays'][] = (int)$day;
                }
            }
            break;
        case EV_RECUR_MONTHLY:
            if (isset($A['mdays']) && is_array($A['mdays'])) {
                $this->rec_data['listdays'] = array();
                foreach ($A['mdays'] as $mday) {
                    $this->rec_data['listdays'][] = (int)$mday;
                }
            }
            // ... fall through to handle weekend skipping
        case EV_RECUR_DAILY:
        case EV_RECUR_YEARLY:
            // Set weekend skip- applies to Monthly, Daily and Yearly
            $this->rec_data['skip'] = isset($A['skipnext']) ?
                (int)$A['skipnext'] : 0;
            break;
        case EV_RECUR_DOM:
            $this->rec_data['weekday'] = (int)$A['weekday'];
            if (!isset($A['interval'])) {
                $A['interval'] = array(1);
            }
            $this->rec_data['interval'] = is_array($A['interval']) ?
                    $A['interval'] : array($A['interval']);
            break;
        case EV_RECUR_DATES:
            // Specific dates. Dates are space- or comma-delmited
            $recDates = preg_split('/[\s,]+/', $A['custom']);
            // keep them in order to minimize schedule-based changes.
            sort($recDates);
            $this->rec_data['custom'] = $recDates;
            break;
        default:
            // Unknown value, nothing to do
            break;
        }
        return $this;
    }


    /**
     * Get a friendly description of a recurring event's frequency.
     * Returns strings like "2 weeks", "month", "3 days", etc, which can
     * be used to create phrases like "occurs every 2 months".
     * This can be called as an object method or an api function by
     * supplying both of the optional parameters.
     *
     * @param   integer $freq       Frequency (number of intervals)
     * @param   integer $interval   Interval, one to six
     * @return  string      Friendly text describing the interval
     */
    public function RecurDscp($freq = '', $interval = '')
    {
        global $LANG_EVLIST;

        if (($freq == '' || $interval == '')) {
            $freq = $this->rec_data['freq'];
            $interval = $this->rec_data['type'];
        }

        $freq = (int)$freq;
        $interval = (int)$interval;
        if ($interval < EV_RECUR_DAILY || $interval > EV_RECUR_DATES) {
            $interval = EV_RECUR_DAILY;
        }
        if ($freq < 1) {
            $freq = 1;
        }
        $freq_str = '';

        // Create the recurring description.  Nothing for custom dates
        if ($interval < EV_RECUR_DATES) {
            //$Intervals = new Models\Intervals;
            //$freq_str = $Intervals->strOccursEvery($freq, $interval);
            if ($freq == 1) {
                $freq_str = $LANG_EVLIST['rec_period_dscp']['single'][$interval];
            } else {
                $freq_str = $freq . ' ' . $LANG_EVLIST['rec_period_dscp']['plural'][$interval];
            }
        }
        return $freq_str;
    }


    /**
     * Check if the current user is the owner of this event.
     *
     * @return  boolean     True if the user is the owner, False if not
     */
    public function isOwner()
    {
        global $_USER;
        return $this->owner_id == $_USER['uid'];
    }


    /**
     * Determine if the current user can edit this event.
     * Editing is allowed for:
     * - Moderators
     * - All owners if moderation is not required
     * - Owners who have the evlist.submit privilege
     * - Users and groups based on permissions matrix
     *
     * @return boolean     True if editing is allowed, False if not
     */
    public function canEdit() : bool
    {
        global $_CONF;

        $canedit = false;
        if ($this->isAdmin || plugin_ismoderator_evlist()) {
            $canedit = true;
        } elseif ($this->isOwner()) {
            // special check so owners subject to the submission queue
            // can't edit their own events.
            $canedit = EVLIST_skipqueue();
        } else {
            $canedit = $this->hasAccess(3);
        }
        return $canedit;
    }


    /**
     * Get the link to display an instance of the event.
     *
     * @param   integer $rp_id  Specific instance, 0 to fetch nearest upcoming
     * @return  string      URL to display link
     */
    public function getLink(int $rp_id=0) : string
    {
        if ($rp_id == 0) {
            $rp_id = Repeat::getNearest($this->id);
        }
        return EVLIST_URL . '/event.php?rp_id=' . $rp_id;
    }


    /**
     * Check if this is a new record.
     * Used to validate the retrieval of an instance.
     *
     * @return  boolean     True if new, False if existing
     */
    public function isNew() : bool
    {
        return $this->isNew ? true : false;
    }


    /**
     * Get the related Calendar object.
     *
     * @return  object      Calendar object
     */
    public function getCalendar()
    {
        if ($this->Calendar == NULL) {
            if ($this->cal_id < 1) {
                $this->cal_id = 1;
            }
            $this->Calendar = Calendar::getInstance($this->cal_id);
        }
        return $this->Calendar;
    }


    /**
     * Get the record ID for the detail record.
     *
     * @return  integer     Detail record ID
     */
    public function getDetailID()
    {
        return (int)$this->det_id;
    }


    /**
     * Get the posting mode.
     *
     * @return  string  Posting mode
     */
    public function getPostMode()
    {
        return $this->postmode;
    }


    /**
     * Check if this event is split (two parts each day).
     *
     * @return  boolean     1 if split, 0 if not
     */
    public function isSplit()
    {
        return $this->split ? 1 : 0;
    }


    /**
     * Get the recurrance type for the event (weekly, daily, etc.)
     *
     * @return  integer     Recurrance setting
     */
    public function getRecurring()
    {
        return (int)$this->recurring;
    }

    public function isRecurring()
    {
        return (int)$this->recurring != 0;
    }


    /**
     * Set the options for the event.
     *
     * @param   string|array    JSON string or array
     * @return  object  $this
     */
    public function setOptions($data) : self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (is_array($data)) {
            $this->options = new EventOptions($data);
        }
        return $this;
    }


    /**
     * Set the recurring data for the event.
     *
     * @param   string|array    Serizlized string or array
     * @return  object  $this
     */
    public function setRecData($data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (is_array($data)) {
            $this->rec_data = new RecurData($data);
        }
        /*if (!is_array($this->rec_data)) {
            $this->rec_data = array();
        }*/
        return $this;
    }


    /**
     * Get the recurrance data for the event.
     *
     * @return  array       Array of recurring information
     */
    public function getRecData()
    {
        return $this->rec_data;
    }


    /**
     * Check if this is an all-day event.
     *
     * @return  boolean     1 if all-day, 0 if timed
     */
    public function isAllDay()
    {
        return $this->allday ? 1 : 0;
    }


    /**
     * Get the owner's user ID.
     *
     * @return  integer     Owner user ID
     */
    public function getOwnerID()
    {
        return (int)$this->owner_id;
    }


    /**
     * Get the calendar ID related to this event.
     *
     * @return  integer     Calender record ID
     */
    public function getCalendarID()
    {
        return (int)$this->cal_id;
    }

    public function showUpcoming()
    {
        return (int)$this->show_upcoming;
    }


    /**
     * Check if reminders are enabled.
     *
     * @return  boolean     1 if enabled, 0 if not
     */
    public function remindersEnabled()
    {
        return $this->enable_reminders ? 1 : 0;
    }


    /**
     * Check if comments are enabled.
     *
     * @return  integer     Enabled status for comments
     */
    public function commentsEnabled() : int
    {
        global $_EV_CONF;

        if ($_EV_CONF['commentsupport']) {
            return (int)$this->enable_comments;
        } else {
            return self::CMT_DISABLED;
        }
    }


    /**
     * Get the detail information for this event.
     *
     * @return  object      Detail object
     */
    public function getDetail()
    {
        if ($this->Detail == NULL) {
            $this->Detail = Detail::getInstance($this->det_id);
        }
        return $this->Detail;
    }


    /**
     * Get the first starting date.
     *
     * @return  object      Starting date object
     */
    public function getStartDate1()
    {
        return $this->date_start1;
    }


    /**
     * Get the timezone used for this event.
     *
     * @return  string      Timezone identifier
     */
    public function getTZID() : string
    {
        return $this->tzid;
    }


    /**
     * Get the revision number.
     *
     * @return  integer     Revision ID
     */
    public function getRevision() : int
    {
        return (int)$this->ev_revision;
    }


    /**
     * See if the current user is allowed to register.
     *
     * @return  boolean     True if allowed, False if not.
     */
    public function userCanRegister() : bool
    {
        global $_EV_CONF;

        return (
            $_EV_CONF['enable_rsvp'] == 1 &&
            $this->getOption('use_rsvp') > 0 &&
            SEC_inGroup($this->getOption('rsvp_signup_grp'))
        );
    }

}
