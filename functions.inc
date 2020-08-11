<?php
/**
 * glFusion API functions for the EvList plugin.
 *
 * @author      Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2010 - 2018 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.5
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | functions.inc                                                            |
// |                                                                          |
// | glFusion Plugin API functions and support functions                      |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008-2010 by the following authors:                        |
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

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** Initialize the configuration */
require_once $_CONF['path_system'] . 'classes/config.class.php';
$ev_config = config::get_instance();
$_EV_CONF = $ev_config->get_config('evlist');

require_once __DIR__ . '/evlist.php';

// Some variables are used all over, so get them once to avoid a lot of
// function calls
$_EV_CONF['_now'] = new Date('now', $_CONF['timezone']);
$_EV_CONF['_today'] = $_EV_CONF['_now']->format('Y-m-d', true);
$_EV_CONF['_today_ts'] = $_EV_CONF['_now']->toUnix();

// Check the version of the locator plugin and reset the use_locator
// config value if it's inadequate.
if (isset($_EV_CONF) && $_EV_CONF['use_locator'] == 1) {
    if (!isset($_PLUGIN_INFO['locator']) ||
        !COM_checkVersion($_PLUGIN_INFO['locator']['pi_version'], $_EV_CONF['min_locator_ver'])) {
        $_EV_CONF['use_locator'] = 0;
    }
}

// Local configuration overrides can be set by having a config.php file.
if (file_exists(__DIR__ . '/config.php')) {
    /** Include local configuration overrides */
    include_once __DIR__ . '/config.php';
}

// Override plugin config if login is required globally
if ($_CONF['loginrequired'] == 1) {
    $_EV_CONF['allow_anon_view'] = '0';
}

// Include the user's language file, if found.  Default to englsh.
$langpath = __DIR__ . '/language';
$langfile = $_CONF['language'] . '.php';
if (!is_file("$langpath/$langfile")) {
    $langfile = 'english.php';
}
require_once "$langpath/$langfile";
if (is_file("$langpath/custom/$langfile")) {
    /** Include the custom language file, if any */
    include_once "$langpath/custom/$langfile";
}

// Define values for whether RSVP can be per repeat or only for a whole event
// This is basically ignored for one-time events.
define('EV_RSVP_EVENT', 1);
define('EV_RSVP_REPEAT', 2);

// macros to make the code easier to read
define('EVCHECKED', 'checked="checked"');
define('EVSELECTED', 'selected="selected"');

// define date format for Pear Date_Calc as common SQL format.
// also defined in lglib/functions.inc starting with version 0.0.7
if (!defined('DATE_CALC_FORMAT')) {
    define('DATE_CALC_FORMAT', 'Y-m-d');
}

// The services file only has functions for event signups and payments.
// No need to include if RSVP is disabled.
if ($_EV_CONF['enable_rsvp']) {
    include_once __DIR__ . '/services.inc.php';
}

spl_autoload_register(function ($class)
{
    // project-specific namespace prefix
    $prefix = 'Evlist\\';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/classes/';

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

use \Evlist\DateFunc;

/**
 * Return the current code (not necessarily installed) version.
 *
 * @return  string  Current version
 */
function plugin_chkVersion_evlist()
{
    global $_EV_CONF;
    return $_EV_CONF['pi_version'];
}


/**
 * Automatic plugin removal.
 *
 * @return  array   Array of data items to be removed
 */
function plugin_autouninstall_evlist()
{
    $out = array (
        'tables'    => array ('evlist_calendars', 'evlist_categories',
                        'evlist_detail', 'evlist_events',
                        'evlist_lookup','evlist_remlookup',
                        'evlist_repeat', 'evlist_submissions',
                        'evlist_tickets', 'evlist_tickettypes',
                        'evlist_cache',
        ),
        'groups'    => array('evlist Admin'),
        'features'  => array('evlist.admin', 'evlist.submit'),

        'php_blocks' => array('phpblock_evlist_upcoming',
                        'phpblock_evlist_smallmonth'),
    );
    Evlist\Cache::clear();
    return $out;
}


/**
 * Upgrade the plugin.
 *
 * @param   boolean $ignore_errors  True to ignore errors (dvlpupdate)
 * @return  mixed   True for success, False or error message number otherwise
 */
function plugin_upgrade_evlist($ignore_errors=false)
{
    if (@file_exists(__DIR__ . '/upgrade.php')) {
        require_once __DIR__ . '/upgrade.php';
        return evlist_upgrade($ignore_errors);
    } else {
        // upgrades are done by the install script - return a generic error
        COM_errorLog("evList plugin upgrade function not implemented");
        return 3001;
    }
}


/**
 * Get the icon and link for this plugin's Command and Control entry.
 *
 * @return  array   Array of items
 */
function plugin_cclabel_evlist()
{
    global $LANG_EVLIST;

    if (plugin_isadmin_evlist()) {
        return array(
                $LANG_EVLIST['pi_title'],
                EVLIST_ADMIN_URL . '/index.php',
                plugin_geticon_evlist(),
        );
    } else {
        return false;
    }
}


/**
 * Get this plugin's link for the Admins Only menu.
 *
 * @return  array   Array of name, link, no. of events
 */
function plugin_getadminoption_evlist()
{
    global $_TABLES, $LANG_EVLIST;

    if (plugin_isadmin_evlist()) {
        $numevents = DB_count($_TABLES['evlist_events']);
        return array(
                $LANG_EVLIST['pi_title'],
                EVLIST_ADMIN_URL . '/index.php',
                $numevents
        );
    } else {
        return false;
    }
}


/**
 * Get the menu entry for the user option menu.
 *
 * @return  mixed   Array of menu parts, or NULL
 */
function plugin_getuseroption_evlist()
{
    global $_EV_CONF, $LANG_EVLIST, $_TABLES, $_USER;

    $retval = array();

    $add_option = array($LANG_EVLIST['add_event'],
                EVLIST_URL . '/event.php?edit=x', 0);

    switch ($_EV_CONF['can_add']) {
    case 0:     // only admins can add events
        if (plugin_ismoderator_evlist()) {
            $retval[] = $add_option;
        }
        break;
    case 1:     // logged-in users can add events
        if (!COM_isAnonUser()) {
            $retval[] = $add_option;
        }
        break;
    case 2:     // logged-in + anonymous users can add
        $retval[] = $add_option;
        break;
    }

    if (!COM_isAnonUser() &&
        DB_count($_TABLES['evlist_events'], 'owner_id', $_USER['uid']) > 0) {
        $retval[] = array('My Events', EVLIST_URL . '/index.php?view=myevents');
    }

    return $retval;
}


/**
 * Get the menu entry for the main plugins menu.
 *
 * @return  array   Menu entry items
 */
function plugin_getmenuitems_evlist()
{
    global $_EV_CONF, $LANG_EVLIST;

    $menuitems = array();
    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1') {
        return $menuitems;
    }

    if ($_EV_CONF['enable_menuitem'] == '1') {
        $icon = '&nbsp;' . Evlist\Icon::gethTML('calendar');
        $menuitems[$LANG_EVLIST['pi_title'] . $icon] = EVLIST_URL . '/index.php';
    }
    return $menuitems;
}


/**
 * Show plugin statistics on the user profile page.
 * if $showsitestats is 1 then we are to only print the overall stats in the 'site
 * statistics' box otherwise we show the detailed stats for the plugin
 *
 * @param   boolean $showsitestats  Not used
 * @return  string      HTML for stats section
 */
function plugin_showstats_evlist($showsitestats)
{
    global $_CONF, $_EV_CONF, $_TABLES, $LANG_EVLIST;

    $retval = '';

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
        return false;
    }

    if (plugin_isadmin_evlist()) {
        $permSQL = '';
    } else {
        $permSQL = COM_getPermSQL('AND');
    }

    $result = DB_query("SELECT ev.id, det.title, ev.hits
            FROM {$_TABLES['evlist_events']} ev
            LEFT JOIN {$_TABLES['evlist_detail']} det
                ON ev.id = det.ev_id
            WHERE ev.hits > 0 $permSQL
            ORDER BY ev.hits DESC LIMIT 10");
    $nrows  = DB_numRows ($result);
    if ($nrows > 0) {
        USES_lib_admin();
        $header_arr = array(
            array('text' => $LANG_EVLIST['event_title'],
                    'field' => 'eid',
                    'header_class' => 'stats-header-title',
            ),
            array(  'text' => $LANG_EVLIST['hits'],
                    'field' => 'hits',
                    'header_class' => 'stats-header-count',
                    'field_class' => 'stats-list-count',
            ),
        );
        $data_arr = array();
        $text_arr = array('has_menu'     => false,
                          'title'        => $LANG_EVLIST['top_ten'],
        );
        for ($i = 0; $i < $nrows; $i++) {
            $A = DB_fetchArray ($result);
            $A['title'] = COM_stripslashes($A['title']);
            $A['eid'] = '<a href="' .
                    COM_buildUrl(EVLIST_URL . '/event.php?eid=' . $A['id']) .
                    '">' . $A['title'] . '</a>';
            $A['hits'] = COM_NumberFormat($A['hits']);
            $data_arr[$i] = $A;
        }
        $retval .= ADMIN_simpleList("", $header_arr, $text_arr, $data_arr);
    } else {
        $retval .= COM_startBlock($LANG_EVLIST['top_ten']);
        $retval .= $LANG_EVLIST['no_events_viewable'];
        $retval .= COM_endBlock();
    }
    return $retval;
}


/**
 * Get summary stats for the user profile page.
 *
 * @return  array   Array of text=>number
 */
function plugin_statssummary_evlist()
{
    global $_EV_CONF, $_TABLES, $LANG_EVLIST;

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
        return false;
    }

    if (plugin_isadmin_evlist()) {
        $permSQL = '';
    } else {
        $permSQL = COM_getPermSQL('AND');
    }

    $result = DB_query("SELECT COUNT(*) AS numrows
                FROM {$_TABLES['evlist_events']}
                WHERE status='1' $permSQL");
    list ($numrows) = DB_fetchArray($result);

    return array($LANG_EVLIST['events'], COM_numberFormat($numrows));
}


/**
 * Return the name to show in the advanced search as the "type".
 *
 * @return  string      String to display in the search type selection
 */
function plugin_searchtypes_evlist()
{
    global $_EV_CONF, $LANG_EVLIST;

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
        return '';
    }

    return array('evlist' => $LANG_EVLIST['pi_title']);
}


