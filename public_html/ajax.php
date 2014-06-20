<?php
/**
*   Common AJAX functions.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Include required glFusion common functions */
require_once '../lib-common.php';

$content = '';

switch ($_GET['action']) {
case 'getloc':
    // Create an array to return so the javascript won't choke.
    $B = array(
        'id'        => '',
        'title'     => '',
        'street'    => '',
        'city'      => '',
        'province'  => '',
        'country'   => '',
        'postal'    => '',
        'lat'       => '',
        'lng'       => '',
    );

    if ($_EV_CONF['use_locator'] && function_exists('GEO_getInfo')) {

        $id = isset($_GET['id']) && !empty($_GET['id']) ? 
                    COM_sanitizeID($_GET['id']) : '';
        $A = GEO_getInfo($id);
        if (!$A) {
            $A = $B;        // Use the default, empty array
            $A['id'] = $id;
        }
        // Now form the XML return
        foreach ($B as $name=>$value) {
            if (isset($A[$name])) {
                $value = $A[$name];
            }
            $content .= "<{$name}>" . 
                htmlspecialchars($value) . 
                "</{$name}>\n";
        }
    }
    break;
}

if (!empty($content)) {
    $content = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" .
        '<location>' . $content . "</location>\n";
    header('Content-Type: text/xml');
    header("Cache-Control: no-cache, must-revalidate");
    //A date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    echo $content;
}
?>
