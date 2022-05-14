<?php
/**
 * Class to manage syndication feeds.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Models;
use Evlist\Calendar;
use Evlist\Event;


/**
 * Class for syndiation feeds.
 * @package evlist
 */
class Syndication
{

    /**
     * Get content for the syndication feeds.
     *
     * @param   string  $feed       Feed ID to get
     * @param   string  $link       Pointer to header link value
     * @param   array   $update_data    Pointer to array of updated item IDs
     * @param   string  $feedType   Feed type (RSS, ICS, etc.) We only do ICS.
     * @return  array               Array of event data
     */
    public static function getFeedContent(
        $feed, &$link, &$update_data, $feedType, $feedVersion
    ) : array
    {
        switch (strtolower($feedType)) {
        case 'ics':
            return self::getICS($feed, $link, $update_data, $feedType);
            break;
        default:
            return self::getXML($feed, $link, $update_data, $feedType);
            break;
        }
    }


    /**
     * Get an XML feed.
     *
     * @param   integer $feed   Feed ID in the syndication table
     * @param   string  $link   Pointer to update the link
     * @param   string  $update_data    Comma-separated list of IDs
     * @param   string  $feedType   Feed type, e.g. 'ICAL'
     * @param   array   $A      Complete record from syndication table
     * @return  array       Array of elements for the XML handler
     */
    private static function getXML($feed, &$link, &$update_data, $feedtype) : array
    {
        global $_CONF, $_EV_CONF, $_TABLES, $LANG_EVLIST;

        $content = array();

        $lids = array();
        $eids = array();
        $feed = (int)$feed;

        $F = self::_getFeedInfo($feed);

        // Set a sane limit on the events retrieved to avoid OOM errors
        $limit = (int)$F['limits'];
        if ($limit > 500) {
            $limit = 500;
        }

        // Get all upcoming events
        $now_ts = $_CONF['_now']->toUnix(true);
        $events = EventSet::create()
            ->withUid(1)
            ->withIcal(true)
            ->withStart($now_ts)
            ->withEnd($now_ts + (365 * 86400))
            ->withCategory($F['topic'])
            ->withLimit($limit)
            ->withFields(array(
                'det.det_revision', 'det.title', 'det.det_last_mod', 'det.lat', 'det.lng',
                'det.summary', 'det.full_description', 'det.location',
            ))
            ->getEvents();

        $domain = '@' . preg_replace('/^https?\:\/\//', '', $_CONF['site_url']);
        $rp_shown = array();    // Store repeat ids to avoid dups
        foreach ($events as $daydata) {
            foreach ($daydata as $event) {
                // Check if this event has an earlier date, or if it has
                // already been included.  Could happen with multi-day events.
                if (
                    $event['rp_date_start'] < $_CONF['_now']->format('Y-m-d') ||
                    array_key_exists($event['rp_id'], $rp_shown)
                ) {
                    continue;
                }
                $rp_shown[$event['rp_id']] = 1;

                $guid = $event['rp_ev_id'] . '-' . $event['rp_id'] . $domain;
                $url = EVLIST_URL . '/event.php?eid=' . urlencode($event['rp_id']);
                $postmode = $event['postmode'] == '2' ? 'html' : 'plaintext';
                if ($event['postmode'] == '1' ) {       //plaintext
                    $event['summary'] = nl2br($event['summary']);
                }

                // Track the event IDs that we're actually including
                $eids[] = $event['rp_id'];
                $content[] = array(
                    'title'     => $event['title'],
                    'summary'   => PLG_replaceTags(COM_stripslashes($event['summary'])),
                    'description' => $event['full_description'],
                    'link'      => $url,
                    'url'       => $url,
                    'date'      => strtotime($event['rp_date_start'].' '.$event['rp_time_start1']),
                    'dtstart'   => date("Ymd\THi00",strtotime($event['rp_date_start'] . $event['time_start1'])),
                    'dtend'     => date("Ymd\THi00",strtotime($event['rp_date_end'] . $event['time_end1'])),
                    'format'    => $postmode,
                    'location'  => $event['location'],
                    'categories' => isset($event['cat_name']) ? $event['cat_name'] : '',
                    'guid'      => $guid,
                );
            }
        }

        $view = isset($_EV_CONF['default_view']) && !empty($_EV_CONF['default_view']) ?
            $_EV_CONF['default_view'] : 'list';
        if ($F['topic'] == 0) {
            $link = EVLIST_URL . '/index.php?view=' . $view;
        } else {
            $link = EVLIST_URL . "/index.php?view={$view}&amp;cat={$F['topic']}";
        }

        if (count($eids) > 0 ) {
            $update_data = implode (',', $eids);
        }

        return $content;
    }


