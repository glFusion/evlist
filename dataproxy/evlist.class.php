<?php
// +--------------------------------------------------------------------------+
// | evList A calendar solution for glFusion                                  |
// +--------------------------------------------------------------------------+
// | evlist.class.php                                                         |
// |                                                                          |
// | evList plugin interface                                                  |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008 by the following authors:                             |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
// | Based on the Data Proxy Plugin for Geeklog CMS                           |
// | Copyright (C) 2007-2008 by the following authors:                        |
// |                                                                          |
// | Authors: mystral-kk        - geeklog AT mystral-kk DOT net               |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+
/**
*   Dataproxy driver for the evList plugin
*   @author     Mark R. Evans mark AT glfusion DOT org
*   @copyright  Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
*   @package    evlist
*   @version    1.3.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/**
*   Dataproxy driver for evList
*   @package    evlist
*/
class Dataproxy_evlist extends DataproxyDriver
{
	var $driver_name = 'evlist';

	/*
	*   Returns the location of index.php of each plugin
    *
    *   @return string  URL to plugin's index page
	*/
	function getEntryPoint()
    {
		global $_CONF;

		return $_CONF['site_url'] . '/evlist/index.php';
	}

	/**
	* Returns array of (
	*   'id'        => $id (string),
	*   'title'     => $title (string),
	*   'uri'       => $uri (string),
	*   'date'      => $date (int: Unix timestamp),
	*   'image_uri' => $image_uri (string),
	*   'raw_data'  => raw data of the item (stripslashed)
	* )
    *
    *   @return array   Array described above
	*/
	function getItemById($id, $all_langs = false)
	{
	    global $_CONF, $_TABLES;

		$retval = array();

		$sql = "SELECT e.date_start1, d.*
		        FROM {$_TABLES['evlist_events']} e
                LEFT JOIN {$_TABLES['evlist_detail']} d
                    ON e.det_id = d.det_id
			    WHERE (e.id = '" . DB_escapeString($id) . "') ";
		if ($this->uid > 0) {
			$sql .= COM_getPermSql('AND', $this->uid, 'e');
		}
		$result = DB_query($sql);
		if (DB_error()) {
			return $retval;
		}

		if (DB_numRows($result) == 1) {
			$A = DB_fetchArray($result, false);

			$retval['id']        = $id;
			$retval['title']     = $A['title'];
			$retval['uri']       = COM_buildURL(
				$_CONF['site_url'] . '/evlist/event.php?eid='
				. rawurlencode($id)
			);
			$retval['date']      = strtotime($A['date_start1']);
			$retval['image_uri'] = false;
			$retval['raw_data']  = $A;
		}

		return $retval;
	}

	/**
	* This function ignores static pages which are displayed in the
	* center block.
	*
	* Returns an array of (
	*   'id'        => $id (string),
	*   'title'     => $title (string),
	*   'uri'       => $uri (string),
	*   'date'      => $date (int: Unix timestamp),
	*   'image_uri' => $image_uri (string)
	* )
    *
    *   @return array   Array of item information
	*/
	function getItems($category, $all_langs = false)
	{
		global $_CONF, $_TABLES, $_EV_CONF;

		$entries = array();

		$sql = "SELECT e.id, d.title, UNIX_TIMESTAMP(e.date_start1) AS day
			    FROM {$_TABLES['evlist_events']} e
                LEFT JOIN {$_TABLES['evlist_detail']} d
                    ON d.ev_id=e.id ";
		if ($this->uid > 0) {
			$sql .= COM_getPermSql('WHERE', $this->uid, 2, 'e');
		}

		$sql .= ' ORDER BY e.date_start1';

		$result = DB_query($sql);
		if (DB_error()) {
			return $entries;
		}

		while (($A = DB_fetchArray($result, false)) !== FALSE) {
			$entry = array();
			$entry['id']        = $A['id'];
			$entry['title']     = $A['title'];
			$entry['uri']       = COM_buildURL(
				$_CONF['site_url'] . '/evlist/event.php?eid='
				. $A['id']
			);
			$entry['date']      = $A['day'];
			$entry['image_uri'] = false;
			$entries[] = $entry;
		}
		return $entries;
	}
}
?>
