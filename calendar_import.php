<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | calendar_import.php                                                      |
// |                                                                          |
// | Import existing calendar entries into evList                             |
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
*   Import data from the Calendar plugin into evList
*   @author     Mark R. Evans mark AT glfusion DOT org
*   @copyright  Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** Import the event class */
USES_evlist_class_event();

/**
*   Import events from the Calendar plugin to evList.
*   This function checks that the event ID isn't already in use to avoid
*   re-importing events.
*
*   @return integer     0 = success, -1 = event table missing, >0 = error count
*/
function evlist_import_calendar_events()
{
    global $_TABLES, $LANG_EVLIST;

    if (!isset($_TABLES['events']) || empty($_TABLES['events'])) {
        return -1;
    }

    $errors = 0;        // Keep track of errors

    $result = DB_query("SELECT * FROM {$_TABLES['events']}", 1);

    while ($A = DB_fetchArray($result, false)) {
        if (empty($A['timestart'])) $A['timestart'] = '00:00:00';
        list($s_hour, $s_min, $s_sec) = explode(':', $A['timestart']);
        if (empty($A['timeend'])) $A['timeend'] = '00:00:00';
        list($e_hour, $e_min, $e_sec) = explode(':', $A['timeend']);
        $s_ampm = $s_hour == 0 || $s_hour > 12 ? 'pm' : 'am';
        $e_ampm = $e_hour == 0 || $e_hour > 12 ? 'pm' : 'am';

        $E = array(
                'eid'           => $A['eid'],
                'title'         => $A['title'],
                'summary'       => $A['description'],
                'full_description' => '',
                'date_start1'   => $A['datestart'],
                'date_end1'     => $A['dateend'],
                'starthour1'    => $s_hour,
                'startminute1'  => $s_min,
                'start_ampm'    => $s_ampm,
                'endhour1'      => $e_hour,
                'endminute1'    => $e_min,
                'end_ampm'      => $e_ampm,
                'url'           => $A['url'],
                'street'        => $A['address1'],
                'city'          => $A['city'],
                'province'      => $A['state'],
                'postal'        => $A['zipcode'],
                'allday'        => $A['allday'] == 1 ? 1 : 0,
                'location'      => $A['location'],
                'owner_id'      => (int)$A['owner_id'],
                'group_id'      => (int)$A['group_id'],
                'perm_owner'    => (int)$A['perm_owner'],
                'perm_group'    => (int)$A['perm_group'],
                'perm_members'  => (int)$A['perm_members'],
                'perm_anon'     => (int)$A['perm_anon'],
                'show_upcoming' => 1,
                'status'        => $A['status'] == 1 ? 1 : 0,
                'hits'          => (int)$A['hits'],
                'cal_id'        => 1,
        );

        // We'll let the event object handle most things, saving the 
        // event and detail records.

        // Create the event object, while checking if the eid exists
        $Ev = new evEvent($A['eid']);
        if ($Ev->id != '')      // Oops, dup ID, must already be done.
            continue;           // Skip possible duplicates

        // Force it to be a new event even though we have an event ID
        $Ev->isNew = true;
        if ($Ev->Save($E, 'evlist_events', true) !== '') {
            COM_errorLog(sprintf($LANG_EVLIST['err_import_event'], $A['eid']));
            $errors++;
            continue;       // This one failed, keep trying the others
        }

        // PITA, but perms don't get updated right by Save().  We can do this
        // or convert them to form-style checkbox values before saving. This
        // seems simpler
        $sql = "UPDATE {$_TABLES['evlist_events']} SET
                    perm_owner   = '{$E['perm_owner']}',
                    perm_group   = '{$E['perm_members']}',
                    perm_members = '{$E['perm_anon']}',
                    perm_anon    = '{$E['perm_anon']}'
                WHERE id='{$Ev->id}'";
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            $error = 1;
            continue;
        }
    }

    return $errors;
}

?>
