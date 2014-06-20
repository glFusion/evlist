evlist
======

Event List and calendar plugin for glFusion
Version: 1.3.0

For the latest documentation, please see

	http://www.glfusion.org/wiki/doku.php?id=evlist:start

LICENSE

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

OVERVIEW

A calendar solution for glFusion. evList supports recurring events, 
categories, and more.

SYSTEM REQUIREMENTS

evList has the following system requirements:

    * PHP 5.1 and higher.
    * glFusion v1.2.0 or newer

INSTALLATION

The evList  Plugin uses the glFusion automated plugin installer.
Simply upload the distribution using the glFusion plugin installer located in
the Plugin Administration page.

UPGRADING

The upgrade process is identical to the installation process, simply upload
the distribution from the Plugin Administration page.

CONFIGURATION SETTINGS

Allow anonymous users to view events?

    Set this to TRUE to allow non-logged in users to view events.  Set to
    FALSE to require that users log in to see events.

Allow anonymous submissions?

    Set to TRUE to allow non-logged-in users to submit events.  All events
    from non-logged-in users will go into the submission queue.
    Set to FALSE to disable non-logged-in users submitting events.

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

GUI SETTINGS

Enable the menu item

    Set this to TRUE to enable a link for evList to be placed in the User Menu.
    See User menu link option for more options.

User menu link option

    Select if the User Menu link is; Add Event or List Events

Week begins on...

	Select which day the week should begin, Sunday or Monday.

Date Format

	Select the date format to display dates in evList.

Time Format

	Select the time format to display times in evList.

An event ceases to be upcoming...

	Select when an event falls off the 'Upcoming' list:
  - as soon as the start date has passed, i.e. the next day
  - as soon as the start time has passed
  - as soon as the end time has passed
  - as soon as the end date has passed, i.e. the next day

Number of events to display per page.

	Number of events to display per page.

CENTERBLOCK SETTINGS

Enable Centerblock?

    Set to TRUE to enable the evList centerblock.

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


QUIRKS AND ISSUES
The selected starting date for a repeating event is always used, even if if
would normally not be included.  For example, creating an event to occur
every third Tuesday, but selecting a Monday as the start date, causes the
event to occur on that Monday as well as the following Tuesdays.

