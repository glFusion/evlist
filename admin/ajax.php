<?php
/**
 * Common AJAX functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

// This is for administrators only.  It's called by Javascript,
// so don't try to display a message
if (!plugin_isadmin_evlist()) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the evlist admin ajax function.");
    exit;
}

switch ($_POST['action']) {
case 'toggle':
    $oldval = $_POST['oldval'] == 1 ? 1 : 0;
    switch ($_POST['component']) {
    case 'category':
        switch ($_POST['type']) {
        case 'enabled':
            $newval = Evlist\Category::toggleEnabled($oldval, $_POST['id']);
            break;

         default:
            exit;
        }
        break;

    case 'calendar':
        switch ($_POST['type']) {
        case 'cal_status':
            $newval = Evlist\Calendar::toggleEnabled($oldval, $_POST['id']);
            break;
        case 'cal_ena_ical':
            $newval = Evlist\Calendar::toggleIcal($oldval, $_POST['id']);
            break;
         default:
            exit;
        }
        break;

    case 'event':
        switch ($_POST['type']) {
        case 'enabled':
            $newval = Evlist\Event::toggleEnabled($oldval, $_POST['id']);
            break;

         default:
            exit;
        }
        break;

    case 'tickettype':
        switch ($_POST['type']) {
        case 'enabled':
        case 'event_pass':
            $newval = Evlist\TicketType::Toggle($_POST['type'], $oldval, $_POST['id']);
            break;

         default:
            exit;
        }

         break;

    default:
        exit;
    }

    $response = array(
        'newval' => $newval,
        'id'    => $_POST['id'],
        'type'  => $_POST['type'],
        'component' => $_POST['component'],
        'baseurl'   => EVLIST_ADMIN_URL,
        'statusMessage' => $newval != $oldval ? $LANG_EVLIST['msg_item_updated'] : $LANG_EVLIST['msg_item_nochange'],
    );
    echo json_encode($response);
    break;
}
