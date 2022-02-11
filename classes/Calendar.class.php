<?php
/**
 * Class to manage calendars.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;

/**
 * Class for calendars.
 * @package evlist
 */
class Calendar
{
    use \Evlist\Traits\DBO;        // Import database operations

    /** Table name, for DBO operations.
     * @var string */
    protected static $TABLE = 'evlist_calendars';

    /** Key field name, for DBO operations.
     * @var string */
    protected static $F_ID = 'cal_id';

    /** Flag to indicate a new record.
     * @var boolean */
    private $isNew = true;

    /** Calendar record ID.
     * @var integer */
    private $cal_id = 0;

    /** Owner permission.
     * @var integer */
    private $perm_owner = 3;

    /** Group permission.
     * Indicates who can submit events to this calendar, not editing the calendar.
     * @var integer */
    private $perm_group = 3;

    /** Site member permission.
     * @var integer */
    private $perm_members = 2;  // view only by default

    /** Anonymous user permission.
     * @var integer */
    private $perm_anon = 2;     // view only by default

    /** Calendar owner user ID.
     * @var integer */
    private $owner_id = 2;      // Root by default

    /** Calendar group ID, for group permission.
     * @var integer */
    private $group_id = 13;     // logged-in users

    /** Calendar sort order for display.
     * @var integer */
    private $orderby = 9999;

    /** Calendar enabled status.
     * @var boolean */
    private $cal_status = 1;

    /** Enable Ical subscription?
     * @var boolean */
    private $cal_ena_ical = 1;

    /** Show events in the upcoming events block?
     * @var bool */
    private $cal_show_upcoming = 1;

    /** Show events in the centerblock?
     * @var bool */
    private $cal_show_cb = 1;

    /** Calendar descriptive name.
     * @var string */
    private $cal_name = '';

    /** Foreground color.
     * @var string */
    private $fgcolor = '#000000';

    /** Background color.
     * @var string */
    private $bgcolor = '#FFFFFF';

    /** Icon name. Just the unique portion from the UIkit icon set.
     * @var string */
    private $cal_icon = '';


    /**
     * Constructor.
     * Create an empty calendar object, or read an existing one.
     *
     * @param   mixed   $calendar   Calendar ID to read, or array of info
     */
    public function __construct($calendar = 0)
    {
        global $_EV_CONF, $_USER, $LANG_EVLIST;

        if (is_array($calendar)) {
            // Already have values from the DB
            $this->setVars($calendar, true);
        } elseif ($calendar != 0) {
            // Have a calendar ID to read
            $this->cal_id = (int)$calendar;
            if ($this->Read())
                $this->isNew = false;
        } else {
            // Default, set perms from configuration.
            $this->perm_owner   = $_EV_CONF['default_permissions'][0];
            $this->perm_group   = $_EV_CONF['default_permissions'][1];
            $this->perm_members = $_EV_CONF['default_permissions'][2];
            $this->perm_anon    = $_EV_CONF['default_permissions'][3];
            $this->owner_id     = $_USER['uid'];
        }
    }


    /**
     * Get an instance of a calendar.
     * Saves objects in a static variable to minimize DB lookups
     *
     * @param   integer $cal_id Calendar ID
     * @return  object          Calendar object
     */
    public static function getInstance($cal_id)
    {
        $Cals = self::getAll();
        return isset($Cals[$cal_id]) ? $Cals[$cal_id] : new self($cal_id);
    }


    /**
     * Read an existing calendar record into this object.
     *
     * @param   integer $cal_id Optional calendar ID, $this->cal_id used if 0
     */
    public function Read($cal_id = 0)
    {
        global $_TABLES;

        if ($cal_id != 0)
            $this->cal_id = $cal_id;

        $sql = "SELECT *
            FROM {$_TABLES['evlist_calendars']}
            WHERE cal_id='{$this->cal_id}'";
        //echo $sql;
        $result = DB_query($sql);

        if (!$result || DB_numRows($result) == 0) {
            $this->cal_id = 0;
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->setVars($row, true);
            return true;
        }
    }


