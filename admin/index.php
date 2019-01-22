<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | index.php                                                                |
// |                                                                          |
// | Administration interface                                                 |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008 by the following authors:                             |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
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
*   Administration entry point for the evList plugin
*   @package    evlist
*/

/** Include glFusion core libraries */
require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!in_array('evlist', $_PLUGINS) || !plugin_ismoderator_evlist()) {
    COM_404();
    exit;
}

/**
*   Create the common header for all admin functions
*
*   @param  string  $page   Current page.  Used for selecting menus
*   @return string      HTML for admin header portion.
*/
function EVLIST_adminHeader($page)
{
    global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_EV_CONF;

    $retval = '';

    USES_lib_admin();

    $menu_arr = array();
    if ($page == 'events') {
        $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?edit=x',
            'text' => $LANG_EVLIST['new_event']);
    } else {
        $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php',
            'text' => $LANG_EVLIST['events']);
    }

    if ($page == 'calendars') {
        $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?editcal=x',
            'text' => $LANG_EVLIST['new_calendar']);
    } else {
        $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?view=calendars',
            'text' => $LANG_EVLIST['calendars']);
    }

    if ($page == 'categories') {
        $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?editcat=x',
            'text' => $LANG_EVLIST['new_category']);
    } else {
        $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?categories=x',
            'text' => $LANG_EVLIST['categories']);
    }

    if ($_EV_CONF['enable_rsvp']) {
        if ($page == 'tickettypes') {
            $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?editticket=0',
                'text' => $LANG_EVLIST['new_ticket_type']);
        } else {
            $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?tickettypes',
                'text' => $LANG_EVLIST['ticket_types']);
        }
    }
    $menu_arr[] = array(
            'url' => EVLIST_ADMIN_URL . '/index.php?import=x',
            'text' => $LANG_EVLIST['import'],
    );
    /*$menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?importcalendar=x',
            'text' => $LANG_EVLIST['import_calendar']);
    $menu_arr[] = array('url' => EVLIST_ADMIN_URL . '/index.php?import=x',
            'text' => $LANG_EVLIST['import_from_csv']);*/

    $menu_arr[] = array('url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home']);

    $retval .= COM_startBlock('evList ' . $_EV_CONF['pi_version'], '',
                              COM_getBlockTemplate('_admin_block', 'header'));
    $retval .= ADMIN_createMenu(
        $menu_arr,
        isset($LANG_EVLIST['admin_instr'][$page]) ? $LANG_EVLIST['admin_instr'][$page] : '',
        plugin_geticon_evlist()
    );
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}


/**
*   Get the list of categories
*
*   @return string      HTML for admin list
*/
function EVLIST_adminlist_categories()
{
    global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

    USES_lib_admin();
    EVLIST_setReturn('admincategories');

    $retval = '';

    $header_arr = array(
        array('text' => $LANG_EVLIST['edit'],
                'field' => 'edit', 'sort' => false,
                'align' => 'center',
        ),
        array('text' => $LANG_EVLIST['id'],
                'field' => 'id', 'sort' => true),
        array('text' => $LANG_EVLIST['cat_name'],
                'field' => 'name', 'sort' => true),
        array('text' => $LANG_EVLIST['enabled'],
                'field' => 'status', 'sort' => false,
                'align' => 'center',
        ),
        array('text' => $LANG_ADMIN['delete'],
                'field' => 'delete', 'sort' => false,
                'align' => 'center',
        ),
    );

    $defsort_arr = array('field' => 'name', 'direction' => 'ASC');

    $text_arr = array('has_menu'     => false,
                      'has_extras'   => false,
                      'title'        => $LANG_EVLIST['pi_title'].': ' .
                                        $LANG_EVLIST['categories'],
                      'form_url'     => EVLIST_ADMIN_URL . '/index.php?categories=x',
                      'help_url'     => ''
    );

    $sql = "SELECT * FROM {$_TABLES['evlist_categories']} WHERE 1=1 ";
    $query_arr = array('table' => 'evlist_categories',
            'sql' => $sql,
            'query_fields' => array('name'),
    );

    $retval .= ADMIN_list('evlist_cat_admin', 'EVLIST_admin_getListField_cat',
            $header_arr, $text_arr, $query_arr, $defsort_arr);
    return $retval;
}


