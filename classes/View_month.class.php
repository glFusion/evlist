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
use LGLib\Date_Calc;

/**
*   Display a monthly calendar
*   @class View_month
*/
class View_month extends View
{

    /**
    *   Constructor to set up the monthly view
    *   Dates that have events scheduled are highlighted.
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
        $this->type = 'month';
        parent::__construct($year, $month, 0, $cat, $cal, $opts);
    }


    /**
    *   Get the actual calendar view content
    *
    *   @return string      HTML for calendar content
    */
    public function Content()
    {
        global $_CONF, $_EV_CONF, $LANG_MONTH, $_USER, $LANG_EVLIST;

        $retval = '';

        // Get all the dates in the month
        $calendarView = Date_Calc::getCalendarMonth($this->month, $this->year, '%Y-%m-%d');

        $x = count($calendarView) - 1;
        $y = count($calendarView[$x]) - 1;
        $starting_date = $calendarView[0][0];
        $ending_date = $calendarView[$x][$y];
        $daynames = self::DayNames();
        $events = EVLIST_getEvents($starting_date, $ending_date,
                array('cat'=>$this->cat, 'cal'=>$this->cal));

        $nextmonth = $this->month + 1;
        $nextyear = $this->year;
        if ($nextmonth > 12) {
            $nextmonth = 1;
            $nextyear++;
        }
        $prevmonth = $this->month - 1;
        $prevyear = $this->year;
        if ($prevmonth < 1) {
            $prevmonth = 12;
            $prevyear--;
        }

        $T = new \Template(EVLIST_PI_PATH . '/templates/monthview');
        $tpl = $this->getTemplate();
        $T->set_file(array(
            'monthview'  => $tpl . '.thtml',
            'allday_event' => 'event_allday.thtml',
            'timed_event' => 'event_timed.thtml',
        ) );

        foreach ($daynames as $key=>$dayname) {
            $T->set_var('dayname'.$key, $dayname);
        }

        list($y, $m, $d) = explode('-', $starting_date);
        $weekOfYear = Date_Calc::weekOfYear($d, $m, $y);

        $T->set_block('monthview', 'weekBlock', 'wBlock');
        foreach ($calendarView as $weeknum => $weekdata) {
            list($weekYear, $weekMonth, $weekDay) = explode('-', $weekdata[0]);
            $T->set_var(array(
                'wyear'  => $weekYear,
                'wmonth' => $weekMonth,
                'wday'   => $weekDay,
                'urlfilt_cat' => $this->cat,
                'urlfilt_cal' => $this->cal,
                'weeknum' => $weekOfYear,
                $tpl => 'true',
            ) );
            $weekOfYear++;

            foreach ($weekdata as $daynum => $daydata) {
                list($y, $m, $d) = explode('-', $daydata);
                if ($daydata == $_EV_CONF['_today']) {
                    $dayclass = 'today';
                } elseif ($m == $this->month) {
                    $dayclass = 'on';
                } else {
                    $dayclass = 'other-month';
                }

                $T->set_var('cal_day_anchortags',
                    COM_createLink(sprintf('%02d', $d),
                        EVLIST_URL . '/index.php?view=day&amp;' .
                        "cat={$this->cat}&amp;cal={$this->cal}" .
                        "&amp;day=$d&amp;month=$m&amp;year=$y",
                        array('class'=>'cal-date'))
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
                    if (!isset($event['allday'])) $event['allday'] = 0;
                    if (!isset($event['split'])) $event['split'] = 0;
                    $ev_hover = '';
                    $ev_title = COM_truncate($event['title'], 40, '...');

                    // Sanitize fields for display.  No HTML in the popup.
                    $title = htmlentities(strip_tags($event['title']));
                    $summary = htmlentities(strip_tags($event['summary']));

                    // add the calendar to the array to create the JS checkboxes
                    $this->addCalUsed($event);

                    // Create the hover tooltip.  Timed events show the times first
                    if ($event['allday'] == 0) {
                        $tz = $event['tzid'] == 'local' ? $_USER['tzid'] : $event['tzid'];
                        $s_dt = new \Date($event['rp_date_start'] . ' ' . $event['rp_time_start1'], $tz);
                        $ev_hover = $s_dt->format($_CONF['timeonly'], true);
                        if ($event['split'] == 1 && !empty($event['rp_time_start2']) ) {
                            $e_dt = new \Date($event['rp_date_start'] . ' ' . $event['rp_time_start2'], $tz);
                            $ev_hover .= ' &amp; ' . $e_dt->format($_CONF['timeonly'], false);
                        }
                        if ($event['tzid'] != 'local') $ev_hover .= ' ' . $s_dt->format('T', true);
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
                        'cal_id_url' => $event['cal_id'],    // calendar requested
                        'cat_id'    => $this->cat,
                        'ev_hover'  => $ev_hover,
                        'ev_title'  => $ev_title,
                        'eid'       => $event['rp_id'],
                        'fgcolor'   => $event['fgcolor'],
                        'bgcolor'   => $event['bgcolor'],
                        'pi_url'    => EVLIST_URL,
                        'show'      => $this->getCalShowPref($event['cal_id']) ? 'block' : 'none',
                        'icon'      => EVLIST_getIcon($event['cal_icon']),
                    ) );
                    switch ($event['cal_id']) {
                    case -1:
                        $T->set_var('ev_url', $event['url']);
                        $dayentries .= $T->parse('output', 'timed_event', true);
                        break;
                    default:
                        $T->clear_var('ev_url');
                        if ($event['allday'] == 1) {
                            $dayentries .= $T->parse('output', 'allday_event', true);
                        } else {
                            $dayentries .= $T->parse('output', 'timed_event', true);
                        }
                        break;
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

                if (EVLIST_canSubmit()) {
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
            'thisyear'      => $this->year,
            'thismonth'     => $this->month,
            'thismonth_str' => $LANG_MONTH[(int)$this->month],
            'prevmonth'     => $prevmonth,
            'prevyear'      => $prevyear,
            'nextmonth'     => $nextmonth,
            'nextyear'      => $nextyear,
            'urlfilt_cat'   => $this->cat,
            'urlfilt_cal'   => $this->cal,
            'cal_header'    => $this->Header(),
            'cal_footer'    => $this->Footer(),
            'cal_checkboxes' => $this->getCalCheckboxes(),
            'site_name'     => $_CONF['site_name'],
            'site_slogan'   => $_CONF['site_slogan'],
            'is_uikit'      => $_EV_CONF['_is_uikit'] ? 'true' : '',
            'iconset'       => $_EV_CONF['_iconset'],
        ) );

        $T->parse('output', 'monthview');
        return $T->finish($T->get_var('output'));
    }

}

?>
