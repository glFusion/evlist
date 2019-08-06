<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | english_utf-8.php                                                        |
// |                                                                          |
// | English language file for evList                                         |
// +--------------------------------------------------------------------------+
// | Based on the evList Plugin for Geeklog CMS                               |
// | Copyright (C) 2007 by the following authors:                             |
// |                                                                          |
// | Authors: Alford Deeley     - ajdeeley AT summitpages.ca                  |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+
/**
*   English language file for the evList plugin
*   @package    evlist
*/
// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}
global $_EV_CONF;
global $_CONF;

$LANG_EVLIST = array(
'pi_title'          => 'Event Calendar',
'moderation_title'  => 'Event Submissions',
'del_future'        => 'Delete this and future instances',
'conf_del_future'   => 'Are you sure you want to delete all future instances of this event?',
'edit_future'       => 'Edit this and future instances',
'del_all'           => 'Delete this event',
'conf_del_all'      => 'Are you sure you want to delete all occurences of this event?',
'del_repeat'        => 'Delete this occurence',
'conf_del_repeat'   => 'Are you sure you want to delete this occurence?',
'conf_del_event'    => 'Are you sure you want to delete this event?',
'conf_del_item'     => 'Are you sure you want to delete this item?',
'edit_repeat'       => 'Edit this instance',
'edit_event'        => 'Edit Event',
'add_event'         => 'Add Event',
'edit_calendar'     => 'Edit Calendar',
'err_missing_title' => 'A title is required.',
'err_missing_weekdays' => 'You must specify at least one day for a day-of-week recurrence.',
'err_times'         => 'The ending time cannot be before the starting time.',
'err_db_saving'     => 'A database error occurred while saving your record.',
'err_cal_import'    => 'There were %d errors importing from the calendar. Check your error log for details',
'err_import_event'  => 'Error importing event %s',
'err_cal_notavail'  => 'The calendar plugin data is not available.',
'err_upd_repeats'   => 'Error updating repeat events',
'info'              => 'Information',
'warning'           => 'Warning',
'alert'             => 'Alert',
'editing_instance'  => 'You are editing a single instance of this event.',
'editing_future'    => 'Your are editing all future instances of this event.',
'editing_series'    => 'You are editing the event series.  Any customizations made to specific instances will be lost!',
'allday'            => 'All Day',
'recur_cust_format' => '(Format: YYYY-MM-DD, YYYY-MM-DD, etc.).',
'recur_cust_ignoredates' => '(Ignores the start and end dates given above.)',
'click_to_select'   => 'Click to select',
'access_denied'     => 'Access Denied',
'skip_weekends'     => 'Skip weekends?',
'yes'               => 'Yes',
'no'                => 'No',
'next_bus_day'      => 'Next business day',
'edit'              => 'Edit',
'event_title'       => 'Event Title',
'event_summary'     => 'Event Summary',
'start_date'        => 'Start Date',
'start_time'        => 'Start Time',
'end_time'          => 'End Time',
'end_date'          => 'End Date',
'copy'              => 'Copy',
'id'                => 'ID',
'enabled'           => 'Enabled',
'enabled_q'         => 'Enabled?',
'ical_enabled'      => 'ICal Enabled',
'calendar'          => 'Calendar',
'calendars'         => 'Calendars',
'select_cals'       => 'Select which calendars will be displayed',
'new_calendar'      => 'New Calendar',
'events'            => 'Events',
'new_event'         => 'New Event',
'categories'        => 'Categories',
'category'          => 'Category',
'new_category'      => 'New Category',
'sel_category'      => 'Select Categories',
'ticket_types'      => 'Ticket Types',
'type'              => 'Type',
'fee'               => 'Fee',
'new_ticket_type'   => 'New Ticket Type',
'print_tickets'     => 'Print Tickets',
'required'          => 'Required',
'import'            => 'Import',
'import_calendar'   => 'Import from Calendar',
'import_from_csv'   => 'Import from CSV',
'title'             => 'Title',
'ev_info'           => 'Event Information',
'ev_schedule'       => 'Schedule',
'ev_perms'          => 'Permissions',
'ev_contact'        => 'Contact',
'ev_location'       => 'Location',
'show_upcoming'     => 'Show in Upcoming Events Block',
'misc'              => 'Miscellaneous',
'foreground'        => 'Foreground',
'background'        => 'Background',
'colors'            => 'Colors',
'cal_name'          => 'Calendar Name',
'cat_name'          => 'Category Name',
'reset'             => 'Reset Form',
'del_cal_msg1'      => 'You are about to delete a calendar.  This is a permanent deletion and cannot be reversed.  Be sure that this is what you want to do before you click "Submit" below!
You may either move existing events to a new calendar, or delete those events.',
'del_cal_events'    => 'This calendar has %d events associated with it.  You may move these events to another calendar by selecting one below.  If you do not select a new calendar for the events, they will ALL be permanently deleted from the database.',
'confirm_del'       => 'Confirm that you want to delete the item',
'none_delete'       => 'None- delete the events',
'deleting_cal'      => 'Deleting Calendar',

'rec_formats'       => array (
    1   => 'Daily by date, e.g., April 4 thru April 7 (sequential)',
    2   => 'Monthly by date (the same dates each month)',
    3   => 'Yearly by date, e.g., December 25th year after year',
    4   => 'Weekly by day, e.g., Mon, Wed, and Fri',
    5   => 'Monthly by day, e.g., the 3rd Friday of each month',
    6   => 'Custom dates: a comma-delimited list of calendar dates',
    ),

'rec_periods'       => array(
    0 => '',
    1 => 'day(s)',
    2 => 'month(s)',
    3 => 'year(s)',
    4 => 'week(s)',
    5 => 'month(s)',
    6 => '',
    ),

'postmodes' => array (
    'plaintext' => 'plaintext',
    'html'      => 'html',
    ),

'rec_intervals'     => array (
    1   => 'First',
    2   => 'Second',
    3   => 'Third',
    4   => 'Fourth',
    5   => 'Last',
    ),

'ranges'            => array (
    1   => 'Past',
    2   => 'Upcoming',
    3   => 'This Week',
    4   => 'This Month',
    ),

'periods'           => array(
    'day'   => 'Day',
    'week'  => 'Week',
    'month' => 'Month',
    'year'  => 'Year',
    'list'  => 'List',
    ),

'filter'            => 'Filter',
'when'              => 'When',
'where'             => 'Where',
'what'              => 'What',
'click_here'        => '<a href="%s" %s>Click Here</a> for more information',
'clk_help'         => 'Click for help',
'more_info'         => 'More Information',
'contact_us'        => 'Please <a href="%s">contact us</a> for more information.',
'rem_subject' => "An event reminder from {$_CONF['site_name']}",
'rem_msg1'  => "You are receiving this event reminder because your address was submitted to {$_CONF['site_name']}.",
'rem_msg2'  => 'This is a one-time message. You will not receive another message unless you subscribe to other events.',
'rem_url'   => 'For more information please visit %s',
'you_are_subscribed' => 'You are subscribed to this event.',
'topic_all'         => 'All',
'topic_home'        => 'Homepage only',
'recur_desc'        => array(
    1   => 'Occurs every day',
    2   => 'Occurs on the same date each month',
    3   => 'Occurs on the same date each year',
    4   => 'Occurs every %interval% week on %day%',
    5   => 'Occurs %interval% month on the %daynum% %day%',
    6   => '',      // custom dates
    ),
'on_days'   => 'on %s',
'on_the_days' => 'on the %s',
'each'      => 'each',
'every_num'  => 'every %d',
'recur_stop_desc' => ' until %s',
'recur_freq_txt' => 'Occurs every',
'interval_label' => 'Specify the interval and day on which this event will recur',
'interval_note' => 'The first occurance will be on the date specified above.',
'all_calendars' => 'All Calendars',
'all_categories' => 'All Categories',
'update_cats'   => 'Update Categories',
'notify_submission' => "A new event has been submitted to {$_CONF['site_name']}.  It can be approved or deleted at {$_CONF['site_admin_url']}/moderation.php.",
'submitted_by' => 'Submitted By',
'notify_subject' => 'Event Notification from ' . $_CONF['site_name'],
'show_contactlink' => 'Show link to contact email',
'no_match'  => 'There are no events that match your criteria.',
'event_begins' => 'This event begins',
'read_more' => 'Read More',
'quick_del' => 'Quick Delete',
'gen_evt_info' => 'General Event Information',
'full_desc' => 'Full Description',
'postmode' => 'Postmode',
'post_html_note1' => 'NOTE: The <i>Event Location</i> field below also accepts html.',
'enable_reminders_q' => 'Email reminders?',
'disable_reminders_note' => 'NOTE: Disabling reminders will delete all stored reminders.',
'submit_email_note' => 'Submit your email address in order to be reminded of this event prior to its occurrence.',
'add_to_cats' => 'Add your event to a single or to multiple categories',
'cats_not_req' => 'Adding your event to a category is not required.',
'cat_note1' => 'Create a new category for your event instead of, or in addition to, any existing categories.',
'url' => 'URL',
'street_address' => 'Street Address',
'city' => 'City',
'state' => 'Province/State',
'country' => 'Country',
'zip' => 'Postal/Zip Code',
'for_more_info' => 'For more information contact',
'email' => 'E-mail',
'phone' => 'Phone #',
'perms_desc' => 'Permissions: (R = read, E = edit, edit rights assume read rights)',
'date_time_info' => 'Date and Time Information',
'split_q' => 'Split?',
'rec_event_info' => 'Recurring Event Information',
'rec_event_q' => 'Is this a recurring event?',
'event_recurs' => 'Event recurs',
'select_format' => 'Select Format',
'jump_today' => 'Jump to Today',
'day_view' => 'Daily View',
'week_view' => 'Weekly View',
'month_view' => 'Monthly View',
'year_view' => 'Yearly View',
'list_view' => 'List View',
'select_range' => 'Select an event range to display',
'or_choose_cat' => 'and/or choose a category',
'go' => 'Go',
'days_prior' => 'days prior to this event.',
'email_private' => 'Your email will remain private and will only be used to remind you of this event.',
'messages' => array(
    1   => 'Success!  Event has been deleted.',
    2   => 'Success!  Your event has been saved.',
    3   => 'Event has been copied.  You may now edit your new event.',
    4   => 'Success!  Event has been updated.',
    5   => 'Required fields are empty.  Please recheck your submission.',
    6   => 'Alert!',
    7   => 'evList default settings have been applied.',
    8   => 'evList configuration settings have been updated.',
    9   => "Thank-you for submitting your event to {$_CONF['site_name']}. It has been submitted to our staff for approval. If approved, your event will be available for others to read on our site.",
    10  => 'Supplied dates are not valid.  Please recheck your submission.',
    11  => 'Categories have been updated.',
    12  => 'Reminder saved.  You should receive an email reminder prior to this event.',
    13  => 'You have supplied an invalid or improperly formatted email address.  Please try again.',
    14  => 'You must specify the number of days prior to this event that you wish to be notified.',
    15  => "This site requires at least {$_EV_CONF['reminder_speedlimit']} seconds between reminder requests.",
    16  => "This site requires at least {$_EV_CONF['post_speedlimit']} seconds between event submissions.",
    17  => 'The glFusion calendar events have been imported',
    18  => 'Successfully removed reminder notification',
    19  => 'One or more errors occured during the calendar import.  Check the error log for details.',
    20  => 'This event doesn\'t allow registrations, or you do not have access to it.',
    21  => 'You\'re already signed up for this event.',
    22  => 'This event is full.',
    23  => 'There was an error processing your request.',
    24  => 'You have been registered for this event.',
    25  => 'Your registration has been cancelled.',
    26  => 'Payment is required, click <a href="%s">here</a> to check out.',
    27  => '%s of your tickets have been added to the waiting list.',
    28  => 'You have %d tickets remaining.',
    50  => 'Not Paid',
    51  => 'Already Used',
),
'admin_instr' => array(
    'categories' => 'Deleting categories <strong>will not</strong> delete events belonging to those categories.<br />Disabling a category <strong>will not</strong> disable its events.  Those events will continue to appear in the event list.',
    'calendars' => 'All events must be associated with a calendar.<br />Disabling a calendar prevents its events from being displayed. Deleting a calendar requires that events belonging to it be moved to another calendar.<br />Calendar number 1 cannot be deleted, but may be disabled.',
    'events' => 'To create a new event, click on "New Event" above.<br />To modify or delete an event, click on that event\'s edit icon below. To enable/disable an event, check the appropriate box below.',
    'tickettypes' => 'Tickets can be created for free or paid admission, and to cover one event occurrence or all occurrences (event pass). Tickets are only used if the global &quot;Enable RSVP&quot; setting is enabled.<br />Ticket Types can only be deleted if they haven&apos;t been used for any events.',
),

'current_events'  => 'Current Events',
'past_events' => 'Past Events',
'upcoming_events' => 'Upcoming Events',
'this_week_events' => 'This Week\'s Events',
'this_month_events' => 'This Month\'s Events',
'hits'          => 'Hits',
'top_ten'       => 'Top Ten evList Events',
'no_events_viewable' => 'No events in the system are currently viewable.',
'date'          => 'Date',
'time'          => 'Time',
'all_upcoming'  => 'All Upcoming Events',
'subscribe_to'  => 'Subscribe to',
'subscribe'     => 'Subscribe',
'event_editor'  => 'Event Editor',
'datestart_note' => "* Starting year and month are required fields.",
'custom_label'  => 'Specify the %s on which this event will recur%s',
'stop_label'    => 'Specify the %s beyond which this event will not recur%s',
'if_any'        => ', if any',
'day_by_date'   => 'day, by date,',
'year_and_month' => 'year and month',
'year'          => 'year',
'days_of_week'  => 'days of the week',
'date_l'        => 'date',
'dates'         => 'Dates',
'all_day_event' => 'This is an all day event.',
'more_from_cat' => 'More events from:',
'access_denied_msg' => 'Only Authorized Users have Access to this Page.  Your user name and IP have been recorded.',
'coordinates'   => 'Coordinates',
'latitude'      => 'Latitude',
'longitude'     => 'Longitude',
'instr_coords'  => 'If zero or empty, the coordinates will be filled in automatically from the address information, if possible.',
'select_location' => 'Select Location',
'instr_sel_loc' => 'Select a location from the list, or fill in the details.',
'use_rsvp'       => 'Enable signups?',
'max_rsvp'       => 'Max. Attendees',
'max_user_rsvp' => 'Max. Registrations per User',
'signup'        => 'Register for this event',
'cancelreg'     => 'Cancel your registration',
'rsvp_none'     => 'Signups Disabled',
'rsvp_event'    => 'Allow signups for the event',
'rsvp_repeat'   => 'Allow signups for each occurrence',
'rsvp_mindays'  => 'Min. days to RSVP',
'admin_rsvp'    => 'Manage RSVP\'s',
'rsvp_date'      => 'Registration Date',
'registration'  => 'Registration',
'rsvp_waitlist' => 'Accept waitlisted reservations?',
'rsvp_cutoff'   => 'RSVP Cutoff (days)',
'sel_monthdays' => 'Select the days each month when the event will occur',
'sub_this_instance' => 'This Instance',
'sub_all_instances' => 'All occurrences',
'description'   => 'Description',
'event_pass'    => 'Event Pass',
'cancel_free'   => 'Free registrations can be cancelled here if you will not be attending.',
'free_rsvp'     => 'Free Registrations',
'ticket_num'    => 'Ticket Number',
'date_used'     => 'Date Used',
'paid'          => 'Paid',
'login_to_register' => 'You need to log into the site to register for this event',
'conf_reset'    => 'Are your sure you want to reset this item?',
'reset_usage'   => 'Reset Usage',
'export_list'   => 'Export List',
'waitlisted'    => 'Waitlisted',
'name'          => 'Name',
'quantity'      => 'Quantity',
'all'           => 'All',
'click_for_datepicker' => 'Click for Date Selector',
'paid_only'     => 'Paid Only',
'paid_or_unpaid'    => 'Paid or Unpaid',
'register'      => 'Register',
'allow_ticket_printing' => 'Allow Ticket Printing',
'enable_comments' => 'Enable Comments?',
'closed'        => 'Closed',
'event'         => 'Event',
'timezone'      => 'Timezone',
'tz_local'      => 'Guest&apos;s local timezone',
'tz_select'     => 'Select Timezone',
'msg_item_updated' => 'Item has been updated',
'msg_item_nochange' => 'Item was not changed',
'print'         => 'Print',
'balance_due'   => 'Balance Due',
'instr_import_cal' => 'Import calendar events from the glFusion Calendar plugin into Evlist. This function should normally be used only once, but events with the same event ID are not imported to guard against duplicates.',
'sample'        => 'Sample',
'icon'          => 'Icon',
'inherit'               => 'Inherit',
'orderby'       => 'Order',
'show_after'    => 'Show After',
'first'         => 'First',
'ev_not_found'  => 'The requested event was not found.',
'jump'          => 'Jump',
);

