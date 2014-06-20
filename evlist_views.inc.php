<?php
/**
*   View functions for the evLIst plugin.
*   Creates daily, weekly, monthly and yearly calendar views
*
*   @author     Lee P. Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011 Lee Garner <lee@leegarner.com
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/


/**
*   Display the common header for all calendar views.
*
*   @param  integer $year   Year being displayed (required)
*   @param  integer $month  Month being displayed (require)
*   @param  integer $day    Day being displayed (required)
*   @param  string  $view   View type (Optional 'year', 'month', etc.)
*   @param  integer $cat    Category (optional)
*   @param  integer $cal    Calendar ID (optional)
*   @param  integer $range  Range being displayed (optional)
*   @return string          HTML for calendar header
*/
function EVLIST_calHeader($year, $month, $day, $view='month', 
    $cat=0, $cal=0, $range=0)
{
    global $_CONF, $_EV_CONF, $LANG_EVLIST, $LANG_MONTH, $_TABLES;

    $T = new Template(EVLIST_PI_PATH . '/templates');
    $T->set_file('calendar_header', 'calendar_header.thtml');

    $thisyear = date('Y');
    $thismonth = date('m');
    $thisday = date('d');

    // Determine if the current user is allowed to add an event, and borrow
    // some space in $_EV_CONF to store a flag for other functions to use.
    $isAnon = COM_isAnonUser();
    if (($isAnon && ($_EV_CONF['can_add'] & EV_ANON_CAN_ADD)) ||
            ($_EV_CONF['can_add'] & EV_USER_CAN_ADD) ||
            SEC_hasRights('evlist.admin')) {
        $_EV_CONF['_can_add'] = 1;
    } else {
        $_EV_CONF['_can_add'] = 0;
    }

    $cat = (int)$cat;
    $type_options = COM_optionList($_TABLES['evlist_categories'],
            'id,name', $cat, 1, 'status=1');
    $range_options = EVLIST_GetOptions($LANG_EVLIST['ranges'], $range);

    // Figure out the add event link, depending on the view.
    if ($_EV_CONF['_can_add'] == 1) {
        $add_event_link = EVLIST_URL . '/event.php';
        switch ($view) {
        case 'day':         // Add the current day
            $T->set_var('addlink_day', $day);
        case 'week':
        case 'month':
            $T->set_var('addlink_month', $month);
        case 'year':
            $T->set_var('addlink_year', $year);
        }
    } else {
        $add_event_link = '';
    }

    $T->set_var(array(
        'pi_url'    => EVLIST_URL,
        'year'      => (int)$year,
        'month'     => (int)$month,
        'day'       => (int)$day,
        'thisyear'  => (int)$thisyear,
        'thismonth' => (int)$thismonth,
        'thisday'   => (int)$thisday,
        'thisview'  => $view,
        'add_event_link' => $add_event_link,
        'add_event_text' => $LANG_EVLIST['add_event'],
        'event_type_select' => $type_options,
        'range_options' => $range_options,
        'action_url'    => EVLIST_URL . '/index.php',
        'iso_lang'  => EVLIST_getIsoLang(),
        'view'      => $view,
        'curdate'   => sprintf("%d-%02d-%02d", $year, $month, $day),
        'urlfilt_cal' => $cal,
        'urlfilt_cat' => $cat,
    ) );

    $cal_selected = isset($_GET['cal']) ? (int)$_GET['cal'] : 0;
    $T->set_var('cal_select', COM_optionList($_TABLES['evlist_calendars'],
                    'cal_id,cal_name', $cal_selected, 1, 
                    '1=1 '. COM_getPermSQL('AND'))
    );

    if (isset($_GET['range']) && !empty($_GET['range'])) {
        $T->set_var('range_url', 'range=' . $_GET['range']);
    }
        
    if ($view == 'detail') {
        // Set marker to disable category/range dropdowns
        $T->set_var('showing_detail', 'true');
    }

    if ($view == 'list' || $view == 'detail') {
        $T->set_var('event_type', $event_type);
    } else {
        // Create the jump-to-date selectors
        $options = '';
        for ($i = 1; $i < 32; $i++) {
            $sel = $i == $day ? EVSELECTED : '';
            $options .= "<option value=\"$i\" $sel>$i</option>" . LB;
        }
        $T->set_var('day_select', $options);

        $options = '';
        for ($i = 1; $i < 13; $i++) {
            $sel = $i == $month ? EVSELECTED : '';
            $options .= "<option value=\"$i\" $sel>{$LANG_MONTH[$i]}</option>" .
                LB;
        }
        $T->set_var('month_select', $options);

        $options = '';
        $lastyear = $thisyear + 6;
        for ($i = $thisyear - 2; $i < $lastyear; $i++) {
            $sel = $i == $year ? EVSELECTED : '';
            $options .= "<option value=\"$i\" $sel>$i</option>" . LB;
        }
        $T->set_var('year_select', $options);
    }

    $images = array('day', 'week', 'month', 'year', 'list');
    $options = '';
    foreach ($images as $v) {
        if ($v == $view) {
            $sel = EVSELECTED;
            $T->set_var($v .'_img', $v . '_on.png');
        } else {
            $sel = '';
            $T->set_var($v .'_img', $v . '_off.png');
        }
        if ($v != 'list') {
            $options .= '<option value="' . $v . '" ' . $sel . ' >' .
                    $LANG_EVLIST['periods'][$v] . '</option>' . LB;
        }
    }
    $T->set_var('view_select', $options);
    $T->parse('output', 'calendar_header');
    return $T->finish($T->get_var('output'));

}