/**
*   Get the list of ticket types
*
*   @return string      HTML for admin list
*/
function EVLIST_adminlist_tickettypes()
{
    global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_EVLIST_HELP, $LANG_ADMIN;

    USES_lib_admin();
    EVLIST_setReturn('admintickettypes');

    $retval = '';

    $header_arr = array(
        array(
            'text' => $LANG_EVLIST['edit'],
            'field' => 'edit',
            'sort' => false,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_EVLIST['id'],
            'field' => 'id',
            'sort' => true,
        ),
        array(
            'text' => $LANG_EVLIST['description'],
            'field' => 'description',
            'sort' => true,
        ),
        array(
            'text' => $LANG_EVLIST['enabled'],
            'field' => 'enabled',
            'sort' => false,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_EVLIST['event_pass'] .
                ' <i class="tooltip uk-icon uk-icon-question-circle" title="' .
                $LANG_EVLIST_HELP['event_pass'] . '"></i>',
            'field' => 'event_pass',
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

    $defsort_arr = array('field' => 'id', 'direction' => 'ASC');

    $text_arr = array('has_menu'     => false,
                      'has_extras'   => false,
                      'title'        => $LANG_EVLIST['pi_title'].': ' .
                                        $LANG_EVLIST['categories'],
                      'form_url'     => EVLIST_ADMIN_URL . '/index.php?view=tickettypes',
                      'help_url'     => ''
    );

    $sql = "SELECT * FROM {$_TABLES['evlist_tickettypes']} WHERE 1=1 ";

    $query_arr = array('table' => 'evlist_tickettypes',
            'sql' => $sql,
            'query_fields' => array('description'),
    );

    $retval .= ADMIN_list('evlist_tickettype_admin', 'EVLIST_admin_getListField_tickettypes',
            $header_arr, $text_arr, $query_arr, $defsort_arr);
    return $retval;
}


/**
*   Get the list of ticket types
*
*   @return string      HTML for admin list
*/
function EVLIST_adminlist_tickets($ev_id, $rp_id = 0)
{
    global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

    USES_lib_admin();
    EVLIST_setReturn('admintickets');

    $retval = '';

    $header_arr = array(
        array('text' => $LANG_EVLIST['id'],
                'field' => 'tick_id', 'sort' => true),
        array('text' => $LANG_EVLIST['registrant'],
                'field' => 'uid', 'sort' => false),
        array('text' => $LANG_EVLIST['fee'],
                'field' => 'fee', 'sort' => false),
        array('text' => $LANG_EVLIST['event_pass'],
                'field' => 'event_pass', 'sort' => false),
        array('text' => $LANG_ADMIN['delete'],
                'field' => 'delete', 'sort' => false,
                'align' => 'center',
        ),
    );

    $defsort_arr = array('field' => 'tick_id', 'direction' => 'ASC');

    $text_arr = array('has_menu'     => false,
                      'has_extras'   => false,
                      'title'        => $LANG_EVLIST['pi_title'].': ' .
                                        $LANG_EVLIST['tickets'],
                      'form_url'     => EVLIST_ADMIN_URL . '/index.php',
                      'help_url'     => ''
    );

    $sql = "SELECT * FROM {$_TABLES['evlist_tickets']} WHERE ev_id='" .
            DB_escapeString($ev_id) . "'";
    if ($rp_id != 0) {
        $sql .= " AND rp_id = " . (int)$rp_id;
    }
    $query_arr = array('table' => 'evlist_tickets',
            'sql' => $sql,
            'query_fields' => array(),
    );

    $retval .= ADMIN_list('evlist_ticket_admin', 'EVLIST_admin_getListField_tickets',
            $header_arr, $text_arr, $query_arr, $defsort_arr);
    return $retval;
}


/**
*   Get the list of events
*
*   @return string      HTML for admin list
*/
function EVLIST_admin_list_events()
{
    global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

    USES_lib_admin();
    EVLIST_setReturn('adminevents');

    $cal_id = isset($_REQUEST['cal_id']) ? (int)$_REQUEST['cal_id'] : 0;
    $retval = '';

    $header_arr = array(
        array('text' => $LANG_EVLIST['edit'],
                'field' => 'edit', 'sort' => false,
                'align' => 'center',
        ),
        array('text' => $LANG_EVLIST['copy'],
                'field' => 'copy', 'sort' => false,
                'align' => 'center',
        ),
        array('text' => $LANG_EVLIST['id'], 'field' => 'id', 'sort' => true),
        array('text' => $LANG_EVLIST['title'],
                'field' => 'title', 'sort' => true),
        array('text' => $LANG_EVLIST['start_date'],
                'field' => 'date_start1', 'sort' => true),
        array('text' => $LANG_EVLIST['enabled'],
                'field' => 'status', 'sort' => false,
                'align' => 'center',
        ),
        array('text' => $LANG_ADMIN['delete'],
                'field' => 'delete', 'sort' => false,
                'align' => 'center',
        ),
    );

    $defsort_arr = array('field' => 'date_start1', 'direction' => 'DESC');
    $options = array(
                'chkdelete' => 'true',
                'chkfield' => 'id',
                'chkname' => 'delevent',
    );
    $text_arr = array('has_menu'     => true,
                      'has_extras'   => true,
                      'title'        => $LANG_EVLIST['pi_title'].': ' .
                                        $LANG_EVLIST['events'],
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
        . Evlist\Calendar::optionList($cal_id) . '</select>';

    $query_arr = array('table' => 'users',
            'sql' => $sql,
            'query_fields' => array('id', 'title', 'summary',
            'full_description', 'location', 'date_start1', 'status')
    );

    $retval .= ADMIN_list('evlist_event_admin', 'EVLIST_admin_getListField', $header_arr, $text_arr,
                    $query_arr, $defsort_arr, $filter, '', $options);
    return $retval;
}



/**
 * Return the display value for a category field.
 *
 * @param   string  $fieldname  Name of the field
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Name-value pairs for all fields
 * @param   array   $icon_arr   Array of system icons
 * @return  string      HTML to display for the field
 */
function EVLIST_admin_getListField_cat($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;

    $retval = '';
    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink(
            '<i class="uk-icon-edit"></i>',
            EVLIST_ADMIN_URL . '/index.php?editcat=x&amp;id=' . $A['id'],
            array(
                'title' => $LANG_ADMIN['edit'],
            )
        );
        break;
    case 'status':
        if ($A['status'] == '1') {
            $switch = EVCHECKED;
            $enabled = 1;
        } else {
            $switch = '';
            $enabled = 0;
        }
        $retval .= "<input type=\"checkbox\" $switch value=\"1\"
                name=\"cat_check\"
                id=\"togenabled{$A['id']}\"
                onclick='EVLIST_toggle(this,\"{$A['id']}\",\"enabled\",".
                '"category","'.EVLIST_ADMIN_URL."\");' />".LB;
        break;
    case 'delete':
        $retval = COM_createLink(
            $EV_CONF['icons']['delete'],
            EVLIST_ADMIN_URL. '/index.php?delcat=x&id=' . $A['id'],
            array('onclick'=>"return confirm('{$LANG_EVLIST['conf_del_item']}');",
                'title' => $LANG_ADMIN['delete'],
            )
        );
        break;
    default:
        $retval = $fieldvalue;
        break;
    }
    return $retval;
}


/**
 * Return the display value for a ticket type field.
 *
 * @param   string  $fieldname  Name of the field
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Name-value pairs for all fields
 * @param   array   $icon_arr   Array of system icons
 * @return  string      HTML to display for the field
 */
function EVLIST_admin_getListField_tickettypes($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_EV_CONF;

    $retval = '';
    switch($fieldname) {
    case 'edit':
        $retval = COM_createLInk(
            '<i class="uk-icon-edit"></i>',
            EVLIST_ADMIN_URL . '/index.php?editticket=' . $A['id'],
            array(
                'title' => $LANG_ADMIN['edit'],
            )
        );
        break;

    case 'enabled':
    case 'event_pass':
        if ($fieldvalue == '1') {
            $switch = EVCHECKED;
            $enabled = 1;
        } else {
            $switch = '';
            $enabled = 0;
        }
        $retval = "<input type=\"checkbox\" $switch value=\"1\"
                name=\"cat_check\"
                id=\"tog{$fieldname}{$A['id']}\"
                onclick='EVLIST_toggle(this,\"{$A['id']}\",\"{$fieldname}\",".
                "\"tickettype\",\"".EVLIST_ADMIN_URL."\");' />".LB;
        break;

    case 'delete':
        if (!Evlist\TicketType::isUsed($A['id'])) {
            $retval = COM_createLink(
                $_EV_CONF['icons']['delete'],
                EVLIST_ADMIN_URL. '/index.php?deltickettype=' . $A['id'],
                array(
                    'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_item']}');",
                    'title' => $LANG_ADMIN['delete'],
                )
            );
        }
        break;

    default:
        $retval = $fieldvalue;
        break;
    }
    return $retval;
}


