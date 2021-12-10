# evlist
Event List and calendar plugin for glFusion
Version: 1.5.0

For the latest documentation, please see

    http://www.glfusion.org/wiki/doku.php?id=evlist:start

## LICENSE
This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

## OVERVIEW
A calendar solution for glFusion. evList supports recurring events, 
categories, and more.

## SYSTEM REQUIREMENTS
evList has the following system requirements:

    * PHP 7.3 and higher.
    * glFusion v1.7.9 or newer
    * lgLib plugin v1.0.10 or newer
    * Shop Plugin 1.0.0 or higher, if used.

evList makes use of the lgLib plugin to handle messages and PDF creation.

## INSTALLATION
The evList  Plugin uses the glFusion automated plugin installer.
Simply upload the distribution using the glFusion plugin installer located in
the Plugin Administration page.

## UPGRADING
The upgrade process is identical to the installation process, simply upload
the distribution from the Plugin Administration page.

## CONFIGURATION SETTINGS
* Allow anonymous users to view events?
  * Set this to TRUE to allow non-logged in users to view events.
  * Set to FALSE to require that users log in to see events.

* Allow logged in user submissions?
  * Set to TRUE to allow normal, logged-in users to submit events. All events
from logged-in users will go into the submission queue.
  * Set to FALSE to disable event submission for logged-in users.

* Allow HTML when posting?
  * Set to TRUE to allow HTML use in the event description and the event
    summaries. ALL HTML will be filtered through the glFusion HTML filtering
    engine.
  * Set to FALSE to disable the use of HTML.

* Enable Categories
  * Set to TRUE to enable category support.

* Reminder Speedlimit
  * How often, in seconds, you can select to be reminded of an event.

* Posting Speedlimit
  * How often, in seconds, you can post a new event.

* Enable email reminders?
  * Select whether email event reminders will be enabled globally.
  * Reminders can still be disabled for a given event.

* Number of days prior to an event to allow reminders
  * Enter the minimum number of days before an event for someone to
enter their email address for a reminder. Default = 1.

### GUI SETTINGS

* Enable the menu item
  * Set this to TRUE to enable a link for evList to be placed in the User Menu.
  * See User menu link option for more options.

* User menu link option
  * Select if the User Menu link is "Add Event" or "List Events"

* An event ceases to be upcoming... Select when an event falls off the 'Upcoming' list:
  * as soon as the start date has passed, i.e. the next day
  * as soon as the start time has passed
  * as soon as the end time has passed
  * as soon as the end date has passed, i.e. the next day

* Number of events to display per page.

* Default View
  * Select the view that is shown on the "home page" for the plugin

### CENTERBLOCK SETTINGS
* Centerblock Type. Select the type of centerblock to use, if any.
  * "disabled" - no centerblock is shown
  * "table" - a table of events is shown
  * "story" - upcoming events are shown as stories
  * "calendar" - embeds a monthly calendar in the page

* Centerblock Position

* Topic
  * In which topic should the centerblock be displayed

* Select an event range to display

* Number of events to display

* Number of characters to display in event summary
  * Number of characters (width) of the centerblock.

### Integrations
* Use Locator - Evlist can display maps from the Locator plugin on the event
detail page. Select "True" to enable this feature (must also have the Locator
plugin installed).

* Use Weather - To have weather information displayed on the event detail page
set this to "True". The Weather plugin must also be installed and configured.

# QUIRKS AND ISSUES
* The selected starting date for a repeating event is always used, even if it
would normally not be included.  For example, creating an event to occur
every third Tuesday, but selecting a Monday as the start date, causes the
event to occur on that Monday as well as the following Tuesdays.
* If ticket printing is used, and the event repeat occurrences are changed after
tickets have been created, the tickets will be orphaned and can't be printed.
