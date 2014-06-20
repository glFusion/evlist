<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | event.php                                                                |
// |                                                                          |
// | Event management routines                                                |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008=2010 by the following authors:                        |
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
*   Event display function for the evList plugin
*
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
    exit;
}

// If global loginrequired is set, override the plugin's setting
if ($_CONF['loginrequired'] == 1) $_EV_CONF['allow_anon_view'] = '0';
if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1') {
    $display = EVLIST_siteHeader();
    $display .= SEC_loginRequiredForm();
    $display .= EVLIST_siteFooter();
    echo $display;
    exit;
}

// Import plugin-specific function library
USES_evlist_functions();
USES_evlist_views();

/*
 * Main function
 */
$expected = array('edit', 'cancel',
    'editfuture',
    'saveevent', 'saverepeat', 'savefuturerepeat',
    'delevent', 'delrepeat', 'delfuture',
    'savereminder', 'delreminder', 'clone',
    'register', 'cancelreg', 'search', 'print',
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

// Set variables that are almost always used
$rp_id = isset($_POST['rp_id']) ? (int)$_POST['rp_id'] :
        isset($_GET['rp_id']) ? (int)$_GET['rp_id'] : 0;
//$eid = isset($_POST['eid']) ? COM_applyFilter($_POST['eid']) :
//        isset($_GET['eid']) ? COM_applyFilter($_GET['eid']) : '';
$cal_id = isset($_GET['cal']) ? (int)$_GET['cal'] : 0;
$cat_id = isset($_GET['cat']) ? $_GET['cat'] : '';

$pagetitle = '';        // Default to empty page title
$template = '';         // Use the default template if none provided

// Get the system message, if any.  There should only be a message if our
// next action is a view, since an action might override the message value.
// We need message queueing!
if (isset($_GET['msg'])) {
    $msg = COM_applyFilter($_GET['msg'], true);
} else {
    $msg = '';
}

//echo $action;die;
switch ($action) {
case 'edit':
case 'view':
case 'clone':
case 'print':
    $view = $action;
    break;

case 'search':
    // search result returned.  eid value is the event ID, not the repeat
    $view = 'home';         // default on failure
    if (!empty($_GET['eid'])) {
        // Default action, view the calendar or event
        $eid = COM_sanitizeID($_GET['eid'], false);

        $sql = "SELECT rp.rp_id
                FROM {$_TABLES['evlist_repeat']} rp
                WHERE rp.rp_ev_id = '$eid'
                AND rp.rp_date_start >= '{$_EV_CONF['_today']}'
                ORDER BY rp.rp_date_start ASC
                LIMIT 1";
        $res = DB_query($sql);
        if ($res && DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
            $eid = $A['rp_id'];
            $view = 'view';
        }
    }
    break;

case 'saverepeat':
case 'savefuturerepeat':
    $rp_id = isset($_POST['rp_id']) ? (int)$_POST['rp_id'] : 0;
    if ($rp_id > 0) {
        USES_evlist_class_repeat();
        $R = new evRepeat($rp_id);
        $errors = $R->Save($_POST); // save detail info
        if (!empty($errors)) {
            $content .= '<span class="alert"><ul>' . $errors . '</ul></span>';
            $content .= $R->Edit();
            $view = 'none';
        } elseif ($action == 'savefuturerepeat') {
            // Update all future repeat records.
            $det_id = $R->det_id;
            $sql = "UPDATE {$_TABLES['evlist_repeat']}
                SET rp_det_id = '{$R->det_id}'
                WHERE rp_date_start >= '{$R->date_start}'
                    AND rp_ev_id = '{$R->ev_id}'";
            DB_query($sql);
        }
    }
    if (isset($_GET['admin'])) {
        echo COM_refresh(EVLIST_ADMIN_URL);
        exit;
    }
    break;

 case 'saveevent':
    USES_evlist_class_event();
    $eid = isset($_POST['eid']) && !empty($_POST['eid']) ? $_POST['eid'] : '';
    $table = empty($eid) ? 'evlist_submissions' : 'evlist_events';
    $Ev = new evEvent($eid);
    $errors = $Ev->Save($_POST, $table);
    if (!empty($errors)) {
        $content .= '<span class="alert"><ul>' . $errors . '</ul></span>';
        $content .= $Ev->Edit();
        $view = 'none';
    } else {
        $view = 'home';
        if ($Ev->table == 'evlist_submissions')
            $msg = 9; // Submission confirmation
        else
            $msg = 2;
    }
    break;

case 'delevent':
    USES_evlist_class_event();
    $eid = isset($_REQUEST['eid']) && !empty($_REQUEST['eid']) ? 
            $_REQUEST['eid'] : '';
    if ($eid != '') {
        evEvent::Delete($eid);
    }
    $view = 'home';
    break;

case 'delrepeat':
    USES_evlist_class_repeat();
    $rp_id = isset($_REQUEST['rp_id']) && !empty($_REQUEST['rp_id']) ? 
            (int)$_REQUEST['rp_id'] : 0;
    if ($rp_id > 0) {
        $R = new evRepeat($rp_id);
        $R->Delete();
    }
    $view = 'home';
    break;

case 'delfuture':
    // Delete the selected and all future occurances.
    USES_evlist_class_repeat();
    $R = new evRepeat($_REQUEST['rp_id']);
    $R->DeleteFuture();
    break;

// DEPRECATED
case 'savereminder':
    USES_evlist_class_repeat();
    $Ev = new evRepeat($_POST['rp_id']);
    if (!COM_isAnonUser() && $Ev->rp_id > 0 && $Ev->Event->hasAccess()) {
        // eid is normally main event id.  This keeps us from being redirected
        // to index.php after saving the reminder.
        $eid = (int)$_POST['rp_id'];
        $sql = "INSERT INTO {$_TABLES['evlist_remlookup']}
            (eid, rp_id, uid, email, days_notice)
        VALUES (
            '{$Ev->Event->id}', 
            '{$Ev->rp_id}', 
            '" . (int)$_USER['uid']. "',
            '" . DB_escapeString($_POST['rem_email']) . "',
            '" . (int)$_POST['notice']. "')";
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            // In case of a duplicate submission or something
            $msg = 23;      // general error message
        }
    }
    break;

// DEPRECATED
case 'delreminder':
    USES_evlist_class_repeat();
    $Ev = new evRepeat($_POST['rp_id']);
    if (!COM_isAnonUser() && $Ev->rp_id > 0) {
        DB_delete($_TABLES['evlist_remlookup'],
            array('eid', 'uid', 'rp_id'),
            array($_POST['eid'], $_USER['uid'], $_POST['rp_id']) );
    }
    $eid = (int)$_POST['rp_id'];
    break;

case 'register':
    if ($rp_id < 1 || COM_isAnonUser()) {
        // Anonymous users can't register
        break;
    }
    USES_evlist_class_repeat();
    $Ev = new evRepeat($rp_id);
    $msg = $Ev->Register();
    if ($msg == 0) $msg = 24;   // Set "success" message.
    $eid = $rp_id;      // Set eid for the event view
    break;

case 'cancelreg':
    USES_evlist_class_repeat();
    $Ev = new evRepeat($rp_id);
    $msg = $Ev->CancelRegistration();
    if ($msg == 0) $msg = 25;   // Set "success" message.
    $eid = $rp_id;      // Set eid for the event view
    break;
 
case 'cancel':
    echo COM_refresh($_CONF['site_admin_url'].'/moderation.php');
    break;

}

