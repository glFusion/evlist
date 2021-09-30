<?php
/**
 * Detail View functions for the evList plugin.
 * Creates daily, weekly, monthly and yearly calendar views
 *
 * @author      Lee P. Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2021 Lee Garner <lee@leegarner.com
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Views;

/**
 * Class to handle the event detail view.
 * The actual view is created by Repeat::Render().
 * This class is used simply to create the standard header.
 */
class detail extends \Evlist\View
{
    /**
     * Construct the event detail view.
     *
     * @param   integer $year   Year to display, default is current year
     * @param   integer $month  Starting month
     * @param   integer $day    Starting day
     * @param   integer $cat    Event category
     * @param   integer $cal    Calendar ID
     * @param   string  $opts   Optional template modifier, e.g. "print"
     */
    public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        $this->type = 'detail';
        $this->inc_dt_sel = false;  // disable date/range selections
        parent::__construct($year, $month, $day, $cat, $cal, $opts);
    }


    /**
     * Display the common header for all calendar views.
     * The event detail page does not use the header.
     *
     * @param   boolean $add_link   True to include a "Add Event" button
     * @return  string          HTML for calendar header
     */
    public function Header($add_link = true)
    {
        return '';
    }
}
