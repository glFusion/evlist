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

USES_evlist_functions();
USES_lglib_class_datecalc();
USES_class_date();
date_default_timezone_set('UTC');

class evView
{
    protected $year;        // Year of display
    protected $month;       // Month of display
    protected $day;         // Day of display
    protected $cat;         // Category to display
    protected $cal;         // Calendar to display
    protected $cal_used;    // Array of calendars used in display
    protected $range;       // Range selector (past, future, etc)
    protected $today;       // Holder for today's date
    protected $today_sql;   // Today's date in YYYY-MM-DD format. Used often.
    protected $type;        // View type (month, year, etc)
    protected $tpl_opt;     // 'print' to create a printable view
    protected $inc_dt_sel= true;   // true to include date/range opts

    /**
    *   Get a calendar view object for the specifiec type.
    *
    *   @param  string  $type   Type of view (month, day, year, etc.)
    *   @param  integer $year   Year for the view
    *   @param  integer $month  Month for the view
    *   @param  integer $day    Day for the view
    *   @param  integer $cat    Category to view
    *   @param  integer $cal    Calendar to view
    *   @param  mixed   $opt    Additional view options
    *   @return object          View object
    */
    public static function GetView($type='', $year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        global $_EV_CONF;

        if (!is_array($opts)) $opts = array();

        // Get the current view from the session variable, if defined, to
        // use as overrides for default parameters
        $current_view = SESS_getVar('evlist.current');
        if ($current_view !== 0) {
            if ($type == '') $type = $current_view['view'];
            if ($year == 0) $year = $current_view['date'][0];
            if ($month == 0) $month = $current_view['date'][1];
            if ($day == 0) $day = $current_view['date'][2];
        } else {
            // no previous session created, default to the current date
            // unless other values provided
            list($cyear, $cmonth, $cday) = explode('-', $_EV_CONF['_today']);
            if ($year == 0) $year = $cyear;
            if ($month == 0) $month = $cmonth;
            if ($day == 0) $day = $cday;
        }

        // catch missing or incorrect view types, set to default or 'month'
        if (!in_array($type, array('day','week','month','year','list'))) {
            $type = isset($_EV_CONF['default_view']) ?
                $_EV_CONF['default_view'] : 'month';
        }
        $class = "evView_{$type}";
        if (class_exists($class)) {
            $view = new $class($year, $month, $day, $cat, $cal, $opts);
            return $view->Render();
        } else {
            // last-ditch error if $type isn't valid
            return '<span class="alert">Failure loading calendar</span>';
        }
    }


    /**
    *   Set common values for all views
    *
    *   @param  integer $year   Year for the view
    *   @param  integer $month  Month for the view
    *   @param  integer $day    Day for the view
    *   @param  integer $cat    Category to view
    *   @param  integer $cal    Calendar to view
    *   @param  mixed   $opts   Additional view options
    */
    public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        global $_EV_CONF;