/**
*   Display the calendar footer
*
*   @return string  HTML for calendar footer
*/
function EVLIST_calFooter($calendars = '')
{
    global $LANG_EVLIST;

    $T = new Template(EVLIST_PI_PATH . '/templates');
    $T->set_file('calendar_footer', 'calendar_footer.thtml');

    $rssA = EVLIST_getFeedLinks();
    $rss_links = '';
    if (!empty($rssA)) {
        foreach ($rssA as $rss) {
            $rss_links .= '<a href="' . $rss['feed_url'] . '">' .
                    $rss['feed_title'] . '</a>&nbsp;&nbsp;';
        }
    }

    // Get ical options for displayed calendars
    $ical_links = '';
    if (is_array($calendars)) {
        $webcal_url = preg_replace('/^https?/', 'webcal', EVLIST_URL);
        foreach ($calendars as $cal) {
            if ($cal['cal_ena_ical']) {
                $ical_links .= '<a href="' . $webcal_url . '/ical.php?cal=' .
                    $cal['cal_id'] . '">' . $cal['cal_name'] . 
                    '</a>&nbsp;&nbsp;';
            }
        }
    }

    $T->set_var(array(
        'pi_url'        => EVLIST_URL,
        'webcal_url'    => $webcal_url,
        'feed_links'    => $rss_links,
        'ical_links'    => $ical_links,
    ) );

    $T->parse('output', 'calendar_footer');
    return $T->finish($T->get_var('output'));
}


