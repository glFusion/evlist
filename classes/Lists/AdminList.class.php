<?php
/**
 * Class to manage event lists for the EvList plugin.
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
use Evlist\Models\Status;
use Evlist\Repeat;
use Evlist\Calendar;
use Evlist\FieldList;


/**
 * Class for event records.
 * @package evlist
 */
class AdminList
{

    /**
     * Get the admin list of events.
     *
     * @return  string      HTML for admin list
     */
    public static function Render()
    {
        global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

        USES_lib_admin();
        EVLIST_setReturn('adminevents');

        $cal_id = isset($_REQUEST['cal_id']) ? (int)$_REQUEST['cal_id'] : 0;
        $retval = '';

        $header_arr = array(
            array(
                'text' => $LANG_EVLIST['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_EVLIST['copy'],
                'field' => 'copy',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_EVLIST['id'],
                'field' => 'id',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['title'],
                'field' => 'title',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['start_date'],
                'field' => 'date_start1',
                'sort' => true,
                'nowrap' => true,
            ),
            array(
                'text' => 'Repeats',
                'field' => 'repeats',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_EVLIST['enabled'],
                'field' => 'status',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
            ),
        );

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
            'has_menu'     => true,
            'has_extras'   => true,
            'form_url'     => EVLIST_ADMIN_URL . '/index.php?cal_id=' . $cal_id,
            'help_url'     => ''
        );

        // Select distinct to get only one entry per event.  We can only edit/modify
        // events here, not repeats
        $sql = "SELECT DISTINCT(ev.id), det.title, ev.date_start1, ev.status
                FROM {$_TABLES['evlist_events']} ev
                LEFT JOIN {$_TABLES['evlist_detail']} det
                    ON det.ev_id = ev.id
                WHERE ev.det_id = det.det_id ";
        if ($cal_id != 0) {
            $sql .= "AND cal_id = $cal_id";
        }

        $filter = $LANG_EVLIST['calendar']
            . ': <select name="cal_id" onchange="this.form.submit()">'
            . '<option value="0">' . $LANG_EVLIST['all_calendars'] . '</option>'
            . Calendar::optionList($cal_id) . '</select>';

        $query_arr = array(
            'table' => 'users',
            'sql' => $sql,
            'query_fields' => array(
                'id', 'title', 'summary',
                'full_description', 'location', 'date_start1', 'status',
            )
        );

        $retval .= COM_createLink(
            FieldList::button(array(
                'text' => $LANG_EVLIST['new_event'],
                'style' => 'success',
            ) ),
            EVLIST_ADMIN_URL . '/index.php?edit=x'
        );

        $extra = array(
            'is_admin' => true,
        );

        $retval .= ADMIN_list(
            'evlist_event_admin',
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr,
            $query_arr, $defsort_arr, $filter, $extra, $options
        );
        return $retval;
    }
    
    
    /**
     * Return the display value for a field in the admin list.
     *
     * @param   string  $fieldname  Name of the field
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Name-value pairs for all fields
     * @param   array   $icon_arr   Array of system icons
     * @param   array   $extra      Extra verbatim values passed in
     * @return  string      HTML to display for the field
     */
    public static function getAdminField($fieldname, $fieldvalue, $A, $icon_arr, $extra)
    {
        global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;
        static $del_icon = NULL;
        $retval = '';

        switch($fieldname) {
        case 'edit':
            $retval = FieldList::edit(array(
                'url' => EVLIST_ADMIN_URL . '/index.php?edit=event&amp;eid=' . $A['id'] . '&from=admin',
                array(
                    'title' => $LANG_EVLIST['edit_event'],
                ),
            ) );
            break;
        case 'copy':
            $retval = FieldList::copy(array(
                'url' => EVLIST_URL . '/event.php?clone=x&amp;eid=' . $A['id'],
                array(
                    'title' => $LANG_EVLIST['copy'],
                ),
            ) );
            break;
        case 'title':
            $retval = COM_createLink(
                $fieldvalue,
                COM_buildUrl(
                    EVLIST_URL . '/event.php?view=event&eid=' . $A['id']
                )
            );
            if ($A['status'] == '2') {
                $retval = '<span class="event_disabled">' . $retval . '</span>';
            }
            break;
        case 'status':
            $base_url = $extra['is_admin'] ? EVLIST_ADMIN_URL : EVLIST_URL;
            $fieldvalue = (int)$fieldvalue;
            $retval = FieldList::select(array(
                'name' => 'status[' . $A['id'] . ']',
                'onchange' => "EVLIST_updateStatus(this, 'repeat', '{$A['id']}', '{$fieldvalue}', '$base_url');",
                'options' => array(
                    $LANG_EVLIST['enabled'] => array(
                        'value' => '1',
                        'selected' => (1 == $fieldvalue),
                    ),
                    $LANG_EVLIST['cancelled'] => array(
                        'value' => '2',
                        'selected' => (2 == $fieldvalue),
                    ),
                    $LANG_EVLIST['disabled'] => array(
                        'value' => '0',
                        'selected' => (0 == $fieldvalue),
                    ),
                ),
            ) );
            break;
        case 'repeats':
            $retval = FieldList::repeat(array(
                'url' => EVLIST_ADMIN_URL . '/index.php?repeats=x&eid=' . $A['id'],
            ) );
            break;
        case 'delete':
            // Enabled events get cancelled, others get immediately deleted.
            $url = EVLIST_ADMIN_URL. '/index.php?delevent=x&eid=' . $A['id'];
            if (isset($_REQUEST['cal_id'])) {
                $url .= '&cal_id=' . (int)$_REQUEST['cal_id'];
            }
            $retval = FieldList::delete(array(
                'delete_url' => $url,
                array(
                    'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_event']}');",
                    'title' => $LANG_ADMIN['delete'],
                    'class' => 'tooltip',
                ),
            ) );
            break;
        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }

}