        $this->today = new Date($_EV_CONF['_today_ts']);
        $this->today_sql = $this->today->format('Y-m-d');
        $this->year = (int)$year;
        $this->month = (int)$month;
        $this->day = (int)$day;
        $this->cat = (int)$cat;
        $this->cal = (int)$cal;
        $this->opt = is_array($opts) ? $opts : array();
        $this->setSession();
    }


    /**
    *   Set the 'print' value to be used in template selection
    *
    *   @param  boolean $on     True to use print template
    */
    public function setPrint($on = true)
    {
        $this->tpl_opt = $on ? 'print' : '';
    }


    /**
    *   Gets the name of the template file to use.
    *   Creates a view based on $this->type . 'view', e.g. 'monthview'
    *   unless overridden.
    *
    *   @param  string  $view   Optional override view
    *   @return string          Template filename
    */
    protected function getTemplate($view = '')
    {
        global $_EV_CONF;

        $tpl = $view == '' ? $this->type . 'view' : $view;
        if ($this->opt == 'print') {
            $tpl .= '_print';
        } else {
            $tpl .= '_json';
        }
        return $tpl;
    }


    /**
    *   Display the common header for all calendar views.
    *
    *   @return string          HTML for calendar header
    */
    public function Header()
    {
        global $_CONF, $_EV_CONF, $LANG_EVLIST, $LANG_MONTH, $_TABLES;

        $retval = '';
        $thisyear = $this->today->format('Y');
        $thismonth = $this->today->format('m');
        $thisday = $this->today->format('d');

        $T = new Template(EVLIST_PI_PATH . '/templates');
        $T->set_file('header', 'calendar_header.thtml');

        $type_options = COM_optionList($_TABLES['evlist_categories'],
            'id,name', $this->cat, 1, 'status=1');
        $range_options = EVLIST_GetOptions($LANG_EVLIST['ranges'], $this->range);

        // Figure out the add event link, depending on the view.
        if (EVLIST_canSubmit()) {
            $add_event_link = EVLIST_URL . '/event.php';
            switch ($this->type) {
            case 'day':         // Add the current day
                $T->set_var('addlink_day', $this->day);
            case 'week':
            case 'month':
                $T->set_var('addlink_month', $this->month);
            case 'year':
                $T->set_var('addlink_year', $this->year);
            }
        } else {
            $add_event_link = '';
        }

        $T->set_var(array(
            'pi_url'    => EVLIST_URL,
            'year'      => $this->year,
            'month'     => $this->month,
            'day'       => $this->day,
            'thisyear'  => $thisyear,
            'thismonth' => $thismonth,
            'thisday'   => $thisday,
            'thisview'  => $this->type,
            'add_event_link' => $add_event_link,
            'add_event_text' => $LANG_EVLIST['add_event'],
            'event_type_select' => $type_options,
            'range_options' => $range_options,
            'action_url'    => EVLIST_URL . '/index.php',
            'iso_lang'  => EVLIST_getIsoLang(),
            'view'      => $this->view,
            'curdate'   => sprintf("%d-%02d-%02d", $year, $month, $day),
            'urlfilt_cal' => (int)$this->cal,
            'urlfilt_cat' => (int)$this->cat,
            'use_json' => 'true',
            'is_uikit' => $_EV_CONF['_is_uikit'] ? 'true' : '',
            'view'  => $this->type,
        ) );

        $cal_selected = isset($_GET['cal']) ? (int)$_GET['cal'] : 0;
        $T->set_var('cal_select', COM_optionList($_TABLES['evlist_calendars'],
                    'cal_id,cal_name', $cal_selected, 1,
                    '1=1 '. COM_getPermSQL('AND'))
        );

        if (isset($_GET['range']) && !empty($_GET['range'])) {
            $T->set_var('range_url', 'range=' . $_GET['range']);
        }

        if ($this->inc_dt_sel) {
            $T->set_var('include_selections', 'true');

            // Create the jump-to-date selectors
            $options = '';
            for ($i = 1; $i < 32; $i++) {
                $sel = $i == $this->day ? EVSELECTED : '';
                $options .= "<option value=\"$i\" $sel>$i</option>" . LB;
            }
            $T->set_var('day_select', $options);

            $options = '';
            for ($i = 1; $i < 13; $i++) {
                $sel = $i == $this->month ? EVSELECTED : '';
                $options .= "<option value=\"$i\" $sel>{$LANG_MONTH[$i]}</option>" .
                    LB;
            }
            $T->set_var('month_select', $options);

            $options = '';
            for ($i = $thisyear - 2; $i < $thisyear + 6; $i++) {
                $sel = $i == $this->year ? EVSELECTED : '';
                $options .= "<option value=\"$i\" $sel>$i</option>" . LB;
            }
            $T->set_var('year_select', $options);
        }

        $images = array('day', 'week', 'month', 'year', 'list');
        $options = '';
        foreach ($images as $v) {
            if ($v == $this->type) {
                $sel = EVSELECTED;
                $T->set_var($v .'_img', $v . '_on.png');
            } else {
                $sel = '';
                $T->set_var($v .'_img', $v . '_off.png');
            }
            // Add views other than "list" to the jump dropdown
            if ($v != 'list') {
                $options .= '<option value="' . $v . '" ' . $sel . ' >' .
                        $LANG_EVLIST['periods'][$v] . '</option>' . LB;
            }
        }

        $T->set_var('view_select', $options);
        $T->parse('output', 'header');
        $retval .= $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
    *   Display the calendar footer
    *
    *   @return string  HTML for calendar footer
    */
    protected function Footer()
    {
        global $LANG_EVLIST, $_EV_CONF;

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
        $webcal_url = preg_replace('/^https?/', 'webcal', EVLIST_URL);
        if (is_array($this->cal_used)) {
            foreach ($this->cal_used as $cal) {
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
            'is_uikit' => $_EV_CONF['_is_uikit'] ? 'true' : '',
        ) );

        $T->parse('output', 'calendar_footer');
        return $T->finish($T->get_var('output'));
    }


    /**
    *   Set the view information into a session variable.
    *   Used to keep track of the last calendar viewed by a visitor so they
    *   can be returned to the same view after viewing an event detail or
    *   when returning to the site.
    *
    *   @uses   SESS_setVar()
    */
    protected function setSession()
    {
        $A = SESS_getVar('evlist.current');
        if (is_array($A['date'])) {
            if ($this->year == 0) $this->year = $A['date'][0];
            if ($this->month == 0) $this->month = $A['date'][1];
            if ($this->day == 0) $this->day = $A['date'][2];
        } else {
            if ($this->year == 0) $this->year = Date_Calc::getYear();
            if ($this->month == 0) $this->month = Date_Calc::getMonth();
            if ($this->day == 0) $this->day = Date_Calc::getDay();
        }
        SESS_setVar('evlist.current', array(
            'view' => $this->type,
            'date' => array($this->year, $this->month, $this->day),
        ) );
    }


    /**
    *   Get the day names for a week based on week start day of Sun or Mon.
    *   Used to create calendar headers for weekly, monthly and yearly views.
    *
    *   @param  integer $letters    Optional number of letters to return
    *   @return array       Array of day names for a week, 0-indexed
    */
    public static function DayNames($letters = 0)
    {
        global $_CONF, $LANG_WEEK;

        $retval = array();

        if ($_CONF['week_start'] == 'Sun') {
            $keys = array(1, 2, 3, 4, 5, 6, 7);
        } else {
            $keys = array(2, 3, 4, 5, 6, 7, 1);
        }

        if ($letters > 0) {
            for ($i = 0; $i < 7; $i++) {
                $retval[$i] = substr($LANG_WEEK[$keys[$i]], 0, $letters);
            }
        } else {
            for ($i = 0; $i < 7; $i++) {
                $retval[$i] = $LANG_WEEK[$keys[$i]];
            }
        }
        return $retval;
    }


    public function Content() {}

    public function Render()
    {
        return $this->viewJSON();
    }


    /**
    *   Prepare variables for view functions using JSON templates
    *   Reads values from the session, if available. If no session present,
    *   get values from parameters or current date
    *
    *   @param  string  $type   Type of view. 'month', 'day', 'list', 'year'
    *   @param  integer $year   Year override
    *   @param  integer $month  Month override
    *   @param  integer $day    Day override
    *   @param  integer $cat    Category pass-through
    *   @param  mixed   $opt    Calendar options pass=through
    *   @return string      Complete HTML for requested calendar
    */
    protected function viewJSON()
    {
        global $_EV_CONF;

        $T = new Template(EVLIST_PI_PATH . '/templates/');
        //$T = new Template(EVLIST_PI_PATH . '/templates/' . $type . 'view');
        $T->set_file(array(
            'view'  => 'json_cal_wrapper.thtml',
        ) );
        /*$function = "EVLIST_{$type}view";
        if (!function_exists($function)) {
            return '<span class="alert">Failure loading calendar</span>';
        }*/

        $T->set_var(array(
            'cal_header'    => $this->Header(),
            'calendar_content' => $this->Content(),
            'urlfilt_cal' => (int)$this->cal,
            'urlfilt_cat' => (int)$this->cat,
        ) );
        $T->parse('output', 'view');
        return $T->finish($T->get_var('output'));
    }


    /**
    *   Create the calendar selection checkboxes to be shown in the javascript
    *   dropdown.
    *
    *   @return string      Input elements for each available calendar
    */
    protected function getCalCheckboxes()
    {
        $boxes = '';
        if (!is_array($this->cal_used) || empty($this->cal_used))
            return $boxes;

        $T = new Template(EVLIST_PI_PATH . '/templates/');
        $T->set_file('boxes', 'cal_checkboxes.thtml');
        $T->set_block('boxes', 'cal_item', 'item');
        asort($this->cal_used);
        foreach ($this->cal_used as $key=>$cal) {
            $T->set_var(array(
                'fgcolor'   => $cal['fgcolor'],
                'key'       => $key,
                'cal_name'  => $cal['cal_name'],
            ) );
            $T->parse('item', 'cal_item', true);
        }
        return $T->parse('output', 'boxes');
    }

}