/**
*   Return the display value for a ticket fields
*
*   @param  string  $fieldname  Name of the field
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Name-value pairs for all fields
*   @param  array   $icon_arr   Array of system icons
*   @return string      HTML to display for the field
*/
function EVLIST_admin_getListField_tickets($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;

    switch($fieldname) {
    case 'event_pass':
        if ($A['rp_id'] == 0) {
            $retval = 'Yes';
        } else {
            $retval = 'No';
        }
        break;
    case 'delete':
        $retval = COM_createLink(
            $_EV_CONF['icons']['delete'],
            EVLIST_ADMIN_URL. '/index.php?delticket=' . $A['id'],
            array(
                'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_item']}');",
                'title' => $LANG_ADMIN['delete'],
            )
        );
        break;
    case 'uid':
        $retval = COM_getDisplayName($fieldvalue);
        break;
    default:
        $retval = $fieldvalue;
        break;
    }
    return $retval;
}


/**
 * Return the display value for an event field.
 *
 * @param   string  $fieldname  Name of the field
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Name-value pairs for all fields
 * @param   array   $icon_arr   Array of system icons
 * @return  string      HTML to display for the field
 */
function EVLIST_admin_getListField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;

    static $del_icon = NULL;
    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink(
            $_EV_CONF['icons']['edit'],
            EVLIST_ADMIN_URL . '/index.php?edit=event&amp;eid=' . $A['id'] . '&from=admin',
            array(
                'title' => $LANG_EVLIST['edit_event'],
            )
        );
        break;
    case 'copy':
        $retval = COM_createLink(
            $_EV_CONF['icons']['copy'],
            EVLIST_URL . '/event.php?clone=x&amp;eid=' . $A['id'],
            array(
                'title' => $LANG_EVLIST['copy'],
            )
        );
        break;
    case 'title':
        $rp_id = Evlist\Repeat::getNearest($A['id']);
        if ($rp_id) {
            $retval = COM_createLink(
                $fieldvalue, EVLIST_URL . '/event.php?eid=' . $rp_id
            );
        } else {
            $retval = $fieldvalue;
        }
        break;
    case 'status':
        if ($A['status'] == '1') {
            $switch = EVCHECKED;
            $enabled = 1;
        } else {
            $switch = '';
            $enabled = 0;
        }
        $retval .= "<input type=\"checkbox\" $switch value=\"1\" name=\"ev_check\"
                id=\"event_{$A['id']}\"
                onclick='EVLIST_toggle(this,\"{$A['id']}\",\"enabled\",".
                '"event","'.EVLIST_ADMIN_URL."\");' />" . LB;
        break;
    case 'delete':
        $url = EVLIST_ADMIN_URL. '/index.php?delevent=x&eid=' . $A['id'];
        if (isset($_REQUEST['cal_id'])) {
            $url .= '&cal_id=' . (int)$_REQUEST['cal_id'];
        }
        $retval = COM_createLink(
            $_EV_CONF['icons']['delete'],
            $url,
            array(
                'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_event']}');",
                'title' => $LANG_ADMIN['delete'],
            )
        );
        break;
    default:
        $retval = $fieldvalue;
        break;
    }
    return $retval;
}


