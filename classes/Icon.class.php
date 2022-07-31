<?php
/**
 * Class to standardize icons.
 * Similar to the FieldList class, but only creates icon strings, no links.
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
    /**
     * Return a cached template object to avoid repetitive path lookups.
     *
     * @return  object      Template object
     */
    protected static function init()
    {
        global $_CONF;

        static $t = NULL;

        if ($t === NULL) {
            $t = new \Template(EVLIST_PI_PATH . '/templates');
            $t->set_file('field', 'icon.thtml');
        } else {
            $t->unset_var('output');
            $t->unset_var('attributes');
            $t->unset_var('class');
        }
        return $t;
    }


    public static function get(string $name, array $args=array())
    {
        $t = self::init();
        $block = 'icon-' . $name;
        $t->set_block('field', $block);

        if (isset($args['class'])) {
            $t->set_var('class', $args['class']);
        }
        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block($block, 'attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output', $block, true);
        return trim($t->finish($t->get_var('output')));
    }


    public static function copy(array $args=array())
    {
        $t = self::init();
        $t->set_block('field','icon-copy');

        if (isset($args['class'])) {
            $t->set_var('class', $args['class']);
        }
        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('icon-copy', 'attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','icon-copy',true);
        return trim($t->finish($t->get_var('output')));
    }


    public static function custom(string $name, array $args=array()) : string
    {
        if (empty($name)) {
            return '';
        }

        $t = self::init();
        $t->set_block('field','icon-custom');

        $t->set_var('icon_name', $name);
        if (isset($args['class'])) {
            $t->set_var('class', $args['class']);
        }
        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('icon-custom', 'attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','icon-custom',true);
        return trim($t->finish($t->get_var('output')));
    }

}
