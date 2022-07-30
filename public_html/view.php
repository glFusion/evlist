<?php
/**
 * View the detail of an event or repeat.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010 - 2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion libraries */
require_once '../lib-common.php';

if (!in_array('evlist', $_PLUGINS)) {
    COM_404();
    exit;
}

// Check if the current user can even view the calendar
if (!EVLIST_canView()) {
    $display = Evlist\Menu::siteHeader();
    $display .= SEC_loginRequiredForm();
    $display .= Evlist\Menu::siteFooter();
    echo $display;
    exit;
}

COM_setArgNames(array('rid', 'eid', 'page'));
$id = COM_getArgument('rid');
$eid = COM_getArgument('eid');
$page = COM_getArgument('page');
$pagetitle = '';        // Default to empty page title
$content = '';

// Get the system message, if any.  There should only be a message if our
// next action is a view, since an action might override the message value.
// We need message queueing!
if (isset($_GET['msg'])) {
    $msg = COM_applyFilter($_GET['msg'], true);
} else {
    $msg = '';
}

if (!empty($id)) {
    $view = 'instance';
} elseif (!empty($eid)) {
    $view = 'event';
} else {
    $view = '';
}

// Passed in via $_GET, not using getArgument()
$query = isset($_GET['query']) ? $_GET['query'] : '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'nested';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

switch ($view) {
case 'event':
    // Given an event ID, get the nearest instance to display
    $id = Evlist\Repeat::getNearest($eid);
    if (!$id) {
        echo COM_refresh(EVLIST_URL . '/index.php');
    }
case 'instance':
    $View = new Evlist\Views\Occurrence($id);
    if (!$View->getRepeat()->canView()) {
        COM_setMsg($LANG_EVLIST['ev_not_found']);
        echo COM_refresh(EVLIST_URL . '/index.php');
    }
    $pagetitle = $View->getRepeat()->getTitle();
    $content .= $View->withQuery($query)
                     ->withTemplate('')
                     ->withCommentMode($mode)
                     ->withCommentOrder($order)
                     ->withReferer()
                     ->Render();
    break;
}

$display = Evlist\Menu::siteHeader($pagetitle);
if (!empty($msg)) {
    $display .= COM_startBlock($LANG_EVLIST['alert'],'','blockheader-message.thtml');
    $display .= $LANG_EVLIST['messages'][$msg];
    $display .= COM_endBlock('blockfooter-message.thtml');
}
$display .= $content;
$display .= Evlist\Menu::siteFooter();
echo $display;