/**
 * Set up the search for this plugin.
 *
 * @param   string  $query      Search query string
 * @param   string  $datestart  Starting date for search
 * @param   string  $dateend    Ending date for search
 * @param   string  $topic      Topic selected for search
 * @param   string  $type       Item type
 * @param   string  $author     Limit results to this author (not used)
 * @param   string  $keyType    Type selected during search
 * @param   integer $page       Page number
 * @param   integer $perpage    Number of results per page
 * @return  object              Search object to be processed by Search class
 */
function plugin_dopluginsearch_evlist($query, $datestart, $dateend,
    $topic, $type, $author, $keyType, $page, $perpage
) {
    global $_CONF, $_EV_CONF,$_TABLES;

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
        return NULL;
    }

    $query = DB_escapeString(htmlspecialchars(trim($query)));
    $permSQL= COM_getPermSql('AND', 0, 2, 'e') . COM_getPermSql('AND', 0, 2, 'c');

    $url = '/evlist/event.php?view=event&eid=';
    $q = '&query=' . urlencode($query);
    $sql = "SELECT e.id, d.title, d.summary as description,
                UNIX_TIMESTAMP(e.date_start1) AS date,
                e.hits, e.owner_id AS uid, gl.username,
                CONCAT('$url',e.id,'$q') AS url
            FROM {$_TABLES['evlist_detail']} d
            LEFT JOIN {$_TABLES['evlist_events']} e
                ON d.ev_id = e.id
            LEFT JOIN {$_TABLES['evlist_calendars']} c
                ON c.cal_id = e.cal_id
            LEFT JOIN {$_TABLES['users']} gl
                ON e.owner_id = gl.uid
            WHERE (e.status='1')
                $permSQL ";

    if (!empty($author))
        $sql .= "AND (e.owner_id = '$author') ";

    $search = new SearchCriteria('evlist', 'evList');
    $columns = array('d.title', 'd.summary', 'd.full_description',
                    'd.location', 'd.contact');
    $sql .= $search->getDateRangeSQL('AND', 'UNIX_TIMESTAMP(e.date_start1)',
                    $datestart, $dateend);
    list($sql,$ftsql) = $search->buildSearchSQL($keyType, $query,
                    $columns, $sql);
    $sql .= ' GROUP BY d.det_id ';
    $search->setSQL($sql);
    $search->setFTSQL($ftsql);
    $search->setRank(5);

    return $search;
}


/**
 * Handle autotags.
 *
 * @param   string  $op         Requested operation (tagname or parse)
 * @param   string  $content    HTML content
 * @param   string  $autotag    Contents of autotag
 * @return  string              Modified HTML $content
 */
function plugin_autotags_evlist($op, $content = '', $autotag = '')
{
    global $_CONF, $_TABLES;

    if ($op == 'tagname') {
        return 'event';
    } elseif ($op == 'parse') {
        $eid = COM_applyFilter($autotag['parm1']);
        $url = COM_buildURL(EVLIST_URL . '/event.php?eid=' . $eid);
        if (empty($autotag['parm2'])) {
            $text = COM_stripslashes(
                DB_getItem($_TABLES['evlist_detail'], 'title', "ev_id='$eid'")
            );
        } else {
            $text = $autotag['parm2'];
        }
        $link = '<a href="' . $url . '">' . $text . '</a>';
        $content = str_replace($autotag['tagstr'], $link, $content);

        return $content;
    }
}


/**
 * Get plugin-specific javascript.
 *
 * @return  array   Array of javascript paths
 */
function plugin_getheaderjs_evlist()
{
    global $_CONF;

    $path = $_CONF['path_html'] . '/evlist/js';
    $files = array(
        $path . '/toggleEnabled.js',
    );
    return $files;
}


/**
 * Get plugin-specific CSS files.
 * Look first in the layout directory, then in the plugin's custom directory,
 * and finally take the default for the plugin.
 *
 * @return  array   Array of file paths.
 */
function plugin_getheadercss_evlist()
{
    global $_CONF;

    // CSS paths are under public_html so they are available to
    // the "print" templates that don't load lib-common.php
    $pi_path = $_CONF['path_html'] . 'evlist/css/';
    $styles = array();
    $files = array(
        'style.css',
        'picker.css',
        'responsive.css',
    );
    // For each style, load the included plugin stylesheet, then
    // custom overrides and layout-specific overrides in that order
    foreach ($files as $file) {
        // Add the default styles
        $styles[] = $pi_path . $file;
        // Plugin customization overrides default
        if (@file_exists($pi_path. 'custom/' . $file)) {
            $styles[] = $pi_path . 'custom/' . $file;
        }
        // Theme customization trumps all
        if (@file_exists($_CONF['path_layout'].'plugins/evlist/'. $file)) {
            $styles[] = $_CONF['path_layout'].'plugins/evlist/' . $file;
        }
    }
    return $styles;
}


/**
 * Create the centerblock.
 *
 * @uses    EVLIST_cb_content()
 * @param   string  $where  Position (top, etc.)
 * @param   string  $page   Page currently being displayed
 * @param   string  $topic  Topic currently being displayed
 * @return  string          HTML for centerblock
 */
