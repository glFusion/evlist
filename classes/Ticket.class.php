<?php
/**
*   Class to manage tickets and registrations
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2015-2016 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;

/**
*   Class for event tickets
*   @package evlist
*/
class Ticket
{
    var $properties = array();

    /**
    *   Constructor
    *   Create an empty ticket type object, or read an existing one
    *
    *   @param  string  $tic_id     Ticket ID to load, or empty string
    */
    public function __construct($tic_id = '')
    {
        $this->tic_id       = $tic_id;
        $this->tic_type     = '';
        $this->ev_id        = '';
        $this->rp_id        = 1;
        $this->fee          = 0;
        $this->paid         = 0;
        $this->uid          = 0;
        $this->used         = 0;
        $this->dt           = NULL;
        if ($this->tic_id != '') {
            $this->Read($this->tic_id);
        }
    }


    /**
    *   Read an existing ticket record into this object
    *
    *   @param  string  $tic_id Optional ticket ID, $this->id used if empty
    */
    public function Read($tic_id = '')
    {
        global $_TABLES;

        if ($tic_id != '')
            $this->tic_id = $tic_id;

        $sql = "SELECT * FROM {$_TABLES['evlist_tickets']}
            WHERE tic_id='{$this->tic_id}'";
        //echo $sql;
        $result = DB_query($sql);

        if (!$result || DB_numRows($result) == 0) {
            $this->tic_id = '';
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->SetVars($row, true);
            return true;
        }
    }


    public function __set($key, $value)
    {
        switch ($key) {
        case 'uid':
        case 'rp_id':
        case 'tic_type':
        case 'used':
        case 'dt':
            $this->properties[$key] = (int)$value;
            break;

        case 'fee':
        case 'paid':
            $this->properties[$key] = (float)$value;
            break;

        case 'tic_id':
        case 'ev_id':
            $this->properties[$key] = trim($value);
            break;
        }
    }


