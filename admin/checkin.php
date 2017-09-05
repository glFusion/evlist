<?php
/**
*   Check in a registrant to an event.
*   Takes arguments "tic" for ticket ID and "rp" for occurrence ID.
*   Intended to be called via QRCode but can be used directly from
*   a browser.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2015 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

require_once '../../../lib-common.php';

// qrcode browser might send a HEAD first, causing dup db entries.
// only act on a GET
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo '<html><body>OK</body></html>';
    exit;
}

$tic_id = isset($_GET['tic']) ? $_GET['tic'] : '';
$rp_id = isset($_GET['rp']) ? $_GET['rp'] : '';
if (!empty($tic_id) && !empty($rp_id)) {
    $Ticket = new Evlist\Ticket($tic_id);
    if ($Ticket->tic_id != $tic_id) {
        $color = "red";
        $text = "Invalid Ticket";
    } else {
        $status = $Ticket->Checkin($rp_id);
        if ($status == 0) {
            $color = "green";
            $text = "Valid Checkin";
        } else {
            $color = "red";
            $text = $LANG_EVLIST['messages'][$status];
        }
    }
} else {
    $color = "red";
    $text = "Missing Ticket";
}
$timestamp = time();
$T = new Template(EVLIST_PI_PATH . '/templates');
$T->set_file('response', 'checkin.thtml');
$T->set_var(array(
    'color' => $color,
    'text'  => $text,
    'timestamp' => $timestamp,
) );
$T->parse('output', 'response');
echo $T->finish($T->get_var('output'));
/*$content = "<html><body>
<h1 style=\"font-size:60px;color:$color;\">$text</h1>
</span><span style=\"font-size:12px;\">$timestamp</span>
</body></html>";
echo $content;*/
exit;
?>
