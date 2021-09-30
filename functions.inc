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
require_once "$langpath/english_utf-8.php";
if (file_exists("$langpath/custom/english_utf-8.php")) {
    include_once "$langpath/custom/english_utf-8.php";
}
$langfile = "{$_CONF['language']}.php";
if (
    $langfile != 'english_utf-8.php' &&
    file_exists("$langpath/$langfile")
) {
    $save = $LANG_EVLIST;
    unset($LANG_EVLIST);
    include_once "$langpath/$langfile";
    if (file_exists("$langpath/custom/$langfile")) {
        include_once "$langpath/custom/$langfile";
    }
    if ($LANG_EVLIST !== NULL) {
        $LANG_EVLIST = array_merge($save, $LANG_EVLIST);
    } else {
        $LANG_EVLIST = $save;
    }
    unset($save);
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
if (isset($_EV_CONF['enable_rsvp']) && $_EV_CONF['enable_rsvp']) {
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
use Evlist\Models\Status;

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
        'tables'    => array (
            'evlist_events',
            'evlist_submissions',
            'evlist_calendars',
            'evlist_categories',
            'evlist_detail',
            'evlist_lookup',
            'evlist_remlookup',
            'evlist_repeat',
            'evlist_tickets',
            'evlist_tickettypes',
            'evlist_cache',
        ),
        'groups'    => array(
            'evlist Admin',
        ),
        'features'  => array(
            'evlist.admin',
            'evlist.submit',
        ),
        'php_blocks' => array(
            'phpblock_evlist_upcoming',
            'phpblock_evlist_smallmonth',
        ),
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

    $add_option = array(
        $LANG_EVLIST['add_event'],
        EVLIST_URL . '/event.php?edit=x',
        0
    );

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

    if (!COM_isAnonUser()) {
        $count = DB_count($_TABLES['evlist_events'], 'owner_id', $_USER['uid']);
        if ($count > 0) {
            $retval[] = array(
                $LANG_EVLIST['my_events'],
                EVLIST_URL . '/index.php?view=myevents',
                $count
            );
        }
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
                WHERE status='" . Status::ENABLED . "' $permSQL");
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

    $url = '/evlist/view.php?rid=0&eid=';
    $q = '&query=' . urlencode($query);
    $sql = "SELECT e.id, d.title, d.summary as description,
                UNIX_TIMESTAMP(e.date_start1) AS date,
                e.owner_id AS uid, gl.username,
                CONCAT('$url',e.id,'$q') AS url
            FROM {$_TABLES['evlist_detail']} d
            LEFT JOIN {$_TABLES['evlist_events']} e
                ON d.ev_id = e.id
            LEFT JOIN {$_TABLES['evlist_calendars']} c
                ON c.cal_id = e.cal_id
            LEFT JOIN {$_TABLES['users']} gl
                ON e.owner_id = gl.uid
            WHERE (
                e.status = " . Status::ENABLED . " AND d.det_status = " . Status::ENABLED . "
            )
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
    //$sql .= ' GROUP BY d.det_id ';
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
    global $_CONF, $_TABLES, $LANG_EVLIST;

    switch ($op) {
    case 'tagname':
        return array('event', 'evlist_signups');
        break;
    case 'tagusage':
        return array(
            array(
                'namespace' => 'evlist',
                'usage' => 'description',
            ),
        );
        break;
    case 'desc':
        switch ($content) {
        case 'event':
        case 'evlist_signups':
            return $LANG_EVLIST['at_dscp_' . $content];
            break;
        default:
            return '';
            break;
        }
        break;
    case 'parse':
        $HTML = '';
        switch ($autotag['tag']) {
        case 'event':
            $eid = COM_applyFilter($autotag['parm1']);
            $url = COM_buildURL(EVLIST_URL . '/event.php?eid=' . $eid);
            if (empty($autotag['parm2'])) {
                $text = COM_stripslashes(
                    DB_getItem($_TABLES['evlist_detail'], 'title', "ev_id='$eid'")
                );
            } else {
                $text = $autotag['parm2'];
            }
            $HTML = '<a href="' . $url . '">' . $text . '</a>';
            break;
        case 'evlist_signups':
            $AT = new Evlist\Autotags\signups;
            $HTML = $AT->parse($autotag);
            break;
        }
        break;
    case 'parse':
        $HTML = '';
        switch ($autotag['tag']) {
        case 'event':
            $eid = COM_applyFilter($autotag['parm1']);
            $url = COM_buildURL(EVLIST_URL . '/event.php?eid=' . $eid);
            if (empty($autotag['parm2'])) {
                $text = COM_stripslashes(
                    DB_getItem($_TABLES['evlist_detail'], 'title', "ev_id='$eid'")
                );
            } else {
                $text = $autotag['parm2'];
            }
            $HTML = '<a href="' . $url . '">' . $text . '</a>';
            break;
        case 'evlist_signups':
            $HTML .= Evlist\Ticket::userList_RSVP($autotag['parm1'], $autotag['parm2']);
            break;
        }

        $content = str_replace($autotag['tagstr'], $HTML, $content);
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
        $path . '/datepicker.js',
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
 * @param   string  $where  Position (top, etc.)
 * @param   string  $page   Page currently being displayed
 * @param   string  $topic  Topic currently being displayed
 * @return  string          HTML for centerblock
 */
function plugin_centerblock_evlist($where, $page, $topic = '')
{
    return Evlist\Views\Centerblock::Render($where, $page, $topic);
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
        owner_id = '2', status = " . Status::DISABLED .
        " WHERE owner_id = '$uid'");
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
    return Evlist\Models\Syndication::getFeedNames();
}

function plugin_getfeedformats_evlist()
{
    return Evlist\Models\Syndication::getFormats();
}

function plugin_getfeedprovider_evlist($format, $A=array())
{
    $cls = '\\Evlist\\Models\\Syndication2';
    if (class_exists($cls)) {
        return new $cls($A);
    } else {
        return NULL;
    }
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
function plugin_getfeedcontent_evlist(
    $feed, &$link, &$update_data, $feedType, $feedVersion
) {
    return Evlist\Models\Syndication::getFeedcontent(
        $feed, $link, $update_data, $feedType, $feedVersion
    );
}

function plugin_getfeedtopic_evlist($tid)
{
    return Evlist\Calendar::getInstance($tid)->getName();
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
function plugin_feedupdatecheck_evlist(
    $feed, $topic, $update_data, $limit,
    $updated_type = '', $updated_topic = '', $updated_id = ''
) {
    return Evlist\Models\Syndication::feedUpdateCheck(
        $feed, $topic, $update_data, $limit,
        $updated_type, $updated_topic, $updated_id
    );
}


/**
 * Run scheduled tasks for the plugin.
 * - Send event reminders
 * - Purge cancelled events if a purge interval is set
 */
function plugin_runScheduledTask_evlist()
{
    global $_EV_CONF;

    if (!EVLIST_isCurrentVersion())  {
        return;
    }

    // Purge cancelled events, if the purge interval is > 0.
    // Otherwise events have been deleted immediately.
    if ($_EV_CONF['purge_cancelled_days'] > 0) {
        Evlist\Event::purgeCancelled();
    }

    // Send out reminders
    $Rems = Evlist\Reminder::getCurrent();
    foreach ($Rems as $R) {
        $R->Send();
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
            SET status = " . Status::ENABLED .
            " WHERE id = '$eid'");

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
        status, postmode, enable_reminders, enable_comments,
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
        // Notify other plugins, e.g. Searcher, to delete all items.
        PLG_itemDeleted('*', 'evlist');
    }

    return $enable;
}


/**
 * Receives notification of anothe plugin's deletion.
 * This handles removing events from other plugins like birthdays.
 *
 * @param   mixed   $id     Item ID
 * @param   string  $type   Plugin Name
 */
function plugin_itemdeleted_evlist($id, $type)
{
    global $_TABLES;

    if ($type == 'evlist') {
        // We already know an item was deleted....
        return;
    }

    if ($id == '*') {
        if ($type == 'lglib') {
            // If the lgLib plugin is disabled or removed, disable this plugin
            plugin_enablestatechange_evlist(false);
            DB_change($_TABLES['plugins'], 'pi_enabled', 0, 'pi_name', 'evlist');
        }
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

    $events = Evlist\Models\EventSet::create()
        ->withCategory($cat)
        ->withCalendar($cal)
        ->withUpcoming(1)
        ->withLimit($limit)
        ->withStart($_EV_CONF['_today'])
        ->withEnd($end)
        ->getEvents();
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
            $Rp->getEvent()->commentsEnabled()
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
    $file = 'config';
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
        $file .= '.html';
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
            $fields[] = 'MAX(d.title) AS title';
            break;
        case 'excerpt':
        case 'introtext':
        case 'description':
            $fields[] = 'MAX(d.summary) AS summary';
        case 'bodytext':
            $fields[] = 'MAX(d.full_description) AS full_description';
            break;
        case 'searchidx':
        case 'content':
            $fields[] = 'MAX(d.title) AS title';
            $fields[] = 'MAX(d.summary) AS summary';
            $fields[] = 'MAX(d.full_description) AS full_description';
            break;
        case 'date':
            $fields[] = 'UNIX_TIMESTAMP(MIN(r.rp_start)) AS unixtime';
            break;
        case 'search_index':
            $fields[] = "group_concat(' ', d.title) as idx_title";
            $fields[] = "group_concat(' ', d.summary) as idx_summary";
            $fields[] = "group_concat(' ', d.full_description) as idx_dscp";
            $fields[] = "group_concat(' ', d.location) as idx_location";
            $buildingIndex = true;
            break;
        case 'perms':
            $fields[] = 'MAX(e.owner_id) AS owner_id';
            $fields[] = 'MAX(e.group_id) AS group_id';
            $fields[] = 'MAX(e.perm_owner) AS perm_owner';
            $fields[] = 'MAX(e.perm_group) AS perm_group';
            $fields[] = 'MAX(e.perm_members) AS perm_members';
            $fields[] = 'MAX(e.perm_anon) AS perm_anon';
            break;
        case 'author':
            $fields[] = 'e.owner_id';
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
    /*} elseif ($buildingIndex) {
        // If building the index, the repeat ID is passed in.
        $where = " WHERE (r.rp_id = '" . DB_escapeString($id) . "') ";
        echo $where;die;*/
    } else {
        $where = " WHERE (e.id = '" . DB_escapeString($id) . "') ";
    }
    $where .= ' AND r.rp_status = ' . Status::ENABLED .
        ' AND c.cal_status = ' . Status::ENABLED;
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
            case 'author':
                $props[$p] = $A['owner_id'];
                break;
            case 'author_name':
                $props[$p] = COM_getDisplayName($A['owner_id']);
                break;
            default:
                $props[$p] = isset($A[$p]) ? $A[$p] : NULL;
                break;
            }
        }
        //$props['url'] = COM_buildUrl(EVLIST_URL . '/event.php?eid=' . $A['id'], 0);
        $props['url'] = EVLIST_URL . '/view.php?&eid=' . $A['id'];
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
        'owner_id,group_id,recur_type,recur_interval,url,show_upcoming,' .
        'time_start,time_end,enable_comments,perms'
    );
    if (!is_array($info) || count($info) == 0) return;  // No data received
    if (!isset($info['is_event']) || !$info['is_event']) return;   // not an event
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
        'status' => Status::ENABLED,
        'enable_comments' => $info['enable_comments'] ? (int)$info['enable_comments'] : -1,
        'newcat' => '',
        'date_start1' => $date,
        'date_end1' => $date,
        'tz_local' => 'on',
        'recurring' => isset($info['recur_type']) ? (int)$info['recur_type'] : 0,
        'format' => $info['recur_type'] !== NULL ? $info['recur_type'] : 0,
        'stopdate' => '',
        'skipnext' => '0',
        'weekday' => '1',
        'rec_freq' => $info['recur_interval'],
        'show_upcoming' => isset($info['show_upcoming']) && $info['show_upcoming'] ? '1' : '0',
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
    $Ev->Save($A);
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
        $installed = DB_getItem(
            $_TABLES['plugins'],
            'pi_version',
            "pi_name = 'evlist'"
        );
        $isCurrent = COM_checkVersion($installed, $_EV_CONF['pi_version']);
    }
    return $isCurrent;
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
function XEVLIST_getIcon($name, $style='')
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
