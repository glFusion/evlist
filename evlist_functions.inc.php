<?php
/**
 * Plugin-specific functions for the EvList plugin
 *
 * @author      Lee P. Garner <lee@leegarner.com
 * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2010 - 2018 Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2007 Alford Deeley <ajdeeley AT sumitpages.ca>
 * @package     evlist
 * @version     v1.4.5
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | evlist_functions.inc.php                                                 |
// |                                                                          |
// | Misc. plugin-specific functions                                          |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008 by the following authors:                             |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
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
 * Get the Google-style page navigation for the list display.
 *
 * @param   integer $numrows    Total number of rows
 * @param   integer $cat        Category ID (optional)
 * @param   integer $page       Current page number
 * @param   integer $range      Range being displayed (upcoming, past, etc)
 * @param   integer $cal        ID of calendar being shown
 * @return  string          HTML for page navigation
 */
function EVLIST_pagenav($numrows, $cat=0, $page = 0, $range = 0, $cal = 0)
{
    global $_TABLES, $_EV_CONF;

    $cat = (int)$cat;
    $range = (int)$range;
    $cal = (int)$cal;
    $limit = (int)$_EV_CONF['limit_list'];
    $retval = '';
    if ($limit < 1) return $retval;

    $base_url = EVLIST_URL.
        "/index.php?cat=$cat&amp;cal=$cal&amp;range=$range&amp;view=list";
    if ($numrows > $limit) {
        $numpages = ceil($numrows / $limit);
        $retval = COM_printPageNavigation($base_url, $page, $numpages);
    }
    return $retval;
}


/**
 * Get an array of option lists for year, month, day, etc.
 *
 * @param   string  $prefix     Prefix to use for ampm variable name
 * @param   string  $curtime    SQL-formatted time to use as default
 * @return  array               Array of option lists, indexed by type
 */
function EVLIST_TimeSelect($prefix, $curtime = '')
{
    global $_CONF;

    // Use "now" as the default if nothing else sent.  Also helps make sure
    // that the explode() function works right.
    if (empty($curtime)) {
        $curtime = $_CONF['_now']->format('H:i:s', true);
    }
    list($hour, $minute, $second) = explode(':', $curtime);

    // Set up the time if we're using 12-hour mode
    if ($_CONF['hour_mode'] == 12) {
        $ampm = $hour < 12 ? 'am' : 'pm';
        if ($hour == 0)
            $hour = 12;
        elseif ($hour > 12)
            $hour -= 12;
    }

    $hourselect     = COM_getHourFormOptions($hour, $_CONF['hour_mode']);
    $minuteselect   = COM_getMinuteFormOptions($minute, 15);

    // This function gets the entire selection, not just the <option> parts,
    // so we use $prefix to create the variable name.
    $ampm_select    = COM_getAmPmFormSelection($prefix . '_ampm', $ampm);

    return array(
            'hour'      => $hourselect,
            'minute'    => $minuteselect,
            'ampm'      => $ampm_select
    );
}


/**
 * Convert the hour from 12-hour time to 24-hour.
 * This is meant to convert incoming values from forms to 24-hour format. If
 * the site uses 24-hour time, the form values should already be that way
 * (and there will be no am/pm indicator), so the hour is returned unchanged.
 *
 * @param   integer $hour   Hour to check (0 - 23)
 * @param   string  $ampm   Either 'am' or 'pm'
 * @return  integer         Hour after switching it to 24-hour time.
 */
function EVLIST_12to24($hour, $ampm='')
{
    return Evlist\DateFunc::conv12to24($hour, $ampm);
    /*
    global $_CONF;

    $hour = (int)$hour;

    if ($hour < 0 || $hour > 23) $hour = 0;
    if ($_CONF['hour_mode'] == 24) return $hour;

    if ($ampm == 'am' && $hour == 12) $hour = 0;
    if ($ampm == 'pm' && $hour < 12) $hour += 12;

    return $hour;
     */
}


/**
 * Get the 12-hour string from a 24-hour time value.
 * The returned value is actually the 24-hour format depending on $_CONF.
 * Used to set the times for the event entry form. Time displays are controlled
 * by the global locale configuration.
 *
 * @param   string  $time_str   24-hour time string, e.g. "14:30"
 * @return  string      12-hour string, e.g. "02:30 PM"
 */
function EVLIST_24to12($time_str)
{
    return Evlist\DateFunc::conv24to12($time_str);

    /*global $_CONF;

    if ($_CONF['hour_mode'] == '12') {
        return date("h:i A", strtotime($time_str));
    } else {
        return $time_str;
    }*/
}


/**
 * Get the day names for a week based on week start day of Sun or Mon.
 * Used to create calendar headers for weekly, monthly and yearly views.
 *
 * @param   integer $letters    Optional number of letters to return
 * @return  array       Array of day names for a week, 0-indexed
 */
function EVLIST_getDayNames($letters = 0)
{
    return Evlist\DateFunc::getDayNames($letters);
    /*
    global $_CONF, $LANG_WEEK;

    $retval = array();

    if ($_CONF['week_start'] == 'Sun') {
        $keys = array(1, 2, 3, 4, 5, 6, 7);
    } else {
        $keys = array(2, 3, 4, 5, 6, 7, 1);
    }

    for ($i = 0; $i < 7; $i++) {
        if ($letters > 0) {
            $retval[$i] = substr($LANG_WEEK[$keys[$i]], 0, $letters);
        } else {
            $retval[$i] = $LANG_WEEK[$keys[$i]];
        }
    }
    return $retval;
     */
}

?>
