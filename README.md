#evlist
======

Event List and calendar plugin for glFusion
Version: 1.3.0

For the latest documentation, please see

	http://www.glfusion.org/wiki/doku.php?id=evlist:start

#LICENSE

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

#OVERVIEW

A calendar solution for glFusion. evList supports recurring events, 
categories, and more.

#SYSTEM REQUIREMENTS

evList has the following system requirements:

    * PHP 5.3 and higher.
    * glFusion v1.4.0 or newer
    * lgLib plugin

evList makes use of the lgLib plugin to handle messages. To ensure that 
messages are displayed, edit your theme header.thtml file and add
    {lglib_messages}
where the messages should be displayed. Placing the tag after the <body> tag
will put the messages at the top of the screen, or it can be placed at the
bottom of the template to display the message below the header but before any
content.

#INSTALLATION

The evList  Plugin uses the glFusion automated plugin installer.
Simply upload the distribution using the glFusion plugin installer located in
the Plugin Administration page.

#UPGRADING

The upgrade process is identical to the installation process, simply upload
the distribution from the Plugin Administration page.

#CONFIGURATION SETTINGS

Allow anonymous users to view events?
    Set this to TRUE to allow non-logged in users to view events.  Set to
    FALSE to require that users log in to see events.

Allow logged in user submissions?
    Set to TRUE to allow normal, logged-in users to submit events. All events
    from logged-in users will go into the submission queue.
    Set to FALSE to disable event submission for logged-in users.

Allow HTML when posting?
    Set to TRUE to allow HTML use in the event description and the event
    summaries.  ALL HTML will be filtered through the glFusion HTML filtering
    engine.  Set to FALSE to disable the use of HTML.

Enable Categories
    Set to TRUE to enable category support.

Reminder Speedlimit
    How often, in seconds, you can select to be reminded of an event.

Posting Speedlimit
    How often, in seconds, you can post a new event.

Enable email reminders?
    Select whether email event reminders will be enabled globall.
    Reminders can still be disabled for a given event.

Number of days prior to an event to allow reminders
    Enter the minimum number of days before an event for someone to 
    enter their email address for a reminder. Default = 1.

#GUI SETTINGS

Enable the menu item
    Set this to TRUE to enable a link for evList to be placed in the User Menu.
    See User menu link option for more options.

User menu link option
    Select if the User Menu link is "Add Event" or "List Events"

An event ceases to be upcoming...
	Select when an event falls off the 'Upcoming' list:
  - as soon as the start date has passed, i.e. the next day
  - as soon as the start time has passed
  - as soon as the end time has passed
  - as soon as the end date has passed, i.e. the next day

Number of events to display per page.
	Number of events to display per page.

#CENTERBLOCK SETTINGS

Centerblock Type
    Select the type of centerblock to use, if any.
    - "disabled" - no centerblock is shown
    - "table" - a table of events is shown
    - "story" - upcoming events are shown as stories

Centerblock Position
    Select the position of the centerblock.

Topic
    Which topic should the centerblock be displayed

Select an event range to display
    Select which event range to include in the centerblock.

Number of events to display
    Number of events to display in the centerblock.

Number of characters to display in event summary
    Number of characters (width) of the centerblock.


#QUIRKS AND ISSUES
* The selected starting date for a repeating event is always used, even if it
would normally not be included.  For example, creating an event to occur
every third Tuesday, but selecting a Monday as the start date, causes the
event to occur on that Monday as well as the following Tuesdays.
* When the JSON calendar is used:
** the Mootools tooltip is not working except on the first calendar viewed. The normal browser hover style is used to show event information when hovering over an event title.
** the date selector always defaults to the date first viewed.
