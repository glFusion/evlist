<?php
/**
*   Class to manage event reminders for the EvList plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;

/**
 *  Class for reminders
 *  @package evlist
 */
class Reminder
{
    /** Property fields.  Accessed via __set() and __get()
    *   @var array */
    private $properties = array();

    /** Indicates if this is a new record or not.
    *   @var boolean */
    private $isNew;

    /** Repeat object for this reminder.
    *   @var object */
    public $Repeat;

    private static $langs = array();

    /**
    *   Constructor.
    *   Reads in the specified reminder from the lookup table.
    *   Sets isNew to false if a record is found, otherwise isNew will be true
    *
    *   @param  integer $rp_id      Optional Repeat ID
    *   @param  integer $uid        Optional user ID
    */
    public function __construct($rp_id='', $uid='')
    {
        if ($rp_id !== '') {
            $this->Repeat = new Repeat($rp_id);
        }
        if ($this->Repeat->rp_id > 0) {
            $this->eid = $this->Repeat->ev_id;
            $this->rp_id = $rp_id;
            $this->uid = $uid;
            $this->Read();
        } else {
            $this->isNew = true;
        }
    }


    /**
    *   Set a property's value.
    *
    *   @param  string  $var    Name of property to set.
    *   @param  mixed   $value  New value for property.
    */
    public function __set($var, $value='')
    {
        global $_USER;
        switch ($var) {
        case 'uid':
            // Empty user ID supplied by default when adding a reminder
            if (empty($value)) {
                $value = $_USER['uid'];
            }
            $this->properties[$var] = (int)$value;
            break;

        case 'rp_id':
            if (empty($value)) {
                $value = 0;
            }
            $this->properties[$var] = (int)$value;
            break;

        case 'eid':
            $this->properties[$var] = COM_sanitizeID($value, false);
            break;

        case 'name':
        case 'email':
            $this->properties[$var] = DB_escapeString($value);
            break;

        case 'date_start':
        case 'timestamp':
        case 'days_notice':
            $this->properties[$var] = (int)$value;
            break;

        default:
            // Undefined values (do nothing)
            break;
        }
    }


    /**
    *   Get the value of a property.
    *
    *   @param  string  $var    Name of property to retrieve.
    *   @return mixed           Value of property, NULL if undefined.
    */
    public function __get($var)
    {
        if (isset($this->properties[$var])) {
            return $this->properties[$var];
        } else {
            return NULL;
        }
    }


    /**
    *   Read a specific record and populate the local values.
    *
    *   @return boolean     True if a record was read, False on failure.
    */
    public function Read()
    {
        global $_TABLES;

        $sql = "SELECT * FROM {$_TABLES['evlist_remlookup']}
                WHERE eid='{$this->eid}'
                AND uid = '{$this->uid}'";
        if ($this->rp_id > 0) {
            $sql .= " AND rp_id = '{$this->rp_id}'";
        }

        $res = DB_query($sql);
        if (!$res || DB_numRows($res) != 1) {
            $this->isNew = true;
            return false;
        } else {
            $A = DB_fetchArray($res, false);
            $this->setVars($A);
            $this->isNew = false;
            return true;
        }
    }


    /**
    *   Sets all the values in array $A into object fields.
    *   Only valid values are set, extras are ignored.
    *
    *   @param  array   $A      Array of name=>value pairs
    */
    private function setVars($A)
    {
        foreach ($A as $fld=>$value) {
            $this->$fld = $value;
        }
    }


    /**
    *   Add or update a reminder record.
    *   The event form doesn't show the reminder form if a reminder exists,
    *   so normally the update isn't needed.
    *
    *   @param  integer $days   days_notice value
    *   @param  string  $email  Email address submitted
    *   @return boolean         True on success, False on failure or no access
    */
    public function Add($days, $email='')
    {
        global $_USER, $_TABLES;

        if (COM_isAnonUser() ||
                $this->Repeat->rp_id == '' ||
                !$this->Repeat->Event->hasAccess(2)) {
            return false;
        }

        $uid = (int)$_USER['uid'];
        if ($email == '') {
            $email = DB_getItem($_TABLES['users'], 'email', "uid = $uid");
        }
        if ($days < 1) $days = 7;

        if ($this->isNew) {
            $sql = "INSERT INTO {$_TABLES['evlist_remlookup']} SET
                    eid = '{$this->eid}',
                    rp_id = '{$this->rp_id}',
                    uid = '$uid',
                    name = '" . DB_escapeString(COM_getDisplayName($_USER['uid'])) . "',
                    email = '" . DB_escapeString($email) . "',
                    date_start = '{$this->Repeat->dtStart1->toUnix()}',
                    days_notice = '" . (int)$days . "'";
        } else {
            $sql = "UPDATE {$_TABLES['evlist_remlookup']} SET
                    date_start = '{$this->Repeat->dtStart1->toUnix()}',
                    days_notice = '" . (int)$days . "'
                    WHERE  eid = '{$this->eid}'
                    AND rp_id = '{$this->rp_id}'
                    AND uid = '$uid'";
        }
        DB_query($sql, 1);
        return DB_error() ? false : true;
    }


