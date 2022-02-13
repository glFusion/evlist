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


    /**
     * Turn off automatic resizing, to allow transparent PNG images to work.
     *
     * @param   string  $record_id  Item record ID
     * @param   string  $varname    Name of form field
     */
    public function __construct($record_id='0', $varname='logofile')
    {
        $this->setAutomaticResizing(false);
        parent::__construct($record_id, $varname);
    }


    /**
     * Get the image URL, width and height.
     * Calendar icons don't use resizing to preserve transparency.
     *
     * @param   string  $filename   Image filename
     * @param   integer $width      Desired display width (not used)
     * @param   integer $height     Desired display height (not used)
     * @return  array       Array of (url, width, height)
     */
    public static function getUrl($filename, $width=0, $height=0)
    {
        global $_CONF;

        return array(
            'url' => Config::get('imageurl') . '/' . static::$pathkey . '/' . $filename,
            'width' => $_CONF['max_topicicon_width'],
            'height' => $_CONF['max_topicicon_height'],
        );
    }


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
