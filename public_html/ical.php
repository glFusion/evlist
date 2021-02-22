<?php
/**
 * ICal export function for the evList plugin.
 *
 * @author      Lee P. Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee P. Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.6
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include core glFusion libraries */
require_once '../lib-common.php';

$View = new Evlist\Views\ical;
$content = $View->Content();

header('Content-Type: text/calendar');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header("Pragma: no-cache");
header('Expires: ' . gmdate ('D, d M Y H:i:s', time()));
echo $content;
exit;
