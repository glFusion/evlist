<?php
/**
 * Administration entry point for the evList plugin.
 *
 * Based on the evList Plugin for Geeklog CMS by Alford Deeley.
 *
 * @author      Lee Garner <lee AT leegarner DOT com>
 * @author      Mark R. Evans <mark AT glfusion DOT org>
 * @author      Alford Deeley <ajdeeley AT sumitpages.ca>
 * @copyright   Copyright (c) 2011-2019 Lee Garner <lee AT leegarner DOT com>
 * @copyright   Copyright (c) 2008-2010 Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2007 Alford Deeley <ajdeeley AT sumitpages.ca>
 * @package     evlist
 * @version     v1.4.6
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include glFusion core libraries */
require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!in_array('evlist', $_PLUGINS) || !plugin_ismoderator_evlist()) {
    COM_404();
    exit;
}


// Main function
$expected = array(
    // Actions to perform
    'savecal', 'editcal', 'moderate', 'saveevent', 'saverepeat',
    'savefuturerepeat',
    'deletecal', 'delcalconfirm', 'approve', 'disapprove',
    'categories', 'updateallcats', 'delcat', 'savecat',
    'saveticket', 'deltickettype', 'delticket', 'printtickets',
    'tickreset_x', 'tickdelete_x', 'exporttickets',
    'import_csv', 'import_cal', 'movecal',
    'delbutton_x', 'tt_move',
    // Views to display
    'view', 'delevent', 'importcalendar', 'clone', 'rsvp', 'calendars',
    'import', 'edit', 'editcat', 'editticket', 'tickettypes',
    'tickets', 'repeats', 'cxrepeat', 'delcxrepeat',
);
$action = 'view';
$actionval = '';
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

if (isset($_REQUEST['msg'])){
    $msg = COM_applyFilter($_REQUEST['msg'], true);
} else {
    $msg = '';
}
$content = '';