/**
*   Display a single-day calendar view.
*
*   @param  integer $year   Year to display, default is current year
*   @param  integer $month  Starting month
*   @param  integer $day    Starting day
*   @param  integer $cat    Category to show
*   @param  integer $cal    Calendar to show
*   @return string          HTML for calendar page
*/
function EVLIST_dayview($year=0, $month=0, $day=0, $cat=0, $cal=0, $opt='')
{
    global $_CONF, $_EV_CONF, $LANG_WEEK, $LANG_EVLIST;

    $retval = '';
    list($currentyear, $currentmonth, $currentday) = 
        explode('-', $_EV_CONF['_today']);

    // Default to the current day
    if ($year == 0) $year = $currentyear;
    if ($month == 0) $month = $currentmonth;
    if ($day == 0) $day = $curentday;
    $cat = (int)$cat;
    $cal = (int)$cal;

    $starting_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
    $ending_date = $starting_date;
    $prevstamp = mktime(0, 0, 0,$month, $day - 1, $year);
    $nextstamp = mktime(0, 0, 0,$month, $day + 1, $year);
    $thedate = COM_getUserDateTimeFormat(mktime(0,0,0,$month,$day,$year));
    $monthname = $LANG_MONTH[$month];
    $dow = Date_Calc::dayOfWeek($day, $month, $year) + 1;
    $dayname = $LANG_WEEK[$dow];

    $tpl = 'dayview';
    if ($opt == 'print') $tpl .= '_print';
    $T = new Template(EVLIST_PI_PATH . '/templates/dayview');
    $T->set_file(array(
        'column'    => 'column.thtml',
        'event'     => 'singleevent.thtml',
        'dayview'   => $tpl . '.thtml',
    ) );

    $events = EVLIST_getEvents($starting_date, $ending_date, 
            array('cat'=>$cat, 'cal'=>$cal));
    $calendars_used = array();
    list($allday, $hourly) = EVLIST_getDayViewData($events, $starting_date);

    // Get allday events
    $alldaycount = count($allday);
    if ($alldaycount > 0) {
        for ($i = 1; $i <= $alldaycount; $i++) {
            $A = current($allday);
            $calendars_used[$A['cal_id']] = array(
                    'cal_name' => $A['cal_name'],
                    'cal_ena_ical' => $A['cal_ena_ical'],
                    'cal_id' => $A['cal_id'],
                    'fgcolor' => $A['fgcolor'],
                    'bgcolor' => $A['bgcolor'],
            );

            $T->set_var(array(
                'delete_imagelink'  => EVLIST_deleteImageLink($A, $token),
                'event_time'        => $LANG_EVLIST['allday'],
                'rp_id'             => $A['rp_id'],
                'event_title'       => stripslashes($A['title']),
                'event_summary'     => stripslashes($A['summary']),
                'bgcolor'           => $A['bgcolor'],
                'fgcolor'       => $A['fgcolor'],
                'cal_id'        => $A['cal_id'],
            ) );
            if ($i < $alldaycount) {
                $T->set_var('br', '<br' . XHTML . '>');
            } else {
                $T->set_var('br', '');
            }
            $T->parse('allday_events', 'event', true);
            next($allday);
        }
    } else {
        $T->set_var('allday_events', '&nbsp;');
    }

    // Get hourly events
    for ($i = 0; $i <= 23; $i++) {
        $hourevents = $hourly[$i];
        $numevents = count($hourevents);
        if ($numevents > 0) {

            for ($j = 1; $j <= $numevents; $j++) {
                $A = current($hourevents);

                $calendars_used[$A['data']['cal_id']] = array(
                    'cal_name' => $A['data']['cal_name'],
                    'cal_ena_ical' => $A['data']['cal_ena_ical'],
                    'cal_id' => $A['data']['cal_id'],
                    'fgcolor' => $A['data']['fgcolor'],
                    'bgcolor' => $A['data']['bgcolor'],
                );

                if ($A['data']['rp_date_start'] == $starting_date) {
                    $start_time = date($_CONF['timeonly'],
                        strtotime($A['data']['rp_date_start'] . ' ' .
                            $A['time_start']));
                            //strtotime($A['evt_start'] . ' ' . $A['timestart']));
                } else {
                    $start_time = date(
                        $_CONF['shortdate'].' @ ' . $_CONF['timeonly'],
                        strtotime($A['data']['rp_date_start'] . ' '. 
                            $A['time_start']));
                }

                if ($A['data']['rp_date_end'] == $ending_date) {
                    $end_time = date($_CONF['timeonly'],
                            strtotime($A['data']['rp_date_end'] . ' ' .
                                $A['time_end']));
                } else $end_time = date(
                        $_CONF['shortdate'].' @ ' . $_CONF['timeonly'],
                        strtotime($A['data']['rp_date_end'] . ' ' . 
                            $A['time_end']));

                if ($start_time == ' ... ' && $end_time == ' ... ')
                    $T->set_var('event_time', $LANG_EVLIST['allday']);
                else
                    $T->set_var('event_time',
                        $start_time . ' - ' . $end_time);

               $T->set_var(array(
                    'delete_imagelink'  => EVLIST_deleteImageLink($A['data'], $token),
                    'eid'               => $A['data']['rp_ev_id'],
                    'rp_id'             => $A['data']['rp_id'],
                    'event_title'       => stripslashes($A['data']['title']),
                    'event_summary' => htmlspecialchars($A['data']['summary']),
                    'fgcolor'       => $A['data']['fgcolor'],
                    'bgcolor'       => '',
                    'cal_id'        => $A['data']['cal_id'],
                ) );
                if ($j < $numevents) {
                    $T->set_var('br', '<br' . XHTML . '>');
                } else {
                    $T->set_var('br', '');
                }
                $T->parse ('event_entry', 'event',
                                       ($j == 1) ? false : true);
                next($hourevents);
            }
        } else {
            $T->set_var('event_entry','&nbsp;');
        }
        $link = date($_CONF['timeonly'], mktime($i, 0));
        if ($_EV_CONF['_can_add']) {
            $link = '<a href="' . EVLIST_URL . '/event.php?edit=x&amp;month=' .
                        $month . '&amp;day=' . $day . '&amp;year=' . $year .
                        '&amp;hour=' . $i . '">' . $link . '</a>';
        }
        $T->set_var ($i . '_hour',$link);
        $T->parse ($i . '_cols', 'column', true);
    }

    $T->set_var(array(
        'month'         => $month,
        'day'           => $day,
        'year'          => $year,
        'prevmonth'     => strftime('%m', $prevstamp),
        'prevday'       => strftime('%d', $prevstamp),
        'prevyear'      => strftime('%Y', $prevstamp),
        'nextmonth'     => strftime('%m', $nextstamp),
        'nextday'       => strftime('%d', $nextstamp),
        'nextyear'      => strftime('%Y', $nextstamp),
        'urlfilt_cal'   => $cal,
        'urlfilt_cat'   => $cat,
        'cal_header'    => EVLIST_calHeader($year, $month, $day, 'day', 
                            $cat, $cal),
        'cal_footer'    => EVLIST_calFooter($calendars_used),
        'pi_url'        => EVLIST_URL,
        'currentday'    => $dayname. ', ' .
                strftime('%x', mktime(0, 0, 0, $month, $day, $year)),
        'week_num'      => @strftime('%V', $thedate[1]),
        'cal_checkboxes', EVLIST_cal_checkboxes($calendars_used),
        'site_name'     => $_CONF['site_name'],
        'site_slogan'   => $_CONF['site_slogan'],
    ) );

    return $T->parse('output', 'dayview');

}


