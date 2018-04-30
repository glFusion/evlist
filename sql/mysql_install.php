<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | mysql_install.php                                                        |
// |                                                                          |
// | MySQL SQL for evList                                                     |
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
*   SQL table creation statements used during evList installation
*   @package    evlist
*/
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_TABLES, $_SQL, $_EV_UPGRADE;

$event_table = 
  "`id` varchar(128) NOT NULL,
  `date_start1` date DEFAULT NULL,
  `date_end1` date DEFAULT NULL,
  `time_start1` time DEFAULT NULL,
  `time_end1` time DEFAULT NULL,
  `time_start2` time DEFAULT NULL,
  `time_end2` time DEFAULT NULL,
  `recurring` tinyint(1) NOT NULL DEFAULT '0',
  `rec_data` text,
  `allday` tinyint(1) NOT NULL DEFAULT '0',
  `split` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `postmode` varchar(10) NOT NULL DEFAULT 'plaintext',
  `hits` int(11) NOT NULL DEFAULT '0',
  `enable_reminders` tinyint(1) NOT NULL DEFAULT '1',
  `enable_comments` tinyint(1) NOT NULL DEFAULT '0',
  `owner_id` mediumint(8) DEFAULT NULL,
  `group_id` mediumint(8) DEFAULT NULL,
  `perm_owner` tinyint(1) DEFAULT NULL,
  `perm_group` tinyint(1) DEFAULT NULL,
  `perm_members` tinyint(1) DEFAULT NULL,
  `perm_anon` tinyint(1) DEFAULT NULL,
  `det_id` int(10) NOT NULL,
  `show_upcoming` tinyint(1) NOT NULL DEFAULT '1',
  `cal_id` int(11) NOT NULL DEFAULT '1',
  `options` varchar(255),
  `tzid` varchar(125) NOT NULL DEFAULT 'local',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM";

$_SQL['evlist_events'] = "CREATE TABLE {$_TABLES['evlist_events']} (" .
    $event_table;

$_SQL['evlist_submissions'] = "CREATE TABLE {$_TABLES['evlist_submissions']} (".
    $event_table;

$_SQL['evlist_repeat'] = "CREATE TABLE {$_TABLES['evlist_repeat']} (
  `rp_id` int(10) NOT NULL AUTO_INCREMENT,
  `rp_ev_id` varchar(128) DEFAULT NULL,
  `rp_det_id` int(10) NOT NULL,
  `rp_date_start` date DEFAULT NULL,
  `rp_date_end` date DEFAULT NULL,
  `rp_time_start1` time DEFAULT NULL,
  `rp_time_end1` time DEFAULT NULL,
  `rp_time_start2` time DEFAULT NULL,
  `rp_time_end2` time DEFAULT NULL,
  `rp_start` DATETIME,
  `rp_end` DATETIME,
  PRIMARY KEY (`rp_id`),
  KEY `event` (`rp_ev_id`),
  KEY `start` (`rp_start`),
  KEY `end` (`rp_end`)
) ENGINE=MyISAM";

$_SQL['evlist_categories'] = "CREATE TABLE {$_TABLES['evlist_categories']} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Name` (`name`(10))
) ENGINE=MyISAM";

$_SQL['evlist_lookup'] = "CREATE TABLE {$_TABLES['evlist_lookup']} (
  eid VARCHAR(128) NOT NULL,
  cid INT UNSIGNED NOT NULL,
  status TINYINT(1) NOT NULL default '1',
  PRIMARY KEY(eid,cid)
) ENGINE=MyISAM";

$_SQL['evlist_remlookup'] = "CREATE TABLE {$_TABLES['evlist_remlookup']} (
  `eid` varchar(128) NOT NULL,
  `rp_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_start` int(10) unsigned NOT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `uid` mediumint(8) NOT NULL DEFAULT '1',
  `name` varchar(40) NOT NULL DEFAULT 'nobody',
  `email` varchar(96) NOT NULL,
  `days_notice` smallint(3) NOT NULL DEFAULT '7',
  UNIQUE KEY `eid` (`eid`,`rp_id`,`email`,`days_notice`)
) ENGINE=MyISAM";

