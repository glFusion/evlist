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

// allow_anon_view is set by functions.inc if global login_required is on
if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')  {
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

/*if (isset($_GET['range'])) {
    $range = COM_applyFilter($_GET['range'], true);
} elseif (isset($_POST['range'])) {
    $range = COM_applyFilter($_POST['range'], true);
} else {
    $range = COM_applyFilter(COM_getArgument('range'),true);
}*/

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
    list($year, $month, $day) = explode('-', $_EV_CONF['_today']);
    $V = Evlist\View::getView('', $year, $month, $day, $category, $calendar);
    $content .= $V->Render();
    break;

case 'day':
case 'week':
case 'month':
case 'year':
case 'list':
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
    $content = EVLIST_list_user_events();
    break;

default:
    $V = Evlist\View::getView('', 0, 0, 0);
    $content = $V->Render();
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


/**
*   Get the list of events owned by the current user
*
*   @return string      HTML for admin list
*/
function EVLIST_list_user_events()
{
    global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN, $_USER;

    USES_lib_admin();

    $retval = '';

    $header_arr = array();

    // Allow editing if the queue is not used or this is an autorized
    // submitter.
    if (!$_CONF['storysubmission'] || plugin_issubmitter_evlist()) {
        $header_arr[] = array(
            'text'  => $LANG_EVLIST['edit'],
            'field' => 'edit', 'sort' => false,
            'align' => 'center',
        );
    }
    $header_arr[] = array(
        'text'  => $LANG_EVLIST['id'],
        'field' => 'ev_id',
        'sort'  => true,
    );
    $header_arr[] = array(
        'text'  => $LANG_EVLIST['title'],
        'field' => 'title',
        'sort'  => true,
    );
    $header_arr[] = array(
        'text'  => $LANG_EVLIST['start_date'],
        'field' => 'date_start1',
        'sort'  => true,
    );
    $header_arr[] = array(
        'text'  => $LANG_EVLIST['enabled'],
        'field' => 'status',
        'sort'  => false,
        'align' => 'center',
    );
    if (!$_CONF['storysubmission'] || plugin_ismoderator_evlist()) {
        $header_arr[] = array(
            'text'  => $LANG_ADMIN['delete'],
            'field' => 'delete',
            'sort'  => false,
            'align' => 'center',
        );
    }

    $defsort_arr = array(
        'field' => 'date_start1',
        'direction' => 'DESC',
    );
    $text_arr = array(
        'has_menu'  => true,
        'has_extras'=> true,
        'title'     => $LANG_EVLIST['pi_title'].': ' . $LANG_EVLIST['events'],
        'form_url'  => EVLIST_URL . '/index.php',
        'help_url'  => '',
    );

    // Select distinct to get only one entry per event.  We can only edit/modify
    // events here, not repeats
    $sql = "SELECT DISTINCT(ev.id) as ev_id, det.title, ev.date_start1, ev.status
            FROM {$_TABLES['evlist_events']} ev
            LEFT JOIN {$_TABLES['evlist_detail']} det
                ON det.ev_id = ev.id
            WHERE owner_id = " . (int)$_USER['uid'] .
                " AND ev.det_id = det.det_id ";
    $query_arr = array(
        'table' => 'users',
        'sql' => $sql,
        'query_fields' => array('id', 'title', 'summary',
        'full_description', 'location', 'date_start1', 'status'),
    );
    $retval .= ADMIN_list('evlist', 'EVLIST_user_getEventListField',
        $header_arr, $text_arr, $query_arr, $defsort_arr);
    return $retval;
}


/**
 * Return the display value for an event field.
 *
 * @param   string  $fieldname  Name of the field
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Name-value pairs for all fields
 * @param   array   $icon_arr   Array of system icons
 * @return  string      HTML to display for the field
 */
function EVLIST_user_getEventListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;

    static $del_icon = NULL;

    switch($fieldname) {
    case 'ev_id':
        $retval = COM_createLink(
            $fieldvalue,
            COM_buildUrl(
                EVLIST_URL . '/view.php?&id=0&eid=' . $fieldvalue
            )
        );
        break;
    case 'edit':
        $retval = COM_createLink(
            '',
            EVLIST_URL . '/event.php?eid=' . $A['ev_id'] . '&amp;edit=event&from=myevents',
            array(
                'class' => 'uk-icon-edit',
            )
        );
        break;
    case 'copy':
        $retval = COM_createLink(
            '',
            EVLIST_URL . '/event.php?clone=x&amp;eid=' . $A['id'],
            array(
                'title' => $LANG_EVLIST['copy'],
                'class' => 'uk-icon-clone',
            )
        );
        break;
    case 'status':
        if ($A['status'] == '1') {
            $switch = EVCHECKED;
            $enabled = 1;
        } else {
            $switch = '';
            $enabled = 0;
        }
        $retval = "<input type=\"checkbox\" $switch value=\"1\" name=\"ev_check\"
                id=\"event_{$A['ev_id']}\"
                onclick='EVLIST_toggle(this,\"{$A['ev_id']}\",\"enabled\",".
                '"event","'.EVLIST_URL."\");' />" . LB;
        break;
    case 'delete':
        $retval = COM_createLink(
            $_EV_CONF['icons']['delete'],
            EVLIST_URL. '/actions.php?delevent=x&eid=' . $A['ev_id'] . '&from=myevents',
            array(
                'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_event']}');",
                'title' => $LANG_ADMIN['delete'],
            )
        );
        break;
    default:
        $retval = $fieldvalue;
        break;
    }
    return $retval;
}
