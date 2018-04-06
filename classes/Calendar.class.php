<?php
/**
*   Class to manage calendars
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011-2017 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;

/**
*   Class for calendar
*   @package evlist
*/
class Calendar
{
    var $properties = array();
    var $isNew;

    /**
    *   Constructor
    *   Create an empty calendar object, or read an existing one
    *
    *   @param  mixed   $calendar   Calendar ID to read, or array of info
    */
    public function __construct($calendar = 0)
    {
        global $_EV_CONF, $_USER;

        if (is_array($calendar)) {
            // Already have values from the DB
            $this->setVars($calendar, true);
        } elseif ($calendar != 0) {
            // Have a calendar ID to read
            $this->cal_id = $calendar;
            if ($this->Read())
                $this->isNew = false;
        } else {
            // Default, create an empty object
            $this->cal_id = 0;
            $this->isNew = true;
            $this->fgcolor = '#000000';
            $this->bgcolor = '#FFFFFF';
            $this->cal_name = '';
            $this->perm_owner   = $_EV_CONF['default_permissions'][0];
            $this->perm_group   = $_EV_CONF['default_permissions'][1];
            $this->perm_members = $_EV_CONF['default_permissions'][2];
            $this->perm_anon    = $_EV_CONF['default_permissions'][3];
            $this->owner_id     = $_USER['uid'];
            $this->group_id     = 13;
            $this->cal_status   = 1;
            $this->cal_ena_ical = 1;
            $this->cal_icon     = '';
        }
    }


    /**
    *   Get an instance of a calendar.
    *   Saves objects in a static variable to minimize DB lookups
    *
    *   @param  integer $cal_id Calendar ID
    *   @return object          Calendar object
    */
    public static function getInstance($cal_id)
    {
        $Cals = self::getAll();
        return isset($Cals[$cal_id]) ? $Cals[$cal_id] : NULL;
    }


