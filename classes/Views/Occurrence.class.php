<?php
/**
 * Class to display an event instance.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Views;
use Evlist\Models\Status;
use Evlist\Repeat;
use Evlist\Icon;
use Evlist\TicketType;
use Evlist\Ticket;
use Evlist\Reminder;


/**
 * Class for events.
 * @package evlist
 */
class Occurrence
{
    /** Repeat record ID.
     * @var integer */
    private $rp_id = 0;

    /** Event record ID.
     * @var string */
    private $ev_id = '';

    /** Occurrence object.
     * @var object */
    private $Repeat = NULL;

    /** Query string used in search.
     * @var string */
    private $_qs = '';

    /** Template name for rendering the event view.
     * @var string */
    private $_tpl = 'event';

    private $_is_admin = false;

    /** Comment mode used for event display.
     * @var string */
    private $_cmtmode = 'nested';

    /** Comment ordering for event display.
     * @var string */
    private $_cmtorder = 'ASC';

    /** Referer URL, used for the "back to calendar" button.
     * @var string */
    private $_referer = '';


    /**
     *  Constructor.
     *  Reads in the specified event repeat, if $rp_id is set.
     *  If $id is zero, then a new entry is being created.
     *
     *  @param integer $rp_id   Optional repeat ID
     */
    public function __construct(?int $rp_id=0)
    {
        $this->_is_admin = plugin_ismoderator_evlist();
        $this->_referer = EVLIST_URL . '/index.php';    // default
        $this->rp_id = (int)$rp_id;
        $this->Repeat = Repeat::getInstance($this->rp_id);
        $this->ev_id = $this->Repeat->getEventID();
    }


