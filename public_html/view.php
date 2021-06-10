<?php
/**
 * View the detail of an event or repeat.
 *
 * @author      Mark R. Evans mark AT glfusion DOT org
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

// If global loginrequired is set, override the plugin's setting
if ($_CONF['loginrequired'] == 1) $_EV_CONF['allow_anon_view'] = '0';
if (COM_isAnonUser() && $_EV_CONF['allow_anon_view'] != '1') {
    $display = EVLIST_siteHeader();
    $display .= SEC_loginRequiredForm();
    $display .= EVLIST_siteFooter();
    echo $display;
    exit;
}

COM_setArgNames(array('rid', 'eid'));
$id = COM_getArgument('rid');
$eid = COM_getArgument('eid');

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
        COM_refresh($EVLIST_URL . '/index.php');
    }
case 'instance':
    $Rep = Evlist\Repeat::getInstance($id);
    if ($Rep->getID() == 0 || !$Rep->getEvent()->hasAccess(2)) {
        COM_setMsg($LANG_EVLIST['ev_not_found']);
        echo COM_refresh(EVLIST_URL . '/index.php');
        exit;
    }
    $pagetitle = COM_stripslashes($Rep->getEvent()->getDetail()->getTitle());
    $content .= $Rep->withQuery($query)
                    ->withTemplate('')
                    ->withCommentMode($mode)
                    ->withCommentOrder($order)
                    ->Render();
    break;
}

$display = Evlist\Menu::siteHeader($pagetitle);
$V = Evlist\View::getView('detail');
$display .= $V->Header();

if (!empty($msg)) {
    $display .= COM_startBlock($LANG_EVLIST['alert'],'','blockheader-message.thtml');
    $display .= $LANG_EVLIST['messages'][$msg];
    $display .= COM_endBlock('blockfooter-message.thtml');
}

$display .= $content;
$display .= Evlist\Menu::siteFooter();
echo $display;