$LANG_EVLIST_HELP = array(
'calendar' => 'Select the calendar where this event will appear. Calendars can be included or excluded from views and feeds.',
'ev_title' => 'Enter the title for this event. This text will appear in most calendars as a hover link to the event summary.',
'ev_summary' => 'Enter a fairly short description of the event. This will appear on the event display, and also when a user hovers the mouse over the event title in calendar views.',
'ev_dscp' => 'Enter an optional detailed description of this event. This text appears only on the event detail page.',
'ev_url' => 'Enter an optional URL for the event, such as a link to a site article or an external web page. You may use <b>%site_url%</b> as a placeholder for the site URL.',
'ev_enabled' => 'Check this box to enable the event. Events can be temporarily hidden from view without deleting them.',
'ena_reminders' => 'If this box is checked, users can enter an email address to have a reminder sent to them a number of days before the event.',
'sel_categories' => 'Categories are optional and a way to relate events together. Select one or more existing categories by checking their checkboxes, or create a new category by entering some text in the provided field.',
'split' => 'Check this box if the event is split into two times each day, e.g. 9:00am - 11:00am and 1:00pm - 4:00pm. If this box is checked, additional fields will appear where you can enter the starting and ending times for the second session.',
'startdt' => 'Enter the start date either by entering the text as a SQL-formatted date (YYYY-MM-DD), or by clicking the calendar icon and browsing to the date. Select the starting time by using the dropdown selections.',
'enddt' => 'Enter the ending date either by entering the text as a SQL-formatted date (YYYY-MM-DD), or by clicking the calendar icon and browsing to the date. Select the ending time by using the dropdown selections.',
'timezone' => 'If the &quot;local timezone&quot; checkbox is unchecked, you can select a timezone for this event which will be displayed next to the date/time information. If the local timezone is checked then no timezone will be shown; this is the same as previous versions of evList.',
'cal_name' => 'Enter a name for this calendar. Names should be unique.',
'cal_colors' => 'Select the foreground and background colors used to display events for this calendar within calendar views. Check the &quot;Inherit&quot; checkbox to have the color inherited from the parent elements.',
'cal_icon' => 'Enter the name of a UIKit icon to be shown with events in this calendar. Enter only the icon name, e.g. &quot;circle&quot;',
'cal_enabled' => 'Check to enable this calendar. Disabled calendars will not show in views nor in the event submittion form.',
'cal_ical_ena' => 'Check to allow ICal subscriptions to this calendar.',
'owner' => 'Select the owner for this item.',
'group' => 'Select the group associated with this item',
'perms' => 'Set the permissions for this item.',

'event_pass' => 'Checked if this ticket type is a full event pass.',
'del_hdr1' => 'Some items are reserved for system use and cannot be deleted.',
);

