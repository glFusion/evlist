<?php
/**
*   Class to manage categories
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
 *  Class for categories
 *  @package evlist
 */
class evCategory
{
    var $properties = array();
    var $isNew;

    /**
    *   Constructor
    *   Create an empty calendar object, or read an existing one
    *
    *   @param  integer $cat_id     Calendar ID to read
    */
    function __construct($cat_id = 0)
    {
        global $_EV_CONF, $_USER;

        $this->cat_id       = $cat_id;
        $this->cat_name     = '';
        $this->cat_status   = 1;
        $this->isNew = true;

        if ($this->cat_id > 0) {
            $this->Read($this->cat_id);
        }
    }


    /**
    *   Read an existing calendar record into this object
    *
    *   @param  integer $cat_id Optional calendar ID, $this->cat_id used if 0
    */
    function Read($cat_id = 0)
    {
        global $_TABLES;

        if ($cat_id > 0)
            $this->cat_id = $cat_id;

        $sql = "SELECT *
            FROM {$_TABLES['evlist_categories']} 
            WHERE id='{$this->cat_id}'";
        //echo $sql;
        $result = DB_query($sql);

        if (!$result || DB_numRows($result) == 0) {
            $this->cat_id = 0;
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
        case 'cat_id':
            $this->properties[$key] = (int)$value;
            break;

        case 'cat_status':
            $this->properties[$key] = $value == 1 ? 1 : 0;
            break;

        case 'cat_name':
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
    function SetVars($A)
    {
        $this->cat_id = isset($A['id']) ? $A['id'] : 0;
        $this->cat_name = $A['name'];
        $this->cat_status = isset($A['status'])? $A['status'] : 0;
    }


    /**
    *   Provide the form to create or edit a calendar
    *
    *   @return string  HTML for editing form
    */
    function Edit()
    {
        $T = new Template(EVLIST_PI_PATH . '/templates');
        $T->set_file('modify', 'catEditForm.thtml');

        $T->set_var(array(
            'cat_id'        => $this->cat_id,
            'cat_name'      => $this->cat_name,
            'stat_chk'      => $this->cat_status == 1 ? EVCHECKED : '',
            'cancel_url'    => EVLIST_ADMIN_URL. '/index.php?categories=x',
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

        if ($this->cat_id > 0) {
            $this->isNew = false;
        } else {
            $this->isNew = true;
        }

        $fld_sql = "name = '" . DB_escapeString($this->cat_name) ."',
            status = '{$this->cat_status}'";

        if ($this->isNew) {
            $sql = "INSERT INTO {$_TABLES['evlist_categories']} SET 
                    $fld_sql";
        } else {
            $sql = "UPDATE {$_TABLES['evlist_categories']} SET 
                    $fld_sql
                    WHERE id='{$this->cat_id}'";
        }

        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            if ($this->isNew) $this->cat_id = DB_insertId();
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
    function Delete($cat_id=0)
    {
        global $_TABLES;

        $cat_id = (int)$cat_id;
        if ($cat_id == 0 && is_object($this)) {
            $cat_id = $this->cat_if;
        }
        DB_delete($_TABLES['evlist_categories'], 'id', $cat_id);

    }



    /**
     *  Sets the "enabled" field to the specified value.
     *
     *  @param  integer $id ID number of element to modify
     *  @param  integer $value New value to set
     *  @return         New value, or old value upon failure
     */
    public function toggleEnabled($oldvalue, $cat_id = 0)
    {
        global $_TABLES;

        $oldvalue = $oldvalue == 0 ? 0 : 1;
        $cat_id = (int)$cat_id;
        if ($cat_id == 0) {
            if (is_object($this))
                $cat_id = $this->cat_id;
            else
                return $oldvalue;
        }
        $newvalue = $oldvalue == 0 ? 1 : 0;
        $sql = "UPDATE {$_TABLES['evlist_categories']}
                SET status=$newvalue
                WHERE id='$cat_id'";
        //echo $sql;die;
        DB_query($sql);
        return $newvalue;

    }

}

?>
