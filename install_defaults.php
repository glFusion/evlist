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
$CONF_EVLIST_DEFAULT = array();

$CONF_EVLIST_DEFAULT['allow_anon_view']    = 1;
// Which users can add events: 1 = users, 2 = anon, 3 = both
$CONF_EVLIST_DEFAULT['can_add']            = EV_USER_CAN_ADD; 
$CONF_EVLIST_DEFAULT['allow_html']         = 1;
$CONF_EVLIST_DEFAULT['usermenu_option']    = 1;
$CONF_EVLIST_DEFAULT['enable_menuitem']    = 1;
$CONF_EVLIST_DEFAULT['week_begins']        = 1;
$CONF_EVLIST_DEFAULT['date_format']        = '%a %b %d, %Y';
$CONF_EVLIST_DEFAULT['time_format']        = '%I:%M %p';
$CONF_EVLIST_DEFAULT['enable_categories']  = 1;
$CONF_EVLIST_DEFAULT['enable_centerblock'] = 0;
$CONF_EVLIST_DEFAULT['pos_centerblock']    = 2;
$CONF_EVLIST_DEFAULT['topic_centerblock']  = 'home';
$CONF_EVLIST_DEFAULT['range_centerblock']  = 2;
$CONF_EVLIST_DEFAULT['limit_list']         = 5;
$CONF_EVLIST_DEFAULT['limit_block']        = 5;
$CONF_EVLIST_DEFAULT['limit_summary']      = 128;
$CONF_EVLIST_DEFAULT['enable_reminders']   = 1;
$CONF_EVLIST_DEFAULT['event_passing']      = 2;
$CONF_EVLIST_DEFAULT['default_permissions'] = array (3, 2, 2, 2);
$CONF_EVLIST_DEFAULT['reminder_speedlimit'] = 30;
$CONF_EVLIST_DEFAULT['post_speedlimit']     = $_CONF['speedlimit'];
$CONF_EVLIST_DEFAULT['reminder_days']       = 1;
$CONF_EVLIST_DEFAULT['displayblocks']       = 1;
$CONF_EVLIST_DEFAULT['default_view']        = 'month';
$CONF_EVLIST_DEFAULT['max_upcoming_days']   = 90;
$CONF_EVLIST_DEFAULT['use_locator']         = 0;
$CONF_EVLIST_DEFAULT['use_weather']         = 0;

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
        $c->add('can_add',$CONF_EVLIST_DEFAULT['allow_anon_add'], 'select',
                0, 0, 15, 20, true, 'evlist');
        /*$c->add('allow_anon_add',$CONF_EVLIST_DEFAULT['allow_anon_add'], 'select',
                0, 0, 0, 20, true, 'evlist');
        $c->add('allow_user_add',$CONF_EVLIST_DEFAULT['allow_user_add'], 'select',
                0, 0, 0, 30, true, 'evlist');*/
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
        $c->add('week_begins',$CONF_EVLIST_DEFAULT['week_begins'], 'select',
                0, 1, 3, 30, true, 'evlist');
        $c->add('date_format',$CONF_EVLIST_DEFAULT['date_format'], 'select',
                0, 1, 4, 40, true, 'evlist');
        $c->add('time_format',$CONF_EVLIST_DEFAULT['time_format'], 'select',
                0, 1, 5, 50, true, 'evlist');
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
        $c->add('use_locator', $CONF_EVLIST_DEFAULT['use_locator'], 'select',
                0, 1, 0, 110, true, 'evlist');
        $c->add('use_weather', $CONF_EVLIST_DEFAULT['use_weather'], 'select',
                0, 1, 0, 120, true, 'evlist');

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
    }

    return true;
}
?>
