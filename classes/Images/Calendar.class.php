<?php
/**
 * Class to handle category images.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     shop
 * @version     v1.5.4
 * @since       v1.5.4
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Images;
use Evlist\Calendar as classCal;
use Evlist\Config;


/**
 * Image-handling class.
 * @package shop
 */
class Calendar extends \Evlist\Image
{
    /** Key into the configuration where the image path can be found.
     * @var string */
    protected static $pathkey = 'calendar';

    /** Maximum width, in pixels. Used if no width is given in getImage functions.
     * @var integer */
    protected static $maxwidth = 300;

    /** Maximum height, in pixels. Used if no width is given in getImage functions.
     * @var integer */
    protected static $maxheight = 300;


    /**
     * Delete a calendar logo image from disk and the table.
     * Intended to be called from ajax.php.
     *
     * @param   integer $rec_id     Record ID
     * @param   string  $nonce      Nonce, not used here
     * @return  boolean     True if image is deleted, False if not
     */
    public static function DeleteImage($rec_id, $nonce)
    {
        $classCal = Calendar::getInstance($rec_id);
        if ($classCal->getID()) {
            @unlink(Config::get('tmpdir') . self::$path . "/" . $classCal->getImageName());
            $classCal->setImageName('')->Save();
        }
        return true;
    }

}
