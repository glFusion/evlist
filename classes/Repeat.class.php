<?php
/**
 * Class to manage event repeats or single instances for the EvList plugin.
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
 * Class for events.
 * @package evlist
 */
class Repeat
{
    /** Property fields accessed via `__set()` and `__get()`.
     * @var array */
    //var $properties = array();

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

    /** Associated event.
     * @var object */
    private $Event = NULL;

    /** Associated event detail (title, location, summary, etc.
     * @var object */
    private $Detail = NULL;

    /** Indicate if admin access is granted to this event/repeat.
     * @var boolean */
    private $isAdmin = false;


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
     * Set a property's value.
     *
     * @param   string  $var    Name of property to set.
     * @param   mixed   $value  New value for property.
     */
    public function X__set($var, $value='')
    {
        global $_USER, $_CONF;

        switch ($var) {
        case 'ev_id':
            $this->properties[$var] = COM_sanitizeId($value, false);
            break;

        case 'rp_id':
        case 'det_id':
        case 'uid':
            $this->properties[$var] = (int)$value;
            break;

        case 'date_start':
        case 'date_end':
        case 'tzid':
            // String values
            $this->properties[$var] = trim(COM_checkHTML($value));
            break;

        case 'time_start1':
        case 'time_end1':
        case 'time_start2':
        case 'time_end2':
            $this->properties[$var] = empty($value) ? '00:00:00' : trim($value);
            break;

        case 'dtStart1':
        case 'dtEnd1':
        case 'dtStart2':
        case 'dtEnd2':
            // Date objects to track starting and ending timestamps
            //$this->properties[$var] = new Date($value, $this->tzid);
            $this->properties[$var] = new \Date($value, $_CONF['timezone']);
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
        switch($var) {
        case 'use_tz':
            return true;
            //return $this->Event->tzid == 'local' ? false : true;
            break;
        default:
            if (array_key_exists($var, $this->properties)) {
                return $this->properties[$var];
            } else {
                return NULL;
            }
            break;
        }
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
    private function setDateStart1($dt_tm)
    {
        global $_CONF;
        $this->dtStart1 = new \Date($dt_tm, $_CONF['timezone']);
    }


    /**
     * Set the first ending date/time object.
     *
     * @param   string  $dt_tm  MySQL-formatted datetime string
     * @return  object  $this
     */
    private function setDateEnd1($dt_tm)
    {
        global $_CONF;
        $this->dtEnd1 = new \Date($dt_tm, $_CONF['timezone']);
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
        // Join or split the date values as needed
        if ($fromDB) {      // Read from the database

            // dates are YYYY-MM-DD
            /*list($startyear, $startmonth, $startday) = explode('-', $row['rp_date_start']);
            list($endyear, $endmonth, $endday) = explode('-', $row['rp_date_end']);
             */
        } else {            // Coming from the form

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
                /*$tmp = DateFunc::conv12to24($row['starthour1'], $row['start1_ampm']);
                $this->time_start1 = sprintf(
                    '%02d:%02d:00',
                    $tmp,
                    $row['startminute1']
                );
                $tmp = DateFunc::conv12to24($row['endhour1'], $row['end1_ampm']);
                $this->time_end1 = sprintf(
                    '%02d:%02d:00',
                    $tmp,
                    $row['endminute1']
                );*/
                if (isset($row['split']) && $row['split'] == '1') {
                    $this->time_start2 = $row['time_start2'];
                    $this->time_end2 = $row['time_end2'];
                    /*$tmp = DateFunc::conv12to24($row['starthour2'], $row['start2_ampm']);
                    $this->time_start2 = sprintf(
                        '%02d:%02d:00',
                        $tmp,
                        $row['startminute1']
                    );
                    $tmp = DateFunc::conv12to24($row['endhour2'], $row['end2_ampm']);
                    $this->time_end2 = sprintf(
                        '%02d:%02d:00',
                        $tmp,
                        $row['endminute2']
                    );*/
                } else {
                    $this->time_start2 = NULL;
                    $this->time_end2   = NULL;
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
            // This is used by Reminders so make sure it's set:
            $this->setDateStart1($this->date_start . ' ' . $this->time_start1);

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
    public function Edit($rp_id = 0, $edit_type='repeat')
    {
        if ($rp_id > 0) {
            $this->Read($rp_id);
        }
        return $this->Event->Edit(
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
    public function Save($A = '')
    {
        global $_TABLES;

        if (is_array($A)) {
            $this->setVars($A);
        }

        if ($this->rp_id > 0) {
            // Update this repeat's detail record if there is one.  Otherwise
            // create a new one.
            if ($this->det_id != $this->Event->getDetailID()) {
                $D = new Detail($this->det_id);
            } else {
                $D = new Detail();
            }
            $D->setVars($A);
            $D->setEventID($this->ev_id);
            $this->det_id = $D->Save();
            $date_start = DB_escapeString($this->date_start);
            $date_end = DB_escapeString($this->date_end);
            $time_start1 = DB_escapeString($this->time_start1);
            $time_start2 = DB_escapeString($this->time_start2);
            $time_end1 = DB_escapeString($this->time_end1);
            $time_end2 = DB_escapeString($this->time_end2);
            $t_end = $this->Event->isSplit() ? $time_end2 : $time_end1;
            $sql = "UPDATE {$_TABLES['evlist_repeat']} SET
                rp_date_start = '$date_start',
                rp_date_end= '$date_end',
                rp_time_start1 = '$time_start1',
                rp_time_end1 = '$time_end1',
                rp_time_start2 = '$time_start2',
                rp_time_end2 = '$time_end2',
                rp_start = '$date_start $time_start1',
                rp_end = '$date_end $t_end',
                rp_det_id='" . (int)$this->det_id . "'
            WHERE rp_id='{$this->rp_id}'";
            DB_query($sql);
            Cache::clear();
            PLG_itemSaved($this->rp_id, 'evlist');
        }
    }


    /**
     * Delete the current instance from the database.
     *
     * @return  boolean     True on success, False on failure
     */
    public function Delete()
    {
        global $_TABLES;

        if ($this->rp_id < 1 || !$this->Event->canEdit()) {
            // non-existent repeat ID or no edit access
            return false;
        }

        // If we have our own detail record, then delete it also
        if ($this->det_id != $this->Event->getDetailID()) {
            $this->Event->getDetail()->Delete();
        }

        DB_delete($_TABLES['evlist_repeat'], 'rp_id', $this->rp_id);
        Cache::clear();
        return true;
    }


    /**
     * Display the detail page for the event occurrence.
     *
     * @param   integer $rp_id  ID of the repeat to display
     * @param   string  $query  Optional query string, for highlighting
     * @param   string  $tpl    Optional template filename, e.g. 'event_print'
     * @param   string  $cmtmode    Comment Mode
     * @param   string  $cmtorder   Comment order
     * @return  string      HTML for the page.
     */
    public function Render($rp_id=0, $query='', $tpl='', $cmtmode='nested', $cmtorder='ASC')
    {
        global $_CONF, $_USER, $_EV_CONF, $LANG_EVLIST, $LANG_WEEK,
                $LANG_LOCALE, $_SYSTEM;

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

        if ($rp_id != 0) {
            $this->Read($rp_id);
        }

        if ($this->rp_id == 0) {
            return EVLIST_alertMessage($LANG_EVLIST['access_denied']);
        }

        // Print or other template modifier can be passed in.
        $template = 'event';
        if (!empty($tpl)) {
            $template .= '_' . $tpl;
        }
        $T = new \Template(EVLIST_PI_PATH . '/templates/');
        $T->set_file(array(
            'event' => $template . '.thtml',
            //'editlinks' => 'edit_links.thtml',
            'datetime' => 'date_time.thtml',
            'address' => 'address.thtml',
            'contact' => 'contact.thtml',
        ) );

        USES_lib_social();
        $permalink = COM_buildUrl(EVLIST_URL . '/event.php?view=event&eid=' . $this->Event->getID());
        $ss = $this->getShareIcons($permalink);

        $Detail = $this->Event->getDetail();
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
        if ($query != '') {
            $title = COM_highlightQuery($title, $query);
            if (!empty($summary)) {
                $summary  = COM_highlightQuery($summary, $query);
            }
            if (!empty($full_description)) {
                $full_description = COM_highlightQuery($full_description, $query);
            }
            if (!empty($location)) {
                $location = COM_highlightQuery($location, $query);
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
                $this->Event->RecurDescrip();
            switch ($rec_data['type']) {
            case EV_RECUR_WEEKLY:        // sequential days
                $weekdays = array();
                if (is_array($rec_data['listdays'])) {
                    foreach ($rec_data['listdays'] as $daynum) {
                        $weekdays[] = $LANG_WEEK[$daynum];
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
                $rec_string .= ' ' . sprintf(
                    $LANG_EVLIST['recur_stop_desc'],
                    $stop_date->format($_CONF['dateonly'], true)
                );
            }
        } else {
            $rec_string = '';
        }

        $T->set_var(array(
            'pi_url' => EVLIST_URL,
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
        ) );

        $outputHandle = \outputHandler::getInstance();
        $outputHandle->addLink('canonical', $permalink, HEADER_PRIO_NORMAL);
        $outputHandle->addMeta(
            'property',
            'og:site_name',
            urlencode($_CONF['site_name']),
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
                    $cmtorder,
                    $cmtmode,
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
                                'tick_fee' => $data['fee'] > 0 ? $fmt_amt : 'FREE',
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
                        'lat' => $lat,
                        'lng' => $lng,
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

        $hasReminder = 0;
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
                $hasReminder = Reminder::countReminders($this->ev_id, $this->rp_id);
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
            'reminder_set' => $hasReminder ? 'true' : 'false',
            'reminder_email' => isset($_USER['email']) ? $_USER['email'] : '',
            'notice' => 1,
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
                $val = $cmt[$key];
            } else {
                $val.= 'n/a';
            }
            $comments[$prompt] = $val;
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
            Ticket::Create($this->Event->getID(), $tick_type, $t_rp_id, $fee, $uid, $wl, $comments);
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

        static $count = NULL;

        if ($count === NULL) {
            if ($_EV_CONF['enable_rsvp'] != 1) {
                $count = 0;
            } elseif ($this->Event->getOption('use_rsvp') == EV_RSVP_EVENT) {
                $count = (int)DB_count($_TABLES['evlist_tickets'], 'ev_id', $this->ev_id);
            } else {
                $count = (int)DB_count($_TABLES['evlist_tickets'], 'rp_id', $this->rp_id);
            }
        }
        return $count;
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
    public function DeleteFuture()
    {
        global $_TABLES;

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
            $sql = "SELECT rp_id, rp_det_id
                    FROM {$_TABLES['evlist_repeat']}
                    WHERE rp_ev_id='{$this->ev_id}'
                    AND rp_date_start >= '{$this->date_start}'
                    AND rp_det_id <> '{$this->Event->getDetailID()}'";
            $res = DB_query($sql);
            $details = array();
            while ($A = DB_fetchArray($res, false)) {
                $details[] = (int)$A['rp_det_id'];
            }
            if (!empty($details)) {
                $detail_str = implode(',', $details);
                $sql = "DELETE FROM {$_TABLES['evlist_detail']}
                        WHERE det_id IN ($detail_str)";
                DB_query($sql);
            }

            // Now delete the repeats
            $sql = "DELETE FROM {$_TABLES['evlist_repeat']}
                    WHERE rp_ev_id='{$this->ev_id}'
                    AND rp_date_start >= '{$this->date_start}'";
            DB_query($sql);

            // Now adjust the recurring stop date for the event.
            $new_stop = DB_getItem($_TABLES['evlist_repeat'],
                'rp_date_start',
                "rp_ev_id='{$R->ev_id}'
                    ORDER BY rp_date_start DESC LIMIT 1");
            if (!empty($new_stop)) {
                $this->Event->getRecData()['stop'] = $new_stop;
                $this->Event->Save();
            }
        }
    }   // function DeleteFuture()


    public function updateFuture()
    {
        global $_TABLES;

        $sql = "UPDATE {$_TABLES['evlist_repeat']}
            SET rp_det_id = '{$this->getDetailID()}'
            WHERE rp_date_start >= '{$this->getDateStart1()->toMySQL(true)}'
            AND rp_ev_id = '{$this->getEventID()}'";
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
    public static function getRepeats($ev_id, $rp_id=0)
    {
        global $_TABLES;

        $repeats = array();
        $where = array();
        $ev_id = DB_escapeString($ev_id);
        $rp_id = (int)$rp_id;

        $where = "rp_ev_id = '$ev_id'";
        if ($rp_id > 0) {
            $where .= " AND rp_id = $rp_id";
        }
        if (!empty($where)) {
            $sql = "SELECT * FROM {$_TABLES['evlist_repeat']} WHERE $where";
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
                    $this->Event->getDetail()->getTitle() . ' ' . $this->date_start.
                    ' ' . $this->time_start1,
            'short_description' => $TickType->getDscp() . ': ' .
                    $this->Event->getDetail()->getTitle() . ' ' . $this->date_start .
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
        global $_EV_CONF, $_TABLES, $_CONF;

        $sql_date = $_EV_CONF['_now']->toMySQL(true);
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
    private function getShareIcons($permalink)
    {
        if (version_compare(GVERSION, '2.0.0', '<')) {
            $ss = SOC_getShareIcons(
                $this->Event->getDetail()->getTitle(),
                $this->Event->getDetail()->getSummary(),
                $permalink
            );
        } else {
            $ss = \glFusion\Social\Social::getShareIcons(
                $this->Event->getDetail()->getTitle(),
                $this->Event->getDetail()->getSummary(),
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
        return $this->Event;
    }


    /**
     * Get the first starting date/time object.
     *
     * @return  object      Starting date object
     */
    public function getDateStart1()
    {
        return $this->dtStart1;
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

}   // class Repeat

?>
