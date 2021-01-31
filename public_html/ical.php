<?php
/**
*   ICal export function for the evList plugin
*
*   @author     Lee P. Garner <lee@leegarner.com>
*   @copyright  Copyright (c) Lee P. Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.3.2
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Include core glFusion libraries */
require_once '../lib-common.php';

// Set up the period to export- now + 1 year
$start = $_EV_CONF['_today'];
list($y, $m, $d) = explode('-', $start);
$end = date('Y-m-d', mktime(0, 0, 0, $m, $d, $y+1));

$opts = array('ical' => 1);
if (isset($_GET['cal']) && !empty($_GET['cal'])) {
    // Get only a specific calendar
    $opts['cal'] = (int)$_GET['cal'];
}
if (isset($_GET['rp_id']) && !empty($_GET['rp_id'])) {
    // Get a single event
    $opts['rp_id'] = (int)$_GET['rp_id'];
}

$events = EVLIST_getEvents($start, $end, $opts);
$ical = '';
$space = '      ';

$rp_shown = array();
foreach ($events as $day) {
    foreach ($day as $event) {

        // Check if this repeat is already shown.  We only want multi-day
        // events included once instead of each day
        if (array_key_exists($event['rp_id'], $rp_shown)) {
            continue;
        }
        $rp_shown[$event['rp_id']] = 1;
        $dtstart = (new \Date($event['rp_start'], $_CONF['timezone']))
            ->format('Ymd\THis\Z', false);
        $dtend = (new \Date($event['rp_end'], $_CONF['timezone']))
            ->format('Ymd\THis\Z', false);
        $summary = $event['title'];
        $permalink = COM_buildURL(EVLIST_URL . '/event.php?eid='. $event['rp_id']);
        $uuid = $event['rp_ev_id'] . '-' . $event['rp_id'];

        // Get the description. Prefer the text Summary, then HTML fulltext
        // Since a description is required, re-use the title if nothing else.
        if (!empty($event['summary'])) {
            $description = $event['summary'];
        } elseif (!empty($event['full_description'])) {
            // Strip HTML
            $description = strip_tags($event['full_description']);
        } else {
            $description = $summary;    // Event title is required
        }
        // Sanitize certain characters
        $description = 'DESCRIPTION:' . str_replace(
            array("\n", "\r"),
            array(' ', ' '),
            $description
        );
        if (strlen($description) > 70) {
            // Break into chunks according to
            // https://icalendar.org/iCalendar-RFC-5545/3-1-content-lines.html
            $description = rtrim(chunk_split($description, 70, "\r\n "));
        }

        $ical .= "BEGIN:VEVENT\r\n" .
            "UID:{$uuid}\r\n" .
            "DTSTAMP:$dtstart\r\n" .
            "DTSTART:$dtstart\r\n" .
            "DTEND:$dtend\r\n" .
            "URL:$permalink\r\n" .
            "SUMMARY:$summary\r\n" .
            "DESCRIPTION:$description\r\n" .
            "END:VEVENT\r\n";
    }
}
if (isset($opts['cal'])) {
    $Cal = Evlist\Calendar::getInstance($opts['cal']);
    $dscp = $Cal->getName();
} else {
    $dscp = $LANG_EVLIST['events'];
}
$content = "BEGIN:VCALENDAR\r\n" .
    "VERSION:2.0\r\n" .
    "PRODID:-//{$_CONF['site_name']}\r\n" .    //NONSGML v1.0//EN
    "X-WR-CALNAME:{$_CONF['site_name']} $dscp\r\n" .
    "X-WR-TIMEZONE:{$_CONF['timezone']}\r\n" .
    "X-ORIGINAL-URL:{$_CONF['site_url']}\r\n" .
    "X-WR-CALDESC:Events from {$_CONF['site_name']} \r\n" .
    "CALSCALE:GREGORIAN\r\n" .
    "METHOD:PUBLISH\r\n" .
    $ical .
    "END:VCALENDAR\r\n";

header('Content-Type: text/calendar');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header("Pragma: no-cache");
header('Expires: ' . gmdate ('D, d M Y H:i:s', time()));
echo $content;
exit;

?>