/**
*   Display a single-day calendar view.
*   @class evView_day
*/
class evView_day extends evView
{
    /*
    *   Construct the daily view class
    *
    *   @param  integer $year   Year to display, default is current year
    *   @param  integer $month  Starting month
    *   @param  integer $day    Starting day
    *   @param  integer $cat    Category to show
    *   @param  integer $cal    Calendar to show
    *   @param  string  $opt    Optional template modifier, e.g. "print"
    */
     public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        $this->type = 'day';
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }


    /**
    *   Get the actual calendar view content
    *
    *   @return string      HTML for calendar content
    */
    public function Content()
    {
        global $_CONF, $_EV_CONF, $LANG_EVLIST, $LANG_MONTH, $LANG_WEEK;

        $retval = '';
        $today_sql = sprintf('%d-%02d-%02d', $this->year, $this->month, $this->day);
        $today = new Date($today_sql);
        $dtPrev = new Date($today->toUnix() - 86400);
        $dtNext = new Date($today->toUnix() + 86400);
        $monthname = $LANG_MONTH[$today->month];
        $dayofweek = $today->dayofweek;
        if ($dayofweek == 7) $dayofweek = 0;
        $dayname = $LANG_WEEK[$dayofweek + 1];

        $tpl = $this->getTemplate();
        $T = new Template(EVLIST_PI_PATH . '/templates/dayview');
        $T->set_file(array(
            'column'    => 'column.thtml',
            'event'     => 'singleevent.thtml',
            'dayview'   => $tpl . '.thtml',
        ) );

        $events = EVLIST_getEvents($today_sql, $today_sql,
                array('cat'=>$this->cat, 'cal'=>$this->cal));
        list($allday, $hourly) = $this->getViewData($events);

        // Get allday events
        $alldaycount = count($allday);
        if ($alldaycount > 0) {
            for ($i = 1; $i <= $alldaycount; $i++) {
                $A = current($allday);
                if (isset($A['cal_id'])) {
                    $this->cal_used[$A['cal_id']] = array(
                        'cal_name' => $A['cal_name'],
                        'cal_ena_ical' => $A['cal_ena_ical'],
                        'cal_id' => $A['cal_id'],
                        'fgcolor' => $A['fgcolor'],
                        'bgcolor' => $A['bgcolor'],
                    );
                }

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
                    $T->set_var('br', '<br />');
                } else {
                    $T->set_var('br', '');
                }
                $T->parse('allday_events', 'event', true);
                next($allday);
            }
        } else {
            $T->set_var('allday_events', '&nbsp;');
        }

        for ($i = 0; $i < 24; $i++) {
            $link = date($_CONF['timeonly'], mktime($i, 0));
            //if ($_EV_CONF['_can_add']) {
            if (EVLIST_canSubmit()) {
                $link = '<a href="' . EVLIST_URL . '/event.php?edit=x&amp;month=' .
                        $month . '&amp;day=' . $day . '&amp;year=' . $year .
                        '&amp;hour=' . $i . '">' . $link . '</a>';
            }
            $T->set_var ($i . '_hour',$link);
        }

        // Get hourly events
        /*$times = array();
        foreach ($hourly as $event) {
            if (!isset($times[$event['starthour']]))
                $times[$event['starthour']] = array();
            $times[$event['starthour']][] = $event;
        }*/
        for ($i = 0; $i <= 23; $i++) {

            $hourevents = $hourly[$i];
            $numevents = count($hourevents);

            $T->clear_var('event_entry');
            for ($j = 1; $j <= $numevents; $j++) {
                $A = current($hourevents);

                if (isset($A['data']['cal_id'])) {
                    $this->cal_used[$A['data']['cal_id']] = array(
                        'cal_name' => $A['data']['cal_name'],
                        'cal_ena_ical' => $A['data']['cal_ena_ical'],
                        'cal_id' => $A['data']['cal_id'],
                        'fgcolor' => $A['data']['fgcolor'],
                        'bgcolor' => $A['data']['bgcolor'],
                    );
                }

                if ($A['data']['rp_date_start'] == $today) {
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

                if ($A['data']['rp_date_end'] == $today) {
                    $end_time = date($_CONF['timeonly'],
                        strtotime($A['data']['rp_date_end'] . ' ' .
                            $A['time_end']));
                } else {
                    $end_time = date(
                        $_CONF['shortdate'].' @ ' . $_CONF['timeonly'],
                        strtotime($A['data']['rp_date_end'] . ' ' .
                            $A['time_end']));
                }

                if ($start_time == ' ... ' && $end_time == ' ... ') {
                    $T->set_var('event_time', $LANG_EVLIST['allday']);
                } else {
                    $T->set_var('event_time',
                        $start_time . ' - ' . $end_time);
                }

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
                if ($A['data']['cal_id'] < 0) {
                    $T->set_var(array(
                        'is_meetup' => 'true',
                        'ev_url' => $A['data']['url'],
                    ) );
                } else {
                    $T->clear_var('is_meetup');
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
                        $month . '&amp;day=' . $day . '&amp;year=' . $year .
                        '&amp;hour=' . $i . '">' . $link . '</a>';
            }
            $T->parse ($i . '_cols', 'column', true);
        }
        $T->set_var(array(
            'month'         => $month,
            'day'           => $day,
            'year'          => $year,
            'prevmonth'     => $dtPrev->format('n', false),
            'prevday'       => $dtPrev->format('j', false),
            'prevyear'      => $dtPrev->format('Y', false),
            'nextmonth'     => $dtNext->format('n', false),
            'nextday'       => $dtNext->format('j', false),
            'nextyear'      => $dtNext->format('Y', false),
            'urlfilt_cal'   => $cal,
            'urlfilt_cat'   => $cat,
            'cal_header'    => $this->Header(),
            'cal_footer'    => $this->Footer(),
            'pi_url'        => EVLIST_URL,
            'currentday'    => $dayname. ', ' . $today->format($_CONF['shortdate']),
            'week_num'      => $today->format('W'),
            'cal_checkboxes'=> $this->getCalCheckboxes(),
            'site_name'     => $_CONF['site_name'],
            'site_slogan'   => $_CONF['site_slogan'],
            'is_uikit'      => $_EV_CONF['_is_uikit'] ? 'true' : '',
        ) );
        return $T->parse('output', 'dayview');
    }


    /**
    *   Organizes events by hour, and separates all-day events.
    *
    *   @param  array   $events     Array of all events
    *   @param  string  $today      Current date, YYYY-MM-DD.  Optional.
    *   @return array               Array of 2 arrays, allday and hourly
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

        USES_class_date();

        // Events are keyed by hour, so read through each hour
        foreach ($events as $date=>$E) {
            // Now read each event contained in each hour
            foreach ($E as $id=>$A) {
                // remove serialized data, not needed for display and interferes
                // with json encoding.
                unset($A['rec_data']);
                unset($A['options']);

                if ($A['allday'] == 1 ||
                    ( ($A['rp_date_start'] < $this->today_sql) &&
                    ($A['rp_date_end'] > $this->today_sql) )
                ) {
                    // This is an allday event, or spans days
                    $alldaydata[] = $A;
                } else {
                    // This is an event with start/end times.  For non-recurring
                    // events, see if it actually starts before or after today
                    // and adjust the times accordingly.
                    if ($A['rp_date_start'] < $this->today_sql) {
                        list($hr, $min, $sec) = explode(':', $A['rp_time_start1']);
                        $hr = '00';
                        $A['rp_times_start1'] = implode(':', array($hr, $min, $sec));
                    //} else {
                    //    $starthour = date('G', strtotime($A['rp_date_start'] .
                    //                    ' ' . $A['rp_time_start^1']));
                    }
                    if ($A['rp_date_end'] > $this->today_sql) {
                        list($hr, $min, $sec) = explode(':', $A['rp_time_end1']);
                        $hr = '23';
                        $A['rp_times_end1'] = implode(':', array($hr, $min, $sec));
                    //} else {
                    //    $endhour = date('G', strtotime($A['rp_date_end'] .
                    //                    ' ' . $A['rp_time_end1']));
                    }
                    $dtStart = new Date(strtotime($A['rp_date_start'] .
                                    ' ' . $A['rp_time_start1']));
                    $dtEnd = new Date(strtotime($A['rp_date_end'] .
                                    ' ' . $A['rp_time_end1']));

                    //if (date('i', strtotime($A['rp_date_end'] . ' ' .
                    //            $A['rp_time_end1'])) == '00') {
                    //    $endhour = $endhour - 1;
                    //}

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

                    if ($A['split'] == 1 &&
                        $A['rp_time_end2'] > $A['rp_time_start2']) {
                        // This is a split event, second half occurs later today.
                        // Events spanning multiple days can't be split, so we
                        // know that the start and end times are on the same day.
                        //$starthour = date('G', strtotime($A['rp_date_start'] .
                        //                ' ' . $A['rp_time_start2']));
                        $dtStart->setTimestamp(strtotime($event['rp_date_start'] .
                                ' ' . $event['rp_time_start2']));
                        $starthour = $dtStart->format('G', false);
                        $time_start = $dtStart->format($_CONF['timeonly'], false);
                        $dtEnd->setTimestamp(strtotime($event['rp_date_start'] .
                                ' ' . $event['rp_time_end2']));
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


/**
*   Create a weekly view calendar
*   @class evView_week
*/
class evView_week extends evView
{
    /**
    *   Construct the weekly view
    *
    *   @param  integer $year   Year to display, default is current year
    *   @param  integer $month  Starting month
    *   @param  integer $day    Starting day
    *   @param  integer $cat    Event category
    *   @param  integer $cal    Calendar to show
    *   @param  string  $opt    Optional template modifier, e.g. "print"
    */
    public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        $this->type = 'week';
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }


    /**
    *   Get the actual calendar view content
    *
    *   @return string      HTML for calendar content
    */
    public function Content()
    {
        global $_CONF, $_EV_CONF, $LANG_MONTH, $LANG_EVLIST;

        $retval = '';

        // Get the events
        $calendarView = Date_Calc::getCalendarWeek($this->day, $this->month, $this->year, '%Y-%m-%d');
        $start_date = $calendarView[0];
        $end_date = $calendarView[6];

        $dtStart = new Date(strtotime($start_date));
        $dtToday = $dtStart;    // used to update date strings each day
        $week_secs = 86400 * 7;
        $dtPrev = new Date($dtStart->toUnix() - $week_secs);
        $dtNext = new Date($dtStart->toUnix() + $week_secs);

        // Set up next and previous week links
        list($sYear, $sMonth, $sDay) = explode('-', $start_date);

        $tpl = 'weekview';
        $T = new Template(EVLIST_PI_PATH . '/templates/weekview');
        if ($this->opt == 'print') {
            $tpl .= '_print';
        } else {
            $tpl .= '_json';
        }
        $T->set_file(array(
            'week'      => $tpl . '.thtml',
            //'events'    => 'weekview/events.thtml',
        ) );

        $daynames = self::DayNames();
        $events = EVLIST_getEvents($start_date, $end_date,
                array('cat'=>$this->cat, 'cal'=>$this->cal));

        $start_mname = $LANG_MONTH[(int)$sMonth];
        $last_date = getdate($dtStart->toUnix() + (86400 * 6));
        $end_mname = $LANG_MONTH[$last_date['mon']];
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
        $T->set_var('date_range', $date_range);

        $T->set_block('week', 'dayBlock', 'dBlk');
        foreach($calendarView as $idx=>$weekData) {
            list($curyear, $curmonth, $curday) = explode('-', $weekData);
            $dtToday->setDateTimestamp($curyear, $curmonth, $curday, 1, 0, 0);
            $T->clear_var('eBlk');
            if ($weekData == $_EV_CONF['_today']) {
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

            //if ($_EV_CONF['_can_add']) {
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
                if (isset($A['cal_id'])) {
                    $this->cal_used[$A['cal_id']] = array(
                        'cal_name' => $A['cal_name'],
                        'cal_ena_ical' => $A['cal_ena_ical'],
                        'cal_id' => $event['cal_id'],
                        'fgcolor' => $A['fgcolor'],
                        'bgcolor' => $A['bgcolor'],
                    );
                }

                $T->set_var(array(
                    'event_times'   => $event_time,
                    'event_title'   => htmlspecialchars($A['title']),
                    'event_summary' => htmlspecialchars($A['summary']),
                    'event_id'      => $A['rp_id'],
                    'cal_id'        => $A['cal_id'],
                    'delete_imagelink' => EVLIST_deleteImageLink($A, $token),
                    //'event_title_and_link' => $eventlink,
                    'pi_url'        => EVLIST_URL,
                    'fgcolor'       => $A['fgcolor'],
                ) );
                if ($A['cal_id'] < 0) {
                    $T->set_var(array(
                        'is_meetup' => 'true',
                        'ev_url' => $A['url'],
                    ) );
                } else {
                    $T->clear_var('is_meetup');
                }
                $T->parse('eBlk', 'eventBlock', true);
            }

            $T->parse('dBlk', 'dayBlock', true);
        }

        $T->set_var(array(
            'pi_url'        => EVLIST_URL,
            'cal_header'    => $this->Header(),
            'cal_footer'    => $this->Footer(),
            'prevmonth'     => $dtPrev->format('n', false),
            'prevday'       => $dtPrev->format('j', false),
            'prevyear'      => $dtPrev->format('Y', false),
            'nextmonth'     => $dtNext->format('n', false),
            'nextday'       => $dtNext->format('j', false),
            'nextyear'      => $dtNext->format('Y', false),
            'urlfilt_cat'   => $this->cat,
            'urlfilt_cal'   => $this->cal,
            'cal_checkboxes' => $this->getCalCheckboxes(),
            'site_name'     => $_CONF['site_name'],
            'site_slogan'   => $_CONF['site_slogan'],
            'year'          => $this->year,
            'month'         => $this->month,
            'day'           => $this->day,
            'is_uikit'      => $_EV_CONF['_is_uikit'] ? 'true' : '',
        ) );
        $T->parse('output','week');
        return $T->finish($T->get_var('output'));
    }
}

