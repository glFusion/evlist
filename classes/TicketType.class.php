<?php
/**
 * Class to manage ticket types.
 * Ticket types are meant to represent the type of admission purchased,
 * such as "General Admission", "VIP Pass", "Balcony", "Orchestra", etc.
 * Each ticket type can also be set to be an Event Pass allowing admission
 * to all occurrences of an event.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2015-2017 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;

/**
 * Class for ticket types.
 * @package evlist
 */
class TicketType
{
    /** Properties accessed via `__set()` and `__get()`.
     * @var array */
    var $properties = array();

    /** Flag to indicate a new record.
     * @var boolean */
    var $isNew;


    /**
     * Constructor.
     * Create an empty ticket type object, or read an existing one.
     *
     * @param   integer $id     Ticket Type ID to read
     */
    public function __construct($id = 0)
    {
        $this->id           = $id;
        $this->description  = '';
        $this->enabled      = 1;
        $this->event_pass   = 0;
        $this->isNew = true;

        if ($this->id > 0) {
            $this->Read($this->id);
        }
    }


    /**
     * Read an existing ticket type record into this object.
     *
     * @param   integer $id Optional type ID, $this->id used if 0
     */
    public function Read($id = 0)
    {
        global $_TABLES;

        if ($id > 0)
            $this->id = $id;

        $sql = "SELECT * FROM {$_TABLES['evlist_tickettypes']}
            WHERE id='{$this->id}'";
        //echo $sql;
        $result = DB_query($sql);

        if (!$result || DB_numRows($result) == 0) {
            $this->id = 0;
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->SetVars($row, true);
            return true;
        }
    }


    /**
     * Setter function.
     * Formats and sets $value into $this->properties[$key].
     *
     * @param   string  $key    Variable name
     * @param   mixed   $value  Valut to assign
     */
    public function __set($key, $value)
    {
        switch ($key) {
        case 'id':
            $this->properties[$key] = (int)$value;
            break;

        case 'event_pass':
        case 'enabled':
            $this->properties[$key] = $value == 1 ? 1 : 0;
            break;

        case 'description':
            $this->properties[$key] = trim($value);
            break;
        }
    }


    /**
     * Get the value of a property if it exists, NULL if not.
     *
     * @param   string  $key   Name of property to retrieve.
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
        $this->id = isset($A['id']) ? $A['id'] : 0;
        $this->description = $A['description'];
        $this->event_pass = $A['event_pass'];
        $this->enabled = $A['enabled'];
    }


    /**
     * Provide the form to create or edit a ticket type.
     *
     * @return  string  HTML for editing form
     */
    public function Edit()
    {
        $T = new \Template(EVLIST_PI_PATH . '/templates');
        $T->set_file(array(
            'modify'    => 'ticketForm.thtml',
            'tips'      => 'tooltipster.thtml',
        ) );
        $T->set_var(array(
            'id'                => $this->id,
            'description'       => $this->description,
            'event_pass_chk'    => $this->event_pass == 1 ? EVCHECKED : '',
            'enabled_chk'       => $this->enabled == 1 ? EVCHECKED : '',
            'doc_url'           => EVLIST_getDocURL('tickettype'),
        ) );
        $T->parse('tooltipster_js', 'tips');
        $T->parse('output','modify');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Insert or update a ticket type.
     *
     * @param   array    $A  Array of data to save, typically from form
     */
    public function Save($A=array())
    {
        global $_TABLES, $_EV_CONF;

        if (is_array($A) && !empty($A))
            $this->SetVars($A);

        if ($this->id > 0) {
            $this->isNew = false;
        } else {
            $this->isNew = true;
        }

        $fld_sql = "description = '" . DB_escapeString($this->description) ."',
            enabled = '{$this->enabled}',
            event_pass = '{$this->event_pass}'";

        if ($this->isNew) {
            $sql = "INSERT INTO {$_TABLES['evlist_tickettypes']} SET
                    $fld_sql";
        } else {
            $sql = "UPDATE {$_TABLES['evlist_tickettypes']} SET
                    $fld_sql
                    WHERE id='{$this->id}'";
        }

        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            if ($this->isNew) $this->id = DB_insertId();
            return true;
        } else {
            COM_errorLog("Evist\\TicketType::Save SQL Error: $sql");
            return false;
        }
    }   // function Save()


    /**
     * Deletes the current ticket type.
     * First checks that the type isn't in use, and don't delete
     * the default ticket type (id == 1).
     *
     * @param   integer $id     ID of ticket type to delete
     */
    public static function Delete($id)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id <= 2 || self::isUsed($id)) {
            // Can't delete the default type, or one that has been used.
            return false;
        } else {
            DB_delete($_TABLES['evlist_tickettypes'], 'id', $id);
            return true;
        }
    }


    /**
     * Determine if the ticket type is in use by any tickets.
     *
     * @param   integer $id     Ticket type ID
     * @return  boolean     False if unused, True if used
     */
    public static function isUsed($id=0)
    {
        global $_TABLES;

        $id = (int)$id;
        $count = DB_count($_TABLES['evlist_tickets'], 'tic_type', $id);
        return $count == 0 ? false : true;
    }


    /**
     * Sets the field to the opposite of the specified value.
     *
     * @param   string  $fld        DB Field to toggle
     * @param   integer $oldvalue   Old (current) value of field
     * @param   integer $id         ID number of element to modify
     * @return          New value, or old value upon failure
     */
    public static function Toggle($fld, $oldvalue, $id)
    {
        global $_TABLES;

        // Validate $item - only toggle-able fields
        switch ($fld) {
        case 'event_pass':
        case 'enabled':
            break;
        default:
            return $oldval;
            break;
        }

        $id = (int)$id;
        if ($id == 0) return $oldvalue;
        $newvalue = $oldvalue == 0 ? 1 : 0;
        $sql = "UPDATE {$_TABLES['evlist_tickettypes']}
                SET $fld = $newvalue
                WHERE id = '$id'";
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("Evlist\\TicketType::Toggle SQL Error: $sql");
            return $oldvalue;
        } else {
            return $newvalue;
        }
    }


    /**
     * Get all the ticket types into objects.
     *
     * @param   boolean $enabled    True to get only enabled, false for all
     * @return  array       Array of TicketType objects, indexed by ID
     */
    public static function GetTicketTypes($enabled = true)
    {
        global $_TABLES;

        static $types = array();
        $key = $enabled ? 1 : 0;

        if (!isset($types[$key])) {
            $types[$key] = array();
            $sql = "SELECT * FROM {$_TABLES['evlist_tickettypes']}";
            if ($enabled) $sql .= " WHERE enabled = 1";
            $res = DB_query($sql, 1);
            while ($A = DB_fetchArray($res, false)) {
                // create empty objects and use SetVars to save DB lookups
                $types[$key][$A['id']] = new TicketType();
                $types[$key][$A['id']]->SetVars($A);
            }
        }
        return $types[$key];
    }

}   // class TicketType

?>