/**
*   Create a weekly view.
*
*   @param  integer $year   Year to display, default is current year
*   @param  integer $month  Starting month
*   @param  integer $day    Starting day
*   @param  integer $cat    Event category
*   @param  integer $cal    Calendar to show
*   @return string          HTML for calendar page
*/
function EVLIST_weekview($year=0, $month=0, $day=0, $cat=0, $cal=0, $opt='')
{
    global $_CONF, $_EV_CONF, $LANG_MONTH, $LANG_WEEK, $LANG_EVLIST;

    $retval = '';
    list($currentyear, $currentmonth, $currentday) = 
        explode('-', $_EV_CONF['_today']);

    // Default to the current month
    if ($year == 0) $year = $currentyear;
    if ($month == 0) $month = $currentmonth;
    if ($day == 0) $day = $curentday;
    $cat = (int)$cat;
    $cal = (int)$cal;

    // Get the events
    $calendarView = Date_Calc::getCalendarWeek($day, $month, $year, '%Y-%m-%d');
    $start_date = $calendarView[0];
    $end_date = $calendarView[6];
    $events = EVLIST_getEvents($start_date, $end_date,
            array('cat'=>$cat, 'cal'=>$cal));
    $calendars_used = array();

    // Set up next and previous week links
    list($sYear, $sMonth, $sDay) = explode('-', $start_date);
    $prevstamp = mktime(0, 0, 0, $sMonth, $sDay-7, $sYear);
    $nextstamp = mktime(0, 0, 0, $sMonth, $sDay+7, $sYear);

    $tpl = 'weekview';
    $T = new Template(EVLIST_PI_PATH . '/templates/weekview');
    if ($opt == 'print') $tpl .= '_print';
    $T->set_file(array(
        'week'      => $tpl . '.thtml',
        //'events'    => 'weekview/events.thtml',
    ) );

    $start_mname = $LANG_MONTH[(int)$sMonth];
    $last_date = getdate(mktime(0, 0, 0, $sMonth, $sDay + 6, $sYear));
    $end_mname = $LANG_MONTH[$last_date['mon']];
    $end_ynum = $last_date['year'];
    $end_dnum = sprintf('%02d', $last_date['mday']);
    $date_range = $start_mname . ' ' . $sDay;
    if ($year <> $end_ynum) {
        $date_range .= ', ' . $year . ' - ';
    } else {
        $date_range .= ' - ';
    }
    if ($start_mname <> $end_mname) {
        $date_range .= $end_mname . ' ';
    }
    $date_range .= "$end_dnum, $end_ynum";
    $T->set_var('date_range', $date_range);
    $T->set_var('week_num',$thedate[1]);

    $T->set_block('week', 'dayBlock', 'dBlk');
    foreach($calendarView as $idx=>$weekData) {
        $T->clear_var('eBlk');
        $dayname = $LANG_WEEK[$idx+1];
        list($curyear, $curmonth, $curday) = explode('-', $weekData);
        $thedate = COM_getUserDateTimeFormat(
                    mktime(0, 0, 0, $curmonth, $curday, $curyear));
        if ($weekData == $_EV_CONF['_today']) {
            $T->set_var('dayclass', 'weekview-curday');
        } else {
            $T->set_var('dayclass', 'weekview-offday');
        }

        $monthname = $LANG_MONTH[(int)$curmonth];
        $T->set_var('dayinfo', $dayname . ', ' .
            COM_createLink(strftime('%x', $thedate[1]),
                EVLIST_URL . "/index.php?view=day&amp;day=$curday" .
                "&amp;cat={$cat}&amp;cal={$cal}" .
                "&amp;month=$curmonth&amp;year=$curyear")
        );

        if ($_EV_CONF['_can_add']) {
            $T->set_var(array(
                'can_add'       => 'true',
                'curday'        => $curday,
                'curmonth'      => $curmonth,
                'curyear'       => $curyear,
            ) );
        }

        if (!isset($events[$weekData])) {
            // Make sure it's a valid but empty array if no events today
            $events[$weekData] = array();
        }

        $T->set_block('week', 'eventBlock', 'eBlk');
        foreach ($events[$weekData] as $A) {
            //$fgstyle = 'color:' . $A['fgcolor'].';';
            if ($A['allday'] == 1 ||
                    ($A['rp_date_start'] < $weekData &&
                    $A['rp_date_end'] > $weekData)) {
                $event_time = $LANG_EVLIST['allday'];
                /*$event_div = '<div class="monthview_allday" 
                    style="background-color:'. $event['bgcolor'].';">';*/
            } else {
                if ($A['rp_date_start'] == $weekData) {
                    $startstamp = strtotime($weekData . ' ' . $A['rp_time_start1']);
                    $starttime = date('g:i a', $startstamp);
                } else {
                    $starttime = ' ... ';
                }

                if ($A['rp_date_end'] == $weekData) {
                    $endstamp = strtotime($weekData . ' ' . $A['rp_time_end1']);
                    $endtime = date('g:i a', $endstamp);
                } else {
                    $endtime = ' ... ';
                }
                $event_time = $starttime . ' - ' . $endtime;

                if ($A['split'] == 1 && !empty($A['rp_time_start2'])) {
                    $startstamp2 = strtotime($weekData . ' ' . $A['rp_time_start2']);
                    $starttime2 = date('g:i a', $startstamp2);
                    $endstamp2 = strtotime($weekData . ' ' . $A['rp_time_end2']);
                    $endtime2 = date('g:i a', $endstamp2);
                    $event_time .= ' & ' . $starttime2 . ' - ' . $endtime2;
                }
            }
            $calendars_used[$A['cal_id']] = array(
                    'cal_name' => $A['cal_name'],
                    'cal_ena_ical' => $A['cal_ena_ical'],
                    'cal_id' => $event['cal_id'],
                    'fgcolor' => $A['fgcolor'],
                    'bgcolor' => $A['bgcolor'],
            );
            /*$eventlink = '<a class="cal-event" style="' .
                        $fgstyle . '" href="' .
                        EVLIST_URL . '/event.php?eid=' .
                        $A['rp_id'] . '">' . stripslashes($A['title']) .
                        '</a>';*/

            $T->set_var(array(
                'event_times'   => $event_time,
                'event_title'   => htmlspecialchars($A['title']),
                'event_id'      => $A['rp_id'],
                'cal_id'        => $A['cal_id'],
                'delete_imagelink' => EVLIST_deleteImageLink($A, $token),
                //'event_title_and_link' => $eventlink,
                'pi_url'        => EVLIST_URL,
                'fgcolor'       => $A['fgcolor'],
            ) );
            $T->parse('eBlk', 'eventBlock', true);
        }

        $T->parse('dBlk', 'dayBlock', true);
    }

    $T->set_var(array(
        'pi_url'        => EVLIST_URL,
        'cal_header'    => EVLIST_calHeader($year, $month, $day, 'week',
                            $cat, $cal),
        'cal_footer'    => EVLIST_calFooter($calendars_used),
        'prevmonth'     => strftime('%m', $prevstamp),
        'prevday'       => date('j', $prevstamp),
        'prevyear'      => strftime('%Y', $prevstamp),
        'nextmonth'     => strftime('%m', $nextstamp),
        'nextday'       => date('j', $nextstamp),
        'nextyear'      => strftime('%Y', $nextstamp),
        'urlfilt_cat'   => $cat,
        'urlfilt_cal'   => $cal,
        'cal_checkboxes' => EVLIST_cal_checkboxes($calendars_used),
        'site_name'     => $_CONF['site_name'],
        'site_slogan'   => $_CONF['site_slogan'],
        'year'          => $year,
        'month'         => $month,
        'day'           => $day,
    ) );
    $T->parse('output','week');
    return $T->finish($T->get_var('output'));
}