    /**
     * See if the Ical subscription is enabled.
     *
     * @return  boolean     True if enabled, False if not
     */
    public function isIcalEnabled()
    {
        return $this->cal_ena_ical ? 1 : 0;
    }


    /**
     * Get the calendar record ID.
     *
     * @return  integer     Calendar ID
     */
    public function getID()
    {
        return (int)$this->cal_id;
    }


    /**
     * Get the calendar decriptive name.
     *
     * @return  string      Calendar name
     */
    public function getName()
    {
        return $this->cal_name;
    }


    /**
     * Get the foreground color used for display.
     *
     * @return  string      Foreground color
     */
    public function getFGcolor() : string
    {
        return $this->fgcolor;
    }


    /**
     * Get the background color used for display.
     *
     * @return  string      Background color
     */
    public function getBGcolor() : string
    {
        return $this->bgcolor;
    }


    /**
     * Check the current users's access to this calendar.
     *
     * @return  integer     3 for read/edit 2 for read only 0 for no access
     */
    public function getSecAccess()
    {
        return SEC_hasAccess(
            $this->owner_id, $this->group_id,
            $this->perm_owner, $this->perm_group,
            $this->perm_members, $this->perm_anon
        );
    }


    /**
     * Set the value of all variables from an array, either DB or a form.
     *
     * @param   array   $A      Array of fields
     * @param   boolean $fromDB True if $A is from the database, false for form
     */
    public function setVars($A, $fromDB=false)
    {
        if (isset($A['cal_id']) && !empty($A['cal_id']))
            $this->cal_id = $A['cal_id'];

        // These fields come in the same way from DB or form
        $fields = array('cal_name', 'fgcolor', 'bgcolor',
            'owner_id', 'group_id', 'cal_icon');
        foreach ($fields as $field) {
            if (isset($A[$field]))
                $this->$field = $A[$field];
        }

        if (isset($A['cal_status']) && $A['cal_status'] == 1) {
            $this->cal_status = 1;
        } else {
            $this->cal_status = 0;
        }
        if (isset($A['cal_ena_ical']) && $A['cal_ena_ical'] == 1) {
            $this->cal_ena_ical = 1;
        } else {
            $this->cal_ena_ical = 0;
        }
        if (isset($A['cal_show_upcoming']) && $A['cal_show_upcoming'] == 1) {
            $this->cal_show_upcoming = 1;
        } else {
            $this->cal_show_upcoming = 0;
        }

        if (isset($A['cal_show_cb']) && $A['cal_show_cb'] == 1) {
            $this->cal_show_cb = 1;
        } else {
            $this->cal_show_cb = 0;
        }

        $this->orderby = isset($A['orderby']) ? $A['orderby'] : 0;
        if ($fromDB) {
            $this->perm_owner   = $A['perm_owner'];
            $this->perm_group   = $A['perm_group'];
            $this->perm_members = $A['perm_members'];
            $this->perm_anon    = $A['perm_anon'];
        } else {
            if (isset($A['fg_inherit'])) $this->fgcolor = '';
            if (isset($A['bg_inherit'])) $this->bgcolor = '';
            $perms = SEC_getPermissionValues($_POST['perm_owner'],
                $_POST['perm_group'], $_POST['perm_members'],
                $_POST['perm_anon']);
            $this->perm_owner   = $perms[0];
            $this->perm_group   = $perms[1];
            $this->perm_members = $perms[2];
            $this->perm_anon    = $perms[3];
        }
    }


