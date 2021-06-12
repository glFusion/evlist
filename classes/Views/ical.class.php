<?php
/**
 * ICal export function for the evList plugin.
 *
 * @author      Lee P. Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee P. Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Views;
use Evlist\Calendar;
use Evlist\Models\EventSet;


/**
 * Class to create iCalendar views for consumption by calendar providers.
 * @package evlist
 */
class ical extends \Evlist\View
{
    /** Characters to be escaped.
     * @var array */
    private static $badchars = array(
        '\\', '"', ',', ':', ';', "\r", "\n",
    );

    /** Replacement characters when escaped.
     * @var array */
    private static $replchars = array(
        '', 'DQUOTE', '\\,', '":"', '\\;', ' ', ' ',
    );

    /** Revision, to alter the UID and force re-reading by the consumer.
     * @var string */
    private $rev = '1';


    /**
     * Escape a text string.
     *
     * @param   string  $str    String to be escaped
     * @return  string      Escaped string
     */
    private static function _escape($str)
    {
        return str_replace(self::$badchars, self::$replchars, $str);
    }


    /**
     * Create the iCal output.
     *
     * @return  string      iCal output
     */
    public function Content()
    {
        global $_EV_CONF, $LANG_EVLIST, $_CONF;

        // Set up the period to export- now + 1 year
        $start = sprintf('%d-%02d-%02d', $this->year-1, $this->month, $this->day);
        $end = sprintf('%d-%02d-%02d', $this->year+1, $this->month, $this->day);
        $EventSet = EventSet::create()
            ->withStart($start)
            ->withEnd($end);

        $opts = array('ical' => 1);
        if (isset($_GET['cal']) && !empty($_GET['cal'])) {
            // Get only a specific calendar
            $EventSet->withCalendar($_GET['cal']);
        }
        if (isset($_GET['rp_id']) && !empty($_GET['rp_id'])) {
            // Get a single event
            $EventSet->withRepeat($_GET['rp_id']);
        }

        $domain = preg_replace('/^https?\:\/\//', '', $_CONF['site_url']);
        $this->rev .= '@' . $domain;

        $events = $EventSet->getEvents();
        $ical = '';
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
                $permalink = COM_buildURL(EVLIST_URL . '/event.php?rp_id='. $event['rp_id']);
                $uuid = $event['rp_ev_id'] . '-' . $event['rp_id'] . '-' . $this->rev;

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
                $summary = self::_escape($summary);
                $description = 'DESCRIPTION:' . self::_escape($description);
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
                    "$description\r\n"; // already includes the DESCRIPTION tag
                // Add other values, if present.
                if (!empty($event['location'])) {
                    $ical .= "LOCATION:" . self::_escape($event['location']) . "\r\n";
                }
                if ($event['lat'] != 0 && $event['lng'] != 0) {
                    $ical .= "GEO:{$event['lat']}:{$event['lng']}\r\n";
                }
                $ical .= "END:VEVENT\r\n";
            }
        }
        if (isset($opts['cal'])) {
            $Cal = Calendar::getInstance($opts['cal']);
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
        return $content;
    }
}
