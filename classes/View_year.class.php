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
*   Display a yearly calendar.
*   @class  View_year
*/
class View_year extends View
{
    /*
    *   Construct the yearly view
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
        $this->type = 'year';
        parent::__construct($year, 0, 0, $cat, $cal, $opts);
    }


    /**
    *   Get the actual calendar view content
    *
    *   @return string      HTML for calendar content
    */
    public function Content()
    {
        global $_CONF, $_EV_CONF, $LANG_MONTH, $_USER;

        $retval = '';

        // Get all the dates in the year
        $starting_date = sprintf('%d-01-01', $this->year);
        $ending_date = sprintf('%d-12-31', $this->year);
        $calendarView = \Date_Calc::getCalendarYear($this->year, '%Y-%m-%d');
        $daynames = self::DayNames(1);
        $events = EVLIST_getEvents($starting_date, $ending_date,
            array('cat'=>$this->cat, 'cal'=>$this->cal));

        $T = new \Template(EVLIST_PI_PATH . '/templates/yearview');
        $tpl = $this->getTemplate();
        $T->set_file(array(
            'yearview'  => $tpl . '.thtml',
        ) );

        $count = 0;
        $T->set_block('yearview', 'month', 'mBlock');
        foreach ($calendarView as $monthnum => $monthdata) {
            $monthnum_str = sprintf('%02d', $monthnum+1);

            $count++;
            if (($count-1) % 4 == 0) {
                $T->set_var('st_row', 'true');
            } else {
                $T->clear_var('st_row');
            }

            $M = new \Template($_CONF['path']
                        . 'plugins/evlist/templates/yearview');
            $M->set_file(array(
                'smallmonth'  => 'smallmonth.thtml',
            ) );

            $M->set_var('thisyear', $this->year);
            $M->set_var('month', $monthnum+1);
            $M->set_var('monthname', $LANG_MONTH[$monthnum+1]);

            $M->set_block('smallmonth', 'daynames', 'nBlock');
            for ($i = 0; $i < 7; $i++) {
                $M->set_var('dayname', $daynames[$i]);
                $M->parse('nBlock', 'daynames', true);
            }

            $M->set_block('smallmonth', 'week', 'wBlock');
            foreach ($monthdata as $weeknum => $weekdata) {
                list($weekYear, $weekMonth, $weekDay) = explode('-', $weekdata[0]);
                $M->set_var(array(
                    'weekyear'  => $weekYear,
                    'weekmonth' => $weekMonth,
                    'weekday'   => $weekDay,
                    'urlfilt_cat' => $this->cat,
                    'urlfilt_cal' => $this->cal,
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
                            $tz = $event['tzid'] == 'local' ? $_USER['tzid'] : $event['tzid'];
                            // Separate events by a newline if more than one
                            if (!empty($popup)) {
                                $popup .= self::tooltip_newline();
                            }
                            // Don't show a time for all-day events
                            if ($event['allday'] == 0) {
                                $dt = new \Date($event['rp_date_start'] . ' ' . $event['rp_time_start1'], $tz);
                                $popup .= $dt->format($_CONF['timeonly'], true);
                                if ($event['tzid'] != 'local') $popup .= ' (' . $dt->format('T') . ')';
                                $popup .=  ': ';
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
                        'urlfilt_cat'   => $this->cat,
                        'urlfilt_cal'   => $this->cal,
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
            'thisyear'      => $this->year,
            'prevyear'      => $this->year - 1,
            'nextyear'      => $this->year + 1,
            'cal_header'    => $this->Header(),
            'cal_footer'    => $this->Footer(),
            'urlfilt_cat'   => $this->cat,
            'urlfilt_cal'   => $this->cal,
        ) );

        $T->parse('output', 'yearview');
        return $T->finish($T->get_var('output'));
    }
}

?>
