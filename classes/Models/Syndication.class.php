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
    public static function getFeedContent($feed, &$link, &$update_data, $feedType)
    {
        global $_CONF, $_EV_CONF, $_TABLES, $LANG_EVLIST;

        $content = array();
        $lids = array();
        $eids = array();
        $feed = (int)$feed;

        // Feeds are not authenticated, so anonymous uers must be
        // allowed to view events
        if ($_EV_CONF['allow_anon_view'] != 1) {
            return $content;
        }

        $result = DB_query(
            "SELECT topic,limits,content_length
            FROM {$_TABLES['syndication']}
            WHERE fid = $feed"
        );
        $F = DB_fetchArray($result, false);

        // Set a sane limit on the events retrieved to avoid OOM errors
        $limit = (int)$F['limits'];
        if ($limit > 500) {
            $limit = 500;
        }

        // Get all upcoming events
        $events = EventSet::create()
            ->withStart($_EV_CONF['_today'])
            ->withEnd(date('Y-m-d', strtotime('+1 year', $_EV_CONF['_today_ts'])))
            ->withCategory($F['topic'])
            ->withLimit($limit)
            ->getEvents();

        $rp_shown = array();    // Store repeat ids to avoid dups
        foreach ($events as $daydata) {
            foreach ($daydata as $event) {
                // Check if this event has an earlier date, or if it has
                // already been included.  Could happen with multi-day events.
                if (
                    $event['rp_date_start'] < $_EV_CONF['_today'] ||
                    array_key_exists($event['rp_id'], $rp_shown)
                ) {
                    continue;
                }
                $rp_shown[$event['rp_id']] = 1;

                $url = EVLIST_URL . '/event.php?eid=' . urlencode($event['rp_id']);
                $postmode = $event['postmode'] == '2' ? 'html' : 'plaintext';
                if ($event['postmode'] == '1' ) {       //plaintext
                    $event['summary'] = nl2br($event['summary']);
                }

                // Track the event IDs that we're actually including
                $eids[] = $event['rp_id'];
                /*if ($event['postmode'] != 'plaintext') {
                    $summary = PLG_replaceTags(COM_stripslashes($event['summary']));
                } else {*/
                    //$summary = ($event['summary']);
                //}
                $content[] = array(
                    'title'     => $event['title'],
                    'summary'   => $event['summary'],
                    'description' => $event['full_description'],
                    'link'      => $url,
                    'url'       => $url,
                    'date'      => strtotime($event['rp_date_start'].' '.$event['rp_time_start1']),
                    'dtstart'   => date("Ymd\THi00",strtotime($event['rp_date_start'] . $event['time_start1'])),
                    'dtend'     => date("Ymd\THi00",strtotime($event['rp_date_end'] . $event['time_end1'])),
                    'format'    => $postmode,
                    'location'  => $event['location'],
                    'categories' => isset($event['cat_name']) ? $event['cat_name'] : '',
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
                'name' => $LANG_EVLIST['all_upcoming']
            ),
        );
        $result = DB_query(
            "SELECT id, name
            FROM {$_TABLES['evlist_categories']}
            WHERE status = 1"
        );
        while ($A = DB_fetchArray($result, false)) {
            $feeds[] = array(
                'id' => $A['id'],
                'name' => $A['name']
            );
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
    public static function feedUpdateCheck($feed, $topic, $update_data, $limit)
    {
        global $_EV_CONF;;

        $eids = array();

        // Set a sane limit on the events retrieved to avoid OOM errors
        $limit = (int)$limit;
        if ($limit > 500) {
            $limit = 500;
        }
        $sql = EventSet::create()
            ->withStart($_EV_CONF['_today'])
            ->withEnd(date('Y-m-d', strtotime('+1 year', $_EV_CONF['_today_ts'])))
            ->withCategory($topic)
            ->withSelection('rep.rp_id')
            ->getSql();

        $result = DB_query($sql, 1);
        while ($A = DB_fetchArray($result, false)) {
            $eids[] = $A['rp_id'];
        }
        $current = implode (',', $eids);
        return ($current != $update_data) ? false : true;
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

        if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != 1) {
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

        // Anon access required for feed access anyway
        if ($_EV_CONF['allow_anon_view'] != 1) {
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

}
