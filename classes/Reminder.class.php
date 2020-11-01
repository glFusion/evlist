<?php
/**
 * Class to manage event reminders for the EvList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.3
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;


/**
 * Class for reminders
 * @package evlist
 */
class Reminder
{
    /** Reminder record ID.
     * @var integer */
    private $rem_id = 0;

    /** Subscribing user ID.
     * @var integer */
    private $uid = 0;

    /** Event recurrance ID.
     * @var integer */
    private $rp_id = 0;

    /** Event record ID.
     * @var string */
    private $eid = '';

    /** User name.
     * @var string */
    private $name = '';

    /** User Email address.
     * @var string */
    private $email = '';

    /** Starting date of the event recurrance as a timestamp.
     * @var integer */
    private $date_start = 0;

    /** How many days ahead of event to send notification.
     * @var integer */
    private $days_notice = 3;

    /** Indicates if this is a new record or not.
     * @var boolean */
    private $isNew = true;

    /** Repeat object for this reminder.
     * @var object */
    private $Repeat = NULL;

    /** Holder for language arrays.
     * @var array */
    private static $langs = array();


    /**
     * Constructor.
     * Reads in the specified reminder from the lookup table.
     * Sets isNew to false if a record is found, otherwise isNew will be true
     *
     * @param   integer $rp_id      Optional Repeat ID
     * @param   integer $uid        Optional user ID
     */
    public function __construct($rp_id='', $uid='')
    {
        global $_USER;

        if (empty($uid)) {
            $uid = $_USER['uid'];
        }
        if ($rp_id !== '') {
            $this->Repeat = Repeat::getInstance($rp_id);
        }
        if ($this->Repeat->getID() > 0) {
            $this->eid = $this->Repeat->getEventID();
            $this->rp_id = $rp_id;
            $this->uid = $uid;
            $this->Read();
        } else {
            $this->isNew = true;
        }
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @return  boolean     True if a record was read, False on failure.
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
     * Sets all the values in array $A into object fields.
     * Only valid values are set, extras are ignored.
     *
     * @param   array   $A      Array of name=>value pairs
     */
    private function setVars($A)
    {
        $this->rem_id = (int)$A['rem_id'];
        $this->eid = COM_sanitizeId($A['eid']);
        $this->rp_id = (int)$A['rp_id'];
        $this->date_start = (int)$A['date_start'];
        $this->uid = (int)$A['uid'];
        $this->name = $A['name'];
        $this->email = $A['email'];
        $this->days_notice = (int)$A['days_notice'];
    }


    /**
     * See if this is a new record, or was not found.
     *
     * @return  boolean     1 if new, 0 if not
     */
    public function isNew()
    {
        return $this->isNew ? 1 : 0;
    }


    /**
     * Get the days notice for this reminder.
     *
     * @return  integer     Days notice value
     */
    public function getDays()
    {
        return (int)$this->days_notice;
    }


    /**
     * Add or update a reminder record.
     * The event form doesn't show the reminder form if a reminder exists,
     * so normally the update isn't needed.
     *
     * @param   integer $days   days_notice value
     * @param   string  $email  Email address submitted
     * @return  boolean         True on success, False on failure or no access
     */
    public function Add($days, $email='')
    {
        global $_USER, $_TABLES;

        if (
            COM_isAnonUser() ||
            $this->Repeat->getID() == 0 ||
            !$this->Repeat->getEvent()->hasAccess(2)
        ) {
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
                    date_start = '{$this->Repeat->getDateStart1()->toUnix()}',
                    days_notice = '" . (int)$days . "'";
        } else {
            $sql = "UPDATE {$_TABLES['evlist_remlookup']} SET
                    date_start = '{$this->Repeat->getDateStart1()->toUnix()}',
                    days_notice = '" . (int)$days . "'
                    WHERE  eid = '{$this->eid}'
                    AND rp_id = '{$this->rp_id}'
                    AND uid = '$uid'";
        }
        DB_query($sql, 1);
        return DB_error() ? false : true;
    }


    /**
     * Delete the current reminder record from the database
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
     * Get all the reminders that are ready for notification.
     *
     * @return  array   Array of Reminder objects
     */
    public static function getCurrent()
    {
        global $_TABLES;

        $Rems = array();
        $sql = "SELECT * FROM {$_TABLES['evlist_remlookup']}
                WHERE date_start <= (UNIX_TIMESTAMP() + (days_notice * 86400))";
        //echo $sql;die;
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $Rems[] = new self($A['rp_id'], $A['uid']);
        }
        return $Rems;
    }


