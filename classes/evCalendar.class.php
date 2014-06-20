<?php
/**
*   Class to manage calendars
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


/**
 *  Class for calendar
 *  @package evlist
 */
class evCalendar
{
    var $properties = array();
    var $isNew;

    /**
    *   Constructor
    *   Create an empty calendar object, or read an existing one
    *
    *   @param  integer $cal_id     Calendar ID to read
    */
    function __construct($cal_id = 0)
    {
        global $_EV_CONF, $_USER;

        $this->cal_id = $cal_id;
        $this->isNew = true;
        $this->fgcolor = '';
        $this->bgcolor = '';
        $this->cal_name = '';
        $this->perm_owner   = $_EV_CONF['default_permissions'][0];
        $this->perm_group   = $_EV_CONF['default_permissions'][1];
        $this->perm_members = $_EV_CONF['default_permissions'][2];
        $this->perm_anon    = $_EV_CONF['default_permissions'][3];
        $this->owner_id     = $_USER['uid'];
        $this->group_id     = 13;
        $this->cal_status   = 1;
        $this->cal_ena_ical = 1;

        if ($this->cal_id > 0) {
            if ($this->Read())
                $this->isNew = false;
        }
    }


    /**
    *   Read an existing calendar record into this object
    *
    *   @param  integer $cal_id Optional calendar ID, $this->cal_id used if 0
    */
    function Read($cal_id = 0)
    {
        global $_TABLES;

        if ($cal_id > 0)
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


    function __set($key, $value)
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
    function __get($key)
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
    function SetVars($A, $fromDB=false)
    {
        if (isset($A['cal_id']) && !empty($A['cal_id']))
            $this->cal_id = $A['cal_id'];

        // These fields come in the same way from DB or form
        $fields = array('cal_name', 'fgcolor', 'bgcolor', 
            'owner_id', 'group_id');
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
    function Edit()
    {

        $T = new Template(EVLIST_PI_PATH . '/templates');
        $T->set_file('modify', 'calEditForm.thtml');

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
        ) );

        $T->parse('output','modify');
        $display .= $T->finish($T->get_var('output'));
        return $display;

    }


    /**
    *   Insert or update a calendar.
    *
    *   @param array    $A  Array of data to save, typically from form
    */
    function Save($A=array())
    {
        global $_TABLES, $_EV_CONF;

        if (is_array($A) && !empty($A))
            $this->SetVars($A);

        if ($this->cal_id > 0) {
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
            group_id = '{$this->group_id}' ";

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
    function Delete($newcal = 0)
    {
        global $_TABLES;

        // Can't delete calendar #1.  Shouldn't get to this point, but
        // return an error if we do.
        if ($this->cal_id == 1) {
            return false;
        }

        $newcal = (int)$newcal;
        if ($newcal > 0) {
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
    function DeleteForm()
    {
        global $_TABLES, $LANG_EVLIST;

        $T = new Template(EVLIST_PI_PATH . '/templates/');
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
     *  Sets the "enabled" field to the specified value.
     *
     *  @param  integer $id ID number of element to modify
     *  @param  integer $value New value to set
     *  @return         New value, or old value upon failure
     */
    public function toggleEnabled($oldvalue, $cal_id = 0)
    {
        global $_TABLES;

        $oldvalue = $oldvalue == 0 ? 0 : 1;
        $cal_id = (int)$cal_id;
        if ($cal_id == 0) {
            if (is_object($this))
                $cal_id = $this->cal_id;
            else
                return $oldvalue;
        }
        $newvalue = $oldvalue == 0 ? 1 : 0;
        $sql = "UPDATE {$_TABLES['evlist_calendars']}
                SET cal_status=$newvalue
                WHERE cal_id='$cal_id'";
        //echo $sql;die;
        DB_query($sql);
        return $newvalue;

    }


    /**
    *   Determine if the current calendar is in use by any events.
    *
    *   @return mixed   Number of events using the calendar, false if unused.
    */
    function isUsed()
    {
        global $_TABLES;

        $cnt = DB_count($_TABLES['evlist_events'], 'cal_id', $this->cal_id);
        if ($cnt > 0) {
            return $cnt;
        } else {
            return false;
        }
    }
}

?>
