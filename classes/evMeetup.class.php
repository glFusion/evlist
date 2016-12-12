<?php
/**
*   Class to retrieve events from meetup.com
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2016 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.0
*   @since      1.4.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/**
*   Class for Meetup events
*   @package evlist
*/
class evMeetup
{
    var $key;
    var $params;

    /**
    *   Constructor
    *
    *   @param  string  $key    Cache key, e.g. "meetup-week-2016-05-01'
    */
    public function __construct($key = 'meetup')
    {
        global $_EV_CONF;
        $this->params = array(
            'group_id' => $_EV_CONF['meetup_gid'],
            'text_format' => 'plain',
            'fields' => 'timezone',
            //'page' => $_EV_CONF['meetup_page'],
            'only' => 'id,name,time,description,event_url,timezone,duration',
        );

        $this->key = $key;
    }


    /**
    *   Set a parameter
    *
    *   @param  string  $key    Name of parameter to set
    *   @param  mixed   $value  Value to set
    */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
    }


    /**
    *   Remove a parameter.
    *
    *   @param  string  $key    Name of parameter to remove
    */
    public function unsetParam($key)
    {
        unset($this->params[$key]);
    }


    /**
    *   Retrieve Meetup events.
    *   Checks the cache table first for a recent entry.  If not found,
    *   get weather info from Google and update the cache.
    *
    *   @return array   Array of event information
    */
    public function getEvents($start='', $end='', $key='')
    {
        global $_TABLES, $_EV_CONF;

        $events = array();

        // cache_minutes is already sanitized as an intgeger
        $db_loc = strtolower(COM_sanitizeId($loc, false));
        if ($key != '') $this->key = $key;
        $key = DB_escapeString($this->key);
        $sql = "SELECT * FROM {$_TABLES['evlist_cache']} WHERE
                type = '$key' AND
                ts > NOW() - INTERVAL {$_EV_CONF['meetup_cache_minutes']} MINUTE";
        //echo $sql;die;
        $res = DB_query($sql);
        if ($res && DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
        } else {
            $A = array();
        }

        if (!empty($A)) {
            // Got current cache data, return it
            $events = @json_decode($A['data']);
            return $events;
        }

        // Try to get new data from the provider
        // Include the public meetup.com API class
        require_once 'meetup.class.php';

        $time_param = '';
        if (!empty($start)) {
            $time_param .= strtotime($start) * 1000;
        }
        if (!empty($end)) {
            if (!empty($start)) $time_param .= ',';
            $time_param .= strtotime($end . ' 23:59:59') * 1000;
        }
        if (!empty($time_param)) {
            $this->setParam('time', $time_param);
        }

        // Get both upcoming and past events
        $this->setParam('status', 'upcoming,past');

        try {
            $M = new Meetup(array('key' => $_EV_CONF['meetup_key']));
            $response = $M->getEvents($this->params);
            $events = array();
            foreach ($response->results as $event) {
                $tz = $event->timezone;
                $d = new Date($event->time / 1000, $tz);
                $dt = $d->format('Y-m-d', true);
                $tm = $d->format('H:i:s', true);
                if (!isset($events[$dt])) $events[$dt] = array();
                $events[$dt][] = $event;
            }
            $this->updateCache($events);
        }
        catch(Exception $e)
        {
            COM_errorLog('EVLIST:' . $e->getMessage());
            if (!empty($A)) {
            // Got old data from cache, better than nothing
                $events = @json_decode($A['data']);
            }
        }
        return $events;
    }


    /**
    *   Update the cache
    *
    *   @param  mixed   $data   Data, typically an array
    */
    public function updateCache($data)
    {
        global $_TABLES, $_EV_CONF;

        $db_data = DB_escapeString(json_encode($data));
        $key = DB_escapeString($this->key);

        // Delete any stale entries and the current location to be replaced
        // cache_minutes is already sanitized as an intgeger
        DB_query("DELETE FROM {$_TABLES['evlist_cache']}
            WHERE ts < NOW() - INTERVAL {$_EV_CONF['meetup_cache_minutes']} MINUTE");

        // Insert the new record to be cached
        DB_query("INSERT INTO {$_TABLES['evlist_cache']}
                (type, data)
            VALUES
                ('$key', '$db_data')");

    }   // function updateCache()

}   // class evMeetup

?>
