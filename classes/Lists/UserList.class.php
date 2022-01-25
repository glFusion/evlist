<?php
/**
 * Class to manage user-facing event lists for the EvList plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Lists;


/**
 * Create the "My Events" view for user-submitted events.
 * @package evlist
 */
class UserList extends AdminList
{

    /**
     * Get the list of events owned by the current user
     *
     * @return  string      HTML for admin list
     */
    public static function Render()
    {
        global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN, $_USER;

        USES_lib_admin();
        EVLIST_setReturn('myevents');
        
        $retval = '';

        $header_arr = array();

        // Allow editing if the queue is not used or this is an autorized
        // submitter.
        if (EVLIST_skipqueue()) {
            $header_arr[] = array(
                'text'  => $LANG_EVLIST['edit'],
                'field' => 'edit', 'sort' => false,
                'align' => 'center',
            );
        }
        $header_arr[] = array(
            'text'  => $LANG_EVLIST['id'],
            'field' => 'id',
            'sort'  => true,
        );
        $header_arr[] = array(
            'text'  => $LANG_EVLIST['title'],
            'field' => 'title',
            'sort'  => true,
        );
        $header_arr[] = array(
            'text'  => $LANG_EVLIST['start_date'],
            'field' => 'date_start1',
            'sort'  => true,
        );
        $header_arr[] = array(
            'text'  => $LANG_EVLIST['enabled'],
            'field' => 'status',
            'sort'  => false,
            'align' => 'center',
        );
        if (!$_CONF['storysubmission'] || plugin_ismoderator_evlist()) {
            $header_arr[] = array(
                'text'  => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort'  => false,
                'align' => 'center',
            );
        }

        $defsort_arr = array(
            'field' => 'date_start1',
            'direction' => 'DESC',
        );
        $options = array(
            'chkdelete' => 'true',
            'chkfield' => 'id',
            'chkname' => 'delevent',
        );
        $text_arr = array(
            'has_menu'  => true,
            'has_extras'=> true,
            'title'     => $LANG_EVLIST['pi_title'].': ' . $LANG_EVLIST['events'],
            'form_url'  => EVLIST_URL . '/index.php?view=myevents',
            'help_url'  => '',
        );

        // Select distinct to get only one entry per event.  We can only edit/modify
        // events here, not repeats
        $sql = "SELECT DISTINCT(ev.id) as id, det.title, ev.date_start1, ev.status
            FROM {$_TABLES['evlist_events']} ev
            LEFT JOIN {$_TABLES['evlist_detail']} det
                ON det.ev_id = ev.id
            WHERE owner_id = " . (int)$_USER['uid'] .
            " AND ev.det_id = det.det_id ";
        $query_arr = array(
            'table' => 'users',
            'sql' => $sql,
            'query_fields' => array('id', 'title', 'summary',
            'full_description', 'location', 'date_start1', 'status'),
        );
        $extra = array(
            'is_admin' => false,
        );

        $retval .= ADMIN_list(
            'evlist_event_user',
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            '', $extra, $options
        );
        return $retval;
    }

}
