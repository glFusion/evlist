<?php
/**
 * Class to manage tickets and registrations.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2015-2020 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;


/**
 * Class for event tickets.
 * @package evlist
 */
class Ticket
{
    /** Ticket record ID.
     * @var string */
    private $tic_id = '';

    /** Ticket type record ID.
     * @var integer */
    private $tic_type = 0;

    /** Event ID.
     * @var string */
    private $ev_id = '';

    /** Repeat ID.
     * @var integer */
    private $rp_id = 0;

    /** Ticket fee.
     * @var float */
    private $fee = 0;

    /** Amount paid.
     * @var float */
    private $paid = 0;

    /** Purchaser's user ID
     * @var integer */
    private $uid = 0;

    /** Timestamp indicating when ticket was used.
     * @var integer  */
    private $used = 0;

    /** Timestamp when the ticket was purchased.
     * @var integer */
    private $dt = 0;

    /** Flag indicating that this ticket is waitlisted.
     * @var boolean */
    private $waitlist = 0;

    /** Comment, if any.
     * @var string */
    private $comment = '';


    /**
     * Constructor.
     * Create an empty ticket type object, or read an existing one
     *
     * @param   string  $tic_id     Ticket ID to load, or empty string
     */
    public function __construct($tic_id = '')
    {
        if (is_array($tic_id)) {
            $this->setVars($tic_id);
        } else {
            $this->tic_id = $tic_id;
            if ($this->tic_id != '') {
                $this->Read($this->tic_id);
            }
        }
    }


    /**
     * Read an existing ticket record into this object.
     *
     * @param   string  $tic_id Optional ticket ID, $this->id used if empty
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
            $this->setVars($row, true);
            return true;
        }
    }


    /**
     * Get the ticket ID.
     *
     * @return  string      Ticket record ID
     */
    public function getID()
    {
        return $this->tic_id;
    }


    /**
     * Get the related base Event record ID.
     *
     * @return   string     Event ID
     */
    public function getEventID()
    {
        return $this->ev_id;
    }


    /**
     * Get the related event instance ID.
     *
     * @return  integer     Repeat ID
     */
    public function getRepeatID()
    {
        return (int)$this->rp_id;
    }


    /**
     * Get the ticket type ID.
     *
     * @return  integer     TicketType ID
     */
    public function getTypeID()
    {
        return (int)$this->tic_type;
    }


    /**
     * Get the amount paid for this ticket.
     *
     * @return  float       Amount paid
     */
    public function getPaid()
    {
        return (float)$this->paid;
    }


    /**
     * Get the total amount charged for this ticket.
     *
     * @return  float       Total price
     */
    public function getFee()
    {
        return (float)$this->fee;
    }


    /**
     * Check if this ticket is fully paid.
     *
     * @return  boolean     True if paid, False if not
     */
    public function isPaid()
    {
        return $this->paid >= $this->fee;
    }


    /**
     * Check if this ticket is on a waitlist.
     *
     * return   boolean     1 if waitlisted, 0 if not
     */
    public function isWaitlisted()
    {
        return $this->waitlist ? 1 : 0;
    }


    /**
     * Set the value of all variables from an array, either DB or a form.
     *
     * @param  array   $A      Array of fields
     */
    public function setVars($A)
    {
        $this->tic_id = $A['tic_id'];
        $this->tic_type = $A['tic_type'];
        $this->ev_id = $A['ev_id'];
        $this->rp_id = (int)$A['rp_id'];
        $this->uid = (int)$A['uid'];
        $this->fee = (float)$A['fee'];
        $this->paid = (float)$A['paid'];
        $this->used = (int)$A['used'];
        $this->dt = (int)$A['dt'];
        $this->waitlist = isset($A['waitlist']) && $A['waitlist'] ? 1 : 0;
        $this->comment = $A['comment'];
    }


    /**
     * Create a unique ticket ID.
     *
     * @param   array   $A      Array of values, non-indexed
     * @return  string          Ticket ID
     */
    public static function makeTicketId($A = array())
    {
        global $_EV_CONF;

        if (function_exists('CUSTOM_evlist_makeTicketId')) {
            $retval = CUSTOM_evlist_makeTicketId($A);
        } else {
            // Make sure a default format is defined if not in the config
            if (strstr($_EV_CONF['ticket_format'], '%s') === false) {
                $_EV_CONF['ticket_format'] = 'EV%s';
            }

            // make a unique value.
            $token = dechex(date('y')) . dechex(date('m')) . self::createToken();
            $retval = sprintf($_EV_CONF['ticket_format'], $token);
        }
        return $retval;
    }


