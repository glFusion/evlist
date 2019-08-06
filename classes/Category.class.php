<?php
/**
 * Class to manage categories.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2017 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;

/**
 * Class for categories.
 * @package evlist
 */
class Category
{
    /** Properties accessed via `__set()` and `__get()`.
     * @var array */
    var $properties = array();

    /** Indicator that this is a new record.
     * @var boolean */
    var $isNew;


    /**
     * Constructor
     * Create an empty calendar object, or read an existing one
     *
     * @param   integer $cat_id     Calendar ID to read
     */
    public function __construct($cat_id = 0)
    {
        global $_EV_CONF, $_USER;

        $this->cat_id       = $cat_id;
        $this->cat_name     = '';
        $this->cat_status   = 1;
        $this->isNew = true;

        if (is_array($cat_id)) {
            $this->SetVars($cat_id);
            $this->isNew = false;
        } else if ($this->cat_id > 0) {
            $this->Read($this->cat_id);
        }
    }


    /**
     * Read an existing calendar record into this object.
     *
     * @param   integer $cat_id Optional calendar ID, $this->cat_id used if 0
     */
    public function Read($cat_id = 0)
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


    /**
     * Set a property's value.
     *
     * @param   $key    Name of property
     * @param   $value  Value to set
     */
    public function __set($key, $value)
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
     * Get the value of a property.
     *
     * @param   string  $key    Name of property to retrieve.
     * @return  mixed           Value of property, NULL if undefined.
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
     * Set the value of all variables from an array, either DB or a form.
     *
     * @param   array   $A      Array of fields
     */
    public function SetVars($A)
    {
        $this->cat_id = isset($A['id']) ? $A['id'] : 0;
        $this->cat_name = $A['name'];
        $this->cat_status = isset($A['status'])? $A['status'] : 0;
    }


