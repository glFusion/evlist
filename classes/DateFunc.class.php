<?php
/**
 * Date functions for Evlist.
 * Based on the Pear `Date_Calc` package which may or may not be available
 * on the target system.
 *
 * @author      Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2008 - 2010 Mark R. Evans mark AT glfusion DOT org
 * @copyright   Copyright (c) 2010 - 2018 Lee Garner <lee@leegarner.com>
 * @package     evlist
 * @version     v1.4.5
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Evlist;

// The constant telling us what day starts the week. Monday (1) is the
// international standard. Redefine this to 0 if you want weeks to
// begin on Sunday.
//define('DATE_CALC_BEGIN_WEEKDAY', 1);
if (!defined('DATE_CALC_BEGIN_WEEKDAY')) {
    global $_CONF;
    switch ($_CONF['week_start']) {
    case 'Mon':
        // week begins on Monday
        define('DATE_CALC_BEGIN_WEEKDAY', 1);
        break;
    case 'Sun':
    default:
        // week begins on Sunday
        define('DATE_CALC_BEGIN_WEEKDAY', 0);
        break;
    }
}

/**
 * Date calculation class.
 * @package evlist
 */
class DateFunc
{
    const DEF_FORMAT = 'Y-m-d';
    const CAL = CAL_GREGORIAN;

    /**
     * Returns the current local date. NOTE: This function
     * retrieves the local date using strftime(), which may
     * or may not be 32-bit safe on your system.
     *
     * @access public
     * @param   string  $format the strftime() format to return the date
     * @return  string      The current date in specified format
     */
    public static function dateNow($format='')
    {
        global $_CONF;

        if ($format == '') {
            $format = self::DEF_FORMAT;
        }
        return $_CONF['_now']->format($format, true);
    }


    /**
     * Get a date object for the provided date values.
     *
     * @param   integer $day    Day of month
     * @param   integer $month  Month number
     * @param   integer $year   Year number
     * @return  object          Date object
     */
    public static function getDate($day=0, $month=0, $year=0)
    {
        global $_CONF;

        list($day, $month, $year) = self::validateParams($day, $month, $year);
        $dt = new \Date(sprintf('%d-%02d-%02d', $year, $month, $day), $_CONF['timezone']);
        return $dt;
    }


    /**
     * Validate the day, month and year parameters for other functions.
     * If any are zero, then use the current date's value.
     *
     * @param   integer $day    Day value
     * @param   integer $month  Month value
     * @param   integer $year   Year value
     * @return  array       Array of valid values
     */
    public static function validateParams($day, $month, $year)
    {
        if ($day == 0) {
            $day = self::getDay();
        }
        if ($month == 0) {
            $month = self::getMonth();
        }
        if ($year == 0) {
            $year = self::getYear();
        }
        return array($day, $month, $year);
    }


    /**
     * Returns true for valid date, false for invalid date.
     * Simple wrapper for checkdate() to use standard parameter ordering
     * for this class.
     *
     * @access  public
     * @param   string  $day    Day in format DD
     * @param   string  $month  Month in format MM
     * @param   string  $year   Year in format CCYY
     * @return  boolean     true/false
     */
    public static function isValidDate($day, $month, $year)
    {
        return checkdate((int)$month, (int)$day, (int)$year);
    }


    /**
     * Determine if a given year is a leap year.
     *
     * @param   string  $year   Year to check.
     * @return  boolean     True if $year is a leap year
     */
    public static function isLeapYear($year=0)
    {
        list($day, $month, $year) = self::validateParams(1, 1, $year);
        if ($year < 1000) {
            return false;
        }
        return (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0);
    }