switch ($view) {
case 'edit':
    $admin = isset($_GET['admin']) ? true : false;
    switch ($actionval) {
    case 'repeat':
    case 'futurerepeat':
        if (isset($_REQUEST['rp_id'])) {
            USES_evlist_class_repeat();
            $rp_id = (int)$_GET['rp_id'];
            $Ev = new evRepeat($rp_id);
            $Ev->Event->AdminMode = $admin;
            $content .= $Ev->Edit(0, $actionval);
        }
        break;
    case 'event':
    default:
        USES_evlist_class_event();
        $Ev = new evEvent($_REQUEST['eid']);
        $Ev->AdminMode = $admin;
        $content .= $Ev->Edit('', $rp_id, 'save'.$actionval);
        break;
    }
    break;

case 'clone':
    if (isset($_GET['eid'])) {
        USES_evlist_class_event();
        $Ev = new evEvent($_GET['eid']);
        if ($Ev->id == '')      // Event not found
            break;
        // Now prep it to be saved as a new record
        $Ev->id = '';
        $Ev->isNew = true;
        $content .= $Ev->Edit();
    }
    break;

case 'none':
    // Don't display anything, it was already taken care of
    break;

case 'home':
    if (!empty($msg)) {
        $msg_url = "?msg=$msg";
    }
    echo COM_refresh(EVLIST_URL . '/index.php' . $msg_url);
    exit;

case 'print':
    $rp_id = isset($_GET['rp_id']) ? $_GET['rp_id'] : '';
    if (!empty($rp_id)) {
        USES_evlist_class_repeat();
        $Rep = new evRepeat($rp_id);
        $pagetitle = COM_stripslashes($Rep->Event->title);
        echo $Rep->Detail('', '', 'event_print');
        exit;
    } else {
        // Shouldn't be in this file without an event ID to display or edit
        echo COM_refresh(EVLIST_URL . '/index.php');
        exit;
    }
    break; 

case 'view':
default:
    if (empty($eid)) {
        // Default action, view the calendar or event
        COM_setArgNames(array('eid','ts','range','cat'));
        $eid = COM_sanitizeID(COM_getArgument('eid'),false);
    }

    if (!empty($eid)) {
        USES_evlist_class_repeat();
        $Rep = new evRepeat($eid);
        $pagetitle = COM_stripslashes($Rep->Event->title);
        if ($view == 'print') {
            $template = 'event_print';
            $query = '';
        }
        $query = isset($_GET['query']) ? $_GET['query'] : '';
        $content .= $Rep->Detail('', $query, $template);
    } else {
        // Shouldn't be in this file without an event ID to display or edit
        echo COM_refresh(EVLIST_URL . '/index.php');
        exit;
    }

    break; 

}

$display = EVLIST_siteHeader($pagetitle);
$display .= EVLIST_calHeader(date('Y'), date('m'), date('d'), 'detail',
                $cat_id, $cal_id);

if (!empty($msg)) {
    $display .= COM_startBlock($LANG_EVLIST['alert'],'','blockheader-message.thtml');
    $display .= $LANG_EVLIST['messages'][$msg];
    $display .= COM_endBlock('blockfooter-message.thtml');
}

$display .= $content;
$display .= EVLIST_siteFooter();
echo $display;

?>
