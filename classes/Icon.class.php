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
        'edit'      => 'uk-icon uk-icon-edit',
        'delete'    => 'uk-icon uk-icon-remove uk-text-danger',
        'delete-disabled' => 'uk-icon uk-icon-remove uk-text-disabled',
        'copy'      => 'uk-icon uk-icon-clone',
        'edit'      => 'uk-icon uk-icon-edit',
        'arrow-up'  => 'uk-icon uk-icon-arrow-up',
        'arrow-down'=> 'uk-icon uk-icon-arrow-down',
        'reset'     => 'uk-icon uk-icon-refresh',
        'trash'     => 'uk-icon uk-icon-trash uk-icon-danger',
        'envelope'  => 'uk-icon uk-icon-envelope',
        'checked'   => 'uk-icon uk-icon-check uk-text-success',
        'question'  => 'uk-icon uk-icon-question-circle',
        'blocked'   => 'uk-icon uk-icon-minus-circle uk-text-danger',
        'alert'     => 'uk-icon uk-icon-exclamation-triangle uk-text-danger',
        'toggle-on' => 'uk-icon uk-icon-toggle-on uk-text-success',
        'toggle-off' => 'uk-icon uk-icon-toggle-off',
        'subscribe' => 'uk-icon uk-icon-calendar',
        'print' => 'uk-icon uk-icon-print',
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
            return '';
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
        if ($icon != '') {
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
        }
        return $icon;
    }

}

?>
