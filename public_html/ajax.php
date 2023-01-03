<?php
/**
 * Common AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2022 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.8
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once __DIR__ . '/../lib-common.php';

$content = '';
$Request = Evlist\Models\Request::getInstance();

switch ($Request->getString('action')) {
case 'setStatus':
    $newval = $Request->getInt('newval');
    $oldval = $Request->getInt('oldval');
    $uid = (int)$_USER['uid'];
    if ($Request->getString('type') == 'event') {
        $newval = Evlist\Event::setEventStatus($Request->getString('id'), $newval, $oldval, $uid);
    }
    $response = array(
        'newval' => $newval,
        'id'    => $Request->getString('id'),
        'type'  => $Request->getString('type'),
        'baseurl'   => EVLIST_URL,
        'statusMessage' => $newval != $oldval ? $LANG_EVLIST['msg_item_updated'] : $LANG_EVLIST['msg_item_nochange'],
    );
    COM_errorLog("DONE");
    COM_errorLog("repsonse is " . json_encode($response));
    echo json_encode($response);
    break;

case 'savecalpref':
    $id = $Request->getInt('id');
    if (empty($id)) {
        break;
    }
    $cal_id = str_replace('cal', '', $id);
    $state = $Request->getInt('state', 1);
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

    $id = $Request->getString('id');
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
    $rp_id = $Request->getInt('rp_id');
    $status = array();
    $R = new Evlist\Reminder($rp_id);
    $status['reminder_set'] = $R->Add($Request->getInt('notice'), $Request->getString('rem_email'));
    if ($status['reminder_set']) {
        $status['message'] = sprintf($LANG_EVLIST['you_are_subscribed'], $Request->getInt('notice'));
    } else {
        $status['message'] = '';
    }
    echo json_encode($status);
    exit;
    break;

case 'delreminder':
    Evlist\Reminder::Delete($Request->getString('eid'), $Request->getInt('rp_id'), $_USER['uid']);
    echo json_encode(array('reminder_set' => false));
    exit;
    break;

case 'day':
case 'week':
case 'month':
case 'year':
case 'list':
case 'agenda':
    $day = $Request->getInt('day');
    $month = $Request->getInt('month');
    $year = $Request->getInt('year');
    $cat = $Request->getInt('cat');
    $cal = $Request->getInt('cal');
    $opt = $Request->getString('opt');
    $V = Evlist\View::getView($Request->getString('action'), $year, $month, $day, $cat, $cal, $opt);
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
    $oldval = $Request->getInt('oldval');
    switch($Request->getString('component')) {
    case 'event':
        $Ev = new Evlist\Event($Request->getString('id'));
        if ( (!plugin_ismoderator_evlist() && !$Ev->isOwner() ) || $Ev->isNew ) {
            $newval = $oldval;
            break;
        }
        switch ($Request->getString('type')) {
        case 'enabled':
            $newval = Evlist\Event::toggleEnabled($oldval, $Ev->getID());
            break;

         default:
            exit;
        }
    }
    $response = array(
        'newval' => $newval,
        'id'    => $Request->getString('id'),
        'type'  => $Request->getString('type'),
        'component' => $Request->getString('component'),
        'baseurl'   => EVLIST_URL,
        'statusMessage' => $newval != $oldval ?
                    $LANG_EVLIST['msg_item_updated'] :
                    $LANG_EVLIST['msg_item_nochange'],
    );
    echo json_encode($response);
    break;
}