$PLG_evlist_MESSAGE1 = 'This event doesn\'t allow registrations, or you do not have access to it.';
$PLG_evlist_MESSAGE2 = 'You\'re already signed up for this event.';
$PLG_evlist_MESSAGE3 = 'This event is full.';
$PLG_evlist_MESSAGE4 = 'There was an error processing your request.';

// Localization of the Admin Configuration UI
$LANG_configsections['evlist'] = array(
    'label'                 => 'evList',
    'title'                 => 'evList Configuration'
);
$LANG_confignames['evlist'] = array(
    'allow_anon_view'       => 'Allow anonymous users to view events?',
    'allow_anon_add'        => 'Allow anonymous submissions?',
    'allow_user_add'        => 'Allow logged in user submissions?',
    'allow_html'            => 'Allow html when posting?',
    'can_add'               => 'Users allowed to add events',
    'usermenu_option'       => 'User menu link option',
    'enable_menuitem'       => 'Enable the menu item?',
    'week_begins'           => 'Week begins on',
    'date_format'           => 'Date format',
    'time_format'           => 'Time format',
    'enable_categories'     => 'Enable Categories',
    'enable_centerblock'    => 'Centerblock Type',
    'pos_centerblock'       => 'Centerblock position',
    'topic_centerblock'     => 'Topic',
    'range_centerblock'     => 'Select an event range to display',
    'limit_centerblock'     => 'Enter the number of events to display',
    'limit_list'            => 'Main list: number of events to display per page',
    'limit_block'           => 'Upcoming events block: number of events to display',
    'limit_summary'         => 'Number of characters to display in event summary',
    'enable_reminders'      => 'Enable email reminders?',
    'event_passing'         => 'An event ceases to be <i>upcoming</i>',
    'default_permissions'   => 'Default Permissions (owner,group,members,anon)',
    'reminder_speedlimit'   => 'Reminder speedlimit',
    'post_speedlimit'       => 'Posting Speedlimit',
    'reminder_days'         => 'Number of days prior to an event to allow reminders',
    'displayblocks'         => 'Display glFusion Blocks',
    'default_view'          => 'Default View',
    'max_upcoming_days'     => 'Max. Upcoming days to show in list',
    'use_locator'           => 'Integrate with the Locator plugin?',
    'use_weather'           => 'Integrate with the Weather plugin?',
    'enable_rsvp'           => 'Enable Registration/Ticketing?',
    'rsvp_print'            => 'Enable Ticket Printing?',
    'meetup_key'            => 'Meetup API Key',
    'meetup_gid'            => 'Meetup Group ID(s)',
    'meetup_cache_minutes'  => 'Cache Minutes',
    'meetup_enabled'        => 'Enable Meetup.com integration?',
    'commentsupport'        => 'Enable Comments?',
    'ticket_format'         => 'Ticket Format String',
    'pi_cal_map'            => 'Plugin-Calendar Mapping',
);
$LANG_configsubgroups['evlist'] = array(
    'sg_main'               => 'Main Settings',
    'sg_rsvp'               => 'RSVP/Ticketing',
    'sg_integ'              => 'Integrations',
);
$LANG_fs['evlist'] = array(
    'ev_main'               => 'General Settings',
    'ev_gui'                => 'GUI Settings',
    'ev_centerblock'        => 'Centerblock Settings',
    'ev_permissions'        => 'Default Permissions',
    'ev_rsvp'               => 'Registration and Ticketing',
    'ev_integ_meetup'       => 'Meetup.Com',
    'ev_integ_other'        => 'Other',
);
$LANG_configselects['evlist'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('True' => TRUE, 'False' => FALSE),
    2 => array('None' => 0, 'Add Event' => 1, 'List Events' => 2),
    3 => array('Sunday' => 1, 'Monday' => 2),
    4 => array(
            'Thu Nov 20, 2008'      => '%a %b %d, %Y',
            'Thu Nov 20'            => '%a %b %d',
            'Thursday Nov 20, 2008' => '%A %b %d, %Y',
            'Thursday Nov 20'       => '%A %b %d',
            'Thursday November 20'  => '%A %B %d',
            'November 20, 2008'     => '%B %d, %Y',
            '11/20/08'              => '%m/%d/%y',
            '11-20-08'              => '%m-%d-%y',
            '2008 11 20'            => '%Y %m %d',
            'Nov 20 2008'           => '%b %d %Y',
            'Nov 20, 2008'          => '%b %d, %Y',
    ),
    5 => array('02:38 PM' => '%I:%M %p', '14:48' => '%H:%M',),
    //4 => array('Thu Nov 20, 2008' => 1,'Thu Nov 20' => 2, 'Thursday Nov 20, 2008' => 3,'Thursday Nov 20' => 4, 'Thursday November 20' => 5, 'November 20, 2008' => 6,'11/20/08' => 7, '11-20-08' => 8,'2008 11 20'=>9,'Nov 20 2008' => 10,'Nov 20, 2008' => 11),
    //5 => array('02:38 PM' => 1,'14:48' => 2),
    6 => array(
            'as soon as the start time has passed (if exists)' => 1,
            'as soon as the start date has passed, ie, the next day.' => 2,
            'as soon as the end time has passed (if exists).' => 3,
            'as soon as the end date has passed.' => 4,
        ),
    7 => array('Top of page'=>1,'After featured story'=>2,'Bottom of page'=>3,'Entire page'=>0),
    8 => array('past'=>1,'upcoming'=>2,'this week'=>3,'this month'=>4),
    9 => array('Disabled' => 0, 'Table' => 1, 'Story' => 2, 'Calendar' => 3),
    12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3),
    13 => array('Left Blocks' => 0, 'Right Blocks' => 1, 'Left & Right Blocks' => 2, 'None' => 3),
    14 => array('Day' => 'day', 'Week' => 'week', 'Month' => 'month', 'Year' => 'year', 'List' => 'list'),
    15 => array('Admins Only' => 0, 'Logged-In Users' => 1, 'Logged-In+Anon Users' => 2),
    16 => array('HTML' => 'html', 'JSON' => 'json'),
    17 => array('No' => 0, 'Default No' => 1, 'Default Paid Only' => 2,
                'Default Paid or Unpaid' => 3),
);

?>