/**
*   Display a monthly calendar.
*   Dates that have events scheduled are highlighted.
*
*   @param  integer $year   Year to display, default is current year
*   @param  integer $month  Starting month
*   @param  integer $day    Starting day
*   @param  integer $cat    Event category
*   @plaram integer $cal    Calendar ID
*   @return string          HTML for calendar page
*/
function EVLIST_monthview($year=0, $month=0, $day=0, $cat=0, $cal=0, $opt='')
{
    global $_CONF, $_EV_CONF, $LANG_MONTH, $LANG_WEEK;

    $retval = '';
    list($currentyear, $currentmonth, $currentday) = 
            explode('-', $_EV_CONF['_today']);

    // Default to the current month
    if ($year == 0) $year = $currentyear;
    if ($month == 0) $month = $currentmonth;
    if ($day == 0) $day = $curentday;
    $cat = (int)$cat;
    $cal = (int)$cal;

    // Set the calendar header.  Done early because the _can_add value is
    // set by this function
    $cal_header = EVLIST_calHeader($year, $month, $day, 'month', $cat, $cal);

    // Get all the dates in the month
    $calendarView = Date_Calc::getCalendarMonth($month, $year, '%Y-%m-%d');
    $x = count($calendarView) - 1;
    $y = count($calendarView[$x]) - 1;
    //$starting_date = sprintf('%4d-%02d-%02d', $year, $month, 1);
    //$lastday = Date_Calc::daysInMonth($month, $year);
    //$ending_date = sprintf('%4d-%02d-%02d', $year, $month, $lastday);
    $starting_date = $calendarView[0][0];
    $ending_date = $calendarView[$x][$y];
    $events = EVLIST_getEvents($starting_date, $ending_date,
            array('cat'=>$cat, 'cal'=>$cal));

    $nextmonth = $month + 1;
    $nextyear = $year;
    if ($nextmonth > 12) {
        $nextmonth = 1;
        $nextyear = $year + 1;
    }

    $prevmonth = $month - 1;
    $prevyear = $year;
    if ($prevmonth < 1) {
        $prevmonth = 12;
        $prevyear = $year -1;
    }

    $tplpath = EVLIST_PI_PATH . '/templates/monthview';
    $tpl = 'monthview';
    if ($opt == 'print') {
        $tpl .= '_print';
    }
    $T = new Template($tplpath);
    $T->set_file(array(
        'monthview'  => $tpl . '.thtml',
        'allday_event' => 'event_allday.thtml',
        'timed_event' => 'event_timed.thtml',
    ) );

    list($y, $m, $d) = explode('-', $starting_date);
    $weekOfYear = Date_Calc::weekOfYear($d, $m, $y);

    $calendars_used = array();
    $i = 0;
    $T->set_block('monthview', 'weekBlock', 'wBlock');
    foreach ($calendarView as $weeknum => $weekdata) {
        list($weekYear, $weekMonth, $weekDay) = explode('-', $weekdata[0]);
        $T->set_var(array(
                'wyear'  => $weekYear,
                'wmonth' => $weekMonth,
                'wday'   => $weekDay,
                'urlfilt_cat' => $cat,
                'urlfilt_cal' => $cal,
                'weeknum' => $weekOfYear,
                $tplx => 'true',
        ) );
        $weekOfYear++;

        foreach ($weekdata as $daynum => $daydata) {
            list($y, $m, $d) = explode('-', $daydata);
            if ($daydata == $_EV_CONF['_today']) {
                $dayclass = 'today';
            } elseif ($m == $month) {
                $dayclass = 'on';
            } else {
                $dayclass = 'off';
            }

            $T->set_var('cal_day_anchortags',
                COM_createLink(sprintf("%02d", $d),
                    EVLIST_URL . '/index.php?view=day&amp;' .
                    "cat={$cat}&amp;cal={$cal}" .
                    "&amp;day=$d&amp;month=$m&amp;year=$y",
                    array('class'=>'cal-date'))
                //. '<hr' . XHTML . '>'
            );

            if (!isset($events[$daydata])) {
                // Just to avoid foreach() errors
                $events[$daydata] = array();
            }

            $dayentries = '';
            $T->clear_var('cal_day_entries');
            $T->set_block('monthview', 'dayBlock', 'dBlock');

            foreach ($events[$daydata] as $event) {

                if (empty($event['title'])) continue;
                $ev_hover = '';

                // Sanitize fields for display.  No HTML in the popup.
                $title = htmlentities(strip_tags($event['title']));
                $summary = htmlentities(strip_tags($event['summary']));

                // add the calendar to the array to create the JS checkboxes
                $calendars_used[$event['cal_id']] = array(
                            'cal_name' => $event['cal_name'],
                            'cal_ena_ical' => $event['cal_ena_ical'],
                            'cal_id' => $event['cal_id'],
                            'fgcolor' => $event['fgcolor'],
                            'bgcolor' => $event['bgcolor'],
                );

                // Create the hover tooltip.  Timed events show the times first
                if ($event['allday'] == 0) {
                    $ev_hover = date($_CONF['timeonly'], 
                        strtotime($event['rp_date_start'] . ' ' . $event['rp_time_start1']) );
                    if ($event['split'] == 1 && !empty($event['rp_time_start2']) ) {
                        $ev_hover .= ' &amp; ' . 
                                date($_CONF['timeonly'], 
                                strtotime($event['rp_date_start'] . ' ' . 
                                $event['rp_time_start2']) );
                    }
                    $ev_hover .= ' - ';
                } else {
                    $ev_hover = '';
                }

                // All events show the summary or title, if available
                if (!empty($summary)) {
                    $ev_hover .= $summary;
                } else {
                    $ev_hover .= $title;
                }

                $T->set_var(array(
                    'cal_id'    => $event['cal_id'],
                    'cal_id_url' => $cal_id,    // calendar requested
                    'cat_id'    => $cat,
                    'ev_hover'  => $ev_hover,
                    'ev_title'  => $event['title'],
                    'eid'       => $event['rp_id'],
                    'fgcolor'   => $event['fgcolor'],
                    'bgcolor'   => $event['bgcolor'],
                    'pi_url'        => EVLIST_URL,
                ) );
                if ($event['allday'] == 1) {
                    $dayentries .= $T->parse('output', 'allday_event', true);
                } else {
                    $dayentries .= $T->parse('output', 'timed_event', true);
                }

            }

            // Now set the vars for the entire day block
            $T->set_var(array(
                'year'          => $y,
                'month'         => $m,
                'day'           => $d,
                //'daterow_style' => 'monthview_daterow',
                'cal_day_style' => $dayclass,
                'pi_url'        => EVLIST_URL,
                'cal_day_entries' => $dayentries,
            ) );

            if ($_EV_CONF['_can_add']) {
                // Add the "Add Event" link for the day
                $T->set_var('can_add', 'true');
            }
            $T->parse('dBlock', 'dayBlock', true);
        }
        $T->parse('wBlock', 'weekBlock', true);
        $T->clear_var('dBlock');
    }

    $T->set_var(array(
        'pi_url'        => EVLIST_URL,
        'thisyear'      => $year,
        'thismonth'     => $month,
        'thismonth_str' => $LANG_MONTH[(int)$month],
        'prevmonth'     => $prevmonth,
        'prevyear'      => $prevyear,
        'nextmonth'     => $nextmonth,
        'nextyear'      => $nextyear,
        'urlfilt_cat'   => $cat,
        'urlfilt_cal'   => $cal,
        'cal_header'    => $cal_header,
        'cal_footer'    => EVLIST_calFooter($calendars_used),
        'cal_checkboxes' => EVLIST_cal_checkboxes($calendars_used),
        'site_name'     => $_CONF['site_name'],
        'site_slogan'   => $_CONF['site_slogan'],
    ) );

    $T->parse('output', 'monthview');
    return $T->finish($T->get_var('output'));
}



