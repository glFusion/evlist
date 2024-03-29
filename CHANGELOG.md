# evList plugin for glFusion - Changelog

## 1.5.8
Release TBD
- Fix layout for Agenda view
- Allow different displayblock settings for list vs. calendar views.
- Add `DateFunc::cal_days_in_month` in case PHP doesn't have calendar support.
- Use Uikit grid for monthly calendar, auto-scale for number of events. (#47)

## 1.5.7
Release 2022-07-30
- Fix adding tooltip class to icons with no title.
- Include event title in page title attribute.
- Use the glFusion system log level.
- Trim icon code to avoid extra spaces within links.
- Fix weekly view when week starts on Monday (v1.5.7.1).
- Activate popup date switcher after AJAX page changes (v1.5.7.2).

## 1.5.6
Release 2022-07-19
- Fix determing beginning of week day when week starts on Monday.
- Fix month starting date where start is Sunday and first of week is Monday (#44).
- Implement popup calendar date switcher.
- Allow changing URL when editing current or future instances.

## 1.5.5
Release 2022-03-07
- Fix call to core config class when upgrading.

## 1.5.4
Release 2022-02-27
- Leave advanced editor tags alone in story-style centerblock.
- Template/CSS fix for event element alignment.
- Fix search and pagination on MyEvents page. (#39)
- Add a flag to show or hide calendars from centerblocks.
- Make EventSet more efficient, don't get all fields. (#37)
- Check permission when viewing an event directly. (#40)
- Fix bug when updating a single instance of a recurring event. (#41)
- Fix invalid members and anon permissions, assume none.
- Better display of calendar checkboxes with light or dark themes.
- Reduce use of caching where it doesn't improve performance.
- Fix saving birthdays to correct calendar.
- Regular users can set permissions and signups when editing events.
- Event ownership not required to clone an event.
- Add config var to enable event submission queue (was using story config).
- Add queue bypass and overall view privileges.
- Add topic-style icons for calendars, for story centerblocks.
- Fixes to date calculations, use PHP Date type.
- Fix agenda display of Calendar and Detail information.

## 1.5.3
Release 2021-12-16
- Add read-more icon to story centerblock items.
- Fix check whether comments are enabled.

## v1.5.2
Release 2021-12-11
- Add missing `show_upcoming` field to clendar table during upgrade.
- Fix extra comma in function calls.

## v1.5.0
Release 2021-12-10
- Retire support for non-UIkit themes.
- Clear category lookup cache when an event is updated.
- Provide location address to Locator plugin.
- Leverage list item template to correctly show event date and time in lists.
- Update service functions for compatibility with Paypal 0.6.0/Shop 1.0.0.
- Switch from Paypal plugin to Shop.
- Add orderby field to calendars and ticket types to control order in selection lists.
- Fix search results not excluding by calendar status or permissions.
- Consider calendar perms in Event::hasAccess().
- Request perms in `plugin_itemsaved`, calculate based on owner/group if not supplied.
- Remove support for Meetup.com API.
- Remove hit counter, was never used.
- Limit centerblock display to a number of days as well as events.
- Use layout-based mail templates for consistency.
- Enable signups with custom fields, may be used instead of the forms plugin.
- Add nofollow and noindex tags to subscription and print links/pages.
- Condense header, better for small screens.
- Use glFusion built-in datetime picker instead of UIkit version.
- Ical improvements:
  - Fix format for long descriptions.
  - Event sequences and statuses to track event changes.
  - Cancel instead of deleting events and occurrences.
  - Publish feeds as .ics files via Content Syndication model.
- Smarter recreation of event occurrences when event is updated.
- Configuration options:
  - Add option to hide centerblock from small screens.
  - Add option to show or hide QRCodes on printed tickets.
  - Configurable key used to hide repeating events from centerblock.
  - Use calendar selector for plugin to calendar mapping config.
    Previously reseerved calendars (Birthdays and Meetup Events) may now be deleted.
- Include event pass tickets in per-instance signup lists.
- Enable advanced editor for event summary. (#38)
- Improve Story-style centerblock

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