/**
*   Get the admin list of calendars
*
*   @return string  HTML for admin list
*/
function EVLIST_admin_list_calendars()
{
    global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

    USES_lib_admin();

    $retval = '';

    $header_arr = array(
        array(  'text'  => $LANG_EVLIST['edit'],
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
        array('text'    => $LANG_EVLIST['orderby'],
                'field' => 'orderby',
                'sort'  => false,
                'align' => 'center',
            ),
         array(  'text'  => $LANG_EVLIST['id'],
                'field' => 'cal_id',
                'sort'  => false,
            ),
        array(  'text'  => $LANG_EVLIST['title'],
                'field' => 'cal_name',
                'sort'  => false,
            ),
        array(  'text'  => $LANG_EVLIST['enabled'],
                'field' => 'cal_status',
                'sort'  => false,
                'align' => 'center',
            ),
        array(  'text'  => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort'  => 'false',
                'align' => 'center',
            ),
    );

    $defsort_arr = array('field' => 'orderby', 'direction' => 'ASC');
    $text_arr = array('has_menu'     => false,
                      'has_extras'   => false,
                      'title'        => $LANG_EVLIST['pi_title'].': ' .
                                        $LANG_EVLIST['calendars'],
                      'form_url'     => EVLIST_ADMIN_URL . '/index.php?view=calendars',
                      'help_url'     => ''
    );
    $sql = "SELECT * FROM {$_TABLES['evlist_calendars']} WHERE 1=1 ";
    $query_arr = array('table' => 'evlist_calendars',
            'sql' => $sql,
            'query_fields' => array('id', 'cal_name',),
    );
    $retval .= ADMIN_list('evlist_cal_admin', 'EVLIST_admin_field_calendars',
                $header_arr, $text_arr, $query_arr, $defsort_arr);
    return $retval;
}


/**
 * Return the display value for a calendar field.
 *
 * @param   string  $fieldname  Name of the field
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Name-value pairs for all fields
 * @param   array   $icon_arr   Array of system icons
 * @return  string      HTML to display for the field
 */
function EVLIST_admin_field_calendars($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;

    $retval = '';
    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink(
            $_EV_CONF['icons']['edit'],
            EVLIST_ADMIN_URL . '/index.php?editcal=' . $A['cal_id'],
            array(
                'title' => $LANG_EVLIST['edit_calendar'],
            )
        );
        break;
    case 'orderby':
        $retval = COM_createLink(
            $_EV_CONF['icons']['arrow-up'],
            EVLIST_ADMIN_URL . '/index.php?movecal=up&id=' . $A['cal_id'],
        );
        $retval .= COM_createLink(
            $_EV_CONF['icons']['arrow-down'],
            EVLIST_ADMIN_URL . '/index.php?movecal=down&id=' . $A['cal_id']
        );
        break;
    case 'cal_status':
        if ($fieldvalue == '1') {
            $switch = EVCHECKED;
            $enabled = 1;
        } else {
            $switch = '';
            $enabled = 0;
        }
        $retval = "<input type=\"checkbox\" $switch value=\"1\" name=\"cal_check\"
                id=\"togenabled{$A['cal_id']}\"
                onclick='EVLIST_toggle(this,\"{$A['cal_id']}\",\"enabled\",".
                '"calendar","'.EVLIST_ADMIN_URL."\");' />".LB;
        break;
    case 'delete':
        if ($A['cal_id'] > 1) {
            $retval = COM_createLink(
                $_EV_CONF['icons']['delete'],
                EVLIST_ADMIN_URL. '/index.php?deletecal=x&id=' . $A['cal_id'],
                array(
                    'onclick'=>"return confirm('{$LANG_EVLIST['conf_del_item']}');",
                )
            );
        }
        break;
    case 'cal_name':
        $retval = '<span style="color:' . $A['fgcolor'] . ';background-color:' . $A['bgcolor'] .
            ';">' . $fieldvalue;
        if (isset($A['cal_icon']) && !empty($A['cal_icon'])) {
            $retval .= '&nbsp;<i class="uk-icon uk-icon-' . $A['cal_icon'] . '"></i>';
        }
        $retval .= '</span>';
        break;
    default:
        $retval = $fieldvalue;
        break;
    }
    return $retval;
}