/**
*   Display a yearly calendar.
*   Dates that have events scheduled are highlighted.
*
*   @param  integer $year   Year to display, default is current year
*   @param  integer $month  Starting month
*   @param  integer $day    Starting day
*   @param  integer $cat    Category to show
*   @param  integer $cal    Calendar to show
*   @return string          HTML for calendar page
*/
function EVLIST_yearview($year=0, $month=0, $day=0, $cat=0, $cal=0)
{
    global $_CONF, $_CONF_EVLIST, $LANG_MONTH, $LANG_WEEK;

    $retval = '';

    // Default to the current year
    if ($year == 0) $year = date('Y');
    if ($month == 0) $month = date('m');
    if ($day == 0) $day = date('d');
    $cat = (int)$cat;
    $cal = (int)$cal;

    // Get all the dates in the year
    $starting_date = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year));
    $ending_date = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year + 1));
    $calendarView = Date_Calc::getCalendarYear($year, '%Y-%m-%d');
    $events = EVLIST_getEvents($starting_date, $ending_date,
            array('cat'=>$cat, 'cal'=>$cal));

    $T = new Template(EVLIST_PI_PATH . '/templates/yearview');
    $T->set_file(array(
        'yearview'  => 'yearview.thtml',
    ) );

    $count = 0;
    $T->set_block('yearview', 'month', 'mBlock');
    foreach ($calendarView as $monthnum => $monthdata) {
        $monthnum_str = sprintf("%02d", $monthnum+1);

        $count++;
        if (($count-1) % 4 == 0) {
            $T->set_var('st_row', 'true');
        } else {
            $T->clear_var('st_row');
        }

        $M = new Template($_CONF['path']
                        . 'plugins/evlist/templates/yearview');
        $M->set_file(array(
            'smallmonth'  => 'smallmonth.thtml',
        ) );

        $M->set_var('thisyear', $year);
        $M->set_var('month', $monthnum+1);
        $M->set_var('monthname', $LANG_MONTH[$monthnum+1]);

        $M->set_block('smallmonth', 'daynames', 'nBlock');
        for ($i = 0; $i < 7; $i++) {
            $M->set_var('dayname', $LANG_WEEK[$i][0]);
            $M->parse('nBlock', 'daynames', true);
        }

        $M->set_block('smallmonth', 'week', 'wBlock');
        foreach ($monthdata as $weeknum => $weekdata) {
            list($weekYear, $weekMonth, $weekDay) = explode('-', $weekdata[0]);
            $M->set_var(array(
                    'weekyear'  => $weekYear,
                    'weekmonth' => $weekMonth,
                    'weekday'   => $weekDay,
                    'urlfilt_cat' => $cat,
                    'urlfilt_cal' => $cal,
            ) );
            $M->set_block('smallmonth', 'day', 'dBlock');
            foreach ($weekdata as $daynum => $daydata) {
                list($y, $m, $d) = explode('-', $daydata);
                $M->clear_var('no_day_link');
                if ($daydata == $_EV_CONF['_today']) {
                    $dayclass = 'today';
                } elseif ($m == $monthnum_str) {
                    $dayclass = 'on';
                } else {
                    $M->set_var('no_day_link', 'true');
                    $dayclass = 'off';
                }

                if (isset($events[$daydata])) {
                    // Create the mootip hover text
                    $popup = '';
                    $daylinkclass = $dayclass == 'off' ?
                            'nolink-events' : 'day-events';
                    foreach ($events[$daydata] as $event) {
                        // Separate events by a line (if more than one)
                        if (!empty($popup)) $popup .= '<hr' . XHTML . '>' . LB;
                        // Don't show a time for all-day events
                        if ($event['allday'] == 0) {
                            $popup .= date($_CONF['timeonly'], 
                                //strtotime($event->date_start . ' ' . 
                                //    $event->time_start)) . ': ';
                                strtotime($event['date_start'] . ' ' . 
                                    $event['time_start1'])) . ': ';
                        }
                        $popup .= htmlentities($event['title']);
                    }
                    $M->set_var('popup', $popup);
                } else {
                    $daylinkclass = 'day-noevents';
                    $M->clear_var('popup');
                }
                $M->set_var(array(
                    'daylinkclass'  => $daylinkclass,
                    'dayclass'      => $dayclass,
                    'day'           => substr($daydata, 8, 2),
                    'pi_url'        => EVLIST_URL,
                    'urlfilt_cat'   => $cat,
                    'urlfilt_cal'   => $cal,
                ) );
                $M->parse('dBlock', 'day', true);
            }
            $M->parse('wBlock', 'week', true);
            $M->clear_var('dBlock');
        }
        $M->parse('onemonth', 'smallmonth');
        $T->set_var('month', $M->finish($M->get_var('onemonth')));

        if ($count % 4 == 0) {
            $T->set_var('end_row', 'true');
        } else {
            $T->clear_var('end_row');
        }

        $T->parse('mBlock', 'month', true);

    }

    $T->set_var(array(
        'pi_url'        => EVLIST_URL,
        'thisyear'      => $year,
        'prevyear'      => $year - 1,
        'nextyear'      => $year + 1,
        'cal_header'    => EVLIST_calHeader($year, $month, $day, 'year', 
                            $cat, $cal),
        'cal_footer'    => EVLIST_calFooter($calendars_used),
        'urlfilt_cat'   => $cat,
        'urlfilt_cal'   => $cal,
    ) );

    $T->parse('output', 'yearview');
    return $T->finish($T->get_var('output'));
}


