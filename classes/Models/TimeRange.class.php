<?php
/**
 * Class to describe event statuses.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.5.0
 * @since       v1.5.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist\Models;

/**
 * Class to enumerate time ranges
 * @package evlist
 */
class TimeRange
{
    const PAST = 1;
    const UPCOMING = 2;
    const WEEK = 3;
    const MONTH = 4;
}
