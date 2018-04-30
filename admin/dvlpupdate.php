<?php
/**
*   Apply updates to Evlist during development.
*   Calls evlist_upgrade() with "ignore_errors" set so repeated SQL statements
*   won't cause functions to abort.
*
*   Only updates from the previous released version.
*
*   @author     Mark R. Evans mark AT glfusion DOT org
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.6
*   @since      1.4.5
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

require_once '../../../lib-common.php';
if (!SEC_inGroup('Root')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to access the Evlist Development Code Upgrade Routine without proper permissions.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: " . $_SERVER['REMOTE_ADDR'],1);
    $display  = COM_siteHeader();
    $display .= COM_startBlock($LANG27[12]);
    $display .= $LANG27[12];
    $display .= COM_endBlock();
    $display .= COM_siteFooter(true);
    echo $display;
    exit;
}
require_once EVLIST_PI_PATH . '/upgrade.php';   // needed for set_version()
CACHE_clear();
Evlist\Cache::clear();

$ver = '1.4.5';
EVLIST_do_set_version($_EV_CONF['pi_version']);
plugin_upgrade_evlist(true);
EVLIST_do_set_version('1.4.6');

// need to clear the template cache so do it here
CACHE_clear();
header('Location: '.$_CONF['site_admin_url'].'/plugins.php?msg=600');
exit;

?>
