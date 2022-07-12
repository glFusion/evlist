<?php
/**
 * Class to manage event reminders for the EvList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2020 Lee Garner <lee@leegarner.com>
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
     * @return  boolean     True if a record was found and read, False on failure.
     */
    public function Read() : bool
    {
        global $_TABLES;

        $db = Database::getInstance();
        $sql = "SELECT * FROM {$_TABLES['evlist_remlookup']}
                WHERE eid = ?
                AND uid = ?";
        $params = array($this->eid, $this->uid);
        $types = array(Database::STRING, Database::INTEGER);
        if ($this->rp_id > 0) {
            $sql .= " AND rp_id = ?";
            $params[] = $this->rp_id;
            $types[] = Database::INTEGER;
        }
        try {
            $data = $db->conn->executeQuery($sql, $params, $types)->fetchAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = false;
        }
        if (is_array($data)) {
            $this->setVars($data);
            $this->isNew = false;
            return true;
        } else {
            $this->isNew = true;
            return false;
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
    public function Add(int $days, string $email='') : bool
    {
        global $_USER, $_TABLES;

        $retval = true;
        if (
            COM_isAnonUser() ||
            $this->Repeat->getID() == 0 ||
            !$this->Repeat->getEvent()->hasAccess(2)
        ) {
            return $retval;
        }

        $db = Database::getInstance();
        $uid = (int)$_USER['uid'];
        if ($email == '') {
            $db = Database::getInstance();
            try {
                $email = $db->getItem($_TABLES['users'], 'email', array('uid' => $uid));
            } catch (\Exception $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $email = false;
            }
            $email = (string)$email;
        }
        if ($days < 1) $days = 7;

        $vals = array(
            'date_start' => $this->Repeat->getDateStart1()->toUnix(),
            'days_notice' => (int)$days,
            'timestamp' => time(),
        );
        $types = array(
            Database::INTEGER,
            Database::INTEGER,
            Database::INTEGER,
        );
        if ($this->isNew) {
            try {
                $vals['eid'] = $this->eid;
                $vals['rp_id'] = $this->rp_id;
                $vals['uid'] = $uid;
                $vals['name'] = COM_getDisplayName($_USER['uid']);
                $vals['email'] =$email;
                $types[] = Database::STRING;
                $types[] = Database::INTEGER;
                $types[] = Database::INTEGER;
                $types[] = Database::STRING;
                $types[] = Database::STRING;
                $db->conn->insert(
                    $_TABLES['evlist_remlookup'],
                    $vals,
                    $types
                );
            } catch (\Exception $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $retval = false;
            }
        } else {
            try {
                $conds = array(
                    'eid' => $this->eid,
                    'rp_id' => $this->rp_id,
                    'uid' => $this->uid,
                );
                $types[] = Database::STRING;
                $types[] = Database::INTEGER;
                $types[] = Database::INTEGER;
                $db->conn->update(
                    $_TABLES['evlist_remlookup'],
                    $vals,
                    $conds,
                    $types
                );
            } catch (\Exception $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $retval = false;
            }
        }
        return $retval;
    }


    /**
     * Delete the current reminder record from the database.
     *
     * @param   string  $ev_id  Master event ID
     * @param   integer $rp_id  Repeat ID, zero to delete all for event
     * @param   intger  $uid    User ID to delete for a single user
     * @return  boolean     Tue on success, False on error
     */
    public static function Delete(string $ev_id, int $rp_id=0, int $uid=0) : bool
    {
        global $_TABLES;

        $db = Database::getInstance();
        $criteria = array('eid' => $ev_id);
        $types = array(Database::STRING);
        if ($rp_id != '') {
            $criteria['rp_id'] = $rp_id;
            $types[] = Database::INTEGER;
        }
        if ($uid > 0) {
            $criteria['uid'] = $uid;
            $types[] = Database::INTEGER;
        }
        try {
            $db->conn->delete($_TABLES['evlist_remlookup'], $criteria, $types);
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return false;
        }
        return true;
    }


    /**
     * Get all the reminders that are ready for notification.
     *
     * @return  array   Array of Reminder objects
     */
    public static function getCurrent() : array
    {
        global $_TABLES;

        $Rems = array();
        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        try {
            $data = $qb->select('rem.*')
               ->from($_TABLES['evlist_remlookup'], 'rem')
               ->leftJoin('rem', $_TABLES['evlist_events'], 'ev', 'ev.id = rem.eid')
               ->leftJoin('rem', $_TABLES['evlist_repeat'], 'rp', 'rp.rp_id = rem.rp_id')
               ->where('rem.date_start <= (UNIX_TIMESTAMP() + (rem.days_notice * 86400))')
               ->andWhere('ev.status = :status')
               ->andWhere('rp.rp_status = :enabled')
               ->setParameter('status', Status::ENABLED, Database::INTEGER)
               ->setParameter('enabled', Status::ENABLED, Database::INTEGER)
               ->execute()
               ->fetchAllAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = false;
        }

        if (is_array($data)) {
            foreach ($data as $A) {
                $Rems[] = new self($A['rp_id'], $A['uid']);
            }
        }
        return $Rems;
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
            $db = Database::getInstance();
            self::$langs[$this->uid] = $db->getItem(
                $_TABLES['users'],
                'language',
                array('uid', $this->uid)
            );
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

        // Convert the image to create full URLs from relative.
        \LGLib\SmartResizer::create()
            ->withLightbox(false)
            ->withFullUrl(true)
            ->convert($summary);

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
            'summary'   => $summary,
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
            'subject' => sprintf($LANG_EVLIST['rem_subject'], $Detail->getTitle()),
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
        self::Delete($this->eid, $this->rp_id, $this->uid);
    }

}
