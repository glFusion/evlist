<?php
/**
*   Class to retrieve events from meetup.com
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2016 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.3
*   @since      1.4.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;

/**
*   Class for Meetup events
*   @package evlist
*/
class evMeetup
{
    var $key;
    var $params;
    private static $tag = 'evlist_meetup';

    /**
    *   Constructor
    *
    *   @param  string  $key    Cache key, e.g. "meetup-week-2016-05-01'
    */
    public function __construct($key = 'meetup')
    {
        global $_EV_CONF;
        $this->params = array(
            'group_id' => implode(',', $_EV_CONF['meetup_gid']),
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
    *   get information from meetup.com
    *
    *   @return array   Array of event information
    */
    public function getEvents($start='', $end='')
    {
        global $_TABLES, $_EV_CONF, $_CONF;

        $events = array();
        if (GVERSION >= '1.8.0') {
            $key = self::$tag . '_' . md5($start.'_'.$end);
            $A = Cache::getCache($key);
            if ($A !== NULL) {
                return $A;
            }
        } else {
            $key = DB_escapeString($this->key);
            $sql = "SELECT * FROM {$_TABLES['evlist_cache']} WHERE
                    type = 'meetup' AND
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
        }

        // Try to get new data from the provider
        // Include the public meetup.com API class
        require_once 'meetup.class.php';

        $time_param = '';
        if (!empty($start)) {
            $d = new \Date($start, $_CONF['timezone']);
            $time_param .= $d->toUnix() * 1000;
        }
        if (!empty($end)) {
            if (!empty($start)) $time_param .= ',';
            $d = new \Date($end . ' 23:59:59', $_CONF['timezone']);
            $time_param .= $d->toUnix() * 1000;
        }
        if (!empty($time_param)) {
            $this->setParam('time', $time_param);
        }

        // Get both upcoming and past events
        $this->setParam('status', 'upcoming,past');

        try {
            $M = new Meetup(array('key' => $_EV_CONF['meetup_key']));
            $response = $M->getEvents($this->params);
            if (!empty($response)) {
                $events = array();
                foreach ($response->results as $event) {
                    $tz = $event->timezone;
                    $d = new \Date($event->time / 1000, $tz);
                    $dt = $d->format('Y-m-d', true);
                    $tm = $d->format('H:i:s', true);
                    if (!isset($events[$dt])) $events[$dt] = array();
                    $events[$dt][] = $event;
                }
                $this->updateCache($key, $events);
            }
        }
        catch(\Exception $e) {
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
    public function updateCache($key, $data)
    {
        global $_TABLES, $_EV_CONF;

        if (GVERSION < '1.8.0') {
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
        } else {
            Cache::setCache($key, $data, self::$tag);
        }
    }

}   // class evMeetup

?>
