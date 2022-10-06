<?php
/**
 * Class to manage categories.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2022 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.8
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Class for categories.
 * @package evlist
 */
class Category
{
    use \Evlist\Traits\DBO;        // Import database operations

    /** Table name, for DBO operations.
     * @var string */
    protected static $TABLE = 'evlist_categories';

    /** Indicator that this is a new record.
     * @var boolean */
    private $isNew = true;

    /** Category record ID.
     * @var integer */
    private $cat_id = 0;

    /** Category enabled status.
     * @var boolean */
    private $cat_status = 1;

    /** Category name.
     * @var string */
    private $cat_name = '';


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
            $this->setVars($cat_id);
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
    public function Read(int $cat_id = 0) : bool
    {
        global $_TABLES;

        if ($cat_id > 0)
            $this->cat_id = $cat_id;

        try {
            $row = Database::getInstance()->conn->executeQuery(
                "SELECT * FROM {$_TABLES['evlist_categories']} WHERE id = ?",
                array($this->cat_id),
                array(Database::INTEGER)
            )->fetchAssociative();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $row = false;
        }
        if (is_array($row)) {
            $this->setVars($row, true);
            return true;
        } else {
            $this->cat_id = 0;
            return false;
        }
    }


    /**
     * Get the category record ID
     *
     * @return  integer     Record ID
     */
    public function getID() : int
    {
        return (int)$this->cat_id;
    }


    /**
     * Get the category status.
     *
     * @return  intetger    1 if enabled, 2 if disabled
     */
    public function getStatus() : int
    {
        return (int)$this->cat_status;
    }


    /**
     * Set the category name.
     *
     * @param   string  $name   Category name
     * @return  object  $this
     */
    public function setName(string $name) : self
    {
        $this->cat_name = $name;
        return $this;
    }


    /**
     * Get the category name.
     *
     * @return  string      Category name
     */
    public function getName() : string
    {
        return $this->cat_name;
    }


    /**
     * Set the value of all variables from an array, either DB or a form.
     *
     * @param   array   $A      Array of fields
     */
    public function setVars(array $A) : self
    {
        $this->cat_id = isset($A['id']) ? (int)$A['id'] : 0;
        $this->cat_name = $A['name'];
        $this->cat_status = isset($A['status'])? (int)$A['status'] : 0;
        return $this;
    }


    /**
     * Provide the form to create or edit a calendar.
     *
     * @return  string  HTML for editing form
     */
    public function Edit() : string
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
    public function Save(array $A=array()) : int
    {
        global $_TABLES, $_EV_CONF;

        if (is_array($A) && !empty($A)) {
            $this->setVars($A);
        }

        if ($this->cat_id > 0) {
            $this->isNew = false;
        } else {
            $this->isNew = true;
        }

        $values = array(
            'name' => $this->cat_name,
            'status' => $this->cat_status,
        );
        $types = array(
            Database::STRING,
            Database::STRING,
        );
        $db = Database::getInstance();
        if ($this->isNew) {
            // If adding a record, make sure it doesn't already exist.
            // If it does, just return the existing ID.
            $id = self::Exists($this->cat_name);
            if ($id > 0) {
                return $id;
            }
            try {
                $db->conn->insert(
                    $_TABLES['evlist_categories'],
                    $values,
                    $types
                );
                $this->cat_id = $db->conn->lastInsertId();
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                return 0;
            }
        } else {
            try {
                $types[] = Database::INTEGER;
                $db->conn->insert(
                    $_TABLES['evlist_categories'],
                    $values,
                    array('id' => $this->cat_id),
                    $types
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                return 0;
            }
        }

        Cache::clear('categories');
        return $this->cat_id;
    }


    /**
     * Deletes the current category. Also deletes any lookup records.
     *
     * @param   integer $cat_id Category to delete
     */
    public static function Delete(int $cat_id=0) : void
    {
        global $_TABLES;

        $cat_id = (int)$cat_id;
        $db = Database::getInstance();
        $db->conn->delete(
            $_TABLES['evlist_categories'],
            array('id' => $cat_id),
            array(Database::INTEGER)
        );
        $db->conn->delete(
            $_TABLES['evlist_lookup'],
            array('cid' => $cat_id),
            array(Database::INTEGER)
        );
        Cache::clear('categories');
    }


    /**
     * Get all categories from the lookup table.
     *
     * $param   boolean $enabled    True to get only enabled calendars
     * return   array       Array of calendar objects
     */
    public static function getAll() : array
    {
        global $_TABLES;

        $cats = array();
        try {
            $stmt = Database::getInstance()->conn->executeQuery(
                "SELECT * FROM {$_TABLES['evlist_categories']} ORDER BY id ASC"
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $cats[$A['id']] = new self($A);
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
            if (!$Cat->getStatus()) continue;
            $sel = $selected == $Cat->getID() ? 'selected="selected"' : '';
            $retval .= "<option value=\"{$Cat->getID()}\" $sel>{$Cat->getName()}</option>" . LB;
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
    public static function Exists(string $cat_name) : int
    {
        global $_TABLES;

        try {
            $id = Database::getInstance()->getItem(
                $_TABLES['evlist_categories'],
                'id',
                array('name' => $cat_name)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $id = 0;
        }
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
            FieldList::button(array(
                'text' => $LANG_EVLIST['new_category'],
                'style' => 'success',
            ) ),
            EVLIST_ADMIN_URL . '/index.php?editcat=0'
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
            $retval = FieldList::edit(array(
                'url' => EVLIST_ADMIN_URL . '/index.php?editcat=' . $A['id'],
                array(
                    'title' => $LANG_ADMIN['edit'],
                ),
            ) );
            break;
        case 'status':
            $retval = FieldList::checkbox(array(
                'checked' => $fieldvalue == 1,
                'id' => "togenabled{$A['id']}",
                'onclick' => "EVLIST_toggle(this,'{$A['id']}','enabled'," .
                    "'category','" . EVLIST_ADMIN_URL . "');",
            ) );
            break;
        case 'delete':
            $retval = FieldList::delete(array(
                'delete_url' => EVLIST_ADMIN_URL. '/index.php?delcat=x&id=' . $A['id'],
                array(
                    'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_item']}');",
                    'title' => $LANG_ADMIN['delete'],
                    'class' => 'tooltip',
                ),
            ) );
            break;
        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }

}