/**
*   Import events from a CSV file into the database.
*
*   @return string      Completion message
*/
function EVLIST_importCSV()
{
    global $_CONF, $_TABLES, $LANG_EVLIST, $_USER;

    // Setting this to true will cause import to print processing status to
    // webpage and to the error.log file
    $verbose_import = true;

    $retval = '';

    // First, upload the file
    USES_class_upload();

    $upload = new upload ();
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
            $retval = $LANG_EVLIST['err_invalid_import'];
            return $retval;
        }
    } else {
        // A problem occurred, print debug information
        $retval .= $upload->printErrors(false);
        return $retval;
    }

    $fp = fopen($filename, 'r');
    if (!$fp) {
        $retval = $LANG_EVLIST['err_invalid_import'];
        return $retval;
    }
    $success = 0;
    $failures = 0;

    // Set owner_id to the current user and group_id to the default
    $owner_id = (int)$_USER['uid'];
    if ($owner_id < 2) $owner_id = 2;   // last resort, use Admin
    $group_id = (int)DB_getItem($_TABLES['groups'],
            'grp_id', 'grp_name="evList Admin"');
    if ($group_id < 2) $group_id = 2;  // last resort, use Root

    while (($event = fgetcsv($fp)) !== false) {
        $Ev = new Evlist\Event();
        $Ev->isNew = true;
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
            'time_start2'   => '00:00:00',
            'time_end2'     => '00:00:00',
            'owner_id'      => $owner_id,
            'group_id'      => $group_id,
        );

        if ($_CONF['hour_mode'] == 12) {
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
        }
        if ($A['time_start1'] == '00:00:00' && $A['time_end1'] == '00:00:00') {
            $A['allday'] = 1;
        } else {
            $A['allday'] = 0;
        }
        $msg = $Ev->Save($A);
        if (empty($msg)) {
            $successes++;
        } else {
            $failures++;
        }
    }

    return "$successes Succeeded<br />$failures Failed";
}


