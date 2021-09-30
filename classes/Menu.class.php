<?php
/**
 * Class to provide admin and user-facing menus.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2019 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.0
 * @since       v1.4.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;

/**
 * Class to provide admin and user-facing menus.
 * @package evlist
 */
class Menu
{
    /**
     * Create the administrator menu.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function Admin($view='')
    {
        global $_CONF, $LANG_ADMIN, $LANG_EVLIST, $_EV_CONF;

        $retval = '';

        USES_lib_admin();

        $menu_arr = array(
            array(
                'url' => EVLIST_ADMIN_URL . '/index.php?events=x',
                'text' => $LANG_EVLIST['events'],
                'active' => $view == 'events' ? true : false,
            ),
            array(
                'url' => EVLIST_ADMIN_URL . '/index.php?calendars=x',
                'text' => $LANG_EVLIST['calendars'],
                'active' => $view == 'calendars' ? true : false,
            ),
            array(
                'url' => EVLIST_ADMIN_URL . '/index.php?categories=x',
                'text' => $LANG_EVLIST['categories'],
                'active' => $view == 'categories' ? true : false,
            ),
            array(
                'url' => EVLIST_ADMIN_URL . '/index.php?import=x',
                'text' => $LANG_EVLIST['import'],
                'active' => $view == 'import' ? true : false,
            ),
        );
        if ($_EV_CONF['enable_rsvp']) {
            $menu_arr[] = array(
                'url' => EVLIST_ADMIN_URL . '/index.php?tickettypes',
                'text' => $LANG_EVLIST['tickettypes'],
                'active' => $view == 'tickettypes' ? true : false,
            );
        }
        $menu_arr[] = array(
            'url' => $_CONF['site_admin_url'],
            'text' => $LANG_ADMIN['admin_home'],
        );
        $title = isset($LANG_EVLIST[$view]) ? $LANG_EVLIST[$view] : $view;
        $retval .= COM_startBlock(
            $LANG_EVLIST['pi_title'].' v' . $_EV_CONF['pi_version'] . ': ' . $title,
            '',
            COM_getBlockTemplate('_admin_block', 'header')
        );
        $retval .= ADMIN_createMenu(
            $menu_arr,
            isset($LANG_EVLIST['admin_instr'][$view]) ? $LANG_EVLIST['admin_instr'][$view] : '',
            plugin_geticon_evlist()
        );
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $retval;
    }


    /**
     * Display the site header, with or without blocks according to configuration.
     *
     * @param   string  $title  Title to put in header
     * @param   string  $meta   Optional header code
     * @return  string          HTML for site header, from COM_siteHeader()
     */
    public static function siteHeader($title='', $meta='')
    {
        global $_EV_CONF;

        $retval = '';

        switch($_EV_CONF['displayblocks']) {
        case 2:     // right only
        case 0:     // none
            $retval .= COM_siteHeader('none', $title, $meta);
            break;

        case 1:     // left only
        case 3:     // both
        default :
            $retval .= COM_siteHeader('menu', $title, $meta);
            break;
        }
        return $retval;
    }


    /**
     * Display the site footer, with or without blocks as configured.
     *
     * @return  string      HTML for site footer, from COM_siteFooter()
     */
    public static function siteFooter()
    {
        global $_EV_CONF;

        $retval = '';

        switch($_EV_CONF['displayblocks']) {
        case 2 : // right only
        case 3 : // left and right
            $retval .= COM_siteFooter();
            break;

        case 0: // none
        case 1: // left only
        default :
            $retval .= COM_siteFooter();
            break;
        }
        return $retval;
    }


    /**
     * Get the Google-style page navigation for the list display.
     *
     * @param   integer $numrows    Total number of rows
     * @param   integer $cat        Category ID (optional)
     * @param   integer $page       Current page number
     * @param   integer $range      Range being displayed (upcoming, past, etc)
     * @param   integer $cal        ID of calendar being shown
     * @return  string          HTML for page navigation
     */
    public static function pageNav($numrows, $cat=0, $page = 0, $range = 0, $cal = 0)
    {
        global $_TABLES, $_EV_CONF;

        $cat = (int)$cat;
        $range = (int)$range;
        $cal = (int)$cal;
        $limit = (int)$_EV_CONF['limit_list'];
        $retval = '';
        if ($limit < 1) {
            return $retval;
        }

        $base_url = EVLIST_URL.
            "/index.php?cat=$cat&amp;cal=$cal&amp;range=$range&amp;view=agenda";
        if ($numrows > $limit) {
            $numpages = ceil($numrows / $limit);
            $retval = COM_printPageNavigation($base_url, $page, $numpages);
        }
        return $retval;
    }

}
