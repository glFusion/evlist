# evList plugin for glFusion - Changelog

## v1.5.0
Release TBD
- Retire support for non-UIkit themes.
- Clear category lookup cache when an event is updated.
- Provide location address to Locator plugin.
- Leverage list item template to correctly show event date and time in lists.
- Use calendar selector for plugin to calendar mapping config.
- Update service functions for compatibility with Paypal 0.6.0/Shop 1.0.0
- Add orderby field to calendars to control order in selection lists.
- Fix search results not excluding by calendar status or permissions.
- Consider calendar perms in Event::hasAccess().
- Request perms in `plugin_itemsaved`, calculate based on owner/group if not supplied.
- Remove support for Meetup.com API.
- Remove hit counter, was never used.
- Switch from Paypal plugin to Shop.
- Limit centerblock display to a number of days as well as events.
- Use layout-based mail templates for consistency.
- Enable signups with custom fields, may be used instead of the forms plugin.
- Add nofollow and noindex tags to subscription and print links/pages.
- Configurable key to hide repeating events from centerblock.
- Condense header, better for small screens.
- Fix iCal format for long descriptions.
- Use glFusion built-in datetime picker instead of UIkit version.
- Add option to hide centerblock from small screens.

## v1.4.5
Release 2018-04-14
- Allow `%site_url%` in event URL to reference the site base URL.
- Supports calls to `PLG_itemSaved()` to allow plugins to submit and update events.
- Added plugin to calendar mapping in the configuration (Integration section).
- Add multiple event deletion
- Add icons to calendars
- Add waitlist status to ticket table, update when tickets are removed
- Save calendar display checkbox changes in session variable
- Use the common colorpicker from LGLIB (requires LGLIB 1.0.6+)
- Integration with the Birthdays plugin
- Fix error when re-editing a new event due to an error
- E_ALL fixes
- Fix centerblock calendar
- Fix paging in list view
- Fix upcoming selection where end date has not passed
- Set current page in list page navigation
- Fix namespace for Date class instantiation in Recur.class.php
- Add social sharing buttons

## v1.4.4
Release 2017-10-02
- Fix missing namespace in calendar-format centerblock
- Add adblock tags to event view templates
- Fix function calls referencing the wrong plugin in upgrade.php

## v1.4.3
Release 2017-10-01
- Move ticket format to a configuration item under RSVP
- Show timezone in tooltips if not local
- Implement Evlist namespace and class autoloader
- Add overflow:auto to day blocks in monthly view
- Added a Reminder class to handle reminder tasks
- Require glFusion 1.6+
- Add css to format long tooltips
- Remove HTML calendar template, use AJAX only
- Use configured numeric separators in location fields
- Add timezone support for events
- Change Meetup.com group IDs to array
- Fix recurring multi-day events
- Add key fields to repeats to help searching by date/time
- Change AJAX functions to use Jquery
- Make the ticket number format configurable

## v1.4.2
Released 2017-01-21
- Fix calls to commentsupport function in another plugin

## v1.4.1
Released 2017-01-21
- Add comment support
- Moved ticket list to new tab on event page
- Add calendar view and replace home page to centerblock

## v1.4.0
- Add event signups
- Add uikit-based templates
- Add responsive month view calendar
- Add ticketing and rsvp
- Add meetup.com event integration

## v1.3.6
- Added AJAX calendar navigation

## v1.3.5
- Fixed owner_id and group_id values when importing from CSV
- Moved calendar popup javascript & css to separate plugin
- Fix to allow yearly events based on DOM, e.g. last Monday in May

## v1.3.2
- Added missing style & javascript for colorpicker.
- Changed version check to accomodate patch-level versions, and require exact
    matches

## v1.3.1
- Updated version number to fix error when calling COM_checkVersion() with
    patch-level releases (e.g. "1.3.0.1")
- Implemented needed changes to centerblock

## v1.3.0
- Fixed import bug when importing from glFusion calendar plugin
  start / end times for all day events.
- Save all event recurrences as discrete events.
    - Allows editing of single instances
    - Allows deletion of single or all future instances
    - Improves search performance
- Added multiple calendars and option to show/hide calendars (a la Google)
- Replaced old date dropdowns & datepicker with a faster and language-aware 
    version.
- Added new options for when events are no longer "upcoming" (Issue 648)
- Added admin notification option when events are submitted to queue.
- Made the event owner contact link optional. (Issue 691)
- Entry times now use 12 or 24-hour format based on global config (Issues 460, 530)
- Full date-time values are used to check if starting is later than ending.
    (Issue 704 revisited)
- Category admin now uses more standard glFusion interface.
- Added RSS subscription icons to calendar view.  Fixed date showing as 1970.
- Changes to upcoming events block
    - Multi-day events only show once
- Added notification to admin upon submission to queue. (Issue 302)
- New or changed configuration options:
-- Added the default view to show (month, week, etc.)
-- Added the max number of days to show in the Upcoming Events block.
-- Consolidated who may add events (Admin, Members, Anon) to a single variable.
-- Changed the date & time formats to values instead of database lookups.
- Added new "evlist.submit" feature to bypass the submission queue.
- Added integration with the Locator and Weather plugins.  If these are 
    installed, map and weather information can be displayed with the event 
    detail.

## v1.2.6
- Implemented support for $_CONF['loginrequired']
- Implemented new glFusion admin authentication.
- Implemented additional plugin calls for improved integration with
  other plugins.
- Configuration option to control which navigation blocks display.
- Added daily, weekly, monthly and yearly calendar views.
- Added a monthly calendar block.

## v1.2.5
- New Dutch translation
- Fix layout issue when categories are enabled

## v1.2.4
- Properly filter search by date ranges

## v1.2.3
- Fixed recurring issue with items like 3rd tuesday of the month
- Fixed several E_ALL warnings.

## v1.2.2
- Fixed issue where the body of the message did not appear when emailing
  of a new post (Mark)

## v1.2.1
- Fixed issue where searching for & (or other special chars) would fail.
        private/plugins/evlist/functions.inc
- Implemented support for glFusion 1.1.2 auto plugin install
- Fixed several permission issues to allow evList Admin group to actually
  moderate events.
- Implemented ability to mail the evList Admin group on new submissions

## v1.2.0
- Small HTML modifications
- Added option to remove / add subscription
- Fixed bug where configured permissions were not used on new events
- Added configuration option for number of days prior to an event
  a reminder can be set.
- Added option to allow users email to be the default entry for a reminder
  form.
- Added configuration option for Enable Reminders to default install
- Added option to import Calendar events.
- Fixed issue where we referred to the wrong image file, reported by LeeG.
- Fixed problem where author not shown in search results
