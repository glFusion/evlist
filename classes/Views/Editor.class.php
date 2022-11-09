<?php
/**
 * Class to edit events and instances.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.8
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Views;
use glFusion\Database\Database;
use glFusion\Log\Log;
use Evlist\Models\Status;
use Evlist\Event;
use Evlist\Repeat;
use Evlist\Calendar;
use Evlist\DateFunc;
use Evlist\TicketType;
use Evlist\Config;


/**
 * Class for event editing
 * @package evlist
 */
class Editor
{
    /** Event object.
     * @var object */
    private $Event = NULL;

    /** Repeat instance object.
     * @var object */
    private $Repeat = NULL;

    /** Detail object.
     * @var object */
    private $Detail = NULL;

    /** URL to return upon cancel, or after saving.
     * @var string */
    private $cancel_url = '';

    /** Save action to pass to the action URL.
     * Will be prepended with "save".
     * @var string */
    private $saveaction = 'event';

    /** Flag to indicate this is an admin edit action.
     * @var boolean */
    private $isAdmin = false;

    public function __construct()
    {
        if (isset($_GET['from'])) {
            if ($_GET['from'] == 'admin') {
                $this->cancel_url = EVLIST_ADMIN_URL . '/index.php';
            } elseif (isset($_GET['eid']) && $_GET['from'] == 'ev_repeats') {
                $this->cancel_url = EVLIST_ADMIN_URL . '/index.php?repeats&eid=' . $_GET['eid'];
            }
        } elseif (EVLIST_checkReturn()) {
            $this->cancel_url = EVLIST_getReturn();
        } else {
            $this->cancel_url = EVLIST_URL . '/index.php';
        }
        $this->isAdmin = plugin_isadmin_evlist();
    }


    /**
     * Set the event being edited.
     *
     * @param   object  $Event  Event object
     * @return  object  $this
     */
    public function withEvent(Event $Event) : self
    {
        $this->Event = $Event;
        $this->Detail = $this->Event->getDetail();
        return $this;
    }


    /**
     * Set the specific occurrence being edited.
     *
     * @param   object  $Repeat Repeat object
     * @return  object  $this
     */
    public function withRepeat(Repeat $Repeat) : self
    {
        $this->Repeat = $Repeat;
        if ($this->Event === NULL) {
            $this->Event = $this->Repeat->getEvent();
        }
        $this->Detail = $this->Repeat->getDetail();
        return $this;
    }

    public function withCancelUrl(string $url) : self
    {
        $this->cancel_url = $url;
        return $this;
    }

    public function withSaveAction(string $action) : self
    {
        $this->saveaction = 'save' . $action;
        return $this;
    }

    /**
     * Check if the current user is an administrator.
     *
     * @param   bool    $var    True for administrators, False for regular users
     * @return  self
     */
    public function asAdmin(bool $var) : self
    {
        $this->isAdmin = $var;
        return $this;
    }


    private function getRepeatID()
    {
        if ($this->Repeat) {
            return $this->Repeat->getID();
        } else {
            return 0;
        }
    }


