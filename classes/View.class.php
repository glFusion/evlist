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

USES_evlist_functions();
USES_lglib_class_datecalc();
date_default_timezone_set('UTC');

/**
*   Create a calendar view
*   @class View
*/
class View
{
    protected $year;        // Year of display
    protected $month;       // Month of display
    protected $day;         // Day of display
    protected $cat;         // Category to display
    protected $cal;         // Calendar to display
    protected $opts;        // Misc. options
    protected $cal_used = array();    // Array of calendars used in display
    protected $range;       // Range selector (past, future, etc)
    protected $today;       // Holder for today's date
    protected $today_sql;   // Today's date in YYYY-MM-DD format. Used often.
    protected $type;        // View type (month, year, etc)
    protected $tpl_opt;     // 'print' to create a printable view
    protected $inc_dt_sel= true;    // true to include date/range opts

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
    public static function getView($type='', $year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
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
        if (!in_array($type, array('day','week','month','year','list', 'smallmonth'))) {
            $type = isset($_EV_CONF['default_view']) ?
                $_EV_CONF['default_view'] : 'month';
        }
        $class = "Evlist\\View_{$type}";
        if (class_exists($class)) {
            $view = new $class($year, $month, $day, $cat, $cal, $opts);
            return $view;
            //return $view->Render();
        } else {
            // last-ditch error if $type isn't valid
            //return '<span class="alert">Failure loading calendar</span>';
            return NULL;
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

        $this->today = $_EV_CONF['_now'];
        $this->today_sql = $this->today->format('Y-m-d');
        $this->year = (int)$year;
        $this->month = (int)$month;
        $this->day = (int)$day;
        $this->cat = (int)$cat;
        $this->cal = (int)$cal;
        $this->opts = is_array($opts) ? $opts : array();
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
        $tpl = $view == '' ? $this->type . 'view' : $view;
        if ($this->tpl_opt == 'print') {
            $tpl .= '_print';
        } else {
            $tpl .= '_json';
        }
        return $tpl;
    }


    /**
    *   Display the common header for all calendar views.
    *
    *   @lparam boolean $add_link   True to include a "Add Event" button
    *   @return string          HTML for calendar header
    */
    public function Header($add_link = true)
    {
        global $_EV_CONF, $LANG_EVLIST, $LANG_MONTH, $_TABLES;

        $retval = '';
        $thisyear = $this->today->format('Y');
        $thismonth = $this->today->format('m');
        $thisday = $this->today->format('d');

        $T = new \Template(EVLIST_PI_PATH . '/templates');
        $T->set_file('header', 'calendar_header.thtml');

        $type_options = COM_optionList($_TABLES['evlist_categories'],
            'id,name', $this->cat, 1, 'status=1');
        $range_options = EVLIST_GetOptions($LANG_EVLIST['ranges'], $this->range);

        // Figure out the add event link, depending on the view.
        if ($add_link && EVLIST_canSubmit()) {
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
            //'view'      => $this->view,
            'curdate'   => sprintf("%d-%02d-%02d", $this->year, $this->month, $this->day),
            'urlfilt_cal' => (int)$this->cal,
            'urlfilt_cat' => (int)$this->cat,
            'use_json' => 'true',
            'is_uikit' => $_EV_CONF['_is_uikit'],
            'view'  => $this->type,
            'iconset'   => $_EV_CONF['_iconset'],
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
            //if ($v != 'list') {
                $options .= '<option value="' . $v . '" ' . $sel . ' >' .
                        $LANG_EVLIST['periods'][$v] . '</option>' . LB;
            //}
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

        $T = new \Template(EVLIST_PI_PATH . '/templates');
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
            'is_uikit'  => $_EV_CONF['_is_uikit'] ? 'true' : '',
            'iconset'   => $_EV_CONF['_iconset'],
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
        // Only used to set the calendar view, no change when
        // viewing an event
        if ($this->type == 'detail') return;

        $A = SESS_getVar('evlist.current');
        if (is_array($A['date'])) {
            if ($this->year == 0) $this->year = $A['date'][0];
            if ($this->month == 0) $this->month = $A['date'][1];
            if ($this->day == 0) $this->day = $A['date'][2];
        } else {
            if ($this->year == 0) $this->year = \Date_Calc::getYear();
            if ($this->month == 0) $this->month = \Date_Calc::getMonth();
            if ($this->day == 0) $this->day = \Date_Calc::getDay();
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


    /**
    *   Return the raw content of the view.
    *   This provides the actual calendar content to be used in calendar
    *   web and print views and updated by Ajax. Each view type must provide
    *   a version of this function.
    *
    *   @return string      HTML for calendar
    */
    public function Content()
    {
        return 'Not Implemented';
    }


    /**
    *   Render a complete calendar page
    *
    *   @return string      HTML for calendar page
    */
    public function Render()
    {
        return $this->viewJSON();
    }


    /**
    *   Prepare variables for view functions using JSON templates
    *   Reads values from the session, if available. If no session present,
    *   get values from parameters or current date
    *
    *   @return string      Complete HTML for requested calendar
    */
    protected function viewJSON()
    {
        global $_EV_CONF;

        $T = new \Template(EVLIST_PI_PATH . '/templates/');
        //$T = new Template(EVLIST_PI_PATH . '/templates/' . $type . 'view');
        $T->set_file(array(
            'view'  => 'json_cal_wrapper.thtml',
        ) );

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
        global $_EV_CONF;

        $boxes = '';
        if (is_array($this->cal_used) && !empty($this->cal_used)) {
            $T = new \Template(EVLIST_PI_PATH . '/templates/');
            $T->set_file('boxes', 'cal_checkboxes.thtml');
            $T->set_block('boxes', 'cal_item', 'item');
            asort($this->cal_used);
            foreach ($this->cal_used as $key=>$cal) {
                $T->set_var(array(
                    'fgcolor'   => $cal['fgcolor'],
                    'key'       => $key,
                    'cal_name'  => $cal['cal_name'],
                    'iconset'   => $_EV_CONF['_iconset'],
                    'chk'   => self::getCalShowPref($key) ? EVCHECKED : ''
                ) );
                $T->parse('item', 'cal_item', true);
            }
            $boxes = $T->parse('output', 'boxes');
        }
        return $boxes;
    }


    /**
    *   Return the newline characters to use for the tooltips.
    *   UIkit themes need <br />, Vintage only uses LB
    *
    *   @return string      Newline characters
    */
    protected static function tooltip_newline()
    {
        global $_EV_CONF;

        if ($_EV_CONF['_is_uikit']) {
            return '<br />' . LB;
        } else {
            return LB;
        }
    }


    /**
    *   Get the user preference whether to show a calendar.
    *   This comes from the checkboxes that are saved to a
    *   session var when checked or unchecked.
    *
    *   @param  integer $cal_id     ID of calendar to check
    *   @return integer         1 to show calendar, 0 to hide
    */
    protected static function getCalShowPref($cal_id)
    {
        static $calprefs = NULL;
        if ($calprefs === NULL) {
            $calprefs = SESS_getVar('evlist.calshowpref');
            if (!is_array($calprefs)) $calprefs = array();
        }
        if (array_key_exists($cal_id, $calprefs) && $calprefs[$cal_id] == 0) {
            return 0;
        } else {
            return 1;
        }
    }


    /**
    *   Add calender info to the cal_used array.
    *   Used later to build the calendar checkboxes and subscription links.
    *
    *   @param  array   $event  Array of event info
    */
    protected function addCalUsed($event)
    {
        if (!isset($event['cal_id'])) return;   // invalid calendar info
        if (!array_key_exists($event['cal_id'], $this->cal_used)) {
            $this->cal_used[$event['cal_id']] = array(
                    'cal_name' => $event['cal_name'],
                    'cal_ena_ical' => $event['cal_ena_ical'],
                    'cal_id' => $event['cal_id'],
                    'fgcolor' => $event['fgcolor'],
                    'bgcolor' => $event['bgcolor'],
                );
        }
    }

}

?>