switch ($action) {
case 'edit':
    $view = 'edit';
    break;

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

case 'delcalconfirm':
    $view = 'calendars';
    if (!isset($_POST['confirmdel']) || $_POST['confirmdel'] != '1') {
        break;
    }
    $cal_id = isset($_POST['cal_id']) ? (int)$_POST['cal_id'] : 0;
    if ($cal_id != 1) {
        $newcal = isset($_POST['newcal']) ? (int)$_POST['newcal'] : 0;
        $Cal = new Evlist\Calendar($cal_id);
        $Cal->Delete($newcal);
    }
    COM_refresh(EVLIST_ADMIN_URL . '/index.php?calendars');
    break;

case 'saveevent':
    $eid = (isset($_POST['eid']) && !empty($_POST['eid'])) ? $_POST['eid'] : '';
    $Ev = new Evlist\Event($eid);
    $status = $Ev->asSubmission(empty($eid))->Save($_POST);
    if (!$status) {
        $content .= $Ev->Edit();
        $view = 'none';
    } else {
        if ($Ev->isSubmission()) {
            COM_setMsg($LANG_EVLIST['messages'][9]);
        } else {
            COM_setMsg($LANG_EVLIST['messages'][2]);
        }
        echo COM_refresh(EVLIST_ADMIN_URL . '/index.php');
    }
    break;

case 'saverepeat':
case 'savefuturerepeat':
    $rp_id = (isset($_POST['rp_id']) && !empty($_POST['rp_id'])) ? $_POST['rp_id'] : '';
    $Rp = new Evlist\Repeat($rp_id);
    $status = $Rp->Save($_POST);
    if (!$status) {
        $content .= $Rp->Edit();
        $view = 'none';
    } else {
        if ($action == 'savefuturerepeat') {
            $Rp->updateFuture();
        }
        COM_setMsg($LANG_EVLIST['messages'][2]);
        COM_refresh(EVLIST_ADMIN_URL . '/index.php');
    }
    break;

case 'savecal':
    $cal_id = isset($_POST['cal_id']) ? $_POST['cal_id'] : 0;
    $Cal = new Evlist\Calendar($cal_id);
    $status = $Cal->Save($_POST);
    $view = 'calendars';
    break;

case 'savecat':
    $C = new Evlist\Category($_POST['id']);
    $status = $C->Save($_POST);
    $view = 'categories';
    break;

case 'saveticket':
    if ($_EV_CONF['enable_rsvp']) {
        $TT = new Evlist\TicketType($_POST['tt_id']);
        $status = $TT->Save($_POST);
        $view = 'tickettypes';
    } else {
        $view = '';
    }
    break;

case 'deltickettype':
    Evlist\TicketType::Delete($actionval);
    COM_refresh(EVLIST_ADMIN_URL . '/index.php?tickettypes');
    break;

case 'delcat':
    $cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($cat_id > 0) {
        Evlist\Category::Delete($cat_id);
    }
    COM_refresh(EVLIST_ADMIN_URL . '/index.php?categories');
    break;

case 'delbutton_x':
    if (isset($_POST['delevent']) && is_array($_POST['delevent'])) {
        foreach ($_POST['delevent'] as $eid) {
            Evlist\Event::Delete($eid);
        }
    }
    COM_refresh(EVLIST_ADMIN_URL . '/index.php?events');
    break;

case 'delcxrepeat':
    // Permanently delete a cancelled repeat.
    // We'll cheat here and set purge_days to zero to force immediate deletion,
    // then fall through to call Repeat::Delete()
    $_EV_CONF['purge_cancelled_days'] = 0;
case 'cxrepeat':
    Evlist\Repeat::getInstance($_GET['rp_id'])->Delete();
    COM_refresh(EVLIST_ADMIN_URL . '/index.php?repeats=x&eid=' . $_GET['ev_id']);
    break;

case 'delevent':
    $eid = isset($_REQUEST['eid']) && !empty($_REQUEST['eid']) ?
            $_REQUEST['eid'] : '';
    if ($eid != '') {
        Evlist\Event::Delete($eid);
    }
    COM_refresh(EVLIST_ADMIN_URL . '/index.php');
    break;

case 'disapprove';
    // Delete a submission.  We'll just do this manually since there's
    // not much to it.
    $id = isset($_POST['eid']) ? COM_sanitizeId($_POST['eid']) : '';
    if ($id != '') {
        DB_delete($_TABLES['evlist_submissions'], 'id', $id);
        DB_delete($_TABLES['evlist_detail'], 'ev_id', $id);
        DB_delete($_TABLES['evlist_lookup'], 'eid', $id);
    }
    echo COM_refresh($_CONF['site_admin_url'].'/moderation.php');
    exit;
    break;

case 'approve':
    // Invoke the core moderation approval functions.
    // It'd be nice if the MODERATE functions weren't in moderate.php
    $id = isset($_POST['eid']) ? COM_sanitizeId($_POST['eid']) : '';
    if ($id != '') {
        list($key, $table, $fields, $submissiontable) =
            plugin_moderationvalues_evlist();
        DB_copy($table,$fields,$fields,$submissiontable,$key,$id);
        plugin_moderationapprove_evlist($id);
    }
    echo COM_refresh($_CONF['site_admin_url'].'/moderation.php');
    exit;
    break;

case 'view':
    $view = $actionval;
    break;

case 'delrsvp':
    if (is_array($_POST['delrsvp'])) {
        foreach ($_POST['delrsvp'] as $rsvp_id) {
            DB_delete($_TABLES['evlist_rsvp'], 'rsvp_id', $rsvp_id);
        }
    }
    $view = 'rsvp';
    break;

case 'import_cal':
    $errors = Evlist\Util\Import::do_calendar();
    if ($errors == -1) {
        $content .= COM_showMessageText(
            $LANG_EVLIST['err_cal_notavail'],
            '',
            true
        );
    } elseif ($errors > 0) {
        $content .= COM_showMessageText(
            sprintf($LANG_EVLIST['err_cal_import'], $errors),
            '',
            true
        );
    }
    break;

case 'import_csv':
    // Import events from CSV file
    $status = Evlist\Util\Import::do_csv();
    $content .= COM_showMessageText($status, '', true, 'error');
    $view = '';
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
        header('Content-Disposition: attachment; filename="event-'.$eid.'.csv');
        echo $doc;
        exit;
    } else {
        $content .= 'Function not available';
    }
    break;

