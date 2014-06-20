<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | def_events.php                                                           |
// |                                                                          |
// | Default event data for evList                                            |
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
*   Default events to load into evList during installation
*   @package    evlist
*/
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

$DEFVALUES['evlist_events'] = "INSERT INTO {$_TABLES['evlist_events']}
        (`id`, `date_start1`, `date_end1`, `time_start1`, `time_end1`, 
        `time_start2`, `time_end2`, `recurring`, `rec_data`, 
        `allday`, `split`, `status`, `postmode`, `hits`, `enable_reminders`, 
        `owner_id`, `group_id`, `perm_owner`, `perm_group`, `perm_members`, 
        `perm_anon`, `det_id`, `show_upcoming`, `cal_id`, `options`) 
    VALUES 
        ('20070924175337252','2011-02-14','2011-02-14','00:00:00','23:59:59',NULL,NULL,1,'a:3:{s:4:\"type\";i:3;s:4:\"stop\";s:10:\"2037-12-31\";s:4:\"skip\";i:0;}',1,0,1,'plaintext',0,1,2,1,3,3,0,0,1,1,1,'a:1:{s:11:\"contactlink\";i:0;}'),
        ('20070922110402423','2011-12-16','2011-12-16','17:00:00','19:00:00',NULL,NULL,0,'a:2:{s:4:\"type\";i:0;s:4:\"stop\";s:10:\"2037-12-31\";}',0,0,1,'plaintext',0,1,2,1,3,3,0,0,2,1,1,'a:1:{s:11:\"contactlink\";i:1;}'),
        ('20070924140852285','2011-03-02','2011-03-02','10:00:00','12:00:00','17:00:00','19:00:00',1,'a:2:{s:4:\"type\";i:1;s:4:\"stop\";s:10:\"2011-03-12\";}',0,1,1,'plaintext',0,1,2,1,3,3,0,0,4,1,1,'a:1:{s:11:\"contactlink\";i:1;}')
";

$DEFVALUES['evlist_submissions'] = "INSERT INTO {$_TABLES['evlist_submissions']}
        (`id`, `date_start1`, `date_end1`, `time_start1`, `time_end1`, 
        `time_start2`, `time_end2`, `recurring`, `rec_data`, 
        `allday`, `split`, `status`, `postmode`, `hits`, `enable_reminders`, 
        `owner_id`, `group_id`, `perm_owner`, `perm_group`, `perm_members`, 
        `perm_anon`, `det_id`, `show_upcoming`, `cal_id`, `options`) 
    VALUES 
        ('20070924133400211','2011-02-01','2011-02-01','17:00:00','21:15:00','','',0,'a:2:{s:4:\"type\";i:0;s:4:\"stop\";s:10:\"2037-12-31\";}',0,0,1,'plaintext',2,1,2,2,3,0,0,0,3,1,1,'a:1:{s:11:\"contactlink\";i:1;}')
";

