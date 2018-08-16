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
    const MIN_GVERSION = '2.0.0';
    private static $tag = 'evlist'; // fallback tag

    /**
    *   Update the cache
    *
    *   @param  string  $key    Item key
    *   @param  mixed   $data   Data, typically an array
    *   @param  mixed   $tag    Single tag or array
    */
    public static function set($key, $data, $tag='')
    {
        global $_EV_CONF;

        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) return NULL;

        $cache_secs = (int)$_EV_CONF['meetup_cache_minutes'] * 60;
        if ($cache_secs < 600) $cache_secs = 1800;
        if ($tag == '')
            $tag = array(self::$tag);
        elseif (is_array($tag))
            array_push($tag, self::$tag);
        else
            $tag = array($tag, self::$tag);
        $key = self::_makeKey($key);
        \glFusion\Cache::getInstance()->set($key, $data, $tag, $cache_secs);
    }


    /**
    *   Delete a single item by key
    *
    *   @param  string  $key    Key to delete
    */
    public static function delete($key)
    {
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) return;
        \glFusion\Cache::getInstance()->delete(self::_makeKey($key));
    }


    /**
    *   Completely clear the cache.
    *   Called after upgrade.
    *   Entries matching all tags, including default tag, are removed.
    *
    *   @param  mixed   $tag    Single or array of tags
    */
    public static function clear($tag = '')
    {
        global $_TABLES;

        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) {
            if (empty($tag)) {
                DB_query("TRUNCATE {$_TABLES['evlist_cache']}", 1);
            }
        } else {
            $tags = array(self::$tag);
            if (!empty($tag)) {
                if (!is_array($tag)) $tag = array($tag);
                $tags = array_merge($tags, $tag);
            }
            \glFusion\Cache::getInstance()->deleteItemsByTagsAll($tags);
        }
    }


    /**
    *   Create a unique cache key.
    *
    *   @return string          Encoded key string to use as a cache ID
    */
    private static function _makeKey($key)
    {
        return self::$tag . '_' . $key . '_' .
            \glFusion\Cache::getInstance()->securityHash(true,true);
    }


    /**
    *   Get a cache entry.
    *   If glFusion version is < 1.8.0 then the DB is used and only Meetup
    *   events are cached. for 1.8.0+ other queries are cached using the Cache
    *   class.
    *
    *   @param  string  $key    Cache key
    *   @return mixed           Array of cached results, or NULL if not found
    */
    public static function get($key)
    {
        global $_EV_CONF;

        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) {
            if ($tag == 'evlist_meetup') {
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
            }
        } else {
            $key = self::_makeKey($key);
            if (\glFusion\Cache::getInstance()->has($key)) {
                $retval = \glFusion\Cache::getInstance()->get($key);
            } else {
                $retval = NULL;
            }
        }
        return $retval;
    }

}   // class Evlist\Cache

?>
