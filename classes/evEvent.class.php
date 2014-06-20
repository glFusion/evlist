<?php
/**
 *  Class to manage events for the EvList plugin
 *
 *  @author     Lee Garner <lee@leegarner.com>
 *  @copyright  Copyright (c) 2011 Lee Garner <lee@leegarner.com>
 *  @package    evlist
 *  @version    1.3.0
 *  @license    http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 *  @filesource
 */

USES_evlist_class_datecalc();
USES_evlist_class_detail();
USES_evlist_class_calendar();
USES_evlist_functions();

/**
 *  Class for event
 *  @package evlist
 */
class evEvent
{
    /** Property fields.  Accessed via Set() and Get()
    *   @var array */
    var $properties = array();

    /** Indicate whether the current user is an administrator
    *   @var boolean */
    var $isAdmin;           // Has evlist.admin privilege
    var $isSubmitter;       // Has evlist.submit privilege
    var $AdminMode = false;

    var $isNew;             // Flags a new event record
    var $det_id;            // Detail record ID

    /** Recurring event data
    *   @var array */
    var $rec_data;

    /** Other miscelaneous options
    *   @var array */
    var $options;

    var $old_schedule;      // Keeper of old schedul info before updating

    var $Detail;            // evDetail object
    var $Calendar;          // evCalendar object
    var $table;             // DB table being used

    /** Array of error messages
     *  @var array */
    var $Errors = array();


    /**
     *  Constructor.
     *  Reads in the specified class, if $id is set.  If $id is zero, 
     *  then a new entry is being created.
     *
     *  @param  string  $ev_id  Optional event ID
     *  @param  integer $detail Optional detail record ID for single repeat
     */
    function __construct($ev_id='', $detail=0)
    {
        global $_EV_CONF, $_USER;

        $this->isNew = true;

        if ($ev_id == '') {
            $this->id = '';
            $this->recurring = 0;
            $this->rec_data = array();
            $this->allday = 0;
            $this->split = 0;
            $this->status = 1;          // assume "enabled"
            $this->show_upcoming = 1;   // show in upcoming events by default
            $this->postmode = 'plaintext';
            $this->hits = 0;
            $this->enable_reminders = 1;
            $this->categories = array();
            $this->owner_id = $_USER['uid'];
            $this->group_id = 13;

            // Create dates & times based on individual URL parameters,
            // or defaults.
            // Start date/time defaults to now
            $startday1 = isset($_GET['day']) ? (int)$_GET['day'] : '';
            if ($startday1 < 1 || $startday1 > 31) $startday1 = date('j');
            $startmonth1 = isset($_GET['month']) ? (int)$_GET['month'] : '';
            if ($startmonth1 < 1 || $startmonth1 > 12) $startmonth1 = date('n');
            $startyear1 = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $starthour1 = isset($_GET['hour']) ? (int)$_GET['hour'] : date('H');
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

            $this->date_start1 = sprintf("%4d-%02d-%02d", 
                            $startyear1, $startmonth1, $startday1);
            $this->time_start1 = sprintf("%02d:%02d:00", 
                            $starthour1, $startminute1);
            $this->time_start2 = sprintf("%02d:%02d:00", 
                            $starthour2, $startminute2);
            $this->date_end1 = sprintf("%4d-%02d-%02d", 
                            $endyear1, $endmonth1, $endday1);
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
                    'contactlink' => '',
                );

            $this->Detail = new evDetail();

        } else {
            $this->id = $ev_id;
            if (!$this->Read()) {
                $this->id = '';
            } else {
                // Load the Detail object.  May need to load a special one
                // if we're editing a repeat instance.
                if ($detail > 0 && $detail != $this->det_id) {
                    $this->Detail = new evDetail($detail);
                } else {
                    // Normal, load our own detail object
                    $this->Detail = new evDetail($this->det_id);
                }
            }
            //var_dump($this);die;
        }

