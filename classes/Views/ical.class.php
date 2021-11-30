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
use Evlist\Config;
use Evlist\Calendar;
use Evlist\Cache;
use Evlist\Models\EventSet;
use Evlist\Models\Status;


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
        global $LANG_EVLIST, $_CONF;

        $ical_ranges = explode(',', Config::get('ical_range', '180,180'));
        $from_days = !empty($ical_ranges[0]) ? (int)$ical_ranges[0] : 180;
        $to_days = !empty($ical_ranges[1]) ? (int)$ical_ranges[1] : $from_days;

        $from = clone($this->today);
        $start = $from->sub(new \DateInterval('P'.$from_days.'D'))->format('Y-m-d');
        $to = clone($this->today);
        $end = $to->add(new \DateInterval('P'.$to_days.'D'))->format('Y-m-d');
        $EventSet = EventSet::create()
            ->withIcal(true)
            ->withStart($start)
            ->withEnd($end)
            ->withStatus(Status::ALL);

        $dscp = $LANG_EVLIST['events'];
        if (isset($_GET['cal']) && !empty($_GET['cal'])) {
            // Get only a specific calendar, and set the description to tne
            // calendar name
            $cal = (int)$_GET['cal'];
            $EventSet->withCalendar($cal);
            $Cal = Calendar::getInstance($cal);
            if ($Cal) {
                $dscp = $Cal->getName();
            }
        } else {
            // Getting all events, just set the description to "Events"
            $cal = 0;
        }
        if (isset($_GET['rp_id']) && !empty($_GET['rp_id'])) {
            // Get a single event
            $rp_id = (int)$_GET['rp_id'];
            $EventSet->withRepeat($rp_id);
        } else {
            $rp_id = 0;
        }

        $cache_key = "ical_{$cal}_{$rp_id}";
        $content = Cache::get($cache_key);
        $content = NULL;
        if ($content !== NULL) {
            return $content;
        }

        $events = $EventSet->getEvents();
        $domain = '@' . preg_replace('/^https?\:\/\//', '', $_CONF['site_url']);
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
                $uuid = $event['rp_ev_id'] . '-' . $event['rp_id'] . $domain;
                $sequence = max($event['rp_revision'], $event['ev_revision'], $event['det_revision']);
                switch ($event['rp_status']) {
                case Status::DISABLED:
                case Status::CANCELLED:
                    $status = 'CANCELLED';
                    break;
                case Status::ENABLED:
                default:
                    $status = 'CONFIRMED';
                    break;
                }

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
                    "SEQUENCE:{$sequence}\r\n" .
                    "STATUS:{$status}\r\n" .
                    "DTSTAMP;VALUE=DATE-TIME:$dtstart\r\n" .
                    "DTSTART;VALUE=DATE-TIME:$dtstart\r\n" .
                    "DTEND;VALUE=DATE-TIME:$dtend\r\n" .
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

        $content = "BEGIN:VCALENDAR\r\n" .
            "VERSION:2.0\r\n" .
            "PRODID:-//{$_CONF['site_name']}\r\n" .    //NONSGML v1.0//EN
            "X-WR-CALNAME:{$_CONF['site_name']} $dscp\r\n" .
            "X-WR-TIMEZONE:{$_CONF['timezone']}\r\n" .
            "X-ORIGINAL-URL:{$_CONF['site_url']}\r\n" .
            "X-WR-CALDESC:Events from {$_CONF['site_name']} \r\n" .
            "CALSCALE:GREGORIAN\r\n" .
            "METHOD:PUBLISH\r\n" .
            "X-PUBLISHED-TTL:PT6H\r\n" .
            $ical .
            "END:VCALENDAR\r\n";
        Cache::set($cache_key, $content, 'feeds', 1800);
        return $content;
    }
}
