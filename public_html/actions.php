<?php
/**
*   Handle actions that can be performed by event owners
*   @author     Lee P. Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee P. Garner
*   @package    evlist
*   @version    1.4.2
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Include glFusion core libraries */
require_once '../lib-common.php';


/*
*   Main function
*/
$expected = array(
    // Actions to perform
    'editevent', 'saveevent', 'delevent',
    'delticket', 'printtickets',
    'tickreset_x', 'tickdelete_x', 'exporttickets',
);
$action = 'view';
$view = '';
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

switch ($action) {
case 'tickdelete_x':
    if (is_array($_POST['delrsvp'])) {
        Evlist\Ticket::Delete($_POST['delrsvp']);
    }
    COM_refresh($_CONF['site_url'] . '/evlist/event.php?eid=' . $_POST['ev_id']);
    exit;

case 'tickreset_x':
    if (is_array($_POST['delrsvp'])) {
        Evlist\Ticket::Reset($_POST['delrsvp']);
    }
    COM_refresh($_CONF['site_url'] . '/evlist/event.php?eid=' . $_POST['ev_id']);
    exit;

case 'saveevent':
    $eid = isset($_POST['eid']) && !empty($_POST['eid']) ? $_POST['eid'] : '';
    $table = empty($eid) ? 'evlist_submissions' : 'evlist_events';
    $Ev = new Evlist\Event($eid);
    $errors = $Ev->Save($_POST, $table);
    if (!empty($errors)) {
        $content .= '<span class="alert"><ul>' . $errors . '</ul></span>';
        $content .= $Ev->Edit();
        $view = 'none';
    } else {
        $view = 'home';
        if ($Ev->table == 'evlist_submissions') {
            COM_setMsg($LANG_EVLIST['messages'][9]);
        } else {
            COM_setMsg($LANG_EVLIST['messages'][2]);
        }
    }
    echo COM_refresh(EVLIST_URL . '/index.php');
    break;

case 'delevent':
    $eid = isset($_REQUEST['eid']) && !empty($_REQUEST['eid']) ? 
            $_REQUEST['eid'] : '';
    if ($eid != '') {
        Evlist\Event::Delete($eid);
    }
    $view = 'events';
    break;

case 'view':
    $view = $actionval;
    break;

case 'printtickets':
    // Print all tickets for an event, for all users
    if ($_EV_CONF['enable_rsvp']) {
        $eid = COM_sanitizeID($_GET['eid'], false);
        $doc = Evlist\Ticket::PrintTickets($eid);
        echo $doc;
        exit;
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
        header('Content-Disposition: attachment; filename="event-'.$ev_id.'.csv');
        echo $doc;
        exit;
    } else {
        $content .= 'Function not available';
    }
    break;

default:
    $view = $action;
    break;
}

$page = $view;      // Default for menu creation
switch ($view) {
case 'tickets':
    $ev_id = isset($_GET['ev_id']) ? $_GET['ev_id'] : '';
    $content .= EVLIST_adminlist_tickets($ev_id);
    break;

case 'editticket':
    if ($_EV_CONF['enable_rsvp']) {
        $Tic = new Evlist\TicketType($actionval);
        $content .= $Tic->Edit();
    }
    break;

case 'editevent':
    $Ev = new Evlist\Event($_REQUEST['eid']);
    $content .= $Ev->Edit('', $rp_id, 'save'.$actionval);
    break;
}

echo COM_refresh(EVLIST_getReturn());
?>
