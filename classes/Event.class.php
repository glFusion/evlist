<?php
/**
 * Class to manage events for the EvList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2019 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.6
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;


/**
 * Class for event records.
 * @package evlist
 */
class Event
{
    const MIN_DATETIME  = '1970-01-01 00:00:00';
    const MAX_DATETIME  = '2037-12-31 23:59:59';
    const MIN_DATE      = '1970-01-01';
    const MAX_DATE      = '2037-12-31';
    const MIN_TIME      = '00:00:00';
    const MAX_TIME      = '23:59:59';

    /** Event record ID.
     * @var string */
    private $id = '';

    /** Page views for this event.
     * @var integer */
    private $hits = 0;

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
    private $time_end1 = self::MAX_TIME;

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

        if ($ev_id == '') {
            $this->owner_id = $_USER['uid'];
            $this->enable_comments = $_EV_CONF['commentsupport'] ? 0 : 2;

            // Create dates & times based on individual URL parameters,
            // or defaults.
            // Start date/time defaults to now
            $startday1 = isset($_GET['day']) ? (int)$_GET['day'] : '';
            if ($startday1 < 1 || $startday1 > 31) {
                $startday1 = $_EV_CONF['_now']->format('j', true);
            }
            $startmonth1 = isset($_GET['month']) ? (int)$_GET['month'] : '';
            if ($startmonth1 < 1 || $startmonth1 > 12) {
                $startmonth1 = $_EV_CONF['_now']->format('n', true);
            }
            $startyear1 = isset($_GET['year']) ?
                    (int)$_GET['year'] : $_EV_CONF['_now']->format('Y', true);
            $starthour1 = isset($_GET['hour']) ?
                    (int)$_GET['hour'] : $_EV_CONF['_now']->format('H', true);
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
                "%02d:%02d:00",
                $starthour1, $startminute1
            );
            $this->time_start2 = sprintf(
                "%02d:%02d:00",
                $starthour2, $startminute2
            );
            $this->date_end1 = sprintf(
                "%4d-%02d-%02d",
                $endyear1, $endmonth1, $endday1
            );
            $this->time_end1 = sprintf("%02d:%02d:00", $endhour1, $endminute1);
            $this->time_end2 = sprintf("%02d:%02d:00", $endhour2, $endminute2);

            $this->perm_owner   = $_EV_CONF['default_permissions'][0];
            $this->perm_group   = $_EV_CONF['default_permissions'][1];
            $this->perm_members = $_EV_CONF['default_permissions'][2];
            $this->perm_anon    = $_EV_CONF['default_permissions'][3];
            $this->options      = array(
                'use_rsvp'   => 0,
                'max_rsvp'   => 0,
                'rsvp_cutoff' => 0,
                'rsvp_waitlist' => 0,
                'ticket_types' => array(),
                'contactlink' => '',
                'max_user_rsvp' => 1,
            );
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
    public static function getInstance($ev_id, $det_id = 0)
    {
        static $records = array();
        if (!array_key_exists($ev_id, $records)) {
            $key = 'event_' . $ev_id . '_' . $det_id;
            $records[$ev_id] = Cache::get($key);
            if ($records[$ev_id] === NULL) {
                $records[$ev_id] = new self($ev_id, $det_id);
                $tags = array(
                    'events',
                    'event_' . $ev_id,
                );
                Cache::set($key, $records[$ev_id], $tags);
            }
        }
        return $records[$ev_id];
    }


    /**
     * Set a property's value.
     *
     * @param   string  $var    Name of property to set.
     * @param   mixed   $value  New value for property.
     */
    public function X__set($var, $value='')
    {
        switch ($var) {
        case 'id':
            $this->properties[$var] = COM_SanitizeID($value, false);
            break;

        case 'hits':
        case 'owner_id':
        case 'group_id':
        case 'perm_owner':
        case 'perm_group':
        case 'perm_members':
        case 'perm_anon':
        case 'startyear1':
        case 'startyear2':
        case 'startmonth1':
        case 'startmonth2':
        case 'startday1':
        case 'startday2':
        case 'endyear1':
        case 'endyear2':
        case 'endmonth1':
        case 'endmonth2':
        case 'endday1':
        case 'endday2':
        case 'cal_id':
        case 'enable_comments':
        case 'recurring':
            // Integer values
            if ($value == '') $value = 0;
            $this->properties[$var] = (int)$value;
            break;

        case 'date_start1':
        case 'date_end1':
        case 'postmode':
        case 'tzid':
            // String values
            $this->properties[$var] = trim(COM_checkHTML($value));
            break;

        case 'time_start1':
        case 'time_start2':
            $this->properties[$var] = empty($value) ? self::MIN_TIME : trim($value);
            break;

        case 'time_end1':
        case 'time_end2':
            $this->properties[$var] = empty($value) ? self::MAX_TIME : trim($value);
            break;

        case 'status':
        case 'allday':
        case 'split':
        case 'enable_reminders':
        case 'show_upcoming':
            // Boolean values
            $this->properties[$var] = $value == 1 ? 1 : 0;
            break;

        case 'categories':
            if (is_array($value)) {
                $this->$var = $value;
            } else {
                $this->$var = explode(',', $value);
            }
            break;

        default:
            // Undefined values (do nothing)
            break;
        }
    }


