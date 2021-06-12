<?php
/**
 * Class to standardize icons.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2019 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.6
 * @since       v1.4.6
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;

/**
 * Class for product and category sales.
 * @package photocomp
 */
class Icon
{
    /** Icon key-value pairs.
     * @var array */
    static $icons = array(
        'edit'      => 'uk-icon-edit',
        'delete'    => 'uk-icon-remove uk-text-danger',
        'delete-disabled' => 'uk-icon-remove uk-text-disabled',
        'copy'      => 'uk-icon-clone',
        'edit'      => 'uk-icon-edit',
        'arrow-up'  => 'uk-icon-arrow-up',
        'arrow-down'=> 'uk-icon-arrow-down',
        'reset'     => 'uk-icon-refresh',
        'trash'     => 'uk-icon-trash uk-icon-danger',
        'envelope'  => 'uk-icon-envelope',
        'checked'   => 'uk-icon-check uk-text-success',
        'question'  => 'uk-icon-question-circle',
        'blocked'   => 'uk-icon-minus-circle uk-text-danger',
        'alert'     => 'uk-icon-exclamation-triangle uk-text-danger',
        'toggle-on' => 'uk-icon-toggle-on uk-text-success',
        'toggle-off' => 'uk-icon-toggle-off',
        'subscribe' => 'uk-icon-calendar',
        'print' => 'uk-icon-print',
    );


    /**
     * Get the base icon text for a particular type.
     *
     * @param   string  $str    Key string, index into self::$icons
     * @return  string      Icon string
     */
    public static function getIcon($str)
    {
        $str = strtolower($str);
        if (array_key_exists($str, self::$icons)) {
            return self::$icons[$str];
        } else {
            return 'uk-icon uk-icon-' . $str;
        }
    }


    /**
     * Get the HTML string for an icon.
     * If the requested icon is not found, returns nothing.
     *
     * @uses    self::getIcon()
     * @param   string  $str    Key string, index into self::$icons
     * @param   string  $cls    Additional class strings to insert
     * @param   array   $extra  Array of any extra HTML to add, e.g. style, etc.
     * @return  string      Complete HTML string to create the icon
     */
    public static function getHTML($str, $cls = '', $extra = array())
    {
        $icon = self::getIcon($str);
        if ($cls != '') {
            // If addition class values are included, add them
            $icon .= ' ' . $cls;
        }
        $extras = '';
        // Assemble the extra HTML, if any, into the string
        foreach ($extra as $key=>$val) {
            $extras .= ' ' . $key . '="' . $val . '"';
        }
        $icon = '<i class="' . $icon . '" ' . $extras . '></i>';
        return $icon;
    }

}
