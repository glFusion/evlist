<?php
/**
 * Automatic installation of the evList plugin.
 *
 * @author      Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2010 - 2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** Include plugin-required files */
require_once __DIR__ . '/evlist.php';
require_once __DIR__ . '/sql/mysql_install.php';
require_once __DIR__ . '/sql/def_events.php';

/**
 * Plugin installation options.
 * @var array
 */
$INSTALL_plugin['evlist'] = array(
    'installer' => array(
        'type' => 'installer',
        'version' => '1',
        'mode' => 'install',
    ),

    'plugin' => array(
        'type'      => 'plugin',
        'name'      => $_EV_CONF['pi_name'],
        'ver'       => $_EV_CONF['pi_version'],
        'gl_ver'    => $_EV_CONF['gl_version'],
        'url'       => $_EV_CONF['pi_url'],
        'display'   => $_EV_CONF['pi_display_name'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_events'],
        'sql' => $_SQL['evlist_events'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_submissions'],
        'sql' => $_SQL['evlist_submissions'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_repeat'],
        'sql' => $_SQL['evlist_repeat'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_categories'],
        'sql' => $_SQL['evlist_categories'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_lookup'],
        'sql' => $_SQL['evlist_lookup'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_remlookup'],
        'sql' => $_SQL['evlist_remlookup'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_detail'],
        'sql' => $_SQL['evlist_detail'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_calendars'],
        'sql' => $_SQL['evlist_calendars'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_tickets'],
        'sql' => $_SQL['evlist_tickets'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_tickettypes'],
        'sql' => $_SQL['evlist_tickettypes'],
    ),
    array(
        'type' => 'table',
        'table' => $_TABLES['evlist_cache'],
        'sql' => $_SQL['evlist_cache'],
    ),
    array(
        'type' => 'feature',
        'feature' => 'evlist.admin',
        'desc' => 'Administrative access to the evList plugin',
        'variable' => 'admin_feature_id',
    ),
    array(
        'type' => 'feature',
        'feature' => 'evlist.submit',
        'desc' => 'May bypass the evList submission queue',
        'variable' => 'submit_feature_id',
    ),
    array(
        'type' => 'mapping',
        'findgroup' => 'Root',
        'feature' => 'admin_feature_id',
        'log' => 'Adding Admin feature to the Root group',
    ),
    array(
        'type' => 'mapping',
        'findgroup' => 'Root',
        'feature' => 'submit_feature_id',
        'log' => 'Adding evList Submit feature to the Root group',
    ),
    array(
        'type' => 'sql',
        'sql' => $DEFVALUES['evlist_events'],
    ),
    array(
        'type' => 'sql',
        'sql' => $DEFVALUES['evlist_detail'],
    ),
    array(
        'type' => 'sql',
        'sql' => $DEFVALUES['evlist_categories'],
    ),
    array(
        'type' => 'sql',
        'sql' => $DEFVALUES['evlist_calendars'],
    ),
    array(
        'type' => 'sql',
        'sql' => $DEFVALUES['evlist_submissions'],
    ),
    array(
        'type' => 'sql',
        'sql' => $DEFVALUES['evlist_repeat'],
    ),
    array(
        'type' => 'sql',
        'sql' => $DEFVALUES['evlist_tickettypes'],
    ),
    array(
        'type' => 'block',
        'name' => 'evlist_upcoming',
        'title' => 'Upcoming Events',
        'phpblockfn' => 'phpblock_evlist_upcoming',
        'block_type' => 'phpblock',
        'group_id' => 'admin_group_id',
    ),
    array(
        'type'          => 'block',
        'name'          => 'evlist_smallmonth',
        'title'         => 'Event Calendar',
        'phpblockfn'    => 'phpblock_evlist_smallmonth',
        'block_type'    => 'phpblock',
        'group_id'      => 'admin_group_id',
        'is_enabled'    => 0,
    ),
);


/**
 * Puts the datastructures for this plugin into the glFusion database.
 * Note: Corresponding uninstall routine is in functions.inc.
 *
 * @return  boolean     True if successful False otherwise
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


/**
 * Loads the configuration records for the Online Config Manager.
 *
 * @return  boolean     true = proceed with install, false = an error occured
 *
 */
function plugin_load_configuration_evlist()
{
    require_once __DIR__ . '/install_defaults.php';
    return plugin_initconfig_evlist();
}