    /**
    *   Delete the current reminder record from the database
    */
    public function Delete()
    {
        global $_TABLES;

        $flds = array('eid', 'uid');
        $vals = array($this->eid, $this->uid);
        if ($this->rp_id != '') {
            $flds[] = 'rp_id';
            $vals[] = $this->rp_id;
        }
        DB_delete($_TABLES['evlist_remlookup'], $flds, $vals);
        $this->rp_id = '';
        $this->isNew = true;
        return true;
    }


    /**
    *   Get all the reminders that are ready for notification.
    *
    *   @return array   Array of Reminder objects
    */
    public static function getCurrent()
    {
        global $_TABLES;

        $Rems = array();
        $sql = "SELECT * FROM {$_TABLES['evlist_remlookup']}
                WHERE date_start <= (UNIX_TIMESTAMP() + (days_notice * 86400))";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $Rems[] = new Reminder($A['rp_id']);
        }
        return $Rems;
    }


    /**
    *   Count reminders for a specific event, repeat and user
    *   This is used to determine whether the reminder form is shown or not.
    *
    *   @param  string  $ev_id  Event ID
    *   @param  integer $rp_id  Repeat ID
    *   @param  integer $uid    User ID, default to current user
    *   @return integer         Count of reminders, should be 0 or 1
    */
    public static function countReminders($ev_id, $rp_id, $uid = 0)
    {
        global $_TABLES, $_USER;

        if ($uid < 1) {
            $uid = $_USER['uid'];
        }
        return DB_count($_TABLES['evlist_remlookup'],
                        array('eid', 'rp_id', 'uid'),
                        array($ev_id, $rp_id, $uid));
    }


    /**
    *   Send the reminder
    */
    public function Send()
    {
        global $_TABLES, $_CONF, $LANG, $LANG_EVLIST;

        // Guard against sending reminders for invalid events
        if ($this->Repeat->rp_id < 1) {
            return;
        }
        // Load the user's language
        if (!isset(self::$langs[$this->uid])) {
            self::$langs[$this->uid] = DB_getItem($_TABLES['users'], 'language',
                    "uid = '{$this->uid}'");
        }
        $LANG = plugin_loadlanguage_evlist(self::$langs[$this->uid]);

        $subject = $LANG['rem_subject'];
        $title = COM_stripslashes($this->Repeat->Event->title);
        $summary = COM_stripslashes($this->Repeat->Event->summary);
        $date_start = $this->Repeat->dtStart1->format($_CONF['dateonly']);
        $event_url = EVLIST_URL . '/event.php?eid=' . $this->rp_id;
        if ($this->Repeat->Event->allday == 1) {
            $times = $LANG['allday'];
        } else {
            $times = EVLIST_formattedTime($this->Repeat->time_start1) . ' - ' .
                    EVLIST_formattedTime($this->Repeat->time_end1);
            if ($this->Repeat->Event->split == 1) {
                $times .= ', ' . EVLIST_formattedTime($this->Repeat->rp_time_start2) .
                    ' - ' . EVLIST_formattedTime($this->Repeat->rp_time_end2);
            }
        }

        $T = new \Template(EVLIST_PI_PATH . '/templates/');
        $T->set_file(array(
            'msg' => 'reminder_mail.thtml',
            'addr' => 'address.thtml',
        ) );

        // Determine if there is any location info to include and set a
        // template flag if so.
        foreach (array('location', 'street', 'city', 'province', 'postal', 'country') as $x) {
            if ($this->Repeat->Event->Detail->$x != '') {
                $T->set_var('have_address', true);
                break;
            }
        }

        $T->set_var(array(
            'lang_what' => $LANG['what'],
            'lang_when' => $LANG['when'],
            'lang_where'    => $LANG['where'],
            'what'      => $this->Repeat->Event->Detail->title,
            'when'      => $date_start . ' ' . $times,
            'location'  => $this->Repeat->Event->Detail->location,
            'street'    => $this->Repeat->Event->Detail->street,
            'city'      => $this->Repeat->Event->Detail->city,
            'province'  => $this->Repeat->Event->Detail->province,
            'postal'    => $this->Repeat->Event->Detail->postal,
            'country'   => $this->Repeat->Event->Detail->country,
            'url'       => sprintf($LANG_EVLIST['rem_url'], $event_url),
            'summary'   => COM_stripSlashes($this->Repeat->Event->Detail->summary),
            'msg1'      => $LANG['rem_msg1'],
            'msg2'      => $LANG['rem_msg2'],
        ) );
        $T->parse('address_info', 'addr', true);
        $T->parse('output', 'msg');
        $message = $T->finish($T->get_var('output'));
        $mailto = COM_formatEmailAddress($this->name, $this->email);

        //mail reminder
        COM_mail($mailto, $subject, $message, '', true);
    }

}   // class Reminder

?>
