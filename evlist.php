<?php
/**
 * Table and variable definitions for the Evlist plugin for glFusion.
 *
 * @author     Mark R. Evans mark AT glfusion DOT org
 * @copyright  Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @copyright  Copyright (c) 2010 - 2022 Lee Garner <lee@leegarner.com>
 * @package    evlist
 * @version    v1.5.6
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_table_prefix, $_TABLES;

/**
 * Define global variables.
 * The arrays have already been created; these are effectively overrides.
 */
$_EV_CONF['pi_name']         = 'evlist';
$_EV_CONF['pi_display_name'] = 'Event Calendar';
$_EV_CONF['pi_version']      = '1.5.7.4';
$_EV_CONF['gl_version']      = '2.0.0';
$_EV_CONF['pi_url']          = 'https://www.glfusion.org';


$_TABLES['evlist_events']       = $_DB_table_prefix . 'evlist_events';
$_TABLES['evlist_submissions']  = $_DB_table_prefix . 'evlist_submissions';
$_TABLES['evlist_calendars']    = $_DB_table_prefix . 'evlist_calendars';
$_TABLES['evlist_categories']   = $_DB_table_prefix . 'evlist_categories';
$_TABLES['evlist_detail']       = $_DB_table_prefix . 'evlist_detail';
$_TABLES['evlist_lookup']       = $_DB_table_prefix . 'evlist_lookup';
$_TABLES['evlist_remlookup']    = $_DB_table_prefix . 'evlist_remlookup';
$_TABLES['evlist_repeat']       = $_DB_table_prefix . 'evlist_repeat';
$_TABLES['evlist_tickettypes']  = $_DB_table_prefix . 'evlist_tickettypes';
$_TABLES['evlist_tickets']      = $_DB_table_prefix . 'evlist_tickets';
$_TABLES['evlist_cache']        = $_DB_table_prefix . 'evlist_cache';

// Deprecated tables, but needed to do the upgrade
$_TABLES['evlist_dateformat']   = $_DB_table_prefix . 'evlist_dateformat';
$_TABLES['evlist_timeformat']   = $_DB_table_prefix . 'evlist_timeformat';
$_TABLES['evlist_rsvp']         = $_DB_table_prefix . 'evlist_rsvp';

/** Define base path to plugin */
define('EVLIST_PI_PATH', "{$_CONF['path']}plugins/{$_EV_CONF['pi_name']}");
/** Define base URL to plugin */
define('EVLIST_URL', "{$_CONF['site_url']}/{$_EV_CONF['pi_name']}");
/** Define URL to plugin admin interface */
define('EVLIST_ADMIN_URL',
        "{$_CONF['site_admin_url']}/plugins/{$_EV_CONF['pi_name']}");

define('EV_RECUR_ONETIME',  0);     // Non-recurring
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

