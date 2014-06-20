<?php
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

/** Initialize the configuration */
require_once $_CONF['path_system'] . 'classes/config.class.php';
$ev_config = config::get_instance();
$_EV_CONF = $ev_config->get_config('evlist');

require_once dirname(__FILE__) . '/evlist.php';

// Some variables are used all over, so get them once to avoid a lot of
// function calls
$_EV_CONF['_today'] = date('Y-m-d');
$_EV_CONF['_today_ts'] = strtotime($_EV_CONF['_today']);

// Check the version of the locator plugin and reset the use_locator
// config value if it's inadequate.
if ($_EV_CONF['use_locator'] == 1) {
    $loc_ver = DB_getItem($_TABLES['plugins'], 'pi_version',
        "pi_name='locator'");
    if (empty($loc_ver) || $loc_ver < $EV_CONF['min_locator_ver']) {
        $_EV_CONF['use_locator'] = 0;
    }
}
        
// Local configuration overrides can be set by having a config.php file.
if (file_exists(EVLIST_PI_PATH . '/config.php')) {
    /** Include local configuration overrides */
    include_once EVLIST_PI_PATH . '/config.php';
}

// Override plugin config if login is required globally
if ($_CONF['loginrequired'] == 1) {
    $_EV_CONF['allow_anon_view'] = '0';
}

// Include the user's language file, if found.  Default to englsh.
$langpath = EVLIST_PI_PATH . '/language';
$langfile = $_CONF['language'] . '.php';
if (!is_file("$langpath/$langfile")) {
    $langfile = 'english.php';
}
require_once "$langpath/$langfile";
if (is_file("$langpath/custom/$langfile")) {
    /** Include the custom language file, if any */
    include_once "$langpath/custom/$langfile";
}

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

function USES_evlist_class_category()
{   require_once EVLIST_PI_PATH . '/classes/evCategory.class.php'; }

function USES_evlist_class_recur()
{   require_once EVLIST_PI_PATH . '/classes/evRecur.class.php'; }

// Define values for whether RSVP can be per repeat or only for a whole event
// This is basically ignored for one-time events.
define('EV_RSVP_EVENT', 1);
define('EV_RSVP_REPEAT', 2);

// macros to make the code easier to read
define('EVCHECKED', 'checked="checked"');
define('EVSELECTED', 'selected="selected"');

/**
*   Return the current code (not necessarily installed) version
*
*   @return string      Current version
*/
function plugin_chkVersion_evlist()
{
    global $_EV_CONF;
    return $_EV_CONF['pi_version'];
}


/**
*   Automatic plugin removal
*
*   @return array   Array of data items to be removed
*/
function plugin_autouninstall_evlist()
{
    $out = array (
        'tables'    => array ('evlist_calendars', 'evlist_categories',
                        'evlist_detail', 'evlist_events',
                        'evlist_lookup','evlist_remlookup',
                        'evlist_repeat', 'evlist_submissions', 
        ),
        'groups'    => array('evlist Admin'),
        'features'  => array('evlist.admin', 'evlist.submit'),

        'php_blocks' => array('phpblock_evlist_upcoming', 
                        'phpblock_evlist_smallmonth'),
    );

    return $out;
}


/**
*   Upgrade the plugin
*
*   @return mixed   True for success, False or error message number otherwise
*/
function plugin_upgrade_evlist()
{
    if (@file_exists(EVLIST_PI_PATH . '/upgrade.php')) {
        require_once EVLIST_PI_PATH . '/upgrade.php';
        return evlist_upgrade();
    } else {
        // upgrades are done by the install script - return a generic error
        COM_errorLog("evList plugin upgrade function not implemented");
        return 3001;
    }
}


/**
*   Get the icon and link for this plugin's Command and Control entry
*
*   @return array   Array of items
*/
function plugin_cclabel_evlist()
{
    global $_EV_CONF;

    if (SEC_hasRights('evlist.admin')) {
        return array(
                $_EV_CONF['pi_name'],
                EVLIST_ADMIN_URL . '/index.php',
                plugin_geticon_evlist(),
        );
    }
}


/**
*   Get this plugin's link for the Admins Only menu
*
*   @return array   Array of name, link, no. of events
*/
function plugin_getadminoption_evlist()
{
    global $_TABLES, $LANG_EVLIST;

    if (SEC_hasRights('evlist.admin')) {

        $numevents = DB_count($_TABLES['evlist_events']);
        return array(
                $LANG_EVLIST['pi_title'],
                EVLIST_ADMIN_URL . '/index.php',
                $numevents
        );
    }
}


/**
*   Get the menu entry for the user option menu
*
*   @return mixed   Array of menu parts, or NULL
*/
function plugin_getuseroption_evlist()
{
    global $_EV_CONF, $LANG_EVLIST;

    $retval = NULL;

    if (!EVLIST_isCurrentVersion()) {
        return $retval;
    }
    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1') {
        return $retval;
    }

    switch ($_EV_CONF['usermenu_option']) {
    case 1:
        $retval = array($LANG_EVLIST['add_event'],
            EVLIST_URL . '/event.php?edit=x',
            0);
        break;
    case 2:
        $retval = array($LANG_EVLIST['pi_title'],
            EVLIST_URL . '/index.php?op=' . $_EV_CONF['default_view'],
            0);
        break;
    }

    return $retval;
}


/**
*   Get the menu entry for the main plugins menu
*
*   @return array   Menu entry items
*/
function plugin_getmenuitems_evlist()
{
    global $_EV_CONF, $LANG_EVLIST;

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1') {
        return NULL;
    }

    $menuitems = array();
    if ($_EV_CONF['enable_menuitem'] == '1') {
        $menuitems[$LANG_EVLIST['pi_title']] = EVLIST_URL . '/index.php';
    }
    return $menuitems;
}


