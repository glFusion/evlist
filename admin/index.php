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


/**
 * Import events from a CSV file into the database.
 *
 * @return  string      Completion message
 */
function EVLIST_importCSV()
{
    global $_CONF, $_TABLES, $LANG_EVLIST, $_USER;

    // Setting this to true will cause import to print processing status to
    // webpage and to the error.log file
    $verbose_import = true;

    $retval = '';

    // First, upload the file
    USES_class_upload();

    $upload = new upload ();
    $upload->setPath ($_CONF['path_data']);
    $upload->setAllowedMimeTypes(array(
        'text/plain' => '.txt, .csv',
        'application/octet-stream' => '.txt, .csv',
    ) );
    $upload->setFileNames('evlist_import_file.txt');
    $upload->setFieldName('importfile');
    if ($upload->uploadFiles()) {
        // Good, file got uploaded, now install everything
        $filename = $_CONF['path_data'] . 'evlist_import_file.txt';
        if (!file_exists($filename)) { // empty upload form
            $retval = $LANG_EVLIST['err_invalid_import'];
            return $retval;
        }
    } else {
        // A problem occurred, print debug information
        $retval .= $upload->printErrors(false);
        return $retval;
    }

    $fp = fopen($filename, 'r');
    if (!$fp) {
        $retval = $LANG_EVLIST['err_invalid_import'];
        return $retval;
    }
    $success = 0;
    $failures = 0;

    // Set owner_id to the current user and group_id to the default
    $owner_id = (int)$_USER['uid'];
    if ($owner_id < 2) $owner_id = 2;   // last resort, use Admin
    $group_id = (int)DB_getItem($_TABLES['groups'],
            'grp_id', 'grp_name="evList Admin"');
    if ($group_id < 2) $group_id = 2;  // last resort, use Root

    while (($event = fgetcsv($fp)) !== false) {
        $Ev = new Evlist\Event();
        $Ev->isNew = true;
        $i = 0;
        $A = array(
            'date_start1'   => $event[$i++],
            'date_end1'     => $event[$i++],
            'time_start1'   => $event[$i++],
            'time_end1'     => $event[$i++],
            'title'         => $event[$i++],
            'summary'       => $event[$i++],
            'full_description' => $event[$i++],
            'url'           => $event[$i++],
            'location'      => $event[$i++],
            'street'        => $event[$i++],
            'city'          => $event[$i++],
            'province'      => $event[$i++],
            'country'       => $event[$i++],
            'postal'        => $event[$i++],
            'contact'       => $event[$i++],
            'email'         => $event[$i++],
            'phone'         => $event[$i++],

            'cal_id'        => 1,
            'status'        => 1,
            'hits'          => 0,
            'recurring'     => 0,
            'split'         => 0,
            'time_start2'   => '00:00:00',
            'time_end2'     => '00:00:00',
            'owner_id'      => $owner_id,
            'group_id'      => $group_id,
        );

        if ($_CONF['hour_mode'] == 12) {
            list($hour, $minute, $second) = explode(':', $A['time_start1']);
            if ($hour > 12) {
                $hour -= 12;
                $am = 'pm';
            } elseif ($hour == 0) {
                $hour = 12;
                $am = 'am';
            } else {
                $am = 'am';
            }
            $A['start1_ampm'] = $am;
            $A['starthour1'] = $hour;
            $A['startminute1'] = $minute;

            list($hour, $minute, $second) = explode(':', $A['time_end1']);
            if ($hour > 12) {
                $hour -= 12;
                $am = 'pm';
            } elseif ($hour == 0) {
                $hour = 12;
                $am = 'am';
            } else {
                $am = 'am';
            }
            $A['end1_ampm'] = $am;
            $A['endhour1'] = $hour;
            $A['endminute1'] = $minute;
        }
        if ($A['time_start1'] == '00:00:00' && $A['time_end1'] == '00:00:00') {
            $A['allday'] = 1;
        } else {
            $A['allday'] = 0;
        }
        $msg = $Ev->Save($A);
        if (empty($msg)) {
            $successes++;
        } else {
            $failures++;
        }
    }

    return "$successes Succeeded<br />$failures Failed";
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
    'delbutton_x',
    // Views to display
    'view', 'delevent', 'importcalendar', 'clone', 'rsvp', 'calendars',
    'import', 'edit', 'editcat', 'editticket', 'tickettypes',
    'tickets',
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
    if ($cal_id < 1) break;
    $newcal = isset($_POST['newcal']) ? (int)$_POST['newcal'] : 0;
    $Cal = new Evlist\Calendar($cal_id);
    $Cal->Delete($newcal);
    break;

case 'saveevent':
    $eid = isset($_POST['eid']) && !empty($_POST['eid']) ? $_POST['eid'] : '';
    $Ev = new Evlist\Event($eid);
    $errors = $Ev->Save($_POST, empty($eid));
    if (!empty($errors)) {
        $content .= '<span class="alert"><ul>' . $errors . '</ul></span>';
        $content .= $Ev->Edit();
        $view = 'none';
    } else {
        $view = 'home';
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
    $rp_id = isset($_POST['rp_id']) && !empty($_POST['rp_id']) ? $_POST['rp_id'] : '';
    $Rp = new Evlist\Repeat($rp_id);
    $errors = $Rp->Save($_POST);
    if (!empty($errors)) {
        $content .= '<span class="alert"><ul>' . $errors . '</ul></span>';
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
    $view = 'categories';
    break;

case 'delbutton_x':
    if (isset($_POST['delevent']) && is_array($_POST['delevent'])) {
        foreach ($_POST['delevent'] as $eid) {
            Evlist\Event::Delete($eid);
        }
    }
    $view = 'events';
    break;

case 'delevent':
    $eid = isset($_REQUEST['eid']) && !empty($_REQUEST['eid']) ?
            $_REQUEST['eid'] : '';
    if ($eid != '') {
        Evlist\Event::Delete($eid);
    }
    $view = 'events';
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
    require_once EVLIST_PI_PATH . '/calendar_import.php';
    $errors = evlist_import_calendar_events();
    if ($errors == -1) {
        $content .= COM_showMessageText($LANG_EVLIST['err_cal_notavail'],
                '', true);
    } elseif ($errors > 0) {
        $content .= COM_showMessageText(
                sprintf($LANG_EVLIST['err_cal_import'], $errors), '', true);
    }
    break;

case 'import_csv':
    // Import events from CSV file
    $status = EVLIST_importCSV();
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
    echo COM_refresh(EVLIST_ADMIN_URL . '/index.php?view=calendars');
    break;

default:
    $view = $action;
    break;
}

$page = $view;      // Default for menu creation
switch ($view) {
case 'deletecal':
    $cal_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    if ($cal_id < 1) break;
    $Cal = new Evlist\Calendar($cal_id);
    $content .= $Cal->DeleteForm();
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
    $Ev->Read($_REQUEST['id'], 'evlist_submissions');
    $content .= $Ev->Edit('', 0, 'moderate');
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
    $T = new Template(EVLIST_PI_PATH . '/templates/');
    $T->set_file(array(
        'form'  => 'import.thtml',
        'instr' => 'import_csv_instr.thtml',
    ) );
    $T->parse('import_csv_instr', 'instr');
    $T->parse('output', 'form');
    $content .= $T->finish($T->get_var('output'));
    break;

case 'edit':
    $eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
    $Ev = Evlist\Event::getInstance($eid);
    $rp_id = isset($_POST['rp_id']) && !empty($_POST['rp_id']) ? $_POST['rp_id'] : '';
    $content .= $Ev->Edit('', $rp_id, 'save'.$actionval);
    break;

default:
    $content .= Evlist\Event::adminList();
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

?>