/*
*   Main function
*/
$expected = array(
    // Actions to perform
    'savecal', 'editcal', 'moderate', 'saveevent', 'saverepeat',
    'deletecal', 'delcalconfirm', 'approve', 'disapprove',
    'categories', 'updateallcats', 'delcat', 'savecat',
    'saveticket', 'deltickettype', 'delticket', 'printtickets',
    'tickreset_x', 'tickdelete_x', 'exporttickets',
    'import_csv', 'import_cal', 'movecal',
    'delbutton_x',
    // Views to display
    'view', 'delevent', 'importcalendar', 'clone', 'rsvp',
    'import', 'edit', 'editcat', 'editticket', 'tickettypes',
    'tickets',
);
$action = 'view';
$actionval = '';
$view = '';
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}

if (isset($_REQUEST['msg'])){
    $msg = COM_applyFilter($_REQUEST['msg'], true);
} else {
    $msg = '';
}
$content = '';

switch ($action) {
case 'edit':
    $view = 'edit';
    break;

case 'tickdelete_x':
    if (is_array($_POST['delrsvp'])) {
        Evlist\Ticket::Delete($_POST['delrsvp']);
    }
    COM_refresh($_CONF['site_url'] . '/evlist/event.php?eid=' . $_POST['ev_id']);
    exit;

case 'tickreset_x':
    if (is_array($_POST['delrsvp'])) {
        Evlist\Ticket::Reset($_POST['delrsvp']);
    }
    COM_refresh($_CONF['site_url'] . '/evlist/event.php?eid=' . $_POST['ev_id']);
    exit;

case 'delcalconfirm':
    $view = 'calendars';
    if (!isset($_POST['confirmdel']) || $_POST['confirmdel'] != '1') {
        break;
    }
    $cal_id = isset($_POST['cal_id']) ? (int)$_POST['cal_id'] : 0;
    if ($cal_id < 1) break;
    $newcal = isset($_POST['newcal']) ? (int)$_POST['newcal'] : 0;
    $Cal = new Evlist\Calendar($cal_id);
    $Cal->Delete($newcal);
    break;

case 'saveevent':
    $eid = isset($_POST['eid']) && !empty($_POST['eid']) ? $_POST['eid'] : '';
    $table = empty($eid) ? 'evlist_submissions' : 'evlist_events';
    $Ev = new Evlist\Event($eid);
    $errors = $Ev->Save($_POST, $table);
    if (!empty($errors)) {
        $content .= '<span class="alert"><ul>' . $errors . '</ul></span>';
        $content .= $Ev->Edit();
        $view = 'none';
    } else {
        $view = 'home';
        if ($Ev->table == 'evlist_submissions') {
            COM_setMsg($LANG_EVLIST['messages'][9]);
        } else {
            COM_setMsg($LANG_EVLIST['messages'][2]);
        }
        echo COM_refresh(EVLIST_ADMIN_URL . '/index.php');
    }
    break;

case 'saverepeat':
    $rp_id = isset($_POST['rp_id']) && !empty($_POST['rp_id']) ? $_POST['rp_id'] : '';
    $Rp = new Evlist\Repeat($rp_id);
    $errors = $Rp->Save($_POST);
    if (!empty($errors)) {
        $content .= '<span class="alert"><ul>' . $errors . '</ul></span>';
        $content .= $Rp->Edit();
        $view = 'none';
    } else {
        COM_setMsg($LANG_EVLIST['messages'][2]);
        echo COM_refresh(EVLIST_ADMIN_URL . '/index.php');
    }
    break;

case 'savecal':
    $cal_id = isset($_POST['cal_id']) ? $_POST['cal_id'] : 0;
    $Cal = new Evlist\Calendar($cal_id);
    $status = $Cal->Save($_POST);
    $view = 'calendars';
    break;

case 'savecat':
    $C = new Evlist\Category($_POST['id']);
    $status = $C->Save($_POST);
    $view = 'categories';
    break;

case 'saveticket':
    if ($_EV_CONF['enable_rsvp']) {
        $C = new Evlist\TicketType($_POST['id']);
        $status = $C->Save($_POST);
        $view = 'tickettypes';
    } else {
        $view = '';
    }
    break;

case 'delcat':
    $cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($cat_id > 0) {
        Evlist\Category::Delete($cat_id);
    }
    $view = 'categories';
    break;

case 'delbutton_x':
    if (isset($_POST['delevent']) && is_array($_POST['delevent'])) {
        foreach ($_POST['delevent'] as $eid) {
            Evlist\Event::Delete($eid);
        }
    }
    $view = 'events';
    break;

case 'delevent':
    $eid = isset($_REQUEST['eid']) && !empty($_REQUEST['eid']) ?
            $_REQUEST['eid'] : '';
    if ($eid != '') {
        Evlist\Event::Delete($eid);
    }
    $view = 'events';
    break;

case 'disapprove';
    // Delete a submission.  We'll just do this manually since there's
    // not much to it.
    $id = isset($_POST['eid']) ? COM_sanitizeId($_POST['eid']) : '';
    if ($id != '') {
        DB_delete($_TABLES['evlist_submissions'], 'id', $id);
        DB_delete($_TABLES['evlist_detail'], 'ev_id', $id);
        DB_delete($_TABLES['evlist_lookup'], 'eid', $id);
    }
    echo COM_refresh($_CONF['site_admin_url'].'/moderation.php');
    exit;
    break;

case 'approve':
    // Invoke the core moderation approval functions.
    // It'd be nice if the MODERATE functions weren't in moderate.php
    $id = isset($_POST['eid']) ? COM_sanitizeId($_POST['eid']) : '';
    if ($id != '') {
        list($key, $table, $fields, $submissiontable) =
            plugin_moderationvalues_evlist();
        DB_copy($table,$fields,$fields,$submissiontable,$key,$id);
        plugin_moderationapprove_evlist($id);
    }
    echo COM_refresh($_CONF['site_admin_url'].'/moderation.php');
    exit;
    break;

case 'view':
    $view = $actionval;
    break;

case 'delrsvp':
    if (is_array($_POST['delrsvp'])) {
        foreach ($_POST['delrsvp'] as $rsvp_id) {
            DB_delete($_TABLES['evlist_rsvp'], 'rsvp_id', $rsvp_id);
        }
    }
    $view = 'rsvp';
    break;

case 'import_cal':
    require_once EVLIST_PI_PATH . '/calendar_import.php';
    $errors = evlist_import_calendar_events();
    if ($errors == -1) {
        $content .= COM_showMessageText($LANG_EVLIST['err_cal_notavail'],
                '', true);
    } elseif ($errors > 0) {
        $content .= COM_showMessageText(
                sprintf($LANG_EVLIST['err_cal_import'], $errors), '', true);
    }
    break;

case 'import_csv':
    // Import events from CSV file
    $status = EVLIST_importCSV();
    $content .= COM_showMessageText($status, '', true, 'error');
    $view = '';
    break;

case 'printtickets':
    // Print all tickets for an event, for all users
    if ($_EV_CONF['enable_rsvp']) {
        $eid = COM_sanitizeID($_GET['eid'], false);
        $doc = Evlist\Ticket::PrintTickets($eid);
        echo $doc;
        exit;
    } else {
        $content .= 'Function not available';
    }
    break;

case 'exporttickets':
    // Print all tickets for an event, for all users
    if ($_EV_CONF['enable_rsvp']) {
        $eid = COM_sanitizeID($_GET['eid'], false);
        $doc = Evlist\Ticket::ExportTickets($eid);
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="event-'.$eid.'.csv');
        echo $doc;
        exit;
    } else {
        $content .= 'Function not available';
    }
    break;

case 'movecal':
    Evlist\Calendar::moveRow($_GET['id'], $actionval);
    echo COM_refresh(EVLIST_ADMIN_URL . '/index.php?view=calendars');
    break;

default:
    $view = $action;
    break;
}