$_SQL['evlist_detail'] = "CREATE TABLE {$_TABLES['evlist_detail']} (
  `det_id` int(10) NOT NULL AUTO_INCREMENT,
  `ev_id` varchar(128) NOT NULL,
  `title` tinytext,
  `summary` text,
  `full_description` text,
  `url` varchar(255) DEFAULT NULL,
  `location` text,
  `street` varchar(64) DEFAULT NULL,
  `city` varchar(64) DEFAULT NULL,
  `province` varchar(64) DEFAULT NULL,
  `country` varchar(64) DEFAULT NULL,
  `postal` varchar(9) DEFAULT NULL,
  `contact` varchar(64) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL,
  PRIMARY KEY (`det_id`)
) ENGINE=MyISAM";

$_SQL['evlist_calendars'] = "CREATE TABLE {$_TABLES['evlist_calendars']} (
  `cal_id` int(11) NOT NULL AUTO_INCREMENT,
  `cal_name` varchar(255) NOT NULL DEFAULT '',
  `cal_status` tinyint(1) unsigned DEFAULT '1',
  `cal_ena_ical` tinyint(1) unsigned DEFAULT '1',
  `bgcolor` varchar(7) NOT NULL DEFAULT '#FFFFFF',
  `fgcolor` varchar(7) NOT NULL DEFAULT '#000000',
  `owner_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `perm_owner` tinyint(1) unsigned NOT NULL DEFAULT '3',
  `perm_group` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `perm_members` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `perm_anon` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `cal_icon` varchar(40) DEFAULT '',
  `orderby` int(5) unsigned NOT NULL DEFAULT 9999,
  PRIMARY KEY (`cal_id`)
) ENGINE=MyISAM";

$_SQL['evlist_rsvp'] = "CREATE TABLE {$_TABLES['evlist_rsvp']} (
  `ev_id` varchar(128) NOT NULL DEFAULT '',
  `rp_id` int(10) unsigned NOT NULL DEFAULT '0',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `num_attendees` int(4) unsigned NOT NULL DEFAULT '1',
  `dt_reg` int(11) DEFAULT '0',
  PRIMARY KEY (`ev_id`,`rp_id`,`uid`)
) ENGINE=MyISAM";

$_SQL['evlist_tickets'] = "CREATE TABLE `{$_TABLES['evlist_tickets']}` (
  `tic_id` varchar(128) NOT NULL,
  `tic_type` int(11) unsigned NOT NULL DEFAULT '0',
  `ev_id` varchar(128) NOT NULL,
  `rp_id` int(11) unsigned NOT NULL DEFAULT '0',
  `fee` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `paid` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `uid` int(11) unsigned NOT NULL,
  `used` int(11) unsigned NOT NULL DEFAULT '0',
  `dt` int(11) unsigned DEFAULT '0',
  `waitlist` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tic_id`),
  KEY `evt_rep` (`ev_id`,`rp_id`),
  KEY `user` (`uid`,`ev_id`),
  KEY `ev_dt` (`ev_id`,`dt`)
) ENGINE=MyISAM";