    /**
    *   Get the value of a property.
    *   Emulates the behaviour of __get() function in PHP 5.
    *
    *   @param  string  $var    Name of property to retrieve.
    *   @return mixed           Value of property, NULL if undefined.
    */
    public function __get($key)
    {
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        } else {
            return NULL;
        }
    }


    /**
    *   Set the value of all variables from an array, either DB or a form
    *
    *   @param  array   $A      Array of fields
    *   @param  boolean $fromDB True if $A is from the database, false for form
    */
    public function SetVars($A)
    {
        $this->tic_id = $A['tic_id'];
        $this->tic_type = $A['tic_type'];
        $this->ev_id = $A['ev_id'];
        $this->rp_id = $A['rp_id'];
        $this->uid = $A['uid'];
        $this->fee = $A['fee'];
        $this->paid = $A['paid'];
        $this->used = $A['used'];
        $this->dt = $A['dt'];
    }


    /**
    *   Create a unique ticket ID
    *
    *   @param  array   $A      Array of values, non-indexed
    *   @return string          Ticket ID
    */
    public static function MakeTicketId($A = array())
    {
        global $_EV_CONF;

        // Make sure a default format is defined if not in the config
        if (strstr($_EV_CONF['ticket_format'], '%s') === false) {
            $_EV_CONF['ticket_format'] = 'EV%s';
        }

        // md5 makes a long value to put in a qrcode url.
        // makeSid() should be sufficient since it includes some
        // random characters.
        return sprintf($_EV_CONF['ticket_format'], COM_makeSid());
    }


    /**
    *   Create a ticket.
    *
    *   @param  string  $ev_id  Event ID (required)
    *   @param  integer $rp_id  Optional Repeat ID, 0 for event pass
    *   @param  integer $type   Type of ticket, from the ticket_types table
    *   @param  float   $fee    Optional Ticket Fee, default = 0 (free)
    *   @param  integer $uid    Optional User ID, default = current user
    *   @return     string  Ticket identifier
    */
    public function Create($ev_id, $type, $rp_id = 0, $fee = 0, $uid = 0)
    {
        global $_TABLES, $_EV_CONF, $_USER;

        $uid = (int)$uid;
        if ($uid == 0) $uid = (int)$_USER['uid'];
        $rp_id = (int)$rp_id;
        $fee = (float)$fee;
        $type = (int)$type;

        $tic_id = self::MakeTicketId(array($ev_id, $rp_id, $fee, $uid));

        $sql = "INSERT INTO {$_TABLES['evlist_tickets']} SET
            tic_id = '" . DB_escapeString($tic_id) . "',
            tic_type = $type,
            ev_id = '" . DB_escapeString($ev_id) . "',
            rp_id = $rp_id,
            fee = $fee,
            paid = 0,
            uid = $uid,
            used = 0,
            dt = UNIX_TIMESTAMP()";

        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            return $tic_id;
        } else {
            return NULL;
        }
    }   // function Create()


    /**
    *   Save the current ticket.
    *
    *   @return     string  Ticket identifier
    */
    public function Save()
    {
        global $_TABLES, $_EV_CONF, $_USER;

        if ($this->uid == 0) $this->uid = $_USER['uid'];
        $rp_id = (int)$rp_id;
        $fee = (float)$fee;
        $type = (int)$type;

        if ($this->tic_id == '') {
            $this->tic_id = self::MakeTicketId(
                array($this->ev_id, $this->rp_id, $this->fee, $this->uid)
            );
            $sql1 = "INSERT INTO {$_TABLES['evlist_tickets']} SET
                tic_id = '" . DB_escapeString($this->tic_id) . "',
                dt = UNIX_TIMESTAMP(), ";
            $sql3 = '';
        } else {
            $sql1 = "UPDATE {$_TABLES['evlist_tickets']} SET ";
            $sql3 = " WHERE tic_id = '{$this->tic_id}'";
        }

        $sql2 = "tic_type = {$this->tic_type},
            ev_id = '" . DB_escapeString($this->ev_id) . "',
            rp_id = {$this->rp_id},
            fee = {$this->fee},
            paid = {$this->paid},
            uid = {$this->uid},
            used = {$this->used}";

        $sql = $sql1 . $sql2 . $sql3;
        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            return $tic_id;
        } else {
            return NULL;
        }
    }   // function Create()


    /**
    *   Deletes the current or specified ticket.
    *
    *   @param  integer $id     ID of ticket to delete
    */
    public function Delete($id='')
    {
        global $_TABLES;

        if ($id == '' && is_object($this)) {
            $id = $this->tic_id;
        }
        if (is_array($id)) {
            foreach ($id as $idx=>$tic_id) {
                $id[$idx] = DB_escapeString($tic_id);
            }
            $where = "IN ('" . implode("','", $id) . "')";
        } else {
            $id = DB_escapeString($id);
            $where = "= '$id'";
        }
        $sql = "DELETE FROM {$_TABLES['evlist_tickets']} WHERE tic_id $where";
        DB_query($sql);
        EVLIST_log("Deleted tickets $where");
    }


    /**
    *   Resets the "used" field in the ticket
    *
    *   @param  integer $id     ID of ticket reset
    */
    public static function Reset($id='')
    {
        global $_TABLES;

        if (is_array($id)) {
            foreach ($id as $idx=>$tic_id) {
                $id[$idx] = DB_escapeString($tic_id);
            }
            $where = "IN ('" . implode("','", $id) . "')";
        } else {
            $id = DB_escapeString($id);
            $where = "= '$id'";
        }
        $sql = "UPDATE {$_TABLES['evlist_tickets']}
                SET used = 0 WHERE tic_id $where";
        DB_query($sql);
        EVLIST_log("Reset usage for tickets $where");
    }


    /**
    *   Get all the tickets into objects for an event or repeat
    *   Can get all tickets for an event, a single occurrence, or all for
    *   a single user. Must have at lest one search parameter or an
    *   empty array is returned.
    *
    *   @param  string  $ev_id      Event ID
    *   @param  integer $rp_id      Repeat ID, 0 for all occurrences
    *   @param  integer $uid        User ID, 0 for all users
    *   @param  string  $paid       'paid', 'unpaid', or empty for all
    *   @return array       Array of Ticket objects, indexed by ID
    */
    public static function GetTickets($ev_id, $rp_id = 0, $uid = 0, $paid='')
    {
        global $_TABLES;

        $tickets = array();

        $ev_id = DB_escapeString($ev_id);
        $rp_id = (int)$rp_id;
        $uid = (int)$uid;
        $where = array('1 = 1');    // Initialize in case of no other clauses
        if ($ev_id != '') {
            $where[] = "ev_id = '$ev_id'";
        }
        if ($rp_id > 0) {
            $where[] = "rp_id = $rp_id";
        }
        if ($uid > 0) {
            // for a user printing their own tickets
            $where[] = "uid = $uid";
        }
        if ($paid == 'paid') {
            $where[] = "paid >= fee";
        } elseif ($paid == 'unpaid') {
            $where[] = "paid < fee";
        }

        if (!empty($where)) {
            $sql_where = implode(' AND ', $where);
            $sql = "SELECT * FROM {$_TABLES['evlist_tickets']} WHERE $sql_where
                    ORDER BY dt ASC";
            $res = DB_query($sql, 1);
            while ($A = DB_fetchArray($res, false)) {
                // create empty objects and use SetVars to save DB lookups
                $tickets[$A['tic_id']] = new Ticket();
                $tickets[$A['tic_id']]->SetVars($A);
            }
        }
        return $tickets;
    }


    /**
    *   Print tickets as PDF documents
    *   Tickets can be printed for an event, a single occurrence,
    *   or all tickets for a user ID.
    *
    *   @param  string  $ev_id  Event ID
    *   @param  integer $rp_id  Repeat ID
    *   @param  integer $uid    User ID
    *   @return string          PDF Document containing tickets
    */
    public static function PrintTickets($ev_id='', $rp_id=0, $uid=0)
    {
        global $_CONF, $_USER, $LANG_EVLIST, $_PLUGINS;

        $Event = new Event($ev_id);

        // Verify that the current user is an admin or event owner to print
        // all tickets, otherwise only print the user's tickets.
        if (!$Event->hasAccess(3) && $uid == 0) {
            $uid = $_USER['uid'];
        }

        $checkin_url = $_CONF['site_admin_url'] . '/plugins/evlist/checkin.php?tic=';

        // get the tickets, paid and unpaid. Need event id and uid.
        $tickets = self::GetTickets($ev_id, $rp_id, $uid);

        // The PDF functions in lgLib are a recent addition. Make sure that
        // the lgLib version supports PDF creation since we can't yet check
        // the lglib version during installation
        if (empty($tickets) || !in_array('lglib', $_PLUGINS)) {
            return "There are no tickets available to print";
        }
        USES_lglib_class_fpdf();

        $ev_id = NULL;

        // create params array for qrcode, if used
        $params = array('module_size'=>5);

        $pdf = new \FPDF();
        $pdf->SetLeftMargin(20);
        $pdf->AddPage();

        $tic_types = array();
        $counter = 0;
        foreach ($tickets as $tic_id=>$ticket) {
            if (!isset($tick_types[$ticket->tic_type])) {
                $tick_types[$ticket->tic_type] = new TicketType($ticket->tic_type);
            }

            // If we don't already have the event info, get it and construct
            // the address string
            if ($ev_id != $ticket->ev_id) {
                $Ev = new Event($ticket->ev_id);
                $ev_id = $Ev->id;
                $addr = array();
                if ($Ev->Detail->street != '') $addr[] = $Ev->Detail->street;
                if ($Ev->Detail->city != '') $addr[] = $Ev->Detail->city;
                if ($Ev->Detail->province != '') $addr[] = $Ev->Detail->province;
                if ($Ev->Detail->country != '') $addr[] = $Ev->Detail->country;
                if ($Ev->Detail->postal != '') $addr[] = $Ev->Detail->postal;
                $address = implode(' ', $addr);
            }

            // Get the repeat(s) for the ticket(s) to print a ticket for each
            // occurrence.
            $repeats = Repeat::GetRepeats($ticket->ev_id, $ticket->rp_id);
            if (empty($repeats)) return;

            foreach ($repeats as $rp_id => $event) {
                $counter++;
                if ($counter > 3) {     // Print up to 3 tickets per page
                    $pdf->AddPage();
                    $counter = 1;
                }

                $ev_date = $event->date_start;
                $ev_time = $event->time_start1 . ' - ' . $event->time_end1;
                if (!empty($event->time_start2)) {
                    $ev_time .= '; ' . $event->time_start1 . ' - ' . $event->time_end2;
                }

                $fee = self::formatAmount($ticket->fee);

                // Get the veritcal position of the current ticket
                // for positioning the qrcode
                $y = $pdf->GetY();

                // Title
                $pdf->SetFont('Times','B',12);
                $pdf->Cell(130,10, "{$tick_types[$ticket->tic_type]->description}: {$Ev->Detail->title}",1,0,'C');
                $pdf->Ln(13);

                $pdf->SetFont('Times','',12);
                $pdf->SetX(-40);
                $pdf->Cell(0, 30, $LANG_EVLIST['fee'] . ': '. $fee);
                if ($ticket->fee > 0) {
                        $pdf->Ln(5);
                    if ($ticket->paid >= $ticket->fee) {
                        $pdf->SetX(-40);
                        $pdf->Cell(0, 30, $LANG_EVLIST['paid']);
                    } else {
                        $pdf->SetX(-55);
                        $due = $ticket->fee - $ticket->paid;
                        $pdf->Cell(0, 30, $LANG_EVLIST['balance_due'] . ': ' . self::formatAmount($due));
                    }
                }

                $pdf->SetX(20);
                $pdf->Cell(0, 8, $LANG_EVLIST['date'] . ': ', 0, 0);
                $pdf->setX(40);
                $pdf->Cell(0, 8, $ev_date, 0, 1);
                $pdf->Cell(0, 6, $LANG_EVLIST['time'] . ': ', 0, 0);
                $pdf->SetX(40);
                $pdf->Cell(0, 6, $ev_time, 0, 1);

                $addr_line = 0;
                if ($Ev->Detail->location != '') {
                    $pdf->Ln(5);
                    $pdf->Cell(0, 2, $LANG_EVLIST['where'] . ': ', 0, 0);
                    $pdf->SetX(40);
                    $pdf->Cell(0, 2, $Ev->Detail->location, 0, 1);
                    $addr_line = 4;
                }
                if (!empty($address)) {
                    if ($Ev->Detail->location == '') {
                        $pdf->Ln(5);
                        $pdf->Cell(0, 2, $LANG_EVLIST['where'] . ': ', 0, 0);
                    }
                    $pdf->Ln($addr_line);
                    $pdf->SetX(40);
                    $pdf->Cell(0, 2, $address, 0, 1);
                }

                // Footer
                $pdf->Ln(6);
                $pdf->setFont('Times', 'I', 10);
                $pdf->Cell(0,10, $_CONF['site_name'], 0, 0);
                $pdf->Ln(6);
                $pdf->Cell(0,10, $ticket->tic_id);

                // print qrcode if possible
                $params['data'] = $checkin_url . $tic_id . '&rp=' . $rp_id;
                $qrc_status = LGLIB_invokeService('qrcode', 'getcode', $params, $qrcode, $svc_msg);
                if ($qrc_status == PLG_RET_OK) {
                    $fileinfo = pathinfo($qrcode['img']);
                    $pdf->SetX(-40);
                    $pdf->Image($qrcode['path'], null, $y, 25, 0, $fileinfo['extension']);
                }
                $pdf->Ln();

                $y = $pdf->GetY();
                $pdf->Line(10,$y,200,$y);
                $pdf->Ln();
            }
        }
        $pdf->Output();
    }   // end func PrintTickets()


    /**
    *   Export tickets to a CSV file for a single occurrence
    *
    *   @param  integer $rp_id  Repeat ID
    *   @return string  CSV file containing all tickets
    */
    public static function ExportTickets($rp_id='')
    {
        global $_CONF, $LANG_EVLIST;

        $retval = '';

        $Rp = new Repeat($rp_id);
        // Verify that the current is an admin or event owner
        if (!$Rp->Event->hasAccess(3)) {
            return $retval;
        }

        // get the tickets, paid and unpaid. Need event id and uid.
        $tickets = self::GetTickets($ev_id, $rp_id, $uid);

        $header = array(
            $LANG_EVLIST['ticket_num'],
            $LANG_EVLIST['rsvp_date'],
            $LANG_EVLIST['name'],
            $LANG_EVLIST['fee'],
            $LANG_EVLIST['paid'],
            $LANG_EVLIST['date_used'],
            $LANG_EVLIST['waitlisted'],
        );
        $retval .= '"' . implode('","', $header) . '"' . "\n";

        $counter = 0;
        // For display, use the site timezone
        $dt_tick = new \Date('now', $_CONF['timezone']);
        $dt_used = new \Date('now', $_CONF['timezone']);
        foreach ($tickets as $tic_id=>$ticket) {
            $counter++;
            $dt_tick->setTimestamp($ticket->dt);
            $dt_used->setTimestamp($ticket->used);

            $values = array(
                $tic_id,
                $dt_tick->toMySQL(),
                str_replace('"', "'", COM_getDisplayName($ticket->uid)),
                $ticket->fee,
                $ticket->paid,
                $ticket->used > $ticket->dt ? $dt_used->toMySQL(true): '',
            );
            if ($Rp->Event->options['max_rsvp'] > 0) {
                $is_waitlisted = ($counter > $Rp->Event->options['max_rsvp']) ? 'Yes': 'No';
            } else {
                $is_waitlisted = 'N/A';
            }
            $values[] = $is_waitlisted;
            $retval .= '"' . implode('","', $values) . '"' . "\n";
        }
        return $retval;
    }   // end func ExportTickets()


    /**
    *   Check in a user at the event.
    *   This is meant to work with the qrcode and a smartphone and is
    *   called from admin/plugins/evlist/checkin.php.
    *   Each ticket should have a ticket ID and occurrence ID in the URL.
    *
    *   @param  integer $rp_id  Occurrence ID.
    *   @return integer Zero on success, Message ID on failure
    */
    public function Checkin($rp_id)
    {
        global $_TABLES;

        // Check that the ticket hasn't already been used
        if ($this->used > 0) return 51;
        if ($this->fee > 0 && $this->paid < $this->fee) return 50;

        // Record the current timestamp in the DB
        $this->used = time();
        $sql = "UPDATE {$_TABLES['evlist_tickets']}
            SET used = {$this->used}
            WHERE tic_id = '{$this->tic_id}'";
        /*$sql = "INSERT INTO {$_TABLES['evlist_tickets_used']} SET
                tic_id = '" . DB_escapeString($this->tic_id) . "',
                rp_id = {$rp_id},
                used = UNIX_TIMESTAMP()";*/
        DB_query($sql, 1);
        return DB_error() ? 51 : 0;
    }


    /**
    *   Adds a payment amount to the ticket record.
    *
    *   @param  string  $tick_id    ID of ticket to update
    *   @param  float   $amt        Amount paid
    */
    public static function AddPayment($tick_id, $amt)
    {
        global $_TABLES;

        // Use US floating point format for MySQL
        $amt = number_format((float)$amt, 2, '.', '');
        $tick_id = DB_escapeString($tick_id);
        $sql = "UPDATE {$_TABLES['evlist_tickets']}
                SET paid = paid + $amt
                WHERE tic_id = '$tick_id'";
        DB_query($sql, 1);
    }


    /*
    *   Format a money amount.
    *   Calls on the Paypal plugin to format according to the selected
    *   currency, and falls back to COM_numberFormat() if Paypal isn't
    *   available.
    *
    *   @param  float   $amount     Amount to format
    *   @param  string  $default    Default if amount is zero, '0.00' if empty
    *   @return string      Formatted money string with currency specifier
    */
    public static function formatAmount($amount, $default=NULL)
    {
        if ($amount > 0) {
            $status = LGLIB_invokeService('paypal', 'formatAmount',
                    array('amount' => $amount), $output, $msg);
            if ($status == PLG_RET_OK) {
                $formatted = $output;
            } else {
                $formatted = COM_numberFormat($amount, 2);
            }
        } else {
            $formatted = $default === NULL ? '0.00' : $default;
        }
        return $formatted;
    }


    /**
    *   Get a count of the unpaid tickets for a user/event.
    *
    *   @param  string  $ev_id      Event ID
    *   @param  integer $rp_id      Instance ID, default 0 (event)
    *   @param  integer $uid        User ID, default to current user
    *   @return integer     Number of unpaid tickets
    */
    public static function CountUnpaid($ev_id, $rp_id=0, $uid=0)
    {
        global $_TABLES, $_USER;

        if ($uid == 0) $uid = $_USER['uid'];
        $uid = (int)$uid;
        $rp_id = (int)$rp_id;
        $ev_id = DB_escapeString($ev_id);

        $vars = array('ev_id', 'uid');
        $vals = array($ev_id, $uid);
        if ($rp > 0) {
            $vars[] = 'rp_id';
            $vals[] = $rp_id;
        }
        $count = DB_count($_TABLES['evlist_tickets'], $vars, $vals);
        return $count;
    }


    /**
    *   Mark a number of tickets paid for a user/event
    *
    *   @uses   Ticket::CountUnpaid()
    *   @param  integer $count      Number of tickets paid
    *   @param  string  $ev_id      Event ID
    *   @param  integer $rp_id      Instance ID, default 0 (event)
    *   @param  integer $uid        User ID, default to current user
    *   @return integer     Number of user/event tickets remainint unpaid
    */
    public static function MarkPaid($count, $ev_id, $rp_id=0, $uid=0)
    {
        global $_TABLES, $_USER;

        $count = (int)$count;
        if ($uid == 0) $uid = $_USER['uid'];
        $uid = (int)$uid;
        $rp_id = (int)$rp_id;
        $ev_id = DB_escapeString($ev_id);

        $sql = "UPDATE {$_TABLES['evlist_tickets']}
                SET paid = fee
                WHERE ev_id = '$ev_id' AND uid = $uid";
        if ($rp_id > 0) $sql .= " AND rp_id = $rp_id";
        $sql .= " LIMIT $count";
        DB_query($sql);
        return self::CountUnpaid($ev_id, $rp_id, $uid);
    }

}   // class Ticket

?>
