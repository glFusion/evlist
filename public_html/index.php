<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | index.php                                                                |
// |                                                                          |
// | Event listing                                                            |
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
*   Public entry point to the evList plugin
*   @author     Mark R. Evans mark AT glfusion DOT org
*   @copyright  Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Import core glFusion libraries */
require_once '../lib-common.php';

if (!in_array('evlist', $_PLUGINS)) {
    COM_404();
}

// allow_anon_view is set by functions.inc if global login_required is on
if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
    $content = COM_siteHeader();
    $content .= SEC_loginRequiredForm();
    $content .= COM_siteFooter();
    echo $content;
    exit;
}

USES_evlist_functions();
USES_evlist_views();

//var_dump($_GET);die;
/*
*   MAIN 
*/
COM_setArgNames(array('view','range','cat'));
if (isset($_GET['view'])) {
    $view = COM_applyFilter($_GET['view']);
} elseif (isset($_POST['view'])) {
    $view = COM_applyFilter($_POST['view']);
} else {
    $view = COM_applyFilter(COM_getArgument('view'));
}

/*if (empty($view)) {
    $view = isset($_EV_CONF['default_view']) ? $_EV_CONF['default_view'] : '';
}*/

if (isset($_GET['range'])) {
    $range = COM_applyFilter($_GET['range'], true);
} elseif (isset($_POST['range'])) {
    $range = COM_applyFilter($_POST['range'], true);
} else {
    $range = COM_applyFilter(COM_getArgument('range'),true);
}

if (isset($_GET['cat'])) {
    $category = COM_applyFilter($_GET['cat'], true);
} elseif (isset($_POST['cat'])) {
    $category = COM_applyFilter($_POST['cat'], true);
} else {
    $category = COM_applyFilter(COM_getArgument('cat'),true);
}

if (isset($_GET['cal'])) {
    $calendar = COM_applyFilter($_GET['cal'], true);
} elseif (isset($_POST['cal'])) {
    $calendar = COM_applyFilter($_POST['cal'], true);
} else {
    $calendar = '';
}

//$_REQUEST['event_type'] = $category;   // Hack

if (!empty($category)) {
    $catname = DB_getItem($_TABLES['evlist_categories'], 'name', 
            "id = '$category'");
}

if (!empty($_REQUEST['msg'])) {
    $msg = COM_applyFilter($_REQUEST['msg'], true);
} else $msg = '';

if (isset($_GET['date']) && !empty($_GET['date'])) {
    list($year, $month, $day) = explode('-', $_GET['date']);
}
// Fill in any missing values
/*if (empty($year))
    $year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : date('Y');
if (empty($month))
    $month = isset($_REQUEST['month']) ? (int)$_REQUEST['month'] : date('m');
if (empty($day))
    $day = isset($_REQUEST['day']) ? (int)$_REQUEST['day'] : date('d');
*/
if (empty($year))
    $year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : 0;
if (empty($month))
    $month = isset($_REQUEST['month']) ? (int)$_REQUEST['month'] : 0;
if (empty($day))
    $day = isset($_REQUEST['day']) ? (int)$_REQUEST['day'] : 0;

switch ($view) {
case 'today':
    list ($year, $month, $day) = explode('-', $_EV_CONF['_today']);
    $content = EVLIST_view('', $year, $month, $day);
    break;

case 'pday':
    $content = EVLIST_dayview($year, $month, $day, $category, $calendar, 'print');
    echo $content;
    exit;

case 'day':
    $content .= EVLIST_view('day', $year, $month, $day, $category, $calendar);
    break;

case 'pweek':
    $content = EVLIST_weekview($year, $month, $day, $category, $calendar, 'print');
    echo $content;
    exit;

case 'week':
    $content .= EVLIST_view('week', $year, $month, $day, $category, $calendar);
    break;

case 'pmonth':
    $content = EVLIST_monthview($year, $month, $day, $category, $calendar, 'print');
    echo $content;
    exit;

case 'month':
    $content .= EVLIST_view('month', $year, $month, $day, $category, $calendar);
    break;

case 'pyear':
    $tpl = 'yearview_print';
    break;

case 'year':
    $content .= EVLIST_view('year', $year, 1, 1, $category, $calendar);
    break;

case 'list':
    switch ($range) {
    case 1:         // Past events
        $block_title = $LANG_EVLIST['past_events'];
        break;
    case 3:         // Next 7 days
        $block_title = $LANG_EVLIST['this_week_events'];
        break;
    case 4:         // Next 1 month
        $block_title = $LANG_EVLIST['this_month_events'];
        break;
    default:        // Upcoming events
        $range = 2;
    case 2:
        $block_title = $LANG_EVLIST['upcoming_events'];
        break;
    }
    if (!empty($category)) {
        $block_title .= '&nbsp;/&nbsp;' . $LANG_EVLIST['category'] . 
            ':&nbsp;' . $catname;
    }

    $content .= EVLIST_calHeader($year, $month, $day, 'list', $category, 
                    $calendar, $range);
    $content .= EVLIST_listview($range, $category, $calendar, $block_title);
    break;

case 'printtickets':
    // Print all tickets for an event, for all users
    if ($_EV_CONF['enable_rsvp']) {
        USES_evlist_class_ticket();
        $eid = COM_sanitizeID($_GET['eid'], false);
        $doc = evTicket::PrintTickets($eid);
        echo $doc;
        exit;
    } else {
        $content .= 'Function not available';
    }
    break;

case 'exporttickets':
    // Print all tickets for an event, for all users
    if ($_EV_CONF['enable_rsvp']) {
        USES_evlist_class_ticket();
        $eid = COM_sanitizeID($_GET['eid'], false);
        $doc = evTicket::ExportTickets($eid);
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="event-'.$ev_id.'.csv');
        echo $doc;
        exit;
    } else {
        $content .= 'Function not available';
    }
    break;


default:
    $content = EVLIST_view('', 0, 0, 0);
    break;
}

$display = EVLIST_siteHeader($LANG_EVLIST['pi_title']);
if (!empty($msg)) {
    //msg block
    $display .= COM_startBlock('','','blockheader-message.thtml');
    $display .= $LANG_EVLIST['messages'][$msg];
    $display .= COM_endBlock('blockfooter-message.thtml');
}
$display .= $content;
$display .= EVLIST_siteFooter();
echo $display;
exit;

?>
