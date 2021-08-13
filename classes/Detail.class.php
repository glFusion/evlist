<?php
/**
 * Class to manage event detail records for the EvList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2019 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.3
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;
use Evlist\Models\Status;


/**
 * Class for event detail.
 * @package evlist
 */
class Detail
{
    /** Property fields.  Accessed via __set() and __get()
     * @var array */
    //var $properties = array();

    /** Detail record ID.
     * @var integer */
    private $det_id = 0;

    /** Related Event record ID.
     * @var string */
    private $ev_id = '';

    /** Event title.
     * @var string */
    private $title = '';

    /** Event one-line summary.
     * @var string */
    private $summary = '';

    /** Full text description.
     * @var string */
    private $full_description = '';

    /** Event url to more information.
     * @var string */
    private $url = '';

    /** Event location - description.
     * @var string */
    private $location = '';

    /** Event location - street address.
     * @var string */
    private $street = '';

    /** Event location - city name.
     * @var string */
    private $city = '';

    /** Event location - state/province name.
     * @var string */
    private $province = '';

    /** Event locaton - postal code.
     * @var string */
    private $postal = '';

    /** Event location - country.
     * @var string */
    private $country = '';

    /** Contact name.
     * @var string */
    private $contact = '';

    /** Contact email.
     * @var string */
    private $email = '';

    /** Contact phone number.
     * @var string */
    private $phone = '';

    /** Location latitude (for Locator plugin.
     * @var float */
    private $lat = 0;

    /** Location longitude (for Locator plugin.
     * @var float */
    private $lng = 0;

    /** Field names.
     * @var array */
    private $fields = array(
        'ev_id', 'title', 'summary', 'full_description',
        'url', 'location', 'street', 'city', 'province', 'country',
        'postal', 'contact', 'email', 'phone',
    );

    /** Marker if this is a new vs. existing record.
     * @var boolean */
    private $isNew = true;

    /** Array of error messages.
     * @var mixed */
    private $Errors = array();


    /**
     * Constructor.
     * Reads in the specified class, if $id is set.  If $id is zero,
     * then a new entry is being created.
     *
     * @param   integer $det_id Optional type ID
     * @param   integer $ev_id  Optional event ID
     */
    public function __construct($det_id='', $ev_id='')
    {
        $this->isNew = true;

        if ($det_id == '') {
            $this->ev_id = $ev_id;
        } else {
            $this->det_id = $det_id;
            if (!$this->Read()) {
                $this->det_id = '';
            }
        }

    }


    /**
     * Get an instance of an event's detail record.
     *
     * @param   integer $det_id     Detail record ID
     * @return  object              Detail object
     */
    public static function getInstance($det_id)
    {
        static $records = array();
        if (!array_key_exists($det_id, $records)) {
            $key = 'detail_' . $det_id;
            $records[$det_id] = Cache::get($key);
            if ($records[$det_id] === NULL) {
                $records[$det_id] = new self($det_id);
            }
            $tags = array(
                'events',
                'detail',
                'event_' . $records[$det_id]->ev_id,
            );
            Cache::set($key, $records[$det_id], $tags);
        }
        return $records[$det_id];
    }


    /**
     * Set the event ID value.
     *
     * @param   string  $ev_id  Event ID
     * @return  object  $this
     */
    public function setEventID($ev_id)
    {
        $this->ev_id = $ev_id;
    }


    /**
     * Get the event title.
     *
     * @return  string      Event title
     */
    public function getTitle()
    {
        return $this->title;
    }


    /**
     * Get the event summary.
     *
     * @return  string      Event summary
     */
    public function getSummary()
    {
        return $this->summary;
    }


    /**
     * Get the full description of the event.
     *
     * @return  string      Description text
     */
    public function getDscp()
    {
        return $this->full_description;
    }


    /**
     * Get the location name for the event.
     *
     * @return  string      Name of location
     */
    public function getLocation()
    {
        return $this->location;
    }


    /**
     * Get the location street address.
     *
     * @return  string      Street address
     */
    public function getStreet()
    {
        return $this->street;
    }