$page = $view;      // Default for menu creation
switch ($view) {
case 'deletecal':
    $cal_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    if ($cal_id < 1) break;
    $Cal = new Evlist\Calendar($cal_id);
    $content .= $Cal->DeleteForm();
    break;

case 'editcal':
    $Cal = new Evlist\Calendar($actionval);
    $content .= $Cal->Edit();
    break;

case 'calendars':
    $content .= EVLIST_admin_list_calendars();
    break;

case 'moderate':
    $Ev = new Evlist\Event();
    $Ev->Read($_REQUEST['id'], 'evlist_submissions');
    $content .= $Ev->Edit('', 0, 'moderate');
    break;

case 'categories':
    $content .= EVLIST_adminlist_categories();
    break;

case 'tickettypes':
    if ($_EV_CONF['enable_rsvp']) {
        $content .= EVLIST_adminlist_tickettypes();
    }
    break;

case 'tickets':
    $ev_id = isset($_GET['ev_id']) ? $_GET['ev_id'] : '';
    $content .= EVLIST_adminlist_tickets($ev_id);
    break;

case 'editcat':
    $C = new Evlist\Category($_GET['id']);
    $content .= $C->Edit();
    break;

case 'editticket':
    if ($_EV_CONF['enable_rsvp']) {
        $Tic = new Evlist\TicketType($actionval);
        $content .= $Tic->Edit();
    }
    break;

case 'rsvp':
    USES_evlist_functions();
    $rp_id = isset($_POST['rp_id']) && !empty($_POST['rp_id']) ?
            $_POST['rp_id'] :
            isset($_GET['rp_id']) && !empty($_GET['rp_id']) ?
            $_GET['rp_id'] : 0;
    if ($rp_id > 0) {
        $content .= EVLIST_adminRSVP($rp_id);
    }
    break;

case 'import':
    $T = new Template(EVLIST_PI_PATH . '/templates/');
    $T->set_file(array(
        'form'  => 'import.thtml',
        'instr' => 'import_csv_instr.thtml',
    ) );
    $T->parse('import_csv_instr', 'instr');
    $T->parse('output', 'form');
    $content .= $T->finish($T->get_var('output'));
    break;

case 'edit':
    $eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
    $Ev = new Evlist\Event($eid);
    $rp_id = isset($_POST['rp_id']) && !empty($_POST['rp_id']) ? $_POST['rp_id'] : '';
    $content .= $Ev->Edit('', $rp_id, 'save'.$actionval);
    break;

default:
    $content .= EVLIST_admin_list_events();
    $page = 'events';
    break;
}

$display = COM_siteHeader();

if (!empty($msg)) {
    //msg block
    $display .= COM_startBlock($LANG_EVLIST['messages'][6],'','blockheader-message.thtml');
    $display .= $LANG_EVLIST['messages'][$msg];
    $display .= COM_endBlock('blockfooter-message.thtml');
}

$display .= EVLIST_adminHeader($page);
$display .= $content;
$display .= COM_siteFooter();

echo $display;

?>
