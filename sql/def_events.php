<?php
/**
 * Default events to load into evList during installation.
 *
 * @author      Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2010 - 2018 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

$DEFVALUES['evlist_events'] = "INSERT INTO `gl_evlist_events` VALUES
    ('20070924175337252','2011-02-14','2011-02-14','00:00:00','23:59:00','00:00:00','00:00:00',3,'a:4:{s:4:\"type\";i:3;s:4:\"freq\";i:1;s:4:\"stop\";s:10:\"2037-12-31\";s:4:\"skip\";i:0;}',1,0,1,'html',1,0,2,1,3,3,0,0,1,1,1,'a:8:{s:11:\"contactlink\";i:0;s:7:\"tickets\";a:0:{}s:8:\"use_rsvp\";i:0;s:8:\"max_rsvp\";i:0;s:11:\"rsvp_cutoff\";i:0;s:13:\"rsvp_waitlist\";i:0;s:13:\"max_user_rsvp\";i:1;s:10:\"rsvp_print\";i:0;}','local',2,'2021-10-13 15:16:41'),
    ('20070922110402423','2021-12-16','2021-12-16','17:00:00','19:00:00','00:00:00','00:00:00',0,'a:3:{s:4:\"type\";i:0;s:4:\"stop\";s:10:\"2037-12-31\";s:4:\"freq\";i:1;}',0,0,1,'html',1,0,2,1,3,3,0,0,2,1,1,'a:8:{s:11:\"contactlink\";i:1;s:7:\"tickets\";a:0:{}s:8:\"use_rsvp\";i:0;s:8:\"max_rsvp\";i:0;s:11:\"rsvp_cutoff\";i:0;s:13:\"rsvp_waitlist\";i:0;s:13:\"max_user_rsvp\";i:1;s:10:\"rsvp_print\";i:0;}','local',3,'2021-10-13 15:15:56'),
    ('20070924140852285','2022-03-02','2022-03-02','10:00:00','12:00:00','19:00:00','19:00:00',1,'a:4:{s:4:\"type\";i:1;s:4:\"freq\";i:1;s:4:\"stop\";s:10:\"2011-03-12\";s:4:\"skip\";i:0;}',0,1,1,'html',1,0,2,1,3,3,0,0,4,1,1,'a:8:{s:11:\"contactlink\";i:1;s:7:\"tickets\";a:0:{}s:8:\"use_rsvp\";i:0;s:8:\"max_rsvp\";i:0;s:11:\"rsvp_cutoff\";i:0;s:13:\"rsvp_waitlist\";i:0;s:13:\"max_user_rsvp\";i:1;s:10:\"rsvp_print\";i:0;}','local',3,'2021-10-13 15:16:06')";


$DEFVALUES['evlist_submissions'] = "INSERT INTO `gl_evlist_submissions` VALUES ('20070924133400211','2011-02-01','2011-02-01','17:00:00','21:15:00','00:00:00','00:00:00',0,'a:2:{s:4:\"type\";i:0;s:4:\"stop\";s:10:\"2037-12-31\";}',0,0,1,'plaintext',1,0,2,2,3,0,0,0,3,1,1,'a:1:{s:11:\"contactlink\";i:1;}','local',1,'2021-10-13 15:05:16')";

$DEFVALUES['evlist_detail'] = "INSERT INTO `gl_evlist_detail` VALUES
    (1,'20070924175337252','4th example: recurring events','Like example #3, this is a recurring event. A recurring event is an event that recurs according to a particular pattern. For example, an event may be set to recur once per year. If it is, then that event will be displayed in the event list that often.','<p>No matter the date and time information that you&#39;ve entered, if an event is set to recur, then it will--then it will. If you check the recurring event box further fields will be presented to collect such information as how often the event will recur and when it will stop recurring if it indeed does stop recurring. An end date is not required for any event, even a recurring event.</p>\r\n\r\n<p>A number of basic formats are available to use for your event. Daily, monthly and yearly events are pretty basic. You can also choose to have an event recur on particular days per week, or on a particular day (e.g., 2nd sunday) per month. You may alternatively supply a list of dates upon which the event should recur.</p>\r\n\r\n<p>An ending date is not required for recurring events, or for any events actually. If supplied, the event will only be displayed up to the end date, otherwise the event will continue to be displayed. A default display range for recurring events is hard-coded into the software to limit the number of events that are displayed. For example, if you have a daily recurring event that will recur for a year, only one month worth of recurrences will be displayed ahead of now. This default range is different depending upon the format chosen.</p>\r\n\r\n<p>Depending upon the format chosen, recurring events might land on weekends and if that is not desired then you have the choice skip the event or to force it to the next business day. This applies to the daily (next business day not available for daily option), monthly and yearly by date formats.</p>\r\n','','This event takes place everywhere!','','','','','','Cupid','lovehurts@nowhere.com','555-love',0.000000,0.000000,1,1,'2021-10-13 15:16:40'),
(2,'20070922110402423','1st example: General Information','This is an example event. Only Root users can view these example events so you have no need to delete them. You may use them as reference later on. Read on for more information.','<p>This example will list just a few of the general features. Please take note that evList is not a tutorial program, but an event list. These instructions appear as events in the list only as a convenient reference.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<ul>\r\n       <li>The only required fields in the event editor are the event title and the start date year and month fields. All other fields are optional.</li>\r\n  <li>This event is posted in html mode. The other option is plaintext and any html will be stripped from such a post. Those fields that accept html are the summary, full description, and location fields.</li>\r\n     <li>Note that this example event has been given a start date (year and month) and no end date. That is acceptable. The only required dates here are the start date year and month. This makes the list much more flexible in terms of what kind of events may be listed. Keep in mind that if you provide a time or a day, you must provide a month, or if you provide a month, you must provide a year, etc. For example: you cannot have an end time without an end date.</li>\r\n   <li>evList supports recurring events and offers a number of basic formats to choose from for configuring your event. An example of a recurring event is provided for you (example #4).</li>\r\n  <li>Your events may be categorized. This is not required. If you wish to view uncategorized events, simply do not choose a category from the drop-down on the event list page.</li>\r\n <li>Events make use of the same permissions system as stories in glFusion which means events can easily be restricted.</li>\r\n <li>The contact section of the event editor asks for an email address. This is not required of course, but rest assured that while you will be able to read any email displayed, that email is encrypted to protect from bots scraping your pages for email addresses.</li>\r\n <li>evList also supports event reminders. Unless an event is occuring within a week of now, an event reminder form will appear at the bottom of the event description. This form will take an email address and a number corresponding to days prior to an event in order for a reminder email to be sent that many days prior to the event. Reminders can be turned on/off per event, or globally.</li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n','http://www.glfusion.org','This is the location field. It will support more than just a place name--clearly. It also supports html if that mode is enabled.','123 Anystreet.','Anytown','Some State','USA','90210','someuser','noone@nowhere.com','',0.000000,0.000000,1,2,'2021-10-13 15:15:56'),
(3,'20070924133400211','2nd example: submissions queue','This example briefly covers the submissions queue and its functions.','If you wish, as admin, to be notified of submissions to the queue, add \"evlist\" to the admin notifications setting, found in the Miscellaneous section of the global configuration manager. Events that reside in the submissions queue awaiting approval are disabled and cannot be viewed by regular users until approved. Events so submitted can be deleted from the list of submissions in the queue or may be sent to the editor for editing. Sending a submission to the editor will provide you, the admin, with event details that do not get listed in the submission queue.\r\n\r\nApproving submissions can be accomplished two ways:  either from the submission queue, checking the &quot;approve&quot; check box; or sending the event from the queue to the event editor, and then checking the &quot;enable event&quot; check box. Submissions that are sent to the editor from the queue are disabled by default and must have the &quot;enable event&quot; check box checked before saving the event or it will remain a disabled event.\r\n\r\nRegular events may also be enabled/disabled via the &quot;enable event&quot; check box in the event editor. You can gain access to disabled events through the admin lists.\r\n\r\nA speed limit is enforced for submissions made by any user without evList admin rights. The speed limits are defined in the plugin\'s config.php file. A speed limit is defined for event submissions and another limit is defined for event reminder requests.\r\n\r\nNotice that this event does not have an address listed. This is OK. Remember that there are only 3 required fields in the editor: the title field and the start date year and month fields.','','','','','','','','','','',NULL,NULL,1,0,'2021-10-13 15:05:16'),
(4,'20070924140852285','3rd example: split and all day events','The event will introduce you to split and all day events, which are simply different ways of defining start and end times for an event.','<p>The day event check box in the event editor, if checked, causes the save event process to ignore any end time or split times that might have been supplied. An all day event goes all day after all. evList will display a small note on the event page that this event is an all day event.</p>\r\n\r\n<p>A split event is and event that is split into one or more pieces, hence the name. evList supports your basic split where and event runs twice in one day. In this case the event will have 2 start and end dates. For example, an event may run in the morning and in the evening, but not in the afternoon. Rather than creating 2 events, simply supply start times and end times for the event on each side of the split</p>\r\n\r\n<p>Regular events, all day events, as well as split events can all be recurring events--to be discussed in example #4.</p>\r\n','','This event takes place online at the following address:  http://example.com.<br>http://third.example.com to visit some place in particular.','','','','','','','','',0.000000,0.000000,1,2,'2021-10-13 15:16:06')";
;

$DEFVALUES['evlist_categories'] = "INSERT INTO {$_TABLES['evlist_categories']}
        (name,status)
    VALUES
        ('General','1'),
        ('Birthdays','1'),
        ('Seminars','1')
    ";

$DEFVALUES['evlist_calendars'] = "INSERT INTO {$_TABLES['evlist_calendars']}
        (cal_id, cal_name, cal_status, fgcolor, bgcolor, owner_id, group_id,
        perm_owner, perm_group, perm_members, perm_anon, cal_icon)
    VALUES
        (1, 'Events', 1, '#990000', '#ffccff', 2, 13, 3, 3, 2, 2, '')
    ";

$DEFVALUES['evlist_repeat'] = "INSERT INTO `gl_evlist_repeat` VALUES
    (1,'20070922110402423',2,'2021-12-16','2021-12-16','17:00:00','19:00:00','00:00:00','00:00:00','2021-12-16 17:00:00','2021-12-16 19:00:00',1,3,'2021-10-13 15:15:56'),
    (2,'20070924140852285',4,'2010-03-02','2010-03-02','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-02 12:00:00','2010-03-02 19:00:00',2,2,'2021-10-13 15:15:18'),
    (3,'20070924140852285',4,'2010-03-03','2010-03-03','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-03 12:00:00','2010-03-03 19:00:00',2,2,'2021-10-13 15:15:18'),
    (4,'20070924140852285',4,'2010-03-04','2010-03-04','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-04 12:00:00','2010-03-04 19:00:00',2,2,'2021-10-13 15:15:18'),
    (5,'20070924140852285',4,'2010-03-05','2010-03-05','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-05 12:00:00','2010-03-05 19:00:00',2,2,'2021-10-13 15:15:18'),
    (6,'20070924140852285',4,'2010-03-06','2010-03-06','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-06 12:00:00','2010-03-06 19:00:00',2,2,'2021-10-13 15:15:18'),
    (7,'20070924140852285',4,'2010-03-07','2010-03-07','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-07 12:00:00','2010-03-07 19:00:00',2,2,'2021-10-13 15:15:18'),
    (8,'20070924140852285',4,'2010-03-08','2010-03-08','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-08 12:00:00','2010-03-08 19:00:00',2,2,'2021-10-13 15:15:18'),
    (9,'20070924140852285',4,'2010-03-09','2010-03-09','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-09 12:00:00','2010-03-09 19:00:00',2,2,'2021-10-13 15:15:18'),
    (10,'20070924140852285',4,'2010-03-10','2010-03-10','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-10 12:00:00','2010-03-10 19:00:00',2,2,'2021-10-13 15:15:18'),
    (11,'20070924140852285',4,'2010-03-11','2010-03-11','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-11 12:00:00','2010-03-11 19:00:00',2,2,'2021-10-13 15:15:18'),
    (12,'20070924140852285',4,'2010-03-12','2010-03-12','10:00:00','12:00:00','17:00:00','19:00:00','2010-03-12 12:00:00','2010-03-12 19:00:00',2,2,'2021-10-13 15:15:18'),
    (13,'20070924175337252',1,'2011-02-14','2011-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2011-02-14 00:00:00','2011-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (14,'20070924175337252',1,'2012-02-14','2012-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2012-02-14 00:00:00','2012-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (15,'20070924175337252',1,'2013-02-14','2013-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2013-02-14 00:00:00','2013-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (16,'20070924175337252',1,'2014-02-14','2014-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2014-02-14 00:00:00','2014-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (17,'20070924175337252',1,'2015-02-14','2015-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2015-02-14 00:00:00','2015-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (18,'20070924175337252',1,'2016-02-14','2016-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2016-02-14 00:00:00','2016-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (19,'20070924175337252',1,'2017-02-14','2017-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2017-02-14 00:00:00','2017-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (20,'20070924175337252',1,'2018-02-14','2018-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2018-02-14 00:00:00','2018-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (21,'20070924175337252',1,'2019-02-14','2019-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2019-02-14 00:00:00','2019-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (22,'20070924175337252',1,'2020-02-14','2020-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2020-02-14 00:00:00','2020-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (23,'20070924175337252',1,'2021-02-14','2021-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2021-02-14 00:00:00','2021-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (24,'20070924175337252',1,'2022-02-14','2022-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2022-02-14 00:00:00','2022-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (25,'20070924175337252',1,'2023-02-14','2023-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2023-02-14 00:00:00','2023-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (26,'20070924175337252',1,'2024-02-14','2024-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2024-02-14 00:00:00','2024-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (27,'20070924175337252',1,'2025-02-14','2025-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2025-02-14 00:00:00','2025-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (28,'20070924175337252',1,'2026-02-14','2026-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2026-02-14 00:00:00','2026-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (29,'20070924175337252',1,'2027-02-14','2027-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2027-02-14 00:00:00','2027-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (30,'20070924175337252',1,'2028-02-14','2028-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2028-02-14 00:00:00','2028-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (31,'20070924175337252',1,'2029-02-14','2029-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2029-02-14 00:00:00','2029-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (32,'20070924175337252',1,'2030-02-14','2030-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2030-02-14 00:00:00','2030-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (33,'20070924175337252',1,'2031-02-14','2031-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2031-02-14 00:00:00','2031-02-14 23:59:00',1,2,'2021-10-13 15:16:40'),
    (34,'20070924175337252',1,'2032-02-14','2032-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2032-02-14 00:00:00','2032-02-14 23:59:00',1,2,'2021-10-13 15:16:41'),
    (35,'20070924175337252',1,'2033-02-14','2033-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2033-02-14 00:00:00','2033-02-14 23:59:00',1,2,'2021-10-13 15:16:41'),
    (36,'20070924175337252',1,'2034-02-14','2034-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2034-02-14 00:00:00','2034-02-14 23:59:00',1,2,'2021-10-13 15:16:41'),
    (37,'20070924175337252',1,'2035-02-14','2035-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2035-02-14 00:00:00','2035-02-14 23:59:00',1,2,'2021-10-13 15:16:41'),
    (38,'20070924175337252',1,'2036-02-14','2036-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2036-02-14 00:00:00','2036-02-14 23:59:00',1,2,'2021-10-13 15:16:41'),
    (39,'20070924175337252',1,'2037-02-14','2037-02-14','00:00:00','23:59:00','00:00:00','00:00:00','2037-02-14 00:00:00','2037-02-14 23:59:00',1,2,'2021-10-13 15:16:41'),
    (40,'20070924175337252',1,'2037-02-14','2037-02-14','00:00:00','23:59:59','00:00:00','00:00:00','2037-02-14 23:59:59','2037-02-14 23:59:59',2,2,'2021-10-13 15:16:41')";

$DEFVALUES['evlist_tickettypes'] = "INSERT INTO `{$_TABLES['evlist_tickettypes']}` VALUES
    (0, 'General Admission', 0, 1)";