    /**
     * Get the location city name.
     *
     * @return  string      City name
     */
    public function getCity()
    {
        return $this->city;
    }


    /**
     * Get the location state/provnce.
     *
     * @return  string      State/province name
     */
    public function getProvince()
    {
        return $this->province;
    }


    /**
     * Get the location postal code
     *
     * @return  string      Postal code
     */
    public function getPostal()
    {
        return $this->postal;
    }


    /**
     * Get the event contact name.
     *
     * @return  string      Contact name
     */
    public function getContact()
    {
        return $this->contact;
    }


    /**
     * Get the event contact email address.
     *
     * @return  string      Email address
     */
    public function getEmail()
    {
        return $this->email;
    }


    /**
     * Get the event contact phone number.
     *
     * @return  string      Phone number
     */
    public function getPhone()
    {
        return $this->phone;
    }


    /**
     * Get the latitude coordinate for the event location.
     *
     * @return  float       Latitude
     */
    public function getLatitude()
    {
        return (float)$this->lat;
    }


    /**
     * Get the longitude coordinate for the event location.
     *
     * @return  float       Longitude
     */
    public function getLongitude()
    {
        return (float)$this->lng;
    }


    /**
     * Get the URL for more info.
     *
     * @return  string      URL
     */
    public function getURL()
    {
        return $this->url;
    }


    /**
     * Get the country location.
     *
     * @return  string      Country name
     */
    public function getCountry()
    {
        return $this->country;
    }


    /**
     * Sets all variables to the matching values from a form or DB record.
     * All properties are optional since the record may come from a plugin
     * after saving an item. Existing values are not overwritten unless
     * specifically included.
     *
     * @param   array   $row        Array of values, from DB or $_POST
     * @param   boolean $fromDB     True if read from DB, false if from $_POST
     */
    public function SetVars($A, $fromDB=false)
    {
        if (!is_array($A)) return;

        if (isset($A['det_id'])) {
            $this->det_id = (int)$A['det_id'];
        }
        if (isset($A['ev_id'])) {
            $this->ev_id = $A['ev_id'];
        }
        if (isset($A['title'])) {
            $this->title = $A['title'];
        }
        if (isset($A['summary'])) {
            $this->summary = $A['summary'];
        }
        if (isset($A['full_description'])) {
            $this->full_description = $A['full_description'];
        }
        if (isset($A['url'])) {
            $this->url = $A['url'];
        }
        if (isset($A['location'])) {
            $this->location = $A['location'];
        }
        if (isset($A['street'])) {
            $this->street = $A['street'];
        }
        if (isset($A['city'])) {
            $this->city = $A['city'];
        }
        if (isset($A['province'])) {
            $this->province = $A['province'];
        }
        if (isset($A['country'])) {
            $this->country = $A['country'];
        }
        if (isset($A['postal'])) {
            $this->postal = $A['postal'];
        }
        if (isset($A['contact'])) {
            $this->contact = $A['contact'];
        }
        if (isset($A['email'])) {
            $this->email = $A['email'];
        }
        if (isset($A['phone'])) {
            $this->phone = $A['phone'];
        }
        if (isset($A['lat'])) {
            $this->lat = (float)$A['lat'];
        }
        if (isset($A['lng'])) {
            $this->lng = (float)$A['lng'];
        }
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @param   integer $det_id Optional ID. Current ID is used if zero.
     * @return  boolean     True if a record was read, False on failure.
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
        if (!$result || DB_numRows($result) != 1) {
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->SetVars($row, true);
            $this->isNew = false;
            return true;
        }
    }