    /**
     * Provide the form to create or edit a calendar.
     *
     * @return  string  HTML for editing form
     */
    public function Edit()
    {
        global $_EV_CONF, $_SYSTEM;

        $T = new \Template(EVLIST_PI_PATH . '/templates');
        $T->set_file(array(
            'modify'    => 'calEditForm.thtml',
            'tips'      => 'tooltipster.thtml',
        ) );

        // Create the calendar selection. Include all calendars except the
        // current one.
        $cals = self::getAll();
        $orderby_sel = '';
        foreach ($cals as $C) {
            if ($C->cal_id == $this->cal_id) continue;
            $sel = ($C->orderby == $this->orderby - 10) ? 'selected="selected"' : '';
            $orderby_sel .= '<option value="' . $C->orderby . '"' . $sel . '>' . $C->cal_name . '</option>' . LB;
        }
        $T->set_var(array(
            'cal_id'        => $this->cal_id,
            'cal_name'      => $this->cal_name,
            'fgcolor'       => $this->fgcolor,
            'bgcolor'       => $this->bgcolor,
            'owner_id'      => $this->owner_id,
            'ownername'     => COM_getDisplayName($this->owner_id),
            'group_dropdown' =>
                SEC_getGroupDropdown($this->group_id, 3),
            'permissions_editor' =>
                SEC_getPermissionsHTML($this->perm_owner, $this->perm_group,
                        $this->perm_members, $this->perm_anon),
            'stat_chk'      => $this->cal_status == 1 ? EVCHECKED : '',
            'ical_chk'      => $this->cal_ena_ical == 1 ? EVCHECKED : '',
            'upcoming_chk'  => $this->cal_show_upcoming == 1 ? EVCHECKED : '',
            'cb_chk'        => $this->cal_show_cb == 1 ? EVCHECKED : '',
            'can_delete'    => $this->cal_id > 1 ? 'true' : '',
            'doc_url'       => EVLIST_getDocUrl('calendar', 'evlist'),
            'colorpicker_js' => LGLIB_colorpicker(array(
                'fg_id'     => 'fld_fgcolor',
                'fg_color'  => $this->fgcolor,
                'bg_id'     => 'fld_bgcolor',
                'bg_color'  => $this->bgcolor,
                'sample_id' => 'sample',
            ) ),
            'fg_inherit_chk' => $this->fgcolor == '' ? EVCHECKED : '',
            'bg_inherit_chk' => $this->bgcolor == '' ? EVCHECKED : '',
            'icon'          => $this->cal_icon,
            'disp_icon'     => Icon::getIcon($this->cal_icon),
            'orderby_sel'   => $orderby_sel,
            'orderby'       => $this->orderby,
        ) );

        $T->parse('tooltipster_js', 'tips');
        $T->parse('output','modify');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Insert or update a calendar.
     *
     * @param   array    $A  Array of data to save, typically from form
     */
    public function Save($A=array())
    {
        global $_TABLES, $_EV_CONF;

        if (is_array($A) && !empty($A)) {
            $this->setVars($A);
        }

        if ($this->cal_id != 0) {
            $this->isNew = false;
        } else {
            $this->isNew = true;
        }

        if (isset($_POST['old_orderby']) && $_POST['old_orderby'] != $this->orderby) {
            $this->orderby += 5;
        }

        $fld_sql = "cal_name = '" . DB_escapeString($this->cal_name) ."',
            fgcolor = '" . DB_escapeString($this->fgcolor) . "',
            bgcolor = '" . DB_escapeString($this->bgcolor) . "',
            cal_status = '{$this->cal_status}',
            cal_ena_ical = '{$this->cal_ena_ical}',
            cal_show_upcoming = '{$this->cal_show_upcoming}',
            cal_show_cb = '{$this->cal_show_cb}',
            perm_owner = '{$this->perm_owner}',
            perm_group = '{$this->perm_group}',
            perm_members = '{$this->perm_members}',
            perm_anon = '{$this->perm_anon}',
            owner_id = '{$this->owner_id}',
            group_id = '{$this->group_id}',
            cal_icon = '" . DB_escapeString($this->cal_icon) . "',
            orderby = '{$this->orderby}'";

        if ($this->isNew) {
            $sql = "INSERT INTO {$_TABLES['evlist_calendars']} SET
                    $fld_sql";
        } else {
            $sql = "UPDATE {$_TABLES['evlist_calendars']} SET
                    $fld_sql
                    WHERE cal_id='{$this->cal_id}'";
        }

        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            $this->cal_id = DB_insertId();
            // Saving from a form, add 5 to the orderby value so it goes between
            // existing calendars. First check if the order was changed.
            if (isset($_POST['old_orderby']) && $_POST['old_orderby'] != $this->orderby) {
                self::reOrder();
            }
            if (!$this->isNew) {
                // Clear events to force re-reading of permissions.
                Cache::clear('events');
            }
            // Clear the cache if updating an existing calendar.
            Cache::clear('calendars');
            return true;
        } else {
            return false;
        }
    }   // function Save()


