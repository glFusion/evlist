<?php
// +--------------------------------------------------------------------------+
// | evList Plugin - glFusion CMS                                             |
// +--------------------------------------------------------------------------+
// | install_defaults.php                                                     |
// |                                                                          |
// | Initial Installation Defaults used when loading the online configuration |
// | records. These settings are only used during the initial installation    |
// | and not referenced any more once the plugin is installed.                |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008-2010 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+
/**
*   Installation defaults for the evList plugin
*
*   @author     Mark R. Evans mark AT glfusion DOT org
*   @copyright  Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die('This file can not be used on its own!');
}

/**
*   @global array   $CONF_EVLIST_DEFAULT
*   evList default settings
*
*   Initial Installation Defaults used when loading the online configuration
*   records. These settings are only used during the initial installation
*   and not referenced any more once the plugin is installed
*/

global $CONF_EVLIST_DEFAULT;
$CONF_EVLIST_DEFAULT = array(
    'allow_anon_view'   => 1,    // allow anonymous to view calendars
// Which users can add events: 1 = users, 2 = anon, 3 = both
    'can_add'           => EV_USER_CAN_ADD,
    'allow_html'        => 1,    // allow html in posts
    'usermenu_option'   => 1,   // add to the glfusion user menu
    'enable_menuitem'   => 1,   // add to the glfusion main menu
    'enable_categories' => 1,   // enable event categories
    'enable_centerblock' => 0,  // set as centerblock
    'pos_centerblock'   => 2,   // centerblock position
    'topic_centerblock' => 'home',  // centerblock topic
    'range_centerblock' => 2,   // event range for centerblock
    'limit_list'        => 5,   // number of events shown in list view
    'limit_block'       => 5,   // number of events shown in upcoming block
    'limit_summary'     => 128, // number of characters in summaries
    'enable_reminders'  => 1,   // enable email reminders
    'event_passing'     => 2,   // when has an event passed
    'default_permissions' => array (3, 2, 2, 2),
    'reminder_speedlimit' => 30,    // max frequency for reminder submissions
    'post_speedlimit'   => $_CONF['speedlimit'],    // max freq. for posts
    'reminder_days'     => 1,   // min number of days for reminders
    'displayblocks'     => 1,   // display glfusion blocks
    'default_view'      => 'month',
    'max_upcoming_days' => 90,  // max days in future for list and block
    'use_locator'       => 0,   // integrate with locater plugin
    'use_weather'       => 0,   // integrate with westher plugin
    'cal_tmpl'          => 'json',   // json or html calendar display
    'enable_rsvp'       => 0,   // 0=false, 1=default no, 2=default yes
    'rsvp_print'        => 0,   // 0=false, 1=default no, 2=default yes paid, 3=default yes all
    'meetup_key'        => '',  // Meetup API key
    'meetup_gid'        => '',  // Meetup group IDs
    'meetup_cache_minutes' => 30,
    'meetup_enabled'    => 0,   // 1 to enable meetup event inclusion
);

