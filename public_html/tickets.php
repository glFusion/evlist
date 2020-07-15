<?php
/**
 * Print tickets for the evList plugin.
 *
 * @copyright   Copyright (c) 2019 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.6
 * @since       v1.4.6
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion libraries */
require_once '../lib-common.php';

if (!in_array('evlist', $_PLUGINS)) {
    COM_404();
    exit;
}

// Retrieve and sanitize input variables.
COM_setArgNames(array('ev_id', 'rp_id', 'token'));

if (isset($_GET['ev_id'])) {
    $ev_id = COM_applyFilter($_GET['ev_id']);
} else {
    $ev_id = COM_getArgument('ev_id');
}
if (isset($_GET['rp_id'])) {
    $token = COM_sanitizeID($_GET['rp_id']);
} else {
    $token = COM_applyFilter(COM_getArgument('rp_id'));
}
if (isset($_GET['token'])) {
    $token = COM_sanitizeID($_GET['token']);
} else {
    $token = COM_applyFilter(COM_getArgument('token'));
}

// Print the current user's own tickets to the event
if ($_EV_CONF['enable_rsvp']) {
    if (!COM_isAnonUser()) {
        $doc = Evlist\Ticket::PrintTickets($ev_id, $rp_id, $_USER['uid']);
    } else {
        $doc = Evlist\Ticket::PrintTickets($ev_id, $rp_id, 1, $token);
    }
    echo $doc;
    exit;
} else {
    $echo .= 'Function not available';
}
?>
