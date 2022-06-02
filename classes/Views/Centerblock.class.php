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
use Evlist\Config;
use Evlist\Detail;
use Evlist\Calendar;
use Evlist\Models\EventSet;
use Evlist\Models\TimeRange;


/**
 * Display a centerblock.
 * @package evlist
 */
class Centerblock
{
    const DISABLED = 0;     // No centerblock shown
    const TABLE = 1;        // Show as a table listing events
    const STORY = 2;        // Show as stories
    const CALENDAR = 3;     // Embed a calendar


    /**
     * Create the centerblock.
     *
     * @uses    self::getContent()
     * @param   string  $where  Position (top, etc.)
     * @param   string  $page   Page currently being displayed
     * @param   string  $topic  Topic currently being displayed
     * @return  string          HTML for centerblock
     */
    public function Render(string $where, string $page, ?string $topic = NULL) : string
    {
        global $_CONF, $_USER, $_TABLES, $LANG_EVLIST;

        if (
            Config::get('pos_centerblock') != $where ||
            !EVLIST_canView()
        ) {
            return '';
        }

        // If we show only on the homepage, check if that's where we are
        // If a topic is being displayed, then we're not on the homepage
        if (
            Config::get('topic_centerblock') == 'home' &&
            ($page > 1 || !empty($topic))
        ) {
            return '';
        }

        if (Config::get('topic_centerblock') != 'all') {
            // display on topic page or not at all
            if (!empty($topic) && Config::get('topic_centerblock') != $topic) {
                return '';
            }
        }

        //$display = '';
        //if (empty($display)) {  // not found in cache
            // overloading the previously-boolean enable_centerblock option to
            // indicate the centerblock format.
            switch (Config::get('enable_centerblock')) {
            case self::TABLE:       // table format
            case self::STORY:       // story format
                $display = $this->getContent();
                break;
            case self::CALENDAR:    // calendar format
                $view = View::getView(Config::get('default_view'));
                $display = $view->Render();
                break;
            case self::DISABLED:    // disabled
                default:
                $display = '';
            }
        //}
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
     * @return  string      HTML for centerblock section
     */
    private function getContent() : string
    {
        global $_CONF, $_USER, $_TABLES, $LANG_EVLIST;

        $retval = '';

        switch (Config::get('enable_centerblock')) {
        case self::TABLE:   // table format
            $tpl_file = 'centerblock.thtml';
            $hidesmall = Config::get('cb_hide_small');
            $length = (int)Config::get('limit_summary');
            $allowed_tags = '';
            $use_outputfilter = false;
            $strip_tags = true;
            break;
        case self::STORY:   // story format
            $tpl_file = 'cblock_stories.thtml';
            $hidesmall = false;     // not hidden on small screens
            $length = -1;
            $allowed_tags = '<div><a><img><span>';
            $use_outputfilter = true;
            $strip_tags = false;
            break;
        default:            // invalid format
            return $retval;
        }

        // Retrieve Centerblock Settings
        $range    = Config::get('range_centerblock');
        $limit    = (int)Config::get('limit_block');
        $_dt = clone($_CONF['_now']);
        $interval = (int)Config::get('cb_max_upcoming_days');
        if ($interval > 0) {
            $cb_max_date = $_dt
                ->add(new \DateInterval("P{$interval}D"))
                ->toMySQL(true);
        } else {
            // no limit by days.
            $cb_max_date = '9999-12-31';
        }
        $dup_chk = Config::get('cb_dup_chk');

        $EventSet = EventSet::create()
            ->withLimit(empty($dup_chk) ? $limit : 0)
            ->withUpcoming(1);

        /*// If checking for duplicates, get all events in the range since
        // we don't know how many dups there will be.
        $opts = array(
            'limit' => empty($dup_chk) ? $limit : 0,
            'show_upcoming' => 1,
        );*/

        $Y = $_CONF['_now']->format('Y');
        $D = $_CONF['_now']->format('d');
        $M = $_CONF['_now']->format('m');
        switch (Config::get('range_centerblock')) {
        case TimeRange::PAST:         // past events
            $dt = clone ($_CONF['_now']);
            $start = $dt->sub(new \DateInterval('P1M'))->format('Y-m-d');
            $dt = clone ($_CONF['_now']);
            $end = $dt->sub(new \DateInterval('P1D'))->format('Y-m-d');
            $limit = 0;     // special, we need to get all events since we can't count back
            $EventSet->withOrder('DESC');
            break;
        case TimeRange::WEEK:         // this week
            $start = DateFunc::beginOfWeek($D, $M, $Y);
            $end = DateFunc::endOfWeek($D, $M, $Y);
            break;
        case TimeRange::MONTH:         // upcoming month
            $start = DateFunc::beginOfMonth($M, $Y);
            $end = DateFunc::dateFormat(DateFunc::daysInMonth($M, $Y), $M, $Y);
            break;
        case TimeRange::UPCOMING:         // upcoming events
        default:
            $EventSet->withUpcoming(true);
            $start = $_CONF['_now']->format('Y-m-d');
            $end = $cb_max_date;
            break;
        }

        $events = $EventSet
            ->withStart($start)
            ->withEnd($end)
            ->withCenterblock(true)
            ->withFields(array('det.title','det.summary','det.full_description'))
            ->getEvents();
        if (empty($events) || !is_array($events)) {
            return $retval;
        }

        // Special handling needed to get the latest X past events.  We have a bunch
        // from the query (to make sure we got enough).  Now pick out the last X.
        if (Config::get('range_centerblock') == TimeRange::PAST) {
            $limit = (int)Config::get('limit_block');     // Need this value again
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
            'hidesmall' => $hidesmall,
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
            if ($date == '_empty_') {
                continue;
            }
            foreach ($day as $A) {
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
                if ($count > $limit) {
                    break;
                }

                // Prepare the summary for display. Remove links and autotags
                $summary = empty($A['summary']) ? $A['title'] : $A['summary'];
                if ($strip_tags) {
                    $summary = strip_tags($summary, $allowed_tags);
                }
                //$summary = preg_replace('!^<p>(.*?)</p>$!i', '$1', $summary);
                if (!empty($patterns)) {
                    $summary = preg_replace($patterns, '', $summary);
                }

                if (!empty($length) && $length >= 1) {
                    if (strlen($summary) > $length) {
                        $summary = substr($summary, 0, $length);
                        $summary = $summary . '...';
                    }
                }
                $s_ts1 = strtotime($A['rp_start']);
                $e_ts1 = strtotime($A['rp_end']);
                $cssid = ($cssid == 1) ? 2: 1;

                if (isset($A['postmode']) && $A['postmode'] != 'plaintext') {
                    $full_dscp = PLG_replaceTags($A['full_description']);
                } else {
                    $full_dscp = $A['full_description'];
                }
                $ev_url = COM_buildUrl(EVLIST_URL . '/view.php?rid=' . $A['rp_id']);
                if (isset($A['url']) && !empty($url)) {
                    $ev_link = COM_createLink(
                        $A['title'],
                        $A['url'],
                        array(
                            'target' => '_blank',
                        )
                    );
                } else {
                    $ev_link = COM_createLink($A['title'], $ev_url);
                }

                $Cal = Calendar::getInstance($A['cal_id']);
                $Det = Detail::getInstance($A['rp_det_id']);
                $email = EVLIST_obfuscate($Det->getEmail());
                if ($_CONF['show_topic_icon']) {
                    $cal_image = $Cal->getImageUrl();
                } else {
                    $cal_image_url = array('url' => '', 'height' => '', 'width' => '');
                }
                $T->set_var(array(
                    'cssid'     => $cssid,
                    'is_birthday' => $A['cal_id'] == -2 ? true : false,
                    'eid'       => $A['rp_id'],
                    'pi_url'    => EVLIST_URL,
                    'title'     => $A['title'],
                    'summary'   => $summary,
                    'full_dscp' => $full_dscp,
                    'contact'   => $Det->getContact(),
                    'location'  => $Det->getLocation(),
                    'ev_link'   => $ev_link,
                    'ev_url'    => $ev_url,
                    'street'    => $Det->getStreet(),
                    'city'      => $Det->getCity(),
                    'province'  => $Det->getProvince(),
                    'country'   => $Det->getCountry(),
                    'email'     => $email,
                    'phone'     => $Det->getPhone(),
                    'startdate' => EVLIST_formattedDate($s_ts1),
                    'enddate'   => EVLIST_formattedDate($e_ts1),
                    'starttime1' => EVLIST_formattedTime($s_ts1),
                    'endtime1'  => EVLIST_formattedTime($e_ts1),
                    'allday'    => $A['allday'],
                    'adblock'   => PLG_displayAdBlock('evlist_centerblock', $count),
                    'multiday'  => ($A['rp_date_start'] != $A['rp_date_end']),
                    'cal_img_title' => $Cal->getName(),
                    'cal_img_url' => $cal_image['url'],
                    'cal_img_height' => $cal_image['height'],
                    'cal_img_width' => $cal_image['width'],
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
                $T->parse('eRow', 'eventRow', true);
            }
        }

        $T->parse('output', 'centerblock');
        $retval .= $T->finish($T->get_var('output'));
        if ($use_outputfilter) {
            $retval = PLG_outputFilter($retval, 'evlist_cblock');
        }
        return $retval;
    }

}
