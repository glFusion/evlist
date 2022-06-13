<?php
/**
 * Class to cache DB and web lookup results.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.5
 * @since       v1.4.5
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;

/**
 * Class to handle caching.
 * Wrapper for glFusion\Cache functions.
 * @package evlist
 */
class Cache
{
    /** Minimum glFusion version required for phpFastcache.
     * @constant */
    const MIN_GVERSION = '2.0.0';

    /** Tag to be added to every cached item to associate with the plugin.
     * @constant */
    const TAG = 'evlist'; // fallback tag


    /**
     * Add or replace an item in the cache.
     *
     * @param   string  $key    Item key
     * @param   mixed   $data   Data, typically an array
     * @param   mixed   $tag    Single tag or array
     * @param   integer $ttl    Optional TTL override, in seconds
     * @return  boolean         True on success, False on error.
     */
    public static function set($key, $data, $tag='', $ttl = 3600)
    {
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) return true;

        if ($tag == '') {
            $tag = array(self::TAG);
        } elseif (is_array($tag)) {
            array_push($tag, self::TAG);
        } else {
            $tag = array($tag, self::TAG);
        }
        $key = self::_makeKey($key);
        return \glFusion\Cache\Cache::getInstance()->set($key, $data, $tag, $ttl);
    }


    /**
     * Delete a single item by key.
     *
     * @param   string  $key    Key to delete
     * @return  boolean         True on success, False on failure
     */
    public static function delete($key)
    {
        // Fake return if caching is not supported
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) return true;

        return \glFusion\Cache\Cache::getInstance()->delete(self::_makeKey($key));
    }


    /**
     * Clear some or all items from the cache.
     * Entries matching all tags, including default tag, are removed.
     *
     * @param   mixed   $tag    Single or array of tags
     * @return  boolean         True on success, False on failure
     */
    public static function clear($tag = '')
    {
        // Fake return if caching not supported by glFusion version
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) return true;

        $tags = array(self::TAG);
        if (!empty($tag)) {
            if (!is_array($tag)) $tag = array($tag);
            $tags = array_merge($tags, $tag);
        } else {
            // If no tags give, assume the system cache should be cleared.
            // Only works in glFusion >= 2.0
            CACHE_clear();
        }
        return \glFusion\Cache\Cache::getInstance()->deleteItemsByTagsAll($tags);
    }


    /**
     * Create a unique cache key.
     *
     * @param   string  $key    Base key
     * @return  string          Encoded key string to use as a cache ID
     */
    private static function _makeKey($key)
    {
        return self::TAG . '_' . $key . '_' .
            \glFusion\Cache\Cache::getInstance()->securityHash(true,true);
    }


    /**
     * Get a cache entry.
     *
     * @param   string  $key    Base cache key
     * @return  mixed           Cached results, or NULL if not found
     */
    public static function get($key)
    {
        $retval = NULL;

        // Fake return if caching not supported by glFusion version
        if (version_compare(GVERSION, self::MIN_GVERSION, '<')) return $retval;

        $key = self::_makeKey($key);
        if (\glFusion\Cache\Cache::getInstance()->has($key)) {
            $retval = \glFusion\Cache\Cache::getInstance()->get($key);
        }
        return $retval;
    }

}