    /**
     * Save the current values to the database.
     * Appends error messages to the $Errors property.
     *
     * @param   array   $A      Optional array of values from $_POST
     * @return  boolean         True if no errors, False otherwise
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
        if (
            $_EV_CONF['use_locator'] == 1 &&
            $this->city != '' &&
            $this->province != ''
        ) {
            $address = $this->street . ' ' . $this->city . ', ' .
                $this->province . ' ' . $this->postal . ' ' .
                $this->country;
            $lat = $this->lat;
            $lng = $this->lng;
            if ($lat == 0 && $lng == 0) {
                $status = LGLIB_invokeService(
                    'locator', 'getCoords',
                    $address, $output, $svc_msg
                );
                if ($status == PLG_RET_OK) {
                    $this->lat = $output['lat'];
                    $this->lng = $output['lng'];
                }
            }
        }

        $lat = EVLIST_coord2str($this->lat, true);
        $lng = EVLIST_coord2str($this->lng, true);

        $fld_set = array();
        foreach ($this->fields as $fld_name) {
            $fld_set[] = "$fld_name='" . DB_escapeString($this->$fld_name) . "'";
        }
        $fld_sql = implode(',', $fld_set);

        // Insert or update the record, as appropriate
        if (!$this->isNew) {
            // For updates, delete the event from the cache table.
           $sql = "UPDATE {$_TABLES['evlist_detail']}
                SET $fld_sql,
                lat = '{$lat}',
                lng = '{$lng}',
                det_revision = det_revision + 1
                WHERE det_id='" . (int)$this->det_id . "'";
            //echo $sql;die;
            DB_query($sql);
        } else {
            $sql = "INSERT INTO {$_TABLES['evlist_detail']} SET
                det_id = 0,
                lat = '{$lat}',
                lng = '{$lng}',
                det_revision = det_revision + 1,
                $fld_sql";
            //echo $sql;die;
            DB_query($sql);
            $this->det_id = DB_insertID();
        }
        return $this->det_id;
    }


    /**
     * Delete the current detail record from the database.
     */
    public function Delete()
    {
        global $_TABLES;

        if ($this->det_id == '') {
            return false;
        }

        $sql = "UPDATE {$_TABLES['evlist_detail']}
            SET det_status = " . Status::CANCELLED .
            " WHERE det_id = {$this->det_id}";
        DB_query($sql);
        return true;
    }


    /**
     * Delete cancelled events that have not been updated in some time.
     */
    public static function purgeCancelled()
    {
        global $_TABLES, $_EV_CONF;

        $days = (int)$_EV_CONF['purge_cancelled_days'];
        $sql = "DELETE FROM {$_TABLES['evlist_detail']}
                WHERE det_status = " . Status::CANCELLED .
                " AND det_last_mod < DATE_SUB(NOW(), INTERVAL $days DAY)";
        DB_query($sql);
    }


    /**
     * Get a formatted address for display.
     * Example: My Location
     *          1234 Main Street.
     *          Los Angeles, CA, USA, 90021
     *
     * @return  string  HTML-formatted address
     */
    public function formatAddress()
    {
        $retval = array();
        if ($this->location != '') {
            $retval[] = htmlspecialchars($this->location);
        }
        if ($this->street != '') {
            $retval[] = htmlspecialchars($this->street);
        }
        $region = array();
        foreach (array('city', 'province', 'country', 'postal') as $fld) {
            if ($this->$fld != '') {
                $region[] = htmlspecialchars($this->$fld);
            }
        }
        if (!empty($region)) {
            $retval[] = implode(' ', $region);
        }
        if (!empty($retval)) {
            return implode('<br />', $retval);
        } else {
            return '';
        }
    }


    /**
     * Get the event address information as an array.
     *
     * @return  array       Array of address lines, omitting blank fields
     */
    public function getAddress()
    {
        $addr = array();
        if ($this->street != '') {
            $addr[] = $this->street;
        }
        if ($this->city != '') {
            $addr[] = $this->city;
        }
        if ($this->province != '') {
            $addr[] = $this->province;
        }
        if ($this->country != '') {
            $addr[] = $this->country;
        }
        if ($this->postal != '') {
            $addr[] = $this->postal;
        }
        return $addr;
    }


    public static function getIndexRecords($fields)
    {
        global $_TABLES;

        if (isset($fields['id']) && !empty($fields['id'])) {
            $ev_id = $fields['id'];
        }
    }

}   // class Detail

?>
