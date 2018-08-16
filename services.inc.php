<?php
/**
*   Web service functions for the EvList plugin.
*   Handles ticket purchases.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2015-2018 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.6
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own!');
}


/**
*   Get information about a specific item.
*
*   @param  array   $args       Item Info (pi_name, item_type, item_id)
*   @param  array   &$output    Array of output data
*   @param  string  &$svc_msg   Unused
*   @return integer     Return value
*/
function service_productinfo_evlist($args, &$output, &$svc_msg)
{
    $retval = PLG_RET_OK;

    $item = EV_getVar($args, 'item_id', 'array');
    $product_id = $item[0];
    $item_id = isset($item[1]) ? $item[1] : '';

    // Create a return array with values to be populated later.
    // The actual paypal product ID is evlist:type:id
    if (empty($item_id)) return PLG_RET_ERROR;
    $output = array(
        'product_id' => 'evlist:' . $product_id . ':' . $item_id,
        'name' => 'Unknown',
        'short_description' => 'Unknown Evlist Item',
        'price' => '0.00',
    );

    switch ($product_id) {
    case 'eventfee':
        $item_parts = explode('/', $item_id);
        $ev_id = $item_parts[0];
        $tick_type = isset($item_parts[1]) ? $item_parts[1] : 0;
        $rp_id = isset($item_parts[2]) ? $item_parts[2] : 0;
        if ($tick_type == 0) {
            return PLG_RET_ERROR;
        }
        $Tick = new Evlist\TicketType($tick_type);
        $Ev = new Evlist\Event($ev_id);
        if (isset($Ev->options['tickets'][$tick_type])) {
            $fee = (float)$Ev->options['tickets'][$tick_type]['fee'];
        } else {
            $fee = 0;
        }

        $short_desc = $Tick->description. ': ' . $Ev->Detail->title;
        if ($rp_id > 0) {
            $Ev = new Evlist\Repeat($rp_id);
            if ($Ev->rp_id == $rp_id) { // valid repeat ID
                $short_desc .=
                    ', ' . $Ev->date_start . ' ' . $Ev->time_start1;
            } else {
                return PLG_RET_ERROR;
            }
        }
        $output['name'] = $short_desc;
        $output['short_description'] = $short_desc;
        $output['price'] = (float)$fee;
        break;
    }
    return $retval;
}


/**
*   Handle the purchase of a product via IPN message.
*
*   @param  array   $args       Array of item and IPN data
*   @param  array   &$output    Return array
*   @param  string  &$svc_msg   Unused
*   @return integer     Return value
*/
function service_handlePurchase_evlist($args, &$output, &$svc_msg)
{
    global $_TABLES, $LANG_PHOTO, $_CONF;

    $item = EV_getVar($args, 'item', 'array');
    $paypal_data = EV_getVar($args, 'ipn_data', 'array');
    $item_id = EV_getVar($item, 'item_id');
    $item_id = explode(':', $item_id);
    $quantity = EV_getVar($item, 'quantity', 'int');

    // Must have an item ID following the plugin name
    if (!is_array($item_id) || !isset($item_id[1]))
        return PLG_RET_ERROR;

    // Initialize the output array
    $output = array(
            'product_id' => $item['item_id'],
            'name' => $item['name'],
            'short_description' => $item['name'],
            'price' => (float)$item['price'],
            'expiration' => NULL,
            'download' => 0,
            'file' => '',
    );

    $custom = EV_getVar($paypal_data, 'custom', 'array');
    $uid = EV_getVar($custom, 'uid', 'int', 1);

    // Initialize an array of payment info to log
    $pmt_info = array(
        'type'          => 'payment',
        'payment_date'  => $paypal_data['sql_date'],
        'txn_id'        => $paypal_data['txn_id'],
        'amount'        => (float)$item['price'],
    );

    switch ($item_id[1]) {
    case 'eventfee':
        // Get event, ticket_type and repeat ID
        $item_parts = explode('/', $item_id[2]);
        if (count($item_parts) < 3) {
            return PLG_RET_ERROR;
        }
        $ev_id = $item_parts[0];
        $tick_type = $item_parts[1];
        $rp_id = $item_parts[2];

        $TickType = new Evlist\TicketType($tick_type);
        $repeats = array();
        if ($rp_id > 0) {
            $repeats[] = $rp_id;
            // Ticket to a single occurrence
            $Rp = new Evlist\Repeat($rp_id);
            $Ev = $Rp->Event;
            $dt_info = $Rp->start_date1 . ' ' . $Rp->start_time1;
        } else {
            // rp_id = 0, make sure it's an event pass
            if ($TickType->event_pass) {
                $Ev = new Evlist\Event($ev_id);
                $dt_info = $Ev->date_start1 . ' ' . $Ev->time_start1;
            } else {
                return PLG_RET_ERROR;
            }
        }
        $ev_fee = (float)$Ev->options['tickets'][$tick_type]['fee'];

        $output['price'] = $ev_fee;
        $output['name'] = $TickType->description . ': ' . $Ev->Detail->title .
                ', ' . $dt_info;
        $output['short_description'] = $output['name'];

        // TODO: fix to handle qty > 1, need loop and calc per-item pmt amt.
        $unpaid = Evlist\Ticket::MarkPaid($quantity, $ev_id, $rp_id, $uid);
        if ($unpaid < 0) {
            EVLIST_Log("ALERT: $quantity tickets paid for user $uid for event $ev_id, exceeds unpaid count by $unpaid");
        } else {
            EVLIST_Log("$quantity tickets paid for user $uid, event $ev_id/$rp_id");
        }
        break;
    }
    return PLG_RET_OK;
}


/**
*   Handle a product refund
*
*   @param  array   $args       Array of item and IPN data
*   @param  array   &$output    Return array
*   @param  string  &$svc_msg   Unused
*   @return integer     Return value
*/
function service_handleRefund_evlist($args, &$output, &$svc_msg)
{
    global $_TABLES;

    $item = $args['item'];      // array of item number info
    $paypal_data = $args['ipn_data'];

    // Must have an item ID following the plugin name
    if (!is_array($item) || !isset($item[1]))
        return PLG_RET_ERROR;

    // User ID is provided in the 'custom' field, so make sure it's numeric.
    if (is_numeric($paypal_data['custom']['uid']))
        $uid = (int)$paypal_data['custom']['uid'];
    else
        $uid = 1;

    switch ($item[1]) {
    case 'eventfee':
        // Handle the refund of an event fee.  Only type handled for now

        if (isset($item[2]) && is_numeric($item[2])) {
            $event_id = (int)$item[2];
        } else {
            $event_id = 0;
        }

        if ($event_id < 1 || $uid < 2) {
            return PLG_RET_ERROR;
        }

        DB_delete($_TABLES['evlist_payments'],
            array('uid', 'event_id', 'section_id', 'entry_id'),
            array($uid, $event_id, 0, 0));
        DB_query("UPDATE {$_TABLES['evlist_entry']}
                SET paid = 0
                WHERE uid = $uid AND eventID = $event_id");
        break;
    }
    return PLG_RET_OK;
}

?>
