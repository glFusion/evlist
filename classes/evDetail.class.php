<?php
/**
 *  Class to manage event detail records for the EvList plugin
 *
 *  @author     Lee Garner <lee@leegarner.com>
 *  @copyright  Copyright (c) 2011 Lee Garner <lee@leegarner.com>
 *  @package    evlist
 *  @version    1.3.0
 *  @license    http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 *  @filesource
 */


/**
 *  Class for event detail
 *  @package evlist
 */
class evDetail
{
    /** Property fields.  Accessed via __set() and __get()
    *   @var array */
    var $properties = array();

    var $fields = array(
            'ev_id', 'title', 'summary', 'full_description',
            'url', 'location', 'street', 'city', 'province', 'country',
            'postal', 'contact', 'email', 'phone', 
    );

    /** Marker if this is a new vs. existing record
    *   @var boolean */
    var $isNew;

    /** Array of error messages
     *  @var mixed */
    var $Errors = array();


    /**
     *  Constructor.
     *  Reads in the specified class, if $id is set.  If $id is zero, 
     *  then a new entry is being created.
     *
     *  @param integer $id Optional type ID
     */
    public function __construct($det_id='', $ev_id='')
    {
        $this->isNew = true;

        if ($det_id == '') {
            $this->det_id = '';
            $this->title = '';
            $this->summary = '';
            $this->full_description = '';
            $this->url = '';
            $this->location = '';
            $this->street = '';
            $this->city = '';
            $this->province = '';
            $this->country = '';
            $this->postal = '';
            $this->contact = '';
            $this->email = '';
            $this->phone = '';
            $this->ev_id = $ev_id;
            $this->lat = 0;
            $this->lng = 0;
        } else {
            $this->det_id = $det_id;
            if (!$this->Read()) {
                $this->det_id = '';
            }
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
        switch ($var) {
        case 'det_id':
            $this->properties[$var] = (int)$value;
            break;

        case 'ev_id':
            $this->properties[$var] = COM_sanitizeID($value, false);
            break;

        case 'title':
        case 'summary':
        case 'full_description':
        case 'url':
        case 'location':
        case 'street':
        case 'city':
        case 'province':
        case 'country':
        case 'postal':
        case 'contact':
        case 'email':
        case 'phone':
            // String values
            $this->properties[$var] = trim(COM_checkHTML($value));
            break;

        case 'lat':
        case 'lng':
            // Convert European decimal char if coming from a form
            $value = str_replace(',', '.', $value);
            $this->properties[$var] = (float)$value;
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
        if (array_key_exists($var, $this->properties)) {
            return $this->properties[$var];
        } else {
            return NULL;
        }
    }


    /**
     *  Sets all variables to the matching values from $rows.
     *
     *  @param  array   $row        Array of values, from DB or $_POST
     *  @param  boolean $fromDB     True if read from DB, false if from $_POST
     */
    public function SetVars($row, $fromDB=false)
    {
        if (!is_array($row)) return;

        foreach ($this->fields as $field) {
            if (isset($row[$field])) {
                $this->$field = $row[$field];
            }
        }
        $this->lat = $row['lat'];
        $this->lng = $row['lng'];
    }


    /**
     *  Read a specific record and populate the local values.
     *
     *  @param  integer $id Optional ID.  Current ID is used if zero.
     *  @return boolean     True if a record was read, False on failure.
     */
    public function Read($det_id = '')
    {
        global $_TABLES;

        if ($det_id != '') {
            $this->det_id = $det_id;
        }

        $result = DB_query("SELECT * 
                    FROM {$_TABLES['evlist_detail']} 
                    WHERE det_id='{$this->det_id}'");
        if (!$result || DB_numRows($result != 1)) {
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->SetVars($row, true);
            $this->isNew = false;
            return true;
        }
    }


    /**
     *  Save the current values to the database.
     *  Appends error messages to the $Errors property.
     *
     *  @param  array   $A      Optional array of values from $_POST
     *  @return boolean         True if no errors, False otherwise
     */
    public function Save($A = '')
    {
        global $_TABLES, $_EV_CONF;

        if (is_array($A)) {
            $this->SetVars($A);
        }

        $this->isNew = $this->det_id > 0 ? false : true;

        // If integrating with the Locator plugin, try to get and save
        // the coordinates to be used when displaying the event.
        // At least a city and state/province is required.
        if ($_EV_CONF['use_locator'] == 1 &&
                $this->city != '' && 
                $this->province != '') {
            $address = $this->street . ' ' . $this->city . ', ' .
                        $this->province . ' ' . $this->postal . ' ' .
                        $this->country;
            $lat = $this->lat;
            $lng = $this->lng;
            if ($lat == 0 && $lng == 0) {
                $status = LGLIB_invokeService('locator', 'getCoords',
                    $address, $output, $svc_msg);
                if ($status == PLG_RET_OK) {
                    $this->lat = $output['lat'];
                    $this->lng = $output['lng'];
                }
            }
        }

        $lat = (string)$this->lat;
        $lat = str_replace(',', '.', $lat);
        $lng = (string)$this->lng;
        $lng = str_replace(',', '.', $lng);

        $fld_set = array();
        foreach ($this->fields as $fld_name) {
            $fld_set[] = "$fld_name='" . DB_escapeString($this->$fld_name) . "'";
        }
        $fld_sql = implode(',', $fld_set);
COM_errorLog("Lat/Lng values: {$this->lat}, {$this->lng}");
COM_errorLog("Lag/Lng saved as: $lat, $lng");

            $lat = number_format($this->lat, 6, '.', '');
            $lng = number_format($this->lng, 6, '.', '');

        // Insert or update the record, as appropriate
        if (!$this->isNew) {
            // For updates, delete the event from the cache table.
           $sql = "UPDATE {$_TABLES['evlist_detail']}
                    SET $fld_sql,
<<<<<<< HEAD
                    lat = {$lat},
                    lng = {$lng}
=======
                    lat = '{$lat}',
                    lng = '{$lng}'
>>>>>>> refs/remotes/leegarner-glfusion/master
                    WHERE det_id='" . (int)$this->det_id . "'";
            //echo $sql;die;
            DB_query($sql);
        } else {
            $sql = "INSERT INTO {$_TABLES['evlist_detail']}
                    SET 
                    det_id = 0,
<<<<<<< HEAD
                    lat = {$lat},
                    lng = {$lng},
=======
                    lat = '{$lat}',
                    lng = '{$lng}',
>>>>>>> refs/remotes/leegarner-glfusion/master
                    $fld_sql";
            //echo $sql;die;
            DB_query($sql);
            $this->det_id = DB_insertID();
        }

        return $this->det_id;

    }


    /**
     *  Delete the current detail record from the database
     */
    public function Delete()
    {
        global $_TABLES;

        if ($this->det_id == '')
            return false;

        DB_delete($_TABLES['evlist_detail'], 'det_id', $this->det_id);
        $this->det_id = 0;
        return true;
    }
        
}   // class evDetail


?>