    /**
     * Deletes the current calendar.
     * Deletes all events, detail and repeats associated with this calendar,
     * or moves them to a different calendar if specified.
     *
     * @param   integer $newcal ID of new calendar to use for events, etc.
     */
    public function Delete($newcal = 0)
    {
        global $_TABLES;

        // Can't delete calendar #1.  Shouldn't get to this point, but
        // return an error if we do. Also don't try to delete an invalid
        // calendar.
        if ($this->cal_id < 2) {
            return false;
        }

        $newcal = (int)$newcal;
        if ($newcal != 0) {
            // Make sure the new calendar exists
            if (DB_count($_TABLES['evlist_calendars'], 'cal_id', $newcal) != 1) {
                return false;
            }

            // Update all the existing events with the new calendar ID
            $sql = "UPDATE {$_TABLES['evlist_events']}
                    SET cal_id = '$newcal'
                    WHERE cal_id='{$this->cal_id}'";
            DB_query($sql, 1);
        } else {
            // Not changing to a new calendar, delete all events for this one
            $sql = "SELECT id FROM {$_TABLES['evlist_events']}
                    WHERE cal_id = '{$this->cal_id}'";
            $result = DB_query($sql);
            while ($A = DB_fetchArray($result, false)) {
                DB_delete($_TABLES['evlist_repeat'], 'ev_id', $A['id']);
                DB_delete($_TABLES['evlist_detail'], 'ev_id', $A['id']);
                DB_delete($_TABLES['evlist_events'], 'id', $A['id']);
            }
        }
        DB_delete($_TABLES['evlist_calendars'], 'cal_id', $this->cal_id);
        Cache::clear();     // Just clear all cache items
    }


