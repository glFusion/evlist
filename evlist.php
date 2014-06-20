<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | evlist.php                                                               |
// |                                                                          |
// | evList configuration options                                             |
// +--------------------------------------------------------------------------+
// | Based on the evList Plugin for Geeklog CMS                               |
// | Copyright (C) 2007 by the following authors:                             |
// |                                                                          |
// | Authors: Alford Deeley     - ajdeeley AT summitpages.ca                  |
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
*   glFusion API functions for the EvList plugin
*
*   @author     Mark R. Evans mark AT glfusion DOT org
*   @copyright  Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/**
*   Define global variables.
*   The arrays have already been created; these are effectively overrides
*/

/*-------------------------------------------------------------------------*/
/* Don't change anything below this line                                   */
/*-------------------------------------------------------------------------*/

$_EV_CONF['pi_name']         = 'evlist';
$_EV_CONF['pi_display_name'] = 'evList Event Calendar';
$_EV_CONF['pi_version']      = '1.3.7';
$_EV_CONF['gl_version']      = '1.3.0';
$_EV_CONF['pi_url']          = 'http://www.glfusion.org';


$_TABLES['evlist_events']       = $_DB_table_prefix . 'evlist_events';
$_TABLES['evlist_categories']   = $_DB_table_prefix . 'evlist_categories';
$_TABLES['evlist_lookup']       = $_DB_table_prefix . 'evlist_lookup';
$_TABLES['evlist_remlookup']    = $_DB_table_prefix . 'evlist_remlookup';
$_TABLES['evlist_submissions']  = $_DB_table_prefix . 'evlist_submissions';
$_TABLES['evlist_repeat']       = $_DB_table_prefix . 'evlist_repeat';
$_TABLES['evlist_detail']       = $_DB_table_prefix . 'evlist_detail';
$_TABLES['evlist_calendars']    = $_DB_table_prefix . 'evlist_calendars';
$_TABLES['evlist_rsvp']         = $_DB_table_prefix . 'evlist_rsvp';

// Deprecated tables, but needed to do the upgrade
$_TABLES['evlist_dateformat']   = $_DB_table_prefix . 'evlist_dateformat';
$_TABLES['evlist_timeformat']   = $_DB_table_prefix . 'evlist_timeformat';

/** Define base path to plugin */
define('EVLIST_PI_PATH', "{$_CONF['path']}plugins/{$_EV_CONF['pi_name']}");
/** Define base URL to plugin */
define('EVLIST_URL', "{$_CONF['site_url']}/{$_EV_CONF['pi_name']}");
/** Define URL to plugin admin interface */
define('EVLIST_ADMIN_URL',
        "{$_CONF['site_admin_url']}/plugins/{$_EV_CONF['pi_name']}");

define('EV_RECUR_DAILY',    1);     // Every day
define('EV_RECUR_MONTHLY',  2);     // Monthly on the date
define('EV_RECUR_YEARLY',   3);     // Yearly on the date
define('EV_RECUR_WEEKLY',   4);     // Weekly on the day(s)
define('EV_RECUR_DOM',      5);     // Day of Month (2nd Tuesday, etc.)
define('EV_RECUR_DATES',    6);     // Specific dates
define('EV_MIN_DATE', '1970-01-01');    // First date that we want to handle
define('EV_MAX_DATE', '2037-12-31');    // Last date that we can handle

define('EV_USER_CAN_ADD',   1);
define('EV_ANON_CAN_ADD',   2);

$_EV_CONF['min_locator_ver'] = '1.0.3'; // minimum locator version required
$_EV_CONF['max_repeats'] = 1000;    // Max repeats created for events
$_EV_CONF['enable_rsvp'] = 0;       // Future feature- TODO: remove when ready

?>