/**
*   Create a list of events
*
*   @param  integer $range          Range indicator (upcoming, past, etc)
*   @param  integer $category       Category to limit search
*   @param  string  $block_title    Title of block
*   @return string      HTML for list page
*/
function EVLIST_listview($range = '', $category = '', $calendar = '', 
        $block_title='')
{
    global $_CONF, $_EV_CONF, $_USER, $_TABLES, $LANG_EVLIST;

    $retval = '';
    $T = new Template(EVLIST_PI_PATH . '/templates/');
    $T->set_file('index', 'index.thtml');

    if ($_EV_CONF['_can_add']) {
        $add_event_link = EVLIST_URL . '/event.php?edit=x';
    } else {
        $add_event_link = '';
    }

    $T->set_var(array(
        'action' => EVLIST_URL . '/index.php',
        'range_options' => EVLIST_GetOptions($LANG_EVLIST['ranges'], $range),
        'add_event_link' => $add_event_link,
        'add_event_text' => $LANG_EVLIST['add_event'],
        'rangetext'     =>  $LANG_EVLIST['ranges'][$range],
    ) );

    $page = empty($_GET['page']) ? 1 : (int)$_GET['page'];
    $opts = array('cat'=>$category, 
                'page'=>$page, 
                'limit'=>$_EV_CONF['limit_list'], 
                'cal'=>$calendar,
            );
    switch ($range) {
    case 1:         // past
        $start = EV_MIN_DATE;
        $end = $_EV_CONF['_today'];
        $opts['order'] = 'DESC';
        break;
    case 3:         //this week
        $start = $_EV_CONF['_today'];
        $end = date('Y-m-d', strtotime('+1 week', $_EV_CONF['_today_ts']));
        break;
    case 4:         //this month
        $start = $_EV_CONF['_today'];
        $end = date('Y-m-d', strtotime('+1 month', $_EV_CONF['_today_ts']));
        break;
    case 2:         //upcoming
    default:
        $start = $_EV_CONF['_today'];
        $end = EV_MAX_DATE;
        break;
    }

    $events = EVLIST_getEvents($start, $end, $opts);
    if (empty($events)) {
        //return empty list msg
        $T->set_var(array(
            'title' => '',
            'block_title' => $block_title,
            'empty_listmsg' => $LANG_EVLIST['no_match'],
        ) );

        if (!empty($range)) {
            $andrange = '&amp;range=' . $range;
            $T->set_var('range',$range);
        } else $andrange = '&amp;range=2';

        if (!empty($category)) {
            $andcat = '&amp;cat=' . $category;
            $T->set_var('category',$category);
        } else $andcat = '';

    } else {
        //populate list

        // So we don't call SEC_hasRights inside the loop
        $isAdmin = SEC_hasRights('evlist.admin');

        $T->set_file(array(
            'item' => 'list_item.thtml',
            'editlinks' => 'edit_links.thtml',
            'category_form' => 'category_dd.thtml'
        ));

        if (!empty($range)) {
            $andrange = '&amp;range=' . $range;
            $T->set_var('range',$range);
        } else $andrange = '&amp;range=2';

        if (!empty($category)) {
            $andcat = '&amp;cat=' . $category;
            $T->set_var('category',$category);
        } else $andcat = '';

        // Track events that have been shown so we show them only once.
        $already_shown = array();
        foreach ($events as $date => $daydata) {
            foreach ($daydata as $A) {
                if (array_key_exists($A['rp_id'], $already_shown)) {
                    continue;
                } else {
                    $already_shown[$A['rp_id']] = 1;
                }

                $titlelink = COM_buildURL(EVLIST_URL . '/event.php?eid=' .
                        $A['rp_id'] . $timestamp . $andrange . $andcat);
                $titlelink = '<a href="' . $titlelink . '">' .
                        COM_stripslashes($A['title']) . '</a>';
                $summary = PLG_replaceTags(COM_stripslashes($A['summary']));
                $datesummary = sprintf($LANG_EVLIST['event_begins'],
                        EVLIST_formattedDate(strtotime($A['rp_date_start'])));
                $morelink = COM_buildURL(EVLIST_URL . '/event.php?eid=' .
                        $A['rp_id'] . $timestamp . $andrange . $andcat);
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
    $retval .= EVLIST_pagenav($start, $end, $category, $page, $range, $calendar);

    return $retval;
}

?>
