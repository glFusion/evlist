<?php
/**
 * Yearly View functions for the evList plugin.
 *
 * @author      Lee P. Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2017-2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Views;


/**
 * Display a yearly calendar.
 * @package evlist
 */
class yearView extends \Evlist\View
{
    /**
     * Construct the yearly view.
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
        $this->type = 'year';
        parent::__construct($year, 0, 0, $cat, $cal, $opts);
    }


    /**
     * Get the actual calendar view content.
     * This uses the smallmonth view 12 times.
     *
     * @return  string      HTML for calendar content
     */
    public function Content()
    {
        $this->prev_date = array(
            'year' => $this->year - 1,
            'month' => 1,
            'day' => 1,
        );
        $this->next_date = array(
            'year' => $this->year + 1,
            'month' => 1,
            'day' => 1,
        );

        $T = new \Template(EVLIST_PI_PATH . '/templates/yearview');
        $tpl = $this->getTemplate();
        $T->set_file(array(
            'yearview'  => $tpl . '.thtml',
        ) );
        $T->set_block('yearview', 'month', 'mBlock');
        for ($i = 1; $i < 13; $i++) {
            $cal = \Evlist\View::getView('smallmonth', $this->year, $i, 1,
                    $this->cat, $this->cal, $this->opts);
            $T->set_var('month', $cal->Render());
            $T->parse('mBlock', 'month', true);
        }
        $T->parse('output', 'yearview');
        return $T->finish($T->get_var('output'));
    }
}