class evView_month extends evView
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
        global $_CONF, $_EV_CONF, $LANG_MONTH;

        $retval = '';

        // Get all the dates in the month
        $calendarView = Date_Calc::getCalendarMonth($this->month, $this->year, '%Y-%m-%d');

        $x = count($calendarView) - 1;
        $y = count($calendarView[$x]) - 1;
        $starting_date = $calendarView[0][0];
        $ending_date = $calendarView[$x][$y];
        $daynames = self::DayNames();
        $events = EVLIST_getEvents($starting_date, $ending_date,
                array('cat'=>$cat, 'cal'=>$cal));
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

        $T = new Template(EVLIST_PI_PATH . '/templates/monthview');
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
                    $ev_hover = '';
                    $ev_title = COM_truncate($event['title'], 40, '...');

                    // Sanitize fields for display.  No HTML in the popup.
                    $title = htmlentities(strip_tags($event['title']));
                    $summary = htmlentities(strip_tags($event['summary']));

                    // add the calendar to the array to create the JS checkboxes
                    if (isset($event['cal_id'])) {
                        $this->cal_used[$event['cal_id']] = array(
                            'cal_name' => $event['cal_name'],
                            'cal_ena_ical' => $event['cal_ena_ical'],
                            'cal_id' => $event['cal_id'],
                            'fgcolor' => $event['fgcolor'],
                            'bgcolor' => $event['bgcolor'],
                        );
                    }

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
                        'ev_title'  => $ev_title,
                        'eid'       => $event['rp_id'],
                        'fgcolor'   => $event['fgcolor'],
                        'bgcolor'   => $event['bgcolor'],
                        'pi_url'        => EVLIST_URL,
                    ) );
                    if ($event['cal_id'] < 0) {
                        $T->set_var(array(
                            'is_meetup' => 'true',
                            'ev_url' => $event['url'],
                        ) );
                    } else {
                        $T->clear_var('is_meetup');
                    }
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

                //if ($_EV_CONF['_can_add']) {
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
        ) );

        $T->parse('output', 'monthview');
        return $T->finish($T->get_var('output'));
    }

}