    /**
     * Display the detail page for the event occurrence.
     *
     * @return  string      HTML for the page.
     */
    public function Render() : string
    {
        global $_CONF, $_USER, $_EV_CONF, $LANG_EVLIST, $LANG_WEEK,
                $LANG_LOCALE, $_SYSTEM, $LANG_EVLIST_HELP;

        $retval = '';
        $url = '';
        $location = '';
        $street = '';
        $city = '';
        $province = '';
        $country = '';
        $postal = '';
        $name = '';
        $email = '';
        $phone = '';

        if ($this->rp_id == 0) {
            return EVLIST_alertMessage($LANG_EVLIST['access_denied']);
        }

        $T = new \Template(EVLIST_PI_PATH . '/templates/');
        $T->set_file(array(
            'event' => $this->_tpl . '.thtml',
            //'editlinks' => 'edit_links.thtml',
            'datetime' => 'date_time.thtml',
            'address' => 'address.thtml',
            'contact' => 'contact.thtml',
        ) );

        USES_lib_social();

        $Detail = $this->Repeat->getDetail();
        $Event = $this->Repeat->getEvent();
        $permalink = COM_buildUrl(EVLIST_URL . '/view.php?&rid=0&eid=' . $this->ev_id);

        // If plain text then replace newlines with <br> tags
        $summary = $Detail->getSummary();
        $full_description = $Detail->getDscp();
        $location = $Detail->getLocation();
        if ($Event->getPostMode() == '1') {       //plaintext
            $summary = nl2br($summary);
            $full_description = nl2br($summary);
            $location = nl2br($summary);
        }
        $title = $Detail->getTitle();
        if ($Event->getPostmode() != 'plaintext') {
            $summary = PLG_replaceTags($summary);
            $full_description = PLG_replaceTags($full_description);
            $location = $location != '' ? PLG_replaceTags($location) : '';
        }
        if ($this->_qs != '') {
            $title = COM_highlightQuery($title, $this->_qs);
            if (!empty($summary)) {
                $summary  = COM_highlightQuery($summary, $this->_qs);
            }
            if (!empty($full_description)) {
                $full_description = COM_highlightQuery($full_description, $this->_qs);
            }
            if (!empty($location)) {
                $location = COM_highlightQuery($location, $this->_qs);
            }
        }
        $date_start = $this->Repeat->getDateStart1()->format($_CONF['dateonly'], true);
        $date_end = $this->Repeat->getDateEnd1()->format($_CONF['dateonly'], true);
        $time_start1 = '';
        $time_end1 = '';
        $time_start2 = '';
        $time_end2 = '';
        if ($date_end == $date_start) $date_end = '';
        if ($Event->isAllDay()) {
            $allday = '<br />' . $LANG_EVLIST['all_day_event'];
        } else {
            $allday = '';
            if ($this->Repeat->getTimeStart1() != '') {
                $time_start1 = $this->Repeat->getDateStart1()->format($_CONF['timeonly'], true);
                $time_end1 =  $this->Repeat->getDateEnd1()->format($_CONF['timeonly'], true);
            }
            //$time_period = $time_start . $time_end;
            if ($Event->isSplit()) {
                //$this->setDateStart2($this->date_start . ' ' . $this->time_start2);
                //$this->setDateEnd2($this->date_start . ' ' . $this->time_end2);
                $time_start2 = $this->Repeat->getDateStart2()->format($_CONF['timeonly'], true);
                $time_end2 = $this->Repeat->getDateEnd2()->format($_CONF['timeonly'], true);
            }
        }

        // Get the link to more info. If it's an external link, target a
        // new browser window.
        $url = $Detail->getUrl();
        if (!empty($url)) {
            $url = str_replace('%site_url%', $_CONF['site_url'], $url);
            if (strncasecmp($_CONF['site_url'], $url, strlen($_CONF['site_url']))) {
                $target = 'target="_blank"';
            } else {
                $target = '';       // Internal url
            }
            $more_info_link = sprintf($LANG_EVLIST['click_here'], $url, $target);
        } else {
            $more_info_link = '';
        }
        $street = $Detail->getStreet();
        $city = $Detail->getCity();
        $province = $Detail->getProvince();
        $postal = $Detail->getPostal();
        $country = $Detail->getCountry();

        // Now get the text description of the recurring interval, if any
        if (
            $Event->getRecurring() &&
            $Event->getRecData()['type'] < EV_RECUR_DATES
        ) {
            $rec_data = $Event->getRecData();
            $rec_string = $LANG_EVLIST['recur_freq_txt'] . ' ' .
                $Event->RecurDscp();
            //$Days = new Models\Days;
            switch ($rec_data['type']) {
            case EV_RECUR_WEEKLY:        // sequential days
                $weekdays = array();
                if (is_array($rec_data['listdays'])) {
                    foreach ($rec_data['listdays'] as $daynum) {
                        $weekdays[] = $LANG_WEEK[$daynum];
                        //$weekdays[] = $Days[$daynum];
                    }
                    $days_text = implode(', ', $weekdays);
                } else {
                    $days_text = '';
                }
                $rec_string .= ' '.sprintf($LANG_EVLIST['on_days'], $days_text);
                break;
            case EV_RECUR_DOM:
                $days = array();
                foreach($rec_data['interval'] as $key=>$day) {
                    $days[] = $LANG_EVLIST['rec_intervals'][$day];
                }
                $days_text = implode(', ', $days) . ' ' .
                    //$Days[$rec_data['weekday']];
                    $LANG_WEEK[$rec_data['weekday']];
                $rec_string .= ' ' . sprintf($LANG_EVLIST['on_the_days'],
                    $days_text);
                break;
            }
            if (
                $Event->getRecData()['stop'] != '' &&
                $Event->getRecData()['stop'] < EV_MAX_DATE
            ) {
                $stop_date = new \Date($Event->getRecData()['stop'], $Event->getTZID());
                $rec_string .= sprintf(
                    $LANG_EVLIST['recur_stop_desc'],
                    $stop_date->format($_CONF['dateonly'], true)
                );
            }
        } else {
            $rec_string = '';
        }

        $T->set_var(array(
            'lang_locale' => $_CONF['iso_lang'],
            'charset'   => COM_getCharset(),
            'direction' => (empty($LANG_DIRECTION) ? 'ltr' : $LANG_DIRECTION),
            'page_site_splitter' => empty($title) ? '' : ' - ',
            'pi_url'    => EVLIST_URL,
            'webcal_url' => EVLIST_URL,
            'rp_id'     => $this->rp_id,
            'ev_id'     => $this->ev_id,
            'title' => $title,
            'summary' => $summary,
            'full_description' => $full_description,
            'can_edit' => $Event->canEdit() ? 'true' : '',
            'start_time1' => $time_start1,
            'end_time1' => $time_end1,
            'start_time2' => $time_start2,
            'end_time2' => $time_end2,
            'start_date' => $date_start,
            'end_date' => $date_end,
            'start_datetime1' => $date_start . $time_start1,
            'end_datetime1' => $date_end . $time_end2,
            'allday_event' => $Event->isAllDay() ? 'true' : '',
            'is_recurring' => $Event->getRecurring(),
            'can_subscribe' => $Event->getCalendar()->isIcalEnabled(),
            'recurring_event'    => $rec_string,
            'owner_id'      => $Event->getOwnerID(),
            'cal_name'      => $Event->getCalendar()->getName(),
            'cal_id'        => $Event->getCalendarID(),
            'site_name'     => $_CONF['site_name'],
            'site_slogan'   => $_CONF['site_slogan'],
            'more_info_link' => $more_info_link,
            'show_tz'   => $Event->getTZID() == 'local' ? '' : 'true',
            'timezone'  => $Event->getTZID(),
            'tz_offset' => sprintf('%+d', $this->Repeat->getDateStart1()->getOffsetFromGMT(true)),
            'social_icons'  => $this->Repeat->getShareIcons($permalink),
            'icon_remove' => Icon::getIcon('delete'),
            'icon_edit' => Icon::getIcon('edit'),
            'icon_copy' => Icon::getIcon('copy'),
            'icon_subscribe' => Icon::getIcon('subscribe'),
            'icon_print' => Icon::getIcon('print'),
            'lang_prt_title' => $LANG_EVLIST_HELP['prt_tickets_btn'],
            '_referer' => $this->_referer,
        ) );

        $outputHandle = \outputHandler::getInstance();
        $outputHandle->addLink('canonical', $permalink, HEADER_PRIO_NORMAL);
        $outputHandle->addMeta(
            'property',
            'og:site_name',
            $_CONF['site_name'],
            HEADER_PRIO_NORMAL
        );
        $outputHandle->addMeta(
            'property',
            'og:locale',
            isset($LANG_LOCALE) ? $LANG_LOCALE : 'en_US',
            HEADER_PRIO_NORMAL
        );
        $outputHandle->addMeta(
            'property',
            'og:title',
            $Detail->getTitle(),
            HEADER_PRIO_NORMAL
        );
        $outputHandle->addMeta(
            'property',
            'og:type',
            'event',
            HEADER_PRIO_NORMAL
        );
        $outputHandle->addMeta(
            'property',
            'og:url',
            $permalink,
            HEADER_PRIO_NORMAL
        );
        $outputHandle->AddMeta(
            'name',
            'description',
            $Detail->getSummary(),
            HEADER_PRIO_NORMAL
        );
        $outputHandle->AddMeta(
            'property',
            'og:description',
            $Detail->getSummary(),
            HEADER_PRIO_NORMAL
        );

        // Show the user comments. Moderators and event owners can delete comments
        if ($Event->commentsEnabled()) {
            USES_lib_comment();
            $T->set_var(
                'usercomments',
                CMT_userComments(
                    $this->rp_id,
                    $Detail->getTitle(),
                    'evlist',
                    $this->_cmtorder,
                    $this->_cmtmode,
                    0,
                    1,
                    false,
                    (plugin_ismoderator_evlist() || $Event->getOwnerID() == $_USER['uid']),
                    $Event->commentsEnabled()
                )
            );
        }

        if ($Event->userCanRegister()) {
            if (time() > $this->Repeat->getDateStart1()->toUnix() - ((int)$Event->getOption('rsvp_cutoff') * 86400)) {
                $past_cutoff = true;
            } else {
                $past_cutoff = false;
            }
            if (COM_isAnonUser()) {
                // Just show a must-log-in message
                $T->set_var('login_to_register', 'true');
            } elseif (!$past_cutoff) {
                $num_free_tickets = $this->Repeat->isRegistered(0, true);
                $total_tickets = $this->Repeat->isRegistered(0, false);
                if ($num_free_tickets > 0) {
                    // If the user is already registered for any free tickets,
                    // show the cancel link
                    $T->set_var(array(
                        'unregister_link' => 'true',
                        'num_free_reg' => $num_free_tickets,
                    ) );
                }

                // Show the registration link
                if (
                    (
                        $Event->getOption('max_rsvp') == 0 ||
                        $Event->getOption('rsvp_waitlist') == 1 ||
                        $Event->getOption('max_rsvp') > $this->TotalRegistrations()
                    )
                    &&
                    (
                        $Event->getOption('max_user_rsvp') == 0 ||
                        $total_tickets < $Event->getOption('max_user_rsvp')
                    )
                ) {
                    if ($Event->getOption('rsvp_comments')) {
                        $T->set_var('rsvp_comments',  true);
                        $prompts = $Event->getOption('rsvp_cmt_prompts');
                        if (empty($prompts)) {
                            $prompts = array($LANG_EVLIST['comment']);
                        }
                        $T->set_block('event', 'rsvpComments', 'rsvpC');
                        foreach ($prompts as $prompt) {
                            $T->set_var('rsvp_cmt_prompt', $prompt);
                            $T->parse('rsvpC', 'rsvpComments', true);
                        }
                    }
                    $Ticks = TicketType::GetTicketTypes();
                    if (!empty($Ticks)) {
                        if ($Event->getOption('max_user_rsvp') > 0) {
                            $T->set_block('event', 'tickCntBlk', 'tcBlk');
                            $T->set_var('register_multi', true);
                            $avail_tickets = $Event->getOption('max_user_rsvp') - $total_tickets;
                            for ($i = 1; $i <= $avail_tickets; $i++) {
                                $T->set_var('tick_cnt', $i);
                                $T->parse('tcBlk', 'tickCntBlk', true);
                            }
                        } else {
                            $T->set_var('register_unltd', 'true');
                        }
                        $T->set_block('event', 'tickTypeBlk', 'tBlk');
                        foreach ($Event->getOption('tickets') as $tick_id=>$data) {
                            // Skip ticket types that may have been disabled
                            if (!array_key_exists($tick_id, $Ticks)) continue;
                            $status = LGLIB_invokeService(
                                'shop', 'formatAmount',
                                array('amount' => $data['fee']),
                                $pp_fmt_amt, $svc_msg
                            );
                            $fmt_amt = $status == PLG_RET_OK ?
                                    $pp_fmt_amt : COM_numberFormat($data['fee'], 2);
                            $T->set_var(array(
                                'tick_type' => $tick_id,
                                'tick_descr' => $Ticks[$tick_id]->getDscp(),
                                'tick_fee' => $data['fee'] > 0 ? $fmt_amt : 'FREE',
                            ) );
                            $T->parse('tBlk', 'tickTypeBlk', true);
                        }
                        $T->set_var(array(
                            'register_link' => 'true',
                            'ticket_types_multi' => count($Event->getOption('tickets')) > 1 ? 'true' : '',
                        ) );
                    }
                }
            }

            // Show the user signups on the event page if authorized.
            if (SEC_inGroup($Event->getOption('rsvp_view_grp'))) {
                $T->set_var('user_signups', Ticket::userList_RSVP($this->rp_id));
            }

            // If ticket printing is enabled for this event, see if the
            // current user has any tickets to print.
            if ($Event->getOption('rsvp_print') > 0) {
                $tickets = Ticket::GetTickets(
                    $this->ev_id,
                    $this->rp_id,
                    $Event->getOwnerID(),
                    $Event->getOption('rsvp_print') == 1 ? 'paid' : ''
                );
                if (count($tickets) > 0) {
                    $tick_url = EVLIST_URL . "/tickets.php?ev_id={$this->ev_id}&rp_id={$this->rp_id}";
                    $T->set_var(array(
                        'have_tickets'  => 'true',
                        'ticket_url' => COM_buildUrl($tick_url),
                        'tic_rp_id' => $Event->getOption('use_rsvp') == EV_RSVP_REPEAT ? $this->rp_id : 0,
                    ) );
                }
            }

        }   // if enable_rsvp

        if (!empty($date_start) || !empty($date_end)) {
            $T->parse('datetime_info', 'datetime');
        }

        // Get coordinates for easy use in Weather and Locator blocks
        $lat = $Detail->getLatitude();
        $lng = $Detail->getLongitude();

        // Only process the location block if at least one element exists.
        // Don't want an empty block showing.
        if (
            !empty($location) ||
            !empty($street) ||
            !empty($city) ||
            !empty($province) ||
            !empty($postal)
        ) {
            // Get info from the Weather plugin, if configured and available
            // There has to be at least some location data for this to work.
            if ($_EV_CONF['use_weather']) {
                // Try coordinates first, if present
                if (!empty($lat) && !empty($lng)) {
                    $loc = array(
                        'parts' => array(
                            'lat' => $lat,
                            'lng' => $lng,
                        ),
                    );
                } else {
                    // The postal code works best, but not internationally.
                    // Try the regular address first.
                    if (!empty($city) && !empty($province)) {
                        $loc = array(
                            'city' => $city,
                            'province' => $province,
                            'country' => $country,
                        );
                    }
                    if (!empty($postal)) {
                        $loc['postal'] = $postal;
                    }
                }
                $weather = '';

                if (!empty($loc)) {
                    // Location info was found, get the weather
                    $s = LGLIB_invokeService(
                        'weather', 'embed',
                        array(
                            'loc' => $loc,
                        ),
                        $weather, $svc_msg
                    );
                    if (!empty($weather)) {
                        // Weather info was found
                        $T->set_var('weather', $weather);
                    }
                }
            }
        }

        // Get a map from the Locator plugin, if configured and available
        if ($_EV_CONF['use_locator'] == 1 && !empty($lat) && !empty($lng)) {
            $args = array(
                'lat'   => $lat,
                'lng'   => $lng,
                'text'  => $Detail->formatAddress(),
            );
            if ($this->_tpl == 'event_print') {
                $status = LGLIB_invokeService('locator', 'getStaticMap', $args, $map, $svc_msg);
                if ($status == PLG_RET_OK) {
                    $T->set_var(array(
                        'map_url'   => $map['url'],
                        'map_type' => $map['type'],
                    ) );
                }
            } else {
                $status = LGLIB_invokeService('locator', 'getMap', $args, $map, $svc_msg);
                if ($status == PLG_RET_OK) {
                    $T->set_var(array(
                        'map'   => $map,
                        'lat'   => EVLIST_coord2str($lat),
                        'lng'   => EVLIST_coord2str($lng),
                    ) );
                }
            }
        }

        //put contact info here: contact, email, phone#
        $name = $Detail->getContact() != '' ?
            COM_applyFilter($Detail->getContact()) : '';
        if ($Detail->getEmail() != '') {
            $email = COM_applyFilter($Detail->getEmail());
            $email = EVLIST_obfuscate($email);
        } else {
            $email = '';
        }
        $phone = $Detail->getPhone() != '' ?
            COM_applyFilter($Detail->getPhone()) : '';

        if (!empty($name) || !empty($email) || !empty($phone)) {
            $T->set_var(array(
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
            ) );
            $T->parse('contact_info', 'contact');
        }

        // TODO: Is the range needed?
        if (!empty($range)) {
            $andrange = '&amp;range=' . $range;
        } else {
            $andrange = '&amp;range=2';
        }

        if (!empty($cat)) {
            $andcat = '&amp;cat=' . $cat;
        } else {
            $andcat = '';
        }

        $cats = $Event->getCategories();
        $catcount = count($cats);
        if ($catcount > 0) {
            $catlinks = array();
            for ($i = 0; $i < $catcount; $i++) {
                $catname = str_replace(' ', '&nbsp;', $cats[$i]['name']);
                $catlinks[] = '<a href="' .
                EVLIST_URL . '/index.php?view=list' . $andrange .
                '&cat=' . $cats[$i]['id'] .
                '">' . $catname . '</a>&nbsp;';
            }
            $catlink = implode(' | ', $catlinks);
            $T->set_var('category_link', $catlink, true);
        }

        //  reminders must be enabled globally first and then per event in
        //  order to be active
        if (!isset($_EV_CONF['reminder_days'])) {
            $_EV_CONF['reminder_days'] = 1;
        }

        $reminder_msg = '';
        if (
            !COM_isAnonUser() &&
            $_EV_CONF['enable_reminders'] == '1' &&
            $Event->remindersEnabled() &&
            time() < strtotime(
                "-".$_EV_CONF['reminder_days']." days",
                $this->Repeat->getDateStart1()->toUnix()
            )
        ) {
            //form will not appear within XX days of scheduled event.
            $show_reminders = true;

            // Let's see if we have already asked for a reminder...
            if ($_USER['uid'] > 1) {
                $Reminder = new Reminder($this->rp_id, $_USER['uid']);
                if (!$Reminder->isNew()) {
                    $reminder_msg = sprintf($LANG_EVLIST['you_are_subscribed'], $Reminder->getDays());
                }
            }
        } else {
            $show_reminders = false;
        }

        if ($Event->getOption('contactlink') && $Event->getOwnerID() > 1) {
            $ownerlink = $_CONF['site_url'] . '/profiles.php?uid=' .
                    $Event->getOwnerID();
            $ownerlink = sprintf($LANG_EVLIST['contact_us'], $ownerlink);
        } else {
            $ownerlink = '';
        }
        $T->set_var(array(
            'owner_link' => $ownerlink,
            'reminder_msg' => $reminder_msg,
            'reminder_email' => isset($_USER['email']) ? $_USER['email'] : '',
            'notice' => (int)$_EV_CONF['reminder_days'],
            'rp_id' => $this->rp_id,
            'eid' => $this->ev_id,
            'show_reminderform' => $show_reminders ? 'true' : '',
            'address_info' =>$Detail->formatAddress(),
        ) );

        $tick_types = TicketType::GetTicketTypes();
        $T->set_block('event', 'registerBlock', 'rBlock');
        if (is_array($Event->getOption('tickets'))) {
            foreach ($Event->getOption('tickets') as $tic_type=>$info) {
                // Skip ticket types that may have been disabled
                if (!array_key_exists($tic_type, $tick_types)) continue;
                $T->set_var(array(
                    'tic_description' => $tick_types[$tic_type]->getDscp(),
                    'tic_fee' => COM_numberFormat($info['fee'], 2),
                ) );
                $T->parse('rBlock', 'registerBlock', true);
            }
        }

        // Show the "manage reservations" link to the event owner
        if (
            $_EV_CONF['enable_rsvp'] == 1 &&
            $Event->getOption('use_rsvp') > 0
        ) {
            if ($this->_is_admin || $Event->isOwner()) {
                $T->set_var(array(
                    'admin_rsvp'    => Ticket::adminList_RSVP($this->rp_id),
                    'rsvp_count'    => $this->Repeat->TotalRegistrations(),
                ) );
            } elseif (SEC_inGroup($Event->getOption('rsvp_view_grp'))) {
                $T->set_var(array(
                    'admin_rsvp'    => Ticket::userList_RSVP($this->rp_id),
                    'rsvp_count'    => $this->Repeat->TotalRegistrations(),
                ) );
            }
        }

        $T->set_var('adblock', PLG_displayAdBlock('evlist_event', 0));
        $T->parse ('output','event');
        $retval .= $T->finish($T->get_var('output'));

        // Apply output filters
        $retval = PLG_outputFilter($retval);

        return $retval;
    }