$_EV_UPGRADE = array(
'1.3.0' => array(
    "CREATE TABLE {$_TABLES['evlist_calendars']} (
      `cal_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `cal_name` varchar(255) NOT NULL DEFAULT '',
      `cal_status` tinyint(1) unsigned DEFAULT '1',
      `bgcolor` varchar(7) NOT NULL DEFAULT '#FFFFFF',
      `fgcolor` varchar(7) NOT NULL DEFAULT '#000000',
      `owner_id` int(10) unsigned NOT NULL,
      `group_id` int(10) unsigned NOT NULL,
      `perm_owner` tinyint(1) unsigned NOT NULL DEFAULT '3',
      `perm_group` tinyint(1) unsigned NOT NULL DEFAULT '2',
      `perm_members` tinyint(1) unsigned NOT NULL DEFAULT '2',
      `perm_anon` tinyint(1) unsigned NOT NULL DEFAULT '2',
      PRIMARY KEY (`cal_id`)
    ) ENGINE=MyISAM",
    "CREATE TABLE {$_TABLES['evlist_repeat']} (
      `rp_id` int(10) NOT NULL AUTO_INCREMENT,
      `rp_ev_id` varchar(128) DEFAULT NULL,
      `rp_det_id` int(10) NOT NULL,
      `rp_date_start` date DEFAULT NULL,
      `rp_date_end` date DEFAULT NULL,
      `rp_time_start1` time DEFAULT NULL,
      `rp_time_end1` time DEFAULT NULL,
      `rp_time_start2` time DEFAULT NULL,
      `rp_time_end2` time DEFAULT NULL,
      PRIMARY KEY (`rp_id`),
      KEY `event` (`rp_ev_id`),
      KEY `start` (`rp_date_start`)
    ) ENGINE=MyISAM",
    "CREATE TABLE {$_TABLES['evlist_detail']} (
      `det_id` int(10) NOT NULL AUTO_INCREMENT,
      `ev_id` varchar(128) NOT NULL,
      `title` tinytext,
      `summary` text,
      `full_description` text,
      `url` varchar(255) DEFAULT NULL,
      `location` text,
      `street` varchar(64) DEFAULT NULL,
      `city` varchar(64) DEFAULT NULL,
      `province` varchar(64) DEFAULT NULL,
      `country` varchar(64) DEFAULT NULL,
      `postal` varchar(9) DEFAULT NULL,
      `contact` varchar(64) DEFAULT NULL,
      `email` varchar(64) DEFAULT NULL,
      `phone` varchar(32) DEFAULT NULL,
      `lat` float(10,5) DEFAULT NULL,
      `lng` float(10,5) DEFAULT NULL,
      PRIMARY KEY (`det_id`)
    ) ENGINE=MyISAM",
    "INSERT INTO {$_TABLES['evlist_calendars']}
        (cal_name, cal_status, fgcolor, bgcolor, owner_id, group_id,
        perm_owner, perm_group, perm_members, perm_anon)
        VALUES
        ('Events', 1, '#990000', '#ffccff', 2, 13, 3, 3, 2, 2)",
    "ALTER TABLE {$_TABLES['evlist_events']}
        ADD det_id int(10) NOT NULL,
        ADD show_upcoming tinyint(1) unsigned NOT NULL DEFAULT '1',
        ADD cal_id int(10) unsigned NOT NULL DEFAULT '1',
        ADD options varchar(255)",
    "ALTER TABLE {$_TABLES['evlist_submissions']}
        ADD det_id int(10) NOT NULL,
        ADD show_upcoming tinyint(1) unsigned NOT NULL DEFAULT '1',
        ADD cal_id int(10) unsigned NOT NULL DEFAULT '1',
        ADD options varchar(255)",
    ),
'1.3.2' => array(
    "ALTER TABLE {$_TABLES['evlist_calendars']}
        ADD `cal_ena_ical` tinyint(1) unsigned DEFAULT '1' AFTER `cal_status`",
    ),
'1.3.7' => array(
    "CREATE TABLE `{$_TABLES['evlist_tickets']}` (
      `tic_id` varchar(128) NOT NULL,
      `tic_type` int(11) unsigned NOT NULL DEFAULT '0',
      `ev_id` varchar(128) NOT NULL,
      `rp_id` int(11) unsigned NOT NULL DEFAULT '0',
      `fee` float(6,2) unsigned NOT NULL DEFAULT '0.00',
      `paid` float(6,2) unsigned NOT NULL DEFAULT '0.00',
      `uid` int(11) unsigned NOT NULL,
      `used` int(11) unsigned NOT NULL DEFAULT '0',
      `dt` int(11) unsigned DEFAULT '0',
      PRIMARY KEY (`tic_id`),
      KEY `evt_rep` (`ev_id`,`rp_id`),
      KEY `user` (`uid`,`ev_id`),
      KEY `ev_dt` (`ev_id`,`dt`)
    ) ENGINE=MyISAM",
    "CREATE TABLE `{$_TABLES['evlist_tickettypes']}` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `description` varchar(255) DEFAULT NULL,
      `event_pass` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `enabled` tinyint(1) NOT NULL DEFAULT '1',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM",
    "INSERT INTO {$_TABLES['evlist_tickettypes']} VALUES (
        0, 'General Admission', 0, 1)",
    "UPDATE {$_TABLES['features']} SET ft_descr = 'Allowed to submit events'
        WHERE ft_name='evlist.submit'",
    "ALTER TABLE {$_TABLES['evlist_remlookup']} DROP KEY `eid`",
    "ALTER TABLE {$_TABLES['evlist_remlookup']}
        ADD UNIQUE KEY `eid` (`eid`, `rp_id`, `email`, `days_notice`)",
    "ALTER TABLE {$_TABLES['evlist_remlookup']}
        ADD name varchar(40) NOT NULL DEFAULT 'nobody' after `uid`",
    ),
'1.4.0' => array(
    "CREATE TABLE {$_TABLES['evlist_cache']} (
      `type` varchar(50) NOT NULL DEFAULT '',
      `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `data` text,
      PRIMARY KEY (`type`,`ts`)
    ) ENGINE=MyISAM",
    "ALTER TABLE {$_TABLES['evlist_events']}
        CHANGE id id varchar(128) NOT NULL",
    "ALTER TABLE {$_TABLES['evlist_repeat']}
        CHANGE rp_ev_id rp_ev_id varchar(128) NOT NULL",
    "ALTER TABLE {$_TABLES['evlist_tickets']}
        CHANGE ev_id ev_id varchar(128) NOT NULL,
        CHANGE tic_id tic_id varchar(128) NOT NULL",
    "ALTER TABLE {$_TABLES['evlist_lookup']}
        CHANGE eid eid VARCHAR(128) NOT NULL",
    "ALTER TABLE {$_TABLES['evlist_remlookup']}
        CHANGE eid eid varchar(128) NOT NULL",
    "ALTER TABLE {$_TABLES['evlist_detail']}
        CHANGE ev_id ev_id varchar(128) NOT NULL",
    "UPDATE {$_TABLES['conf_values']} SET
        subgroup = 30, sort_order = 50, fieldset = 0
        WHERE group_name = 'evlist' AND name='use_weather'",
    "UPDATE {$_TABLES['conf_values']} SET
        subgroup = 30, sort_order = 60, fieldset = 0
        WHERE group_name = 'evlist' AND name='use_locator'",
    ),
