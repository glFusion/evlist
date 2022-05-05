<?php
/**
 * Monthly View functions for the evList plugin.
 *
 * @author      Lee P. Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Views;
use Evlist\DateFunc;
use Evlist\Icon;
use Evlist\Models\EventSet;
use Evlist\Detail;


/**
 * Display a monthly calendar.
 * @package evlist
 */
class month extends \Evlist\View
{

    /**
     * Constructor to set up the monthly view.
     * Dates that have events scheduled are highlighted.
     *
     * @param   integer $year   Year to display, default is current year
     * @param   integer $month  Starting month
     * @param   integer $day    Starting day
     * @param   integer $cat    Event category
     * @param   integer $cal    Calendar ID
     * @param   string  $opts   Optional template modifier, e.g. "print"
     */
    public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        $this->type = 'month';
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }


    /**
     * Get the actual calendar view content.
     *
     * @return  string      HTML for calendar content
     */
    public function Content()
    {
        global $_CONF, $_EV_CONF, $LANG_MONTH, $_USER, $LANG_EVLIST;

        $retval = '';

        // Get all the dates in the month
        $calendarView = DateFunc::getCalendarMonth($this->month, $this->year);
        $x = count($calendarView) - 1;
        $y = count($calendarView[$x]) - 1;
        $starting_date = $calendarView[0][0];
        $ending_date = $calendarView[$x][$y];
        $daynames = self::DayNames();
        $events = EventSet::create()
            ->withStart($starting_date)
            ->withEnd($ending_date)
            ->withCategory($this->cat)
            ->withCalendar($this->cal)
            ->getEvents();

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
        $weekOfYear = DateFunc::weekOfYear($d, $m, $y);

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
                if ($daydata == $_CONF['_now']->format('Y-m-d', true)) {
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
                    $Det = Detail::getInstance($event['rp_det_id']);
                    if (empty($Det->getTitle())) {
                        continue;
                    }
                    if (!isset($event['allday'])) $event['allday'] = 0;
                    if (!isset($event['split'])) $event['split'] = 0;
                    $ev_hover = '';
                    $ev_title = COM_truncate($Det->getTitle(), 40, '...');

                    // Sanitize fields for display.  No HTML in the popup.
                    $title = htmlentities(strip_tags($Det->getTitle()));
                    $summary = htmlentities(strip_tags($Det->getSummary()));

                    // add the calendar to the array to create the JS checkboxes
                    $this->addCalUsed($event['cal_id']);

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
                        'ev_url'    => COM_buildUrl(EVLIST_URL . '/view.php?rid=' . $event['rp_id']),
                        'show'      => $this->getCalShowPref($event['cal_id']) ? 'block' : 'none',
                        'icon'      => Icon::getIcon($event['cal_icon']),
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

                if (EVLIST_canSubmit()) {
                    // Add the "Add Event" link for the day
                    $T->set_var('can_add', 'true');
                }
                $T->parse('dBlock', 'dayBlock', true);
            }
            $T->parse('wBlock', 'weekBlock', true);
            $T->clear_var('dBlock');
        }

        $this->prev_date = array(
            'year' => $prevyear,
            'month' => $prevmonth,
            'day' => 1,
        );
        $this->next_date = array(
            'year' => $nextyear,
            'month' => $nextmonth,
            'day' => 1,
        );
        $T->set_var(array(
            'pi_url'        => EVLIST_URL,
            'thisyear'      => $this->year,
            'thismonth'     => $this->month,
            'thisday'       => $this->day,
            //'thismonth_str' => $LANG_MONTH[(int)$this->month],
            /*'prevmonth'     => $prevmonth,
            'prevyear'      => $prevyear,
            'nextmonth'     => $nextmonth,
            'nextyear'      => $nextyear,*/
            'urlfilt_cat'   => $this->cat,
            'urlfilt_cal'   => $this->cal,
            //'cal_header'    => $this->Header(),
            'cal_footer'    => $this->Footer(),
            //'cal_checkboxes' => $this->getCalCheckboxes(),
            'site_name'     => $_CONF['site_name'],
            'site_slogan'   => $_CONF['site_slogan'],
            'today_str'     => $this->getDisplayDate(),
        ) );

        $T->parse('output', 'monthview');
        return $T->finish($T->get_var('output'));
    }

}
