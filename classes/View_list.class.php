<?php
/**
*   View functions for the evList plugin.
*   Creates daily, weekly, monthly and yearly calendar views
*
*   @author     Lee P. Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com
*   @package    evlist
*   @version    1.4.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;

/**
*   Create a list of events
*/
class View_list extends View
{
    /*
    *   Construct the list view
    *
    *   @param  integer $year   Year to display, default is current year
    *   @param  integer $month  Starting month
    *   @param  integer $day    Starting day
    *   @param  integer $cat    Event category
    *   @param  integer $cal    Calendar ID
    *   @param  string  $opt    Optional template modifier, e.g. "print"
    */
    public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        $this->type = 'list';
        $this->incl_dt_sel = false;  // disable date/range selections
        if (!isset($opts['range'])) {
            $this->range = (int)SESS_getVar('evlist.range');
        }
        if ($this->range < 1) $this->range = 2; // default to "upcoming"
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }


    /**
    *   Create the list output
    *
    *   @return string  HTML for the event list
    */
    public function Content()
    {
        global $_CONF, $_EV_CONF, $_USER, $_TABLES, $LANG_EVLIST, $_USER;

        $retval = '';
        $T = new \Template(EVLIST_PI_PATH . '/templates/');
        $T->set_file('index', 'index.thtml');

        if (EVLIST_canSubmit()) {
            $add_event_link = EVLIST_URL . '/event.php?edit=x';
        } else {
            $add_event_link = '';
        }

        $T->set_var(array(
            'action' => EVLIST_URL . '/index.php',
            'range_options' => EVLIST_GetOptions($LANG_EVLIST['ranges'], $this->range),
            'add_event_link' => $add_event_link,
            'add_event_text' => $LANG_EVLIST['add_event'],
            'rangetext'     =>  $LANG_EVLIST['ranges'][$this->range],
        ) );

        $page = empty($_GET['page']) ? 1 : (int)$_GET['page'];
        $opts = array('cat'=>$this->cat,
                'page'=>$page,
                'limit'=>$_EV_CONF['limit_list'],
                'cal'=>$this->cal,
            );
        switch ($this->range) {
        case 1:         // past
            $start = EV_MIN_DATE;
            $end = $_EV_CONF['_now']->toMySQL(true);
            $opts['order'] = 'DESC';
            break;
        case 3:         //this week
            $start = \Date_Calc::beginOfWeek();
            $end = \Date_Calc::endOfWeek();
            break;
        case 4:         //this month
            $start = \Date_Calc::beginOfMonth();
            $year = \Date_Calc::getYear();
            $month = \Date_Calc::getMonth();
            $day = \Date_Calc::daysInMonth($month, $year);
            $end = \Date_Calc::dateFormat($day, $month, $year, '%Y-%m-%d');
            break;
        case 2:         //upcoming
        default:
            $opts['upcoming'] = true;
            $start = $_EV_CONF['_today'];
            $dt = new \Date($_EV_CONF['_today_ts'] + (86400 * $_EV_CONF['max_upcoming_days']), $_CONF['timezone']);
            $end = $dt->format('Y-m-d', true);
            break;
        }

        $events = EVLIST_getEvents($start, $end, $opts);

        if (!empty($this->cat)) {
            $andcat = '&amp;cat=' . $this->cat;
        } else {
            $andcat = '';
        }

        if (empty($events)) {
            //return empty list msg
            $T->set_var(array(
                'title' => '',
                'block_title' => $block_title,
                'empty_listmsg' => $LANG_EVLIST['no_match'],
            ) );
        } else {
            //populate list
            $T->set_file(array(
                'item' => 'list_item.thtml',
                'editlinks' => 'edit_links.thtml',
                'category_form' => 'category_dd.thtml'
            ));

            // Track events that have been shown so we show them only once.
            $already_shown = array();
            foreach ($events as $date => $daydata) {
                foreach ($daydata as $A) {
                    if (array_key_exists($A['rp_id'], $already_shown)) {
                        continue;
                    } else {
                        $already_shown[$A['rp_id']] = 1;
                    }

                    // Prepare the link to the event, internal for internal
                    // events, new window for meetup events
                    if ($A['cal_id'] > 0) {
                        $url = COM_buildURL(EVLIST_URL . '/event.php?view=repeat&eid=' .
                            $A['rp_id'] . $timestamp . $andrange . $andcat);
                        $url_attr = array();
                    } elseif (!empty($A['url'])) {
                        // This is a meetup event with a URL
                        $url = COM_buildURL($A['url']);
                        $url_attr = array('target' => '_blank');
                    }
                    $title = COM_stripslashes($A['title']);
                    if (!empty($url)) {
                        $titlelink = COM_createLink($title, $url, $url_attr);
                    } else {
                        $titlelink = $A['title'];
                    }

                    $summary = PLG_replaceTags(COM_stripslashes($A['summary']));
                    $tz = $A['tzid'] == 'local' ? $_USER['tzid'] : $A['tzid'];
                    $d = new \Date($A['rp_date_start'] . ' ' . $A['rp_time_start1'], $tz);
                    $datesummary = sprintf($LANG_EVLIST['event_begins'],
                        $d->format($_CONF['date'], true));
                    $morelink = COM_buildURL(EVLIST_URL . '/event.php?view=repeat&eid=' .
                        $A['rp_id'] . $andrange . $andcat);
                    $morelink = '<a href="' . $morelink . '">' .
                        $LANG_EVLIST['read_more'] . '</a>';

                    if (empty($A['email'])) {
                        $contactlink = $_CONF['site_url'] . '/profiles.php?uid=' .
                            $A['owner_id'];
                    } else {
                        $contactlink = 'mailto:' .
                                EVLIST_obfuscate($A['email']);
                    }
                    $contactlink = '<a href="' . $contactlink . '">' .
                        $LANG_EVLIST['ev_contact'] . '</a>';

                    $T->set_var(array(
                        'title' => $titlelink,
                        'date_summary' => $datesummary,
                        'summary' => $summary,
                        'more_link' => $morelink,
                        'contact_link' => $contactlink,
                        'contact_name' => $A['contact'],
                        'owner_name' => COM_getDisplayName($A['owner_id']),
                        'block_title' => $block_title,
                        'category_links' => EVLIST_getCatLinks($A['ev_id'], $andrange),
                        'cal_id' => $A['cal_id'],
                        'cal_name' => $A['cal_name'],
                        'cal_fgcolor' => $A['fgcolor'],
                        'cal_bgcolor' => $A['bgcolor'],
                    ) );

                    $T->parse('event_item','item', true);
                }
            }
        }
        $T->parse('output', 'index');
        $retval .= $T->finish($T->get_var('output'));

        // Set page navigation
        $retval .= EVLIST_pagenav(count($events));
        //$retval .= EVLIST_pagenav($start, $end, $category, $page, $range, $calendar);
        return $retval;
    }
}

?>