function plugin_showstats_evlist($showsitestats)
{
    global $_CONF, $_EV_CONF, $_TABLES, $LANG_EVLIST;

    $retval = '';

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
        return;
    }

    if (SEC_hasRights('evlist.admin')) {
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


function plugin_statssummary_evlist()
{
    global $_EV_CONF, $_TABLES, $LANG_EVLIST;

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
        return;
    }

    if (SEC_hasRights('evlist.admin')) {
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
*   Return the name to show in the advanced search as the "type"
*
*   @return string      String to display in the search type selection
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
*   Set up the search for this plugin
*
*   @return mixed       SearchCriteria object, or NULL if not allowed
*/
function plugin_dopluginsearch_evlist($query, $datestart, $dateend, 
    $topic, $type, $author, $keyType, $page, $perpage
) {
    global $_CONF, $_EV_CONF,$_TABLES;

    if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
        return NULL;
    }

    $query = DB_escapeString(htmlspecialchars(trim($query)));

    if (SEC_hasRights('evlist.admin')) {
        $permSQL = '';
    } else {
        $permSQL = COM_getPermSQL('AND');
    }

    $url = '/evlist/event.php?view=x&eid=';
    $q = '&query=' . urlencode($query);
    $sql = "SELECT e.id, d.title, d.summary as description, 
                UNIX_TIMESTAMP(r.rp_date_start) AS date,
                e.hits, e.owner_id AS uid, gl.username, 
                CONCAT('$url',r.rp_id,'$q') AS url
            FROM {$_TABLES['evlist_detail']} d
            LEFT JOIN {$_TABLES['evlist_events']} e
                ON d.ev_id = e.id
            LEFT JOIN {$_TABLES['evlist_repeat']} r
                ON e.id = r.rp_ev_id
            LEFT JOIN {$_TABLES['users']} gl
                ON e.owner_id = gl.uid
            WHERE (e.status='1')
                $permSQL ";

    if (!empty($author))
        $sql .= "AND (e.owner_id = '$author') ";

    $search = new SearchCriteria('evlist', 'evList');
    $columns = array('d.title', 'd.summary', 'd.full_description',
                    'd.location', 'd.contact');
    $sql .= $search->getDateRangeSQL('AND', 'UNIX_TIMESTAMP(rp.rp_date_start)', 
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
*   Handle autotags
*
*   @param  string  $op         Requested operation (tagname or parse)
*   @param  string  $content    HTML content
*   @param  string  $autotag    Contents of autotag
*   @return string              Modified HTML $content
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
*   Get plugin-specific javascript.
*
*   @return array   Array of javascript paths
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
*   Get plugin-specific CSS files.
*   Look first in the layout directory, then in the plugin's custom directory,
*   and finally take the default for the plugin.
*
*   @return array   Array of file paths.
*/
function plugin_getheadercss_evlist()
{
    global $_CONF;

    $pi_path = EVLIST_PI_PATH . '/css/';
    $styles = array();
    $files = array(
        'style.css',
        'picker.css',
    );

    foreach ($files as $file) {
        if (@file_exists($_CONF['path_layout'].'plugins/evlist/', $file)) {
            $styles[] = $_CONF['path_layout'].'plugins/evlist/' . $file;
        } elseif (@file_exists($pi_path. 'custom/' . $file)) {
            $styles[] = $pi_path . 'custom/' . $file;
        } else {
            $styles[] = $pi_path . $file;
        }
    }

    return $styles;
}


/**
*   Create the centerblock.
*
*   @param  string  $where  Position (top, etc.)
*   @param  string  $page   Page currently being displayed
*   @param  string  $topic  Topic currently being displayed
*   @return string          HTML for centerblock
*/
function plugin_centerblock_evlist($where, $page, $topic = '')
{
    global $_EV_CONF, $_CONF, $_USER, $_TABLES, $LANG_EVLIST;

    // overloading the previously-boolean enable_centerblock option to
    // indicate the centerblock format.
    switch ($_EV_CONF['enable_centerblock']) {
    case 1:     // table format
        $dup_chk = 'rp_id';
        $tpl_file = 'centerblock.thtml';
        break;
    case 2:     // story format
        $dup_chk = 'rp_ev_id';
        $tpl_file = 'cblock_stories.thtml';
        break;
    case 0:     // disabled
    default:
        return '';
    }
 
    if ( (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1') ||
        $_EV_CONF['pos_centerblock'] != $where ) {
        return '';
    }

    if (!EVLIST_isCurrentVersion()) return '';

    // Retrieve Centerblock Settings
    $range    = $_EV_CONF['range_centerblock'];
    $limit    = (int)$_EV_CONF['limit_block'];
    $length   = $_EV_CONF['limit_summary'];

    // If we show only on the homepage, check if that's where we are
    // If a topic is being displayed, then we're not on the homepage
    if ($_EV_CONF['topic_centerblock'] == 'home' &&
            ($page > 1 || !empty($topic)) ) {
        return '';
    }

    if ($_EV_CONF['topic_centerblock'] != 'all') {
        // display on homepage or not at all
        if (empty($topic) && $_EV_CONF['topic_centerblock'] != 'home') {
            return '';
        }
        // display on topic page or not at all
        if (!empty($topic) && $_EV_CONF['topic_centerblock'] != $topic) {
            return '';
        }
    }

    // If we got this far, we're going to display something as long
    // as there's something to be displayed.
    USES_evlist_functions();
    USES_evlist_class_datecalc();

    $retval = '';
    $opts = array(
        'limit' => $limit,
        'show_upcoming' => 1,
    );

    switch ($_EV_CONF['range_centerblock']) {
    case 1:         // past events
        $start = date('Y-m-d', strtotime("{$_EV_CONF['_today']} - 1 month"));
        $end = date('Y-m-d', strtotime("{$_EV_CONF['_today']} - 1 day"));
        $limit = 0;     // special, we need to get all events since we can't count back
        $opts['order'] = 'DESC';
        break;
    case 2:         // upcoming events
    default:
        $start = $_EV_CONF['_today'];
        $end = EV_MAX_DATE;
        break;
    case 3:         // this week
        $start = $_EV_CONF['_today'];
        $end = date('Y-m-d', strtotime("{$_EV_CONF['_today']} + 7 day"));
        break;
    case 4:
        $start = $_EV_CONF['_today'];
        $end = date('Y-m-d', strtotime("{$_EV_CONF['_today']} + 1 month"));
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

    $T = new Template(EVLIST_PI_PATH . '/templates/');
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
    foreach ($events as $day) {
        foreach ($day as $A) {

            // Make sure we only show each event once for multiday
            if (array_key_exists($A[$dup_chk], $rp_shown))
                continue;
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

            $s_ts1 = strtotime($A['rp_date_start'].' '.$A['rp_time_start1']);
            $e_ts1= strtotime($A['rp_date_end'].' '.$A['rp_time_end1']);
            $email = EVLIST_obfuscate($A['email']);
            $cssid = ($cssid ==1) ? 2: 1;

            if ($A['postmode'] != 'plaintext') {
                $full_desc = PLG_replaceTags($A['full_description']);
            } else {
                $full_desc = $A['full_description'];
            }

            $T->set_var(array(
                'cssid'     => $cssid,
                'eid'       => $A['rp_id'],
                'pi_url'    => EVLIST_URL,
                'title'     => $A['title'],
                'summary'   => $summary,
                'full_description' => $full_desc,
                'contact'   => $A['contact'],
                'location'  => $A['location'],
                'url'       => $A['url'],
                'street'    => $A['street'],
                'city'      => $A['city'],
                'province'  => $A['province'],
                'country'   => $A['country'],
                'email'     => $email,
                'phone'     => $A['phone'],
                'startdate' => 
                        EVLIST_formattedDate($s_ts1),
                'enddate' => 
                        EVLIST_formattedDate($e_ts1),
                'starttime1' => 
                        EVLIST_formattedTime($s_ts1),
                'endtime1' => 
                        EVLIST_formattedTime($e_ts1),
            ) );
            if ($A['split'] == 1) {
                $s_ts2 = strtotime($A['rp_date_start'] . ' ' . $A['rp_time_start2']);
                $e_ts2= strtotime($A['rp_date_end'] . ' ' . $A['rp_time_end2']);
                $T->set_var(array(
                    'starttime2' => 
                        EVLIST_formattedTime($s_ts2),
                    'endtime2' => 
                        EVLIST_formattedTime($e_ts2),
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
*   Change ownership of user events when the user is deleted.
*/
function plugin_user_delete_evlist($uid)
{
    global $_TABLES;

    DB_query("UPDATE {$_TABLES['evlist_events']} SET
        owner_id = '2', status = '0'
        WHERE owner_id = '$uid'");
}


/**
*   Get the plugin's icon
*
*   @return string  URL to icon image
*/
function plugin_geticon_evlist()
{
    return EVLIST_URL . '/images/evList.png';
}


/**
*   Get the names of RSS feeds that are provided.
*   For evList this is a list of topics
*
*   @return array   Array of ID=>Name pairs
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
*   Get content for the syndication feeds.
*
*   @param  string  $feed       Feed ID to get
*   @param  string  $link       Pointer to header link value
*   @param  array   $update_data    Pointer to array of updated item IDs
*   @param  string  $feedType   Feed type (RSS, ICS, etc.) We only do ICS.
*   @return array               Array of event data
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
    $events = EVLIST_getEvents($_EV_CONF['_today'], 
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
*   Checks to see if the RSS feed is up-to-date
*
*   @param  integer $feed   Feed ID from the RSS configuration
*   @param  integer $topic  Topic ID being requested
*   @param  string  $update_data    Comma-separated string of current item IDs
*   @param  integer $limit  Configured limit on item count for this feed
*   @return boolean         True if feed needs updating, False otherwise
*/
function plugin_feedupdatecheck_evlist($feed, $topic, $update_data, $limit)
{
    global $_EV_CONF;;

    $eids = array();

    // Set a sane limit on the events retrieved to avoid OOM errors
    $limit = (int)$limit;
    if ($limit > 500) $limit = 500;

    $sql = EVLIST_getEvents_sql($_EV_CONF['_today'], 
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
*   Run scheduled tasks for the plugin.
*   - Send event reminders
*/
function plugin_runScheduledTask_evlist()
{
    global $_CONF, $_TABLES, $LANG_EVLIST;

    if (!EVLIST_isCurrentVersion()) return;

    //check if reminders exist
    $sql = "SELECT 
            rem.email, rem.eid, rem.uid,
            det.title, det.summary,
            rep.rp_date_start, rep.rp_time_start1, rep.rp_time_end1, rep.rp_id,
            rep.rp_time_start2, rep.rp_time_end2,
            ev.allday, ev.split,
            u.language
        FROM 
            {$_TABLES['evlist_remlookup']} rem,
            {$_TABLES['evlist_repeat']} rep,
            {$_TABLES['evlist_events']} ev,
            {$_TABLES['evlist_detail']} det,
            {$_TABLES['users']} u
        WHERE
            ev.id=rem.eid 
        AND 
            rep.rp_id = rem.rp_id
        AND
            ev.id=rep.rp_ev_id
        AND
            det.det_id = rep.rp_det_id
        AND
            u.uid = rem.uid
        AND
            rep.rp_date_start < date_add(NOW(), INTERVAL rem.days_notice DAY)
        ";
        // TODO: Figure out sql for recurring event reminders
        /*AND (
            rem.rp_id = 0 AND 
            rep.rp_date_start < date_add(NOW(), INTERVAL rem.days_notice DAY)            )
    )";*/
    //COM_errorLog($sql);
    $result = DB_query($sql, 1);
    if (DB_numRows($result) < 1) {
        return true;
    }
    
    $now = time();

    //common variables
    //begin loop
    while ($A = DB_fetchArray($result, false)) {

        // Load the user's language
        $LANG = plugin_loadlanguage_evlist($A['language']);

        $subject = $LANG['rem_subject'];
        $msg_top = $LANG['rem_msg1'];
        //$msg_bot = $LANG['rem_msg3'];

        $title = COM_stripslashes($A['title']);
        $summary = COM_stripslashes($A['summary']);
        $date_start = EVLIST_formattedDate($A['rp_date_start']);
        $event_url = EVLIST_URL . '/event.php?eid=' . $A['rp_id'];
        if ($A['allday'] == 1) {
            $times = $LANG['allday'];
        } else {
            $times = EVLIST_formattedTime($A['rp_time_start1']) . ' - ' .
                    EVLIST_formattedTime($A['rp_time_end1']);
            if ($A['split'] == 1) {
                $times .= ', ' . EVLIST_formattedTime($A['rp_time_start2']) .
                    ' - ' . EVLIST_formattedTime($A['rp_time_end2']);
            }
        }

        $message = $msg_top . LB . LB;
        $message .= $LANG['what'] . ': ' . $title . LB;
        $message .= $LANG['when'] . ': ' . $date_start . ' ' . $times . LB;
        if (!empty($summary)) {
            $message .= LB . $summary . LB . LB;
        }
        $message .= sprintf($LANG_EVLIST['rem_url'], $event_url) . LB . LB;

        $mailto = COM_formatEmailAddress('',$A['email']);
        //mail reminder
        COM_mail($mailto, $subject, $message);

        //delete reminder
        DB_delete($_TABLES['evlist_remlookup'],
                    array('eid', 'rp_id', 'uid'),
                    array($A['eid'], $A['rp_id'], $A['uid']));

        $A['title'] = '';
        $A['summary'] = '';
    }

    return true;
}


/**
*   Get the number of submissions awaiting moderation
*
*   @return integer     Number of submissions in queue
*/
function plugin_submissioncount_evlist()
{
    global $_TABLES;

    return DB_count($_TABLES['evlist_submissions']);
}


/**
*   Additional activity when a submission is approved.
*   The event data will have been copied from the submission table already.
*
8   @param  string  $eid    Event ID being approved
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
    // but now we're going to read that data back and let the evEvent class
    // create the recurrences.
    USES_evlist_class_event();
    $Ev = new evEvent($eid);
    $Ev->UpdateRepeats();

    return true;
}


/**
*   Additional activity when a submission is deleted by the moderator.
*   The record will be deleted from the submission table by the core.
*   Repeats haven't been created yet.
*
*   @param  string  $eid    Event ID being deleted
*/
function plugin_moderationdelete_evlist($eid)
{
    global $_TABLES;

    // Delete from the category lookup
    DB_delete($_TABLES['evlist_lookup'], 'eid', $eid);
    // Delete the detail portion of the event
    DB_delete($_TABLES['evlist_detail'], 'ev_id', $eid);
}


function plugin_moderationvalues_evlist()
{
    global $_TABLES;

    return array(
        'id',
        $_TABLES['evlist_events'],
        'id, date_start1, date_end1,
        time_start1, time_end1, time_start2, time_end2, 
        recurring, rec_data, allday, split, 
        status, postmode, hits, enable_reminders, 
        owner_id, group_id, perm_owner, perm_group, perm_members, perm_anon,
        det_id, show_upcoming, cal_id, options',
        $_TABLES['evlist_submissions']
    );
}


/**
*   Determine if the current user is a plugin moderator
*
*   @see SEC_hasRights()
*/
function plugin_ismoderator_evlist()
{
    return SEC_hasRights('evlist.submit');
}


/**
*   Get a list of items to be moderated
*
*   @return object  Plugin object containg item data
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
*   Perform actions needed when the plubin is enabled or disabled.
*   Enables or disables syndication, and turns off PHP blocks if disabling.
*   Blocks must be turned on manually.
*
*   @param  boolean $enable True if plugin is now enabled, False if disabled.
*/
function plugin_enablestatechange_evlist($enable)
{
    global $_TABLES;

    $is_enabled = $enable ? 1 : 0;

    // toggle evlist feeds
    DB_query ("UPDATE {$_TABLES['syndication']} 
                SET is_enabled = $is_enabled 
                WHERE type = 'evlist'");

    // turns block off if on, but will not turn it on if off.
    if ($is_enabled != 1) {
        DB_query ("UPDATE {$_TABLES['blocks']} 
                    SET is_enabled = $is_enabled 
                    WHERE (type = 'phpblock') 
                    AND (phpblockfn LIKE 'phpblock_evlist_%')");
    }
}


function evlist_sendNotification($A)
{
    global $LANG_DIRECTION, $LANG_CHARSET, $_CONF, $_TABLES, $LANG_EVLIST;

    require_once $_CONF['path'].'lib/phpmailer/class.phpmailer.php';

    if( empty( $LANG_DIRECTION )) {
        // default to left-to-right
        $direction = 'ltr';
    } else {
        $direction = $LANG_DIRECTION;
    }
    if( empty( $LANG_CHARSET )) {
        $charset = $_CONF['default_charset'];
        if( empty( $charset )) {
            $charset = 'iso-8859-1';
        }
    } else {
        $charset = $LANG_CHARSET;
    }

    COM_clearSpeedlimit(300,'evnotify');
    $last = COM_checkSpeedlimit ('evnotify');
    if ( $last == 0 ) {
        $mail = new PHPMailer();
        $mail->CharSet = $charset;
        if ($_CONF['mail_backend'] == 'smtp' ) {
            $mail->Host     = $_CONF['mail_smtp_host'] . ':' . $_CONF['mail_smtp_port'];
            $mail->SMTPAuth = $_CONF['mail_smtp_auth'];
            $mail->Username = $_CONF['mail_smtp_username'];
            $mail->Password = $_CONF['mail_smtp_password'];
            $mail->Mailer = "smtp";
        } elseif ($_CONF['mail_backend'] == 'sendmail') {
            $mail->Mailer = "sendmail";
            $mail->Sendmail = $_CONF['mail_sendmail_path'];
        } else {
            $mail->Mailer = "mail";
        }
        $mail->WordWrap = 76;
        $mail->IsHTML(false);

        $title = stripslashes ($A['title']);
        $summary = stripslashes ($A['summary']);

        $mail->Subject = $_CONF['site_name'] . ' ' . $LANG_EVLIST['moderation_title'];

        $mailbody = "{$LANG_EVLIST['title']}: $title\n"
                  . "{$LANG_EVLIST['date']}: " . strftime ($_CONF['date'],
                    strtotime ($A['date_start1']));
        $mailbody .= "\n" . $summary . "\n\n";
        $mailbody .= "{$LANG_EVLIST['moderation_title']} <{$_CONF['site_admin_url']}/moderation.php>\n\n";
        $mailbody .= "\n------------------------------\n";
        $mailbody .= "\n{$LANG_EVLIST['end_message']}\n";
        $mailbody .= "\n------------------------------\n";

        $mail->Body = $mailbody;

        $mail->From     = $_CONF['site_mail'];
        $mail->FromName = $_CONF['site_name'];

        $group_id = DB_getItem($_TABLES['groups'],'grp_id','grp_name="evList Admin"');

        $groups = EV_getGroupList($group_id);
        $groupList = implode(',',$groups);

        $sql = "SELECT DISTINCT {$_TABLES['users']}.uid,username,fullname,email "
              ."FROM {$_TABLES['group_assignments']},{$_TABLES['users']} "
              ."WHERE {$_TABLES['users']}.uid > 1 "
              ."AND {$_TABLES['users']}.uid = {$_TABLES['group_assignments']}.ug_uid "
              ."AND ({$_TABLES['group_assignments']}.ug_main_grp_id IN ({$groupList}))";

        $result = DB_query($sql);
        $nRows = DB_numRows($result);
        $toCount = 0;
        for ($i=0;$i < $nRows; $i++ ) {
            $row = DB_fetchArray($result);
            if ( $row['email'] != '' ) {
                COM_errorLog("evList Submit: Sending notification email to: " . $row['email'] . " - " . $row['username']);
                $toCount++;
                $mail->AddAddress($row['email'], $row['username']);
            }
        }
        if ( $toCount > 0 ) {
            if(!$mail->Send()) {
                COM_errorLog("evList Submit: Unable to send moderation email - error:" . $mail->ErrorInfo);
            }
        } else {
            COM_errorLog("evList Submit: Error - Did not find any administrators to email");
        }
        COM_updateSpeedlimit ('evnotify');
    }
    return true;
}


/**
* Get a list (actually an array) of all groups this group belongs to.
*
* @param   basegroup   int     id of group
* @return              array   array of all groups 'basegroup' belongs to
*
*/
function EV_getGroupList ($basegroup)
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
*   Add a hit to an event
*
*   @param  string  $eid    Event ID
*/
function evlist_hit($eid)
{
    global $_TABLES;

    DB_query("UPDATE {$_TABLES['evlist_events']}
            SET hits = hits + 1
            WHERE id = '" . DB_escapeString($eid) ."'");
}


function Xevlist_events_query($range = '', $limit = '', $category = '')
{
    global $_EV_CONF, $_TABLES;

return NULL;

    if (!isset($_EV_CONF['limit_list']) || $_EV_CONF['limit_list'] == '') {
        $tLimit = 5;
    } else {
        $tLimit = (int)$_EV_CONF['limit_list'];
    }
    if ( $tLimit == 0 ) {
        $_EV_CONF['limit_list'] = 5;
    }

    switch ($_EV_CONF['event_passing']) {
    case 1:
        //event passes when now > start_datetime
        //The seeming complexity of the following where clause is due to the fact
        //that evList does not require a start time.  That's the short version.
        $eventstart = "ADDTIME(CONCAT(date_start1,' ','23:59:59'),time_start1)";
        break;
    case 3:
        //event passes when end_datetime < now
        //problem is that an end date and an end time are not required.
        #$eventstart = "ADDTIME(CONCAT(date_end1,' ','00:00:00'),time_end1)";
        #break;
    case 4:
        //event passes when endate < now
        //problem is that an end date is not required.
        #$eventstart = "date_end1";
        #break;
    case 2:
    default:
        //event passes when now > start_date
//        $eventstart = "date_start1";
        $eventstart = "CONCAT(date_start1,' ','23:59:59')";
    }

    switch ($range) {
    case 1:// past
        $where = " AND $eventstart < NOW()";
        $asc = " DESC";
        break;
    case 3://this week
        $where = " AND $eventstart BETWEEN NOW() AND NOW() + INTERVAL 7 DAY";
        $asc = " ASC";
        break;
    case 4://this month
        $where = " AND $eventstart BETWEEN NOW() AND NOW() + INTERVAL 1 MONTH";
        $asc = " ASC";
        break;
    #case 5:
        //current -- probably not necessary.
    #    $where = "";
    #    $asc = " ASC";
    #    break;
    case 2://upcoming
    default://upcoming
        $where = " AND $eventstart >= NOW()";
        $asc = " ASC";
    }

    if (!empty($category) && $category != '0') {
        $category = COM_applyFilter($category, true);
        $category_leftjoin = " LEFT JOIN {$_TABLES['evlist_lookup']} tl"
            . " ON id = eid";
        $wherecat = " AND (cid = '$category' AND tl.status = '1')";
    } else {
        $category_leftjoin = '';
        $wherecat = '';
    }

    if (!empty($limit)) {
        if (substr ($limit, -1) == 'h') { // last xx hours
            //this is for the feed update check which could be number of items or hours.
            $hours = substr ($limit, 0, -1);
            $where .= " AND ADDTIME(CONCAT(date_start1,' ','00:00:00'),time_start1) >= DATE_SUB(NOW(),INTERVAL $hours HOUR)";
        } else {
            $limit = ' LIMIT ' . $limit;
        }
    } elseif (!empty($_REQUEST['page'])) {
        $page = COM_applyFilter($_REQUEST['page'], true);
        $limit = ($_EV_CONF['limit_list'] * $page) - $_EV_CONF['limit_list'];
        $limit = " LIMIT $limit, ".$_EV_CONF['limit_list'];
    } else {
        //$display_limit = $_EV_CONF['limit_list'];//this is also set in evlist_pagenav()
        $page = 1;
        $limit = ($_EV_CONF['limit_list'] * $page) - $_EV_CONF['limit_list'];
        $limit = " LIMIT $limit, ".$_EV_CONF['limit_list'];
    }

    //get potential recurrences and add them to the result set
    evlist_makeRecurrences();

    if ( SEC_hasRights('evlist.admin') ) {
        $permSQL = '';
    } else {
        $permSQL = COM_getPermSQL('AND');
    }

    $result = DB_query("SELECT * FROM evlist_temp_events tt"
        . $category_leftjoin
        . " WHERE tt.status = '1'"
        . $wherecat . $where . $permSQL
        . " ORDER BY ADDTIME(CONCAT(date_start1,' ','00:00:00'),time_start1)" . $asc . $limit);
    $nrows = DB_numRows($result);
    if ($nrows < 1) {
        //return empty
        $retval = '';
    } else {
        $retval = $result;
    }

    return $retval;
}


function Xevlist_single_event_query($eid, $access = '2')
{
    global $_TABLES;

    if ( SEC_hasRights('evlist.admin') ) {
        $permSQL = '';
    } else {
        $permSQL = COM_getPermSQL('AND', '0', $access);
    }

    $result = DB_query("SELECT * FROM {$_TABLES['evlist_events']} WHERE id = '".DB_escapeString($eid)."'" . $permSQL . " LIMIT 1");
    $nrows = DB_numRows($result);
    if ($nrows < 1) {
        //perhaps this id corresponds to a submission that needs to be edited prior to approval
        $result = DB_query("SELECT * FROM {$_TABLES['evlist_submissions']} WHERE id = '".DB_escapeString($eid)."'" . $permSQL . " LIMIT 1");
        $nrows = DB_numRows($result);
        if ($nrows < 1 ) {
            //return empty
            $retval = '';
        } else {
            $retval = $result;
        }
    } else {
        $retval = $result;
    }

    return $retval;
}


/**
*   Get the links to categories related to a specific event
*
*   @param  string  $eid    Event ID
*   @param  string  @andrange   Range to pass wo url TODO: required?
*   @return string      HTML for category inls
*/
function EVLIST_getCatLinks($eid, $andrange)
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
            $catlinks[] = '<a href="' .
                COM_buildURL(EVLIST_URL . '/index.php?op=list' . $andrange . 
                '&amp;cat=' . $C['id']) . '">' . COM_stripslashes($C['name']) . 
                '</a>';
        }
        $retval = implode('&nbsp;|&nbsp;', $catlinks);
    } else {
        $retval = '';
    }
    return $retval;

}


function evlist_getCategoryOptions($selected) {

    global $_TABLES;

    $options = '';
    $result = DB_query("SELECT id, name 
                FROM {$_TABLES['evlist_categories']} 
                WHERE status = '1'");
    while ($A = DB_fetchArray($result)) {
        $options .= '<option value="' . $A['id'] . '"';
        if ($selected == $A['id']) {
            $options .= ' selected="selected"';
        }
        $options .= '>' . COM_stripslashes($A['name']) . '</option>';
    }

    return $options;
}


/**
*   Returns a formatted date given a timestamp.
*   Converts the timestamp parameter to an actual timestamp if non-numeric
*
*   @param  mixed   $timestamp  Timestamp to return as formatted.
*   @return string      Formatted date
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
*   Returns a formatted time given a timestamp.
*   Converts the timestamp parameter to an actual timestamp if non-numeric
*   Uses the global time format configuration
*
*   @param  mixed   $timestamp  Timestamp to return as formatted.
*   @return string      Formatted date
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
*   Create an obfuscated version of a string for display.
*
*   @param  string  $str    String to obfuscate
*   @return string          Obfuscated versiona
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
*   Create the "Upcoming Events" block.
*   Some things are hard-coded and should be moved to configuation items:
*       - The string length for displayed items, currently 96
*
*   @param  array   $A      Array of block info
*   @param  string  $catcal Category or calendar id, e.g. "cat:2" or "cat:2:cal:3"
*   @return string  HTML for the block, empty if no qualifying events
*/
function phpblock_evlist_upcoming($A='', $catcal='')
{
    global $_EV_CONF;

    $retval = '';

    if ( !EVLIST_isCurrentVersion() && 
        (!isset($_EV_CONF['devel']) || $_EV_CONF['devel'] == 0) ) {
        // if not the current version, and not in development mode, return
        return $retval;
    }
    USES_evlist_functions();

    //length to be set from admin panels sometime later, or not.
    $length = '96';

    // Max period to include (days)
    $max_days = (int)$_EV_CONF['max_upcoming_days'];

    // Set the start & ending periods.
    $end = date('Y-m-d', $_EV_CONF['_today_ts'] + (86400 * $max_days));
    $limit = $_EV_CONF['limit_block'];
    $now = date('Y-m-d H:i:s');

    // Figure out the selected category & calendar.  Only one supported now,
    // defaults to category.
    $cal = 0;
    $cat = 0;
    if (is_numeric($catcal)) {
        $cat = (int)$catcal;
    } else {
        $catcalA = explode(':', $catcal);
        if (!empty($catcalA)) {
            $c = count($catcalA);
            for ($i = 0; $i < $c; $i += 2) {
                $val = isset($catcalA[$i + 1]) ? (int)$catcalA[$i + 1] : 0;
                if ($val < 1) continue;
                switch ($catcalA[$i]) {
                case 'cat':
                case 'cal':
                    $$catcalA[$i] = $val;
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
    );
    $events = EVLIST_getEvents($_EV_CONF['_today'], $end, $opts);
    if (empty($events)) return $retval;

    $T = new Template(EVLIST_PI_PATH . '/templates/');
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

            // Get the timestamps for quick comparisons.  If split, use time2
            $s_ts = $ev['rp_date_start'] . ' ' . $ev['rp_time_start1'];
            if ($ev['split'] == 1) {
                $e_ts = $ev['rp_date_end'] . ' ' . $ev['rp_time_end2'];
            } else {
                $e_ts = $ev['rp_date_end'] . ' ' . $ev['rp_time_end1'];
            }

            // Determine if this event is "past" for the purpose of this list.
            switch ($_EV_CONF['event_passing']) {
            case 1:     // start time has passed
                if ($s_ts < $now)
                    continue 2;
                break;
            case 2:     // start date is past
                if ($ev['rp_date_start'] < $_EV_CONF['_today'])
                    continue 2;
                break;
            case 3:     // end time has passed, maybe same day
                if ($e_ts < $now)
                    continue 2;
                break;
            case 4:     // end date has passed
                if ($ev['rp_date_end'] < $_EV_CONF['_today'])
                    continue 2;
                break;
            default:    // show the event
                break;
            }

            // Check that this event should be shown as upcoming at all, and
            // only show multi-day events once.
            if ($ev['show_upcoming'] != 1 || 
                array_key_exists($ev['rp_id'], $rp_shown)) {
                continue;
            }
            $rp_shown[$ev['rp_id']] = 1;

            // TODO: displaying summaries in the block will be set from the 
            // admin panels sometime later.
            $summary = strip_tags(EVLIST_noquotes($ev['summary']));
            if (strlen($summary) > $length) {
                $summary = substr($summary, 0, $length) . '...';
            }
            $T->set_block('block', 'blkItem', 'bItem');
            $T->set_var(array(
                'pi_url'    => EVLIST_URL,
                'rp_id'     => $ev['rp_id'],
                'title'     => htmlspecialchars($ev['title']),
                'summary'   => htmlspecialchars($summary),
                'full_description' => $ev['full_description'],
            ) );
            $T->parse('bItem','blkItem', true);
            $count++;
            $daycount++;
        }
        if ($daycount > 0) {
            $T->set_var('start_date', $startdate);
            $T->parse('bDay', 'blkDay', true);
            $T->clear_var('bItem');
        }
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
*   Helper function: Provide topic dropdown for plugin config
*
*   @return array   Array of topic_name => topic_id pairs
*/
function plugin_configmanager_select_topic_centerblock_evlist()
{

    global $_TABLES,$LANG_EVLIST;

    $topics = array();

    // Topic options not in the DB
    $topics[$LANG_EVLIST['topic_all']] = 'all';
    $topics[$LANG_EVLIST['topic_home']] = 'home';

    // Check topic access if not an admin
    if ( SEC_hasRights('evlist.admin') ) {
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
* Return information for an event
*
* @param    string  $id         event ID or '*'
* @param    string  $what       comma-separated list of properties
* @param    int     $uid        user ID or 0 = current user
* @param    array   $options    (reserved for future extensions)
* @return   mixed               string or array of strings with the information
*
*/
function plugin_getiteminfo_evlist($id, $what, $uid = 0, $options = array())
{
    global $_CONF, $_TABLES, $LANG_EVLIST;

    $properties = explode(',', $what);
    $fields = array();
    foreach ($properties as $p) {
        switch ($p) {
            case 'date':
                $fields[] = 'date_start';
                break;
            case 'description':
            case 'raw-description' :
                $fields[] = 'full_description';
                break;
            case 'excerpt':
                $fields[] = 'summary';
                break;
            case 'label':
            case 'url':
                $fields[] = 'id';
                break;
            case 'status' :
            case 'id':
            case 'title':
                $fields[] = $p;
                break;
            case 'author':
                $fields[] = 'owner_id';
                break;
            default:
                break;
        }
    }

    $fields = array_unique($fields);

    if (count($fields) == 0) {
        $retval = array();
        return $retval;
    }

    if ($id == '*') {
        $where = '';
        $permOp = 'WHERE';
    } else {
        $where = " WHERE id = '" . DB_escapeString($id) . "'";
        $permOp = 'AND';
    }
    if ($uid > 0) {
        $permSql = COM_getPermSql($permOp, $uid);
    } else {
        $permSql = COM_getPermSql($permOp);
    }
    $sql = "SELECT " . implode(',', $fields) . 
            " FROM {$_TABLES['evlist_events']}
            $where 
            $permSql ";

    if ($id != '*') {
        $sql .= ' LIMIT 1';
    }

    $result = DB_query($sql);
    $numRows = DB_numRows($result);

    $retval = array();
    for ($i = 0; $i < $numRows; $i++) {
        $A = DB_fetchArray($result);

        $props = array();
        foreach ($properties as $p) {
            switch ($p) {
                case 'date' :
                    $props[$p] = $A['date_start1'];
                    break;
                case 'description':
                    if ($A['postmode'] != 'plaintext') {
                        $props[$p] = PLG_replaceTags($A['full_description']);
                    } else {
                        $props[$p] = $A['full_description'];
                    }
                    break;
                case 'raw-description' :
                    $props[$p] = $A['full_description'];
                    break;
                case 'excerpt':
                    $props[$p] = $A['summary'];
                    break;
                case 'id':
                    $props['id'] = $A['id'];
                    break;
                case 'title':
                    $props['title'] = $A['title'];
                    break;
                case 'url':
                    if (empty($A['id'])) {
                        $props['url'] = COM_buildURL(EVLIST_URL . '/event.php?eid=' . $id);
                    } else {
                        $props['url'] = COM_buildURL(EVLIST_URL . '/event.php?eid=' . $A['id']);
                    }
                    break;
                case 'label':
                    $props['label'] = $LANG_EVLIST['pi_title'];
                    break;
                case 'status':
                    $props['status'] = $A['status'];
                    break;
                case 'author':
                    $props['author'] = $A['owner_id'];
                    break;
                default:
                    $props[$p] = '';
                    break;
            }
        }

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

    return $retval;
}


/*
*   Return a link to the help element for this configuration option
*
*   @param  string  $element    Element being looked up
*   @param  string  $doclang    Language
*   @return array       Array (URL, window option)
*/
function plugin_getconfigelementhelp_evlist($element, $doclang )
{
    return array('http://www.glfusion.org/wiki/evlist:start#configuration_options',2);
}


/**
*   Get the site header, with or without left blocks according to config.
*
*   @see    function COM_siteHeader()
*   @param  string  $title  Optional text for the title bar
*   @param  string  $meta   Optional additional meta text
*   @return string          HTML for site header
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
*   Get the site footer, with or without right blocks according to config.
*
*   @see    function COM_siteFooter()
*   @return string      HTML for site footer
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
*   Display a small month calendar in a block.
*   Optional parameters allow for custom blocks to be created that display
*   only one or a subset of calendar data
*
*   @see    function EVLIST_smallmonth()
*   @param  array   $blk    Optional block data
*   @param  string  $cal    Optional calendar ID
*   @return string      HTML for the block contents
*/
function phpblock_evlist_smallmonth($blk=array(), $cal=0)
{
    if (!EVLIST_isCurrentVersion()) return;
    USES_evlist_functions();

    if ($cal > 0) {
        $opts = array('cal' => (int)$cal);
    } else {
        $opts = array();
    }
    return EVLIST_smallmonth(0, 0, $opts);
}


/**
*   Loads the language array.  If $requested is an array, the first valid
*   language file is loaded.  If not, the $requested language file is loaded.
*   If $requested doesn't refer to a vailid language, then $_CONF['language']
*   is assumed.
*
*   After loading the base language file, the same filename is loaded from
*   language/custom, if available.  The admin can override language strings
*   by creating a language file in that directory.
*
*   By including the language file, this function reads the $LANG_EVLIST 
*   array. However, since it is not declared as global this does not override 
*   the global version.  This version is returned to be used in a different 
*   variable name.
*
*   @param  mixed   $requested  A single or array of language strings
*   @return array       $LANG_EVLIST, the global language array for the plugin
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
    $langpath = EVLIST_PI_PATH . '/language';
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
*   Check that the currently installed version matches the code version.
*   This is to disable functions that might not work due to a mismatch.
*   Just check the major.minor.revision values.  Any fourth element is ignored.
*
*   @return boolean     True if current == installed version, False if not
*/
function EVLIST_isCurrentVersion()
{
    global $_EV_CONF, $_TABLES;

    static $isCurrent = -1;     // static var to minimize DB calls

    if ($isCurrent === -1) {
        $isCurrent = true;
        $evListVersion = DB_getItem($_TABLES['plugins'], 'pi_version', 
                "pi_name = 'evlist'");
        $installed = explode('.', $evListVersion);
        $code = explode('.', $_EV_CONF['pi_version']);
        for ($i = 0; $i < 3; $i++) {
            if ($installed[$i] != $code[$i]) $isCurrent = false;
        }
    }
    return $isCurrent;
}


/**
*   Create the SQL query to get all events that fall within a range.
*
*   The $opts array parameter is used to restrict the result set.
*   The options currently available are:
*       'cat'   int     Limit to this category ID
*       'cal'   int     Limit to this calendar ID
*       'show_upcoming' boolean     Whether this flag is set in the event
*       'limit' int     Max number of results
*       'page'  int     Page number, used in the limit clause if > 1
*
*   @param  string  $start  Starting date, YYYY-MM-DD
*   @param  string  $end    Ending date, YYYY-MM-DD
*   @param  array   $opts   Array of other options (name=>value)
*   @return string          SQL query to retrieve events
*/
function EVLIST_getEvents_sql($start='', $end='', $opts=array())
{
    global $_TABLES, $_EV_CONF;

    USES_evlist_class_datecalc();

    if ($start == '') $start = $_EV_CONF['_today'];
    if ($end == '') $end = $start;

    list($y, $m, $d) = explode('-', $start);
    if (!Date_Calc::isValidDate($d, $m, $y)) $start = $_EV_CONF['_today'];
    list($y, $m, $d) = explode('-', $end);
    if (!Date_Calc::isValidDate($d, $m, $y)) $end = $start;
    $start = DB_escapeString($start);
    $end = DB_escapeString($end);

    // Set up other search options.
    $selection = '';
    $opt_select = '';
    $opt_join = '';
    $opt_where = '';
    $opt_order = 'ASC';
    $limit = 0;
    $page = 0;
    $cat_status = ' AND (cat.status = 1 OR cat.status IS NULL)';
    if (is_array($opts) && !empty($opts)) {
        foreach ($opts as $key => $value) {
            $value = DB_escapeString($value);
            switch ($key) {
            case 'cal':
                if ($value > 0)
                    $opt_where .= ' AND cal.cal_id = ' . (int)$value;
                break;
            case 'eid':
                $opt_where .= " AND ev.id = '$value'";
                break;
            case 'rp_id':
                $opt_where .= ' AND rep.rp_id = ' . (int)$value;
                break;
            case 'ical':
                $opt_where .= ' AND cal.cal_ena_ical = '.($value == 1 ? 1 : 0);
                break;
            case 'cat':
                if ($value > 0) {
                    $opt_select .= ', cat.name AS cat_name';
                    $cat_status .= " AND l.cid = '$value' AND cat.status = 1";
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
            case 'select':
                $selection = $value;
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
            LEFT JOIN {$_TABLES['evlist_lookup']} l
                ON l.eid = ev.id 
            LEFT JOIN {$_TABLES['evlist_categories']} cat
                ON cat.id = l.cid 
            WHERE ev.status = 1 
            AND (cal.cal_status = 1 OR cal.cal_status IS NULL)
            $cat_status
            AND (
                (rep.rp_date_start <= '$end' AND rep.rp_date_end >= '$start')
                OR
                (rep.rp_date_end >= '$start' AND rep.rp_date_start <= '$start')
                OR
                (rep.rp_date_end <= '$end' AND rep.rp_date_start >= '$start')
            ) " . 
            COM_getPermSQL('AND', 0, 2, 'ev') . ' ' .
            COM_getPermSQL('AND', 0, 2, 'cal') . 
            " $opt_where
            GROUP BY rep.rp_id
            ORDER BY rep.rp_date_start $opt_order, 
                    rep.rp_time_start1 $opt_order";
    if ($limit > 0) {
        if ($page > 1) {
            $sql .= ' LIMIT ' . (($page - 1) * $limit) . ',' . $limit;
        } else {
            // page 1 or 0, no starting offset
            $sql .= " LIMIT $limit";
        }
    }

    return $sql;
}


/**
*   Get all events that fall within a range.
*   This is in functions.inc so it can be used by the feed update
*   functions without having to load evlist_functions.inc.php.
*
*   The $opts array parameter is used to restrict the result set.
*   The options currently available are:
*       'cat'   int     Limit to this category ID
*       'cal'   int     Limit to this calendar ID
*       'show_upcoming' boolean     Whether this flag is set in the event
*       'limit' int     Max number of results
*       'page'  int     Page number, used in the limit clause if > 1
*
*   @param  string  $start  Starting date, YYYY-MM-DD
*   @param  string  $end    Ending date, YYYY-MM-DD
*   @param  array   $opts   Array of other options (name=>value)
*   @return array           Array of matching events, keyed by date
*/
function EVLIST_getEvents($start='', $end='', $opts=array())
{
    global $_EV_CONF;

    $events = array();

    $sql = EVLIST_getEvents_sql($start, $end, $opts);
    $result = DB_query($sql, 1);

    if (!$result || DB_error())
        return $events;

    USES_evlist_class_repeat();
    USES_evlist_class_datecalc();

    while ($A = DB_fetchArray($result, false)) {

        if ($A['rp_date_start'] == $A['rp_date_end']) {
            // Single-day event just gets added to the array
            $events[$A['rp_date_start']][] = $A;
        } else {
            // Multi-day events get a record for each day up to the event end
            // or limit, whichever comes first
            $end_date = min($A['rp_date_end'], $end);
            $newdate = max($A['rp_date_start'], $start);
            while ($newdate <= $end_date) {
                if (!isset($events[$newdate])) $events[$newdate] = array();
                $events[$newdate][] = $A;
                list($y, $m, $d) = explode('-', $newdate);
                $newdate = Date_Calc::nextDay($d, $m, $y, '%Y-%m-%d');
            }

        }

    }   // while

    return $events;
}


function EVLIST_getEvents_json($start='', $end='', $opts=array())
{
    $events = EVLIST_getEvents($start, $end, $opts);
    $events = json_encode($events);
    return events;
}

/**
*   Replace quotes and apostrophes with HTML entities.
*   This is to prevent quotes from corrupting the HTML display while allowing
*   other HTML in event data to be displayed as such.
*
*   @param  string  $str    Original text
*   @return string          Modified version
*/
function EVLIST_noquotes($str)
{
    return str_replace(array("'", '"'), array('&apos;', '&quot;'), $str);
}


?>
