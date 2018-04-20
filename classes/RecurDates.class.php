<?php
/**
*   Class to create recurrences by date for the evList plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2011-2017 Lee Garner <lee@leegarner.com>
*   @package    evlist
*   @version    1.4.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;
use LGLib\Date_Calc;

/**
*   Class for events recurring by user-specified dates.
*   @package evlist
*/
class RecurDates extends Recur
{
    public function MakeRecurrences()
    {
        if (!is_array($this->event->rec_data['custom']))
            return $this->events;

        foreach($this->event->rec_data['custom'] as $occurrence) {
            list($y, $m, $d) = explode('-', $occurrence);
            if (Date_Calc::isValidDate($d, $m, $y)) {
                $this->storeEvent($occurrence);
            }
        }
        return $this->events;
    }

}   // class RecurDates

?>