    /**
    *   Read an existing calendar record into this object
    *
    *   @param  integer $cal_id Optional calendar ID, $this->cal_id used if 0
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
            $this->SetVars($row, true);
            return true;
        }
    }


    public function __set($key, $value)
    {
        switch ($key) {
        case 'cal_id':
        case 'perm_owner':
        case 'perm_group':
        case 'perm_members':
        case 'perm_anon':
        case 'owner_id':
        case 'group_id':
            $this->properties[$key] = (int)$value;
            break;

        case 'cal_status':
        case 'cal_ena_ical':
            $this->properties[$key] = $value == 1 ? 1 : 0;
            break;

        case 'cal_name':
        case 'fgcolor':
        case 'bgcolor':
        case 'cal_icon':
            $this->properties[$key] = trim($value);
            break;
        }
    }


    /**
    *   Get the value of a property.
    *   Emulates the behaviour of __get() function in PHP 5.
    *
    *   @param  string  $var    Name of property to retrieve.
    *   @return mixed           Value of property, NULL if undefined.
    */
    public function __get($key)
    {
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        } else {
            return NULL;
        }
    }


    /**
    *   Set the value of all variables from an array, either DB or a form
    *
    *   @param  array   $A      Array of fields
    *   @param  boolean $fromDB True if $A is from the database, false for form
    */
    public function SetVars($A, $fromDB=false)
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
    *   Provide the form to create or edit a calendar
    *
    *   @return string  HTML for editing form
    */
    public function Edit()
    {
        global $_EV_CONF, $_SYSTEM;

        $T = new \Template(EVLIST_PI_PATH . '/templates');
        if ($_EV_CONF['_is_uikit']) {
            $T->set_file('modify', 'calEditForm.uikit.thtml');
        } else {
            $T->set_file('modify', 'calEditForm.thtml');
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
            'cancel_url'    => EVLIST_ADMIN_URL. '/index.php?admin=cal',
            'can_delete'    => $this->cal_id > 1 ? 'true' : '',
            'mootools' => $_SYSTEM['disable_mootools'] ? '' : 'true',
            'help_url' => LGLIB_getDocUrl('calendar', 'evlist'),
            'colorpicker' => LGLIB_colorpicker(array(
                    'fg_id'     => 'fld_fgcolor',
                    'fg_color'  => $this->fgcolor,
                    'bg_id'     => 'fld_bgcolor',
                    'bg_color'  => $this->bgcolor,
                    'sample_id' => 'sample',
                )),
            'fg_inherit_chk' => $this->fgcolor == '' ? EVCHECKED : '',
            'bg_inherit_chk' => $this->bgcolor == '' ? EVCHECKED : '',
            'icon'          => $this->cal_icon,
        ) );

        $T->parse('output','modify');
        return $T->finish($T->get_var('output'));
    }


    /**
    *   Insert or update a calendar.
    *
    *   @param array    $A  Array of data to save, typically from form
    */
    public function Save($A=array())
    {
        global $_TABLES, $_EV_CONF;

        if (is_array($A) && !empty($A))
            $this->SetVars($A);

        if ($this->cal_id != 0) {
            $this->isNew = false;
        } else {
            $this->isNew = true;
        }

        $fld_sql = "cal_name = '" . DB_escapeString($this->cal_name) ."',
            fgcolor = '" . DB_escapeString($this->fgcolor) . "',
            bgcolor = '" . DB_escapeString($this->bgcolor) . "',
            cal_status = '{$this->cal_status}',
            cal_ena_ical = '{$this->cal_ena_ical}',
            perm_owner = '{$this->perm_owner}',
            perm_group = '{$this->perm_group}',
            perm_members = '{$this->perm_members}',
            perm_anon = '{$this->perm_anon}',
            owner_id = '{$this->owner_id}',
            group_id = '{$this->group_id}',
            cal_icon = '" . DB_escapeString($this->cal_icon) . "' ";

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
            if (version_compare(GVERSION, '1.8.0', '>=')) {
                Cache::clear('calendars');
            }
            return true;
        } else {
            return false;
        }
    }   // function Save()


    /**
    *   Deletes the current calendar.
    *   Deletes all events, detail and repeats associated with this calendar,
    *   or moves them to a different calendar if specified.
    *
    *   @param  integer $newcal ID of new calendar to use for events, etc.
    */
    public function Delete($newcal = 0)
    {
        global $_TABLES;

        // Can't delete calendar #1.  Shouldn't get to this point, but
        // return an error if we do.
        if ($this->cal_id == 1) {
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
    }


    /**
    *   Display a confirmation form to the user to confirm the deletion.
    *   Shows the user how many events are tied to the calendar being
    *   deleted.
    *
    *   @return string      HTML for confirmation form.
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
    *   Sets the "enabled" field to the specified value.
    *
    *   @param  integer $id ID number of element to modify
    *   @param  integer $value New value to set
    *   @return         New value, or old value upon failure
    */
    public static function toggleEnabled($oldvalue, $cal_id = 0)
    {
        global $_TABLES;

        $cal_id = (int)$cal_id;
        $newvalue = $oldvalue == 0 ? 1 : 0;
        $sql = "UPDATE {$_TABLES['evlist_calendars']}
                SET cal_status=$newvalue
                WHERE cal_id='$cal_id'";
        DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("SQL Error: $sql");
            return $oldvalue;
        } else {
            Cache::clear('calendars');
            Cache::clear('events');
            return $newvalue;
        }
    }


    /**
    *   Determine if the current calendar is in use by any events.
    *
    *   @return mixed   Number of events using the calendar, false if unused.
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
    *   Get the options for a calendar selection list.
    *   Leverages self::getAll() to re-use the database query.
    *
    *   @param  integer $selected   ID of selected calendar
    *   @param  boolean $enabled    True to show only enabled calendars
    *   @return string      Option tags
    */
    public static function getSelectionList($selected = 0, $enabled = false, $access = 0)
    {
        $retval = '';
        $cals = self::getAll();
        foreach ($cals as $cal_id=>$cal) {
            // Skip disabled calendars if enabled flag is set
            if ($enabled && $cal->cal_status == 0) continue;
            // Check access if acces level is set
            if ($access > 0 && !self::hasAccess($cal_id, $access)) continue;

            $sel = $cal_id == $selected ? EVSELECTED : '';
            $retval .= '<option ' . $sel . ' value="' . $cal_id . '">' .
                        $cal->cal_name . '</option>' . LB;
        }
        return $retval;
    }


    /**
    *   Get all calendars.
    *
    *   $param  boolean $enabled    True to get only enabled calendars
    *   return  array       Array of calendar objects
    */
    public static function getAll()
    {
        global $_TABLES;
        static $cals = NULL;

        // First check if the calendars have been read already
        if ($cals === NULL) {
            // Then check the cache
            $cals = Cache::get('calendars');
            if ($cals === NULL) {
                // Still nothing? Then read from the DB
                $cals = array();
                $sql = "SELECT * FROM {$_TABLES['evlist_calendars']}
                        ORDER BY cal_name ASC";
                $res = DB_query($sql);
                while ($A = DB_fetchArray($res, false)) {
                    $cals[$A['cal_id']] = new self($A);
                }
                Cache::set('calendars', $cals, 'calendars');
            }
        }
        return $cals;
    }


    /**
    *   Determine whether the current user has access to this event
    *
    *   @param  integer $level  Access level required
    *   @return boolean         True = has sufficieng access, False = not
    */
    public static function hasAccess($cal_id, $level=2)
    {
        // Admin & editor has all rights
        if (plugin_ismoderator_evlist()) return true;

        $Cal = self::getInstance($cal_id);
        if ($Cal === NULL || $Cal->cal_status == 0) return false;

        $access = SEC_hasAccess($Cal->owner_id, $Cal->group_id,
                    $Cal->perm_owner, $Cal->perm_group,
                    $Cal->perm_members, $Cal->perm_anon);
        return $access >= $level ? true : false;
    }


    /**
    *   Get the calendar that's mapped to a plugin name.
    *   Returns the calendar object. If no mapping exists, or the mapping
    *   refers to a non-existent calendar, then the default calendar object
    *   is returned.
    *
    *   @param  string  $pi_name    Plugin or calendar name
    *   @return object      Calendar object
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
        if (!$Cal) {
            $Cal = self::getInstance(1);
        }
        return $Cal;
    }

}

?>