    /**
     * Get the names of RSS feeds that are provided.
     * For evList this is a list of topics
     *
     * @return  array   Array of ID=>Name pairs
     */
    public static function getFeedNames()
    {
        global $_TABLES, $LANG_EVLIST;

        $feeds = array(
            // Always include "All" as an option
            array(
                'id' => '0',
                'name' => $LANG_EVLIST['all_calendars']
            ),
        );
        $Cals = Calendar::getAll(true);
        foreach ($Cals as $Cal) {
            if ($Cal->isIcalEnabled()) {
                $feeds[] = array(
                    'id' => $Cal->getID(),
                    'name' => $Cal->getName(),
                );
            }
        }
        return $feeds;
    }


    /**
     * Checks to see if the RSS feed is up-to-date.
     *
     * @param   integer $feed   Feed ID from the RSS configuration
     * @param   integer $topic  Topic ID being requested
     * @param   string  $update_data    Comma-separated string of current item IDs
     * @param   integer $limit  Configured limit on item count for this feed
     * @return  boolean         True if feed needs updating, False otherwise
     */
    public static function feedUpdateCheck(
        $feed, $topic, $update_data, $limit,
        $updated_type = '', $updated_topic = '', $updated_id = ''
    ) {
        global $_EV_CONF, $_CONF, $_TABLES;

        $feed = (int)$feed;
        $F = self::_getFeedInfo($feed);
        if (!$F) {
            // If not a valid feed, just return true to indicate no further action.
            return true;
        }

        $dt = clone $_CONF['_now'];
        if ($F['updated'] > $dt->sub(new \DateInterval('PT30M'))) {
            return true;
        }

        // Not found in cache, get events and check
        $eids = array();
        $start = clone $_CONF['_now'];
        $start->sub(new \DateInterval('P30D'));
        $end = NULL;
        $limit = $F['limits'];
        if (!empty($limit)) {
            if (substr($limit, -1) == 'h') { // last xx hours
                $end = clone $_CONF['_now'];
                $hours = (int) substr( $limit, 0, -1 );
                $end->add(new \DateInterval('PT' . $hours . 'H'));
                $limit = 0;
            }
        } else {
            $limit = 100;
        }

        // Set a sane limit on the events retrieved to avoid OOM errors
        $limit = (int)$limit;
        if ($limit > 100) {
            $limit = 100;
        }
        $ES = EventSet::create()
            ->withUid(1)            // Anonymous must be able to view feeds
            ->withStart($start->format('Y-m-d', true))
            ->withCalendar($F['topic'])
            ->withSelection('rep.rp_id, rep.rp_revision, ev.ev_revision, det.det_revision')
            ->withStatus(Status::ALL)
            ->withLimit($limit);
        if ($end) {
            $ES->withEnd($end->format('Y-m-d', true));
        }
        $sql = $ES->getSql();
        $result = DB_query($sql, 1);
        while ($A = DB_fetchArray($result, false)) {
            $rev = $A['ev_revision'] + $A['det_revision'] + $A['rp_revision'];
            $eids[] = $A['rp_id'] . '.' . $rev;
        }
        $current = implode (',', $eids);
        return ($current != $F['update_info']) ? false : true;
    }


    /**
     * Get the RSS feed links only.
     *
     * @return  array   Array of links & titles
     */
    public static function getFeedLinks()
    {
        global $_EV_CONF, $_TABLES;

        $retval = array();

        if (!EVLIST_canView()) {
            return $retval;
        }

        // Get the feed info for configured feeds
        $result = DB_query(
            "SELECT title, filename
            FROM {$_TABLES['syndication']}
            WHERE type='" . DB_escapeString($_EV_CONF['pi_name']) . "'"
        );

        if (DB_numRows($result) > 0) {
            $feed_url = SYND_getFeedUrl();
            while ($A = DB_fetchArray($result, false)) {
                $retval[] = array(
                    'feed_title'   => $A['title'],
                    'feed_url'     => $feed_url . $A['filename'],
                );
            }
        }
        return $retval;
    }


