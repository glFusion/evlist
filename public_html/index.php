<?php
/**
 * Public entry point to the evList plugin.
 *
 * @author      Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @package     evlist
 * @version     1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion libraries */
require_once '../lib-common.php';

if (!in_array('evlist', $_PLUGINS)) {
    COM_404();
}

// Check if the current user can even view the calendar
if (!EVLIST_canView()) {
    $content = COM_siteHeader();
    $content .= SEC_loginRequiredForm();
    $content .= COM_siteFooter();
    echo $content;
    exit;
}


/*
*   MAIN
*/
COM_setArgNames(array('view','cat', 'id'));
if (isset($_GET['view'])) {
    $view = COM_applyFilter($_GET['view']);
} elseif (isset($_POST['view'])) {
    $view = COM_applyFilter($_POST['view']);
} else {
    $view = COM_applyFilter(COM_getArgument('view'));
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

if (!empty($category)) {
    $catname = DB_getItem(
        $_TABLES['evlist_categories'],
        'name',
        "id = '$category'"
    );
}

if (!empty($_REQUEST['msg'])) {
    $msg = COM_applyFilter($_REQUEST['msg'], true);
} else $msg = '';

if (isset($_GET['date']) && !empty($_GET['date'])) {
    list($year, $month, $day) = explode('-', $_GET['date']);
}
if (empty($year)) {
    $year = isset($_REQUEST['year']) ? (int)$_REQUEST['year'] : 0;
}
if (empty($month)) {
    $month = isset($_REQUEST['month']) ? (int)$_REQUEST['month'] : 0;
}
if (empty($day)) {
    $day = isset($_REQUEST['day']) ? (int)$_REQUEST['day'] : 0;
}

// If deleting events from the "myevents" page, handle them first.
if (isset($_POST['delevent']) && is_array($_POST['delevent'])) {
    foreach ($_POST['delevent'] as $eid) {
        Evlist\Event::Delete($eid);
        Evlist\Cache::clear();
    }
    echo COM_refresh(EVLIST_URL . '/index.php?view=' . $view);
}

EVLIST_setReturn(EVLIST_URL . '/index.php?view=' . $view);
$content = '';
switch ($view) {
case 'pday':
case 'pweek':
case 'pmonth':
case 'pyear':
    // Strip the leading "p" for "print"
    $v = substr($view, 1);
    $V = Evlist\View::getView($v, $year, $month, $day, $category, $calendar);
    $V->setPrint();
    echo $V->Content();
    exit;

case 'today':
    $year = $_CONF['_now']->format('Y');
    $month = $_CONF['_now']->format('m');
    $day = $_CONF['_now']->format('d');
    $V = Evlist\View::getView('', $year, $month, $day, $category, $calendar);
    $content .= $V->Render();
    break;

case 'day':
case 'week':
case 'month':
case 'year':
case 'agenda':
    $V = Evlist\View::getView($view, $year, $month, $day, $category, $calendar);
    if ($V) {
        $content .= $V->Render();
    }
    break;

case 'printtickets':
    // Print all tickets for an event, for all users
    if ($_EV_CONF['enable_rsvp']) {
        $eid = COM_sanitizeID($_GET['eid'], false);
        $doc = Evlist\Ticket::printEvent($eid);
        if ($doc !== false) {
            echo $doc;
            exit;
        } else {
            COM_setMsg($LANG_EVLIST['no_tickets_print']);
            COM_refresh(COM_buildUrl(EVLIST_URL . '/view.php?rid=0&eid=' . $eid));
        }
    } else {
        $content .= 'Function not available';
    }
    break;

case 'exporttickets':
    // Print all tickets for an event, for all users
    if ($_EV_CONF['enable_rsvp']) {
        $eid = COM_sanitizeID($_GET['eid'], false);
        $doc = Evlist\Ticket::ExportTickets($eid);
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="event-'.$eid.'.csv');
        echo $doc;
        exit;
    } else {
        $content .= 'Function not available';
    }
    break;

case 'myevents':
    if (COM_isAnonUser()) {
        // Anonymous users can't access event list
        COM_404();
        exit;
    }
    $content = Evlist\Lists\UserList::Render();
    break;

default:
    $V = Evlist\View::getView('', $year, $month, $day);
    $content = $V->Render();
    break;
}

$display = Evlist\Menu::siteHeader($LANG_EVLIST['pi_title']);
if (!empty($msg)) {
    //msg block
    $display .= COM_startBlock('','','blockheader-message.thtml');
    $display .= $LANG_EVLIST['messages'][$msg];
    $display .= COM_endBlock('blockfooter-message.thtml');
}
$display .= $content;
$display .= Evlist\Menu::siteFooter();
echo $display;