/**
*   Display a yearly calendar.
*   @class  evView_year
*/
class evView_year extends evView
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
        global $_CONF, $_EV_CONF, $LANG_MONTH;

        $retval = '';

        // Get all the dates in the year
        $starting_date = sprintf('%d-01-01', $this->year);
        $ending_date = sprintf('%d-12-31', $this->year);
        $calendarView = Date_Calc::getCalendarYear($this->year, '%Y-%m-%d');
        $daynames = self::DayNames(1);
        $events = EVLIST_getEvents($starting_date, $ending_date,
            array('cat'=>$this->cat, 'cal'=>$this->cal));
        // A date object to handle formatting
        $dt = new Date('now');

        $T = new Template(EVLIST_PI_PATH . '/templates/yearview');
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

            $M = new Template($_CONF['path']
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
                            // Separate events by a newline if more than one
                            if (!empty($popup)) {
                                $popup .= EVLIST_tooltip_newline();
                            }
                            // Don't show a time for all-day events
                            if ($event['allday'] == 0) {
                                $dt->setTimestamp(strtotime($event['rp_date_start'] .
                                    ' ' . $event['rp_time_start1']));
                                // Time is a localized string, not a timestamp, so
                                // don't adjust for the timezone
                                $popup .= $dt->format($_CONF['timeonly'], false) . ': ';
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


/**
*   Create a list of events
*/
class evView_list extends evView
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
        global $_CONF, $_EV_CONF, $_USER, $_TABLES, $LANG_EVLIST;

        $retval = '';
        $T = new Template(EVLIST_PI_PATH . '/templates/');
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
            $dt = new Date('now', $_CONF['timezone']);
            $start = EV_MIN_DATE;
            //$end = $_EV_CONF['_today'];
            $end = $dt->toMySQL(true);
            $opts['order'] = 'DESC';
            break;
        case 3:         //this week
            $start = Date_Calc::beginOfWeek();
            $end = Date_Calc::endOfWeek();
            break;
        case 4:         //this month
            $start = Date_Calc::beginOfMonth();
            $year = Date_Calc::getYear();
            $month = Date_Calc::getMonth();
            $day = Date_Calc::daysInMonth($month, $year);
            $end = Date_Calc::dateFormat($day, $month, $year, '%Y-%m-%d');
            break;
        case 2:         //upcoming
        default:
            $opts['upcoming'] = true;
            $start = $_EV_CONF['_today'];
            $dt = new Date($_EV_CONF['_today_ts'] + (86400 * $_EV_CONF['max_upcoming_days']), $_CONF['timezone']);
            $end = $dt->format('Y-m-d', true);
            break;
        }

        //$_EV_CONF['meetup_enabled'] = false;
        $events = EVLIST_getEvents($start, $end, $opts);
        $andrange = '&amp;range=' . $this->range;
        $T->set_var('range', $this->range);

        if (!empty($this->cat)) {
            $andcat = '&amp;cat=' . $this->cat;
            $T->set_var('category', $this->cat);
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
                    $datesummary = sprintf($LANG_EVLIST['event_begins'],
                        EVLIST_formattedDate(strtotime($A['rp_date_start'])));
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


class evView_smallmonth extends evView
{
    public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }


    /**
    *   Display a small monthly calendar for the current month.
    *   Dates that have events scheduled are highlighted.
    *
    *   @param  integer $year   Year to display, default is current year
    *   @param  integer $month  Starting month
    *   @return string          HTML for calendar page
    */
    public function Render()
    {
        global $_CONF, $_EV_CONF, $LANG_MONTH, $_SYSTEM;

        $retval = '';

        // Default to the current year
        $monthnum_str = sprintf('%02d', (int)$this->month);

        // Get all the dates in the period
        $starting_date = date('Y-m-d', mktime(0, 0, 0, $this->month, 1, $this->year));
        $ending_date = date('Y-m-d', mktime(23, 59, 59, $this->month,
            Date_Calc::daysInMonth($this->year, $this->month), $this->year));
        $calendarView = Date_Calc::getCalendarMonth($this->month, $this->year, '%Y-%m-%d');
        $events = EVLIST_getEvents($starting_date, $ending_date, $opts);

        $T = new Template(EVLIST_PI_PATH . '/templates');
        $T->set_file(array(
            'smallmonth'  => 'phpblock_month.thtml',
        ) );

        $T->set_var(array(
            'thisyear' => $this->year,
            'month' => $this->month,
            'monthname' => $LANG_MONTH[(int)$this->month],
        ));

        // Set each day column header to the first letter of the day name
        $T->set_block('smallmonth', 'daynames', 'nBlock');
        $daynames = self::DayNames(1);
        foreach ($daynames as $key=>$dayname) {
            $T->set_var('dayname', $dayname);
            $T->parse('nBlock', 'daynames', true);
        }

        $T->set_block('smallmonth', 'week', 'wBlock');

        USES_class_date();
        $dt = new Date('now');

        foreach ($calendarView as $weeknum => $weekdata) {
            list($weekYear, $weekMonth, $weekDay) = explode('-', $weekdata[0]);
            $T->set_var(array(
                    'weekyear'  => $weekYear,
                    'weekmonth' => $weekMonth,
                    'weekday'   => $weekDay,
            ) );
            $T->set_block('smallmonth', 'day', 'dBlock');
            foreach ($weekdata as $daynum => $daydata) {
                list($y, $m, $d) = explode('-', $daydata);
                $T->clear_var('no_day_link');
                if ($daydata == $_EV_CONF['_today']) {
                    $dayclass = 'monthtoday';
                } elseif ($m == $monthnum_str) {
                    $dayclass = 'monthon';
                } else {
                    $T->set_var('no_day_link', 'true');
                    $dayclass = 'monthoff';
                }
                $popup = '';
                if (isset($events[$daydata])) {
                    // Create the tooltip hover text
                    $daylinkclass = $dayclass == 'monthoff' ?
                                'nolink-events' : 'day-events';
                    $dayspanclass='tooltip gl_mootip';
                    foreach ($events[$daydata] as $event) {
                        // Show event titles on different lines if more than one
                        if (!empty($popup)) $popup .= EVLIST_tooltip_newline();
                        // Don't show a time for all-day events
                        if ($event['allday'] == 0 &&
                            $event['rp_date_start'] == $event['rp_date_end']) {
                            $dt->setTimestamp(strtotime($event['rp_date_start'] .
                                ' ' . $event['rp_time_start1']));
                            // Time is a localized string, not a timestamp, so
                            // don't adjust for the timezone
                            $popup .= $dt->format($_CONF['timeonly'], false) . ': ';
                        }
                        $popup .= htmlentities($event['title']);
                    }
                    $T->set_var('popup', $popup);
                } else {
                    $dayspanclass='';
                    $daylinkclass = 'day-noevents';
                    $T->clear_var('popup');
                }
                $T->set_var(array(
                    'daylinkclass'      => $daylinkclass,
                    'dayclass'          => $dayclass,
                    'dayspanclass'      => $dayspanclass,
                    'day'               => substr($daydata, 8, 2),
                    'pi_url'            => EVLIST_URL,
                ) );
                $T->parse('dBlock', 'day', true);
            }
            $T->parse('wBlock', 'week', true);
            $T->clear_var('dBlock');
        }
        $T->parse('output', 'smallmonth');
        return $T->finish($T->get_var('output'));
    }
}


/**
*   Class to handle the event detail view. The actual view is created
*   by evRepeat::Render(). This class is used simply to create the standard
*   header.
*/
class evView_detail extends evView
{
    /*
    *   Construct the event detail view
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
        $this->type = 'detail';
        $this->inc_dt_sel = false;  // disable date/range selections
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }
}

?>