    /**
     * Determines if given date is a future date from now.
     * May be called with no parameters, but should include at least one
     * to make any sense.
     *
     * @access  public
     * @param   string  $day    Day in format DD
     * @param   string  $month  Month in format MM
     * @param   string  $year   Year in format CCYY
     * @return  boolean true/false
     */
    public static function isFutureDate($day=0, $month=0, $year=0)
    {
        list($day, $month, $year) = self::validateParams($day, $month, $year);
        $this_year = self::getYear();
        $this_month = self::getMonth();
        $this_day = self::getDay();

        if ($year > $this_year) {
            return true;
        } elseif ($year == $this_year) {
            if ($month > $this_month) {
                return true;
            } elseif ($month == $this_month) {
                if ($day > $this_day) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Determines if given date is a past date from now.
     *
     * @access  public
     * @param   string  $day    Day in format DD
     * @param   string  $month  Month in format MM
     * @param   string  $year   Year in format CCYY
     * @return  boolean true/false
     */
    public static function isPastDate($day, $month, $year)
    {
        list($day, $month, $year) = self::validateParams($day, $month, $year);
        $this_year = self::getYear();
        $this_month = self::getMonth();
        $this_day = self::getDay();

        if ($year < $this_year) {
            return true;
        } elseif ($year == $this_year) {
            if ($month < $this_month) {
                return true;
            } elseif ($month == $this_month) {
                if ($day < $this_day) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Returns day of week for given date, 0=Sunday.
     *
     * @access  public
     * @param   string  $day    Day in format DD, default is current local day
     * @param   string  $month  Month in format MM, default is current local month
     * @param   string  $year   Year in format CCYY, default is current local year
     * @return  integer     Weekday number
     */
    public static function dayOfWeek($day=0, $month=0, $year=0)
    {
        return self::getDate($day, $month, $year)->format('w');
    }


    /**
     * Returns week of the year, first Sunday is first day of first week.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @return  integer         Week Number
     */
    public static function weekOfYear($day=0, $month=0, $year=0)
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $week_year = $year - 1501;
        $week_day = $week_year * 365 + floor($week_year / 4) - 29872 + 1 -
            floor($week_year / 100) + floor(($week_year - 300) / 400);

        $week_number =
            ceil((self::julianDate($day, $month, $year) + floor(($week_day + 4) % 7)) / 7);
        return $week_number;
    }


    /**
     * Returns number of days since 31 December of year before given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @return  integer         Julian date
     */
    public static function julianDate($day=0, $month=0, $year=0)
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $days = array(0,31,59,90,120,151,181,212,243,273,304,334);
        $julian = ($days[$month - 1] + $day);
        if ($month > 2 && self::isLeapYear($year)) {
            $julian++;
        }
        return($julian);
    }


    /**
     * Returns quarter of the year for given date.
     *
     * @param   string  $month  Month in format MM, default current local month
     * @return  integer         Quarter (1 - 4)
     */
    public static function quarterOfYear($month=0)
    {
        if (empty($month)) {
            $month = self::getMonth();
        }
        return (int)(($month - 1) / 3 + 1);
    }


    /**
     * Returns date of begin of next month of given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function beginOfNextMonth($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $day = 1;
        if ($month < 12) {
            $month++;
        } else {
            $year++;
            $month = 1;
        }
        return self::dateFormat($day, $month, $year, $format);
    }


    /**
     * Returns date of the last day of next month of given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function endOfNextMonth($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        if ($month < 12) {
            $month++;
        } else {
            $year++;
            $month=1;
        }
        $day = self::daysInMonth($month, $year);
        return self::dateFormat($day, $month, $year, $format);
    }


    /**
     * Returns date of the first day of previous month of given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function beginOfPrevMonth($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        if ($month > 1) {
            $month--;
            $day=1;
        } else {
            $year--;
            $month=12;
            $day=1;
        }
        return self::dateFormat($day, $month, $year, $format);
    }


    /**
     * Returns date of the last day of previous month for given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function endOfPrevMonth($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMolnth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        if ($month > 1) {
            $month--;
        } else {
            $year--;
            $month=12;
        }

        $day = self::daysInMonth($month, $year);
        return self::dateFormat($day, $month, $year, $format);
    }


    /**
     * Returns date of the next weekday of given date, skipping from Friday to Monday.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function nextWeekday($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $days = self::dateToDays($day, $month, $year);
        $dow = self::dayOfWeek($day, $month, $year);
        if ($dow  == 5) {
            $days += 3;
        } elseif ($dow == 6) {
            $days += 2;
        } else {
            $days += 1;
        }
        return self::daysToDate($days, $format);
    }


    /**
     * Returns date of the previous weekday, skipping from Monday to Friday.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function prevWeekday($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $days = self::dateToDays($day, $month, $year);
        if (self::dayOfWeek($day, $month, $year) == 1) {
            $days -= 3;
        } elseif (self::dayOfWeek($day, $month, $year) == 0) {
            $days -= 2;
        } else {
            $days -= 1;
        }
        return(self::daysToDate($days, $format));
    }


    /**
     * Returns date of the next specific day of the week
     * from the given date.
     *
     * @param   integer $dow    Day of week, 0=Sunday
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @param   boolean $onOrAfter  If true and days are same, returns current day
     * @return  string          Ddate in given format
     */
    public static function nextDayOfWeek($dow, $day=0, $month=0, $year=0, $format='', $onOrAfter=false)
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $days = self::dateToDays($day, $month, $year);
        $curr_weekday = self::dayOfWeek($day, $month, $year);
        if ($curr_weekday == $dow) {
            if (!$onOrAfter) {
                $days += 7;
            }
        } elseif ($curr_weekday > $dow) {
            $days += 7 - ($curr_weekday - $dow);
        } else {
            $days += $dow - $curr_weekday;
        }
        return self::daysToDate($days, $format);
    }


    /**
     * Returns date of the previous specific day of the week
     * from the given date.
     *
     * @param   integer $dow    Day of week, 0=Sunday
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @param   boolean $onOrBefore True for before current date, False for after
     * @return  string          Date in given format
     */
    public static function prevDayOfWeek($dow, $day=0, $month=0, $year=0, $format='', $onOrBefore=false)
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $days = self::dateToDays($day, $month, $year);
        $curr_weekday = self::dayOfWeek($day, $month, $year);
        if ($curr_weekday == $dow) {
            if (!$onOrBefore) {
                $days -= 7;
            }
        } elseif ($curr_weekday < $dow) {
            $days -= (7 - ($dow - $curr_weekday));
        } else {
            $days -= $curr_weekday - $dow;
        }
        return(self::daysToDate($days, $format));
    }


    /**
     * Returns date of the next specific day of the week on or after the given date.
     *
     * @param   integer $dow    Day of week, 0=Sunday
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function nextDayOfWeekOnOrAfter($dow, $day=0, $month=0, $year=0, $format='')
    {
        return self::nextDayOfWeek($dow, $day, $month, $year, $format, true);
    }


    /**
     * Returns date of the previous specific day of the week on or before the given date.
     *
     * @param   integer $dow    Day of week, 0=Sunday
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function prevDayOfWeekOnOrBefore($dow, $day=0, $month=0, $year=0, $format='')
    {
        return self::prevDayOfWeek($dow, $day, $month, $year, $format, true);
    }


    /**
     * Returns date of day after given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function nextDay($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $days = self::dateToDays($day, $month, $year);
        return(self::daysToDate($days + 1, $format));
    }


    /**
     * Returns date of day before given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function prevDay($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $days = self::dateToDays($day, $month, $year);
        return self::daysToDate($days - 1, $format);
    }


    /**
     * Returns number of days between two given dates.
     *
     * @param   string  $day1   Start Day in format DD, default current local day
     * @param   string  $month1 Start Month in format MM, default current local month
     * @param   string  $year1  Start Year in format CCYY, default current local year
     * @param   string  $day2   End Day in format DD, default current local day
     * @param   string  $month2 End Month in format MM, default current local month
     * @param   string  $year2  End Year in format CCYY, default current local year
     * @return  integer     Absolute value of number of days between dates, -1 for error
     */
    public static function dateDiff($day1, $month1, $year1, $day2, $month2, $year2)
    {
        if (!checkdate($month1, $day1, $year1) ||
            !checkdate($month2, $day2, $year2)) {
            return -1;
        }
        $date1 = new \Date(sprintf('%d-%02d-%02d', $year1, $month1, $day1));
        $date2 = new \Date(sprintf('%d-%02d-%02d', $year2, $month2, $day2));
        $diff = $date1->diff($date2);
        return $diff->days;
    }


    /**
     * Find the number of days in the given month.
     *
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year as YYYY, used only for leap year
     * @return  integer     Number of days in the month
     */
    public static function daysInMonth($month=0, $year=0)
    {
        list($day, $month, $year) = self::validateParams(1, $month, $year);
        return cal_days_in_month(self::CAL, $month, $year);
    }


    /**
     * Returns the number of rows on a calendar month.
     * Useful for determining the number of rows when displaying a typical
     * month calendar.
     *
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @return  integer         Number of weeks or partial weeks
     */
    public static function weeksInMonth($month="",$year="")
    {
        if (empty($year)) {
            $year = self::dateNow("%Y");
        }
        if (empty($month)) {
            $month = self::dateNow("%m");
        }
        if (DATE_CALC_BEGIN_WEEKDAY == 1) {
            // starts on monday
            if (self::firstOfMonthWeekday($month,$year) == 0) {
                $first_week_days = 1;
            } else {
                $first_week_days = 7 - (self::firstOfMonthWeekday($month,$year) - 1);
            }
        } elseif (DATE_CALC_BEGIN_WEEKDAY == 6) {
            // starts on saturday
            if (self::firstOfMonthWeekday($month,$year) == 0) {
                $first_week_days = 6;
            } else {
                $first_week_days = 7 - (self::firstOfMonthWeekday($month,$year) + 1);
            }
        } else {
            // starts on sunday
            $first_week_days = 7 - self::firstOfMonthWeekday($month,$year);
        }
        return ceil(((self::daysInMonth($month,$year) - $first_week_days) / 7) + 1);
    }


    /**
     * Find the day of the week for the first of the month of given date.
     *
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @return  integer         Number of weekday for the first day, 0=Sunday
     */
    public static function firstOfMonthWeekday($month=0, $year=0)
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        return self::dayOfWeek(1, $month, $year);
    }


    /**
     * Return date of first day of month of given date.
     *
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function beginOfMonth($month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        return self::dateFormat(1, $month, $year, $format);
    }


    /**
     * Find the month day of the beginning of week for given date,
     * Using DATE_CALC_BEGIN_WEEKDAY. (can return weekday of prev month.)
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function beginOfWeek($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $this_weekday = self::dayOfWeek($day, $month, $year);
        if (DATE_CALC_BEGIN_WEEKDAY == 1) {
            if ($this_weekday == 0) {
                $beginOfWeek = self::dateToDays($day, $month, $year) - 6;
            } else {
                $beginOfWeek = self::dateToDays($day, $month, $year) - $this_weekday + 1;
            }
        } else {
            $beginOfWeek = self::dateToDays($day, $month, $year) - $this_weekday;
            /*  $beginOfWeek = (self::dateToDays($day, $month, $year)
                - ($this_weekday - DATE_CALC_BEGIN_WEEKDAY)); */
        }
        return self::daysToDate($beginOfWeek, $format);
    }


    /**
     * Find the month day of the end of week for given date,
     * using DATE_CALC_BEGIN_WEEKDAY. (can return weekday
     * of following month.)
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function endOfWeek($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $this_weekday = self::dayOfWeek($day, $month, $year);
        $last_dayOfWeek = self::dateToDays($day, $month, $year) + (6 - $this_weekday + DATE_CALC_BEGIN_WEEKDAY);

        return self::daysToDate($last_dayOfWeek, $format);
    }


    /**
     * Find the month day of the beginning of week after given date,
     * Using DATE_CALC_BEGIN_WEEKDAY. (can return weekday of prev month.)
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function beginOfNextWeek($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $date = self::daysToDate(self::dateToDays($day+7, $month, $year));
        $next_week_year = substr($date, 0, 4);
        $next_week_month = substr($date, 5, 2);
        $next_week_day = substr($date, 8, 2);
        $this_weekday = self::dayOfWeek($next_week_day, $next_week_month, $next_week_year);
        $beginOfWeek = self::dateToDays($next_week_day, $next_week_month, $next_week_year) - ($this_weekday - DATE_CALC_BEGIN_WEEKDAY);
        return self::daysToDate($beginOfWeek, $format);
    }


    /**
     * Find the month day of the beginning of week before given date,
     * Using DATE_CALC_BEGIN_WEEKDAY. (can return weekday of prev month.)
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function beginOfPrevWeek($day=0, $month=0, $year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $date = self::daysToDate(self::dateToDays($day-7, $month, $year));
        $next_week_year = substr($date,0,4);
        $next_week_month = substr($date,4,2);
        $next_week_day = substr($date,6,2);

        $this_weekday = self::dayOfWeek($next_week_day, $next_week_month, $next_week_year);
        $beginOfWeek = self::dateToDays($next_week_day , $next_week_month, $next_week_year)
            - ($this_weekday - DATE_CALC_BEGIN_WEEKDAY);

        return self::daysToDate($beginOfWeek, $format);
    }


    /**
     * Return an array with days in week
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  array           $week[$weekday]
     */
    public static function getCalendarWeek($day=0, $month=0, $year=0, $format='')
    {
        list($day, $month, $year) = self::validateParams($day, $month, $year);
        $week_array = array();

        // date for the column of week
        $curr_day = self::beginOfWeek($day, $month, $year, 'E');
        for ($counter=0; $counter <= 6; $counter++) {
            $week_array[$counter] = self::daysToDate($curr_day, $format);
            $curr_day++;
        }
        return $week_array;
    }


    /**
     * Return a set of arrays to construct a calendar month for the given date.
     *
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  array           $month[$row][$col]
     */
    public static function getCalendarMonth($month=0, $year=0, $format='')
    {
        list($day, $month, $year) = self::validateParams(1, $month, $year);
        $month_array = array();
        // starts on monday
        if (DATE_CALC_BEGIN_WEEKDAY == 1) {
            if (self::firstOfMonthWeekday($month, $year) == 0) {
                $curr_day = self::dateToDays(1, $month, $year) - 6;
            } else {
                $curr_day = self::dateToDays(1, $month, $year) - self::firstOfMonthWeekday($month, $year) + 1;
            }
        // starts on saturday
        } elseif (DATE_CALC_BEGIN_WEEKDAY == 6) {
            if (self::firstOfMonthWeekday($month, $year) == 0) {
                $curr_day = self::dateToDays(1, $month, $year) - 1;
            } else {
                $curr_day = self::dateToDays(1, $month, $year) - self::firstOfMonthWeekday($month, $year) - 1;
            }
        // starts on sunday
        } else {
            $curr_day = (self::dateToDays(1, $month, $year) - self::firstOfMonthWeekday($month, $year));
        }
        // number of days in this month
        $daysInMonth = self::daysInMonth($month, $year);
        $weeksInMonth = self::weeksInMonth($month, $year);
        for ($row = 0; $row < $weeksInMonth; $row++) {
            for ($col = 0; $col < 7; $col++) {
                $month_array[$row][$col] = self::daysToDate($curr_day, $format);
                $curr_day++;
            }
        }
        return $month_array;
    }


    /**
     * Return a set of arrays to construct a calendar year for the given date.
     *
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  array           $year[$month][$row][$col]
     */
    public static function getCalendarYear($year=0, $format='')
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        $year_array = array();

        for($month = 0; $month < 12; $month++) {
            $year_array[$month] = self::getCalendarMonth($month + 1, $year, $format);
        }
        return $year_array;
    }


    /**
     * Converts a date to number of days since a distant unspecified epoch.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @return  integer         Number of days
     */
    public static function dateToDays($day=0, $month=0, $year=0)
    {
        list($day, $month, $year) = self::validateParams($day, $month, $year);

        $century = (int)($year / 100);
        $year = $year % 100;

        if ($month > 2) {
            $month -= 3;
        } else {
            $month += 9;
            if ($year) {
                $year--;
            } else {
                $year = 99;
                $century --;
            }
        }

        return ( floor((  146097 * $century)    /  4 ) +
                floor(( 1461 * $year)        /  4 ) +
                floor(( 153 * $month +  2) /  5 ) +
                    $day +  1721119);
    }


    /**
     * Converts number of days to a distant unspecified epoch.
     *
     * @param   integer $days   Number of days
     * @param   string  $format Format for returned date
     * @return  string          Date in specified format
     */
    public static function daysToDate($days, $format='')
    {
        $days       -= 1721119;
        $century    = floor(( 4 * $days -  1) /  146097);
        $days       = floor(4 * $days - 1 - 146097 * $century);
        $day        = floor($days /  4);

        $year       = floor(( 4 * $day +  3) /  1461);
        $day        = floor(4 * $day +  3 -  1461 * $year);
        $day        = floor(($day +  4) /  4);

        $month      = floor(( 5 * $day -  3) /  153);
        $day        = floor(5 * $day -  3 -  153 * $month);
        $day        = floor(($day +  5) /  5);

        if ($month < 10) {
            $month +=3;
        } else {
            $month -=9;
            if ($year++ == 99) {
                $year = 0;
                $century++;
            }
        }
        //$century = sprintf("%02d", $century);
        //$year = sprintf("%02d", $year);
        return self::dateFormat($day, $month, ($century*100) + $year, $format);
    }


    /**
     * Calculates the date of the Nth weekday of the month.
     * Example: the second Saturday of January 2000.
     *
     * @param   string  $occurrence      Occurence: 1=first, 2=second, 3=third, etc.
     * @param   string  $dayOfWeek      0=Sunday, 1=Monday, etc.
     * @param   string  $month          Month in format MM
     * @param   string  $year           Year in format CCYY
     * @param   string  $format         Format for returned date
     * @return  string              Date in given format
     */
    public static function NWeekdayOfMonth($occurrence, $dayOfWeek, $month, $year, $format='')
    {
        $year = (int)$year;
        $month = (int)$month;
        $occurrence = (int)$occurrence;

        $DOW1day = ($occurrence - 1) * 7 + 1;
        $DOW1 = self::dayOfWeek($DOW1day, $month, $year);

        $wdate = ($occurrence - 1) * 7 + 1 +
                (7 + $dayOfWeek - $DOW1) % 7;

        if ($wdate > self::daysInMonth($month, $year)) {
            if ($occurrence == 5) {
                // Getting the last day overshot the month, go back a week
                $wdate -= 7;
                return self::dateFormat($wdate, $month, $year, $format);
            } else {
                // For $occurrence === 1 through 4 this is an error
                return -1;
            }
        } else {
            return(self::dateFormat($wdate, $month, $year, $format));
        }
    }


    /**
     * Formats the date in the given format, much like strfmt().
     * This version uses the PHP `date` object, unlike the original Pear version.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   string  $format Format for returned date
     * @return  string          Date in given format
     */
    public static function dateFormat($day, $month, $year, $format='')
    {
        global $_CONF;

        if (!self::isValidDate($day, $month, $year)) {
            $year = self::getYear();
            $month = self::getMonth();
            $day = self::getDay();
        }
        if ($format == '') {
            $format = self::DEF_FORMAT;
        }
        $output = '';
        switch ($format) {
        case 'E':
            $output = self::dateToDays($day, $month, $year);
            break;
        default:
            $dt = new \Date(sprintf('%d-%02d-%02d', $year, $month, $day), $_CONF['timezone']);
            $output = $dt->format($format);
            break;
        }
        return $output;
    }


    /**
     * Returns the current local year in format CCYY.
     *
     * @return  string  Current year in format CCYY
     */
    public static function getYear()
    {
        return self::dateNow('Y');
    }

    /**
     * Returns the current local month number
     *
     * @return  string  Current month number
     */
    public static function getMonth()
    {
        return self::dateNow('m');
    }


    /**
     * Returns the current local day of the month.
     *
     * @return  string  Current day of the month
     */
    public static function getDay()
    {
        return self::dateNow('d');
    }


    /**
     * Returns the full month name for the given month.
     *
     * @param   string  $month  Month in format MM
     * @return  string          Full month name
     */
    public static function getMonthFullname($month)
    {
        $month = (int)$month;
        if ($month == 0) {
            $month = self::getMonth();
        }

        $month_names = self::getMonthNames();
        return $month_names[$month];
        // getMonthNames returns months with correct indexes
        //return $month_names[($month - 1)];
    }


    /**
     * Returns the abbreviated month name for the given month.
     *
     * @uses    self::getMonthFullname
     * @param   string  $month   Month in format MM
     * @param   integer $length Optional length of abbreviation, default is 3
     * @return  string      Abbreviated month name
     */
    public static function getMonthAbbrname($month, $length=3)
    {
        $month = (int)$month;

        if (empty($month)) {
            $month = self::getMonth();
        }
        return substr(self::getMonthFullname($month), 0, $length);
    }


    /**
     * Returns the full weekday name for the given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @return  string          Full month name
     */
    public static function getWeekdayFullname($day=0, $month=0, $year=0)
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }

        $weekday_names = self::getWeekDays();
        $weekday = self::dayOfWeek($day, $month, $year);
        return $weekday_names[$weekday];
    }


    /**
     * Returns the abbreviated weekday name for the given date.
     *
     * @param   string  $day    Day in format DD, default current local day
     * @param   string  $month  Month in format MM, default current local month
     * @param   string  $year   Year in format CCYY, default current local year
     * @param   integer $length Optional length of abbreviation, default is 3
     *
     * @access public
     *
     * @return string full month name
     * @see self::getWeekdayFullname
     */

    public static function getWeekdayAbbrname($day=0, $month=0, $year=0, $length=3)
    {
        if (empty($year)) {
            $year = self::getYear();
        }
        if (empty($month)) {
            $month = self::getMonth();
        }
        if (empty($day)) {
            $day = self::getDay();
        }
        return substr(self::getWeekdayFullname($day, $month, $year), 0, $length);
    }


    /**
     * Returns the numeric month from the month name or an abreviation
     *
     * Both August and Aug would return 8.
     * Month name is case insensitive.
     *
     * @param   string  $month  Month name
     * @return  integer         Month number
     */
    public static function getMonthFromFullName($month)
    {
        $month = strtolower($month);
        $months = self::getMonthNames();
        foreach ($months as $id=>$name) {
            if (ereg($month, strtolower($name))) {
                return $id;
            }
        }
        return 0;
    }


    /**
     * Retunrs an array of month names
     *
     * Used to take advantage of the setlocale function to return
     * language specific month names.
     * @todo cache values to some global array to avoid preformace hits when called more than once.
     *
     * @returns array An array of month names
     */
    public static function getMonthNames()
    {
        static $months = NULL;

        if ($months === NULL) {
            $months = array();
            for ($i = 1; $i < 13; $i++) {
                $months[$i] = strftime('%B', mktime(0, 0, 0, $i, 1, 2001));
            }
        }
        return $months;
    }


    /**
     * Returns an array of week days.
     *
     * Used to take advantage of the setlocale function to
     * return language specific week days.
     * @todo cache values to some global array to avoid preformace hits when called more than once.
     *
     * @return  array An array of week day names
     */
    public static function getWeekDays()
    {
        $weekdays = array();
        for ($i = 0; $i <7; $i++) {
            $weekdays[$i] = strftime('%A', mktime(0, 0, 0, 1, $i, 2001));
        }
        return($weekdays);
    }


    /**
     * Returns the week of the month in which a date falls.
     *
     * @param   integer $day    Day of the month
     * @return  integer         Week number
     */
    public static function weekOfMonth($day)
    {
        $prevweek = $day - 7;
        if ($prevweek > 21) {
            $instance = 5;
        } elseif ($prevweek > 14) {
            $instance = 4;
        } elseif ($prevweek > 7) {
            $instance = 3;
        } elseif ($prevweek > 0) {
            $instance = 2;
        } else {
            $instance = 1;
        }
        return $instance;
    }


    /**
     * Returns the end date of the month.
     *
     * @param   integer $month  Month
     * @param   integer $year   Year
     * @param   string  $format Format to use for the date
     * @return  string  Formatted date for last day
     */
    public static function endOfMonth($month=0, $year=0, $format='')
    {
        $day = self::daysInMonth($month, $year);
        return self::dateFormat($day, $month, $year, $format);
     }


    /**
     * Convert the hour from 12-hour time to 24-hour.
     * This is meant to convert incoming values from forms to 24-hour format. If
     * the site uses 24-hour time, the form values should already be that way
     * (and there will be no am/pm indicator), so the hour is returned unchanged.
     *
     * @param   integer $hour   Hour to check (0 - 23)
     * @param   string  $ampm   Either 'am' or 'pm'
     * @return  integer         Hour after switching it to 24-hour time.
     */
    public static function conv12to24($hour, $ampm='')
    {
        global $_CONF;

        $hour = (int)$hour;

        if ($hour < 0 || $hour > 23) $hour = 0;
        if ($_CONF['hour_mode'] == 24) return $hour;

        if ($ampm == 'am' && $hour == 12) $hour = 0;
        if ($ampm == 'pm' && $hour < 12) $hour += 12;

        return $hour;
    }


    /**
     * Get the 12-hour string from a 24-hour time value.
     * The returned value is actually the 24-hour format depending on $_CONF.
     * Used to set the times for the event entry form. Time displays are controlled
     * by the global locale configuration.
     *
     * @param   string  $time_str   24-hour time string, e.g. "14:30"
     * @return  string      12-hour string, e.g. "02:30 PM"
     */
    public static function conv24to12($time_str)
    {
        global $_CONF;

        if ($_CONF['hour_mode'] == '12') {
            return date("h:i A", strtotime($time_str));
        } else {
            return $time_str;
        }
    }


    /**
     * Get the day names for a week based on week start day of Sun or Mon.
     * Used to create calendar headers for weekly, monthly and yearly views.
     *
     * @param   integer $letters    Optional number of letters to return
     * @return  array       Array of day names for a week, 0-indexed
     */
    public static function getDayNames($letters = 0)
    {
        global $_CONF, $LANG_WEEK;

        $retval = array();

        if ($_CONF['week_start'] == 'Sun') {
            $keys = array(1, 2, 3, 4, 5, 6, 7);
        } else {
            $keys = array(2, 3, 4, 5, 6, 7, 1);
        }

        for ($i = 0; $i < 7; $i++) {
            if ($letters > 0) {
                $retval[$i] = substr($LANG_WEEK[$keys[$i]], 0, $letters);
            } else {
                $retval[$i] = $LANG_WEEK[$keys[$i]];
            }
        }
        return $retval;
    }


    /**
     * Get an array of option lists for year, month, day, etc.
     *
     * @param   string  $prefix     Prefix to use for ampm variable name
     * @param   string  $curtime    SQL-formatted time to use as default
     * @return  array               Array of option lists, indexed by type
     */
    public static function TimeSelect($prefix, $curtime = '')
    {
        global $_CONF;

        // Use "now" as the default if nothing else sent.  Also helps make sure
        // that the explode() function works right.
        if (empty($curtime)) {
            $curtime = $_CONF['_now']->format('H:i:s', true);
        }
        $parts = explode(':', $curtime);
        $hour = $parts[0];
        $minute = $parts[1];
        if (isset($parts[2])) {
            $second = $parts[2];
        } else {
            $second = 0;
        }

        // Set up the time if we're using 12-hour mode
        if ($_CONF['hour_mode'] == 12) {
            $ampm = $hour < 12 ? 'am' : 'pm';
            if ($hour == 0) {
                $hour = 12;
            } elseif ($hour > 12) {
                $hour -= 12;
            }
        }

        $hourselect     = COM_getHourFormOptions($hour, $_CONF['hour_mode']);
        $minuteselect   = COM_getMinuteFormOptions($minute, 15);

        // This function gets the entire selection, not just the <option> parts,
        // so we use $prefix to create the variable name.
        $ampm_select    = COM_getAmPmFormSelection($prefix . '_ampm', $ampm);

        return array(
            'hour'      => $hourselect,
            'minute'    => $minuteselect,
            'ampm'      => $ampm_select
        );
    }

}

?>
