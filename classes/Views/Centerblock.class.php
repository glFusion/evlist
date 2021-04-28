<?php
/**
 * Centerblock View functions for the evList plugin.
 *
 * @author      Lee P. Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Views;
use Evlist\View;
use Evlist\Cache;


/**
 * Display a centerblock.
 * @package evlist
 */
class Centerblock
{
    
    /**
     * Create the centerblock.
     *
     * @uses    self::getContent()
     * @param   string  $where  Position (top, etc.)
     * @param   string  $page   Page currently being displayed
     * @param   string  $topic  Topic currently being displayed
     * @return  string          HTML for centerblock
     */
    public static function Render($where, $page, $topic = '')
    {
        global $_EV_CONF, $_CONF, $_USER, $_TABLES, $LANG_EVLIST;

        if ($_EV_CONF['pos_centerblock'] != $where ||
            (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1')) {
            return '';
        }

        // If we show only on the homepage, check if that's where we are
        // If a topic is being displayed, then we're not on the homepage
        if (
            $_EV_CONF['topic_centerblock'] == 'home' &&
            ($page > 1 || !empty($topic))
        ) {
            return '';
        }

        if ($_EV_CONF['topic_centerblock'] != 'all') {
            // display on topic page or not at all
            if (!empty($topic) && $_EV_CONF['topic_centerblock'] != $topic) {
                return '';
            }
        }

        if (empty($display)) {  // not found in cache
            // overloading the previously-boolean enable_centerblock option to
            // indicate the centerblock format.
            switch ($_EV_CONF['enable_centerblock']) {
            case 1:     // table format
            case 2:     // story format
                $display = self::getContent($_EV_CONF['enable_centerblock']);
                break;
            case 3:     // calendar format
                $view = View::getView('month');
                $display = $view->Render();
                break;
            case 0:     // disabled
                default:
                $display = '';
            }
        }
        if (!empty($display)) {
            if ($where == 0) {      // replacing home page
                $display = EVLIST_siteHeader() . $display . EVLIST_siteFooter();
            }
        }
        return $display;
    }


    /**
     * Get the content for a table- or story-formatted centerblock.
     *
     * @param   integer $format     Centerblock type, 1=table,2=story
     * @return  string      HTML for centerblock section
     */
    private static function getContent($format)
    {
        global $_EV_CONF, $_CONF, $_USER, $_TABLES, $LANG_EVLIST;

        $retval = '';

        switch ($format) {
        case 1:     // table format
            $tpl_file = 'centerblock.thtml';
            break;
        case 2:     // story format
            $tpl_file = 'cblock_stories.thtml';
            break;
        default:
            return '';
        }

        $range    = $_EV_CONF['range_centerblock'];
        $limit    = (int)$_EV_CONF['limit_block'];
        $length   = $_EV_CONF['limit_summary'];
        // Retrieve Centerblock Settings
        $_dt = clone($_CONF['_now']);
        $interval = (int)$_EV_CONF['max_upcoming_days'];
        if ($interval > 0) {
            $cb_max_date = $_dt
                ->add(new \DateInterval("P{$interval}D"))
                ->toMySQL(true);
        } else {
            // no limit by days.
            $cb_max_date = '9999-12-31';
        }

        $opts = array(
            'limit' => $limit,
            'show_upcoming' => 1,
        );

        $dup_chk = $_EV_CONF['cb_dup_chk'];
        $Y = $_CONF['_now']->format('Y');
        $D = $_CONF['_now']->format('d');
        $M = $_CONF['_now']->format('m');
        switch ($_EV_CONF['range_centerblock']) {
        case 1:         // past events
            $start = date('Y-m-d', strtotime("{$_EV_CONF['_today']} - 1 month"));
            $end = date('Y-m-d', strtotime("{$_EV_CONF['_today']} - 1 day"));
            $limit = 0;     // special, we need to get all events since we can't count back
            $opts['order'] = 'DESC';
            break;
        case 2:         // upcoming events
        default:
            $opts['upcoming'] = true;
            $start = $_EV_CONF['_today'];
            $end = $cb_max_date;
            break;
        case 3:         // this week
            $start = DateFunc::beginOfWeek($D, $M, $Y);
            $end = DateFunc::endOfWeek($D, $M, $Y);
            break;
        case 4:         // upcoming month
            $start = DateFunc::beginOfMonth($M, $Y);
            $end = DateFunc::dateFormat(DateFunc::daysInMonth($M, $Y), $M, $Y);
            break;
        }

        // Try first to get from cache
        $cache_key = 'evlistcb_' . $_EV_CONF['enable_centerblock'] . '_' .
            $_EV_CONF['cb_dup_chk'];
        $events = Cache::get($cache_key);
        if ($events === NULL) {
            $events = EVLIST_getEvents($start, $end, $opts);
            Cache::set($cache_key, $events, 'evlistcb');
        }
        if (empty($events) || !is_array($events)) {
            return '';
        }

        // Special handling needed to get the latest X past events.  We have a bunch
        // from the query (to make sure we got enough).  Now pick out the last X.
        if ($_EV_CONF['range_centerblock'] == 1) {
            $limit = (int)$_EV_CONF['limit_block'];     // Need this value again
            $events = array_splice($events, ($limit * -1), $limit);
        }

        // Find all the autotags that need to be stripped from the summary.
        $tmp = PLG_collectTags();
        $patterns = array();
        if (is_array($tmp)) {
            foreach ($tmp as $tag=>$plugin) {
                $patterns[] = '/\[' . $tag . ':.*\]/';
            }
        }

        $T = new \Template(EVLIST_PI_PATH . '/templates');
        $T->set_file('centerblock', $tpl_file);

        $T->set_var(array(
            'startblock' => COM_startBlock($LANG_EVLIST['ranges'][$range]),
            'endblock' => COM_endBlock(),
            'title_label' => $LANG_EVLIST['event_title'],
            'date_label' => $LANG_EVLIST['start_date'],
        ) );
        $cssid = 0;

        $T->set_block('centerblock', 'eventRow', 'eRow');
        $rp_shown = array();    // Array to hold repeat id's to avoid dups
        $count = 0;
        $T->set_var('adblock_0', PLG_displayAdBlock('evlist_centerblock', 0));
        foreach ($events as $date=>$day) {
            if ($date > $cb_max_date) {
                // Reached the maximum date to show
                break;
            }
            if ($date == '_empty_') continue;
            foreach ($day as $A) {
                // Don't display birthdays as story items
                if ($format == 2 && $A['cal_id'] == -2) continue;

                // Make sure we only show each event once for multiday
                if ($dup_chk != '') {
                   if (array_key_exists($A[$dup_chk], $rp_shown)) {
                        continue;
                    } else {
                        $rp_shown[$A[$dup_chk]] = 1;
                    }
                }

                // Now increment and check the counter.
                $count++;
                if ($count > $limit) break;

                // Prepare the summary for display. Remove links and autotags
                $summary = empty($A['summary']) ? $A['title'] : $A['summary'];
                $summary = strip_tags($summary, '<a>');
                $summary = preg_replace($patterns, '', $summary);

                if (!empty($length) && $length >= 1) {
                    if (strlen($summary) > $length) {
                        $summary = substr($summary, 0, $length);
                        $summary = $summary . '...';
                    }
                }
                $s_ts1 = strtotime($A['rp_start']);
                $e_ts1 = strtotime($A['rp_end']);
                $email = isset($A['email']) ? EVLIST_obfuscate($A['email']) : '';
                $cssid = ($cssid == 1) ? 2: 1;

                if (isset($A['postmode']) && $A['postmode'] != 'plaintext') {
                    $full_dscp = PLG_replaceTags($A['full_description']);
                } else {
                    $full_dscp = $A['full_description'];
                }

                $T->set_var(array(
                    'cssid'     => $cssid,
                    'is_birthday' => $A['cal_id'] == -2 ? true : false,
                    'eid'       => $A['rp_id'],
                    'pi_url'    => EVLIST_URL,
                    'title'     => $A['title'],
                    'summary'   => $summary,
                    'full_dscp' => $full_dscp,
                    'contact'   => isset($A['contact']) ? $A['contact'] : '',
                    'location'  => isset($A['location']) ? $A['location'] : '',
                    'ev_url'    => isset($A['url']) ? $A['url'] : '',
                    'street'    => isset($A['street']) ? $A['street'] : '',
                    'city'      => isset($A['city']) ? $A['city'] : '',
                    'province'  => isset($A['province']) ? $A['province'] : '',
                    'country'   => isset($A['country']) ? $A['country'] : '',
                    'email'     => $email,
                    'phone'     => isset($A['phone']) ? $A['phone'] : '',
                    'startdate' => EVLIST_formattedDate($s_ts1),
                    'enddate'   => EVLIST_formattedDate($e_ts1),
                    'starttime1' => EVLIST_formattedTime($s_ts1),
                    'endtime1'  => EVLIST_formattedTime($e_ts1),
                    'allday'    => $A['allday'],
                    'adblock'   => PLG_displayAdBlock('evlist_centerblock', $count),
                ) );
                if (isset($A['split']) && $A['split'] == 1) {
                    $s_ts2 = strtotime($A['rp_date_start'] . ' ' . $A['rp_time_start2']);
                    $e_ts2= strtotime($A['rp_date_end'] . ' ' . $A['rp_time_end2']);
                    $T->set_var(array(
                        'starttime2' => EVLIST_formattedTime($s_ts2),
                        'endtime2' => EVLIST_formattedTime($e_ts2),
                    ) );
                } else {
                    $T->set_var(array(
                        'starttime2' => '',
                        'endtime2' => '',
                    ) );
                }
                //$T->parse('eventrow', 'item', true);
                $T->parse('eRow', 'eventRow', true);
            }
        }

        $T->parse('output', 'centerblock');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }

}