$DEFVALUES['evlist_detail'] = "INSERT INTO {$_TABLES['evlist_detail']} 
        (`det_id`, `ev_id`, `title`, `summary`, `full_description`, `url`, 
        `location`, `street`, `city`, `province`, `country`, `postal`, 
        `contact`, `email`, `phone`) 
    VALUES 
        (1,'20070924175337252','4th example: recurring events','Like example #3, this is a recurring event.  A recurring event is an event that recurs according to a particular pattern.  For example, an event may be set to recur once per year.  If it is, then that event will be displayed in the event list that often.','<p>No matter the date and time information that you\'ve entered, if an event is set to recur, then it will--then it will.  If you check the recurring event box further fields will be presented to collect such information as how often the event will recur and when it will stop recurring if it indeed does stop recurring.  An end date is not required for any event, even a recurring event.</p>\r<p>A number of basic formats are available to use for your event.  Daily, monthly and yearly events are pretty basic.  You can also choose to have an event recur on particular days per week, or on a particular day (e.g., 2nd sunday) per month.  You may alternatively supply a list of dates upon which the event should recur.</p>\r<p>An ending date is not required for recurring events, or for any events actually.  If supplied, the event will only be displayed up to the end date, otherwise the event will continue to be displayed.  A default display range for recurring events is hard-coded into the software to limit the number of events that are displayed.  For example, if you have a daily recurring event that will recur for a year, only one month worth of recurrences will be displayed ahead of now.  This default range is different depending upon the format chosen.</p>\r<p>Depending upon the format chosen, recurring events might land on weekends and if that is not desired then you have the choice skip the event or to force it to the next business day.  This applies to the daily (next business day not available for daily option), monthly and yearly by date formats.</p>\r','','This event takes place everywhere!','','','','','','Cupid','lovehurts@nowhere.com','555-love'),
    (2,'20070922110402423','1st example: General Information','This is an example event.  Only Root users can view these example events so you have no need to delete them.  You may use them as reference later on.  Read on for more information.','<p>This example will list just a few of the general features.  Please take note that evList is not a tutorial program, but an event list.  These instructions appear as events in the list only as a convenient reference.</p>\r\n<br /><ul>\r\n<li>The only required fields in the event editor are the event title and the start date year and month fields.  All other fields are optional.</li>\r\n<li>This event is posted in html mode.  The other option is plaintext and any html will be stripped from such a post.  Those fields that accept html are the summary, full description, and location fields.</li>\r\n<li>Note that this example event has been given a start date (year and month) and no end date.  That is acceptable.  The only required dates here are the start date year and month.  This makes the list much more flexible in terms of what kind of events may be listed.  Keep in mind that if you provide a time or a day, you must provide a month, or if you provide a month, you must provide a year, etc.  For example: you cannot have an end time without an end date.</li>\r\n<li>evList supports recurring events and offers a number of basic formats to choose from for configuring your event.  An example of a recurring event is provided for you (example #4).</li>\r\n<li>Your events may be categorized.  This is not required.  If you wish to view uncategorized events, simply do not choose a category from the drop-down on the event list page.</li>\r\n<li>Events make use of the same permissions system as stories in glFusion which means events can easily be restricted.</li>\r\n<li>The contact section of the event editor asks for an email address.  This is not required of course, but rest assured that while you will be able to read any email displayed, that email is encrypted to protect from bots scraping your pages for email addresses.</li>\r\n<li>evList also supports event reminders.  Unless an event is occuring within a week of now, an event reminder form will appear at the bottom of the event description.  This form will take an email address and a number corresponding to days prior to an event in order for a reminder email to be sent that many days prior to the event.  Reminders can be turned on/off per event, or globally.</li>\r\n</ul><br />','http://www.glfusion.org','This is the location field.  It will support more than just a place name--clearly.  It also supports html if that mode is enabled.','123 Anystreet.','Anytown','Some State','USA','90210','someuser','noone@nowhere.com',''),
    (3,'20070924133400211','2nd example: submissions queue','This example briefly covers the submissions queue and its functions.','If you wish, as admin, to be notified of submissions to the queue, add \"evlist\" to \$_CONF[\'notification\'].  Events that reside in the submissions queue awaiting approval are disabled and cannot be viewed by regular users until approved.  Events so submitted can be deleted from the list of submissions in the queue or may be sent to the editor for editing.  Sending a submission to the editor will provide you, the admin, with event details that do not get listed in the submission queue.\r\n\r\nApproving submissions can be accomplished two ways:  either from the submission queue, checking the &quot;approve&quot; check box; or sending the event from the queue to the event editor, and then checking the &quot;enable event&quot; check box.  Submissions that are sent to the editor from the queue are disabled by default and must have the &quot;enable event&quot; check box checked before saving the event or it will remain a disabled event.\r\n\r\nRegular events may also be enabled/disabled via the &quot;enable event&quot; check box in the event editor.  You can gain access to disabled events through the admin lists.\r\n\r\nA speed limit is enforced for submissions made by any user without evList admin rights.  The speed limits are defined in the plugin\'s config.php file.  A speed limit is defined for event submissions and another limit is defined for event reminder requests.\r\n\r\nNotice that this event does not have an address listed.  This is OK.  Remember that there are only 3 required fields in the editor: the title field and the start date year and month fields.','','','','','','','','','',''),
    (4,'20070924140852285','3rd example: split and all day events','The event will introduce you to split and all day events, which are simply different ways of defining start and end times for an event.','<p>The day event check box in the event editor, if checked, causes the save event process to ignore any end time or split times that might have been supplied.  An all day event goes all day after all.  evList will display a small note on the event page that this event is an all day event.</p>\r\n<p>A split event is and event that is split into one or more pieces, hence the name.  evList supports your basic split where and event runs twice in one day.  In this case the event will have 2 start and end dates.  For example, an event may run in the morning and in the evening, but not in the afternoon.  Rather than creating 2 events, simply supply start times and end times for the event on each side of the split</p>\r\n<p>Regular events, all day events, as well as split events can all be recurring events--to be discussed in example #4.</p>','','This event takes place online at the following address:  http://example.com.<br>http://third.example.com to visit some place in particular.','','','','','','','','')