    /**
     * Display a confirmation form to the user to confirm the deletion.
     * Shows the user how many events are tied to the calendar being
     * deleted.
     *
     * @return  string      HTML for confirmation form.
     */
    public function DeleteForm()
    {
        global $_TABLES, $LANG_EVLIST;

        $T = new \Template(EVLIST_PI_PATH . '/templates/');
        $T->set_file('delcalfrm', 'delcalform.thtml');

        $T->set_var(array(
            'cal_id'    => $this->cal_id,
            'cal_name'  => $this->cal_name,
        ) );
        $events = DB_count($_TABLES['evlist_events'], 'cal_id', $this->cal_id);
        if ($events > 0) {
            $cal_select = COM_optionList($_TABLES['evlist_calendars'],
                    'cal_id,cal_name', '1', 1, "cal_id <> {$this->cal_id}");

            $T->set_var(array(
                'has_events' => sprintf($LANG_EVLIST['del_cal_events'], $events),
                'newcal_select' => $cal_select,
            ) );
        }

        $T->parse('output', 'delcalfrm');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Determine if the current calendar is in use by any events.
     *
     * @return  mixed   Number of events using the calendar, false if unused.
     */
    public function isUsed()
    {
        global $_TABLES;

        $cnt = DB_count($_TABLES['evlist_events'], 'cal_id', $this->cal_id);
        if ($cnt > 0) {
            return $cnt;
        } else {
            return false;
        }
    }


    /**
     * Get all the enabled ICal links.
     *
     * @param   boolean $incl_all   True to include the All Calendars link
     * @return  array   Array of active Ical links
     */
    public static function getIcalLinks($incl_all=true)
    {
        global $LANG_EVLIST;

        $retval = array();
        if ($incl_all) {
            $retval[] = COM_createLink(
                $LANG_EVLIST['all_calendars'],
                EVLIST_URL . '/ical.php',
                array(
                    'rel' => 'nofollow',
                )
            );
        }
        $Cals = self::getAll(true);
        foreach ($Cals as $Cal) {
            if ($Cal->isIcalEnabled()) {
                $retval[] = $Cal->icalUrl();
            }
        }
        return $retval;
    }


    /**
     * Get the ICal link for this calendar.
     * Does not consider whether Ical is enabled.
     *
     * @return  string  URL string for the ICal link
     */
    public function icalUrl()
    {
        return COM_createLink(
            $this->getName(),
            EVLIST_URL . '/ical.php?cal=' . $this->getID(),
            array(
                'rel' => 'nofollow',
            )
        );
    }


    /**
     * Get the options for a calendar selection list.
     * Leverages self::getAll() to re-use the database query.
     *
     * @param   integer $selected   ID of selected calendar
     * @param   boolean $enabled    True to show only enabled calendars
     * @param   integer $access     Access level required (1 - 3)
     * @return  string      Option tags
     */
    public static function optionList($selected = 0, $enabled = true, $access = 0)
    {
        $retval = '';
        $cals = self::getAll();
        foreach ($cals as $cal_id=>$cal) {
            // Skip disabled calendars if enabled flag is set
            if ($enabled && $cal->cal_status == 0) continue;
            // Check access if acces level is set
            if ($access > 0 && !$cal->hasAccess($access)) continue;

            $sel = $cal_id == $selected ? EVSELECTED : '';
            $retval .= '<option ' . $sel . ' value="' . $cal_id . '">' .
                        $cal->cal_name . '</option>' . LB;
        }
        return $retval;
    }


    /**
     * Get all calendars.
     *
     * $param   boolean $enabled    True to get only enabled calendars
     * return   array       Array of calendar objects
     */
    public static function getAll($enabled=false)
    {
        global $_TABLES;

        $cals = array();
        $sql = "SELECT * FROM {$_TABLES['evlist_calendars']} ";
        if ($enabled) {
            $sql .= "WHERE cal_status = 1 ";
        }
        $sql .= "ORDER BY orderby ASC";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $cals[$A['cal_id']] = new self($A);
        }
        return $cals;
    }


    /**
     * Public function to check access to this calendar.
     *
     * @param   integer $level  Access level required
     * @return  boolean         True = has sufficieng access, False = not
     */
    public function hasAccess($level=2)
    {
        // Admin & editor has all rights
        if (plugin_ismoderator_evlist()) return true;

        $access = SEC_hasAccess(
            $this->owner_id, $this->group_id,
            $this->perm_owner, $this->perm_group,
            $this->perm_members, $this->perm_anon
        );
        return $access >= $level ? true : false;
    }


    /**
     * Get the calendar that's mapped to a plugin name.
     * Returns the calendar object. If no mapping exists, or the mapping
     * refers to a non-existent calendar, then the default calendar object
     * is returned.
     *
     * @param   string  $pi_name    Plugin or calendar name
     * @return  object      Calendar object
     */
    public static function getMapped($pi_name)
    {
        global $_EV_CONF;

        // Check for a configured calendar mapped to this plugin name.
        // Default to "1" if none.
        if (isset($_EV_CONF['pi_cal_map'][$pi_name])) {
            $cal_id = (int)$_EV_CONF['pi_cal_map'][$pi_name];
        } else {
            $cal_id = 1;        // default
        }

        // Read the calendar to verify that it actually exists.
        // Return the default calendar if it doesn't.
        $Cal = self::getInstance($cal_id);
        if (!$Cal || $Cal->getID() == 0) {
            $Cal = self::getInstance(1);
        }
        return $Cal;
    }


    /**
     * Get the icon for the calendar, if any.
     *
     * @return  string      Calendar icon name
     */
    public function getIcon($style='')
    {
        return $this->cal_icon;
    }