    /**
     * Count reminders for a specific event, repeat and user.
     * This is used to determine whether the reminder form is shown or not.
     *
     * @param   string  $ev_id  Event ID
     * @param   integer $rp_id  Repeat ID
     * @param   integer $uid    User ID, default to current user
     * @return  integer         Count of reminders, should be 0 or 1
     */
    public static function countReminders($ev_id, $rp_id, $uid = 0)
    {
        global $_TABLES, $_USER;

        if ($uid < 1) {
            $uid = $_USER['uid'];
        }
        return DB_count(
            $_TABLES['evlist_remlookup'],
            array('eid', 'rp_id', 'uid'),
            array($ev_id, $rp_id, $uid)
        );
    }


    /**
     * Send the reminder.
     */
    public function Send()
    {
        global $_TABLES, $_CONF, $LANG, $LANG_EVLIST;

        // Guard against sending reminders for invalid events
        if ($this->Repeat->getID() < 1 || $this->email == '') {
            return;
        }
        // Load the user's language
        if (!isset(self::$langs[$this->uid])) {
            self::$langs[$this->uid] = DB_getItem($_TABLES['users'], 'language',
                    "uid = '{$this->uid}'");
        }
        $LANG = plugin_loadlanguage_evlist(self::$langs[$this->uid]);
        $Detail = $this->Repeat->getEvent()->getDetail();
        $subject = $LANG['rem_subject'];
        $title = COM_stripslashes($Detail->getTitle());
        $summary = COM_stripslashes($Detail->getSummary());
        $date_start = $this->Repeat->getDateStart1()->format($_CONF['dateonly']);
        $event_url = EVLIST_URL . '/event.php?eid=' . $this->rp_id;
        if ($this->Repeat->getEvent()->isAllDay()) {
            $times = $LANG['allday'];
        } else {
            $times = EVLIST_formattedTime($this->Repeat->getTimeStart1()) . ' - ' .
                    EVLIST_formattedTime($this->Repeat->getTimeEnd1());
            if ($this->Repeat->getEvent()->isSplit()) {
                $times .= ', ' . EVLIST_formattedTime($this->Repeat->getTimeStart2()) .
                    ' - ' . EVLIST_formattedTime($this->Repeat->getTimeEnd2());
            }
        }

        $T = new \Template(array(
            $_CONF['path_layout'] . 'email/',
            __DIR__ . '/../templates/notify/',
            __DIR__ . '/../templates/',
        ) );
        $T->set_file(array(
            'html_msg' => 'mailtemplate_html.thtml',
            'text_msg' => 'mailtemplate_text.thtml',
            'msg' => 'rem_message.thtml',
            'addr' => 'address.thtml',
        ) );

        if (!empty($Detail->getAddress())) {
            $T->set_var('have_address', true);
        }

        $T->set_var(array(
            'lang_what' => $LANG['what'],
            'lang_when' => $LANG['when'],
            'lang_where'    => $LANG['where'],
            'what'      => $Detail->getTitle(),
            'when'      => $date_start . ' ' . $times,
            'location'  => $Detail->getLocation(),
            'street'    => $Detail->getStreet(),
            'city'      => $Detail->getCity(),
            'province'  => $Detail->getProvince(),
            'postal'    => $Detail->getPostal(),
            'country'   => $Detail->getCountry(),
            'url'       => sprintf($LANG_EVLIST['rem_url'], $event_url),
            'summary'   => COM_stripSlashes($Detail->getSummary()),
            //'msg1'      => $LANG['rem_msg1'],
            //'msg2'      => $LANG['rem_msg2'],
        ) );
        $T->parse('address_info', 'addr');
        $T->parse('output', 'msg');
        $html_content = $T->finish($T->get_var('output'));
        $T->set_block('html_msg', 'content', 'contentblock');
        $T->set_var('content_text', $html_content);
        $T->parse('contentblock', 'content',true);

        $html2TextConverter = new \Html2Text\Html2Text($html_content);
        $text_content = $html2TextConverter->getText();
        $T->set_block('text_msg', 'contenttext', 'contenttextblock');
        $T->set_var('content_text', $text_content);
        $T->parse('contenttextblock', 'contenttext',true);

        $T->parse('output', 'html_msg');
        $html_msg = $T->finish($T->get_var('output'));
        $T->parse('textoutput', 'text_msg');
        $text_msg = $T->finish($T->get_var('textoutput'));

        $msgData = array(
            'htmlmessage' => $html_msg,
            'textmessage' => $text_msg,
            'subject' => $LANG_EVLIST['rem_title'],
            'from' => array(
                'name' => $_CONF['site_name'],
                'email' => $_CONF['noreply_mail'],
            ),
            'to' => array(
                'name' => $this->name,
                'email' => $this->email,
            ),
        );
        COM_emailNotification($msgData);
    }

}   // class Reminder