        $this->isAdmin = SEC_hasRights('evlist.admin') ? 1 : 0;
        $this->isSubmitter = $this->isAdmin || SEC_hasRights('evlist.submit') ?
                    1 : 0;
    }


    /**
    *   Set a property's value.
    *   Emulates the __set() magic function in PHP 5.
    *
    *   @param  string  $var    Name of property to set.
    *   @param  mixed   $value  New value for property.
    */
    function __set($var, $value='')
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
            // Integer values
            if ($value == '') $value = 0;
            $this->properties[$var] = (int)$value;
            break;

        case 'date_start1':
        case 'date_end1':
        case 'postmode':
            // String values
            $this->properties[$var] = trim(COM_checkHTML($value));
            break;

        case 'time_start1':
        case 'time_start2':
        case 'time_end1':
        case 'time_end2':
            $this->properties[$var] = empty($value) ? '00:00:00' : trim($value);
            break;

        case 'status':
        case 'recurring':
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
    *   Get the value of a property.
    *   Emulates the behaviour of __get() function in PHP 5.
    *
    *   @param  string  $var    Name of property to retrieve.
    *   @param  boolean $db     True if string values should be escaped for DB.
    *   @return mixed           Value of property, NULL if undefined.
    */
    function __get($var)
    {
        if (array_key_exists($var, $this->properties)) {
            return $this->properties[$var];
        } else {
            return NULL;
        }
    }


    /**
     *  Sets all variables to the matching values from $rows.
     *
     *  @param  array   $row        Array of values, from DB or $_POST
     *  @param  boolean $fromDB     True if read from DB, false if from $_POST
     */
    function SetVars($row, $fromDB=false)
    {
        global $_EV_CONF;

        if (!is_array($row)) return;

        $this->date_start1 = (isset($row['date_start1']) && 
            !empty($row['date_start1'])) ? $row['date_start1'] : date('Y-m-d');
        $this->date_end1 = (isset($row['date_end1']) && 
            !empty($row['date_end1'])) ? $row['date_end1'] : $this->date_start1;
        $this->cal_id = $row['cal_id'];
        $this->show_upcoming = isset($row['show_upcoming']) ? 1 : 0;
        $this->recurring = isset($row['recurring']) && 
                $row['recurring'] == 1 ? 1 : 0;
        $this->show_upcoming = isset($row['show_upcoming']) && 
                $row['show_upcoming'] == 1 ? 1 : 0;
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
        $this->owner_id = $row['owner_id'];
        $this->group_id = $row['group_id'];
        //$this->title = $row['title'];

        if (isset($row['categories']) && is_array($row['categories'])) {
            $this->categories = $row['categories'];
        }

        // Join or split the date values as needed
        if ($fromDB) {

            // dates are YYYY-MM-DD
            $this->id = isset($row['id']) ? $row['id'] : '';
            $this->rec_data = unserialize($row['rec_data']);
            if (!$this->rec_data) $this->rec_data = array();
            $this->det_id = $row['det_id'];
            $this->hits = $row['hits'];
            $this->perm_owner = $row['perm_owner'];
            $this->perm_group = $row['perm_group'];
            $this->perm_members = $row['perm_members'];
            $this->perm_anon = $row['perm_anon'];
            $this->time_start1 = $row['time_start1'];
            $this->time_end1 = $row['time_end1'];
            $this->time_start2 = $row['time_start2'];
            $this->time_end2 = $row['time_end2'];
            $this->options = unserialize($row['options']);
            if (!$this->options) $this->options = array();

        } else {        // Coming from the form

            $this->id = isset($row['eid']) ? $row['eid'] : '';
            // Ignore time entries & set to all day if flagged as such
            if (isset($row['allday']) && $row['allday'] == '1') {
                $this->time_start1 = '00:00:00';
                $this->time_end1 = '23:59:59';
            } else {
                $tmp = EVLIST_12to24($row['starthour1'], $row['start1_ampm']);
                $this->time_start1 = sprintf('%02d:%02d:00', 
                    $tmp, $row['startminute1']);
                $tmp = EVLIST_12to24($row['endhour1'], $row['end1_ampm']);
                $this->time_end1 = sprintf('%02d:%02d:00', 
                    $tmp, $row['endminute1']);
            }

            // If split, record second time/date values.
            // Splits don't support allday events
            if ($this->split == 1) {
                $tmp = EVLIST_12to24($row['starthour2'], $row['start2_ampm']);
                $this->time_start2 = sprintf('%02d:%02d:00', 
                    $tmp, $row['startminute2']);
                $tmp = EVLIST_12to24($row['endhour2'], $row['end2_ampm']);
                $this->time_end2 = sprintf('%02d:%02d:00', 
                    $tmp, $row['endminute1']);
            } else {
                $this->time_start2 = NULL;
                $this->time_end2 = NULL;
            }

            if (isset($_POST['perm_owner'])) {
                $perms = SEC_getPermissionValues($row['perm_owner'],
                    $row['perm_group'], $row['perm_members'],
                    $row['perm_anon']);
                $this->perm_owner   = $perms[0];
                $this->perm_group   = $perms[1];
                $this->perm_members = $perms[2];
                $this->perm_anon    = $perms[3];
            }

            $this->owner_id = $row['owner_id'];
            $this->group_id = $row['group_id'];
            $this->options['contactlink'] = isset($row['contactlink']) ? 1 : 0;
            if ($_EV_CONF['enable_rsvp']) {
                $this->options['use_rsvp'] = (int)$row['use_rsvp'];
                $this->options['max_rsvp'] = (int)$row['max_rsvp'];
                $this->options['rsvp_waitlist'] = isset($row['rsvp_waitlist']) ? 1 : 0;
                $this->options['rsvp_cutoff'] = (int)$row['rsvp_cutoff'];
                if ($this->options['max_rsvp'] < 0) $this->options['max_rsvp'] = 0;
            } else {
                $this->options['use_rsvp'] = 0;
                $this->options['max_rsvp'] = 0;
                $this->options['rsvp_cutoff'] = 0;
                $this->options['rsvp_waitlist'] = 0;
            }
        }

    }


    /**
     *  Read a specific record and populate the local values.
     *
     *  @param  integer $id Optional ID.  Current ID is used if zero.
     *  @return boolean     True if a record was read, False on failure.
     */
    function Read($ev_id = '', $table = 'evlist_events')
    {
        global $_TABLES;

        if ($ev_id != '') {
            $this->id = COM_sanitizeID($ev_id);
        }

        if ($table != 'evlist_events') $table = 'evlist_submissions';
        $this->table = $table;

        $sql = "SELECT * FROM {$_TABLES[$this->table]} WHERE id='$this->id'";
        $result = DB_query($sql);
        if (!$result || DB_numRows($result != 1)) {
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

            $this->Detail = new evDetail($this->det_id);
            $this->Calendar = new evCalendar($this->cal_id);

            return true;
        }
    }


    /**
     *  Save the current values to the database.
     *  Appends error messages to the $Errors property.
     *
     *  The $forceNew parameter is a hack to force this record to be saved
     *  as a new record even if it already has an ID.  This is only to
     *  handle events imported from the Calendar plugin.
     *
     *  @param  array   $A      Optional array of values from $_POST
     *  @param  string  $table  Table name (submission or production)
     *  @param  boolean $forceNew   Hack to force this record to be "new"
     *  @return boolean         True if no errors, False otherwise
     */
    public function Save($A = '', $table = 'evlist_submissions', $forceNew=false)
    {
        global $_TABLES, $LANG_EVLIST, $_EV_CONF, $_USER, $_CONF;

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
            $this->old_schedule = array();
        }

        // Now we can update our main record with the new info
        if (is_array($A)) {
            $this->SetVars($A);
            $this->MakeRecData();
        }

        if (isset($A['eid']) && !empty($A['eid']) && !$forceNew) {
            $this->isNew = false;
            $oldid = COM_sanitizeID($A['eid']);
        }

        // Authorized to bypass the queue
        if ($this->isAdmin || $this->isSubmitter) {
            $table = 'evlist_events';
        }
        $this->table = $table;

        if ($this->id == '') {
            // If we allow users to create IDs, this could happen
            $this->id = COM_makesid();
        }

        $ev_id_DB = DB_escapeString($this->id);   // Used often, sanitize now

        // Insert or update the record, as appropriate
        if (!$this->isNew) {

            // Existing event, we already have a Detail object instantiated
            $this->Detail->SetVars($A);
            $this->Detail->ev_id = $this->id;
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
                    $this->UpdateRepeats();
                } else {
                    // this is a one-time event, update the existing instance
                    $sql = "UPDATE {$_TABLES['evlist_repeat']} SET
                            rp_date_start = '{$this->date_start1}',
                            rp_date_end = '{$this->date_end1}',
                            rp_time_start1 = '{$this->time_start1}',
                            rp_time_end1 = '{$this->time_end1}',
                            rp_time_start2 = '{$this->time_start2}',
                            rp_time_end2 = '{$this->time_end2}'
                        WHERE rp_ev_id = {$this->id}";
                    DB_query($sql, 1);
                }
            }

        } else {
            // New event

            if (!$this->isAdmin) {
                // Override any submitted permissions if user is not an admin
                $this->perm_owner = $_EV_CONF['default_permissions'][0];
                $this->perm_group = $_EV_CONF['default_permissions'][1];
                $this->perm_members = $_EV_CONF['default_permissions'][2];
                $this->perm_anon = $_EV_CONF['default_permissions'][3];
                // Set the group_id to the default 
                $this->group_id = (int)DB_getItem($_TABLES['groups'],
                        'grp_id', 'grp_name="evList Admin"');
                // Set the owner to the submitter
                $this->owner_id = (int)$_USER['uid'];
            }

            // Create a detail record
            $this->Detail = new evDetail();
            $this->Detail->SetVars($A);
            $this->Detail->ev_id = $this->id;
            if (!$this->isValidRecord()) {
                return $this->PrintErrors();
            }

            // Save the new detail record & get the ID
            $this->det_id = $this->Detail->Save();

            // Quit now if the detail record failed
            if ($this->det_id == 0) return false;

            if ($this->table != 'evlist_submissions') {
                // This function gets the rec_data value.
                $this->UpdateRepeats();
                //var_dump($this);die;
            }

            $sql1 = "INSERT INTO {$_TABLES[$this->table]} SET
                    id = '" . DB_escapeString($this->id) . "', ";
            $sql2 = '';
        }

        // Now save the categories
        // First save the new category if one was submitted
        if (!is_array($this->categories)) $this->categories = array();
        if (isset($A['newcat']) && !empty($A['newcat'])) {
            $newcat = $this->SaveCategory($A['newcat']);
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
            //echo $sql;die;
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
            owner_id = '{$this->owner_id}',
            group_id = '{$this->group_id}',
            perm_owner = '{$this->perm_owner}',
            perm_group = '{$this->perm_group}',
            perm_members = '{$this->perm_members}',
            perm_anon = '{$this->perm_anon}',
            det_id = '{$this->det_id}',
            cal_id = '{$this->cal_id}',
            show_upcoming = '{$this->show_upcoming}',
            options = '" . DB_escapeString(serialize($this->options)) . "' ";

        $sql = $sql1 . $fld_sql . $sql2;

        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            $this->Errors[] = $LANG_EVLIST['err_db_saving'];
        } elseif ($this->table == 'evlist_submissions' && 
                isset($_CONF['notification']) &&
                in_array ('evlist', $_CONF['notification'])) {
            $N = new Template(EVLIST_PI_PATH . '/templates/');
            $N->set_file('mail', 'notify_submission.thtml');
            $N->set_var(array(
                'title'     => $this->Detail->title,
                'summary'   => $this->Detail->summary,
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
            return '';
        } else {
            return $this->PrintErrors();
        }

    }


    /**
     *  Delete the current event record and all repeats.
     */
    function Delete($eid = '')
    {
        global $_TABLES, $_PP_CONF;

        if ($eid == '' && is_object($this)) {
            $eid = $this->id;
        }
        if ($eid == '')
            return false;

        // Make sure the current user has access to delete this event
        $res = DB_query("SELECT id FROM {$_TABLES['evlist_events']}
                WHERE id='$eid' " . COM_getPermSQL('AND', 0, 3));
        if (!$res || DB_numRows($res) != 1)
            return false;

        DB_delete($_TABLES['evlist_events'], 'id', $eid);
        DB_delete($_TABLES['evlist_detail'], 'ev_id', $eid);
        DB_delete($_TABLES['evlist_repeat'], 'rp_ev_id', $eid);
        DB_delete($_TABLES['evlist_remlookup'], 'eid', $eid);
        DB_delete($_TABLES['evlist_lookup'], 'eid', $eid);
        
        return true;
    }


    /**
     *  Determines if the current record is valid.
     *
     *  @return boolean     True if ok, False when first test fails.
     */
    function isValidRecord()
    {
        global $LANG_EVLIST;

        // Check that basic required fields are filled in.  We don't
        // check the event ID since that will be created automatically if
        // it is.
        if ($this->Detail->title == '')
            $this->Errors[] = $LANG_EVLIST['err_missing_title'];

        if ($this->date_start1 . ' ' . $this->time_start1 >
            $this->date_end1 . ' ' . $this->time_end1)
            $this->Errors[] = $LANG_EVLIST['err_times'];

        if ($this->split == 1 && $this->date_start1 == $this->date_end1) {
            if ($this->date_start1 . ' ' . $this->time_start2 > 
                $this->date_start1 . ' ' . $this->time_end2)
                $this->Errors[] = $LANG_EVLIST['err_times'];
        }

        if ($this->format == EV_RECUR_WEEKLY && 
                $this->listdays == '') 
            $this->Errors[] = $LANG_EVLIST['err_missing_weekdays'];

        if (!empty($this->Errors)) {
            return false;
        } else {
            return true;
        }

    }


    /**
     *  Creates the edit form
     *  @param integer $id Optional ID, current record used if zero
     *  @return string HTML for edit form
     */
    public function Edit($eid = '', $rp_id = 0, $saveaction = '')
    {
        global $_CONF, $_EV_CONF, $_TABLES, $_USER, $LANG_EVLIST, $LANG_WEEK,
                $LANG_ADMIN, $_GROUPS, $LANG_ACCESS;

        // If an eid is specified and this is an object, then read the
        // event data- UNLESS a repeat ID is given in which case we're
        // editing a repeat and already have the info we need.
        // This probably needs to change, since we should always read event
        // data during construction.
        if ($eid != ''  && $rp_id == 0 && is_object($this)) {
            // If an id is passed in, then read that record
            if (!$this->Read($eid)) {
                return 'Invalid object ID';
            }
        } elseif (isset($_POST['eid']) && !empty($_POST['eid'])) {
            // Returning to an existing form, probably due to errors
            $this->SetVars($_POST);

            // Make sure the current user has access to this event.
            if (!$this->hasAccess()) return $LANG_EVLIST['access_denied'];

        }

        $T = new Template($_CONF['path'] . 'plugins/evlist/templates/');
        $T->set_file(array(
            'editor' => 'editor.thtml',
        ));

        // Basic tabs for editing both events and instances, show up on 
        // all edit forms
        //$tabs = array('ev_info', 'ev_schedule', 'ev_location', 'ev_contact',);
        $tabs = array('ev_info', 'ev_location', 'ev_contact',);

        $rp_id = (int)$rp_id;

        if ($rp_id > 0) {
            // Make sure the current user has access to this event.
            if (!$this->hasAccess()) return $LANG_EVLIST['access_denied'];

            if ($saveaction == 'savefuturerepeat') {
                $alert_msg = EVLIST_alertMessage($LANG_EVLIST['editing_future'],
                        'info');
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
            // $rp_id is passed when this is called from class evRepeat.
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
        } else {
            // Editing the main event record

            if ($this->id != '' && $this->recurring == 1) {
                $alert_msg = EVLIST_alertMessage($LANG_EVLIST['editing_series'],
                    'alert');
            }
            if ($this->isAdmin) {
                $tabs[] = 'ev_perms';   // Add permissions tab, event edit only
                $T->set_var('permissions_editor', 'true');
            }
            $T->set_var(array(
                'contact_section' => 'true',
                'category_section' => 'true',
                'upcoming_chk' => $this->show_upcoming ? EVCHECKED : '',
            ) );
        }

        $action_url = EVLIST_URL . '/event.php';
        $delaction = 'delevent';
        $cancel_url = EVLIST_URL . '/index.php';
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
        $recweekday  = '';

        $ownerusername = DB_getItem($_TABLES['users'], 
                    'username', "uid='{$this->owner_id}'");

        $retval .= COM_startBlock($LANG_EVLIST['event_editor']);
        $summary = $this->Detail->summary;
        $full_description = $this->Detail->full_description;
        $location = $this->Detail->location;
        if (($this->isAdmin || 
                ($_EV_CONF['allow_html'] == '1' && $_USER['uid'] > 1)) 
                && $A['postmode'] == 'html') {
            $postmode = '2';      //html
        } else {
            $postmode = '1';            //plaintext
            $summary = htmlspecialchars(COM_undoClickableLinks(COM_undoSpecialChars($this->Detail->summary)));
            $full_description = htmlspecialchars(COM_undoClickableLinks(COM_undoSpecialChars($this->Detail->full_description)));
            $location = htmlspecialchars(COM_undoClickableLinks(COM_undoSpecialChars($this->Detail->location)));
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
            $days_interval = Date_Calc::dateDiff(
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

        if ($this->recurring != '1') {
            $T->set_var(array(
                'recurring_show'    => ' style="display:none;"',
                'format_opt'        => '0',
            ) );
            //for ($i = 1; $i <= 6; $i++) {
            //    $T->set_var('format' . $i . 'show', ' style="display:none;"');
            //}
        } else {
            $option = empty($this->rec_data['type']) ? 
                        '0' : (int)$this->rec_data['type'];

            $T->set_var(array(
                'recurring_show' => '',
                'recurring_checked' => EVCHECKED,
                'format_opt'    => $option,
            ) );
        }
            if (isset($this->rec_data['stop']) && 
                    !empty($this->rec_data['stop'])) {
                $T->set_var(array(
                    'stopdate'      => $this->rec_data['stop'],
                    'd_stopdate'    => 
                                EVLIST_formattedDate($this->rec_data['stop']),
                ) );
            }
            if (!empty($this->rec_data['skip'])) {
                $T->set_var("skipnext{$this->rec_data['skip']}_checked", 
                        EVCHECKED);
            }

            if (!empty($this->rec_data['freq'])) {
                $freq = (int)$this->rec_data['freq'];
                if ($freq < 1) $freq = 1;
            } else {
                $freq = 1;
            }
            $T->set_var(array(
                'freq_text' => $LANG_EVLIST['rec_periods'][$this->rec_data['type']].'(s)',
                'rec_freq'  => $freq,
            ) );

            foreach ($LANG_EVLIST['rec_intervals'] as $key=>$str) {
                $T->set_var('dom_int_txt_' . $key, $str);
                if (is_array($this->rec_data['interval'])) {
                    if (in_array($key, $this->rec_data['interval'])) {
                        $T->set_var('dom_int_chk_'.$key, EVCHECKED);
                    }
                }
            }

            // Set up the recurring options needed for the current event
            switch ($option) {
            case 0:
                break;
            case EV_RECUR_MONTHLY:
                if (is_array($this->rec_data['listdays'])) {
                    foreach ($this->rec_data['listdays'] as $mday) {
                        $T->set_var('mdchk'.$mday, EVCHECKED);
                    }
                }
                break;
            case EV_RECUR_WEEKLY:
                $T->set_var('listdays_val', COM_stripslashes($rec_data[0]));
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
                //$T->set_var('recurring_weekday_options',
                //         EVLIST_GetOptions($LANG_WEEK, $recweekday));
                $recweekday = $this->rec_data['weekday'];
                break;
            case EV_RECUR_DATES:
                $T->set_var(array(
                    'stopshow'      => 'style="display:none;"',
                    'custom_val' => implode(',', $this->rec_data['custom']),
                ) );
                break;
            }

        $start1 = EVLIST_TimeSelect('start1', $this->time_start1);
        $start2 = EVLIST_TimeSelect('start2', $this->time_start2);
        $end1 = EVLIST_TimeSelect('end1', $this->time_end1);
        $end2 = EVLIST_TimeSelect('end2', $this->time_end2);
        $cal_select = COM_optionList($_TABLES['evlist_calendars'],
            'cal_id,cal_name', $this->cal_id, 1,
            'cal_status = 1 ' . COM_getPermSQL('AND', 0, 2));

        USES_class_navbar();
        $navbar = new navbar;
        $cnt = 0;
        foreach ($tabs as $id) {
            $navbar->add_menuitem($LANG_EVLIST[$id],'showhideEventDiv("'.$id.'",'.$cnt.');return false;',true);
            $cnt++;
        }
        $navbar->set_selected($LANG_EVLIST['ev_info']);

        if ($this->AdminMode) {
            $action_url .= '?admin=true';
        }

        $T->set_var(array(
            'action_url'    => $action_url,
            'navbar'        => $navbar->generate(),
            'alert_msg'     => $alert_msg,
            'cancel_url'    => $cancel_url,
            'eid'           => $this->id,
            'rp_id'         => $rp_id,
            'title'         => $this->Detail->title,
            'summary'       => $summary,
            'description'   => $full_description,
            'location'      => $location,
            'status_checked' => $this->status == 1 ? EVCHECKED : '',
            'url'           => $this->Detail->url,
            'street'        => $this->Detail->street,
            'city'          => $this->Detail->city,
            'province'      => $this->Detail->province,
            'country'       => $this->Detail->country,
            'postal'        => $this->Detail->postal,
            'contact'       => $this->Detail->contact,
            'email'         => $this->Detail->email,
            'phone'         => $this->Detail->phone,
            'startdate1'    => $this->date_start1,
            'enddate1'      => $this->date_end1,
            'd_startdate1'  => EVLIST_formattedDate($this->date_start1),
            'd_enddate1'    => EVLIST_formattedDate($this->date_end1),
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
            'recurring_format_options' => 
                    EVLIST_GetOptions($LANG_EVLIST['rec_formats'], $option),
            //'recurring_interval_options' => EVLIST_GetOptions($LANG_EVLIST['rec_intervals'], $recinterval),
            'recurring_weekday_options' => EVLIST_GetOptions($LANG_WEEK, $recweekday),
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
            'src'   => isset($_GET['src']) && $_GET['src'] == 'a' ? '1' : '0',

            'rem_status_checked' => $this->enable_reminders == 1 ? 
                        EVCHECKED : '',
            'del_button'    => $this->id == '' ? '' : 'true',
            'saveaction'    => $saveaction,
            'delaction'     => $delaction,
            'owner_id'      => $this->owner_id,
            'enable_reminders' => $_EV_CONF['enable_reminders'],
            'iso_lang'      => EVLIST_getIsoLang(),
            'hour_mode'     => $_CONF['hour_mode'],
            'days_interval' => $days_interval,
            'display_format' => $_CONF['shortdate'],
            'ts_start'      => strtotime($this->date_start1),
            'ts_end'        => strtotime($this->date_end1),
            'cal_select'    => $cal_select,
            'contactlink_chk' => $this->options['contactlink'] == 1 ? 
                                EVCHECKED : '',
            'lat'           => $this->Detail->lat,
            'lng'           => $this->Detail->lng,
            'perm_msg'      => $LANG_ACCESS['permmsg'],
            'last'          => $LANG_EVLIST['rec_intervals'][5],
         ) );

        if ($_EV_CONF['enable_rsvp']) {
            $T->set_var(array(
                'enable_rsvp' => 'true',
                'reg_chk'.$this->options['use_rsvp'] => EVCHECKED,
                'rsvp_wait_chk' => $this->options['rsvp_waitlist'] == 1 ?
                                EVCHECKED : '',
                'max_rsvp'   => $this->options['max_rsvp'],
                'rsvp_cutoff' => $this->options['rsvp_cutoff'],
                'use_rsvp' => $this->options['use_rsvp'], // for javascript
                'rsvp_waitlist' => $this->options['rsvp_waitlist'],
            ) );
        }

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

        //category info
        if ($_EV_CONF['enable_categories'] == '1') {
            $cresult = DB_query("SELECT DISTINCT tc.id, tc.name, tl.eid
                    FROM {$_TABLES['evlist_categories']} tc 
                    LEFT JOIN {$_TABLES['evlist_lookup']} tl 
                    ON tc.id = tl.cid AND tl.eid = '{$this->id}'
                    WHERE tc.status='1' ORDER BY tc.name");

            $numcats = DB_numRows($cresult);
            if ($numcats > 0) {
                $catlist = '';
                //$T->set_block('editor', 'CatItemBlk', 'catitem');
                while ($C = DB_fetchArray($cresult, false)) {
                    $chk = !is_null($C['eid']) ? EVCHECKED : '';
                    $catlist .= '<input type="checkbox" name="categories[]" ' .
                        'value="' . $C['id'] . '" ' . $chk . ' ' . XHTML . '>' .
                        '&nbsp;' . $C['name'] . '&nbsp;&nbsp;';
                    /*$T->set_var(array(
                        'category_name' => $C['name'],
                        'category_id' => $C['id'],
                    ));

                    if (!is_null($C['eid'])) {
                        $T->set_var('cat_checked', EVCHECKED);
                    } else {
                        $T->clear_var('cat_checked');
                    }
                    $T->parse('catitem', 'CatItemBlk', true);*/
                }
                $T->set_var('catlist', $catlist);
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
                        'uid,username', $this->owner_id, 1, 
                        "uid <> 1"),
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
            $T->set_var(array(
                'use_locator'   => 'true',
                'loc_selection' => GEO_optionList(),
            ) );
        }

        $T->parse('output', 'editor');
        $retval .= $T->finish($T->get_var('output'));

        $retval .= COM_endBlock();
        return $retval;

    }   // function Edit()


    /**
     *  Sets the "enabled" field to the specified value.
     *
     *  @param  integer $id ID number of element to modify
     *  @param  integer $value New value to set
     *  @return         New value, or old value upon failure
     */
    function _toggle($oldvalue, $varname, $ev_id='')
    {
        global $_TABLES;

        if ($ev_id == '') {
            if (is_object($this))
                $ev_id = $this->id;
            else
                return;
        }

        // If it's still an invalid ID, return the old value
        if ($ev_id == '')
            return $oldvalue;

        // Determing the new value (opposite the old)
        $newvalue = $oldvalue == 1 ? 0 : 1;

        $sql = "UPDATE {$_TABLES['evlist_events']}
                SET $varname=$newvalue
                WHERE id=$ev_id";
        //echo $sql;die;
        DB_query($sql);

        return $newvalue;
    }


    /**
     *  Sets the "enabled" field to the specified value.
     *
     *  @param  integer $id ID number of element to modify
     *  @param  integer $value New value to set
     *  @return         New value, or old value upon failure
     */
    function toggleEnabled($oldvalue, $ev_id='')
    {
        $oldvalue = $oldvalue == 0 ? 0 : 1;
        if ($ev_id == '') {
            if (is_object($this))
                $ev_id = $this->id;
            else
                return $oldvalue;
        } else {
            $ev_id = COM_sanitizeID($ev_id, false);
        }

        return evEvent::_toggle($oldvalue, 'status', $ev_id);
    }


    /**
    *   Create the individual occurrances of a the current event.
    *   If the event is not recurring, returns an array with only one element.
    *
    *   @return array           Array of matching events, keyed by date
    */
    function MakeRecurrences()
    {
        USES_evlist_class_recur();

        $events = array();

        switch ($this->rec_data['type']) {
        case 0:
            // Single non-repeating event
            $events[] = array(
                    'dt_start'  => $this->date_start1,
                    'dt_end'    => $this->date_end1,
                    'tm_start1' => $this->time_start1,
                    'tm_end1'   => $this->time_end1,
                    'tm_start2' => $this->time_start2,
                    'tm_end2'   => $this->time_end2,
            );
            return $events;
            break;
        case EV_RECUR_DATES:
            // Specific dates.  Simple handling.
            $Rec = new evRecurDates($this);
            break;
        case EV_RECUR_DOM:
            // Recurs on one or more days each month- 
            // e.g. first and third Tuesday
            $Rec = new evRecurDOM($this);
            break;
        case EV_RECUR_DAILY:
            // Recurs daily for a number of days
            $Rec = new evRecurDaily($this);
            break;
        case EV_RECUR_WEEKLY:
            // Recurs on one or more days each week- 
            // e.g. Tuesday and Thursday
            $Rec = new evRecurWeekly($this);
            break;
        case EV_RECUR_MONTHLY:
            // Recurs on the same date(s) each month
            $Rec = new evRecurMonthly($this);
            break;
        case EV_RECUR_YEARLY:
            // Recurs once each year
            $Rec = new evRecurYearly($this);
            break;
        }

        $events = $Rec->MakeRecurrences();
        return $events;
    }


    /**
    *   Update all the repeats in the database.
    *   Deletes all existing repeats, then creates new ones.  Not very
    *   efficient; it might make sense to check all related values, but there
    *   are several.
    */
    public function UpdateRepeats()
    {
        global $_TABLES;

        if ($this->rec_data['stop'] == '' ||
            $this->rec_data['stop'] > EV_MAX_DATE) {
            $this->rec_data['stop'] = EV_MAX_DATE;
        }
        if ((int)$this->rec_data['freq'] < 1) $this->rec_data['freq'] = 1;

        // Delete all existing instances
        DB_delete($_TABLES['evlist_repeat'], 'rp_ev_id', $this->id);

        // Get the actual repeat occurrences.
        $days = $this->MakeRecurrences();

        $i = 0;
        foreach($days as $event) {
            $sql = "INSERT INTO {$_TABLES['evlist_repeat']} (
                        rp_ev_id, rp_det_id, rp_date_start, rp_date_end,
                        rp_time_start1, rp_time_end1,
                        rp_time_start2, rp_time_end2
                    ) VALUES (
                        '{$this->id}', '{$this->det_id}', 
                        '{$event['dt_start']}', '{$event['dt_end']}',
                        '{$event['tm_start1']}', '{$event['tm_end1']}',
                        '{$event['tm_start2']}', '{$event['tm_end2']}'
                    )";
            //echo $sql;
            DB_query($sql, 1);
        }
    }


    /**
    *   Create a formatted display-ready version of the error messages.
    *
    *   @return string      Formatted error messages.
    */
    function PrintErrors()
    {
        $retval = '';
        foreach($this->Errors as $key=>$msg) {
            $retval .= "<li>$msg</li>" . LB;
        }
        return $retval;
    }


    /**
    *   Break up a date & time into component parts
    *
    *   @param  string  $date   SQL-formatted date
    *   @param  string  $time   Time (HH:MM)
    *   @return array   Array of values.
    */
    function DateParts($date, $time)
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
    *   Determine whether the current user has access to this event
    *
    *   @param  integer $level  Access level required
    *   @return boolean         True = has sufficieng access, False = not
    */
    function hasAccess($level=3)
    {
        // Admin & editor has all rights
        if ($this->isAdmin)
            return true;

        $access = SEC_hasAccess($this->owner_id, $this->group_id,
                    $this->perm_owner, $this->perm_group, 
                    $this->perm_members, $this->perm_anon);

        return $access >= $level ? true : false;

    }


    /**
    *   Get the categories currently tied to this event
    *
    *   @return array   Array of categories
    */
    function GetCategories()
    {
        global $_TABLES;

        $retval = array();
        $sql = "SELECT tc.id, tc.name 
                FROM {$_TABLES['evlist_categories']} tc
                LEFT JOIN {$_TABLES['evlist_lookup']} tl 
                ON tc.id = tl.cid 
                WHERE tl.eid = '{$this->id}' 
                AND tl.status = '1'";
        //echo $sql;die;
        $cresult = DB_query($sql, 1);
        while ($C = DB_fetchArray($cresult, false)) {
            $retval[] = $C;
        }
        return $retval;
    }


    /**
    *   Save a new category submitted with the event.
    *   Returns the ID of the newly-added category, or of the existing
    *   catgory if $cat_name is a duplicate.
    *
    *   @param  string  $cat_name   New category name.
    *   @return integer     ID of category
    */
    function SaveCategory($cat_name)
    {
        global $_TABLES;

        $cat_name = DB_escapeString($cat_name);

        // Make sure it's not a duplicate name.  While we're at it, get
        // the category ID to return.
        $id = DB_getItem($_TABLES['evlist_categories'], 'id', 
                "name='$cat_name'");
        if (!$id) {
            DB_query("INSERT INTO {$_TABLES['evlist_categories']}
                    (name, status)
                VALUES
                    ('$cat_name', 1)");
            if (!DB_error()) {
                $id = DB_insertId();
            }
        }

        return $id;
    }


    /**
    *   Determine if an update to the repeat table is needed.
    *   Checks all the dates & times, and the recurring settings to see
    *   if any have changed.
    *   Uses the old_schedule variable, which must be set first.
    *
    *   @param  array   $A  Array of values (e.g. $_POST)
    *   @return boolean     True if an update is needed, false if not
    */
    function NeedRepeatUpdate($A)
    {
        // Just check each relevant value in $A against our value.
        // If any matches, return true
        if ($this->old_schedule['date_start1'] != $this->date_start1)
            return true;
        if ($this->old_schedule['date_end1'] != $this->date_end1)
            return true;
        if ($this->old_schedule['time_start1'] != $this->time_start1)
            return true;
        if ($this->old_schedule['time_end1'] != $this->time_end1)
            return true;

        if ($this->time_start2 == '') $this->time_start2 = '00:00:00';
        if ($this->time_end2 == '') $this->time_end2 = '00:00:00';

        // Checking split times, this should cover the split checkbox also
        if ($this->old_schedule['time_start2'] != $this->time_start2)
            return true;
        if ($this->old_schedule['time_end2'] != $this->time_end2)
            return true;
        if ($this->old_schedule['allday'] != $this->allday)
            return true;

        // Possibilities:
        //  - was not recurring, is now.  Return true at this point.
        //  - was recurring, isn't now.  Return true at this point.
        //  - wasn't recurring, still isn't, old_schedule['rec_data'] will
        //      be empty, ignore.
        //  - was recurring, still is.  Have to check old and new rec_data
        //      arrays.
        if ($this->old_schedule['recurring'] != $this->recurring)
            return true;

        if (!empty($this->old_schedule['rec_data'])) {

            $old_rec = is_array($this->old_schedule['rec_data']) ?
                $this->old_schedule['rec_data'] : array();
            $new_rec = is_array($this->rec_data) ?
                $this->rec_data : array();

            // Check the recurring event options
            $diff = array_diff_assoc($old_rec, $new_rec);
            if (!empty($diff)) return true;

            // Have to descend into sub-arrays manually.  Old and/or new
            // values may not be arrays if the recurrence type was changed.
            foreach (array('listdays', 'interval', 'custom') as $key) {
                $oldA = is_array($old_rec[$key]) ? $old_rec[$key] : array();
                $newA = is_array($new_rec[$key]) ? $new_rec[$key] : array();
                $diff = array_diff_assoc($oldA, $newA);
                if (!empty($diff)) return true;
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
    *   Creates the rec_data array.
    *
    *   @param  array   $A      Array of data, default to $_POST
    */
    function MakeRecData($A = '')
    {
        if ($A == '') $A = $_POST;

        // Re-initialize the array, and make sure this is really a 
        $this->rec_data = array();
        if (!isset($A['recurring']) ||$A['recurring'] != 1) {
            $this->rec_data['type'] = 0;
            $this->rec_data['stop'] = EV_MAX_DATE;
            $this->rec_data['freq'] = 1;
            return;
        } else {
            $this->rec_data['type'] = isset($A['format']) ? 
                    (int)$A['format'] : 0;
            $this->rec_data['freq'] = isset($A['rec_freq']) ?
                    (int)$A['rec_freq'] : 1;
            if ($this->rec_data['freq'] < 1) $this->rec_data['freq'] = 1;
        }

        if (!empty($A['stopdate'])) {
            list($stop_y, $stop_m, $stop_d) = explode('-', $A['stopdate']);
            if (Date_Calc::isValidDate($stop_d, $stop_m, $stop_y)) {
                $this->rec_data['stop'] = $A['stopdate'];
            }
        }

        switch ($this->rec_data['type']) {
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
            // Specific dates.  Simple handling.
            $recDates = preg_split('/[\s,]+/', $A['custom']);
            sort($recDates);        // why not keep them in order...
            $this->rec_data['custom'] = $recDates;

            /*foreach($recDates as $occurrence) {
                list($y, $m, $d) = explode('-', $occurrence);
                if (Date_Calc::isValidDate($d, $m, $y)) {
                    $events[] = array(
                                'dt_start'  => $occurrence,
                                'dt_end'    => $occurrence,
                                'tm_start1'  => $this->time_start1,
                                'tm_end1'    => $this->time_end1,
                                'tm_start2'  => $this->time_start2,
                                'tm_end2'    => $this->time_end2,
                    );
                }
            }
            // We have the dates, don't need to go through the loop.
            return $events;*/
            break;

        default:
            // Unknown value, nothing to do
            break;
        }

    }


    /**
    *   Get a friendly description of a recurring event's frequency.
    *   Returns strings like "2 weeks", "month", "3 days", etc, which can
    *   be used to create phrases like "occurs every 2 months".
    *   This can be called as an object method or an api function by
    *   supplying both of the optional parameters.
    *
    *   @param  integer $freq       Frequency (number of intervals)
    *   @param  integer $interval   Interval, one to six
    *   @return string      Friendly text describing the interval
    */
    function RecurDescrip($freq = '', $interval = '')
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
                $freq_str = "$freq {$interval_txt}s";
            else
                $freq_str = $interval_txt;
        }

        return $freq_str;
    }


}   // class evEvent


?>