case 'movecal':
    Evlist\Calendar::moveRow($_GET['id'], $actionval);
    COM_refresh(EVLIST_ADMIN_URL . '/index.php?view=calendars');
    break;

case 'tt_move':
    $tt_id = EV_getVar($_GET, 'tt_id', 'integer');
    if ($tt_id > 0) {
        Evlist\TicketType::moveRow($tt_id, $actionval);
    }
    COM_refresh(EVLIST_ADMIN_URL . '/index.php?view=tickettypes');
    break;

default:
    $view = $action;
    break;
}

$page = $view;      // Default for menu creation
switch ($view) {
case 'deletecal':
    $cal_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    if ($cal_id != 1) {
        $Cal = new Evlist\Calendar($cal_id);
        $content .= $Cal->DeleteForm();
    }
    break;

case 'editcal':
    $Cal = new Evlist\Calendar($actionval);
    $content .= $Cal->Edit();
    break;

case 'calendars':
    $content .= Evlist\Calendar::adminList();
    break;

case 'moderate':
    $Ev = new Evlist\Event();
    $Ev->asSubmission()->Read($_REQUEST['id']);
    $Editor = new Evlist\Views\Editor;
    $content .= $Editor->withEvent($Ev)->Render();
    break;

case 'categories':
    $content .= Evlist\Category::adminList();
    break;

case 'tickettypes':
    if ($_EV_CONF['enable_rsvp']) {
        $content .= Evlist\TicketType::adminList();
    }
    break;

case 'tickets':
    $ev_id = isset($_GET['ev_id']) ? $_GET['ev_id'] : '';
    $content .= Evlist\Ticket::adminList($ev_id);
    break;

case 'editcat':
    $C = new Evlist\Category($actionval);
    $content .= $C->Edit();
    break;

case 'editticket':
    if ($_EV_CONF['enable_rsvp']) {
        $Tic = new Evlist\TicketType($actionval);
        $content .= $Tic->Edit();
    }
    break;

case 'rsvp':
    $rp_id = 0;
    if (isset($_POST['rp_id']) && !empty($_POST['rp_id'])) {
        $rp_id = $_POST['rp_id'];
    } elseif (isset($_GET['rp_id']) && !empty($_GET['rp_id'])) {
        $rp_id =  $_GET['rp_id'];
    }
    if ($rp_id > 0) {
        $content .= Evlist\Ticket::adminList($rp_id);
    }
    break;

case 'import':
    $content .= Evlist\Util\Import::showForm();
    break;

case 'edit':
    $eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
    $Ev = Evlist\Event::getInstance($eid);
    $Editor = new Evlist\Views\Editor;
    $rp_id = (isset($_POST['rp_id']) && !empty($_POST['rp_id'])) ? $_POST['rp_id'] : '';
    if (!empty($rp_id)) {
        $Editor->withRepeat(Evlist\Repeat::getIntance($rp_id));
    } else {
        $Editor->withEvent($Ev);
    }
    $content .= $Editor->Render();
    break;

case 'repeats':
    $eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
    $content .= Evlist\Repeat::adminList($eid);
    break;

default:
    $content .= Evlist\Lists\AdminList::Render();
    $page = 'events';
    break;
}

$display = COM_siteHeader();

if (!empty($msg)) {
    //msg block
    $display .= COM_startBlock($LANG_EVLIST['messages'][6],'','blockheader-message.thtml');
    $display .= $LANG_EVLIST['messages'][$msg];
    $display .= COM_endBlock('blockfooter-message.thtml');
}

$display .= Evlist\Menu::Admin($page);
$display .= $content;
$display .= COM_siteFooter();

echo $display;
