<?php
/**
 * Common AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once __DIR__ . '/../lib-common.php';

$content = '';

switch ($_POST['action']) {
case 'savecalpref':
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        break;
    }
    $cal_id = str_replace('cal', '', $_POST['id']);
    $state = isset($_POST['state']) ? (int)$_POST['state'] : 1;
    $cals = SESS_getVar('evlist.calshowpref');
    if (empty($cals)) $cals = array();
    $cals[$cal_id] = $state;
    SESS_setVar('evlist.calshowpref', $cals);
    break;

case 'getloc':
    if (!$_EV_CONF['use_locator']) {
        break;
    }

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

    $id = isset($_POST['id']) && !empty($_POST['id']) ?
                    COM_sanitizeID($_POST['id']) : '';
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
    $rp_id = (int)$_POST['rp_id'];
    $status = array();
    $R = new Evlist\Reminder($_POST['rp_id']);
    $status['reminder_set'] = $R->Add($_POST['notice'], $_POST['rem_email']);
    if ($status['reminder_set']) {
        $status['message'] = sprintf($LANG_EVLIST['you_are_subscribed'], $_POST['notice']);
    } else {
        $status['message'] = '';
    }
    echo json_encode($status);
    exit;
    break;

case 'delreminder':
    $R = new Evlist\Reminder($_POST['rp_id']);
    $R->Delete();
    echo json_encode(array('reminder_set' => false));
    exit;
    break;

case 'day':
case 'week':
case 'month':
case 'year':
case 'list':
    $day = isset($_POST['day']) ? (int)$_POST['day'] : 0;
    $month = isset($_POST['month']) ? (int)$_POST['month'] : 0;
    $year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
    $cat = isset($_POST['cat']) ? (int)$_POST['cat'] : 0;
    $cal = isset($_POST['cal']) ? (int)$_POST['cal'] : 0;
    $opt = isset($_POST['opt']) ? $_POST['opt'] : '';
    $V = Evlist\View::getView($_POST['action'], $year, $month, $day, $cat, $cal, $opt);
    $retval = array(
        'content' => $V->Content(),
        'header' => $V->Header(),
    );
    echo json_encode($retval);
    exit;
    break;

case 'toggle':
    // Toggle the enabled flag for an event or other item.
    // This is the same as the admin ajax function and takes the same $_REQUEST
    // parameters, but checks that the user is the event owner or other
    // authorized user before acting.
    $oldval = isset($_POST['oldval']) && $_POST['oldval'] == 1 ? 1 : 0;
    switch($_POST['component']) {
    case 'event':
        $Ev = new Evlist\Event($_POST['id']);
        if ( (!plugin_ismoderator_evlist() && !$Ev->isOwner() ) || $Ev->isNew ) {
            $newval = $oldval;
            break;
        }
        switch ($_POST['type']) {
        case 'enabled':
            $newval = Evlist\Event::toggleEnabled($oldval, $_POST['id']);
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
        'statusMessage' => $newval != $oldval ?
                    $LANG_EVLIST['msg_item_updated'] :
                    $LANG_EVLIST['msg_item_nochange'],
    );
    echo json_encode($response);
    break;
}

?>