";

$DEFVALUES['evlist_categories'] = "INSERT INTO {$_TABLES['evlist_categories']} 
        (name,status) 
    VALUES
        ('General','1'),
        ('Birthdays','1'),
        ('Seminars','1')
    ";

$DEFVALUES['evlist_calendars'] = "INSERT INTO {$_TABLES['evlist_calendars']}
        (cal_name, cal_status, fgcolor, bgcolor, owner_id, group_id,
        perm_owner, perm_group, perm_members, perm_anon)
    VALUES
        ('Events', 1, '#990000', '#ffccff', 2, 13, 3, 3, 2, 2)
    ";
    
$DEFVALUES['evlist_repeat'] = "INSERT INTO `{$_TABLES['evlist_repeat']}`
(`rp_id`, `rp_ev_id`, `rp_det_id`, `rp_date_start`, `rp_date_end`, `rp_time_start1`, `rp_time_end1`, `rp_time_start2`, `rp_time_end2`) VALUES 
(1,'20070922110402423',2,'2010-12-16','2010-12-16','17:00:00','19:00:00','00:00:00','00:00:00'),
(2,'20070924140852285',4,'2010-03-02','2010-03-02','10:00:00','12:00:00','17:00:00','19:00:00'),
(3,'20070924140852285',4,'2010-03-03','2010-03-03','10:00:00','12:00:00','17:00:00','19:00:00'),
(4,'20070924140852285',4,'2010-03-04','2010-03-04','10:00:00','12:00:00','17:00:00','19:00:00'),
(5,'20070924140852285',4,'2010-03-05','2010-03-05','10:00:00','12:00:00','17:00:00','19:00:00'),
(6,'20070924140852285',4,'2010-03-06','2010-03-06','10:00:00','12:00:00','17:00:00','19:00:00'),
(7,'20070924140852285',4,'2010-03-07','2010-03-07','10:00:00','12:00:00','17:00:00','19:00:00'),
(8,'20070924140852285',4,'2010-03-08','2010-03-08','10:00:00','12:00:00','17:00:00','19:00:00'),
(9,'20070924140852285',4,'2010-03-09','2010-03-09','10:00:00','12:00:00','17:00:00','19:00:00'),
(10,'20070924140852285',4,'2010-03-10','2010-03-10','10:00:00','12:00:00','17:00:00','19:00:00'),
(11,'20070924140852285',4,'2010-03-11','2010-03-11','10:00:00','12:00:00','17:00:00','19:00:00'),
(12,'20070924140852285',4,'2010-03-12','2010-03-12','10:00:00','12:00:00','17:00:00','19:00:00'),
(13,'20070924175337252',1,'2010-02-14','2010-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(14,'20070924175337252',1,'2011-02-14','2011-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(15,'20070924175337252',1,'2012-02-14','2012-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(16,'20070924175337252',1,'2013-02-14','2013-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(17,'20070924175337252',1,'2014-02-14','2014-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(18,'20070924175337252',1,'2015-02-14','2015-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(19,'20070924175337252',1,'2016-02-14','2016-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(20,'20070924175337252',1,'2017-02-14','2017-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(21,'20070924175337252',1,'2018-02-14','2018-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(22,'20070924175337252',1,'2019-02-14','2019-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(23,'20070924175337252',1,'2020-02-14','2020-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(24,'20070924175337252',1,'2021-02-14','2021-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(25,'20070924175337252',1,'2022-02-14','2022-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(26,'20070924175337252',1,'2023-02-14','2023-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(27,'20070924175337252',1,'2024-02-14','2024-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(28,'20070924175337252',1,'2025-02-14','2025-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(29,'20070924175337252',1,'2026-02-14','2026-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(30,'20070924175337252',1,'2027-02-14','2027-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(31,'20070924175337252',1,'2028-02-14','2028-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(32,'20070924175337252',1,'2029-02-14','2029-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(33,'20070924175337252',1,'2030-02-14','2030-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(34,'20070924175337252',1,'2031-02-14','2031-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(35,'20070924175337252',1,'2032-02-14','2032-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(36,'20070924175337252',1,'2033-02-14','2033-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(37,'20070924175337252',1,'2034-02-14','2034-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(38,'20070924175337252',1,'2035-02-14','2035-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(39,'20070924175337252',1,'2036-02-14','2036-02-14','00:00:00','23:59:59','00:00:00','00:00:00'),
(40,'20070924175337252',1,'2037-02-14','2037-02-14','00:00:00','23:59:59','00:00:00','00:00:00')";

?>
