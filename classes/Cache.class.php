<?php
/**
*   Class to cache DB and web lookup results
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.5
*   @since      1.4.5
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;

/**
*   Class for Meetup events
*   @package evlist
*/
class Cache
{
    private static $tag = 'evlist'; // fallback tag

    /**
    *   Update the cache
    *
    *   @param  string  $key    Item key
    *   @param  mixed   $data   Data, typically an array
    *   @param  
    */
    public static function setCache($key, $data, $tag='')
    {
        global $_EV_CONF;

        $cache_mins = (int)$_EV_CONF['meetup_cache_minutes'];
        if ($cache_mins < 10) $cache_mins = 30;
        if ($tag == '')
            $tag = array(self::$tag);
        else
            $tag = array($tag, self::$tag);
        $key = self::_makeKey($key, $tag);
        \glFusion\Cache::getInstance()
            ->set($key, $data, $tag, $cache_mins * 60);
    }


    /**
    *   Completely clear the cache.
    *   Called after upgrade.
    */
    public static function clearCache()
    {
        \glFusion\Cache::getInstance()->deleteItemsByTag(self::$tag);
    }


    /**
    *   Create a unique cache key.
    *
    *   @return string          Encoded key string to use as a cache ID
    */
    private static function _makeKey($key, $tag='')
    {
        if ($tag == '') $tag = self::$tag;
        return $tag[0] . '_' . $key;
    }

    
    public static function getCache($key, $tag='')
    {
        global $_EV_CONF;

        $key = self::_makeKey($key);
        if (GVERSION < '1.8.0') {
            $retval = array();
            $cache_mins = (int)$_EV_CONF['meetup_cache_minutes'];
            if ($cache_mins < 10) $cache_mins = 30;
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
                $retval = @json_decode($A['data']);
            }
            return $retval;
        } else {
            return \glFusion\Cache::getInstance()
                ->get(self::_makeKey($key, $tag));
        }
    }

}   // class Evlist\Cache

?>
