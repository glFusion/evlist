<?php
/**
*   Common AJAX functions.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011-2017 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.2
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Include required glFusion common functions */
require_once dirname(__FILE__) . '/../lib-common.php';

$content = '';

switch ($_REQUEST['action']) {
case 'getloc':
    // Create an array to return so the javascript won't choke.
    $B = array(
        'id'        => '',
        'title'     => '',
        'address'    => '',
        'city'      => '',
        'state'  => '',
        'country'   => '',
        'postal'    => '',
        'lat'       => '',
        'lng'       => '',
    );

    if (!$_EV_CONF['use_locator']) {
        break;
    }
    $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ?
                    COM_sanitizeID($_REQUEST['id']) : '';
    $status = LGLIB_invokeService('locator', 'getInfo',
            array('id' => $id), $A, $svc_msg);
    if ($status == PLG_RET_OK) {
        if (!$A) {
            $A = $B;        // Use the default, empty array
            $A['id'] = $id;
        }
        // Now form the JSON return
        $A = array_merge($B, $A);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        // A date in the past to force no caching
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo json_encode($A);
    }
    break;

case 'addreminder':
    $rp_id = (int)$_REQUEST['rp_id'];
    $status = array();
    USES_evlist_class_repeat();
    $Ev = new evRepeat($rp_id);
    if (!COM_isAnonUser() && $Ev->rp_id > 0 && $Ev->Event->hasAccess(2)) {
        $username = COM_getDisplayName($_USER['uid']);
        $sql = "INSERT INTO {$_TABLES['evlist_remlookup']}
            (eid, rp_id, uid, name, email, days_notice)
        VALUES (
            '{$Ev->Event->id}',
            '{$Ev->rp_id}',
            '" . (int)$_USER['uid']. "',
            '" . DB_escapeString($username) . "',
            '" . DB_escapeString($_REQUEST['rem_email']) . "',
            '" . (int)$_REQUEST['notice']. "')";
        //COM_errorLog($sql);
        DB_query($sql, 1);
        if (!DB_error()) {
            $status['reminder_set'] = true;
        } else {
            $status['reminder_set'] = false;
        }
    }
    echo json_encode($status);
    exit;
    break;

case 'delreminder':
    $rp_id = (int)$_REQUEST['rp_id'];
    $uid = (int)$_USER['uid'];
    if (!COM_isAnonUser() && $rp_id > 0) {
        USES_evlist_class_repeat();
        $Ev = new evRepeat($rp_id);
        DB_delete($_TABLES['evlist_remlookup'],
            array('eid', 'uid', 'rp_id'),
            array($Ev->Event->id, $uid, $rp_id) );
    }
    echo json_encode(array('reminder_set' => false));
    exit;
    break;

case 'getCalDay':
    $month = (int)$_REQUEST['month'];
    $day = (int)$_REQUEST['day'];
    $year = (int)$_REQUEST['year'];
    $cat = isset($_REQUEST['cat']) ? (int)$_REQUEST['cat'] : 0;
    $cal = isset($_REQUEST['cal']) ? (int)$_REQUEST['cal'] : 0;
    $opt = isset($_REQUEST['opt']) ? $_REQUEST['opt'] : '';
    USES_evlist_class_view();
    $V = new evView_day($year, $month, $day, $cat, $cal, $opt);
    echo $V->Content();
    exit;
    break;

case 'getCalWeek':
    $month = (int)$_REQUEST['month'];
    $day = (int)$_REQUEST['day'];
    $year = (int)$_REQUEST['year'];
    $cat = isset($_REQUEST['cat']) ? (int)$_REQUEST['cat'] : 0;
    $cal = isset($_REQUEST['cal']) ? (int)$_REQUEST['cal'] : 0;
    $opt = isset($_REQUEST['opt']) ? $_REQUEST['opt'] : '';
    USES_evlist_class_view();
    $V = new evView_week($year, $month, $day, $cat, $cal, $opt);
    echo $V->Content();
    exit;
    break;

case 'getCalMonth':
    $month = (int)$_REQUEST['month'];
    $year = (int)$_REQUEST['year'];
    $cat = isset($_REQUEST['cat']) ? (int)$_REQUEST['cat'] : 0;
    $cal = isset($_REQUEST['cal']) ? (int)$_REQUEST['cal'] : 0;
    $opt = isset($_REQUEST['opt']) ? $_REQUEST['opt'] : '';
    USES_evlist_class_view();
    $V = new evView_month($year, $month, 1, $cat, $cal, $opt);
    echo $V->Content();
    exit;
    break;

case 'getCalYear':
    $year = (int)$_REQUEST['year'];
    $cat = isset($_REQUEST['cat']) ? (int)$_REQUEST['cat'] : 0;
    $cal = isset($_REQUEST['cal']) ? (int)$_REQUEST['cal'] : 0;
    $opt = isset($_REQUEST['opt']) ? $_REQUEST['opt'] : '';
    USES_evlist_class_view();
    $V = new evView_year($year, 1, 1, $cat, $cal, $opt);
    echo $V->Content();
    exit;
    break;

case 'toggle':
    // Toggle the enabled flag for an event or other item.
    // This is the same as the admin ajax function and takes the same $_REQUEST
    // parameters, but checks that the user is the event owner or other
    // authorized user before acting.
    $oldval = $_POST['oldval'] == 1 ? 1 : 0;
    switch($_POST['component']) {
    case 'event':
        USES_evlist_class_event();
        $Ev = new evEvent($_POST['id']);
        if (!plugin_ismoderator_evlist() || !$Ev->isOwner() || $Ev->isNew) {
            $newval = $oldval;
            break;
        }
        switch ($_POST['type']) {
        case 'enabled':
            $newval = evEvent::toggleEnabled($oldval, $_POST['id']);
            break;

         default:
            exit;
        }
    }
    $response = array(
        'newval' => $newval,
        'id'    => $_POST['id'],
        'type'  => $_POST['type'],
        'component' => $_POST['component'],
        'baseurl'   => EVLIST_URL,
        'statusMessage' => $newval != $oldval ? $LANG_EVLIST['msg_item_updated'] : $LANG_EVLIST['msg_item_nochange'],
    );
    echo json_encode($response);
    break;
}

?>
