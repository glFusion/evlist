<?php
/**
*   Sitemap driver for the evList plugin.
*
*   @author     Lee P. Garner <lee AT leegarner DOT com>
*   @copyright  Copyright (c) 2017 Lee P. Garner <lee AT leegarner DOT com>
*   @package    evlist
*   @version    1.4.3
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/**
*   Sitemap driver for evList
*   @package    evlist
*/
class sitemap_evlist extends sitemap_base
{
    protected $name = 'evlist';


    /**
    *   Get the friendly display name for the plugin
    *
    *   @return string  Plugin's display name
    */
    public function getDisplayName()
    {
        global $LANG_EVLIST;
        return $LANG_EVLIST['pi_title'];
    }


    /**
    *   Get Event items to display in the sitemap
    *
    *   Returns an array of (
    *       'id'        => $id (string),
    *       'title'     => $title (string),
    *       'uri'       => $uri (string),
    *       'date'      => $date (int: Unix timestamp),
    *       'image_uri' => $image_uri (string)
    *   )
    *
    *   @return array   Array of item information
    */
    public function getItems($category = false)
    {
        global $_CONF, $_TABLES, $_EV_CONF;

        $entries = array();

        $sql = "SELECT e.id, d.title, UNIX_TIMESTAMP(e.date_start1) AS day
                FROM {$_TABLES['evlist_repeat']} r
                LEFT JOIN {$_TABLES['evlist_events']} e ON e.id = r.rp_ev_id
                LEFT JOIN {$_TABLES['evlist_detail']} d ON d.ev_id=r.rp_ev_id
                WHERE 1=1 ";
        if ($this->uid > 0) {
            $sql .= COM_getPermSql('AND', $this->uid, 2, 'e');
        }
        if ($this->isHTML()) {
            // Includes events that end anytime today
            $sql .= " AND r.rp_end >= '{$_EV_CONF['_today']}'";
        }
        $sql .= ' GROUP BY e.id ORDER BY r.rp_date_start DESC';
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_evlist::getItems() error: $sql");
            return $entries;
        }

        while ($A = DB_fetchArray($result, false)) {
            $entries[] = array(
                'id'        => $A['id'],
                'title'     => $A['title'],
                'uri'       => COM_buildURL(
                    $_CONF['site_url'] . '/evlist/event.php?view=event&eid=' . $A['id']),
                'date'      => $A['day'],
                'image_uri' => false,
            );
        }
        return $entries;
    }

}

?>
