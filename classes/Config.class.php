<?php
/**
 * Class to read and manipulate Evlist configuration values.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;


/**
 * Class to get plugin configuration data.
 * @package evlist
 */
final class Config
{
    /** Plugin Name.
     */
    public const PI_NAME = 'evlist';

    /** Array of config items (name=>val).
     * @var array */
    private $properties = NULL;

    /** Config class singleton instance.
     * @var object */
    static private $instance = NULL;


    /**
     * Get the Evlist configuration object.
     * Creates an instance if it doesn't already exist.
     *
     * @return  object      Configuration object
     */
    public static function getInstance()
    {
        if (self::$instance === NULL) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**
     * Create an instance of the Shop configuration object.
     */
    private function __construct()
    {
        global $_CONF, $_PLUGINS, $_EV_CONF;

        $this->properties = \config::get_instance()->get_config(self::PI_NAME);

        $this->properties['pi_name'] = self::PI_NAME;
        $this->properties['pi_display_name'] = 'Evlist';
        $this->properties['pi_url'] = 'http://www.glfusion.org';
        $this->properties['url'] = $_CONF['site_url'] . '/' . self::PI_NAME;
        $this->properties['admin_url'] = $_CONF['site_admin_url'] . '/plugins/' . self::PI_NAME;
        $this->properties['path'] = $_CONF['path'] . 'plugins/' . self::PI_NAME . '/';
        $this->properties['datapath'] = "{$_CONF['path_html']}/data/" . self::PI_NAME . '/';
        $this->properties['imagepath'] = "{$_CONF['path_html']}/data/" . self::PI_NAME . '/images/';
        $this->properties['imageurl'] = "{$_CONF['site_url']}/data/" . self::PI_NAME . '/images';

        // Check that the Locator and Weather plugins are enabled if used.
        if ($this->properties['use_locator'] && !in_array('locator', $_PLUGINS)) {
            $this->properties['use_locator'] = 0;
        }
        if ($this->properties['use_weather'] && !in_array('weather', $_PLUGINS)) {
            $this->properties['use_weather'] = 0;
        }

        $_EV_CONF = $this->properties;
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key        Name of item to retrieve
     * @param   mixed       $default    Default value if item is not set
     * @return  mixed       Value of config item
     */
    private function _get($key=NULL, $default=NULL)
    {
        if ($key === NULL) {
            return $this->properties;
        } elseif (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        } else {
           return $default;
        }
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set
     */
    private function _set(string $key, $val) : self
    {
        $this->properties[$key] = $val;
        return $this;
    }


    /**
     * Set a configuration value.
     * Unlike the root glFusion config class, this does not add anything to
     * the database. It only adds temporary config vars.
     *
     * @param   string  $key    Configuration item name
     * @param   mixed   $val    Value to set, NULL to unset
     */
    public static function set(string $key, $val=NULL)
    {
        global $_EV_CONF;

        $_EV_CONF[$key] = $val;     // legacy support
        return self::getInstance()->_set($key, $val);
    }


    /**
     * Returns a configuration item.
     * Returns all items if `$key` is NULL.
     *
     * @param   string|NULL $key        Name of item to retrieve
     * @param   mixed       $default    Default value if item is not set
     * @return  mixed       Value of config item
     */
    public static function get(?string $key=NULL, $default=NULL)
    {
        return self::getInstance()->_get($key, $default);
    }


    /**
     * Convenience function to get the base plugin path.
     *
     * @return  string      Path to main plugin directory.
     */
    public static function path()
    {
        return self::_get('path');
    }


    /**
     * Convenience function to get the path to plugin templates.
     *
     * @return  string      Template path
     */
    public static function path_template()
    {
        return self::get('path') . 'templates/';
    }

}