    /**
     * Set the query string received to highlight words in the event.
     *
     * @param   string  $qs     Query string
     * @return  object  $this
     */
    public function withQuery(string $qs) : self
    {
        $this->qs = $qs;
        return $this;
    }


    /**
     * Set the override to use a different template for the event.
     * The parameter should be the part of the template name following `event_`.
     *
     * @param   string  $tpl    Templage name (partial), empty to reset
     * @return  object  $this
     */
    public function withTemplate(?string $tpl = '') : self
    {
        if ($tpl != '') {
            $this->_tpl .= '_' . $tpl;
        }
        return $this;
    }


    /**
     * Set the comment mode to use with the event display.
     *
     * @param   string  $cmtmode    Comment mode (nested, etc.)
     * @return  object  $this
     */
    public function withCommentMode(string $cmtmode) : self
    {
        $this->_cmtmode = $cmtmode;
        return $this;
    }


    /**
     * Set the comment ordering for event display.
     *
     * @param   string  $cmtorder   Comment order (ASC or DESC)
     * @return  object  $this
     */
    public function withCommentOrder(string $cmtorder) : self
    {
        $this->_cmtorder = $cmtorder;
        return $this;
    }


    /**
     * Set the referrer URL, to create the "back to calendar" button.
     *
     * @param   string  $url    Return URL. Could be null if direct access.
     * @return  object  $this
     */
    public function withReferer(?string $url) : self
    {
        $this->_referer = $url;
        return $this;
    }

}
