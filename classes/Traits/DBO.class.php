<?php
/**
 * DataBase Object trait to provide common functions for other classes.
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
namespace Evlist\Traits;
use glFusion\Database\Database;
use glFusion\Log\Log;
use Evlist\Cache;


/**
 * Utility trait containing common database operations.
 * Classes using this trait must define at least the `$TABLE` variable.
 * @package evlist
 */
trait DBO
{
    /** Key field name. Can be overridden by defining `$F_ID`.
     * @var string */
    protected static $_F_ID = 'id';

    /** Order field name. Can be overridden by defining `$F_ORDERBY`.
     * @var string */
    protected static $_F_ORDERBY = 'orderby';


    /**
     * Move a record up or down the admin list.
     *
     * @param   string  $id     ID field value
     * @param   string  $where  Direction to move (up or down)
     */
    public static function moveRow(string $id, string $where) : void
    {
        global $_TABLES;

        // Do nothing if the derived class did not specify a table key.
        if (static::$TABLE == '') {
            return;
        }

        switch ($where) {
        case 'up':
            $oper = '-';
            break;
        case 'down':
            $oper = '+';
            break;
        default:
            $oper = 'invalid';
            break;
        }

        if ($oper != 'invalid') {
            $f_orderby = isset(static::$F_ORDERBY) ? static::$F_ORDERBY : static::$_F_ORDERBY;
            $f_id = isset(static::$F_ID) ? static::$F_ID : static::$_F_ID;
            if (!empty($oper)) {
                $db = Database::getInstance();
                try {
                    $db->conn->executeStatement(
                        "UPDATE {$_TABLES[static::$TABLE]}
                        SET $f_orderby = $f_orderby $oper 11
                        WHERE $f_id = ?",
                        array($id),
                        array(Database::STRING)
                    );
                    self::reOrder();
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                }
            }
        }
    }


    /**
     * Reorder all records.
     *
     * @param   ?string $f_orderby  Optional field for initial ordering
     */
    public static function reOrder(?string $f_orderby = NULL) : void
    {
        global $_TABLES;

        // Do nothing if the derived class did not specify a table key.
        if (!isset(static::$TABLE)) {
            return;
        }

        if ($f_orderby === NULL) {
            $f_orderby = isset(static::$F_ORDERBY) ? static::$F_ORDERBY : static::$_F_ORDERBY;
        }
        $f_id = isset(static::$F_ID) ? static::$F_ID : static::$_F_ID;
        $table = $_TABLES[static::$TABLE];
        $db = Database::getInstance();
        try {
            $stmt = $db->conn->executeQuery(
                "SELECT $f_id, $f_orderby FROM $table
                ORDER BY $f_orderby ASC"
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            $order = 10;
            $stepNumber = 10;
            while ($A = $stmt->fetchAssociative()) {
                if ($A[$f_orderby] != $order) {  // only update incorrect ones
                    try {
                        $db->conn->update(
                            $table,
                            array($f_orderby => $order),
                            array($f_id => $A['f_id']),
                            array(Database::INTEGER, Database::STRING)
                        );
                    } catch (\Throwable $e) {
                        Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                    }
                }
                $order += $stepNumber;
            }
        }
    }


    /**
     * Sets a boolean field to the opposite of the supplied value.
     *
     * @param   integer $oldvalue   Old (current) value
     * @param   string  $varname    Name of DB field to set
     * @param   integer $id         ID of record to modify
     * @return  integer     New value, or old value upon failure
     */
    private static function _toggle($oldvalue, $varname, $id)
    {
        global $_TABLES;

        // Do nothing if the derived class did not specify a table key.
        if (!isset(static::$TABLE)) {
            return $oldvalue;
        }

        $f_id = isset(static::$F_ID) ? static::$F_ID : static::$_F_ID;

        // Determing the new value (opposite the old)
        $oldvalue = $oldvalue == 1 ? 1 : 0;
        $newvalue = $oldvalue == 1 ? 0 : 1;

        $db = Database::getInstance();
        try {
            $db->conn->update(
                $_TABLES[static::$TABLE],
                array($varname => $newvalue),
                array($f_id => $id),
                array(Database::INTEGER, Database::STRING)
            );
            Cache::clear();
            return $newvalue;
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            return $oldvalue;
        }
    }


    /**
     * Public-facing function to toggle some field from oldvalue.
     * Used for objects that don't have their own Toggle function and don't
     * need any other action taken, like clearing caches.
     *
     * @param   integer $oldval Original value to be changed
     * @param   string  $field  Name of field to change
     * @param   mixed   $id     Record ID
     * @return  integer     New value on success, Old value on error
     */
    public static function Toggle($oldval, $field, $id)
    {
        return self::_toggle($oldval, $field, $id);
    }

}