function plugin_centerblock_evlist($where, $page, $topic = '')
{
    global $_EV_CONF, $_CONF, $_USER, $_TABLES, $LANG_EVLIST;

    if ($_EV_CONF['pos_centerblock'] != $where ||
        (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')) {
        return '';
    }

    // If we show only on the homepage, check if that's where we are
    // If a topic is being displayed, then we're not on the homepage
    if ($_EV_CONF['topic_centerblock'] == 'home' &&
            ($page > 1 || !empty($topic)) ) {
        return '';
    }

    if ($_EV_CONF['topic_centerblock'] != 'all') {
        // display on topic page or not at all
        if (!empty($topic) && $_EV_CONF['topic_centerblock'] != $topic) {
            return '';
        }
    }

    // Try first to get from cache
    $cache_key = 'evlistcb_' . $_EV_CONF['enable_centerblock'];
    $display = Evlist\Cache::get($cache_key);

    if (empty($display)) {  // not found in cache
        // overloading the previously-boolean enable_centerblock option to
        // indicate the centerblock format.
        switch ($_EV_CONF['enable_centerblock']) {
        case 1:     // table format
        case 2:     // story format
            $display = EVLIST_cb_content($_EV_CONF['enable_centerblock']);
            break;
        case 3:     // calendar format
            $view = Evlist\View::getView('month');
            $display = $view->Render();
            break;
        case 0:     // disabled
            default:
            $display = '';
        }
        Evlist\Cache::set($cache_key, $display, 'evlistcb');
    }
    if (!empty($display)) {
        if ($where == 0) {      // replacing home page
            $display = EVLIST_siteHeader() . $display . EVLIST_siteFooter();
        }
    }
    return $display;
}


/**
 * Get the content for a table- or story-formatted centerblock.
 *
 * @param   integer $format     Centerblock type, 1=table,2=story
 * @return  string      HTML for centerblock section
 */
function EVLIST_cb_content($format)
{
    global $_EV_CONF, $_CONF, $_USER, $_TABLES, $LANG_EVLIST;

    $retval = '';
    // Retrieve Centerblock Settings
    $range    = $_EV_CONF['range_centerblock'];
    $limit    = (int)$_EV_CONF['limit_block'];
    $length   = $_EV_CONF['limit_summary'];
    $_dt = clone($_CONF['_now']);
    $interval = (int)$_EV_CONF['cb_max_days'];
    if ($interval > 0) {
        $cb_max_date = $_dt->add(new \DateInterval("P{$interval}D"))
            ->toMySQL(true);
    } else {
        // no limit by days.
        $cb_max_date = '9999-12-31';
    }

    $opts = array(
        'limit' => $limit,
        'show_upcoming' => 1,
    );

    switch ($format) {
    case 1:     // table format
        $dup_chk = 'rp_id';
        $tpl_file = 'centerblock.thtml';
        break;
    case 2:     // story format
        $dup_chk = 'rp_ev_id';
        $tpl_file = 'cblock_stories.thtml';
        break;
    default:
        return '';
    }

    $Y = $_CONF['_now']->format('Y');
    $D = $_CONF['_now']->format('d');
    $M = $_CONF['_now']->format('m');
    switch ($_EV_CONF['range_centerblock']) {
    case 1:         // past events
        $start = date('Y-m-d', strtotime("{$_EV_CONF['_today']} - 1 month"));
        $end = date('Y-m-d', strtotime("{$_EV_CONF['_today']} - 1 day"));
        $limit = 0;     // special, we need to get all events since we can't count back
        $opts['order'] = 'DESC';
        break;
    case 2:         // upcoming events
    default:
        $opts['upcoming'] = true;
        $start = $_EV_CONF['_today'];
        $end = EV_MAX_DATE;
        break;
    case 3:         // this week
        $start = DateFunc::beginOfWeek($D, $M, $Y);
        $end = DateFunc::endOfWeek($D, $M, $Y);
        break;
    case 4:         // upcoming month
        $start = DateFunc::beginOfMonth($M, $Y);
        $end = DateFunc::dateFormat(DateFunc::daysInMonth($M, $Y), $M, $Y);
        break;
    }

    $events = EVLIST_getEvents($start, $end, $opts);
    if (empty($events) || !is_array($events)) {
        return '';
    }

    // Special handling needed to get the latest X past events.  We have a bunch
    // from the query (to make sure we got enough).  Now pick out the last X.
    if ($_EV_CONF['range_centerblock'] == 1) {
        $limit = (int)$_EV_CONF['limit_block'];     // Need this value again
        $events = array_splice($events, ($limit * -1), $limit);
    }

    // Find all the autotags that need to be stripped from the summary.
    $tmp = PLG_collectTags();
    $patterns = array();
    if (is_array($tmp)) {
        foreach ($tmp as $tag=>$plugin) {
            $patterns[] = '/\[' . $tag . ':.*\]/';
        }
    }

    $T = new Template(EVLIST_PI_PATH . '/templates');
    $T->set_file('centerblock', $tpl_file);

    $T->set_var(array(
        'startblock' => COM_startBlock($LANG_EVLIST['ranges'][$range]),
        'endblock' => COM_endBlock(),
        'title_label' => $LANG_EVLIST['event_title'],
        'date_label' => $LANG_EVLIST['start_date'],
    ) );
    $cssid = 0;

    $T->set_block('centerblock', 'eventRow', 'eRow');
    $rp_shown = array();    // Array to hold repeat id's to avoid dups
    $count = 0;
    $T->set_var('adblock_0', PLG_displayAdBlock('evlist_centerblock', 0));
    foreach ($events as $date=>$day) {
        if ($date > $_cb_max_date) {
            // Reached the maximum date to show
            break;
        }
        if ($date == '_empty_') continue;
        foreach ($day as $A) {
            // Don't display birthdays as story items
            if ($format == 2 && $A['cal_id'] == -2) continue;

            // Make sure we only show each event once for multiday
            if (array_key_exists($A[$dup_chk], $rp_shown))
                continue;

            // Now increment and check the counter.
            $count++;
            if ($count > $limit) break;

            $rp_shown[$A[$dup_chk]] = 1;

            // Prepare the summary for display. Remove links and autotags
            $summary = empty($A['summary']) ? $A['title'] : $A['summary'];
            $summary = strip_tags($summary, '<a>');
            $summary = preg_replace($patterns, '', $summary);

            if (!empty($length) && $length >= 1) {
                if (strlen($summary) > $length) {
                    $summary = substr($summary, 0, $length);
                    $summary = $summary . '...';
                }
            }
            $s_ts1 = strtotime($A['rp_start']);
            $e_ts1 = strtotime($A['rp_end']);
            $email = isset($A['email']) ? EVLIST_obfuscate($A['email']) : '';
            $cssid = ($cssid == 1) ? 2: 1;

            if (isset($A['postmode']) && $A['postmode'] != 'plaintext') {
                $full_dscp = PLG_replaceTags($A['full_description']);
            } else {
                $full_dscp = $A['full_description'];
            }

            $T->set_var(array(
                'cssid'     => $cssid,
                'is_birthday' => $A['cal_id'] == -2 ? true : false,
                'eid'       => $A['rp_id'],
                'pi_url'    => EVLIST_URL,
                'title'     => $A['title'],
                'summary'   => $summary,
                'full_dscp' => $full_dscp,
                'contact'   => isset($A['contact']) ? $A['contact'] : '',
                'location'  => isset($A['location']) ? $A['location'] : '',
                'ev_url'    => isset($A['url']) ? $A['url'] : '',
                'street'    => isset($A['street']) ? $A['street'] : '',
                'city'      => isset($A['city']) ? $A['city'] : '',
                'province'  => isset($A['province']) ? $A['province'] : '',
                'country'   => isset($A['country']) ? $A['country'] : '',
                'email'     => $email,
                'phone'     => isset($A['phone']) ? $A['phone'] : '',
                'startdate' => EVLIST_formattedDate($s_ts1),
                'enddate'   => EVLIST_formattedDate($e_ts1),
                'starttime1' => EVLIST_formattedTime($s_ts1),
                'endtime1'  => EVLIST_formattedTime($e_ts1),
                'adblock'   => PLG_displayAdBlock('evlist_centerblock', $count),
            ) );
            if (isset($A['split']) && $A['split'] == 1) {
                $s_ts2 = strtotime($A['rp_date_start'] . ' ' . $A['rp_time_start2']);
                $e_ts2= strtotime($A['rp_date_end'] . ' ' . $A['rp_time_end2']);
                $T->set_var(array(
                    'starttime2' => EVLIST_formattedTime($s_ts2),
                    'endtime2' => EVLIST_formattedTime($e_ts2),
                ) );
            } else {
                $T->set_var(array(
                    'starttime2' => '',
                    'endtime2' => '',
                ) );
            }
            //$T->parse('eventrow', 'item', true);
            $T->parse('eRow', 'eventRow', true);
        }
    }

    $T->parse('output', 'centerblock');
    $retval .= $T->finish($T->get_var('output'));
    return $retval;
}


/**
 * Change ownership of user events when the user is deleted.
 *
 * @param   integer $uid    ID of user being deleted
 */
function plugin_user_delete_evlist($uid)
{
    global $_TABLES;

    DB_query("UPDATE {$_TABLES['evlist_events']} SET
        owner_id = '2', status = '0'
        WHERE owner_id = '$uid'");
}


/**
 * Support merging local accounts into remote accounts.
 *
 * @param   integer $origUID    Original (remote) user ID
 * @param   integer $destUID    Merged (local) user ID
 */
function plugin_user_move_evlist($origUID, $destUID)
{
    global $_TABLES;

    $origUID = (int)$origUID;
    $destUID = (int)$destUID;

    DB_query("UPDATE {$_TABLES['evlist_events']}
            SET owner_id = $destUID WHERE owner_id = $origUID", 1);
    DB_query("UPDATE {$_TABLES['evlist_submissions']}
            SET owner_id = $destUID WHERE owner_id = $origUID", 1);
    DB_query("UPDATE {$_TABLES['evlist_remlookup']}
            SET uid = $destUID WHERE uid = $origUID", 1);
    DB_query("UPDATE {$_TABLES['evlist_tickets']}
            SET uid = $destUID WHERE uid = $origUID", 1);
}


/**
 * Get the plugin's icon
 *
 * @return  string  URL to icon image
 */
function plugin_geticon_evlist()
{
    return EVLIST_URL . '/images/evList.png';
}


/**
 * Get the names of RSS feeds that are provided.
 * For evList this is a list of topics
 *
 * @return  array   Array of ID=>Name pairs
 */
function plugin_getfeednames_evlist()
{
    global $_TABLES, $LANG_EVLIST;

    $feeds = array(
        // Always include "All" as an option
        array('id' => '0', 'name' => $LANG_EVLIST['all_upcoming']),
    );
    $result = DB_query("SELECT id, name
            FROM {$_TABLES['evlist_categories']}
            WHERE status = 1");
    while ($A = DB_fetchArray($result, false)) {
        $feeds[] = array('id' => $A['id'], 'name' => $A['name']);
    }

    return $feeds;
}


/**
 * Get content for the syndication feeds.
 *
 * @param   string  $feed       Feed ID to get
 * @param   string  $link       Pointer to header link value
 * @param   array   $update_data    Pointer to array of updated item IDs
 * @param   string  $feedType   Feed type (RSS, ICS, etc.) We only do ICS.
 * @return  array               Array of event data
 */
function plugin_getfeedcontent_evlist($feed, &$link, &$update_data, $feedType)
{
    global $_CONF, $_EV_CONF, $_TABLES, $LANG_EVLIST;

    $content = array();
    $lids = array();
    $eids = array();

    // Feeds are not authenticated, so anonymous uers must be
    // allowed to view events
    if ($_EV_CONF['allow_anon_view'] != 1) {
        return $content;
    }

    $result = DB_query("SELECT topic,limits,content_length
            FROM {$_TABLES['syndication']}
            WHERE fid = $feed");
    $F = DB_fetchArray($result, false);

    // Set a sane limit on the events retrieved to avoid OOM errors
    $limit = (int)$F['limits'];
    if ($limit > 500) $limit = 500;

    // Get all upcoming events
    $events = EVLIST_getEvents(
        $_EV_CONF['_today'],
        date('Y-m-d', strtotime('+1 year', $_EV_CONF['_today_ts'])),
        array('cat'=>$F['topic'], 'limit'=>$limit)
    );

    $rp_shown = array();    // Store repeat ids to avoid dups
    foreach ($events as $daydata) {
        foreach ($daydata as $event) {
            // Check if this event has an earlier date, or if it has
            // already been included.  Could happen with multi-day events.
            if ($event['rp_date_start'] < $_EV_CONF['_today'] ||
                    array_key_exists($event['rp_id'], $rp_shown)) {
                continue;
            }
            $rp_shown[$event['rp_id']] = 1;

            $url = EVLIST_URL . '/event.php?eid=' . urlencode($event['rp_id']);
            $postmode = $event['postmode'] == '2' ? 'html' : 'plaintext';
            if( $event['postmode'] == '1' ) {       //plaintext
                $event['summary'] = nl2br($event['summary']);
            }

            // Track the event IDs that we're actually including
            $eids[] = $event['rp_id'];
            /*if ($event['postmode'] != 'plaintext') {
                $summary = PLG_replaceTags(COM_stripslashes($event['summary']));
            } else {*/
                //$summary = ($event['summary']);
            //}
            $content[] = array(
                'title'     => $event['title'],
                'summary'   => $event['summary'],
                'description' => $event['full_description'],
                'link'      => $url,
                'url'       => $url,
                'date'      => strtotime($event['rp_date_start'].' '.$event['rp_time_start1']),
                'dtstart'   => date("Ymd\THi00",strtotime($event['rp_date_start'] . $event['time_start1'])),
                'dtend'     => date("Ymd\THi00",strtotime($event['rp_date_end'] . $event['time_end1'])),
                'format'    => $postmode,
                'location'  => $event['location'],
                'categories' => isset($event['cat_name']) ? $event['cat_name'] : '',
            );
        }
    }

    $view = isset($_EV_CONF['default_view']) &&
            !empty($_EV_CONF['default_view']) ?
            $_EV_CONF['default_view'] : 'list';
    if ($F['topic'] == 0) {
        $link = EVLIST_URL . '/index.php?view=' . $view;
    } else {
        $link = EVLIST_URL . "/index.php?view={$view}&amp;cat={$F['topic']}";
    }

    if (count($eids) > 0 ) {
        $update_data = implode (',', $eids);
    }

    return $content;
}


/**
 * Checks to see if the RSS feed is up-to-date.
 *
 * @param   integer $feed   Feed ID from the RSS configuration
 * @param   integer $topic  Topic ID being requested
 * @param   string  $update_data    Comma-separated string of current item IDs
 * @param   integer $limit  Configured limit on item count for this feed
 * @return  boolean         True if feed needs updating, False otherwise
 */
function plugin_feedupdatecheck_evlist($feed, $topic, $update_data, $limit)
{
    global $_EV_CONF;;

    $eids = array();

    // Set a sane limit on the events retrieved to avoid OOM errors
    $limit = (int)$limit;
    if ($limit > 500) $limit = 500;

    $sql = EVLIST_getEvents_sql(
        $_EV_CONF['_today'],
        date('Y-m-d', strtotime('+1 year', $_EV_CONF['_today_ts'])),
        array('cat'=>$topic, 'limit'=>$limit, 'select'=>'rep.rp_id')
    );
    $result = DB_query($sql, 1);
    while ($A = DB_fetchArray($result, false)) {
        $eids[] = $A['rp_id'];
    }
    $current = implode (',', $eids);

    return ($current != $update_data) ? false : true;
}


/**
 * Run scheduled tasks for the plugin.
 * - Send event reminders
 */
function plugin_runScheduledTask_evlist()
{
    if (!EVLIST_isCurrentVersion()) return;

    $Rems = Evlist\Reminder::getCurrent();
    foreach ($Rems as $R) {
        $R->Send();
        $R->Delete();
    }
    return true;
}


/**
 * Get the number of submissions awaiting moderation.
 *
 * @return  integer     Number of submissions in queue
 */
function plugin_submissioncount_evlist()
{
    global $_TABLES;

    return DB_count($_TABLES['evlist_submissions']);
}


/**
 * Additional activity when a submission is approved.
 * The event data will have been copied from the submission table already.
 *
 * @param   string  $eid    Event ID being approved
 */
function plugin_moderationapprove_evlist($eid)
{
    global $_TABLES;

    // Update the event with status=1
    $result = DB_query("UPDATE {$_TABLES['evlist_events']}
            SET status = '1'
            WHERE id = '$eid'");

    // The glFusion core already copies the event information to the event
    // table, and the Detail table was already in place.  This is wasteful,
    // but now we're going to read that data back and let the Event class
    // create the recurrences.
    $Ev = new Evlist\Event($eid);
    $Ev->UpdateRepeats();

    return true;
}


/**
 * Additional activity when a submission is deleted by the moderator.
 * The record will be deleted from the submission table by the core.
 * Repeats haven't been created yet.
 *
 * @param   string  $eid    Event ID being deleted
 */
function plugin_moderationdelete_evlist($eid)
{
    global $_TABLES;

    // Delete from the category lookup
    DB_delete($_TABLES['evlist_lookup'], 'eid', $eid);
    // Delete the detail portion of the event
    DB_delete($_TABLES['evlist_detail'], 'ev_id', $eid);
}


/**
 * Get the values to move from submissions to main table upon approval.
 *
 * @return  array   Array of (key field, main table, data fields, submission table)
 */
function plugin_moderationvalues_evlist()
{
    global $_TABLES;

    return array(
        'id',
        $_TABLES['evlist_events'],
        'id, date_start1, date_end1,
        time_start1, time_end1, time_start2, time_end2,
        recurring, rec_data, allday, split,
        status, postmode, hits, enable_reminders, enable_comments,
        owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon,
        det_id, show_upcoming, cal_id, options, tzid',
        $_TABLES['evlist_submissions']
    );
}


/**
 * Determine if the current user has the "submit" privilege.
 *
 * @see     SEC_hasRights()
 * @uses    plugin_ismoderator_evlist()
 * @return  boolean     True if the user has access, False if not
 */
function plugin_issubmitter_evlist()
{
    static $retval = NULL;
    if ($retval === NULL) {
        if (plugin_ismoderator_evlist()) {
            // moderators automatically have submit access
            $retval = true;
        } else {
            $retval = SEC_hasRights('evlist.submit');
        }
    }
    return $retval;
}


/**
 * Determine if the current user is a plugin admin.
 *
 * @uses    plugin_ismoderator_evlist()
 * @return  boolean     True if the current user is an admin, False if not
 */
function plugin_isadmin_evlist()
{
    static $isadmin = NULL;
    if ($isadmin === NULL) {
        $isadmin = SEC_hasRights('evlist.admin');
    }
    return $isadmin;
}


/**
 * Determine if the current user is a plugin moderator.
 * Currently the permission is the same, so just call isadmin function.
 *
 * @uses    plugin_isadmin_evlist()
 */
function plugin_ismoderator_evlist()
{
    return plugin_isadmin_evlist();
}


/**
 * Get a list of items to be moderated.
 *
 * @return  object  Plugin object containg item data
 */
function plugin_itemlist_evlist()
{
    global $_TABLES, $LANG_EVLIST;

    if (plugin_ismoderator_evlist()) {
        $plugin = new Plugin();
        $plugin->submissionlabel = $LANG_EVLIST['moderation_title'];
        $plugin->getsubmissionssql = "SELECT
                                ev.id, det.title, ev.date_start1,
                                ev.time_start1, ev.owner_id as uid
                            FROM {$_TABLES['evlist_submissions']} ev
                            LEFT JOIN {$_TABLES['evlist_detail']} det
                            ON det.det_id = ev.det_id
                            ORDER BY ev.date_start1 ASC";
        $plugin->addsubmissionheading($LANG_EVLIST['title']);
        $plugin->addsubmissionheading($LANG_EVLIST['start_date']);
        $plugin->addsubmissionheading($LANG_EVLIST['start_time']);
        return $plugin;
    }
}


/**
 * Perform actions needed when the plubin is enabled or disabled.
 * Enables or disables syndication, and turns off PHP blocks if disabling.
 * Blocks must be turned on manually.
 *
 * @param   boolean $enable True if plugin is now enabled, False if disabled.
 */
function plugin_enablestatechange_evlist($enable)
{
    global $_TABLES, $_PLUGINS;

    $is_enabled = $enable ? 1 : 0;

    // If trying to enable the plugin but lgLib is not available,
    // take no action and return
    if ($is_enabled && !in_array('lglib', $_PLUGINS)) {
        // Re-disable the plugin
        DB_change($_TABLES['plugins'], 'pi_enabled', 0, 'pi_name', 'evlist');
        $enable = false;
        $is_enabled = 0;
    }

    // toggle evlist feeds
    DB_change($_TABLES['syndication'], 'is_enabled', $is_enabled, 'type', 'evlist');

    // turns block off if on, but will not turn it on if off.
    if ($is_enabled != 1) {
        DB_query ("UPDATE {$_TABLES['blocks']}
                    SET is_enabled = $is_enabled
                    WHERE (type = 'phpblock')
                    AND (phpblockfn LIKE 'phpblock_evlist_%')");
    }
    return $enable;
}


/**
 * Receives notification of anothe plugin's deletion.
 *
 * @param   mixed   $id     Item ID
 * @param   string  $type   Plugin Name
 */
function plugin_itemdeleted_evlist($id, $type)
{
    global $_TABLES;

    // If the lgLib plugin is disabled or removed, disable thie plugin
    if ($type == 'lglib' && $id == '*') {
        plugin_enablestatechange_evlist(false);
        DB_change($_TABLES['plugins'], 'pi_enabled', 0, 'pi_name', 'evlist');
    }
    if ($id == '*') {
        // TODO: Delete all calendar items for a plugin?
    } else {
        // Delete a single event related to a plugin item that was deleted
        $ev_id = "_{$type}_{$id}";
        Evlist\Event::Delete($ev_id);
    }
}


/**
 * Get a list (actually an array) of all groups this group belongs to.
 *
 * @param   integer $basegroup   id of group
 * @return  array   Array of all groups 'basegroup' belongs to
 */
function EV_getGroupList($basegroup)
{
    global $_TABLES;

    $to_check = array ();
    array_push ($to_check, $basegroup);

    $checked = array ();

    while (sizeof ($to_check) > 0) {
        $thisgroup = array_pop ($to_check);
        if ($thisgroup > 0) {
            $result = DB_query ("SELECT ug_grp_id FROM {$_TABLES['group_assignments']} WHERE ug_main_grp_id = $thisgroup");
            $numGroups = DB_numRows ($result);
            for ($i = 0; $i < $numGroups; $i++) {
                $A = DB_fetchArray ($result);
                if (!in_array ($A['ug_grp_id'], $checked)) {
                    if (!in_array ($A['ug_grp_id'], $to_check)) {
                        array_push ($to_check, $A['ug_grp_id']);
                    }
                }
            }
            $checked[] = $thisgroup;
        }
    }

    return $checked;
}


/**
 * Get the links to categories related to a specific event.
 *
 * @param   string  $eid    Event ID
 * @param   string  $andrange   Range to pass wo url TODO: required?
 * @return  string      HTML for category inls
 */
function EVLIST_getCatLinks($eid, $andrange = '')
{
    global $_TABLES, $_CONF;

    $sql = "SELECT tc.id, tc.name
            FROM {$_TABLES['evlist_categories']} tc
            LEFT JOIN {$_TABLES['evlist_lookup']} tl
                ON tc.id = tl.cid
            WHERE tl.eid = '".DB_escapeString($eid)."'
            AND tl.status = '1'";
    //echo $sql;die;
    $cresult = DB_query($sql);
    $catcount = DB_numRows($cresult);
    if ($catcount > 0) {
        $catlinks = array();
        while ($C = DB_fetchArray($cresult, false)) {
            $catname = str_replace(' ', '&nbsp;', $C['name']);
            $catlinks[] = '<a href="' .
                COM_buildURL(EVLIST_URL . '/index.php?view=list' . $andrange .
                '&amp;cat=' . $C['id']) . '">' . COM_stripslashes($catname) .
                '</a>';
        }
        $retval = implode('&nbsp;|&nbsp;', $catlinks);
    } else {
        $retval = '';
    }
    return $retval;

}


/**
 * Returns a formatted date given a timestamp.
 * Converts the timestamp parameter to an actual timestamp if non-numeric
 *
 * @param   mixed   $timestamp  Timestamp to return as formatted.
 * @return  string      Formatted date
 */
function EVLIST_formattedDate($timestamp)
{
    global $_CONF;

    if (empty($timestamp)) {
        $timestamp = time();
    }
    if (!is_numeric($timestamp)) {
        $retval = date($_CONF['shortdate'], strtotime($timestamp));
    } else {
        $retval = date($_CONF['shortdate'], $timestamp);
    }

    return $retval;
}


/**
 * Returns a formatted time given a timestamp.
 * Converts the timestamp parameter to an actual timestamp if non-numeric
 * Uses the global time format configuration
 *
 * @param   mixed   $timestamp  Timestamp to return as formatted.
 * @return  string      Formatted date
 */
function EVLIST_formattedTime($timestamp)
{
    global $_CONF, $_EV_CONF;

    if (empty($timestamp)) {
        $timestamp = time();
    }
    if (!is_numeric($timestamp)) {
        $retval = date($_CONF['timeonly'],
                strtotime($_EV_CONF['_today'] . $timestamp));
    } else {
        $retval = date($_CONF['timeonly'], $timestamp);
    }
    return $retval;
}


/**
 * Create an obfuscated version of a string for display.
 *
 * @param   string  $str    String to obfuscate
 * @return  string          Obfuscated versiona
 */
function EVLIST_obfuscate($str)
{
    $retval = '';
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $retval .= '&#x' . bin2hex($str[$i]) . ';';
    }
    return $retval;
}


/**
 * Create the "Upcoming Events" block.
 * Some things are hard-coded and should be moved to configuation items:
 * - The string length for displayed items, currently 96
 *
 * @param  array   $A      Array of block info
 * @param  string  $params  Parameters, e.g. 'limit:3 cat:2 cal:5'
 * @return string  HTML for the block, empty if no qualifying events
 */
function phpblock_evlist_upcoming($A='', $params='')
{
    global $_EV_CONF, $_CONF;

    $retval = '';

    if ( !EVLIST_isCurrentVersion() &&
        (!isset($_EV_CONF['devel']) || $_EV_CONF['devel'] == 0) ) {
        // if not the current version, and not in development mode, return
        return $retval;
    }

    //length to be set from admin panels sometime later, or not.
    $length = '96';

    // Max period to include (days)
    $max_days = (int)$_EV_CONF['max_upcoming_days'];

    // Set the start & ending periods.
    $dt = new Date($_EV_CONF['_today_ts'] + (86400 * $max_days));
    $end = $dt->format('Y-m-d', true);

    // Figure out the selected category & calendar.  Only one supported now,
    // defaults to category.
    $cal = 0;
    $cat = 0;
    $limit = (int)$_EV_CONF['limit_block'];
    if (is_numeric($params)) {
        // backward compatibility, default to only the category.
        $cat = (int)$params;
    } else {
        $parms = explode(' ', $params);
        foreach ($parms as $part) {
            $parts = explode(':', $part);
            foreach ($parts as $key=>$val) {
                switch ($key) {
                case 'cat':
                case 'cal':
                case 'limit':
                    $$key = (int)$val;
                    break;
                }
            }
        }
    }

    $opts = array(
        'cat' => $cat,
        'cal' => $cal,
        'show_upcoming' => 1,
        'limit' => $limit,
        'upcoming' => true,
    );
    $events = EVLIST_getEvents($_EV_CONF['_today'], $end, $opts);
    if (empty($events)) {
        return $retval;
    }

    $T = new Template(EVLIST_PI_PATH . '/templates');
    $T->set_file('block','phpblock.thtml');
    $listlink = COM_buildURL(EVLIST_URL . '/index.php');

    $count = 0;
    $rp_shown = array();    // Store repeat ids to avoid dups
    foreach ($events as $ev_dt=>$daydata) {
        // Get each event by day into $daydata, then each event for the
        // day goes into $ev
        $startdate = EVLIST_formattedDate(strtotime($ev_dt));
        $T->set_block('block', 'blkDay', 'bDay');
        $daycount = 0;      // Count events shown each day.

        foreach ($daydata as $ev_id=>$ev) {
            // Check that this event should be shown as upcoming at all, and
            // only show multi-day events once.
            if ($ev['show_upcoming'] != 1 ||
                array_key_exists($ev['rp_id'], $rp_shown)) {
                continue;
            }
            $rp_shown[$ev['rp_id']] = 1;

            // TODO: displaying summaries in the block will be set from the
            // admin panels sometime later.
            $summary = COM_truncate(htmlspecialchars(strip_tags(EVLIST_noquotes($ev['summary']))), $length, '...');
            $title = htmlspecialchars($ev['title']);
            $tooltip = $title;
            if (!empty($summary)) {
                $tooltip .= '::' . $summary;
            }
            $T->set_block('block', 'blkItem', 'bItem');
            if ($ev['cal_id'] < 0) {
                $url = $ev['url'];
                $url_target = '_blank';
            } else {
                $url =  $_CONF['site_url'] . "/evlist/event.php?eid={$ev['rp_id']}";
                $url_target = '';
            }
            $T->set_var(array(
                'rp_id'     => $ev['rp_id'],
                'title'     => $title,
                'summary'   => $summary,
                'full_description' => $ev['full_description'],
                'tooltip'   => $tooltip,
                'url'       => $url,
                'url_target' => $url_target,
            ) );
            $T->parse('bItem','blkItem', true);
            $count++;
            $daycount++;

            // Only $limit events were retrieved from Evlist, but if Meetup
            // events are added the total may exceed the count. Break here
            // to close out the day view, then check in the outer loop to
            // stop displaying anything further.
            if ($count > $limit) break;
        }
        if ($daycount > 0) {
            $T->set_var('start_date', $startdate);
            $T->parse('bDay', 'blkDay', true);
            $T->clear_var('bItem');
        }
        if ($count >= $limit) break;
    }
    if ($count > 0) {
        // Add the block footer only if at least one event is shown.  Otherwise
        // we want to return the empty $retval.
        $T->set_var(array(
            'listlink'      => $listlink,
            'pi_icon'       => plugin_geticon_evlist(),
        ) );
        $T->parse('output','bItem');
        $T->parse('output', 'block');
        $retval .= $T->finish($T->get_var('output'));
    }
    return $retval;
}


/**
 * Helper function: Provide topic dropdown for plugin config.
 *
 * @return  array   Array of topic_name => topic_id pairs
 */
function plugin_configmanager_select_topic_centerblock_evlist()
{

    global $_TABLES,$LANG_EVLIST;

    $topics = array();

    // Topic options not in the DB
    $topics[$LANG_EVLIST['topic_all']] = 'all';
    $topics[$LANG_EVLIST['topic_home']] = 'home';

    // Check topic access if not an admin
    if ( plugin_isadmin_evlist() ) {
        $permSQL = '';
    } else {
        $permSQL = COM_getPermSQL('WHERE');
    }

    $result = DB_query("SELECT tid, topic
                FROM {$_TABLES['topics']}
                $permSQL");
    while ($A = DB_fetchArray($result, false)) {
        $tid = COM_stripslashes($A['tid']);
        $topic = COM_stripslashes($A['topic']);
        $topics[$topic] = $tid;
    }
    return $topics;
}


/**
 * Select the calendar to use for plugin-supplied events.
 *
 * @return  array   $cal_id => $cal_name
 */
function plugin_configmanager_select_pi_cal_map_evlist()
{
    $A = array();
    foreach (\Evlist\Calendar::getAll() as $cal) {
        $A[$cal->getName()] = $cal->getID();
    }
    return $A;
}


/**
 * Check to see if this Plugin uses comments.
 *
 * @return  boolean true or false
 */
function plugin_commentsupport_evlist()
{
    global $_EV_CONF;
    return (int)$_EV_CONF['commentsupport'] == 1 ? true : false;
}


/**
 * Save a comment.
 * Saves the comment, updates the comment counter in the ad record,
 * and notifies the ad owner that a new comment has been posted.
 *
 * @param   string  $title  comment title
 * @param   string  $comment comment text
 * @param   string  $id     Item id to which $comment belongs
 * @param   int     $pid    comment parent
 * @param   string  $postmode 'html' or 'text'
 * @return  mixed   false for failure, HTML string (redirect?) for success
 */
function plugin_savecomment_evlist($title,$comment,$id,$pid,$postmode)
{
    global $LANG03;

    if (!plugin_commentsupport_evlist())
        return;

    $title = strip_tags($title);
    $pid = COM_applyFilter($pid, true);
    $postmode = COM_applyFilter($postmode);

    $ret = CMT_saveComment($title, $comment, $id, $pid,
            'evlist', $postmode);

    if ($ret > 0) {         // failure, return to comment form
        return EVLIST_siteHeader()
            . CMT_commentform($title, $comment, $id, $pid,
                    'evlist', $LANG03[14], $postmode)
            . EVLIST_siteFooter();
    }
    return (COM_refresh(EVLIST_URL . '/event.php?eid='.$id));
}


/**
 * Plugin API to delete a comment.
 * Moderators and event owners can delete comments.
 *
 * @param   integer $cid    Comment to be deleted
 * @param   string  $id     Item id to which $cid belongs
 * @return  mixed   false for failure, HTML string (redirect?) for success
 */
function plugin_deletecomment_evlist($cid,$id)
{
    //USES_class_evlist_repeat();
    $Rp = new Evlist\Repeat($id);
    if ($Rp->isNew()) return false;   // invalid item

    if (plugin_ismoderator_evlist() || $Rp->Event->isOwner()) {
        if (CMT_deleteComment($cid, $id, 'evlist') == 0) {
            // Redirect back to the event view
            return (COM_refresh(EVLIST_URL . '/event.php?eid='  . $id));
        } else {
            return false;
        }
    } else {
        return false;
    }
}


/**
 * Get the plugin base URL and item ID key.
 *
 * @return  array   (url, 'eid')
 */
function plugin_getcommenturlid_evlist() {
    $retval = array(
        EVLIST_URL . '/event.php',
        'eid',
    );
    return $retval;
}


/**
 * How the plugin will display specific comments.
 *
 * @param   string  $id     Unique idenifier for item comment belongs to
 * @param   int     $cid    Comment id to display
 * @param   string  $title  Page/comment title
 * @param   string  $order  'ASC' or 'DSC' or blank
 * @param   string  $format 'threaded', 'nested', or 'flat'
 * @param   int     $page   Page number of comments to display
 * @param   boolean $view   True to view comment (by cid), false to display (by $pid)
 * @return  mixed   results of calling the plugin_displaycomment_ function
 */
function plugin_displaycomment_evlist($id, $cid, $title, $order, $format, $page, $view)
{
    global $LANG_ACCESS, $_USER;

    if (!plugin_commentsupport_evlist()) {
        return '';
    }

    $Rp = new Evlist\Repeat($id);
    if (!$Rp->isNew()) {
        $delete_option = (
            plugin_ismoderator_evlist() ||
            $Rp->getEvent()->getOwnerID() == $_USER['uid']
        );
        $retval = CMT_userComments(
            $id, $title, 'evlist', $order, $format,
            $cid, $page, $view, $delete_option,
            plugin_commentsupport_evlist()
        );
    } else {
        $retval = COM_startBlock(
            $LANG_ACCESS['accessdenied'], '',
            COM_getBlockTemplate ('_msg_block', 'header')
        )
        . $LANG_ACCESS['storydenialmsg']
        . COM_endBlock(COM_getBlockTemplate ('_msg_block', 'footer'));
    }
    return $retval;
}


/**
 * Return a link to the help element for this configuration option
 *
 * @param   string  $element    Element being looked up
 * @param   string  $doclang    Language
 * @return  array       Array (URL, window option)
 */
function plugin_getconfigelementhelp_evlist($element, $doclang )
{
    $file = 'config.html';
    $doc_url = EVLIST_getDocURL($file, $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }
    return array($doc_url, 0);
}


/**
 * Get the link to a documentation file.
 * Returns the URL to the file, or to the directory if no file requested.
 *
 * @param   string  $file       HTML file
 * @param   string  $doclang    Documentation language
 * @return  string              URL to document directory or file
 */
function EVLIST_getDocURL($file='', $doclang='english')
{
    global $_CONF;

    $doc_path = $_CONF['path_html'] . "/evlist/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        if (!file_exists($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = EVLIST_URL . "/docs/{$doclang}/{$file}";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = EVLIST_URL . "/docs/$doclang/";
    }
    return $doc_url;
}


/**
 * Get the site header, with or without left blocks according to config.
 *
 * @see     COM_siteHeader()
 * @param   string  $title  Optional text for the title bar
 * @param   string  $meta   Optional additional meta text
 * @return  string          HTML for site header
 */
function EVLIST_siteHeader($title='', $meta='')
{
    global $_EV_CONF;

    $retval = '';

    switch( $_EV_CONF['displayblocks'] ) {
        case 1 :    // right only
        case 3 :    // none
            $retval .= COM_siteHeader('none', $title, $meta);
            break;
        case 0 :    // left only
        case 2 :    // both
        default :
            $retval .= COM_siteHeader('menu', $title, $meta);
            break;
    }
    return $retval;
}


/**
 * Get the site footer, with or without right blocks according to config.
 *
 * @see     COM_siteFooter()
 * @return  string      HTML for site footer
 */
function EVLIST_siteFooter()
{
    global $_CONF, $_EV_CONF;

    $retval = '';

    switch( $_EV_CONF['displayblocks'] ) {
        case 1 :    // right only
        case 2 :    // left and right
            $retval .= COM_siteFooter( true );
            break;
        case 0 :    // left only
        case 3 :    // none
        default :
            $retval .= COM_siteFooter();
            break;
    }
    return $retval;
}


/**
 * Display a small month calendar in a block.
 * Optional parameters allow for custom blocks to be created that display
 * only one or a subset of calendar data
 *
 * @uses   Evlist\Views\smallmonthView
 * @param  array   $blk    Optional block data
 * @param  string  $cal    Optional calendar ID
 * @return string      HTML for the block contents
 */
function phpblock_evlist_smallmonth($blk=array(), $cal=0)
{
    global $_EV_CONF;

    // PHP block calendar is always current, regardless of session vars
    list($y, $m, $d) = explode('-', $_EV_CONF['_today']);
    $Cal = Evlist\View::getView('smallmonth', $y, $m, $d, 0, $cal);
    return $Cal->Render();
}


/**
 * Loads the language array.
 *
 * If $requested is an array, the first valid language file is loaded.
 * If not, the $requested language file is loaded.
 * If $requested doesn't refer to a vailid language, then $_CONF['language']
 * is assumed.
 *
 * After loading the base language file, the same filename is loaded from
 * language/custom, if available.  The admin can override language strings
 * by creating a language file in that directory.
 *
 * By including the language file, this function reads the $LANG_EVLIST
 * array. However, since it is not declared as global this does not override
 * the global version.  This version is returned to be used in a different
 * variable name.
 *
 * @param   mixed   $requested  A single or array of language strings
 * @return  array       $LANG_EVLIST, the global language array for the plugin
 */
function plugin_loadlanguage_evlist($requested='')
{
    global $_CONF;

    $languages = array();

    // Add the requested language, which may be an array or
    // a single item.
    if (is_array($requested)) {
        $languages = $requested;
    } elseif ($requested != '') {
        $languages[] = $requested;
    }

    // Add the site language as a failsafe
    if (!in_array($_CONF['language'], $languages)) {
        $languages[] = $_CONF['language'];
    }

    // Search the array for desired language files, in order.
    $langpath = __DIR__ . '/language';
    foreach ($languages as $language) {
        if (file_exists("$langpath/$language.php")) {
            include "$langpath/$language.php";
            // Include admin-supplied overrides, if any.
            if (file_exists("$langpath/custom/$language.php")) {
                include "$langpath/custom/$language.php";
            }
            break;      // only load the first language found.
        }
    }

    return $LANG_EVLIST;
}


/**
 * Provides the getItemInfo function for this plugin.
 *
 * @param   string  $id         Product ID
 * @param   string  $what       Comma-separated list of fields to return
 * @param   integer $uid        User ID
 * @param   array   $options    Not used
 * @return  array           Array of product information
 */
function plugin_getiteminfo_evlist($id, $what, $uid=0, $options=array())
{
    global $_TABLES, $_CONF;

    $properties = explode(',', $what);
    $fields = array();
    $retval = array();
    $buildingIndex = false;
    foreach ($properties as $p) {
        switch ($p) {
        case 'id':
            $fields[] = 'e.id';
            break;
        case 'title':
            $fields[] = 'd.title';
            break;
        case 'excerpt':
        case 'introtext':
        case 'description':
            $fields[] = 'd.summary';
        case 'bodytext':
            $fields[] = 'd.full_description';
            break;
        case 'searchidx':
        case 'content':
            $fields[] = 'd.title';
            $fields[] = 'd.summary';
            $fields[] = 'd.full_description';
            break;
        case 'date':
            $fields[] = 'UNIX_TIMESTAMP(r.rp_start) AS unixtime';
            break;
        case 'search_index':
            $fields[] = "group_concat(' ', d.title) as idx_title";
            $fields[] = "group_concat(' ', d.summary) as idx_summary";
            $fields[] = "group_concat(' ', d.full_description) as idx_dscp";
            $fields[] = "group_concat(' ', d.location) as idx_location";
            $buildingIndex = true;
            break;
        case 'perms':
            $fields[] = 'e.owner_id';
            $fields[] = 'e.group_id';
            $fields[] = 'e.perm_owner';
            $fields[] = 'e.perm_group';
            $fields[] = 'e.perm_members';
            $fields[] = 'e.perm_anon';
            break;
        }
    }
    if (count($fields) == 0) {
        return $retval;
    }
    $fields = array_unique($fields);
    $fields = implode(',', $fields);
    $retval = array();
    $sql = "SELECT $fields
            FROM {$_TABLES['evlist_events']} e
            LEFT JOIN {$_TABLES['evlist_detail']} d
                ON d.ev_id = e.id
            LEFT JOIN {$_TABLES['evlist_repeat']} r
                ON r.rp_ev_id = e.id
            LEFT JOIN {$_TABLES['evlist_calendars']} c
                ON c.cal_id = e.cal_id";
    if ($id == '*') {
        $where = ' WHERE 1 = 1 ';
    } else {
        $where = " WHERE (e.id = '" . DB_escapeString($id) . "') ";
    }
    $where .= ' AND e.status = 1 AND c.cal_status = 1 ';
    if ($uid > 0) {
        $permsql = COM_getPermSql('AND', $uid, 2, 'e')
                 . COM_getPermSql('AND', $uid, 2, 'c');
    } else {
        $permsql = COM_getPermSql('AND', 0, 2, 'e') . COM_getPermSql('AND', 0, 2, 'c');
    }
    $sql .= $where . ' ' . $permsql . ' GROUP BY e.id';
    //echo $sql;die;
    $res = DB_query($sql);

    $props = array();
    while ($A = DB_fetchArray($res, false)) {
        foreach ($properties as $p) {
            switch ($p) {
            case 'title':
                if ($buildingIndex) {
                    $props[$p] = $A['idx_title'];
                } else {
                    $props[$p] = $A['title'];
                }
                break;
            case 'introtext':
                $props[$p] = $A['summary'];
                break;
            case 'bodytext':
                $props[$p] = $A['full_description'];
                break;
            case 'searchidx':
            case 'content':
                if ($buildingIndex) {
                    $props[$p] = $A['idx_title'] . ' ' . $A['idx_summary'] . ' ' . $A['idx_dscp'];
                } else {
                    $props[$p] = $A['title'] . ' ' .
                        $A['summary'] . ' ' . $A['full_description'];
                }
                break;
            case 'description':
                $props[$p] = $A['summary'] . ' ' . $A['full_description'];
                break;
            case 'date':
                $props[$p] = $A['unixtime'];
                break;
            case 'perms':
                $props['perms'] = array(
                    'owner_id' => $A['owner_id'],
                    'group_id' => $A['group_id'],
                    'perm_owner' => $A['perm_owner'],
                    'perm_group' => $A['perm_group'],
                    'perm_members' => $A['perm_members'],
                    'perm_anon' => $A['perm_anon'],
                );
                break;
            default:
                $props[$p] = isset($A[$p]) ? $A[$p] : NULL;
                break;
            }
        }
        //$props['url'] = COM_buildUrl(EVLIST_URL . '/event.php?eid=' . $A['id'], 0);
        $props['url'] = EVLIST_URL . '/event.php?view=event&eid=' . $A['id'];
        $mapped = array();
        foreach ($props as $key => $value) {
            if ($id == '*') {
                if ($value != '') {
                    $mapped[$key] = $value;
                }
            } else {
                $mapped[$key] = $value;
            }
        }

        if ($id == '*') {
            $retval[] = $mapped;
        } else {
            $retval = $mapped;
            break;
        }
    }

    if (($id != '*') && (count($retval) == 1)) {
        $tRet = array_values($retval);
        $retval = $tRet[0];
    }
    if ( $retval === '' || (is_array($retval) && count($retval) == 0 ) ) return NULL;
    return $retval;
}


/**
 * Return the template names that support ad blocks.
 */
function plugin_supportadblock_evlist()
{
    return array('evlist_event', 'evlist_centerblock');
}


/**
 * Get event information from other plugins when an item is saved.
 * Recurring, single-day events are supported.
 *
 * @param   string  $id     Item ID
 * @param   string  $type   Item Type
 * @param   string  $old_id Original ID in case of ID change
 */
function plugin_itemsaved_evlist($id, $type, $old_id = '')
{
    global $_EV_CONF;

    $info = PLG_getItemInfo(
        $type, $id,
        'id,is_event,date,year,month,day,title,summary,description,' .
        'owner_id,group_id,recur_type,recur_interval,url,' .
        'time_start,time_end,enable_comments,perms'
    );
    if (!is_array($info) || count($info) == 0) return;  // No data received
    if (!$info['is_event']) return;   // not an event
    if (!$info['title'])  {
        COM_errorLog(__FUNCTION__ . ": Event $id from $type must have a title");
        return;        // Must have a title
    }

    if (!$info['year']) {
        $info['year'] = $_EV_CONF['_now']->format('Y');
    }
    if ($info['date']) {
        $date = $info['date'];
    } else {
        if (!$info['month'] || !$info['day']) return;   // Need something for date
        $date = sprintf('%d-%02d-%02d', $info['year'], $info['month'], $info['day']);
    }
    $summary = $info['summary'] ? $info['summary'] : $info['title'];
    $full_dscp = $info['description'] ? $info['description'] : $summary;
    if ($info['group_id'] === NULL) $info['group_id'] = 13;
    if ($info['owner_id'] === NULL) $info['owner_id'] = 2;
    if ($info['perms'] === NULL || !is_array($info['perms'])) {
        $info['perms'] = array(
            'perm_owner'    => 3,
            'perm_group'    => 2,
            'perm_members'  => 0,
            'perm_anon'     => 0,
        );
        switch ($info['group_id']) {
        case 1:         // Anonymous (include Logged-In)
            $info['perms']['perm_anon'] = 2;
        case 13:        // Logged-In Users
            $info['perms']['perm_members'] = 2;
            break;
        }
    }
    // Set calendar id based on config, default to "1"
    $Cal = Evlist\Calendar::getMapped($type);

    $ev_id = "_{$type}_{$id}";      // Event ID is "_plugin_itemid"
    $Ev = Evlist\Event::getInstance($ev_id);
    // Set variables to simulate a form submission
    $A = array(
        'eid' => $ev_id,
        'owner_id' => $info['owner_id'] ? $info['owner_id'] : 2,
        'group_id' => $info['group_id'] !== NULL ? $info['group_id'] : 13,
        'cal_id' => $Cal->getID(),
        'title' => $info['title'],
        'summary' => $summary,
        'full_description' => $full_dscp,
        'url' => $info['url'] ? $info['url'] : '',
        'status' => '1',
        'enable_comments' => $info['enable_comments'] ? (int)$info['enable_comments'] : -1,
        'newcat' => '',
        'date_start1' => $date,
        'date_end1' => $date,
        'tz_local' => 'on',
        'recurring' => $info['recur_type'] > 0 ? '1' : '0',
        'format' => $info['recur_type'] !== NULL ? $info['recur_type'] : 0,
        'stopdate' => '',
        'skipnext' => '0',
        'weekday' => '1',
        'rec_freq' => $info['recur_interval'],
        'show_upcoming' => '1',
        'use_rsvp' => '0',
        'perm_owner' => array ( 0 => $info['perms']['perm_owner'] ),
        'perm_group' => array ( 0 => $info['perms']['perm_group'] ),
        'perm_members' => array ( 0 => $info['perms']['perm_members'] ),
        'perm_anon' => array ( 0 => $info['perms']['perm_anon'] ),
    );

    if ($info['time_start'] && $info['time_end']) {
        // This is a timed event if both start and end times are given.
        // Convert to the same 12-hour variables as the submisison form.
        $A['allday'] = 0;
        list($s_hour, $s_min) = explode(':', $info['time_start']);
        list($e_hour, $e_min) = explode(':', $info['time_end']);
        if ($s_hour > 11) {
            $s_hour -= 12;
            $s_ampm = 'pm';
        } else {
            $s_ampm = 'am';
        }
        if ($e_hour > 11) {
            $e_hour -= 12;
            $e_ampm = 'pm';
        } else {
            $e_ampm = 'am';
        }
        $A['starthour1'] = $s_hour;
        $A['startminute1'] = $s_min;
        $A['start_ampm'] = $s_ampm;
        $A['endhour1'] = $e_hour;
        $A['endminute1'] = $e_min;
        $A['end_ampm'] = $e_ampm;
        $A['time_start1'] = $info['time_start'];
        $A['time_end1'] = $info['time_end'];
    } else {
        // No times specified = all-day event
        $A['allday'] = 1;
    }
    $Ev->Save($A, false);
}


/**
 * Check that the currently installed version matches the code version.
 * This is to disable functions that might not work due to a mismatch.
 * Just check the major.minor.revision values.  Any fourth element is ignored.
 *
 * @return  boolean     True if current == installed version, False if not
 */
function EVLIST_isCurrentVersion()
{
    global $_EV_CONF, $_TABLES;

    static $isCurrent = -1;     // static var to minimize DB calls

    if ($isCurrent === -1) {
        $installed = DB_getItem($_TABLES['plugins'], 'pi_version',
                "pi_name = 'evlist'");
        $isCurrent = COM_checkVersion($installed, $_EV_CONF['pi_version']);
    }
    return $isCurrent;
}


/**
 * Create the SQL query to get all events that fall within a range.
 *
 * The $opts array parameter is used to restrict the result set.
 * The options currently available are:
 *      'cat'   int     Limit to this category ID
 *      'cal'   int     Limit to this calendar ID
 *      'show_upcoming' boolean     Whether this flag is set in the event
 *      'limit' int     Max number of results
 *      'page'  int     Page number, used in the limit clause if > 1
 *
 * @param  string  $start  Starting date, YYYY-MM-DD
 * @param  string  $end    Ending date, YYYY-MM-DD
 * @param  array   $opts   Array of other options (name=>value)
 * @return string          SQL query to retrieve events
 */
function EVLIST_getEvents_sql($start='', $end='', $opts=array())
{
    global $_TABLES, $_EV_CONF, $_CONF, $_USER;

    if ($start == '') $start = $_EV_CONF['_today'];
    if ($end == '') $end = $start;

    list($y, $m, $d) = explode('-', $start);
    if (!DateFunc::isValidDate($d, $m, $y)) $start = $_EV_CONF['_today'];
    list($y, $m, $d) = explode('-', $end);
    if (!DateFunc::isValidDate($d, $m, $y)) $end = $start;
    $start = DB_escapeString($start . ' 00:00:00');
    $end = DB_escapeString($end . ' 23:59:59');

    // Set up other search options.
    $selection = '';
    $opt_select = '';
    $opt_join = '';
    $opt_where = '';
    $opt_order = 'ASC';
    $orderby = 'rep.rp_start';
    $grp_by = 'rep.rp_id';
    $limit = 0;
    $page = 0;
    $cat_status = '';
    $cat_join = '';
    //$cat_status = ' AND (cat.status = 1 OR cat.status IS NULL)';
    // default date range for fixed calendars, "upcoming" may be different
    $dt_sql = "rep.rp_start <= '$end' AND rep.rp_end >= '$start'";
    if (is_array($opts) && !empty($opts)) {
        foreach ($opts as $key => $value) {
            $value = DB_escapeString($value);
            switch ($key) {
            case 'cal':
                if ($value != 0)
                    $opt_where .= ' AND cal.cal_id = ' . (int)$value;
                break;
            case 'eid':
                $opt_where .= " AND ev.id = '$value'";
                break;
            case 'rp_id':
                $opt_where .= ' AND rep.rp_id = ' . (int)$value;
                break;
            case 'ical':
                $opt_where .= ' AND cal.cal_ena_ical = ' . ($value == 1 ? 1 : 0);
                break;
            case 'cat':
                if ($value > 0) {
                    //$opt_select .= ', cat.name AS cat_name';
                    $cat_status = " AND l.cid = '$value' AND cat.status = 1";
                    $cat_join = "LEFT JOIN {$_TABLES['evlist_lookup']} l ON l.eid = ev.id " .
                                "LEFT JOIN {$_TABLES['evlist_categories']} cat ON cat.id = l.cid ";
                }
                break;
            case 'limit':
                if ($value > 0)
                    $limit = (int)$value;
                break;
            case 'page':
                if ($value > 0)
                    $page = (int)$value;
                break;
            case 'order':
                $opt_order = strtoupper($value) == 'DESC' ? 'DESC' : 'ASC';
                break;
            case 'show_upcoming':
                // This allows for generic key-value pairs.  $value may
                // or may not be numeric.
                $opt_where .= " AND ev.{$key} = '$value'";
                break;
            case 'upcoming':
                // Alters the date range based on the setting for upcoming
                // events.
                switch ($_EV_CONF['event_passing']) {
                case 1:     // include if start time has not passed
                    $dt_sql = "rep.rp_start >= '" . $_EV_CONF['_now']->toMySQL(true) . "'";
                    break;
                case 2:     // include if start date has not passed
                    $dt_sql = "rep.rp_start >= '$start'";
                    break;
                case 3:     // include if end time has not passed
                    $dt_sql = "rep.rp_end >= '" . $_EV_CONF['_now']->toMySQL(true) . "'";
                    break;
                case 4:     // include if end date has not passed
                    $dt_sql = "rep.rp_end >= '{$_EV_CONF['_today']} 23:59:59'";
                    break;
                }
                // Always limit to events starting before the specified end date
                $dt_sql .= " AND rep.rp_start <= '$end'";
                break;
            case 'select':
                $selection = $value;
                break;
            case 'unique':
                $grp_by = 'ev.id';
                break;
            }
        }
    }

    // By default, get all fields that the caller could possibly want.  If
    // a selection option is specified, then that is used instead.  It's up
    // to the caller to request the value properly, including table prefix.
    if ($selection == '')
        $selection = "rep.*, det.*, cal.*, ev.* $opt_select";

    // All the "*" queries may be ineffecient, but we need to read all
    // fields that might be wanted by whoever calls this function
    $sql = "SELECT $selection
            FROM {$_TABLES['evlist_repeat']} rep
            LEFT JOIN {$_TABLES['evlist_events']} ev
                ON ev.id = rep.rp_ev_id
            LEFT JOIN {$_TABLES['evlist_detail']} det
                ON det.det_id = rep.rp_det_id
            LEFT JOIN {$_TABLES['evlist_calendars']} cal
                ON cal.cal_id = ev.cal_id
            $cat_join
            WHERE ev.status = 1
            AND ($dt_sql)
            AND (cal.cal_status = 1 OR cal.cal_status IS NULL)
            $cat_status " .
            COM_getPermSQL('AND', 0, 2, 'ev') . ' ' .
            COM_getPermSQL('AND', 0, 2, 'cal') .
            " $opt_where
            GROUP BY $grp_by ASC
            ORDER BY $orderby $opt_order";
            //ORDER BY rep.rp_date_start $opt_order,
            //        rep.rp_time_start1 $opt_order";
    if ($limit > 0) {
        if ($page > 1) {
            $sql .= ' LIMIT ' . (($page - 1) * $limit) . ',' . $limit;
        } else {
            // page 1 or 0, no starting offset
            $sql .= " LIMIT $limit";
        }
    }
    //echo $sql;die;
    return $sql;
}


/**
 * Get all events that fall within a range.
 * This is in functions.inc so it can be used by the feed update
 * functions without having to load evlist_functions.inc.php.
 *
 * The $opts array parameter is used to restrict the result set.
 * The options currently available are:
 *      'cat'   int     Limit to this category ID
 *      'cal'   int     Limit to this calendar ID
 *      'show_upcoming' boolean     Whether this flag is set in the event
 *      'limit' int     Max number of results
 *      'page'  int     Page number, used in the limit clause if > 1
 *
 * @param  string  $start  Starting date, YYYY-MM-DD
 * @param  string  $end    Ending date, YYYY-MM-DD
 * @param  array   $opts   Array of other options (name=>value)
 * @return array           Array of matching events, keyed by date
 */
function EVLIST_getEvents($start='', $end='', $opts=array())
{
    global $_EV_CONF, $_USER;

    $sql = EVLIST_getEvents_sql($start, $end, $opts);
    //echo $sql;die;
    $key = md5($sql);
    $events = Evlist\Cache::get($key);

    if (is_null($events)) {     // not found in cache, read from DB
        $events = array();
        $result = DB_query($sql, 1);
        if ($result && !DB_error()) {
            while ($A = DB_fetchArray($result, false))  {
                if (!isset($events[$A['rp_date_start']])) {
                    $events[$A['rp_date_start']] = array();
                }
                $A['options'] = @unserialize($A['options']);
                $A['rec_data'] = @unserialize($A['rec_data']);
                // Set a valid foreground and background color
                if (empty($A['fgcolor'])) $A['fgcolor'] = 'inherit';
                if (empty($A['bgcolor'])) $A['bgcolor'] = 'inherit';
                if ($A['rp_date_start'] == $A['rp_date_end']) {
                    // Single-day event just gets added to the array
                    $events[$A['rp_date_start']][] = $A;
                } else {
                    // Multi-day events get a record for each day up to the event end
                    // or limit, whichever comes first
                    $end_date = min($A['rp_date_end'], $end);
                    $newdate = max($A['rp_date_start'], $start);
                    while ($newdate <= $end_date) {
                        if (!isset($events[$newdate]))
                            $events[$newdate] = array();
                        $events[$newdate][] = $A;
                        list($y, $m, $d) = explode('-', $newdate);
                        $newdate = DateFunc::nextDay($d, $m, $y);
                    }
                }
            }   // while
        }
        Evlist\Cache::set($key, $events, 'events');
    }
    return $events;
}


/**
 * Replace quotes and apostrophes with HTML entities.
 * This is to prevent quotes from corrupting the HTML display while allowing
 * other HTML in event data to be displayed as intended.
 *
 * @param   string  $str    Original text
 * @return  string          Modified version
 */
function EVLIST_noquotes($str)
{
    return str_replace(array('"'), array('&quot;'), $str);
}


/**
 * Determine if the current user can submit an event.
 * Checks config and logged-in status.
 *
 * @return  boolean     True if subbmission is allowed, False if not
 */
function EVLIST_canSubmit()
{
    global $_EV_CONF;
    static $canSubmit = NULL;

    if ($canSubmit === NULL) {
        // Determine if the current user is allowed to add an event, and borrow
        // some space in $_EV_CONF to store a flag for other functions to use.
        if (($_EV_CONF['can_add'] & EV_ANON_CAN_ADD) == EV_ANON_CAN_ADD ||
            (!COM_isAnonUser() &&
            ($_EV_CONF['can_add'] & EV_USER_CAN_ADD) == EV_USER_CAN_ADD) ||
            plugin_ismoderator_evlist()) {
            $canSubmit = true;
        } else {
            $canSubmit = false;
        }
    }
    return $canSubmit;
}


// EVLIST_xxxReturn() functions simply set and read a session variable
// so the calling routines don't have to know what the designated variable
// name is.

/**
 * Set the return url to be used after an action is completed.
 *
 * @param   string  $key    URL or key to the URL array (see EVLIST_getReturn())
 */
function EVLIST_setReturn($key)
{
    SESS_setVar('evlist_fromurl', $key);
}


/**
 * Get the value of the return key, if set.
 * Used to check if a return URL has been set so other actions can
 * be taken if necessary.
 *
 * @return  mixed   URL, key, or zero if not set
 */
function EVLIST_checkReturn()
{
    return SESS_getVar('evlist_fromurl');
}


/**
 * Get the return URL set in the session variable.
 * Returns either the URL associated with a key or the actual URL, depending
 * on which is set. This function always returns a URL, so
 * EVLIST_checkReturn should be used if you need to check for the existance
 * of a return url.
 *
 * @return  string      URL associated with the key in the session var, or home
 */
function EVLIST_getReturn()
{
    $urls = array(
        'myevents'  => EVLIST_URL . '/index.php?view=myevents',
        'adminevents' => EVLIST_ADMIN_URL . '/index.php?view=events',
        'admincategories' => EVLIST_ADMIN_URL . '/index.php?view=categories',
        'admintickets' => EVLIST_ADMIN_URL . '/index.php?view=tickets',
        'admintickettypes' => EVLIST_ADMIN_URL . '/index.php?view=tickettypes',
        'list' => EVLIST_URL . '/index.php?view=list',
        'home'      => EVLIST_URL . '/index.php',
    );

    $from = EVLIST_checkReturn();
    if (isset($urls[$from])) {
        // A key value is set, return the associated URL
        return $urls[$from];
    } elseif ($from !== 0) {
        // A session var is set, return it (should be a URL)
        return $from;
    } else {
        // Nothing set in the session var, return the home URL
        return $urls['home'];
    }
}


/**
 * Get an icon from the CSS icon set, either UIkit or FontAwesome.
 *
 * @param   string  $name   Base name of icon, e.g. "edit" for "uk-icon-edit"
 * @param   string  $style  Style name from local CSS
 * @return  string      Class names for icon
 */
function EVLIST_getIcon($name, $style='')
{
    global $_EV_CONF;

    if ($name == '') return '';
    if ($style != '') {
        $style = ' ev-icon-' . $style;
    }
    return 'uk-icon-' . $name . $style;
}


/**
 * Get an array variable, first checking whether it's set.
 *
 * @param   array   $A          Array where var should be
 * @param   string  $varname    Name of array element
 * @param   string  $type       Type of return value
 * @param   mixed   $default    Default value, if any
 * @return      Array element or NULL, cast to $type
 */
function EV_getVar($A, $varname, $type = 'string', $default = NULL)
{
    if (!isset($A[$varname])) {
        return $default;
    } else {
        $val = $A[$varname];
    }
    switch ($type) {
    case 'int':
    case 'integer':
        return (int)$val;
        break;
    case 'bool':
        return (bool)$val;
        break;
    case 'float':
        return (float)$val;
        break;
    case 'array':
        if ($val === NULL) {
            $val = array();
        } elseif (!is_array($val)) {
            $val = array($val);
        }
        return $val;
        break;
    case 'string':
    default:
        return (string)$val;
        break;
    }
}


/**
 * Display a formatted error message.
 *
 * @param   string  $msg    Error message to display
 * @param   string  $type   Type of message, used for style and header
 * @param   string  $header Optional header text.
 * @return  string      Formatted error message
 */
function EVLIST_alertMessage($msg = '', $type = '', $header = '')
{
    global $LANG_EVLIST;

    // Require a valid message
    if ($msg == '')
        return '';
    return COM_showMessageText($msg, $header, true, $type);
}


/**
 * Get the options for a select list.
 * Similar to COM_optionList, but expects a value=>name array of
 * elements, which will typically be from a language array.
 *
 * @param   array   $options    value=>description array of elements
 * @param   mixed   $selected   Optional value to preselect
 * @param   integer $bias       Amount to add to each value
 * @return  string          HTML for <option></option> elements
 */
function EVLIST_GetOptions($options, $selected = '', $bias=0)
{
    if (!is_array($options)) return '';

    $retval = '';
    $bias = (int)$bias;

    foreach ($options as $value=>$name) {
        if (is_numeric($value)) $value += $bias;
        $retval .= '<option value="' . $value . '"';
        if ($value == $selected) {
                $retval .= ' selected="selected"';
        }
        $retval .= ">$name</option>" . LB;
    }
    return $retval;
}


/**
 * Get the RSS feed links only.
 *
 * @return  array   Array of links & titles
 */
function EVLIST_getFeedLinks()
{
    global $_EV_CONF, $_TABLES;

    $retval = array();

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != 1)
        return $retval;

    // Get the feed info for configured feeds
    $result = DB_query("SELECT title, filename
            FROM {$_TABLES['syndication']}
            WHERE type='" . DB_escapeString($_EV_CONF['pi_name']) . "'");

    if (DB_numRows($result) > 0) {
        $feed_url = SYND_getFeedUrl();
        while ($A = DB_fetchArray($result, false)) {
            $retval[] = array(
                'feed_title'   => $A['title'],
                'feed_url'     => $feed_url . $A['filename'],
            );
        }
    }
    return $retval;
}


/**
 * Get the feed subscription urls & icons.
 * This returns a ready-to-display set of icons for visitors
 * to subscribe to RSS feeds
 *
 * @return  string  HTML for icons
 */
function EVLIST_getFeedIcons()
{
    global $_CONF, $_EV_CONF, $_TABLES;

    $retval = '';

    // Anon access required for feed access anyway
    if ($_EV_CONF['allow_anon_view'] != 1)
        return $retval;

    // Get the feed info for configured feeds
    $result = DB_query("SELECT title, filename
            FROM {$_TABLES['syndication']}
            WHERE type='" . DB_escapeString($_EV_CONF['pi_name']) . "'");

    if (DB_numRows($result) > 0) {
        $T = new Template(EVLIST_PI_PATH . '/templates');
        $T->set_file('feed', 'rss_icon.thtml');
        $feed_url = SYND_getFeedUrl();
        while ($A = DB_fetchArray($result, false)) {
            $T->set_var(array(
                'feed_title'    => $A['title'],
                'feed_url'     => $feed_url . $A['filename'],
            ) );
            $T->parse('output', 'feed', true);
        }
        $retval = $T->finish($T->get_var('output'));
    }
    return $retval;
}


/**
 * Convert a latitude or longitude to a string based on the configured separators.
 *
 * @param   float   $val    Value to convert
 * @param   boolean $us     True to force US formatting, False for locale-based
 * @return  string      Formatted numeric string
 */
function EVLIST_coord2str($val, $us = false)
{
    if (!is_numeric($val)) return '';
    if ($us) {
        return number_format($val, 6, '.', ',');
    } else {
        return COM_numberFormat($val, 6);
    }
}


/**
 * Log activity to a plugin-specific log file.
 *
 * @param   string  $logentry   Text to log
 */
function EVLIST_Log($logentry = '')
{
    global $_CONF, $_USER, $LANG01;

    if ($logentry == '')
        return '';

    // A little sanitizing
    $logentry = str_replace(
            array('<?', '?>'),
            array('(@', '@)'),
            $logentry);

    $timestamp = strftime( '%c' );
    $logfile = $_CONF['path_log'] . 'evlist.log';

    // Can't open the log file?  Return an error
    if (!$file = fopen($logfile, 'a')) {
        return $LANG01[33] . $logfile . ' (' . $timestamp . ')<br />' . LB;
    }

    // Get the user name if it's not anonymous
    if (isset($_USER['uid'])) {
        $byuser = $_USER['uid'] . '-'.
            COM_getDisplayName($_USER['uid'],
                $_USER['username'], $_USER['fullname']);
    } else {
        $byuser = 'anon';
    }
    $byuser .= '@' . $_SERVER['REMOTE_ADDR'];

    // Write the log entry to the file
    fputs($file, "$timestamp ($byuser) - $logentry\n");
}

?>