/**
* Initialize evList plugin configuration
*
* Creates the database entries for the configuation if they don't already
* exist. Initial values will be taken from $CONF_EVLIST if available (e.g. from
* an old config.php), uses $CONF_EVLIST_DEFAULT otherwise.
*
* @return   boolean     true: success; false: an error occurred
*
*/
function plugin_initconfig_evlist()
{
    global $_EV_CONF, $CONF_EVLIST_DEFAULT;

    if (is_array($_EV_CONF) && (count($_EV_CONF) > 1)) {
        $CONF_EVLIST_DEFAULT = array_merge($CONF_EVLIST_DEFAULT, $_EV_CONF);
    }
    $c = config::get_instance();
    if (!$c->group_exists('evlist')) {

        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true, 'evlist');
        $c->add('ev_access', NULL, 'fieldset', 0, 0, NULL, 0, true, 'evlist');

        $c->add('allow_anon_view',$CONF_EVLIST_DEFAULT['allow_anon_view'], 'select',
                0, 0, 0, 10, true, 'evlist');
        $c->add('can_add',$CONF_EVLIST_DEFAULT['can_add'], 'select',
                0, 0, 15, 20, true, 'evlist');
        $c->add('allow_html',$CONF_EVLIST_DEFAULT['allow_html'], 'select',
                0, 0, 0, 40, true, 'evlist');
        $c->add('enable_menuitem',$CONF_EVLIST_DEFAULT['enable_menuitem'], 'select',
                0, 0, 0, 50, true, 'evlist');
        $c->add('enable_categories',$CONF_EVLIST_DEFAULT['enable_categories'], 'select',
                0, 0, 0, 55, true, 'evlist');
        $c->add('reminder_speedlimit',$CONF_EVLIST_DEFAULT['reminder_speedlimit'], 'text',
                0, 0, NULL, 60, true, 'evlist');
        $c->add('post_speedlimit',$CONF_EVLIST_DEFAULT['post_speedlimit'], 'text',
                0, 0, NULL, 70, true, 'evlist');
        $c->add('enable_reminders',$CONF_EVLIST_DEFAULT['enable_reminders'], 'select',
                0, 0, 0, 80, true, 'evlist');
        $c->add('reminder_days',$CONF_EVLIST_DEFAULT['reminder_days'], 'text',
                0, 0, NULL, 90, true, 'evlist');


        $c->add('ev_gui', NULL, 'fieldset', 0, 1, NULL, 0, true, 'evlist');
        $c->add('enable_menuitem',$CONF_EVLIST_DEFAULT['enable_menuitem'], 'select',
                0, 1, 0, 10, true, 'evlist');
        $c->add('usermenu_option',$CONF_EVLIST_DEFAULT['usermenu_option'], 'select',
                0, 1, 2, 20, true, 'evlist');
        $c->add('displayblocks',$CONF_EVLIST_DEFAULT['displayblocks'], 'select',
                0, 1, 13, 25, true, 'evlist');
        $c->add('event_passing',$CONF_EVLIST_DEFAULT['event_passing'], 'select',
                0, 1, 6, 60, true, 'evlist');
        $c->add('limit_list',$CONF_EVLIST_DEFAULT['limit_list'], 'text',
                0, 1, 0, 70, true, 'evlist');
        $c->add('limit_block',$CONF_EVLIST_DEFAULT['limit_block'], 'text',
                0, 1, 0, 80, true, 'evlist');
        $c->add('default_view', $CONF_EVLIST_DEFAULT['default_view'], 'select',
                0, 1, 14, 90, true, 'evlist');
        $c->add('max_upcoming_days', $CONF_EVLIST_DEFAULT['max_upcoming_days'], 'text',
                0, 1, 0, 100, true, 'evlist');
        $c->add('cal_tmpl', $CONF_EVLIST_DEFAULT['cal_tmpl'], 'select',
                0, 1, 16, 130, true, 'evlist');

        $c->add('ev_centerblock', NULL, 'fieldset', 0, 2, NULL, 0, true,
                'evlist');
        $c->add('enable_centerblock',$CONF_EVLIST_DEFAULT['enable_centerblock'], 'select',
                0, 2, 9, 10, true, 'evlist');
        $c->add('pos_centerblock',$CONF_EVLIST_DEFAULT['pos_centerblock'], 'select',
                0, 2, 7, 20, true, 'evlist');
        $c->add('topic_centerblock',$CONF_EVLIST_DEFAULT['topic_centerblock'], 'select',
                0, 2, NULL, 30, true, 'evlist');
        $c->add('range_centerblock',$CONF_EVLIST_DEFAULT['range_centerblock'], 'select',
                0, 2, 8, 40, true, 'evlist');
        $c->add('limit_block',$CONF_EVLIST_DEFAULT['limit_block'], 'text',
                0, 2, 0, 50, true, 'evlist');
        $c->add('limit_summary',$CONF_EVLIST_DEFAULT['limit_summary'], 'text',
                0, 2, 0, 60, true, 'evlist');

        $c->add('ev_permissions', NULL, 'fieldset', 0, 3, NULL, 0, true,
                'evlist');
        $c->add('default_permissions', $CONF_EVLIST_DEFAULT['default_permissions'],
                '@select', 0, 3, 12, 10, true, 'evlist');

        $c->add('sg_rsvp', NULL, 'subgroup', 20, 0, NULL, 0, true, 'evlist');
        $c->add('ev_rsvp', NULL, 'fieldset', 20, 10, NULL, 0, true, 'evlist');
        $c->add('enable_rsvp',$CONF_EVLIST_DEFAULT['enable_rsvp'], 'select',
                20, 10, 0, 10, true, 'evlist');
        $c->add('rsvp_print',$CONF_EVLIST_DEFAULT['rsvp_print'], 'select',
                20, 10, 17, 20, true, 'evlist');

        // External integrations
        $c->add('sg_integ', NULL, 'subgroup', 30, 0, NULL, 0, true, 'evlist');
        $c->add('ev_integ_meetup', NULL, 'fieldset', 30, 10, NULL, 0, true, 'evlist');
        $c->add('meetup_enabled',$CONF_EVLIST_DEFAULT['meetup_enabled'], 'select',
                30, 10, 0, 10, true, 'evlist');
        $c->add('meetup_key',$CONF_EVLIST_DEFAULT['meetup_key'], 'text',
                30, 10, 0, 20, true, 'evlist');
        $c->add('meetup_gid',$CONF_EVLIST_DEFAULT['meetup_gid'], 'text',
                30, 10, 0, 30, true, 'evlist');
        $c->add('meetup_cache_minutes',$CONF_EVLIST_DEFAULT['meetup_cache_minutes'], 'text',
                30, 10, 0, 40, true, 'evlist');
        $c->add('use_locator', $CONF_EVLIST_DEFAULT['use_locator'], 'select',
                30, 10, 0, 50, true, 'evlist');
        $c->add('use_weather', $CONF_EVLIST_DEFAULT['use_weather'], 'select',
                30, 10, 0, 60, true, 'evlist');
     }

    return true;
}
?>