    /**
     * Get the feed subscription urls & icons.
     * This returns a ready-to-display set of icons for visitors
     * to subscribe to RSS feeds
     *
     * @return  string  HTML for icons
     */
    public static function getFeedIcons()
    {
        global $_CONF, $_EV_CONF, $_TABLES;

        $retval = '';

        if (!EVLIST_canView()) {
            return $retval;
        }

        // Get the feed info for configured feeds
        $result = DB_query(
            "SELECT title, filename FROM {$_TABLES['syndication']}
            WHERE type='" . DB_escapeString($_EV_CONF['pi_name']) . "'"
        );

        if (DB_numRows($result) > 0) {
            $T = new \Template(EVLIST_PI_PATH . '/templates');
            $T->set_file('feed', 'rss_icon.thtml');
            $feed_url = SYND_getFeedUrl();
            while ($A = DB_fetchArray($result, false)) {
                $T->set_var(array(
                    'feed_title'    => $A['title'],
                    'feed_url'     => $feed_url . $A['filename'],
                ) );
                $T->parse('output', 'feed', true);
            }
            $retval = $T->finish($T->get_var('output'));
        }
        return $retval;
    }


    /**
     * Create the iCal output.
     *
     * @param   integer $feed   Feed ID in the syndication table
     * @param   string  $link   Pointer to update the link
     * @param   string  $update_data    Comma-separated list of IDs
     * @param   string  $feedType   Feed type, e.g. 'ICAL'
     * @param   array   $A      Complete record from syndication table
     * @return  array       Array of elements for the ICS handler
     */
    private static function getICS($feed, &$link, &$update_data, $feedType) : array
    {
        global $_EV_CONF, $LANG_EVLIST, $_CONF;

        $retval = array();
        $start = clone $_CONF['_now'];
        $start->sub(new \DateInterval('P30D'));
        $end = NULL;

        $A = self::_getFeedInfo($feed);
        if (!$A) {
            // Invalid feed data received.
            return '';
        }

        if (substr($A['limits'], -1) == 'h') { // last xx hours
            $end = clone $_CONF['_now'];
            $hours = (int) substr($A['limits'], 0, -1 );
            $end->add(new \DateInterval('PT' . $hours . 'H'));
            $limit = 0;
        } else {
            $limit = (int)$A['limits'];
        }

        // Set a sane limit on the events retrieved to avoid OOM errors
        $limit = (int)$limit;
        if ($limit < 1 || $limit > 100) {
            $limit = 100;
        }

        $ES = EventSet::create()
            ->withUid(1)
            ->withStatus(Status::ALL)
            ->withIcal(true)
            ->withStart($start->format('Y-m-d', true))
            ->withFields(array(
                'det.det_revision', 'det.title', 'det.det_last_mod', 'det.lat', 'det.lng',
                'det.summary', 'det.full_description', 'det.location', 'det.street',
                'det.city', 'det.province', 'det.postal'
            ))
            ->withLimit($limit);
        if ($end) {
            $ES->withEnd($end->format('Y-m-d', true));
        } else {
            $ES->withEnd(Event::MAX_DATE);
        }

        // Default description if retrieving all events
        $dscp = empty($A['description']) ? $LANG_EVLIST['events'] : $A['description'];
        if (isset($A['topic']) && !empty($A['topic'])) {
            // Get only a specific calendar, and set the description to tne
            // calendar name
            $ES->withCalendar($A['topic']);
            $Cal = Calendar::getInstance($A['topic']);
            if ($Cal) {
                $dscp = $Cal->getName();
            }
        }

        $domain = '@' . preg_replace('/^https?\:\/\//', '', $_CONF['site_url']);

        $events = $ES->getEvents();

        $ical = '';
        $rp_shown = array();
        $eids = array();
        foreach ($events as $day) {
            foreach ($day as $event) {

                // Check if this repeat is already shown.  We only want multi-day
                // events included once instead of each day
                if (array_key_exists($event['rp_id'], $rp_shown)) {
                    continue;
                }
                $sequence = $event['rp_revision'] + $event['ev_revision'] + $event['det_revision'];
                // Collect the unique identifiers for the syndication table to track
                $eids[] = $event['rp_id'] . '.' . $sequence;
                // Track that this repeat has already been shown
                $rp_shown[$event['rp_id']] = 1;

                // Format the dates for iCalendar
                $dtstart = (new \Date($event['rp_start'], $_CONF['timezone']))
                    ->format('Ymd\THis\Z', false);
                $dtend = (new \Date($event['rp_end'], $_CONF['timezone']))
                    ->format('Ymd\THis\Z', false);

                $summary = self::_strip_tags($event['title']);
                $permalink = COM_buildURL(EVLIST_URL . '/event.php?rp_id='. $event['rp_id']);
                $guid = $event['rp_ev_id'] . '-' . $event['rp_id'] . $domain;
                $created = max($event['rp_last_mod'], $event['ev_last_mod'], $event['det_last_mod']);
                // Get the description. Prefer the text Summary, then HTML fulltext.
                // Since a description is required, re-use the title if nothing else.
                if (!empty($event['summary'])) {
                    $description = self::_strip_tags($event['summary']);
                } elseif (!empty($event['full_description'])) {
                    $description = self::_strip_tags($event['full_description']);
                } else {
                    $description = $summary;    // Event title is required
                }

                // Assemble the location parts into a string
                $loc_parts = array();
                foreach (array('location', 'street', 'city', 'province', 'postal') as $elem) {
                    if (isset($event[$elem]) && !empty($event[$elem])) {
                        $loc_parts[] = $event[$elem];
                    }
                }
                $location = implode(',', $loc_parts);
                $tmp = array(
                    'date' => $created,
                    'title' => $summary,
                    'summary' => $description,
                    'guid' => $guid,
                    'link' => $permalink,
                    'dtstart' => $dtstart,
                    'dtend' => $dtend,
                    'allday' => $event['allday'],
                    'sequence' => $sequence,
                );
                if (!empty($location)) {
                    $tmp['location'] = $location;
                }

                switch ($event['rp_status']) {
                case Status::DISABLED:
                case Status::CANCELLED:
                    continue 2;
                    $tmp['status'] = 'CANCELLED';
                    break;
                case Status::ENABLED:
                default:
                    //$status = 'CONFIRMED';
                    if ($event['lat'] != 0 && $event['lng'] != 0) {
                        $tmp['geo'] = "{$event['lat']},{$event['lng']}";
                    }
                    break;
                }
                $retval[] = $tmp;
            }
        }
        // Contains a list of repeats shown, returned to lib-syndication
        // to check for changes
        $update_data = implode(',', $eids);
        return $retval;
    }


    /**
     * Get the feed information from the database.
     *
     * @param   integer $fid    Feed ID
     * @return  array|bool  Array of key->values from the DB, False on error
     */
    private static function _getFeedInfo($fid)
    {
        global $_TABLES;
        static $feeds = array();

        $fid = (int)$fid;
        if (!isset($feeds[$fid])) {
            $res = DB_query(
                "SELECT * FROM {$_TABLES['syndication']}
                WHERE fid = $fid"
            );
            if ($res) {
                $feeds[$fid] = DB_fetchArray($res, false);
                if (!$feeds[$fid]) {
                    return false;
                }
            }
        }
        return $feeds[$fid];
    }


    /**
     * Get the available feed formats.
     * Only ICS is supported by Evlist.
     *
     * @return  array   Array of name & version arrays
     */
    public static function getFormats() : array
    {
        return array(
            array(
                'name' => 'ICS',
                'version' => '1.0',
            ),
        );
    }


    /**
     * Version of strip_tags to also take out trailing newline characters.
     * Newlines may be added by the advanced editor.
     *
     * @param   string  $str    String to be modified
     * @return  string      Modified string
     */
    private static function _strip_tags(string $str) : string
    {
        return strip_tags(trim($str));
    }

}
