<?php
/**
* glFusion CMS
*
* glFusion Data Filtering
*
* @license GNU General Public License version 2 or later
*     http://www.opensource.org/licenses/gpl-license.php
*
*  Copyright (C) 2021 by the following authors:
*   Mark R. Evans   mark AT glfusion DOT org
*
*/

namespace Evlist;

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

class FieldList extends \glFusion\FieldList
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
            $t->set_file('field', 'fieldlist.thtml');
        } else {
            $t->unset_var('output');
            $t->unset_var('attributes');
        }
        return $t;
    }


    /**
     * Custom to evlist
     */
    public static function repeat(array $args) : string
    {
        $t = self::init();
        $t->set_block('field','field-repeat');
        if (isset($args['url'])) {
            $t->set_var('repeat_url',$args['url']);
        } else {
            $t->set_var('repeat_url','#');
        }

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field-repeat','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-repeat');
        return $t->finish($t->get_var('output'));
    }


    /**
     * Custom to evlist
     */
    public static function space() : string
    {
        $t = self::init();
        $t->set_block('field','field-space');
        $t->parse('output','field-space');
        return $t->finish($t->get_var('output'));
    }

}
