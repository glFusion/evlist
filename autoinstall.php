<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | autoinstall.php                                                          |
// |                                                                          |
// | glFusion Auto Installer module                                           |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009 by the following authors:                             |
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
*   Automatic installation of the evList plugin
*   @package    evlist
*/
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_dbms;

/** Include plugin-required files */
require_once $_CONF['path'].'plugins/evlist/evlist.php';
require_once $_CONF['path'].'plugins/evlist/sql/'.$_DB_dbms.'_install.php';
require_once $_CONF['path'].'plugins/evlist/sql/def_events.php';

// +--------------------------------------------------------------------------+
// | Plugin installation options                                              |
// +--------------------------------------------------------------------------+

$INSTALL_plugin['evlist'] = array(
    'installer' => array('type' => 'installer',
                    'version' => '1', 
                    'mode' => 'install'),

    'plugin' => array(  'type'      => 'plugin',
                        'name'      => $_EV_CONF['pi_name'],
                        'ver'       => $_EV_CONF['pi_version'],
                        'gl_ver'    => $_EV_CONF['gl_version'],
                        'url'       => $_EV_CONF['pi_url'],
                        'display'   => $_EV_CONF['pi_display_name']),

    array(  'type' => 'table', 
            'table' => $_TABLES['evlist_events'], 
            'sql' => $_SQL['evlist_events'],
        ),
    array(  'type' => 'table', 
            'table' => $_TABLES['evlist_submissions'], 
            'sql' => $_SQL['evlist_submissions'],
        ),
    array(  'type' => 'table', 
            'table' => $_TABLES['evlist_repeat'], 
            'sql' => $_SQL['evlist_repeat'],
        ),
    array(  'type' => 'table', 
            'table' => $_TABLES['evlist_categories'], 
            'sql' => $_SQL['evlist_categories'],
        ),
    array(  'type' => 'table', 
            'table' => $_TABLES['evlist_lookup'], 
            'sql' => $_SQL['evlist_lookup'],
        ),
    array(  'type' => 'table', 
            'table' => $_TABLES['evlist_remlookup'], 
            'sql' => $_SQL['evlist_remlookup'],
        ),

    array(  'type' => 'table',
            'table' => $_TABLES['evlist_detail'],
            'sql' => $_SQL['evlist_detail'],
        ),

    array(  'type' => 'table',
            'table' => $_TABLES['evlist_calendars'],
            'sql' => $_SQL['evlist_calendars'],
        ),

    array(  'type' => 'group', 
            'group' => 'evList Admin', 
            'desc' => 'Administrator of the evList Plugin',
            'variable' => 'admin_group_id', 
            'addroot' => true, 
            'admin' => true,
        ),

    array(  'type' => 'feature', 
            'feature' => 'evlist.submit', 
            'desc' => 'May bypass the evList submission queue',
            'variable' => 'submit_feature_id',
        ),

    array(  'type' => 'mapping', 
            'group' => 'admin_group_id', 
            'feature' => 'submit_feature_id', 
            'log' => 'Adding evList Submit feature to the evList admin group',
        ),

    array(  'type' => 'feature', 
            'feature' => 'evlist.admin', 
            'desc' => 'Administrative access to the evList plugin',
            'variable' => 'admin_feature_id',
        ),

    array(  'type' => 'mapping', 
            'group' => 'admin_group_id', 
            'feature' => 'admin_feature_id', 
            'log' => 'Adding evList Admin feature to the evList admin group',
        ),

    array('type' => 'sql', 'sql' => $DEFVALUES['evlist_events']),
    array('type' => 'sql', 'sql' => $DEFVALUES['evlist_detail']),
    array('type' => 'sql', 'sql' => $DEFVALUES['evlist_categories']),
    array('type' => 'sql', 'sql' => $DEFVALUES['evlist_calendars']),
    array('type' => 'sql', 'sql' => $DEFVALUES['evlist_submissions']),
    array('type' => 'sql', 'sql' => $DEFVALUES['evlist_repeat']),

    array(  'type' => 'block', 
            'name' => 'evlist_upcoming', 
            'title' => 'Upcoming Events',
            'phpblockfn' => 'phpblock_evlist_upcoming', 
            'block_type' => 'phpblock',
            'group_id' => 'admin_group_id',
        ),

    array(  'type'          => 'block', 
            'name'          => 'evlist_smallmonth', 
            'title'         => 'Event Calendar',
            'phpblockfn'    => 'phpblock_evlist_smallmonth', 
            'block_type'    => 'phpblock',
            'group_id'      => 'admin_group_id',
            'is_enabled'    => 0,
        ),
);

/**
*   Puts the datastructures for this plugin into the glFusion database
*   Note: Corresponding uninstall routine is in functions.inc
*
*   @return   boolean True if successful False otherwise
*/
function plugin_install_evlist()
{
    global $INSTALL_plugin, $_EV_CONF, $_TABLES;

    $pi_name            = $_EV_CONF['pi_name'];
    $pi_display_name    = $_EV_CONF['pi_display_name'];
    $pi_version         = $_EV_CONF['pi_version'];

    COM_errorLog("Attempting to install the $pi_display_name plugin", 1);

    $ret = INSTALLER_install($INSTALL_plugin[$pi_name]);
    if ($ret > 0) {
        return false;
    }

    return true;
}


// These are required for the evEvent class.  They're included here
// since functions.inc isn't available until the plugin is installed
define(EVLIST_PI_PATH, dirname(__FILE__));

function USES_evlist_functions()
{    require_once EVLIST_PI_PATH . '/evlist_functions.inc.php'; }

function USES_evlist_views()
{    require_once EVLIST_PI_PATH . '/evlist_views.inc.php'; }

function USES_evlist_class_datecalc()
{   require_once EVLIST_PI_PATH . '/classes/DateCalc.class.php'; }

function USES_evlist_class_event()
{   require_once EVLIST_PI_PATH . '/classes/evEvent.class.php'; }

function USES_evlist_class_detail()
{   require_once EVLIST_PI_PATH . '/classes/evDetail.class.php'; }

function USES_evlist_class_repeat()
{   require_once EVLIST_PI_PATH . '/classes/evRepeat.class.php'; }

function USES_evlist_class_calendar()
{   require_once EVLIST_PI_PATH . '/classes/evCalendar.class.php'; }


/**
*   Post-installation activity.
*   Create the repeating event records for the sample data that was loaded.
*/
function X_plugin_postinstall_evlist()
{
    global $_TABLES, $_CONF, $_EV_CONF;

    require_once $_CONF['path'].'/plugins/evlist/functions.inc';

    // Now create the repeat records for the default events
    USES_evlist_class_event();
    $sql = "SELECT id FROM {$_TABLES['evlist_events']}";
    $res = DB_query($sql);
    $Ev = new evEvent();
    while ($A = DB_fetchArray($res, false)) {
        $Ev->Read($A['id']);
        $Ev->UpdateRepeats();
    }

    // Clear the template cache since we've introduced some new css.
    // Might not be needed outside of testing where the plugin is repeatedly
    // installed & removed, but doesn't hurt.
    CTL_clearCache();
}


/**
* Loads the configuration records for the Online Config Manager
*
* @return   boolean     true = proceed with install, false = an error occured
*
*/
function plugin_load_configuration_evlist()
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/evlist/install_defaults.php';

    return plugin_initconfig_evlist();
}


?>
