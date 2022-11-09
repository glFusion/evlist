<?php
/**
 * Class to import events from the Calendar plugin or a CSV file
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Util;
use glFusion\Database\Database;
use glFusion\Log\Log;
use Evlist\Event;


/**
 * Class for imports from Calendar or CSV files.
 * @package evlist
 */
class Import
{
    /**
     * Import events from a CSV file into the database.
     *
     * @return  array   Array of (dups, errors, successes)
     */
    public static function do_csv() : array
    {
        global $_CONF, $_TABLES, $LANG_EVLIST, $_USER;

        // Setting this to true will cause import to print processing status to
        // webpage and to the error.log file
        $verbose_import = true;

        $retval = array(
            'errors' => 0,
            'dups' => 0,
            'successes' => 0,
            'message' => '',    // verbatim error message to return
        );

        if (!isset($_FILES['importfile']) || empty($_FILES['importfile']['name'])) {
            $retval['message'] = 'No file uploaded';
            return $retval;
        }
        // First, upload the file
        USES_class_upload();

        $upload = new \upload();
        $upload->setPath ($_CONF['path_data']);
        $upload->setAllowedMimeTypes(array(
            'text/plain' => '.txt, .csv',
            'application/octet-stream' => '.txt, .csv',
        ) );
        $upload->setFileNames('evlist_import_file.txt');
        $upload->setFieldName('importfile');
        if ($upload->uploadFiles()) {
            // Good, file got uploaded, now install everything
            $filename = $_CONF['path_data'] . 'evlist_import_file.txt';
            if (!file_exists($filename)) { // empty upload form
                $retval['message'] = $LANG_EVLIST['err_invalid_import'];
                return $retval;
            }
        } else {
            // A problem occurred, print debug information
            $retval['message'] = $upload->printErrors(false);
            return $retval;
        }

        $fp = fopen($filename, 'r');
        if (!$fp) {
            $retval['message'] = $LANG_EVLIST['err_invalid_import'];
            return $retval;
        }

        // Set owner_id to the current user and group_id to the default
        $owner_id = (int)$_USER['uid'];
        if ($owner_id < 2) $owner_id = 2;   // last resort, use Admin
        $group_id = (int)Database::getInstance()->getItem(
            $_TABLES['groups'],
            'grp_id',
            array('grp_name'),
            array('evList Admin')
        );
        if ($group_id < 2) $group_id = 2;  // last resort, use Root

        while (($event = fgetcsv($fp)) !== false) {
            $Ev = new Event();
            $i = 0;
            $A = array(
                'date_start1'   => $event[$i++],
                'date_end1'     => $event[$i++],
                'time_start1'   => $event[$i++],
                'time_end1'     => $event[$i++],
                'title'         => $event[$i++],
                'summary'       => $event[$i++],
                'full_description' => $event[$i++],
                'url'           => $event[$i++],
                'location'      => $event[$i++],
                'street'        => $event[$i++],
                'city'          => $event[$i++],
                'province'      => $event[$i++],
                'country'       => $event[$i++],
                'postal'        => $event[$i++],
                'contact'       => $event[$i++],
                'email'         => $event[$i++],
                'phone'         => $event[$i++],

                'cal_id'        => 1,
                'status'        => 1,
                'hits'          => 0,
                'recurring'     => 0,
                'split'         => 0,
                'time_start2'   => '00:00',
                'time_end2'     => '00:00',
                'owner_id'      => $owner_id,
                'group_id'      => $group_id,
            );

            /*if ($_CONF['hour_mode'] == 12) {
                list($hour, $minute, $second) = explode(':', $A['time_start1']);
                if ($hour > 12) {
                    $hour -= 12;
                    $am = 'pm';
                } elseif ($hour == 0) {
                    $hour = 12;
                    $am = 'am';
                } else {
                    $am = 'am';
                }
                $A['start1_ampm'] = $am;
                $A['starthour1'] = $hour;
                $A['startminute1'] = $minute;

                list($hour, $minute, $second) = explode(':', $A['time_end1']);
                if ($hour > 12) {
                    $hour -= 12;
                    $am = 'pm';
                } elseif ($hour == 0) {
                    $hour = 12;
                    $am = 'am';
                } else {
                    $am = 'am';
                }
                $A['end1_ampm'] = $am;
                $A['endhour1'] = $hour;
                $A['endminute1'] = $minute;
            }*/
            if (
                substr($A['time_start1'], 0, 5) == '00:00' &&
                substr($A['time_end1'], 0, 5) == '00:00'
            ) {
                $A['allday'] = 1;
            } else {
                $A['allday'] = 0;
            }
            $status = $Ev->Save($A);
            if (empty($msg)) {
                $retval['successes']++;
            } else {
                $retval['errors']++;
            }
        }
        return $retval;
    }

    
    /**
     * Import data from the Calendar plugin into evList.
     *   This function checks that the event ID isn't already in use to avoid
     *   re-importing events.
     *
     * @author      Mark R. Evans mark AT glfusion DOT org
     * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
     * @return  array   Array of (added, dups, errors)
     */
    public static function do_calendar() : array
    {
        global $_TABLES, $LANG_EVLIST;

        $retval = array(
            'errors' => 0,
            'dups' => 0,
            'successes' => 0,
        );
        if (!isset($_TABLES['events']) || empty($_TABLES['events'])) {
            // Calendar plugin not available
            $retval['errors'] = -1;
            return $retval;
        }

        $db = Database::getInstance();
        try {
            $stmt = $db->conn->executeQuery("SELECT * FROM {$_TABLES['events']}");
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                if (empty($A['timestart'])) $A['timestart'] = '00:00:00';
                list($s_hour, $s_min, $s_sec) = explode(':', $A['timestart']);
                if (empty($A['timeend'])) $A['timeend'] = '00:00:00';
                list($e_hour, $e_min, $e_sec) = explode(':', $A['timeend']);
                $s_ampm = $s_hour == 0 || $s_hour > 12 ? 'pm' : 'am';
                $e_ampm = $e_hour == 0 || $e_hour > 12 ? 'pm' : 'am';

                $E = array(
                    'ev_id'         => $A['eid'],
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
                    'cal_id'        => $_POST['dest_cal'],
                    'recurring'     => 0,
                );

                // We'll let the event object handle most things, saving the 
                // event and detail records.

                // Create the event object, while checking if the eid exists
                $Ev = new Event($A['eid']);
                if ($Ev->getID() != '') {    // Oops, dup ID, must already be done.
                    $retval['dups']++;
                    Log::write('system', Log::DEBUG, "{$A['eid']} - already exists");
                    continue;           // Skip possible duplicates
                }

                // Force it to be a new event even though we have an event ID
                $status = $Ev->forceNew()->Save($E);
                if (!$status) {
                    Log::write('system', Log::ERROR,
                        sprintf($LANG_EVLIST['err_import_event'], $A['eid']) .
                        var_dump($Ev->getErrors(),true)
                    );
                    $retval['errors']++;
                    continue;       // This one failed, keep trying the others
                } else {
                    $retval['successes']++;
                }

                // PITA, but perms don't get updated right by Save().  We can do this
                // or convert them to form-style checkbox values before saving. This
                // seems simpler
                try {
                    $db->conn->update(
                        $_TABLES['evlist_events'],
                        array(
                            'perm_owner'    => $E['perm_owner'],
                            'perm_group'    => $E['perm_members'],
                            'perm_members'  => $E['perm_anon'],
                            'perm_anon'     => $E['perm_anon'],
                        ),
                        array('id' => $Ev->getID()),
                        array(
                            Database::INTEGER,
                            Database::INTEGER,
                            Database::INTEGER,
                            Database::INTEGER,
                            Database::STRING,
                        )
                    );
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                }
            }
        }
        return $retval;
    }


    /**
     * Create the import form.
     *
     * @return  string      HTML for import form
     */
    public static function showForm() : string
    {
        global $_TABLES;

        $T = new \Template(EVLIST_PI_PATH . '/templates/');
        $T->set_file(array(
            'form'  => 'import.thtml',
            'instr' => 'import_csv_instr.thtml',
        ) );
        $T->set_var(array(
            'cal_options' => COM_optionList(
                $_TABLES['evlist_calendars'],
                'cal_id,cal_name',
                1
            ),
        ) );
        $T->parse('import_csv_instr', 'instr');
        $T->parse('output', 'form');
        return $T->finish($T->get_var('output'));
    }

}

