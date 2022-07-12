<?php
/**
 * Weekly View functions for the evList plugin.
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
use Evlist\Calendar;
use Evlist\Detail;


/**
 * Create a weekly view calendar.
 * @package evlist
 */
class week extends \Evlist\View
{
    /**
     * Construct the weekly view.
     *
     * @param   integer $year   Year to display, default is current year
     * @param   integer $month  Starting month
     * @param   integer $day    Starting day
     * @param   integer $cat    Event category
     * @param   integer $cal    Calendar to show
     * @param   string  $opts   Optional template modifier, e.g. "print"
     */
    public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        $this->type = 'week';
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }


    /**
     * Get the actual calendar view content.
     *
     * @return  string      HTML for calendar content
     */
    public function Content()
    {
        global $_CONF, $_EV_CONF, $LANG_MONTH, $LANG_EVLIST, $_USER;

        $retval = '';

        // Get the events
        $calendarView = DateFunc::getCalendarWeek($this->day, $this->month, $this->year);
        $start_date = $calendarView[0];
        $end_date = $calendarView[6];

        $dtStart = new \Date(strtotime($start_date));
        $dtToday = $dtStart;    // used to update date strings each day
        $week_secs = 86400 * 7;
        $dtPrev = new \Date($dtStart->toUnix() - $week_secs);
        $dtNext = new \Date($dtStart->toUnix() + $week_secs);

        // Set up next and previous week links
        list($sYear, $sMonth, $sDay) = explode('-', $start_date);

        $T = new \Template(EVLIST_PI_PATH . '/templates/weekview');
        $tpl = $this->getTemplate();
        $T->set_file(array(
            'week'      => $tpl . '.thtml',
            'event'     => 'event.thtml',
        ) );

        $daynames = self::DayNames();
        $events = EventSet::create()
            ->withStart($start_date)
            ->withEnd($end_date)
            ->withCategory($this->cat)
            ->withCalendar($this->cal)
            ->getEvents();

        $start_mname = $LANG_MONTH[(int)$sMonth+12];
        $last_date = getdate($dtStart->toUnix() + (86400 * 6));
        $end_mname = $LANG_MONTH[$last_date['mon']+12];
        $end_ynum = $last_date['year'];
        $end_dnum = sprintf('%02d', $last_date['mday']);
        $date_range = $start_mname . ' ' . $sDay;
        if ($this->year <> $end_ynum) {
            $date_range .= ', ' . $this->year . ' - ';
        } else {
            $date_range .= ' - ';
        }
        if ($start_mname <> $end_mname) {
            $date_range .= $end_mname . ' ';
        }
        $date_range .= "$end_dnum, $end_ynum";
        $this->today_str = $date_range;
        //$T->set_var('date_range', $date_range);

        $T->set_block('week', 'dayBlock', 'dBlk');
        foreach($calendarView as $idx=>$weekData) {
            list($curyear, $curmonth, $curday) = explode('-', $weekData);
            $dtToday->setDateTimestamp($curyear, $curmonth, $curday, 1, 0, 0);
            $T->clear_var('eBlk');
            if ($weekData == $_CONF['_now']->format('Y-m-d', true)) {
                $T->set_var('dayclass', 'weekview-curday');
            } else {
                $T->set_var('dayclass', 'weekview-offday');
            }

            $monthname = $LANG_MONTH[(int)$curmonth];
            $T->set_var('dayinfo', $daynames[$idx] . ', ' .
                COM_createLink($dtToday->format($_CONF['shortdate']),
                    EVLIST_URL . "/index.php?view=day&amp;day=$curday" .
                    "&amp;cat={$this->cat}&amp;cal={$this->cal}" .
                    "&amp;month=$curmonth&amp;year=$curyear")
            );

            if (EVLIST_canSubmit()) {
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
                $tz = $A['tzid'] == 'local' ? $_USER['tzid'] : $A['tzid'];
                if (isset($A['allday']) && $A['allday'] == 1 ||
                        ($A['rp_date_start'] < $weekData &&
                        $A['rp_date_end'] > $weekData)) {
                    $event_time = $LANG_EVLIST['allday'];
                } else {
                    if ($A['rp_date_start'] == $weekData) {
                        $s_dt = new \Date($weekData  . ' ' . $A['rp_time_start1'], $tz);
                        $starttime = $s_dt->format($_CONF['timeonly'],true);
                    } else {
                        $starttime = ' ... ';
                    }

                    if ($A['rp_date_end'] == $weekData) {
                        $e_dt = new \Date($weekData . ' ' . $A['rp_time_end1'], $tz);
                        $endtime = $e_dt->format($_CONF['timeonly'], true);
                    } else {
                        $endtime = ' ... ';
                    }
                    $event_time = $starttime . ' - ' . $endtime;
                    if ($A['tzid'] != 'local') $event_time .= ' ( ' . $s_dt->format('T',true) . ')';

                    if (isset($A['split']) && $A['split'] == 1 && !empty($A['rp_time_start2'])) {
                        $s_dt2 = new \Date($weekData . ' ' . $A['rp_time_start2'], $tz);
                        $e_dt2 = new \Date($weekData . ' ' . $A['rp_time_end2'], $tz);
                        $starttime2 = $s_dt2->format($_CONF['timeonly'], true);
                        $endtime2 = $e_dt2->format($_CONF['timeonly'], true);
                        $event_time .= ' & ' . $starttime2 . ' - ' . $endtime2;
                    }
                }
                $this->addCalUsed($A['cal_id']);
                $Det = Detail::getInstance($A['rp_det_id']);

                $T->set_var(array(
                    'event_times'   => $event_time,
                    'event_title'   => strip_tags($Det->getTitle()),
                    'event_summary' => strip_tags($Det->getSummary()),
                    'event_id'      => $A['rp_id'],
                    'cal_id'        => $A['cal_id'],
                    'pi_url'        => EVLIST_URL,
                    'fgcolor'       => $A['fgcolor'],
                    'show'      => $this->getCalShowPref($A['cal_id']) ? 'block' : 'none',
                    'icon'      => Icon::custom($A['cal_icon']),
                    'ev_url'    => COM_buildUrl(EVLIST_URL . '/view.php?rid=' . $A['rp_id']),
                ) );
                $T->parse('event', 'event', false);
                $T->parse('eBlk', 'eventBlock', true);
            }
            $T->parse('dBlk', 'dayBlock', true);
        }

        $this->prev_date = array(
            'month'     => $dtPrev->format('n', false),
            'day'       => $dtPrev->format('j', false),
            'year'      => $dtPrev->format('Y', false),
        );
        $this->next_date = array(
            'month'     => $dtNext->format('n', false),
            'day'       => $dtNext->format('j', false),
            'year'      => $dtNext->format('Y', false),
        );
        $T->set_var(array(
            'pi_url'        => EVLIST_URL,
            'cal_header'    => $this->Header(),
            'cal_footer'    => $this->Footer(),
            'urlfilt_cat'   => $this->cat,
            'urlfilt_cal'   => $this->cal,
            'cal_checkboxes' => $this->getCalCheckboxes(),
            'site_name'     => $_CONF['site_name'],
            'site_slogan'   => $_CONF['site_slogan'],
            'year'          => $this->year,
            'month'         => $this->month,
            'day'           => $this->day,
            'today_str'     => $this->getDisplayDate(),
        ) );
        $T->parse('output','week');
        return $T->finish($T->get_var('output'));
    }
}
