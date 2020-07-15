<?php
/**
 * Daily View functions for the evList plugin.
 *
 * @author      Lee P. Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017 Lee Garner <lee@leegarner.com
 * @package     evlist
 * @version     v1.4.3
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Views;


/**
 * Display a single-day calendar view.
 * @package evlist
 */
class dayView extends \Evlist\View
{
    /**
     * Construct the daily view class.
     *
     * @param   integer $year   Year to display, default is current year
     * @param   integer $month  Starting month
     * @param   integer $day    Starting day
     * @param   integer $cat    Category to show
     * @param   integer $cal    Calendar to show
     * @param   string  $opts   Optional template modifier, e.g. "print"
     */
     public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        $this->type = 'day';
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }


    /**
     * Get the actual calendar view content.
     *
     * @return  string      HTML for calendar content
     */
    public function Content()
    {
        global $_CONF, $_EV_CONF, $LANG_EVLIST, $LANG_MONTH, $LANG_WEEK, $_USER;

        $retval = '';
        $today_sql = sprintf('%d-%02d-%02d', $this->year, $this->month, $this->day);
        $today = new \Date($today_sql);
        $dtPrev = new \Date($today->toUnix() - 86400);
        $dtNext = new \Date($today->toUnix() + 86400);
        $monthname = $LANG_MONTH[$this->month];
        $dayofweek = $today->dayofweek;
        if ($dayofweek == 7) $dayofweek = 0;
        $dayname = $LANG_WEEK[$dayofweek + 1];
        $token = SEC_createToken(); // for image deletion links

        $tpl = $this->getTemplate();
        $T = new \Template(EVLIST_PI_PATH . '/templates/dayview');
        $T->set_file(array(
            'column'    => 'column.thtml',
            'event'     => 'singleevent.thtml',
            'dayview'   => $tpl . '.thtml',
        ) );
        $events = EVLIST_getEvents(
            $today_sql, $today_sql,
            array('cat'=>$this->cat, 'cal'=>$this->cal)
        );
        list($allday, $hourly) = $this->getViewData($events);

        // Get allday events
        $alldaycount = count($allday);
        if ($alldaycount > 0) {
            for ($i = 1; $i <= $alldaycount; $i++) {
                $A = current($allday);
                $this->addCalUsed($A);

                $T->set_var(array(
                    'event_time'        => $LANG_EVLIST['allday'],
                    'rp_id'             => $A['rp_id'],
                    'event_title'       => stripslashes($A['title']),
                    'event_summary'     => stripslashes($A['summary']),
                    'bgcolor'           => $A['bgcolor'],
                    'fgcolor'       => $A['fgcolor'],
                    'cal_id'        => $A['cal_id'],
                    'br'            => $i < $alldaycount ? '<br />' : '',
                    'show'      => self::getCalShowPref($A['cal_id']) ? 'block' : 'none',
                    'icon'      => EVLIST_getIcon($A['cal_icon']),
                ) );
                switch ($A['cal_id']) {
                case -1:
                    $T->set_var('ev_url', $A['url']);
                    break;
                default:
                    $T->clear_var('ev_url');
                    break;
                }
                $T->parse('allday_events', 'event', true);
                next($allday);
            }
        } else {
            $T->set_var('allday_events', '&nbsp;');
        }

        for ($i = 0; $i < 24; $i++) {
            $link = date($_CONF['timeonly'], mktime($i, 0));
            if (EVLIST_canSubmit()) {
                $link = '<a href="' . EVLIST_URL . '/event.php?edit=x&amp;month=' .
                        $today->month . '&amp;day=' . $today->day .
                        '&amp;year=' . $today->year .
                        '&amp;hour=' . $i . '">' . $link . '</a>';
            }
            $T->set_var ($i . '_hour',$link);
        }

        // Get hourly events
        for ($i = 0; $i <= 23; $i++) {

            $hourevents = $hourly[$i];
            $numevents = count($hourevents);

            $T->clear_var('event_entry');
            for ($j = 1; $j <= $numevents; $j++) {
                $A = current($hourevents);
                $tz = $A['data']['tzid'] == 'local' ? $_USER['tzid'] : $A['data']['tzid'];
                $s_dt = new \Date($A['data']['rp_date_start'] . ' ' . $A['data']['rp_time_start1'], $tz);
                $e_dt = new \Date($A['data']['rp_date_end'] . ' ' . $A['data']['rp_time_end1'], $tz);

                $this->addCalUsed($A['data']);

                if ($s_dt->format('Y-m-d', true) != $today->format('Y-m-d', true)) {
                    $start_time = $s_dt->format($_CONF['shortdate']) . ' @ ';
                } else {
                    $start_time = '';
                }
                $start_time .= $A['time_start'];
                $end_time = $A['time_end'];

                // Show the timezone abbr. if not "user local"
                if ($A['data']['tzid'] != 'local') $end_time .= ' (' . $e_dt->format('T', true) . ')';
                $T->set_var(array(
                    'eid'               => $A['data']['rp_ev_id'],
                    'rp_id'             => $A['data']['rp_id'],
                    'event_title'       => stripslashes($A['data']['title']),
                    'event_summary' => htmlspecialchars($A['data']['summary']),
                    'fgcolor'       => $A['data']['fgcolor'],
                    'bgcolor'       => '',
                    'cal_id'        => $A['data']['cal_id'],
                    'event_time'    => $start_time . ' - ' . $end_time,
                    'show'      => self::getCalShowPref($A['data']['cal_id']) ? 'block' : 'none',
                    'icon'      => EVLIST_getIcon($A['data']['cal_icon']),
                ) );
                // Only evlist and meetup events are hourly, birthdays are
                // handled as allday events above.
                if ($A['data']['cal_id'] == -1) {
                    $T->set_var('ev_url', $A['data']['url']);
                } else {
                    $T->clear_var('ev_url');
                }

                if ($j < $numevents) {
                    $T->set_var('br', '<br />');
                } else {
                    $T->set_var('br', '');
                }
                $T->parse ('event_entry', 'event',
                                       ($j == 1) ? false : true);
                next($hourevents);
            }
            $link = date($_CONF['timeonly'], mktime($i, 0));
            if (EVLIST_canSubmit()) {
                $link = '<a href="' . EVLIST_URL . '/event.php?edit=x&amp;month=' .
                        $today->month . '&amp;day=' . $today->day .
                        '&amp;year=' . $today->year .
                        '&amp;hour=' . $i . '">' . $link . '</a>';
            }
            $T->parse ($i . '_cols', 'column', true);
        }
        $T->set_var(array(
            'month'         => $today->month,
            'day'           => $today->day,
            'year'          => $today->year,
            'prevmonth'     => $dtPrev->format('n', false),
            'prevday'       => $dtPrev->format('j', false),
            'prevyear'      => $dtPrev->format('Y', false),
            'nextmonth'     => $dtNext->format('n', false),
            'nextday'       => $dtNext->format('j', false),
            'nextyear'      => $dtNext->format('Y', false),
            'urlfilt_cal'   => $this->cal,
            'urlfilt_cat'   => $this->cat,
            'cal_header'    => $this->Header(),
            'cal_footer'    => $this->Footer(),
            'pi_url'        => EVLIST_URL,
            'currentday'    => $dayname. ', ' . $today->format($_CONF['shortdate']),
            'week_num'      => $today->format('W'),
            'cal_checkboxes'=> $this->getCalCheckboxes(),
            'site_name'     => $_CONF['site_name'],
            'site_slogan'   => $_CONF['site_slogan'],
        ) );
        return $T->parse('output', 'dayview');
    }


    /**
     * Organizes events by hour, and separates all-day events.
     *
     * @param   array   $events     Array of all events
     * @return  array               Array of 2 arrays, allday and hourly
     */
    function getViewData($events)
    {
        global $_CONF, $_EV_CONF;

        $hourlydata = array(
            0   => array(), 1   => array(), 2   => array(), 3   => array(),
            4   => array(), 5   => array(), 6   => array(), 7   => array(),
            8   => array(), 9   => array(), 10  => array(), 11  =>array(),
            12  => array(), 13  => array(), 14  => array(), 15  => array(),
            16  => array(), 17  => array(), 18  => array(), 19  => array(),
            20  => array(), 21  => array(), 22  => array(), 23  => array(),
        );
        $alldaydata = array();

        // Events are keyed by hour, so read through each hour
        foreach ($events as $date=>$E) {
            // Now read each event contained in each hour
            foreach ($E as $id=>$A) {
                // remove serialized data, not needed for display and interferes
                // with json encoding.
                unset($A['rec_data']);
                unset($A['options']);
                if ($A['cal_name'] != 'meetup' &&
                    ( $A['allday'] == 1 ||
                    ( ($A['rp_date_start'] < $this->today_sql) &&
                    ($A['rp_date_end'] > $this->today_sql) ) )
                ) {
                    // This is an allday event, or spans days
                    $alldaydata[] = $A;
                } else {
                    // This is an event with start/end times.  For non-recurring
                    // events, see if it actually starts before or after today
                    // and adjust the times accordingly.
                    if ($A['rp_date_start'] < $this->today_sql) {
                        $tm_tmp = explode(':', $A['rp_time_start1']);
                        $hr = $tm_tmp[0];
                        $min = $tm_tmp[1];
                        $hr = '00';
                        $A['rp_times_start1'] = implode(':', array($hr, $min, '00'));
                    //} else {
                    //    $starthour = date('G', strtotime($A['rp_date_start'] .
                    //                    ' ' . $A['rp_time_start^1']));
                    }
                    if ($A['rp_date_end'] > $this->today_sql) {
                        $tm_tmp = explode(':', $A['rp_time_end1']);
                        $hr = '23';;
                        $min = $tm_tmp[1];
                        $A['rp_times_end1'] = implode(':', array($hr, $min, '00'));
                    }
                    $dtStart = new \Date(strtotime($A['rp_date_start'] .
                                    ' ' . $A['rp_time_start1']));
                    $dtEnd = new \Date(strtotime($A['rp_date_end'] .
                                    ' ' . $A['rp_time_end1']));

                    // Save the start & end times in separate variables.
                    // This way we can add $A to a different hour if it's a split.
                    //if (!isset($hourlydata[$starthour]))
                    //    $hourlydata[$starthour] = array();
                    // Set localized, formatted start and end time fields
                    $starthour = $dtStart->format('G', false); // array index
                    $time_start = $dtStart->format($_CONF['timeonly'], false);
                    $time_end = $dtEnd->format($_CONF['timeonly'], false);
                    $hourlydata[(int)$starthour][] = array(
                        'starthour'  => $starthour,
                        'time_start' => $time_start,
                        'time_end'   => $time_end,
                        'data'       => $A,
                    );

                    if (isset($A['split']) && $A['split'] == 1 &&
                        $A['rp_time_end2'] > $A['rp_time_start2']) {
                        // This is a split event, second half occurs later today.
                        // Events spanning multiple days can't be split, so we
                        // know that the start and end times are on the same day.
                        //$starthour = date('G', strtotime($A['rp_date_start'] .
                        //                ' ' . $A['rp_time_start2']));
                        $dtStart->setTimestamp(strtotime($A['rp_date_start'] .
                                ' ' . $A['rp_time_start2']));
                        $starthour = $dtStart->format('G', false);
                        $time_start = $dtStart->format($_CONF['timeonly'], false);
                        $dtEnd->setTimestamp(strtotime($A['rp_date_start'] .
                                ' ' . $A['rp_time_end2']));
                        $time_end = $dtEnd->format($_CONF['timeonly'], false);
                        $hourlydata[(int)$starthour][] = array(
                            'starthour' => $starthour,
                            'time_start' => $time_start,
                            'time_end'   => $time_end,
                            'data'       => $A,
                        );
                    }
                }
            }
        }
        return array($alldaydata, $hourlydata);
    }

}

?>
