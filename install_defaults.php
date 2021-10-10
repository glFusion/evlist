<?php
/**
*   Installation defaults for the evList plugin
*
*   @author     Mark R. Evans mark AT glfusion DOT org
*   @copyright  Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
*   @package    evlist
*   @version    v1.4.6
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die('This file can not be used on its own!');
}

/**
*   @global array   $evlistConfigData
*   evList default settings
*/
global $evlistConfigData;
$evlistConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'ev_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'allow_anon_view',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'can_add',
        'default_value' => EV_USER_CAN_ADD,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'allow_html',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'enable_categories',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'event_passing',
        'default_value' => 2,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 6,
        'sort' => 50,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'reminder_speedlimit',
        'default_value' => '30',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 60,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'post_speedlimit',
        'default_value' => $_CONF['speedlimit'],
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 70,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'enable_reminders',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 80,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'reminder_days',
        'default_value' => 1,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 90,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'commentsupport',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 100,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'default_permissions',
        'default_value' => array (3, 2, 2, 2),
        'type' => '@select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 12,
        'sort' => 110,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'purge_cancelled_days',
        'default_value' => '30',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 120,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'ical_range',
        'default_value' => '60,180',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 130,
        'set' => true,
        'group' => 'evlist',
    ),

    // GUI settings
    array(
        'name' => 'ev_gui',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'enable_menuitem',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'usermenu_option',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 2,
        'sort' => 20,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'displayblocks',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 13,
        'sort' => 30,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'limit_list',
        'default_value' => '10',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'limit_block',
        'default_value' => '5',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 50,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'default_view',
        'default_value' => 'month',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 14,
        'sort' => 60,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'max_upcoming_days',
        'default_value' => '90',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 70,
        'set' => true,
        'group' => 'evlist',
    ),

    // Centerblock settings
    array(
        'name' => 'ev_centerblock',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'enable_centerblock',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 9,
        'sort' => 10,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'pos_centerblock',
        'default_value' => 'home',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 7,
        'sort' => 20,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'topic_centerblock',
        'default_value' => 'home',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 0,     // uses helper function
        'sort' => 30,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'range_centerblock',
        'default_value' => 2,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 8,
        'sort' => 40,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'limit_summary',
        'default_value' => '128',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 0,
        'sort' => 60,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'cb_dup_chk',
        'default_value' => 'rp_ev_id',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 10,
        'sort' => 70,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'cb_hide_small',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 0,
        'sort' => 80,
        'set' => true,
        'group' => 'evlist',
    ),

    // Subgroup: RSVP
    array(
        'name' => 'sg_rsvp',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 10,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'ev_rsvp',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 10,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'enable_rsvp',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 10,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'rsvp_print',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 10,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'ticket_format',
        'default_value' => 'EV%s',
        'type' => 'text',
        'subgroup' => 10,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'qrcode_tickets',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 10,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'evlist',
    ),

    // Subgroup: External integrations
    array(
        'name' => 'sg_integ',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 20,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'evlist',
    ),
    // Fieldset: Other Integrations
    array(
        'name' => 'ev_integ_other',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 20,
        'fieldset' => 10,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'use_locator',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 20,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'use_weather',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 20,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'evlist',
    ),
    array(
        'name' => 'pi_cal_map',
        'default_value' => array(
            'birthdays' => '-2',
        ),
        'type' => '*select',
        'subgroup' => 20,
        'fieldset' => 10,
        'selection_array' => 2,
        'sort' => 30,
        'set' => true,
        'group' => 'evlist',
    ),
);


/**
* Initialize evList plugin configuration
*
* Creates the database entries for the configuation if they don't already
* exist. Initial values will be taken from $CONF_EVLIST if available (e.g. from
* an old config.php), uses $CONF_EVLIST_DEFAULT otherwise.
*
* @param    integer $group_id   Not used
* @return   boolean     true: success; false: an error occurred
*
*/
function plugin_initconfig_evlist($group_id = 0)
{
    global $evlistConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('evlist')) {
        USES_lib_install();
        foreach ($evlistConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    }
    return true;
}


/**
 * Sync the configuration in the DB to the above configs
 */
function plugin_updateconfig_evlist()
{
    global $evlistConfigData;

    USES_lib_install();
    _update_config('evlist', $evlistConfigData);
}

?>
