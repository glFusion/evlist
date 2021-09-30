<?php
/**
 * Import data from the Calendar plugin into evList.
 *
 * @author      Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

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
            'time_start1'   => $s_hour . ':' . $s_min,
            'time_end1'     => $e_hour . ':' . $e_min,
            //'starthour1'    => $s_hour,
            //'startminute1'  => $s_min,
            'start1_ampm'   => $s_ampm,
            //'endhour1'      => $e_hour,
            //'endminute1'    => $e_min,
            'end1_ampm'     => $e_ampm,
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
            'recurring'     => 0,
        );

        // We'll let the event object handle most things, saving the 
        // event and detail records.

        // Create the event object, while checking if the eid exists
        $Ev = new Evlist\Event($A['eid']);
        if ($Ev->getID() != '') {    // Oops, dup ID, must already be done.
            COM_errorLog("{$A['eid']} - already exists");
            continue;           // Skip possible duplicates
        }

        // Force it to be a new event even though we have an event ID
        if ($Ev->forceNew()->Save($E) !== '') {
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
                WHERE id='{$Ev->getID()}'";
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("{$A['eid']} - Unknow DB error: $sql");
            $errors++;
            continue;
        }
    }
    return $errors;
}