    /**
     * Creates the edit form.
     *
     * @return  string      HTML for edit form
     */
    public function Render() : string
    {
        global $_CONF, $_EV_CONF, $_TABLES, $_USER, $LANG_EVLIST,
                $LANG_ADMIN, $_GROUPS, $LANG_ACCESS, $_SYSTEM;

        // If an eid is specified and this is an object, then read the
        // event data- UNLESS a repeat ID is given in which case we're
        // editing a repeat and already have the info we need.
        // This probably needs to change, since we should always read event
        // data during construction.
        if (!EVLIST_canSubmit()) {
            // At least submit privilege required
            COM_404();
        }/* elseif ($this->Event) {
            // If an id is passed in, then read that record
            if (!$this->Read($eid)) {
                return 'Invalid object ID';
            }
        } elseif (isset($_POST['eid']) && !empty($_POST['eid'])) {
            // Returning to an existing form, probably due to errors
            $this->SetVars($_POST);

            // Make sure the current user has access to this event.
            if (!$this->hasAccess(3)) COM_404();
        }*/

        if (!$this->Event && !plugin_ismoderator_evlist()) {
            COM_404();
        }

        $T = new \Template(EVLIST_PI_PATH . '/templates');
        $T->set_file(array(
            'editor'    => 'editor.thtml',
            'tips'      => 'tooltipster.thtml',
        ) );

        // Set up the wysiwyg editor, if available
        switch (PLG_getEditorType()) {
        case 'ckeditor':
            $T->set_var('show_htmleditor', true);
            PLG_requestEditor('evlist','evlist_entry','ckeditor_evlist.thtml');
            PLG_templateSetVars('evlist_entry', $T);
            $postmode = 'html';
            SEC_setCookie(
                $_CONF['cookie_name'].'adveditor',
                SEC_createTokenGeneral('advancededitor'),
                time() + 1200,
                $_CONF['cookie_path'],
                $_CONF['cookiedomain'],
                $_CONF['cookiesecure'],
                false
            );
            break;
        case 'tinymce' :
            $T->set_var('show_htmleditor',true);
            PLG_requestEditor('evlist','evlist_entry','tinymce_evlist.thtml');
            PLG_templateSetVars('evlist_entry', $T);
            $postmode = 'html';
            break;
        default :
            // don't support others right now
            $T->set_var('show_htmleditor', false);
            break;
        }

        if (
            isset($this->Event->getRecData()['stop']) &&
            !empty($this->Event->getRecData()['stop'])
        ) {
            $T->set_var(array(
                'stopdate'      => $this->Event->getRecData()['stop'],
            ) );
        }

        // Set up the recurring options needed for the current event
        $recweekday  = '';
        switch ($this->Event->getRecurring()) {
        case 0:
            // Not a recurring event
            break;
        case EV_RECUR_MONTHLY:
            if (is_array($this->Event->getRecData()['listdays'])) {
                foreach ($this->Event->getRecData()['listdays'] as $mday) {
                    $T->set_var('mdchk'.$mday, EVCHECKED);
                }
            }
            break;
        case EV_RECUR_WEEKLY:
            //$T->set_var('listdays_val', COM_stripslashes($rec_data[0]));
            if (
                isset($this->Event->getRecData()['listdays']) &&
                is_array($this->Event->getRecData()['listdays']) &&
                !empty($this->Event->getRecData()['listdays'])
            ) {
                foreach($this->Event->getRecData()['listdays'] as $day) {
                    $day = (int)$day;
                    if ($day > 0 && $day < 8) {
                        $T->set_var('daychk'.$day, EVCHECKED);
                    }
                }
            }
            break;
        case EV_RECUR_DOM:
            $recweekday = $this->Event->getRecData()['weekday'];
            break;
        case EV_RECUR_DATES:
            $T->set_var(array(
                'stopshow'      => 'style="display:none;"',
                'custom_val' => implode(',', $this->Event->getRecData()['custom']),
            ) );
            break;
        }

        // Basic tabs for editing both events and instances, show up on
        // all edit forms
        $alert_msg = '';
        $rp_id = (int)$this->getRepeatID();
        if ($this->Repeat) {   // Editing a single occurrence
            // Make sure the current user has access to this event.
            if (!$this->Event->hasAccess(3)) COM_404();

            if ($this->saveaction == 'savefuturerepeat') {
                $alert_msg = EVLIST_alertMessage($LANG_EVLIST['editing_future'],
                        'warning');
            } else {
                $alert_msg = EVLIST_alertMessage($LANG_EVLIST['editing_instance'],
                        'info');
            }

            //$T->clear_var('contact_section');
            $T->clear_var('category_section');
            $T->clear_var('permissions_editor');

            // Set the static calendar name for the edit form.  Can't
            // change it for a single instance.
            $Cal = $this->Event->getCalendar();
            $cal_name = $Cal->getName();
            $T->set_var(array(
                'contact_section' => 'true',
                'is_repeat'     => 'true',    // tell the template it's a repeat
                'cal_name'      => $cal_name,
            ) );

            // Override our dates & times with those from the repeat.
            // $rp_id is passed when this is called from class Repeat.
            // Maybe that should pass in the repeat's data instead to avoid
            // another DB lookup.  An array of values could be used.
            /*$Rep = DB_fetchArray(DB_query("SELECT *
                    FROM {$_TABLES['evlist_repeat']}
                    WHERE rp_id='$rp_id'"), false);
            if ($Rep) {*/
                $date_start1 = $this->Repeat->getDateStart1()->format('Y-m-d',true);
                $date_end1 = $this->Repeat->getDateEnd1()->format('Y-m-d',true);
                $time_start1 = $this->Repeat->getTimeStart1();
                $time_end1 = $this->Repeat->getTimeEnd1();
                $time_start2 = $this->Repeat->getTimeStart2();
                $time_end2 = $this->Repeat->getTimeEnd2();
            /*}*/

        } else {            // Editing the main event record
            $date_start1 = $this->Event->getDateStart1();
            $date_end1 = $this->Event->getDateEnd1();
            $time_start1 = $this->Event->getTimeStart1();
            $time_end1 = $this->Event->getTimeEnd1();
            $time_start2 = $this->Event->getTimeStart2();
            $time_end2 = $this->Event->getTimeEnd2();

            if ($this->Event->getID() != '' && $this->Event->isRecurring() == 1) {
                $alert_msg = EVLIST_alertMessage($LANG_EVLIST['editing_series'],
                    'error');
            }
            if ($this->isAdmin) {
                $T->set_var('permissions_editor', 'true');
            }
            if ($_EV_CONF['enable_rsvp']) {
                $T->set_var('rsvp_enabled', true);
            }

            //$Intervals = new Models\Intervals;
            $T->set_var(array(
                'is_recurring' => $this->Event->isRecurring(),
                'recur_type' => $this->Event->getRecurring(),
                'recur_section' => 'true',
                'contact_section' => 'true',
                'category_section' => 'true',
                'upcoming_chk' => $this->Event->showUpcoming() ? EVCHECKED : '',
                'enable_reminders' => $_EV_CONF['enable_reminders'],
                'rem_status_checked' => $this->Event->remindersEnabled() == 1 ?
                        EVCHECKED : '',
                'commentsupport' => $_EV_CONF['commentsupport'],
                'ena_cmt_' . $this->Event->commentsEnabled() => 'selected="selected"',
                'recurring_format_options' =>
                        EVLIST_GetOptions($LANG_EVLIST['rec_formats'], $this->Event->getRecurring()),
                'recurring_weekday_options' => EVLIST_GetOptions(DateFunc::getWeekDays(), $recweekday, 1),
                'dailystop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['day_by_date'], ''),
                'monthlystop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['year_and_month'], $LANG_EVLIST['if_any']),
                'yearlystop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['year'], $LANG_EVLIST['if_any']),
                'listdaystop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['date_l'], $LANG_EVLIST['if_any']),
                'intervalstop_label' => sprintf($LANG_EVLIST['stop_label'],
                        $LANG_EVLIST['year_and_month'], $LANG_EVLIST['if_any']),
                'listdays_label' => sprintf($LANG_EVLIST['custom_label'],
                        $LANG_EVLIST['days_of_week'], ''),
                'custom_label' => sprintf($LANG_EVLIST['custom_label'],
                        $LANG_EVLIST['dates'], ''),
                'datestart_note' => $LANG_EVLIST['datestart_note'],
                'help_url' => EVLIST_getDocURL('event'),
                'rsvp_cmt_chk' => $this->Event->getOption('rsvp_comments') ? EVCHECKED : '',
                'rsvp_cmt_prompts' => implode('|', $this->Event->getOption('rsvp_cmt_prompts', array())),
            ) );
        }

        $action_url = $this->isAdmin ? EVLIST_ADMIN_URL . '/index.php' : EVLIST_URL . '/event.php';
        $delaction = 'delevent';
        switch ($this->saveaction) {
        case 'saverepeat':
        case 'savefuturerepeat':
        case 'saveevent':
            break;
        case 'moderate':
            // Approving a submission
            $this->saveaction = 'approve';
            $delaction = 'disapprove';
            $action_url = EVLIST_ADMIN_URL . '/index.php';
            $this->cancel_url = $_CONF['site_admin_url'] . '/moderation.php';
            break;
        default:
            $this->saveaction = 'saveevent';
            break;
        }

        $retval = '';

        $retval .= COM_startBlock($LANG_EVLIST['event_editor']);
        $summary = $this->Detail->getSummary();
        $full_description = $this->Detail->getDscp();
        $location = $this->Detail->getLocation();
        if (
            ($this->isAdmin ||
            ($_EV_CONF['allow_html'] == '1' && $_USER['uid'] > 1)
            )
            && $postmode == 'html'
        ) {
            $postmode = 'html';      //html
        } else {
            $postmode = 'plaintext';            //plaintext
            $summary = htmlspecialchars(
                COM_undoClickableLinks(
                    COM_undoSpecialChars(
                        $this->Detail->getSummary()
                    )
                )
            );
            $full_description = htmlspecialchars(
                COM_undoClickableLinks(
                    COM_undoSpecialChars(
                        $this->Detail->getDscp()
                    )
                )
            );
            $location = htmlspecialchars(
                COM_undoClickableLinks(
                    COM_undoSpecialChars(
                        $this->Detail->getLocation()
                    )
                )
            );
         }

        $starthour2 = '';
        $startminute2 = '';
        $endhour2 = '';
        $endminute2 = '';

        if ($this->Event->getDateEnd1() == '' || $this->Event->getDateEnd1() == '0000-00-00') {
            $this->Event->setDateEnd1($this->Event->getDateStart1());
        }
        if ($this->Event->getDateStart1() != '' && $this->Event->getDateStart1() != '0000-00-00') {
            list(
                $startmonth1, $startday1, $startyear1,$starthour1, $startminute1
            ) = $this->Event->DateParts($this->Event->getDateStart1(), $this->Event->getTimeStart1());
        } else {
            list(
                $startmonth1, $startday1, $startyear1, $starthour1, $startminute1
            ) = $this->Event->DateParts(date('Y-m-d', time()), date('H:i', time()));
        }

        // The end date can't be before the start date
        if ($this->Event->getDateEnd1() >= $this->Event->getDateStart1()) {
            list(
                $endmonth1, $endday1, $endyear1, $endhour1, $endminute1
            ) = $this->Event->DateParts($this->Event->getDateEnd1(), $this->Event->getTimeEnd1());
            $days_interval = DateFunc::dateDiff(
                    $endday1, $endmonth1, $endyear1,
                    $startday1, $startmonth1, $startyear1
            );
        } else {
            $days_interval = 0;
            $endmonth1  = $startmonth1;
            $endday1    = $startday1;
            $endyear1   = $startyear1;
            $endhour1   = $starthour1;
            $endminute1 = $startminute1;
        }

        // Skip weekends. Default to "no" if not already set for this event
        $skip = empty($this->Event->getRecData()['skip']) ? 0 : $this->Event->getRecData()['skip'];

        if (!empty($this->Event->getRecData()['freq'])) {
            $freq = (int)$this->Event->getRecData()['freq'];
            if ($freq < 1) $freq = 1;
        } else {
            $freq = 1;
        }
        $T->set_var(array(
            'freq_text' => $LANG_EVLIST['rec_periods'][$this->Event->getRecurring()],
            'rec_freq'  => $freq,
            "skipnext{$skip}_checked" => EVCHECKED,
        ) );

        foreach ($LANG_EVLIST['rec_intervals'] as $key=>$str) {
            $T->set_var('dom_int_txt_' . $key, $str);
            if (
                isset($this->Event->getRecData()['interval']) &&
                is_array($this->Event->getRecData()['interval'])
            ) {
                if (in_array($key, $this->Event->getRecData()['interval'])) {
                    $T->set_var('dom_int_chk_'.$key, EVCHECKED);
                }
            }
        }

        $start1 = DateFunc::TimeSelect('start1', $time_start1);
        $start2 = DateFunc::TimeSelect('start2', $time_start2);
        $end1 = DateFunc::TimeSelect('end1', $time_end1);
        $end2 = DateFunc::TimeSelect('end2', $time_end2);
        $cal_select = Calendar::optionList($this->Event->getCalendarID(), true, 3);
        $navbar = new \navbar;
        $cnt = 0;

        $T->set_var(array(
            'is_admin'      => $this->isAdmin,
            'action_url'    => $action_url,
            'alert_msg'     => $alert_msg,
            'cancel_url'    => $this->cancel_url,
            'eid'           => $this->Event->getID(),
            'rp_id'         => $rp_id,
            'title'         => $this->Detail->getTitle(),
            'summary'       => $summary,
            'description'   => $full_description,
            'location'      => $location,
            'status'        => $this->Event->getStatus(),
            'url'           => $this->Detail->getUrl(),
            'street'        => $this->Detail->getStreet(),
            'city'          => $this->Detail->getCity(),
            'province'      => $this->Detail->getProvince(),
            'country'       => $this->Detail->getCountry(),
            'postal'        => $this->Detail->getPostal(),
            'contact'       => $this->Detail->getContact(),
            'email'         => $this->Detail->getEmail(),
            'phone'         => $this->Detail->getPhone(),
            'startdate1'    => $date_start1,
            'enddate1'      => $date_end1,
            //'d_startdate1'  => EVLIST_formattedDate($this->Event->getDateStart1()),
            //'d_enddate1'    => EVLIST_formattedDate($this->Event->getDateEnd1()),
            // Don't need seconds in the time boxes
            'hour_mode'     => $_CONF['hour_mode'],
            'time_start1'   => substr($time_start1, 0, 5),
            'time_end1'     => substr($time_end1, 0, 5),
            'time_start2'   => substr($time_start2, 0, 5),
            'time_end2'     => substr($time_end2, 0, 5),
            'start_hour_options1'   => $start1['hour'],
            'start_minute_options1' => $start1['minute'],
            'startdate1_ampm'       => $start1['ampm'],
            'end_hour_options1'     => $end1['hour'],
            'end_minute_options1'   => $end1['minute'],
            'enddate1_ampm'         => $end1['ampm'],
            'start_hour_options2'   => $start2['hour'],
            'start_minute_options2' => $start2['minute'],
            'startdate2_ampm'       => $start2['ampm'],
            'end_hour_options2'     => $end2['hour'],
            'end_minute_options2'   => $end2['minute'],
            'enddate2_ampm'         => $end2['ampm'],
            'src'   => isset($_GET['src']) && $_GET['src'] == 'a' ? '1' : '0',

            'del_button'    => $this->Event->getID() == '' ? '' : 'true',
            'saveaction'    => $this->saveaction,
            'delaction'     => $delaction,
            'owner_id'      => $this->Event->getOwnerID(),
            'days_interval' => $days_interval,
            'display_format' => $_CONF['shortdate'],
            'ts_start'      => strtotime($this->Event->getDateStart1()),
            'ts_end'        => strtotime($this->Event->getDateEnd1()),
            'cal_select'    => $cal_select,
            'contactlink_chk' => $this->Event->getOption('contactlink') == 1 ?
                                EVCHECKED : '',
            'lat'           => EVLIST_coord2str($this->Detail->getLatitude()),
            'lng'           => EVLIST_coord2str($this->Detail->getLongitude()),
            'perm_msg'      => $LANG_ACCESS['permmsg'],
            'last'          => $LANG_EVLIST['rec_intervals'][5],
            'doc_url'       => EVLIST_getDocURL('event'),
            // If the event timezone is "local", just use some valid timezone
            // for the selection. The checkbox will be checked which will
            // hide the timezone selection anyway.
            'tz_select'     => \Date::getTimeZoneDropDown(
                        $this->Event->getTZID() == 'local' ? $_CONF['timezone'] : $this->Event->getTZID(),
                        array('id' => 'tzid', 'name' => 'tzid')),
            'tz_islocal'    => $this->Event->getTZID() == 'local' ? EVCHECKED : '',
//            'isNew'         => (int)$this->isNew,
            'fomat_opt'     => $this->Event->getRecurring(),
            'owner_name' => COM_getDisplayName($this->Event->getOwnerID()),
        ) );

        if ($_EV_CONF['enable_rsvp'] && $rp_id == 0) {
            $TickTypes = TicketType::GetTicketTypes();
            $T->set_block('editor', 'Tickets', 'tTypes');
            $tick_opts = $this->Event->getOption('tickets');
            foreach ($TickTypes as $tick_id=>$TicketType) {
                // Check enabled tickets. Ticket type 1 enabled by default
                if (isset($tick_opts[$tick_id]) || $tick_id == 1) {
                    $checked = true;
                    if (isset($tick_opts[$tick_id]) && isset($tick_opts[$tick_id]['fee'])) {
                        $fee = (float)$tick_opts[$tick_id]['fee'];
                    } else {
                        $fee = 0;
                    }
                } else {
                    $checked = false;
                    $fee = 0;
                }
                $T->set_var(array(
                    'tick_id' => $tick_id,
                    'tick_dscp' => $TicketType->getDscp(),
                    'tick_fee' => $fee,
                    'tick_checked' => $checked,
                ) ) ;
                $T->parse('tTypes', 'Tickets', true);
            }

            if ($_EV_CONF['rsvp_print'] > 0) {
                $rsvp_print_chk  = 'rsvp_print_chk' . (int)$this->Event->getOption('rsvp_print');
                $rsvp_print = 'true';
            } else {
                $rsvp_print = '';
                $rsvp_print_chk = 'no_rsvp_print';
            }

            $T->set_var(array(
                'enable_rsvp' => 'true',
                'reg_chk'.(int)$this->Event->getOption('use_rsvp') => EVCHECKED,
                'rsvp_wait_chk' => $this->Event->getOption('rsvp_waitlist') == 1 ?
                                EVCHECKED : '',
                'max_rsvp'   => $this->Event->getOption('max_rsvp'),
                'max_user_rsvp' => $this->Event->getOption('max_user_rsvp'),
                'rsvp_cutoff' => $this->Event->getOption('rsvp_cutoff'),
                'use_rsvp' => $this->Event->getOption('use_rsvp'), // for javascript
                'rsvp_waitlist' => $this->Event->getOption('rsvp_waitlist'),
                'rsvp_print'    => $rsvp_print,
                $rsvp_print_chk => 'checked="checked"',
            ) );

        }   // if rsvp_enabled

        // Split & All-Day settings
        if ($this->Event->isAllDay() == 1) {   // allday, can't be split, no times
            $T->set_var(array(
                'starttime1_show'   => 'style="display:none;"',
                'endtime1_show'     => 'style="display:none;"',
                'datetime2_show'    => 'style="display:none;"',
                'allday_checked'    => EVCHECKED,
                'split_checked'     => '',
                'split_show'        => 'style="display:none;"',
            ) );
        } elseif ($this->Event->isSplit() == '1') {
            $T->set_var(array(
                'split_checked'     => EVCHECKED,
                'allday_checked'    => '',
                'allday_show'       => 'style="display:none"',
            ) );
        } else {
            $T->set_var(array(
                'datetime2_show'    => 'style="display:none;"',
            ) );
        }

        // Category fields. If $_POST['categories'] is set, then this is a
        // form re-entry due to an error saving. Populate checkboxes from the
        // submitted form. Include the user-added category, if any.
        // If not from a form re-entry, get the checked categories from the
        // evlist_lookup table.
        // Both "select" and "checkbox"-type values are supplied so the
        // can use either form element.
        if ($_EV_CONF['enable_categories'] == '1') {
            try {
                $cresult = Database::getInstance()->conn->executeQuery(
                    "SELECT tc.id, tc.name FROM {$_TABLES['evlist_categories']} tc
                    WHERE tc.status='1' ORDER BY tc.name"
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $cresult = false;
            }

            $T->set_block('editor', 'catSelect', 'catSel');
            if ($cresult) {
                if (isset($_POST['categories']) && is_array($_POST['categories'])) {
                    // Coming from a form re-entry
                    $cat_array = $_POST['categories'];
                } else {
                    $cat_array = $this->Event->getCategories();
                }
                while ($A = $cresult->fetchAssociative()) {
                    if (array_search($A['id'], array_column($cat_array, 'id')) !== false) {
                        // category is currently selected
                        $chk = EVCHECKED;
                        $sel = EVSELECTED;
                    } else {
                        $chk = '';
                        $sel = '';
                    }
                    $T->set_var(array(
                        'cat_id'    => $A['id'],
                        'cat_name'  => htmlspecialchars($A['name']),
                        'cat_chk'   => $chk,
                        'cat_sel'   => $sel,
                    ) );
                    $T->parse('catSel', 'catSelect', true);
                }
                if (isset($_POST['newcat'])) {
                    $T->set_var('newcat', $_POST['newcat']);
                }

                if ($_USER['uid'] > 1 && $rp_id == 0) {
                    $T->set_var('category_section', 'true');
                    $T->set_var('add_cat_input', 'true');
                }
            }
        }

        // Enable the post mode selector if we allow HTML and the user is
        // logged in, or if this user is an authorized editor
        if (
            $this->isAdmin ||
            ($_EV_CONF['allow_html'] == '1' && $_USER['uid'] > 1)
        ) {
            $T->set_var(array(
                'postmode_options' => EVLIST_GetOptions($LANG_EVLIST['postmodes'], $postmode),
                'allowed_html' => COM_allowedHTML('evlist.submit'),
                'postmode' => 'html',
            ) );
            if ($postmode == 'plaintext') {
                // plaintext, hide postmode selector
                $T->set_var(array(
                    'postmode_show' => ' style="display:none"',
                    'postmode' => 'html',
                ) );
            }
            $T->parse('event_postmode', 'edit_postmode');
        }

        if ($this->isAdmin) {
            $T->set_var(
                'owner_dropdown',
                COM_optionList(
                    $_TABLES['users'], 'uid,username', $this->Event->getOwnerID(), 1
                )
            );
        }

        if (Config::get('enable_rsvp')) {
            $T->set_var(array(
                'rsvp_view_grp_dropdown' => SEC_getGroupDropdown(
                    (int)$this->Event->getOption('rsvp_view_grp', 1), 3, 'rsvp_view_grp'
                ),
                'rsvp_signup_grp_dropdown' => SEC_getGroupDropdown(
                    (int)$this->Event->getOption('rsvp_signup_grp', 1), 3, 'rsvp_signup_grp'
                ),
            ) );
        }

        // can only change permissions on main event
        if ($rp_id == 0) {
            $T->set_var(array(
                'permissions_editor' => SEC_getPermissionsHTML(
                    $this->Event->getPerms()['perm_owner'],
                    $this->Event->getPerms()['perm_group'],
                    $this->Event->getPerms()['perm_members'],
                    $this->Event->getPerms()['perm_anon']
                ),
                'group_dropdown' => SEC_getGroupDropdown($this->Event->getGroupID(), 3),
            ) );
        }

        // Latitude & Longitude part of location, if Location plugin is used
        if ($_EV_CONF['use_locator']) {
            $status = LGLIB_invokeService(
                'locator', 'optionList',
                '',
                $output,
                $svc_msg
            );
            if ($status == PLG_RET_OK) {
                $T->set_var(array(
                    'use_locator'   => 'true',
                    'loc_selection' => $output,
                ) );
            }
            /*$opts = PLG_callFunctionForOnePlugin(
                'plugin_optionlist_locator',
                array(1 => '')
            );
            if (!empty($opts)) {
                $T->set_var(array(
                    'use_locator'   => 'true',
                    'loc_selection' => $output,
                ) );
            }*/
        }
        $T->parse('tooltipster_js', 'tips');
        $T->parse('output', 'editor');
        $retval .= $T->finish($T->get_var('output'));

        $retval .= COM_endBlock();
        return $retval;
    }

}