    /**
     * Provide the form to create or edit a calendar.
     *
     * @return  string  HTML for editing form
     */
    public function Edit()
    {
        global $_SYSTEM;

        $T = new \Template(EVLIST_PI_PATH . '/templates');
        $T->set_file('modify', 'catEditForm.thtml');
        $T->set_var(array(
            'cat_id'        => $this->cat_id,
            'cat_name'      => $this->cat_name,
            'stat_chk'      => $this->cat_status == 1 ? EVCHECKED : '',
            'cancel_url'    => EVLIST_ADMIN_URL. '/index.php?categories=x',
        ) );
        $T->parse('output','modify');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Insert or update a category record.
     *
     * @param   array   $A  Array of data to save, typically from form
     * @return  integer     Category ID, 0 on failure.
     */
    public function Save($A=array())
    {
        global $_TABLES, $_EV_CONF;

        if (is_array($A) && !empty($A)) {
            $this->SetVars($A);
        }

        if ($this->cat_id > 0) {
            $this->isNew = false;
        } else {
            $this->isNew = true;
        }

        $fld_sql = "name = '" . DB_escapeString($this->cat_name) ."',
            status = '{$this->cat_status}'";

        if ($this->isNew) {
            // If adding a record, make sure it doesn't already exist.
            // If it does, just return the existing ID.
            $id = self::Exists($this->cat_name);
            if ($id > 0) {
                return $id;
            }
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
            Cache::clear('categories');
            return $this->cat_id;
        } else {
            return 0;
        }
    }   // function Save()


    /**
     * Deletes the current category. Also deletes any lookup records.
     *
     * @param   integer $cat_id Category to delete
     */
    public static function Delete($cat_id=0)
    {
        global $_TABLES;

        $cat_id = (int)$cat_id;
        DB_delete($_TABLES['evlist_categories'], 'id', $cat_id);
        DB_delete($_TABLES['evlist_lookup'], 'cid', $cat_id);
        Cache::clear('categories');
    }


    /**
     * Sets the "enabled" field to the specified value.
     *
     * @param   integer $oldvalue   Original value to be toggled
     * @param   integer $cat_id     ID number of element to modify
     * @return      New value, or old value upon failure
     */
    public static function toggleEnabled($oldvalue, $cat_id)
    {
        global $_TABLES;

        $cat_id = (int)$cat_id;
        $newvalue = $oldvalue == 0 ? 1 : 0;
        $sql = "UPDATE {$_TABLES['evlist_categories']}
                SET status=$newvalue
                WHERE id='$cat_id'";
        DB_query($sql, 1);
        if (DB_error()) {
            return $oldvalue;
        } else {
            Cache::clear('categories');
            return $newvalue;
        }
    }


    /**
     * Get all categories from the lookup table.
     *
     * $param   boolean $enabled    True to get only enabled calendars
     * return   array       Array of calendar objects
     */
    public static function getAll()
    {
        global $_TABLES;
        static $cats = NULL;

        // First check if the calendars have been read already
        if ($cats === NULL) {
            // Then check the cache
            $cats = Cache::get('categories');
            if ($cats === NULL) {
                // Still nothing? Then read from the DB
                $cats = array();
                $sql = "SELECT * FROM {$_TABLES['evlist_categories']}
                        ORDER BY id ASC";
                $res = DB_query($sql);
                while ($A = DB_fetchArray($res, false)) {
                    $cats[$A['id']] = new self($A);
                }
                Cache::set('categories', $cats, 'categories');
            }
        }
        return $cats;
    }


    /**
     * Get an instance of a category.
     * Saves objects in a static variable to minimize DB lookups
     *
     * @param   integer $id     Category ID
     * @return  object          Category object
     */
    public static function getInstance($id)
    {
        $Cats = self::getAll();
        return isset($Cats[$id]) ? $Cats[$id] : NULL;
    }


    /**
     * Create the option variables for a dropdown selection list.
     *
     * @param   integer $selected   Selected item
     * @return  string      HTML for option list
     */
    public static function optionList($selected = 0)
    {
        $Cats = self::getAll();
        $retval = '';
        foreach ($Cats as $Cat) {
            if (!$Cat->cat_status) continue;
            $sel = $selected == $Cat->cat_id ? 'selected="selected"' : '';
            $retval .= "<option value=\"{$Cat->cat_id}\" $sel>{$Cat->cat_name}</option>" . LB;
        }
        return $retval;
    }


    /**
     * Check if a category exists by name.
     * Used to ensure duplicate categories aren't created.
     *
     * @param   string  $cat_name   Category name
     * @return  integer     ID of record, 0 if it doesn't exist
     */
    public static function Exists($cat_name)
    {
        global $_TABLES;

        $id = (int)DB_getItem($_TABLES['evlist_categories'], 'id',
            "name = '" . DB_escapeString($cat_name) . "'");
        return $id;
    }


    /**
     * Get the admin list of categories.
     *
     * @return  string      HTML for admin list
     */
    public static function adminList()
    {
        global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

        USES_lib_admin();
        EVLIST_setReturn('admincategories');

        $retval = '';

        $header_arr = array(
            array(
                'text' => $LANG_EVLIST['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_EVLIST['id'],
                'field' => 'id',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['cat_name'],
                'field' => 'name',
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

        $defsort_arr = array('field' => 'name', 'direction' => 'ASC');
        $text_arr = array(
            'has_menu'     => false,
            'has_extras'   => false,
            'form_url'     => EVLIST_ADMIN_URL . '/index.php?categories=x',
            'help_url'     => ''
        );
        $sql = "SELECT * FROM {$_TABLES['evlist_categories']} WHERE 1=1 ";
        $query_arr = array(
            'table' => 'evlist_categories',
            'sql' => $sql,
            'query_fields' => array('name'),
        );

        $retval .= COM_createLink(
            $LANG_EVLIST['new_category'],
            EVLIST_ADMIN_URL . '/index.php?editcat=x',
            array(
                'class' => 'uk-button uk-button-success',
                'style' => 'float:left',
            )
        );
        $retval .= ADMIN_list(
            'evlist_cat_admin',
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr
        );
        return $retval;
    }


    /**
     * Return the display value for a category field.
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
            $retval = COM_createLink(
                '<i class="uk-icon-edit"></i>',
                EVLIST_ADMIN_URL . '/index.php?editcat=x&amp;id=' . $A['id'],
                array(
                    'title' => $LANG_ADMIN['edit'],
                )
            );
            break;
        case 'status':
            if ($A['status'] == '1') {
                $switch = EVCHECKED;
                $enabled = 1;
            } else {
                $switch = '';
                $enabled = 0;
            }
            $retval .= "<input type=\"checkbox\" $switch value=\"1\"
                name=\"cat_check\"
                id=\"togenabled{$A['id']}\"
                onclick='EVLIST_toggle(this,\"{$A['id']}\",\"enabled\",".
                '"category","'.EVLIST_ADMIN_URL."\");' />".LB;
            break;
        case 'delete':
            $retval = COM_createLink(
                $_EV_CONF['icons']['delete'],
                EVLIST_ADMIN_URL. '/index.php?delcat=x&id=' . $A['id'],
                array(
                    'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_item']}');",
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

}

?>