    /**
     * Get the admin list of calendars.
     *
     * @return  string  HTML for admin list
     */
    public static function adminList()
    {
        global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_EVLIST_HELP, $LANG_ADMIN;

        USES_lib_admin();

        $retval = '';

        $header_arr = array(
            array(
                'text'  => $LANG_EVLIST['edit'],
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
            array(
                'text'    => $LANG_EVLIST['orderby'],
                'field' => 'orderby',
                'sort'  => false,
                'align' => 'center',
            ),
             array(
                 'text'  => $LANG_EVLIST['id'],
                'field' => 'cal_id',
                'sort'  => false,
            ),
            array(
                'text'  => $LANG_EVLIST['title'],
                'field' => 'cal_name',
                'sort'  => false,
            ),
            array(
                'text'  => $LANG_EVLIST['enabled'],
                'field' => 'cal_status',
                'sort'  => false,
                'align' => 'center',
            ),
            array(
                'text'  => $LANG_EVLIST['ical_enabled'],
                'field' => 'cal_ena_ical',
                'sort'  => false,
                'align' => 'center',
            ),
            array(
                'text'  => $LANG_ADMIN['delete'] .
                    '&nbsp;' . FieldList::info(array(
                        'title' => $LANG_EVLIST_HELP['del_hdr1'],
                    ) ),
                'field' => 'delete',
                'sort'  => 'false',
                'align' => 'center',
            ),
        );

        $defsort_arr = array('field' => 'orderby', 'direction' => 'ASC');
        $text_arr = array(
            'has_menu'     => false,
            'has_extras'   => false,
            'form_url'     => EVLIST_ADMIN_URL . '/index.php?view=calendars',
            'help_url'     => ''
        );
        $sql = "SELECT * FROM {$_TABLES['evlist_calendars']} WHERE 1=1 ";
        $query_arr = array(
            'table' => 'evlist_calendars',
            'sql' => $sql,
            'query_fields' => array('id', 'cal_name'),
        );

        $retval .= COM_createLink(
            FieldList::button(array(
                'text' => $LANG_EVLIST['new_calendar'],
                'style' => 'success',
            ) ),
            EVLIST_ADMIN_URL . '/index.php?editcal=x'
        );
        $retval .= ADMIN_list(
            'evlist_cal_admin',
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr
        );
        return $retval;
    }


    /**
     * Return the display value for a calendar field.
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

        $retval = '';
        switch($fieldname) {
        case 'edit':
            $retval = FieldList::edit(array(
                'url' => EVLIST_ADMIN_URL . '/index.php?editcal=' . $A['cal_id'],
                array(
                    'title' => $LANG_EVLIST['editcal'],
                )
            ) );
            break;
        case 'orderby':
            $retval = FieldList::up(array(
                'url' => EVLIST_ADMIN_URL . '/index.php?movecal=up&id=' . $A['cal_id']
            ) );
            $retval .= FieldList::down(array(
                'url' => EVLIST_ADMIN_URL . '/index.php?movecal=down&id=' . $A['cal_id']
            ) );
            break;
        case 'cal_status':
        case 'cal_ena_ical':
            $retval = FieldList::checkbox(array(
                'checked' => $fieldvalue == 1,
                'id' => "tog{$fieldname}enabled{$A['cal_id']}",
                'onclick' => "EVLIST_toggle(this,'{$A['cal_id']}','" .
                    $fieldname . "','calendar','" . EVLIST_ADMIN_URL . "');",
                ) );
            break;
        case 'delete':
            if ($A['cal_id'] != 1) {
                $retval = FieldList::delete(array(
                    'delete_url' => EVLIST_ADMIN_URL. '/index.php?deletecal=' . $A['cal_id']
                ) );
            }
            break;
        case 'cal_name':
            $retval = '<span style="color:' . $A['fgcolor'] . ';background-color:' . $A['bgcolor'] .
                ';">' . $fieldvalue;
            if (isset($A['cal_icon']) && !empty($A['cal_icon'])) {
                $retval .= '&nbsp;' . Icon::getHTML($A['cal_icon']);
            }
            $retval .= '</span>';
            break;
        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }

}
