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
 * Class to describe event statuses.
 * @package evlist
 */
class Status
{
    const DISABLED = 0;
    const ENABLED = 1;
    const CANCELLED = 2;
    const ALL = 15;     // should be enough for growth...
}
