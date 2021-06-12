<?php
/**
 * Handle the autotag to show event signups.
 *
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.6
 * @since       v1.4.6
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Autotags;

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own!');
}

/**
 * Event Signups autotag.
 * @package evlist
 */
class signups
{
    /**
     * Parse the autotag and render the output.
     *
     * @param   string  $p1         First option after the tag name
     * @param   string  $opts       Name=>Vaue array of other options
     * @param   string  $fulltag    Full autotag string
     * @return  string      Replacement HTML, if applicable.
     */
    public function parse($autotag)
    {
        global $_CONF, $_TABLES, $_USER, $LANG01;

        $skip = 0;
        $link = 0;        // 1 = link to event, 0 = title only
        $px = explode(' ', trim($autotag['parm2']));
        foreach ($px as $part) {
            $A = explode(':', $part);
            $key = $A[0];
            $val = isset($A[1]) ? strtolower($A[1]) : NULL;
            switch ($key) {
            case 'link':
                if ($val == 'true' || $val == '1') {
                    $link = 1;
                }
                $skip++;
                break;
            }
        }

        // Any leftover parts become the caption.  Borrowed from
        // Mediagallery's caption handling.
        if ($skip > 0) {
            if (count($px) > $skip) {
                for ($i = 0; $i < $skip; $i++) {
                    // Skip to the end of the processed directives
                    array_shift($px);
                }
                $caption = trim(implode(' ', $px));
            } else {
                $caption = '';
            }
        } else {
            // There weren't any control parameters, so all of parm2 is
            // the caption.
            $caption = trim($autotag['parm2']);
        }
        if ($link) {
            $caption = COM_createLink(
                $caption,
                EVLIST_URL . '/view.php?rid=' . $autotag['parm1']
            );
        }
        $retval = \Evlist\Ticket::userList_RSVP($autotag['parm1'], $caption);
        return $retval;
    }

}

?>