    /**
     * Create a ticket.
     *
     * @param   string  $ev_id  Event ID (required)
     * @param   integer $type   Type of ticket, from the ticket_types table
     * @param   integer $rp_id  Optional Repeat ID, 0 for event pass
     * @param   float   $fee    Optional Ticket Fee, default = 0 (free)
     * @param   integer $uid    Optional User ID, default = current user
     * @param   integer $wl     Waitlisted ? 1 = yes, 0 = no
     * @param   string  $cmt    User-supplied comment
     * @return  string      Ticket identifier
     */
    public static function Create($ev_id, $type, $rp_id = 0, $fee = 0, $uid = 0, $wl = 0, $cmt='')
    {
        global $_TABLES, $_EV_CONF, $_USER;

        $uid = (int)$uid;
        if ($uid == 0) $uid = (int)$_USER['uid'];
        $rp_id = (int)$rp_id;
        $fee = (float)$fee;
        $type = (int)$type;
        $wl = $wl == 0 ? 0 : 1;
        $tic_num = self::makeTicketId(array($ev_id, $rp_id, $fee, $uid));
        if (!is_array($cmt) || empty($cmt)) {
            $cmt = array();
        }
        $cmt = DB_escapeString(json_encode($cmt));
        $sql = "INSERT INTO {$_TABLES['evlist_tickets']} SET
            tic_num = '" . DB_escapeString($tic_num) . "',
            tic_type = $type,
            ev_id = '" . DB_escapeString($ev_id) . "',
            rp_id = $rp_id,
            fee = $fee,
            paid = 0,
            uid = $uid,
            used = 0,
            dt = UNIX_TIMESTAMP(),
            waitlist = $wl,
            comment = '$cmt'";
        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            return DB_insertId();
        } else {
            return NULL;
        }
    }


    /**
     * Save the current ticket.
     *
     * @return  string  Ticket identifier
     */
    public function Save()
    {
        global $_TABLES, $_EV_CONF, $_USER;

        if ($this->uid == 0) $this->uid = $_USER['uid'];
        $rp_id = (int)$rp_id;
        $fee = (float)$fee;
        $type = (int)$type;

        if ($this->tic_id == '') {
            $this->tic_id = self::makeTicketId(
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
            used = {$this->used},
            comment = '" . DB_escapeString($this->comment) . "'";

        $sql = $sql1 . $sql2 . $sql3;
        //echo $sql;die;
        DB_query($sql, 1);
        if (!DB_error()) {
            return $tic_id;
        } else {
            return NULL;
        }
    }


    /**
     * Deletes the specified ticket(s).
     *
     * @param   array|string    $id Single or Array of ticket IDs to delete
     */
    public static function Delete($id='')
    {
        global $_TABLES;

        if (is_array($id)) {
            $key = $id[0];
            foreach ($id as $idx=>$tic_id) {
                $id[$idx] = DB_escapeString($tic_id);
            }
            $where = "IN ('" . implode("','", $id) . "')";
        } else {
            $key = $id;
            $id = DB_escapeString($id);
            $where = "= '$id'";
        }
        // Grab a ticket to get the event information to find out if the
        // waitlist should be updated.
        $tic = new self($key);
        $Ev = new Event($tic->ev_id);
        $max_rsvp = (int)$Ev->getOption('max_rsvp');
        $sql = "DELETE FROM {$_TABLES['evlist_tickets']} WHERE tic_id $where";
        DB_query($sql);
        // Now that the tickets have been deleted, reset the waitlist if needed
        if ($max_rsvp > 0 && $Ev->getOption('rsvp_waitlist')) {
            self::resetWaitlist($max_rsvp, $Ev->getID(), $tic->getID());
        }
        EVLIST_log("Deleted tickets $where");
    }


    /**
     * Resets the "used" field in the ticket.
     *
     * @param   integer $id     ID of ticket reset
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
     * Get all the tickets into objects for an event or repeat.
     * Can get all tickets for an event, a single occurrence, or all for
     * a single user. Must have at lest one search parameter or an
     * empty array is returned.
     *
     * @param   string  $ev_id      Event ID
     * @param   integer $rp_id      Repeat ID, 0 for all occurrences
     * @param   integer $uid        User ID, 0 for all users
     * @param   string  $paid       'paid', 'unpaid', or empty for all
     * @return  array       Array of Ticket objects, indexed by ID
     */
    public static function getTickets($ev_id, $rp_id = 0, $uid = 0, $paid='')
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
            $sql = "SELECT * FROM {$_TABLES['evlist_tickets']}
                WHERE $sql_where
                ORDER BY waitlist, dt ASC";
            $res = DB_query($sql, 1);
            while ($A = DB_fetchArray($res, false)) {
                // create empty objects and use setVars to save DB lookups
                $tickets[$A['tic_id']] = new self($A);
            }
        }
        return $tickets;
    }


    /**
     * Print tickets as PDF documents.
     * Tickets can be printed for an event, a single occurrence,
     * or all tickets for a user ID.
     *
     * @param   string  $ev_id  Event ID
     * @param   integer $rp_id  Repeat ID
     * @param   integer $uid    User ID
     * @return  string          PDF Document containing tickets
     */
    public static function printEvent($ev_id='', $rp_id=0, $uid=0)
    {
        global $_CONF, $_USER, $LANG_EVLIST, $_PLUGINS;

        $Event = Event::getInstance($ev_id);
        $rsvp_print = (int)$Event->getOption('rsvp_print');

        // Verify that the current user is an admin or event owner to print
        // all tickets, otherwise only print the user's tickets.
        if (!$Event->hasAccess(3) && $uid == 0) {
            $uid = $_USER['uid'];
        }

        // get the tickets, paid and unpaid. Need event id and uid.
        $tickets = self::getTickets($ev_id, $rp_id, $uid);
        return self::_printTickets($tickets);
    }


    /**
     * Print selected as a PDF document.
     *
     * @param   array   $tic_ids    Ticket ID numbers
     * @return  string          PDF Document containing tickets
     */
    public static function printSelected($tic_ids)
    {
        foreach ($tic_ids as $id) {
            $tic = new self($id);
            if ($tic->getID() != '') {
                $tickets[$tic->getID()] = $tic;
            }
        }
        if (!empty($tickets)) {
            return self::_printTickets($tickets);
        }
    }

    
    /**
     * Print tickets as PDF documents.
     * Tickets can be printed for an event, a single occurrence,
     * or all tickets for a user ID.
     *
     * @param   array   $tickets    Array of Ticket objects
     * @return  string          PDF Document containing tickets
     */
    private static function _printTickets($tickets)
    {
        global $_CONF, $_USER, $LANG_EVLIST, $_PLUGINS;

        /*$Event = Event::getInstance($ev_id);

        // Verify that the current user is an admin or event owner to print
        // all tickets, otherwise only print the user's tickets.
        if (!$Event->hasAccess(3) && $uid == 0) {
            $uid = $_USER['uid'];
        }
        */
        $checkin_url = $_CONF['site_admin_url'] . '/plugins/evlist/checkin.php?tic=';

        // get the tickets, paid and unpaid. Need event id and uid.
        //$tickets = self::getTickets($ev_id, $rp_id, $uid);

        // The PDF functions in lgLib are a recent addition. Make sure that
        // the lgLib version supports PDF creation since we can't yet check
        // the lglib version during installation
        if (
        //    $rsvp_print == 0 ||
            empty($tickets) ||
            !in_array('lglib', $_PLUGINS)
        ) {
            return "There are no tickets available to print";
        }

        // Track when the event information is read to avoid duplicate
        // reading.
        $ev_id = NULL;

        // create params array for qrcode, if used
        $params = array('module_size'=>5);

        $pdf = new \TCPDF();
        $pdf->SetLeftMargin(20);
        $pdf->AddPage();

        $tic_types = array();
        $counter = 0;           // count tickets printed per page
        $tic_count = 0;         // count total tickets printed
        foreach ($tickets as $tic_id=>$ticket) {
            // Don't print waitlisted tickets
            if ($ticket->isWaitlisted()) continue;

            if (!isset($tick_types[$ticket->getTypeID()])) {
                $tick_types[$ticket->getTypeID()] = new TicketType($ticket->getTypeID());
            }

            // If we don't already have the event info, get it and construct
            // the address string
            if ($ev_id != $ticket->getEventID()) {
                $Ev = new Event($ticket->getEventID());
                $ev_id = $Ev->getID();
                $addr = $Ev->getDetail()->getAddress();
                $address = implode(' ', $addr);
                $rsvp_print = (int)$Ev->getOption('rsvp_print');
                if ($rsvp_print == 0) {     // no printing allowed
                    return false;
                }
            }

            // Don't print unpaid tickets if not allowed
            if ($rsvp_print == 1 && !$ticket->isPaid()) {
                continue;
            }

            // Get the repeat(s) for the ticket(s) to print a ticket for each
            // occurrence.
            $repeats = Repeat::getRepeats($ticket->getEventID(), $ticket->getRepeatID());
            if (empty($repeats)) {
                continue;
            }
            foreach ($repeats as $rp_id => $event) {
                $counter++;         // increment the per-page counter
                // Increment the printed ticket counter to know whether to display
                // the PDF output or show an error message.
                $tic_count++;

                if ($counter > 3) {     // Print up to 3 tickets per page
                    $pdf->AddPage();
                    $counter = 1;
                }

                $ev_date = $event->getDateStart1()->format('Y-m-d');
                $ev_time = $event->getTimeStart1() . ' - ' . $event->getTimeEnd1();
                if (!empty($event->getTimeStart2())) {
                    $ev_time .= '; ' . $event->getTimeStart2() . ' - ' . $event->getTimeEnd2();
                }

                $fee = self::formatAmount($ticket->getFee());

                // Get the veritcal position of the current ticket
                // for positioning the qrcode
                $y = $pdf->GetY();

                // Title
                $pdf->SetFont('Times','B',12);
                $pdf->Cell(130,10, "{$tick_types[$ticket->getTypeID()]->getDscp()}: {$Ev->getDetail()->getTitle()}",1,0,'C');
                $pdf->Ln(13);

                $pdf->SetFont('Times','',12);
                $pdf->SetX(-40);
                $pdf->Cell(0, 30, $LANG_EVLIST['fee'] . ': '. $fee);
                if ($ticket->getFee() > 0) {
                    $pdf->Ln(5);
                    if ($ticket->isPaid()) {
                        $pdf->SetX(-40);
                        $pdf->Cell(0, 30, $LANG_EVLIST['paid']);
                    } else {
                        $pdf->SetX(-55);
                        $due = $ticket->getFee() - $ticket->getPaid();
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
                if ($Ev->getDetail()->getLocation() != '') {
                    $pdf->Ln(5);
                    $pdf->Cell(0, 2, $LANG_EVLIST['where'] . ': ', 0, 0);
                    $pdf->SetX(40);
                    $pdf->Cell(0, 2, $Ev->getDetail()->getLocation(), 0, 1);
                    $addr_line = 4;
                }
                if (!empty($address)) {
                    if ($Ev->getDetail()->getLocation() == '') {
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
                $pdf->Cell(0,10, $ticket->getID());

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
        if ($tic_count > 0) {
            $pdf->Output();
        } else {
            return false;
        }
    }   // end func _printTickets()


    /**
     * Export tickets to a CSV file for a single occurrence.
     *
     * @param   integer $rp_id  Repeat ID
     * @return  string  CSV file containing all tickets
     */
    public static function ExportTickets($rp_id='')
    {
        global $_CONF, $LANG_EVLIST;

        $retval = '';

        $Rp = new Repeat($rp_id);
        // Verify that the current is an admin or event owner
        if (!$Rp->getEvent()->hasAccess(3)) {
            return $retval;
        }

        // get the tickets, paid and unpaid.
        // $uid = 0 to export all
        $tickets = self::getTickets($Rp->getEventID(), $rp_id, 0);

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
            if ($Rp->getEvent()->getOption('max_rsvp') > 0) {
                $is_waitlisted = ($counter > $Rp->getEvent()->getOption('max_rsvp')) ? 'Yes': 'No';
            } else {
                $is_waitlisted = 'N/A';
            }
            $values[] = $is_waitlisted;
            $retval .= '"' . implode('","', $values) . '"' . "\n";
        }
        return $retval;
    }   // end func ExportTickets()


    /**
     * Check in a user at the event.
     * This is meant to work with the qrcode and a smartphone and is
     * called from admin/plugins/evlist/checkin.php.
     * Each ticket should have a ticket ID and occurrence ID in the URL.
     *
     * @param   integer $rp_id  Occurrence ID.
     * @return  integer Zero on success, Message ID on failure
     */
    public function Checkin($rp_id)
    {
        global $_TABLES;

        // Check that the ticket hasn't already been used
        if ($this->used > 0) return 51;
        if ($this->fee > 0 && $this->paid < $this->fee) return 50;
        $reg_cookie = EV_getVar($_COOKIE, 'evlist_register', 'array');
        $code = EV_getVar($reg_cookie, $this->ev_id);
        $auth = (int)DB_getItem('gl_evlist_checkin_auth', 'auth', "ev_id = '{$this->ev_id}' AND code = '$code'");
        if ($auth != 1) {
            echo "Unauthorized";
            return 52;
        }

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
     * Adds a payment amount to the ticket record.
     *
     * @param   string  $tick_id    ID of ticket to update
     * @param   float   $amt        Amount paid
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


    /**
     * Format a money amount.
     * Calls on the Shop plugin to format according to the selected
     * currency, and falls back to COM_numberFormat() if Shop isn't
     * available.
     *
     * @param   float   $amount     Amount to format
     * @param   string  $default    Default if amount is zero, '0.00' if empty
     * @return  string      Formatted money string with currency specifier
     */
    public static function formatAmount($amount, $default=NULL)
    {
        if ($amount > 0) {
            $status = LGLIB_invokeService(
                'shop', 'formatAmount',
                array(
                    'amount' => $amount,
                    'symbol' => false,
                ),
                $output,
                $msg
            );
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
     * Get a count of the unpaid tickets for a user/event.
     *
     * @param   string  $ev_id      Event ID
     * @param   integer $rp_id      Instance ID, default 0 (event)
     * @param   integer $uid        User ID, default to current user
     * @return  integer     Number of unpaid tickets
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
        if ($rp_id > 0) {
            $vars[] = 'rp_id';
            $vals[] = $rp_id;
        }
        $count = DB_count($_TABLES['evlist_tickets'], $vars, $vals);
        return $count;
    }


    /**
     * Mark a number of tickets paid for a user/event.
     *
     * @uses    Ticket::CountUnpaid()
     * @param   integer $count      Number of tickets paid
     * @param   string  $ev_id      Event ID
     * @param   integer $rp_id      Instance ID, default 0 (event)
     * @param   integer $uid        User ID, default to current user
     * @return  integer     Number of user/event tickets remainint unpaid
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
                WHERE ev_id = '$ev_id' AND uid = $uid AND paid=0";
        if ($rp_id > 0) $sql .= " AND rp_id = $rp_id";
        $sql .= " LIMIT $count";
        DB_query($sql);
        return self::CountUnpaid($ev_id, $rp_id, $uid);
    }


    /**
     * Reset the waitlist status for tickets.
     * Called after deleting or cancelling tickets to move waitlisted
     * tickets to non-waitlisted.
     *
     * @param   integer $max_rsvp   Max reservations
     * @param   string  $ev_id      Event ID
     * @param   integer $rp_id      Instance ID
     * @return  array               Array of updated ticket IDs
     */
    public static function resetWaitlist($max_rsvp, $ev_id, $rp_id)
    {
        global $_TABLES;

        if ($max_rsvp == 0) return array();   // no max, nothing to change
        $upd = array();
        $tickets = self::getTickets($ev_id, $rp_id);
        $i = 0;
        foreach ($tickets as $tic_id=>$ticket) {
            if ($i < $max_rsvp) {
                if ($ticket->waitlist == 1) {
                    $upd[] = DB_escapeString($tic_id);
                }
            }
            $i++;
        }
        if (!empty($upd)) {
            $sql_str = "'" . implode("','", $upd) . "'";
            $sql = "UPDATE {$_TABLES['evlist_tickets']}
                    SET waitlist = 0
                    WHERE tic_id IN ($sql_str)";
            DB_query($sql);
        }
        return $upd;
    }


    /**
     * Get the list of tickets.
     *
     * @param   string  $ev_id  Event ID
     * @param   integer $rp_id  Repeat ID, 0 for all
     * @return  string      HTML for admin list
     */
    function userList($ev_id, $rp_id = 0)
    {
        global $_CONF, $_TABLES, $LANG_EVLIST, $LANG_ADMIN;

        USES_lib_admin();
        EVLIST_setReturn('admintickets');

        $retval = '';

        $header_arr = array(
            array(
                'text' => $LANG_EVLIST['id'],
                'field' => 'tick_id',
                'sort' => true,
            ),
            array(
                'text' => $LANG_EVLIST['registrant'],
                'field' => 'uid',
                'sort' => false,
            ),
            array(
                'text' => $LANG_EVLIST['fee'],
                'field' => 'fee',
                'sort' => false,
            ),
            array(
                'text' => $LANG_EVLIST['event_pass'],
                'field' => 'event_pass',
                'sort' => false,
            ),
            array(
                'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
            ),
        );

        $defsort_arr = array('field' => 'tick_id', 'direction' => 'ASC');
        $text_arr = array(
            'has_menu'     => false,
            'has_extras'   => false,
            'form_url'     => EVLIST_ADMIN_URL . '/index.php',
            'help_url'     => '',
        );

        $sql = "SELECT * FROM {$_TABLES['evlist_tickets']} WHERE ev_id='" .
            DB_escapeString($ev_id) . "'";
        if ($rp_id != 0) {
            $sql .= " AND rp_id = " . (int)$rp_id;
        }
        $query_arr = array(
            'table' => 'evlist_tickets',
            'sql' => $sql,
            'query_fields' => array(),
        );

        $retval .= ADMIN_list(
            'evlist_ticket_user',
            array(__CLASS__, 'getUserField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr
        );
        return $retval;
    }


    /**
     * Return the display value for a ticket fields in the admin list.
     *
     * @param   string  $fieldname  Name of the field
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Name-value pairs for all fields
     * @param   array   $icon_arr   Array of system icons
     * @return  string      HTML to display for the field
     */
    public static function getUserField($fieldname, $fieldvalue, $A, $icon_arr)
        {
        global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_TABLES, $_EV_CONF;

        switch($fieldname) {
        case 'event_pass':
            $retval = $A['rp_id'] == 0 ? $LANG_EVLIST['yes'] : $LANG_EVLIST['no'];
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
     * Show the public-facing RSVP list, if enabled.
     * Same as adminList_RSVP() but with limited fields and no actions.
     *
     * @param   integer $rp_id  Repeat ID being viewed or checked
     * @param   string  $title  Optional title to show with the list
     * @return  string          HTML for admin list
     */
    public static function userList_RSVP($rp_id, $title='')
    {
        global $LANG_EVLIST, $LANG_ADMIN, $_TABLES, $_CONF, $_EV_CONF;

        USES_lib_admin();
        $Ev = \Evlist\Repeat::getInstance($rp_id);
        if (
            $Ev->getID() == 0 ||
            !SEC_inGroup($Ev->getEvent()->getOption('rsvp_view_grp'))
        ) {
            return '';
        }

        $sql = "SELECT tk.dt, tk.tic_id, tk.tic_type, tk.rp_id, tk.fee, tk.paid,
                    tk.uid, tk.used, tt.dscp, tk.waitlist, tk.comment,
                    u.fullname,
                    {$Ev->getEvent()->getOption('max_rsvp')} as max_rsvp
            FROM {$_TABLES['evlist_tickets']} tk
            LEFT JOIN {$_TABLES['evlist_tickettypes']} tt
                ON tt.tt_id = tk.tic_type
            LEFT JOIN {$_TABLES['users']} u
                ON u.uid = tk.uid
            WHERE tk.ev_id = '{$Ev->getEvent()->getID()}' ";

        if ($Ev->getEvent()->getOption('use_rsvp') == EV_RSVP_REPEAT) {
            $sql .= " AND rp_id = '{$Ev->getID()}' ";
        }

        $text_arr = array(
            'has_menu'     => false,
            'has_extras'   => false,
            'form_url'     => EVLIST_URL . '/event.php?rp_id=' . $rp_id,
            'help_url'     => '',
        );

        $header_arr = array(
            array(
                'text'  => $LANG_EVLIST['name'],
                'field' => 'fullname',
                'sort'  => true,
            ),
        );
        if ($Ev->getEvent()->getOption('use_rsvp')) {
            $prompts = $Ev->getEvent()->getOption('rsvp_cmt_prompts');
            if (empty($prompts)) {
                $prompts = array($LANG_EVLIST['comment']);
            }
            $c = 0;
            foreach ($prompts as $prompt) {
                $header_arr[] = array(
                    'text' => $prompt,
                    'field' => 'cmt_' . $c++,
                    'sort' => false,
                );
            }
        }

        $data_arr = array();
        $res = DB_query($sql);
        $i = 0;
        while ($A = DB_fetchArray($res, false)) {
            $data_arr[$i] = array(
                'fullname' => $A['fullname'],
            );
            $cmts = json_decode($A['comment'], true);
            $j = 0;
            foreach ($cmts as $p=>$val) {
                $data_arr[$i]['cmt_' . $j++] = $val;
            }
            $i++;
        }

        $options_arr = array();

        $retval = '';
        if (!empty($title)) {
            $retval .= '<h2>' . $title . '</h2>';
        }
        $retval .= ADMIN_simpleList(
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr, $data_arr, $options_arr
        );
        return $retval;
    }


    /**
     * Administer user registrations.
     * This will appear in the admin area for administrators, and as part of
     * the event detail for event owners.  Owners can delete registrations.
     *
     * @param   integer $rp_id  Repeat ID being viewed or checked
     * @return  string          HTML for admin list
     */
    public static function adminList_RSVP($rp_id)
    {
        global $LANG_EVLIST, $LANG_ADMIN, $_TABLES, $_CONF, $_EV_CONF;

        USES_lib_admin();
        $Ev = \Evlist\Repeat::getInstance($rp_id);
        if ($Ev->getID() == 0) return '';

        $sql = "SELECT tk.dt, tk.tic_id, tk.tic_type, tk.rp_id, tk.fee, tk.paid,
                    tk.uid, tk.used, tt.dscp, tk.waitlist, tk.comment,
                    u.fullname,
                    {$Ev->getEvent()->getOption('max_rsvp')} as max_rsvp
            FROM {$_TABLES['evlist_tickets']} tk
            LEFT JOIN {$_TABLES['evlist_tickettypes']} tt
                ON tt.tt_id = tk.tic_type
            LEFT JOIN {$_TABLES['users']} u
                ON u.uid = tk.uid
            WHERE tk.ev_id = '{$Ev->getEvent()->getID()}' ";

        $title = $LANG_EVLIST['admin_rsvp'] .
            '&nbsp;&nbsp;<a href="'.EVLIST_URL .
            '/index.php?view=printtickets&eid=' . $Ev->getEventID() .
            '" class="uk-button uk-button-primary uk-button-small" target="_blank">' . $LANG_EVLIST['print_tickets'] . '</a>' .
            '&nbsp;&nbsp;<a href="'.EVLIST_URL .
            '/index.php?view=exporttickets&eid=' . $Ev->getID() .
            '" class="uk-button uk-button-primary uk-button-small">' . $LANG_EVLIST['export_list'] . '</a>';

        if ($Ev->getEvent()->getOption('use_rsvp') == EV_RSVP_REPEAT) {
            $sql .= " AND rp_id = '{$Ev->getID()}' ";
        }

        $defsort_arr = array('field' => 'waitlist,dt', 'direction' => 'ASC');
        $text_arr = array(
        'has_menu'     => false,
        'has_extras'   => false,
        'title'        => $title,
        'form_url'     => EVLIST_URL . '/event.php?rp_id=' . $rp_id,
        'help_url'     => '',
        );

        $prompts = $Ev->getEvent()->getOption('rsvp_cmt_prompts');
        if (empty($prompts) || count($prompts) > 1) {
            $cmt_title = $LANG_EVLIST['comment'];
        } else {
            $cmt_title = $prompts[0];
        }
        $header_arr = array(
            array(
                'text'  => $LANG_EVLIST['rsvp_date'],
                'field' => 'dt',
                'sort'  => true,
            ),
            array(
                'text'  => $LANG_EVLIST['name'],
                'field' => 'fullname',
                'sort'  => false,
            ),
            array(
                'text'  => $LANG_EVLIST['fee'],
                'field' => 'fee',
                'sort'  => false,
            ),
            array(
                'text'  => $LANG_EVLIST['paid'],
                'field' => 'paid',
                'sort'  => false,
            ),
            array(
                'text'  => $LANG_EVLIST['ticket_num'],
                'field' => 'tic_id',
                'sort'  => false,
            ),
            array(
                'text'  => $LANG_EVLIST['date_used'],
                'field' => 'used',
                'sort'  => false,
            ),
            array(
                'text'  => $LANG_EVLIST['waitlisted'],
                'field' => 'waitlist',
                'sort'  => false,
            ),
            array(
                'text'  => $cmt_title,
                'field' => 'comment',
                'sort'  => false,
            ),
        );
        $extra = array(
            'cmt_prompts' => $prompts,
            'cmt_count' => count($prompts),
        );

        $options_arr = array(
            'chkdelete' => true,
            'chkfield'  => 'tic_id',
            'chkname'   => 'delrsvp',
            'chkactions' =>
                '<button type="submit" '
                . 'class="uk-button uk-button-mini uk-button-danger" '
                . 'onclick="return confirm(\'' . $LANG_EVLIST['conf_del_item'] . '\');" '
                . 'name="tickdelete">' . $LANG_ADMIN['delete'] . '</button>'
                . '&nbsp;&nbsp;<button type="submit" '
                . 'class="uk-button uk-button-mini" '
                . 'onclick="return confirm(\'' . $LANG_EVLIST['conf_reset'] . '\');" '
                . 'name="tickreset">' . $LANG_EVLIST['reset_usage'] . '</button>'
                . '&nbsp;&nbsp;<button type="submit" '
                . 'class="uk-button uk-button-mini uk-button-primary" '
                . 'name="tickprint">' . $LANG_EVLIST['print'] . '</button>'
                . '<input type="hidden" name="ev_id" value="' . $rp_id . '"/>',
        );

        $query_arr = array(
            'sql'       => $sql,
        );
        return ADMIN_list(
            'evlist_adminlist_rsvp',
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            '', $extra, $options_arr
        );
    }


    /**
     * Display fields for the RSVP admin list.
     *
     * @param   string  $fieldname      Name of field
     * @param   mixed   $fieldvalue     Value of field
     * @param   array   $A              Array of all fields ($name=>$value)
     * @param   array   $icon_arr       Handy array of icon images
     * @param   array   $extra          Extra values passed in verbatim
     * @return  string                  Field value formatted for display
     */
    public static function getAdminField($fieldname, $fieldvalue, $A, $icon_arr, $extra=array())
    {
        global $_CONF, $LANG_ACCESS, $LANG_ADMIN, $LANG_EVLIST;

        $retval = '';

        switch($fieldname) {
        case 'waitlist':
            $retval = $fieldvalue == 0 ? '' : $LANG_EVLIST['yes'];
            break;

        case 'uid':
            $retval = COM_getDisplayName($fieldvalue);
            break;

        case 'rank':
            if ($fieldvalue > $A['max_signups']) {
                $retval = $LANG_EVLIST['yes'];
            } else {
                $retval = $LANG_EVLIST['no'];
            }
            break;

        case 'dt':
        case 'used':
            if ($fieldvalue > 0) {
                $d = new \Date($fieldvalue);
                $retval = $d->format($_CONF['shortdate'] . ' ' . $_CONF['timeonly'], false);
            } else {
                $retval = '';
            }
            break;

        case 'comment':
            $data = json_decode($fieldvalue, true);
            $item_count = max(count($data), count($extra['cmt_prompts']));
            if (is_array($data)) {
                if ($item_count == 1) {
                    $retval .= array_pop($data);
                } else {
                    $comments = array();
                    foreach ($data as $prompt=>$val) {
                        $comments[] = $prompt . ': ' . $val;
                    }
                    $retval .= implode(', ', $comments);
                }
            }
            break;

        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }


    /**
     * Create a random token string for this order to allow anonymous users
     * to view the order from an email link.
     *
     * @return  string      Token string
     */
    public static function createToken($len=13)
    {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(ceil($len / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($len / 2));
        } else {
            $bytes = md5(time() . rand(1,1000));
        }
        return substr(bin2hex($bytes), 0, $len);
    }

}   // class Ticket

?>