    /**
     * Get the value of a property.
     *
     * @param   string  $var    Name of property to retrieve.
     * @return  mixed           Value of property, NULL if undefined.
     */
    public function X__get($var)
    {
        if (array_key_exists($var, $this->properties)) {
            return $this->properties[$var];
        } else {
            return NULL;
        }
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
    public function setOwner($id=0)
    {
        global $_USER;

        if ($id == 0) $id = $_USER['uid'];
        $this->owner_id = (int)$id;
        return $this;
    }


    /**
     * Sanitize and set the group ID.
     *
     * @param   integer $id     Group ID
     * @return  object  $this
     */
    public function setGroup($id)
    {
        $this->group_id = (int)$id;
        return $this;
    }


    /**
     * Set the owner permission.
     *
     * @param   integer $perm   Permission value, -1 for default
     */
    public function setPermOwner($perm = -1)
    {
        global $_EV_CONF;

        if ($perm == -1) $perm = $_EV_CONF['default_permissions'][0];
        $this->perm_owner = (int)$perm;
        return $this;
    }


    /**
     * Set the group permission.
     *
     * @param   integer $perm   Permission value, -1 for default
     */
    public function setPermGroup($perm = -1)
    {
        global $_EV_CONF;

        if ($perm == -1) $perm = $_EV_CONF['default_permissions'][1];
        $this->perm_group = (int)$perm;
        return $this;
    }


    /**
     * Set the member permission.
     *
     * @param   integer $perm   Permission value, -1 for default
     */
    public function setPermMembers($perm = -1)
    {
        global $_EV_CONF;

        if ($perm == -1) $perm = $_EV_CONF['default_permissions'][2];
        $this->perm_member = (int)$perm;
        return $this;
    }


    /**
     * Set the anonymous permission.
     *
     * @param   integer $perm   Permission value, -1 for default
     */
    public function setPermAnon($perm = -1)
    {
        global $_EV_CONF;

        if ($perm == -1) $perm = $_EV_CONF['default_permissions'][3];
        $this->perm_anon = (int)$perm;
        return $this;
    }


    /**
     * Get the options set for the event.
     *
     * @return  array   Array of all options
     */
    public function getOptions()
    {
        return $this->options;
    }


    /**
     * Get a single option value, NULL if not set.
     *
     * @return  mixed   Single value from the options array
     */
    public function getOption($key)
    {
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        } else {
            return NULL;
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
    public function setTable($submission=true)
    {
        $this->table = $submission ? 'evlist_submissions' : 'evlist_events';
        return $this;
    }


    public function getDateStart1()
    {
        return $this->date_start1;
    }


    public function getDateEnd1()
    {
        return $this->date_end1;
    }


    public function getTimeStart1()
    {
        return $this->time_start1;
    }

    public function getTimeEnd1()
    {
        return $this->time_end1;
    }

    public function getTimeStart2()
    {
        return $this->time_end2;
    }

    public function getTimeEnd2()
    {
        return $this->time_end2;
    }


    /**
     * Sets all variables to the matching values from $rows.
     *
     * @param   array   $row        Array of values, from DB or $_POST
     * @param   boolean $fromDB     True if read from DB, false if from $_POST
     */
    public function SetVars($row, $fromDB=false)
    {
        global $_EV_CONF;

        if (!is_array($row)) return;

        $this->date_start1 = (isset($row['date_start1']) &&
            !empty($row['date_start1'])) ? $row['date_start1'] : date('Y-m-d');
        $this->date_end1 = (isset($row['date_end1']) &&
            !empty($row['date_end1'])) ? $row['date_end1'] : $this->date_start1;
        $this->cal_id = $row['cal_id'];
        $this->show_upcoming = isset($row['show_upcoming']) ? (int)$row['show_upcoming'] : 0;
        $this->recurring = (int)$row['recurring'];
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

        $this->status = isset($row['status']) && $row['status'] == 1 ? 1 : 0;
        $this->postmode = isset($row['postmode']) &&
                $row['postmode'] == 'html' ? 'html' : 'plaintext';
        $this->enable_reminders = isset($row['enable_reminders']) &&
                $row['enable_reminders'] == 1 ? 1 : 0;
        $this->setOwner(isset($row['owner_id']) ? $row['owner_id'] : 2);
        $this->setGroup(isset($row['group_id']) ? $row['group_id'] : 13);
        $this->enable_comments = isset($row['enable_comments']) ? $row['enable_comments'] : 0;


        // Categores get added to the row during Read if from a DB, or as part
        // of the posted form.
        $this->setCategories(EV_getVar($row, 'categories', 'array', array()));

        // Join or split the date values as needed
        if ($fromDB) {
            // dates are YYYY-MM-DD
            $this->setID(isset($row['id']) ? $row['id'] : '');
            $this->setRecData($row['rec_data']);
            $this->det_id = (int)$row['det_id'];
            $this->hits = (int)$row['hits'];
            $this->setPermOwner($row['perm_owner'])
                ->setPermGroup($row['perm_group'])
                ->setPermMembers($row['perm_members'])
                ->setPermAnon($row['perm_anon']);
            $this->time_start1 = $row['time_start1'];
            $this->time_end1 = $row['time_end1'];
            $this->time_start2 = $row['time_start2'];
            $this->time_end2 = $row['time_end2'];
            $this->options = @unserialize($row['options']);
            if (!$this->options) {
                $this->options = array();
            }
            $this->tzid = $row['tzid'];
        } else {        // Coming from the form
            $this->id = isset($row['eid']) ? $row['eid'] : '';
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
                $this->time_start2 = NULL;
                $this->time_end2 = NULL;
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
                    $this->options['tickets'][$tick_id] = array(
                        'fee' => $tick_fee,
                    );
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
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @param   integer $ev_id  Optional ID.  Current ID is used if zero.
     * @param   string  $table  Table name, default = production
     * @return  boolean     True if a record was read, False on failure.
     */
    public function Read($ev_id = '', $submissions=false)
    {
        global $_TABLES;

        if ($ev_id != '') {
            $this->id = COM_sanitizeID($ev_id);
        }

        $this->setTable($submissions);
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
     * Save the current values to the database.
     * Appends error messages to the $Errors property.
     *
     * The $forceNew parameter is a hack to force this record to be saved
     * as a new record even if it already has an ID.  This is only to
     * handle events imported from the Calendar plugin.
     *
     * @param   array   $A      Optional array of values from $_POST
     * @param   boolean $isSubmission   True if this uses the submission table
     * @param   boolean $forceNew   Hack to force this record to be "new"
     * @return  string      Error text, or empty string on success
     */
    public function Save($A = '', $isSubmission = true, $forceNew=false)
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

        // Now we can update our main record with the new info
        if (is_array($A)) {
            $this->SetVars($A);
            $this->MakeRecData($A);
            DB_delete($_TABLES['evlist_lookup'], 'eid', $this->id);
        }

        /*if (isset($A['eid']) && !empty($A['eid']) && !$forceNew) {
            $this->isNew = false;
        }*/

        // Authorized to bypass the queue
        if ($this->isAdmin || plugin_ismoderator_evlist()) {
            $this->setTable(false);
        } else {
            $this->setTable($isSubmission);
        }

        if ($this->id == '') {
            // If we allow users to create IDs, this could happen
            $this->id = COM_makesid();
        }

        $ev_id_DB = DB_escapeString($this->id);   // Used often, sanitize now

        // Insert or update the record, as appropriate
        if (!$this->isNew) {

            // Existing event, we already have a Detail object instantiated
            $this->Detail->setVars($A);
            //$this->Detail->setEventID($this->id);
            if (!$this->isValidRecord()) {
                return $this->PrintErrors();
            }
            // Delete the category lookups
            DB_delete($_TABLES['evlist_lookup'], 'eid', $this->id);

            // Save the main event record
            $sql1 = "UPDATE {$_TABLES[$this->table]} SET ";
            $sql2 = "WHERE id='$ev_id_DB'";

            // Save the new detail record & get the ID
            $this->det_id = $this->Detail->Save();

            // Quit now if the detail record failed
            if ($this->det_id == 0) return false;

            // Determine if the schedule has changed so that we need to
            // update the repeat tables.  If we do, any customizations will
            // be lost.
            if ($this->NeedRepeatUpdate($A)) {
                if ($this->old_schedule['recurring'] || $this->recurring) {
                    // If this was, or is now, a recurring event then clear
                    // out the repeats and update with new ones.
                    // First, delete all detail records except the master
                    DB_query("DELETE FROM {$_TABLES['evlist_detail']}
                            WHERE ev_id = '{$this->id}'
                            AND det_id <> '{$this->det_id}'");
                    // This function sets the rec_data value.
                    if (!$this->UpdateRepeats()) {
                        return $this->PrintErrors();
                    }
                } else {
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
                            rp_end = CONCAT('{$this->date_end1}' , ' ' , '$t_end')
                        WHERE rp_ev_id = '{$this->id}'";
                    DB_query($sql, 1);
                }
            }

        } else {
            // New event
            if (!$this->isAdmin) {
                // Override any submitted permissions if user is not an admin
                $this->setPermOwner()
                    ->setPermGroup()
                    ->setPermMembers()
                    ->setPermAnon()
                    ->setGroup(DB_getItem(
                        $_TABLES['groups'],
                        'grp_id',
                        'grp_name="evList Admin"'
                    ) )
                    // Set the owner to the submitter
                    ->setOwner();
            }

            // Create a detail record
            $this->Detail = new Detail();
            $this->Detail->setVars($A);
            $this->Detail->setEventID($this->id);
            if (!$this->isValidRecord()) {
                return $this->PrintErrors();
            }

            // Save the new detail record & get the ID
            $this->det_id = $this->Detail->Save();

            // Quit now if the detail record failed
            if ($this->det_id == 0) return false;

            if (!$this->isSubmission()) {
                // This function gets the rec_data value.
                if (!$this->UpdateRepeats()) {
                    return $this->PrintErrors();
                }
            }

            $sql1 = "INSERT INTO {$_TABLES[$this->table]} SET
                    id = '" . DB_escapeString($this->id) . "', ";
            $sql2 = '';
        }

        if (!$_EV_CONF['commentsupport']) $this->enable_comments = 2;

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
            rec_data = '" . DB_escapeString(serialize($this->rec_data)) . "',
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
            options = '" . DB_escapeString(serialize($this->options)) . "' ";

        $sql = $sql1 . $fld_sql . $sql2;
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            $this->Errors[] = $LANG_EVLIST['err_db_saving'];
        } elseif (
            $this->isSubmission() &&
            isset($_CONF['notification']) &&
            in_array ('evlist', $_CONF['notification'])
        ) {
            $N = new \Template(EVLIST_PI_PATH . '/templates');
            $N->set_file('mail', 'notify_submission.thtml');
            $N->set_var(array(
                'title'     => $this->Detail->getTitle(),
                'summary'   => $this->Detail->getSummary(),
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
            if (!$this->isSubmission()) {
                Cache::clear('events');
                PLG_itemSaved($this->id, 'evlist');
            }
            return '';
        } else {
            return $this->PrintErrors();
        }
    }


    /**
     * Delete the specified event record and all repeats.
     *
     * @param   integer $eid    Event ID
     * @return      True on success, False on failure
     */
    public static function Delete($eid)
    {
        global $_TABLES, $_PP_CONF;

        if ($eid == '') {
            return false;
        }

        // Make sure the current user has access to delete this event
        $sql = "SELECT id FROM {$_TABLES['evlist_events']}
                WHERE id='$eid' " . COM_getPermSQL('AND', 0, 3);
        $res = DB_query($sql);
        if (!$res || DB_numRows($res) != 1) {
            return false;
        }

        DB_delete($_TABLES['evlist_remlookup'], 'eid', $eid);
        DB_delete($_TABLES['evlist_lookup'], 'eid', $eid);
        DB_delete($_TABLES['evlist_tickets'], 'ev_id', $eid);
        DB_delete($_TABLES['evlist_repeat'], 'rp_ev_id', $eid);
        DB_delete($_TABLES['evlist_detail'], 'ev_id', $eid);
        DB_delete($_TABLES['evlist_events'], 'id', $eid);
        PLG_itemDeleted($eid, 'evlist');
        Cache::clear();
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
        if ($this->Detail->getTitle() == '') {
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
     * Creates the edit form.
     *
     * @param   string  $eid    Optional Event ID, current record used if zero
     * @param   integer $rp_id  Optional Repeat ID
     * @param   string  $saveaction     Action when saving
     * @return  string      HTML for edit form
     */
    public function Edit($eid = '', $rp_id = 0, $saveaction = '')
    {
        global $_CONF, $_EV_CONF, $_TABLES, $_USER, $LANG_EVLIST,
                $LANG_ADMIN, $_GROUPS, $LANG_ACCESS, $_SYSTEM;

        // If an eid is specified and this is an object, then read the
        // event data- UNLESS a repeat ID is given in which case we're
        // editing a repeat and already have the info we need.
        // This probably needs to change, since we should always read event
        // data during construction.
        if (!EVLIST_canSubmit()) {
            // At least submit privilege required
            COM_404();
        } elseif ($eid != ''  && $rp_id == 0) {
            // If an id is passed in, then read that record
            if (!$this->Read($eid)) {
                return 'Invalid object ID';
            }
        } elseif (isset($_POST['eid']) && !empty($_POST['eid'])) {
            // Returning to an existing form, probably due to errors
            $this->SetVars($_POST);

            // Make sure the current user has access to this event.
            if (!$this->hasAccess(3)) COM_404();
        }

        if (!$this->isNew && !plugin_ismoderator_evlist()) {
            COM_404();
        }

        $T = new \Template(EVLIST_PI_PATH . '/templates');
        $T->set_file(array(
            'editor'    => 'editor.thtml',
            'tips'      => 'tooltipster.thtml',
        ) );

        // Set up the wysiwyg editor, if available
        switch (PLG_getEditorType()) {
        case 'ckeditor':
            $T->set_var('show_htmleditor', true);
            PLG_requestEditor('evlist','evlist_entry','ckeditor_evlist.thtml');
            PLG_templateSetVars('evlist_entry', $T);
            break;
        case 'tinymce' :
            $T->set_var('show_htmleditor',true);
            PLG_requestEditor('evlist','evlist_entry','tinymce_evlist.thtml');
            PLG_templateSetVars('evlist_entry', $T);
            break;
        default :
            // don't support others right now
            $T->set_var('show_htmleditor', false);
            break;
        }

        if (
            isset($this->rec_data['stop']) &&
            !empty($this->rec_data['stop'])
        ) {
            $T->set_var(array(
                'stopdate'      => $this->rec_data['stop'],
            ) );
        }

        // Set up the recurring options needed for the current event
        $recweekday  = '';
        switch ($this->recurring) {
        case 0:
            // Not a recurring event
            break;
        case EV_RECUR_MONTHLY:
            if (is_array($this->rec_data['listdays'])) {
                foreach ($this->rec_data['listdays'] as $mday) {
                    $T->set_var('mdchk'.$mday, EVCHECKED);
                }
            }
            break;
        case EV_RECUR_WEEKLY:
            //$T->set_var('listdays_val', COM_stripslashes($rec_data[0]));
            if (is_array($this->rec_data['listdays']) &&
                    !empty($this->rec_data['listdays'])) {
                foreach($this->rec_data['listdays'] as $day) {
                    $day = (int)$day;
                    if ($day > 0 && $day < 8) {
                        $T->set_var('daychk'.$day, EVCHECKED);
                    }
                }
            }
            break;
        case EV_RECUR_DOM:
            $recweekday = $this->rec_data['weekday'];
            break;
        case EV_RECUR_DATES:
            $T->set_var(array(
                'stopshow'      => 'style="display:none;"',
                'custom_val' => implode(',', $this->rec_data['custom']),
            ) );
            break;
        }

        // Basic tabs for editing both events and instances, show up on
        // all edit forms
        $tabs = array('ev_info', 'ev_location', 'ev_contact',);
        $alert_msg = '';
        $rp_id = (int)$rp_id;
        if ($rp_id > 0) {   // Editing a single occurrence
            // Make sure the current user has access to this event.
            if (!$this->hasAccess(3)) COM_404();

            if ($saveaction == 'savefuturerepeat') {
                $alert_msg = EVLIST_alertMessage($LANG_EVLIST['editing_future'],
                        'warning');
            } else {
                $alert_msg = EVLIST_alertMessage($LANG_EVLIST['editing_instance'],
                        'info');
            }

            //$T->clear_var('contact_section');
            $T->clear_var('category_section');
            $T->clear_var('permissions_editor');

            // Set the static calendar name for the edit form.  Can't
            // change it for a single instance.
            $cal_name = DB_getItem($_TABLES['evlist_calendars'], 'cal_name',
                "cal_id='" . (int)$this->cal_id . "'");

            $T->set_var(array(
                'contact_section' => 'true',
                'is_repeat'     => 'true',    // tell the template it's a repeat
                'cal_name'      => $cal_name,
            ) );

            // Override our dates & times with those from the repeat.
            // $rp_id is passed when this is called from class Repeat.
            // Maybe that should pass in the repeat's data instead to avoid
            // another DB lookup.  An array of values could be used.
            $Rep = DB_fetchArray(DB_query("SELECT *
                    FROM {$_TABLES['evlist_repeat']}
                    WHERE rp_id='$rp_id'"), false);
            if ($Rep) {
                $this->date_start1 = $Rep['rp_date_start'];
                $this->date_end1 = $Rep['rp_date_end'];
                $this->time_start1 = $Rep['rp_time_start1'];
                $this->time_end1 = $Rep['rp_time_end1'];
                $this->time_start2 = $Rep['rp_time_start2'];
                $this->time_end2 = $Rep['rp_time_end2'];
            }

        } else {            // Editing the main event record

            if ($this->id != '' && $this->recurring == 1) {
                $alert_msg = EVLIST_alertMessage($LANG_EVLIST['editing_series'],
                    'error');
            }
            if ($this->isAdmin) {
                $tabs[] = 'ev_perms';   // Add permissions tab, event edit only
                $T->set_var('permissions_editor', 'true');
            }
            $T->set_var(array(
                'recurring' => $this->recurring,
                'recur_section' => 'true',
                'contact_section' => 'true',
                'category_section' => 'true',
                'upcoming_chk' => $this->show_upcoming ? EVCHECKED : '',
                'enable_reminders' => $_EV_CONF['enable_reminders'],
                'rem_status_checked' => $this->enable_reminders == 1 ?
                        EVCHECKED : '',
                'commentsupport' => $_EV_CONF['commentsupport'],
                'ena_cmt_' . $this->enable_comments => 'selected="selected"',
                'recurring_format_options' =>
                        EVLIST_GetOptions($LANG_EVLIST['rec_formats'], $this->recurring),
                'recurring_weekday_options' => EVLIST_GetOptions(DateFunc::getWeekDays(), $recweekday, 1),
                'dailystop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['day_by_date'], ''),
                'monthlystop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['year_and_month'], $LANG_EVLIST['if_any']),
                'yearlystop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['year'], $LANG_EVLIST['if_any']),
                'listdays_label' => sprintf($LANG_EVLIST['custom_label'],
                        $LANG_EVLIST['days_of_week'], ''),
                'listdaystop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['date_l'], $LANG_EVLIST['if_any']),
                'intervalstop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['year_and_month'], $LANG_EVLIST['if_any']),
                'custom_label' => sprintf($LANG_EVLIST['custom_label'],
                        $LANG_EVLIST['dates'], ''),
                'datestart_note' => $LANG_EVLIST['datestart_note'],
                'help_url' => EVLIST_getDocURL('event'),
            ) );
        }

        $action_url = $this->isAdmin ? EVLIST_ADMIN_URL . '/index.php' : EVLIST_URL . '/event.php';
        $delaction = 'delevent';
        if (EVLIST_checkReturn()) {
            $cancel_url = EVLIST_getReturn();
        } elseif (isset($_GET['from']) && $_GET['from'] == 'admin') {
            $cancel_url = EVLIST_ADMIN_URL . '/index.php';
        } else {
            $cancel_url = EVLIST_URL . '/index.php';
        }
        switch ($saveaction) {
        case 'saverepeat':
        case 'savefuturerepeat':
        case 'saveevent':
            break;
        case 'moderate':
            // Approving a submission
            $saveaction = 'approve';
            $delaction = 'disapprove';
            $action_url = EVLIST_ADMIN_URL . '/index.php';
            $cancel_url = $_CONF['site_admin_url'] . '/moderation.php';
            break;
        default:
            $saveaction = 'saveevent';
            break;
        }

        $retval = '';
        //$recinterval = '';

        $ownerusername = DB_getItem($_TABLES['users'],
                    'username', "uid='{$this->owner_id}'");

        $retval .= COM_startBlock($LANG_EVLIST['event_editor']);
        $summary = $this->Detail->getSummary();
        $full_description = $this->Detail->getDscp();
        $location = $this->Detail->getLocation();
        if (($this->isAdmin ||
                ($_EV_CONF['allow_html'] == '1' && $_USER['uid'] > 1))
                && $this->postmode == 'html') {
            $postmode = '2';      //html
        } else {
            $postmode = '1';            //plaintext
            $summary = htmlspecialchars(
                COM_undoClickableLinks(
                    COM_undoSpecialChars(
                        $this->Detail->getSummary()
                    )
                )
            );
            $full_description = htmlspecialchars(
                COM_undoClickableLinks(
                    COM_undoSpecialChars(
                        $this->Detail->getDscp()
                    )
                )
            );
            $location = htmlspecialchars(
                COM_undoClickableLinks(
                    COM_undoSpecialChars(
                        $this->Detail->getLocation()
                    )
                )
            );
         }

        $starthour2 = '';
        $startminute2 = '';
        $endhour2 = '';
        $endminute2 = '';

        if ($this->date_end1 == '' || $this->date_end1 == '0000-00-00') {
            $this->date_end1 = $this->date_start1;
        }
        if ($this->date_start1 != '' && $this->date_start1 != '0000-00-00') {
            list($startmonth1, $startday1, $startyear1,
                $starthour1, $startminute1) =
                $this->DateParts($this->date_start1, $this->time_start1);
        } else {
            list($startmonth1, $startday1, $startyear1,
                $starthour1, $startminute1) =
                $this->DateParts(date('Y-m-d', time()), date('H:i:s', time()));
        }

        // The end date can't be before the start date
        if ($this->date_end1 >= $this->date_start1) {
            list($endmonth1, $endday1, $endyear1,
                    $endhour1, $endminute1) =
                    $this->DateParts($this->date_end1, $this->time_end1);
            $days_interval = DateFunc::dateDiff(
                    $endday1, $endmonth1, $endyear1,
                    $startday1, $startmonth1, $startyear1);
        } else {
            $days_interval = 0;
            $endmonth1  = $startmonth1;
            $endday1    = $startday1;
            $endyear1   = $startyear1;
            $endhour1   = $starthour1;
            $endminute1 = $startminute1;
        }

        // Skip weekends. Default to "no" if not already set for this event
        $skip = empty($this->rec_data['skip']) ? 0 : $this->rec_data['skip'];

        if (!empty($this->rec_data['freq'])) {
            $freq = (int)$this->rec_data['freq'];
            if ($freq < 1) $freq = 1;
        } else {
            $freq = 1;
        }
        $T->set_var(array(
            'freq_text' => $LANG_EVLIST['rec_periods'][$this->recurring],
            'rec_freq'  => $freq,
            "skipnext{$skip}_checked" => EVCHECKED,
        ) );

        foreach ($LANG_EVLIST['rec_intervals'] as $key=>$str) {
            $T->set_var('dom_int_txt_' . $key, $str);
            if (isset($this->rec_data['interval']) &&
                    is_array($this->rec_data['interval'])) {
                if (in_array($key, $this->rec_data['interval'])) {
                    $T->set_var('dom_int_chk_'.$key, EVCHECKED);
                }
            }
        }

        $start1 = DateFunc::TimeSelect('start1', $this->time_start1);
        $start2 = DateFunc::TimeSelect('start2', $this->time_start2);
        $end1 = DateFunc::TimeSelect('end1', $this->time_end1);
        $end2 = DateFunc::TimeSelect('end2', $this->time_end2);
        $cal_select = Calendar::optionList($this->cal_id, true, 3);
        $navbar = new \navbar;
        $cnt = 0;
        foreach ($tabs as $id) {
            $navbar->add_menuitem($LANG_EVLIST[$id],'showhideEventDiv("'.$id.'",'.$cnt.');return false;',true);
            $cnt++;
        }
        $navbar->set_selected($LANG_EVLIST['ev_info']);

        $T->set_var(array(
            'is_admin'      => $this->isAdmin,
            'action_url'    => $action_url,
            'navbar'        => $navbar->generate(),
            'alert_msg'     => $alert_msg,
            'cancel_url'    => $cancel_url,
            'eid'           => $this->id,
            'rp_id'         => $rp_id,
            'title'         => $this->Detail->getTitle(),
            'summary'       => $summary,
            'description'   => $full_description,
            'location'      => $location,
            'status_checked' => $this->status == 1 ? EVCHECKED : '',
            'url'           => $this->Detail->getUrl(),
            'street'        => $this->Detail->getStreet(),
            'city'          => $this->Detail->getCity(),
            'province'      => $this->Detail->getProvince(),
            'country'       => $this->Detail->getCountry(),
            'postal'        => $this->Detail->getPostal(),
            'contact'       => $this->Detail->getContact(),
            'email'         => $this->Detail->getEmail(),
            'phone'         => $this->Detail->getPhone(),
            'startdate1'    => $this->date_start1,
            'enddate1'      => $this->date_end1,
            'd_startdate1'  => EVLIST_formattedDate($this->date_start1),
            'd_enddate1'    => EVLIST_formattedDate($this->date_end1),
            // Don't need seconds in the time boxes
            'hour_mode'     => $_CONF['hour_mode'],
            /*'time_start1'   => DateFunc::conv24to12($this->time_start1),
            'time_end1'     => DateFunc::conv24to12($this->time_end1),
            'time_start2'   => DateFunc::conv24to12($this->time_start2),
            'time_end2'     => DateFunc::conv24to12($this->time_end2),*/
            'time_start1'   => $this->time_start1,
            'time_end1'     => $this->time_end1,
            'time_start2'   => $this->time_start2,
            'time_end2'     => $this->time_end2,
            'start_hour_options1'   => $start1['hour'],
            'start_minute_options1' => $start1['minute'],
            'startdate1_ampm'       => $start1['ampm'],
            'end_hour_options1'     => $end1['hour'],
            'end_minute_options1'   => $end1['minute'],
            'enddate1_ampm'         => $end1['ampm'],
            'start_hour_options2'   => $start2['hour'],
            'start_minute_options2' => $start2['minute'],
            'startdate2_ampm'       => $start2['ampm'],
            'end_hour_options2'     => $end2['hour'],
            'end_minute_options2'   => $end2['minute'],
            'enddate2_ampm'         => $end2['ampm'],
            'src'   => isset($_GET['src']) && $_GET['src'] == 'a' ? '1' : '0',

            'del_button'    => $this->id == '' ? '' : 'true',
            'saveaction'    => $saveaction,
            'delaction'     => $delaction,
            'owner_id'      => $this->owner_id,
            'days_interval' => $days_interval,
            'display_format' => $_CONF['shortdate'],
            'ts_start'      => strtotime($this->date_start1),
            'ts_end'        => strtotime($this->date_end1),
            'cal_select'    => $cal_select,
            'contactlink_chk' => $this->options['contactlink'] == 1 ?
                                EVCHECKED : '',
            'lat'           => EVLIST_coord2str($this->Detail->getLatitude()),
            'lng'           => EVLIST_coord2str($this->Detail->getLongitude()),
            'perm_msg'      => $LANG_ACCESS['permmsg'],
            'last'          => $LANG_EVLIST['rec_intervals'][5],
            'doc_url'       => EVLIST_getDocURL('event'),
            // If the event timezone is "local", just use some valid timezone
            // for the selection. The checkbox will be checked which will
            // hide the timezone selection anyway.
            'tz_select'     => \Date::getTimeZoneDropDown(
                        $this->tzid == 'local' ? $_CONF['timezone'] : $this->tzid,
                        array('id' => 'tzid', 'name' => 'tzid')),
            'tz_islocal'    => $this->tzid == 'local' ? EVCHECKED : '',
            'isNew'         => (int)$this->isNew,
            'fomat_opt'     => $this->recurring,
        ) );

        if ($_EV_CONF['enable_rsvp'] && $rp_id == 0) {
            $TickTypes = TicketType::GetTicketTypes();
            //$T->set_block('editor', 'Tickets', 'tTypes');
            $tick_opts = '';
            foreach ($TickTypes as $tick_id=>$tick_obj) {
                // Check enabled tickets. Ticket type 1 enabled by default
                if (isset($this->options['tickets'][$tick_id]) || $tick_id == 1) {
                    $checked = 'checked="checked"';
                    if (isset($this->options['tickets'][$tick_id])) {
                        $fee = (float)$this->options['tickets'][$tick_id]['fee'];
                    } else {
                        $fee = 0;
                    }
                } else {
                    $checked = '';
                    $fee = 0;
                }
                $tick_opts .= '<tr><td><input name="tickets[' . $tick_id .
                    ']" type="checkbox" ' . $checked .
                    ' value="' . $tick_id . '" /></td>' .
                    '<td>' . $tick_obj->getDscp() . '</td>' .
                    '<td><input type="text" name="tick_fees[' . $tick_id .
                    ']" value="' . $fee . '" size="8" /></td></tr>' . LB;
                /*$T->set_var(array(
                    'tick_id' => $tic['id'],
                    'tick_desc' => $tic['description'],
                    'tick_fee' => $fee,
                    'tick_enabled' => $enabled ? 'checked="checked"' : '',
                ) ) ;
                //$T->parse('tTypes', 'Tickets', true);*/
            }

            if ($_EV_CONF['rsvp_print'] > 0) {
                $rsvp_print_chk  = 'rsvp_print_chk' . $this->options['rsvp_print'];
                $rsvp_print = 'true';
            } else {
                $rsvp_print = '';
                $rsvp_print_chk = 'no_rsvp_print';
            }

            $T->set_var(array(
                'enable_rsvp' => 'true',
                'reg_chk'.$this->options['use_rsvp'] => EVCHECKED,
                'rsvp_wait_chk' => $this->options['rsvp_waitlist'] == 1 ?
                                EVCHECKED : '',
                'max_rsvp'   => $this->options['max_rsvp'],
                'max_user_rsvp' => $this->options['max_user_rsvp'],
                'rsvp_cutoff' => $this->options['rsvp_cutoff'],
                'use_rsvp' => $this->options['use_rsvp'], // for javascript
                'rsvp_waitlist' => $this->options['rsvp_waitlist'],
                'tick_opts'     => $tick_opts,
                'rsvp_print'    => $rsvp_print,
                $rsvp_print_chk => 'checked="checked"',
            ) );

        }   // if rsvp_enabled

        // Split & All-Day settings
        if ($this->allday == 1) {   // allday, can't be split, no times
            $T->set_var(array(
                'starttime1_show'   => 'style="display:none;"',
                'endtime1_show'     => 'style="display:none;"',
                'datetime2_show'    => 'style="display:none;"',
                'allday_checked'    => EVCHECKED,
                'split_checked'     => '',
                'split_show'        => 'style="display:none;"',
            ) );
        } elseif ($this->split == '1') {
            $T->set_var(array(
                'split_checked'     => EVCHECKED,
                'allday_checked'    => '',
                'allday_show'       => 'style="display:none"',
            ) );
        } else {
            $T->set_var(array(
                'datetime2_show'    => 'style="display:none;"',
            ) );
        }

        // Category fields. If $_POST['categories'] is set, then this is a
        // form re-entry due to an error saving. Populate checkboxes from the
        // submitted form. Include the user-added category, if any.
        // If not from a form re-entry, get the checked categories from the
        // evlist_lookup table.
        // Both "select" and "checkbox"-type values are supplied so the
        // can use either form element.
        if ($_EV_CONF['enable_categories'] == '1') {
            $cresult = DB_query("SELECT tc.id, tc.name
                FROM {$_TABLES['evlist_categories']} tc
                WHERE tc.status='1' ORDER BY tc.name");
            $T->set_block('editor', 'catSelect', 'catSel');
            $catlist = '';
            while ($A = DB_fetchArray($cresult, false)) {
                if (isset($_POST['categories']) && is_array($_POST['categories'])) {
                    // Coming from a form re-entry
                    $cat_array = $_POST['categories'];
                } else {
                    $cat_array = $this->categories;
                }
                if (in_array($A['id'], $cat_array)) {
                    // category is currently selected
                    $chk = EVCHECKED;
                    $sel = EVSELECTED;
                } else {
                    $chk = '';
                    $sel = '';
                }
                $catlist .= '<input type="checkbox" name="categories[]" ' .
                    'value="' . $A['id'] . '" ' . $chk . ' />' .
                    '&nbsp;' . $A['name'] . '&nbsp;&nbsp;';
                $T->set_var(array(
                    'cat_id'    => $A['id'],
                    'cat_name'  => htmlspecialchars($A['name']),
                    'cat_chk'   => $chk,
                    'cat_sel'   => $sel,
                ) );
                $T->parse('catSel', 'catSelect', true);
            }
            $T->set_var('catlist', $catlist);

            if (isset($_POST['newcat'])) {
                $T->set_var('newcat', $_POST['newcat']);
            }

            if ($_USER['uid'] > 1 && $rp_id == 0) {
                $T->set_var('category_section', 'true');
                $T->set_var('add_cat_input', 'true');
            }
        }

        // Enable the post mode selector if we allow HTML and the user is
        // logged in, or if this user is an authorized editor
        if ($this->isAdmin ||
                ($_EV_CONF['allow_html'] == '1' && $_USER['uid'] > 1)) {
            $T->set_var(array(
                'postmode_options' => EVLIST_GetOptions($LANG_EVLIST['postmodes'], $postmode),
                'allowed_html' => COM_allowedHTML('evlist.submit'),
            ));

            if ($postmode == 'plaintext') {
                // plaintext, hide postmode selector
                $T->set_var('postmode_show', ' style="display:none"');
            }
            $T->parse('event_postmode', 'edit_postmode');
        }

        if ($this->isAdmin) {
            $T->set_var(array(
                'owner_username' => COM_stripslashes($ownerusername),
                'owner_dropdown' => COM_optionList($_TABLES['users'],
                        'uid,username', $this->owner_id, 1),
                'group_dropdown' => SEC_getGroupDropdown ($this->group_id, 3),
            ) );
            if ($rp_id == 0) {  // can only change permissions on main event
                $T->set_var('permissions_editor', SEC_getPermissionsHTML(
                        $this->perm_owner, $this->perm_group,
                        $this->perm_members, $this->perm_anon));
            }

        } else {
            $T->set_var('group_id', $this->group_id);
        }

        // Latitude & Longitude part of location, if Location plugin is used
        if ($_EV_CONF['use_locator']) {
            $status = LGLIB_invokeService('locator', 'optionList', '',
                $output, $svc_msg);
            if ($status == PLG_RET_OK) {
                $T->set_var(array(
                    'use_locator'   => 'true',
                    'loc_selection' => $output,
                ) );
            }
        }
        $T->parse('tooltipster_js', 'tips');
        $T->parse('output', 'editor');
        $retval .= $T->finish($T->get_var('output'));

        $retval .= COM_endBlock();
        return $retval;
    }   // function Edit()


    /**
     * Toggles a field to the opposite of the existing value.
     *
     * @param   integer $oldvalue   Original value
     * @param   string  $varname    DB field name to toggle
     * @param   string  $ev_id      Event record ID
     * @return  integer     New value, or old value upon failure
     */
    private static function _toggle($oldvalue, $varname, $ev_id)
    {
        global $_TABLES;

        $ev_id = COM_sanitizeID($ev_id, false);
        if ($ev_id == '') return $oldvalue;
        $oldvalue = $oldvalue == 0 ? 0 : 1;
        $newvalue = $oldvalue == 1 ? 0 : 1;
        $sql = "UPDATE {$_TABLES['evlist_events']}
                SET $varname=$newvalue
                WHERE id='" . DB_escapeString($ev_id) . "'";
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("Event::_toggle SQL Error: $sql");
            return $oldvalue;
        } else {
            Cache::clear('events');
            return $newvalue;
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
     * Create the individual occurrances of a the current event.
     * If the event is not recurring, returns an array with only one element.
     *
     * @return  array       Array of matching events, keyed by date, or false
     */
    public function MakeRecurrences()
    {
        return Recurrence::getInstance($this)
            ->MakeRecurrences();
    }


    /**
     * Update all the repeats in the database.
     * Deletes all existing repeats, then creates new ones. Not very
     * efficient; it might make sense to check all related values, but there
     * are several.
     *
     * @return  boolean     True on success, False on failure
     */
    public function UpdateRepeats()
    {
        global $_TABLES;

        if ($this->rec_data['stop'] == '' ||
            $this->rec_data['stop'] > EV_MAX_DATE) {
            $this->rec_data['stop'] = EV_MAX_DATE;
        }
        if ((int)$this->rec_data['freq'] < 1) $this->rec_data['freq'] = 1;

        // Get the actual repeat occurrences.
        $days = $this->MakeRecurrences();
        if ($days === false) {
            $this->Errors[] = $LANG_EVLIST['err_upd_repeats'];
            return false;
        }

        // Delete all existing instances
        DB_delete($_TABLES['evlist_repeat'], 'rp_ev_id', $this->id);
        Cache::clear('repeats', 'event_' . $this->id);

        $i = 0;
        $vals = array();
        foreach($days as $event) {
            $t_end = $this->split ? $this->time_end2 : $this->time_end1;
            $vals[] = "(
                '{$this->id}', '{$this->det_id}',
                '{$event['dt_start']}', '{$event['dt_end']}',
                '{$this->time_start1}', '{$this->time_end1}',
                '{$this->time_start2}', '{$this->time_end2}',
                '{$event['dt_start']} {$this->time_start1}',
                '{$event['dt_end']} {$t_end}'
            )";
        }
        if (!empty($vals)) {
            $vals = implode(',', $vals);
            $sql = "INSERT INTO {$_TABLES['evlist_repeat']} (
                        rp_ev_id, rp_det_id, rp_date_start, rp_date_end,
                        rp_time_start1, rp_time_end1,
                        rp_time_start2, rp_time_end2,
                        rp_start, rp_end
                    ) VALUES $vals";
            //echo $sql;die;
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
                list($hour, $minute, $second) = explode(':', $time);
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
     * @return  boolean         True = has sufficieng access, False = not
     */
    public function hasAccess($level=3)
    {
        // Admin & editor has all rights
        if ($this->isAdmin) {
            return true;
        }

        $ev_access = SEC_hasAccess(
            $this->owner_id, $this->group_id,
            $this->perm_owner, $this->perm_group,
            $this->perm_members, $this->perm_anon
        );
        if (
            $ev_access < $level ||
            $this->Calendar->getSecAccess() < $level
        ) {
            return false;
        }
        return true;
    }


    /**
     * Get the categories currently tied to this event.
     * Uses Category::getAll() to leverage caching.
     *
     * @uses    Category::getall()
     * @return  array   Array of (id, name)
     */
    public function getCategories()
    {
        global $_TABLES;

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
    public function saveCategory($cat_name)
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
     * @return  boolean     True if an update is needed, false if not
     */
    public function NeedRepeatUpdate($A)
    {
        // Just check each relevant value in $A against our value.
        // If any matches, return true
        if (
            $this->old_schedule['date_start1'] != $this->date_start1 ||
            $this->old_schedule['date_end1'] != $this->date_end1 ||
            $this->old_schedule['time_start1'] != $this->time_start1 . ':00' ||
            $this->old_schedule['time_end1'] != $this->time_end1 . ':00'
        ) {
            return true;
        }

        if ($this->time_start2 == '') $this->time_start2 = self::MIN_TIME;
        if ($this->time_end2 == '') $this->time_end2 = self::MAX_TIME;

        // Checking split times, this should cover the split checkbox also
        if (
            $this->old_schedule['time_start2'] != $this->time_start2 ||
            $this->old_schedule['time_end2'] != $this->time_end2 ||
            $this->old_schedule['allday'] != $this->allday
        ) {
            return true;
        }

        // Possibilities:
        //  - was not recurring, is now.  Return true at this point.
        //  - was recurring, isn't now.  Return true at this point.
        //  - wasn't recurring, still isn't, old_schedule['rec_data'] will
        //      be empty, ignore.
        //  - was recurring, still is.  Have to check old and new rec_data
        //      arrays.
        if ($this->old_schedule['recurring'] != $this->recurring) {
            return true;
        } elseif (!empty($this->old_schedule['rec_data'])) {
            $old_rec = is_array($this->old_schedule['rec_data']) ?
                $this->old_schedule['rec_data'] : array();
            $new_rec = is_array($this->rec_data) ?
                $this->rec_data : array();

            // Check the recurring event options
            $diff = self::_arrayDiffAssocRecursive($old_rec, $new_rec);
            if (!empty($diff)) {
                return true;
            }

            // Have to descend into sub-arrays manually.  Old and/or new
            // values may not be arrays if the recurrence type was changed.
            foreach (array('listdays', 'interval', 'custom') as $key) {
                $oldA = isset($old_rec[$key]) && is_array($old_rec[$key]) ?
                    $old_rec[$key] : array();
                $newA = isset($new_rec[$key]) && is_array($new_rec[$key]) ?
                    $new_rec[$key] : array();
                $diff = self::_arrayDiffAssocRecursive($oldA, $newA);
                if (!empty($diff)) {
                    return true;
                }
            }
        } else {
            // Even non-recurring events should have some empty array for
            // old schedule data, so go ahead & rebuild the repeats.
            return true;
        }

        // If all tests fail, return false (no need to update repeats
        return false;
    }


    /**
     * Recursively check two arrays for differences.
     * From http://nl3.php.net/manual/en/function.array-diff-assoc.php#73972
     *
     * @see     self::NeedRepeatUpdate()
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
    public function RecurDescrip($freq = '', $interval = '')
    {
        global $LANG_EVLIST;

        if (($freq == '' || $interval == '') && is_object($this)) {
            $freq = $this->rec_data['freq'];
            $interval = $this->rec_data['type'];
        }

        $freq = (int)$freq;
        $interval = (int)$interval;
        if ($interval < EV_RECUR_DAILY || $interval > EV_RECUR_DATES) {
            $interval = EV_RECUR_DAILY;
        }
        if ($freq < 1)
            $freq = 1;

        $freq_str = '';

        // Create the recurring description.  Nothing for custom dates
        if ($interval < EV_RECUR_DATES) {
            $interval_txt = $LANG_EVLIST['rec_periods'][$interval];
            if ($freq > 1)
                $freq_str = "$freq {$interval_txt}";
            else
                $freq_str = $interval_txt;
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
     *
     * @return boolean     True if editing is allowed, False if not
     */
    public function canEdit()
    {
        global $_CONF;

        static $canedit = NULL;

        if ($canedit === NULL) {
            $canedit = false;
            if (plugin_ismoderator_evlist()) {
                $canedit = true;
            } elseif ($this->isOwner()) {
                if ($_CONF['storysubmission'] == 0) {
                    $canedit = true;
                } elseif (plugin_issubmitter_evlist()) {
                    $canedit = true;
                }
            }
        }
        return $canedit;
    }


    public function getLink($rp_id=0)
    {
        if ($rp_id == 0) {
            $rp_id = Repeat::getNearest($this->id);
        }
        return EVLIST_URL . '/event.php?rp_id=' . $rp_id;
    }


    /**
     * Get the admin list of events.
     *
     * @return  string      HTML for admin list
     */
    public static function adminList()
    {
        global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

        USES_lib_admin();
        EVLIST_setReturn('adminevents');

        $cal_id = isset($_REQUEST['cal_id']) ? (int)$_REQUEST['cal_id'] : 0;
        $retval = '';

        $header_arr = array(
            array(
                'text' => $LANG_EVLIST['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_EVLIST['copy'],
                'field' => 'copy',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_EVLIST['id'],
                'field' => 'id',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['title'],
                'field' => 'title',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['start_date'],
                'field' => 'date_start1',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['enabled'],
                'field' => 'status',
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
            'field' => 'date_start1',
            'direction' => 'DESC',
        );
        $options = array(
            'chkdelete' => 'true',
            'chkfield' => 'id',
            'chkname' => 'delevent',
        );
        $text_arr = array(
            'has_menu'     => true,
            'has_extras'   => true,
            'form_url'     => EVLIST_ADMIN_URL . '/index.php?cal_id=' . $cal_id,
            'help_url'     => ''
        );

        // Select distinct to get only one entry per event.  We can only edit/modify
        // events here, not repeats
        $sql = "SELECT DISTINCT(ev.id), det.title, ev.date_start1, ev.status
                FROM {$_TABLES['evlist_events']} ev
                LEFT JOIN {$_TABLES['evlist_detail']} det
                    ON det.ev_id = ev.id
                WHERE ev.det_id = det.det_id ";
        if ($cal_id != 0) {
            $sql .= "AND cal_id = $cal_id";
        }

        $filter = $LANG_EVLIST['calendar']
            . ': <select name="cal_id" onchange="this.form.submit()">'
            . '<option value="0">' . $LANG_EVLIST['all_calendars'] . '</option>'
            . Calendar::optionList($cal_id) . '</select>';

        $query_arr = array(
            'table' => 'users',
            'sql' => $sql,
            'query_fields' => array(
                'id', 'title', 'summary',
                'full_description', 'location', 'date_start1', 'status',
            )
        );

        $retval .= COM_createLink(
            $LANG_EVLIST['new_event'],
            EVLIST_ADMIN_URL . '/index.php?edit=x',
            array(
                'class' => 'uk-button uk-button-success',
                'style' => 'float:left',
            )
        );

        $retval .= ADMIN_list(
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
    public static function getAdminField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;
        static $del_icon = NULL;
        $retval = '';

        switch($fieldname) {
        case 'edit':
            $retval = COM_createLink(
                $_EV_CONF['icons']['edit'],
                EVLIST_ADMIN_URL . '/index.php?edit=event&amp;eid=' . $A['id'] . '&from=admin',
                array(
                    'title' => $LANG_EVLIST['edit_event'],
                )
            );
            break;
        case 'copy':
            $retval = COM_createLink(
                $_EV_CONF['icons']['copy'],
                EVLIST_URL . '/event.php?clone=x&amp;eid=' . $A['id'],
                array(
                    'title' => $LANG_EVLIST['copy'],
                )
            );
            break;
        case 'title':
            $rp_id = Repeat::getNearest($A['id']);
            if ($rp_id) {
                $retval = COM_createLink(
                    $fieldvalue, EVLIST_URL . '/event.php?eid=' . $rp_id
                );
            } else {
                $retval = $fieldvalue;
            }
            break;
        case 'status':
            if ($A['status'] == '1') {
                $switch = EVCHECKED;
                $enabled = 1;
            } else {
                $switch = '';
                $enabled = 0;
            }
            $retval .= "<input type=\"checkbox\" $switch value=\"1\" name=\"ev_check\"
                id=\"event_{$A['id']}\"
                onclick='EVLIST_toggle(this,\"{$A['id']}\",\"enabled\",".
                '"event","'.EVLIST_ADMIN_URL."\");' />" . LB;
            break;
        case 'delete':
            $url = EVLIST_ADMIN_URL. '/index.php?delevent=x&eid=' . $A['id'];
            if (isset($_REQUEST['cal_id'])) {
                $url .= '&cal_id=' . (int)$_REQUEST['cal_id'];
            }
            $retval = COM_createLink(
                $_EV_CONF['icons']['delete'],
                $url,
                array(
                    'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_event']}');",
                    'title' => $LANG_ADMIN['delete'],
                    'class' => 'tooltip',
                )
            );
            break;
        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }


    /**
     * Check if comments are allowed and open for this event.
     *
     * @return  boolean     True if comments can be viewed and added.
     */
    public function commentsAllowed()
    {
        return ($this->enable_comments > -1 && plugin_commentsupport_evlist());
    }


    /**
     * Check if this is a new record.
     * Used to validate the retrieval of an instance.
     *
     * @return  boolean     True if new, False if existing
     */
    public function isNew()
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


    /**
     * Set the recurring data for the event.
     *
     * @param   string|array    Serizlized string or array
     * @return  object  $this
     */
    public function setRecData($data)
    {
        if (is_array($data)) {
            $this->rec_data = $data;
        } else {
            $this->rec_data = @unserialize($data);
        }
        if (!is_array($this->rec_data)) {
            $this->rec_data = array();
        }
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
    public function commentsEnabled()
    {
        return (int)$this->enable_comments;
    }


    /**
     * Get the detail information for this event.
     *
     * @return  object      Detail object
     */
    public function getDetail()
    {
        return $this->Detail;
    }


    /**
     * Get the first starting date.
     *
     * @return  object      Starting date object
     */
    public function getStartDate1()
    {
        return $this->start_date1;
    }


    /**
     * Get the timezone used for this event.
     *
     * @return  string      Timezone identifier
     */
    public function getTZID()
    {
        return $this->tzid;
    }

}   // class Event

?>
