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
        if (array_key_exists($event['rp_id'], $rp_shown))
            continue;
        $rp_shown[$event['rp_id']] = 1;

        $start_time = gmstrftime('%Y%m%dT%H%M%SZ', 
                strtotime($event['rp_date_start'] . ' ' . 
                    $event['rp_time_start1']));
        $end_time = gmstrftime('%Y%m%dT%H%M%SZ', 
                strtotime($event['rp_date_end'] . ' ' . 
                    $event['rp_time_end1']));
        $summary = $event['title'];
        $permalink = COM_buildURL(EVLIST_URL . '/event.php?eid='.
                $event['rp_id']);

        if (!empty($event['full_description'])) {
            $description = $event['full_description'];
        } elseif (!empty($event['summary'])) {
            $description = $event['summary'];
        } else {
            $description = $summary;    // Event title is required
        }
    	//$description = str_replace(",", "\,", $description);
        // TODO: the following 3 lines commented out 2011-02-17 to see if
        // they're necessary for google calendar
    	//$description = str_replace("\\", "\\\\", $description);
    	//$description = str_replace("\n", $space, strip_tags($description));
    	//$description = str_replace("\r", $space, strip_tags($description));

        $ical .= "BEGIN:VEVENT\n" .
            "DTSTART:$start_time\n" .
            "DTEND:$end_time\n" .
            "URL:$permalink\n" .
            "SUMMARY:$summary\n" .
            "DESCRIPTION:$description\n" .
            "END:VEVENT\n\n";
    }
}

$content = <<<CONTENT
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//{$_CONF['site_name']}//NONSGML v1.0//EN
X-WR-CALNAME:{$_CONF['site_name']} Events
X-WR-TIMEZONE:{$_CONF['timezone']}
X-ORIGINAL-URL:{$_CONF['site_url']}
X-WR-CALDESC:Events from {$_CONF['site_name']}
CALSCALE:GREGORIAN
METHOD:PUBLISH
{$ical}END:VCALENDAR
CONTENT;
    
echo $content;
exit;

?>