'1.4.1' => array(
    "ALTER TABLE {$_TABLES['evlist_events']}
        ADD `enable_comments` tinyint(1) NOT NULL DEFAULT '0' AFTER enable_reminders",
    "ALTER TABLE {$_TABLES['evlist_submissions']}
        ADD `enable_comments` tinyint(1) NOT NULL DEFAULT '0' AFTER enable_reminders",
    "ALTER TABLE {$_TABLES['evlist_submissions']}
        CHANGE id id varchar(128) NOT NULL",
    ),
'1.4.3' => array(
    "ALTER TABLE {$_TABLES['evlist_events']}
        ADD `tzid` varchar(125) NOT NULL DEFAULT 'local' AFTER options", 
    "ALTER TABLE {$_TABLES['evlist_submissions']}
        ADD `tzid` varchar(125) NOT NULL DEFAULT 'local' AFTER options", 
    "ALTER TABLE {$_TABLES['evlist_detail']}
        CHANGE lat lat float(10,6) default NULL,
        CHANGE lng lng float(10,6) default NULL",
    "UPDATE {$_TABLES['conf_values']} SET type = '%text' WHERE
        name = 'meetup_gid' AND group_name = 'evlist'",
    "ALTER TABLE {$_TABLES['evlist_repeat']}
        ADD rp_start DATETIME, ADD rp_end DATETIME",
    "ALTER TABLE {$_TABLES['evlist_repeat']}
        DROP KEY `start`,
        ADD KEY `start`(rp_start),
        ADD KEY `end`(rp_end)",
    "UPDATE {$_TABLES['evlist_repeat']} SET
        rp_start = CONCAT(rp_date_start, ' ', rp_time_end1),
        rp_end = concat(rp_date_end, ' ', IF (rp_time_end2 > '00:00:00', rp_time_end2, rp_time_end1))",
    ),
'1.4.5' => array(
    "ALTER TABLE {$_TABLES['evlist_calendars']}
        CHANGE cal_id cal_id int(11) not null auto_increment,
        ADD cal_icon varchar(40) default ''",
    "INSERT INTO {$_TABLES['evlist_calendars']} VALUES
        (-1, 'Meetup Events', 1, 0, '#ffffff', '#000000', 2, 13, 3, 3, 2, 2, ''),
        (-2, 'Birthdays', 1, 0, '#ffffff', '#000000', 2, 13, 3, 3, 2, 2, 'birthday-cake')",
    "ALTER TABLE {$_TABLES['evlist_tickets']}
        ADD waitlist tinyint(1) unsigned not null default 0",
    "ALTER TABLE {$_TABLES['evlist_events']}
        CHANGE cal_id cal_id int(10) not null DEFAULT 1",
    ),
'1.4.6' => array(
    "ALTER TABLE {$_TABLES['evlist_calendars']}
        ADD `orderby` int(5) unsigned NOT NULL DEFAULT 9999",
    ),
);
$_SQL['evlist_tickettypes'] = $_EV_UPGRADE['1.3.7'][1];
$_SQL['evlist_cache'] = $_EV_UPGRADE['1.4.0'][0];

?>
