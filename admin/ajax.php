<?php
/**
*   Common AJAX functions.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

// This is for administrators only.  It's called by Javascript,
// so don't try to display a message
if (!SEC_hasRights('evlist.admin')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the evlist admin ajax function.");
    exit;
}

switch ($_GET['action']) {
case 'toggle':
    switch ($_GET['component']) {
    case 'category':
        USES_evlist_class_category();

        switch ($_GET['type']) {
        case 'enabled':
            $newval = evCategory::toggleEnabled($_REQUEST['oldval'], $_REQUEST['id']);
            break;

         default:
            exit;
        }

        /*header('Content-Type: text/xml');
        header("Cache-Control: no-cache, must-revalidate");
        //A date in the past
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        echo '<?xml version="1.0" encoding="ISO-8859-1"?>
        <info>'. "\n";
        echo "<newval>$newval</newval>\n";
        echo "<id>{$_REQUEST['id']}</id>\n";
        echo "<type>{$_REQUEST['type']}</type>\n";
        echo "<component>{$_REQUEST['component']}</component>\n";
        echo "<baseurl>" . EVLIST_ADMIN_URL . "</baseurl>\n";
        echo "</info>\n";*/
        break;

    case 'calendar':
        USES_evlist_class_calendar();

        switch ($_GET['type']) {
        case 'enabled':
            $newval = evCalendar::toggleEnabled($_REQUEST['oldval'], $_REQUEST['id']);
            break;

         default:
            exit;
        }

        /*header('Content-Type: text/xml');
        header("Cache-Control: no-cache, must-revalidate");
        //A date in the past
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        echo '<?xml version="1.0" encoding="ISO-8859-1"?>
        <info>'. "\n";
        echo "<newval>$newval</newval>\n";
        echo "<id>{$_REQUEST['id']}</id>\n";
        echo "<type>{$_REQUEST['type']}</type>\n";
        echo "<component>{$_REQUEST['component']}</component>\n";
        echo "<baseurl>" . EVLIST_ADMIN_URL . "</baseurl>\n";
        echo "</info>\n";*/
        break;

    case 'event':
        USES_evlist_class_event();

        switch ($_GET['type']) {
        case 'enabled':
            $newval = evEvent::toggleEnabled($_REQUEST['oldval'], $_REQUEST['id']);
            break;

         default:
            exit;
        }

        /*header('Content-Type: text/xml');
        header("Cache-Control: no-cache, must-revalidate");
        //A date in the past
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        echo '<?xml version="1.0" encoding="ISO-8859-1"?>
        <info>'. "\n";
        echo "<newval>$newval</newval>\n";
        echo "<id>{$_REQUEST['id']}</id>\n";
        echo "<type>{$_REQUEST['type']}</type>\n";
        echo "<component>{$_REQUEST['component']}</component>\n";
        echo "<baseurl>" . EVLIST_ADMIN_URL . "</baseurl>\n";
        echo "</info>\n";*/
        break;

    default:
        exit;
    }

        header('Content-Type: text/xml');
        header("Cache-Control: no-cache, must-revalidate");
        //A date in the past
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        echo '<?xml version="1.0" encoding="ISO-8859-1"?>
        <info>'. "\n";
        echo "<newval>$newval</newval>\n";
        echo "<id>{$_REQUEST['id']}</id>\n";
        echo "<type>{$_REQUEST['type']}</type>\n";
        echo "<component>{$_REQUEST['component']}</component>\n";
        echo "<baseurl>" . EVLIST_ADMIN_URL . "</baseurl>\n";
        echo "</info>\n";
        break;


}

?>
