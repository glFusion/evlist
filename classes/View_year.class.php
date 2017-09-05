<?php
/**
*   View functions for the evList plugin.
*   Creates daily, weekly, monthly and yearly calendar views
*
*   @author     Lee P. Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com
*   @package    evlist
*   @version    1.4.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Evlist;


/**
*   Display a yearly calendar.
*   @class  View_year
*/
class View_year extends View
{
    /*
    *   Construct the yearly view
    *
    *   @param  integer $year   Year to display, default is current year
    *   @param  integer $month  Starting month
    *   @param  integer $day    Starting day
    *   @param  integer $cat    Event category
    *   @param  integer $cal    Calendar ID
    *   @param  string  $opt    Optional template modifier, e.g. "print"
    */
    public function __construct($year=0, $month=0, $day=0, $cat=0, $cal=0, $opts=array())
    {
        $this->type = 'year';
        parent::__construct($year, 0, 0, $cat, $cal, $opts);
    }


    /**
    *   Get the actual calendar view content
    *
    *   @uses   View_smallmonth
    *   @return string      HTML for calendar content
    */
    public function Content()
    {
        global $_EV_CONF;

        $T = new \Template(EVLIST_PI_PATH . '/templates/yearview');
        $tpl = $this->getTemplate();
        $T->set_file(array(
            'yearview'  => $tpl . '.thtml',
        ) );

        $T->set_block('yearview', 'month', 'mBlock');
        for ($i = 1; $i < 13; $i++) {
            $cal = View::getView('smallmonth', $this->year, $i, 1,
                    $this->cat, $this->cal, $this->opts);
            $T->set_var('month', $cal->Render());
            $T->parse('mBlock', 'month', true);
        }

        $T->set_var(array(
            'pi_url'        => EVLIST_URL,
            'thisyear'      => $this->year,
            'prevyear'      => $this->year - 1,
            'nextyear'      => $this->year + 1,
            'cal_header'    => $this->Header(),
            'cal_footer'    => $this->Footer(),
            'urlfilt_cat'   => $this->cat,
            'urlfilt_cal'   => $this->cal,
        ) );

        $T->parse('output', 'yearview');
        return $T->finish($T->get_var('output'));
    }
}

?>
