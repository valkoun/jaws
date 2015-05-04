<?php
/**
 * Class to manage Gregorian calendar
 *
 * @category   Jaws_Date
 * @package    Core
 * @author     Amir Mohammad Saied <amir@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Date_Gregorian extends Jaws_Date
{
    /**
     *
     * @param   int $year   Gregorian year
     * @param   int $month  Gregorian month
     * @param   int $day    Gregorian day
     * @access  public
     * @return  array   Converted time
     */
    function ToBaseDate($year, $month = 1, $day = 1)
    {
        $dt = mktime(0, 0, 0, $month, $day, $year);
        return array('timestamp' => $dt,
                     'year'      => date("Y", $dt),
                     'month'     => date("m", $dt),
                     'day'       => date("d", $dt),
                     'monthDays' => date("t", $dt),
                     'yearDay'   => date("z", $dt)
                    );
    }

    /**
     * Format the input date.
     *
     * @param  string  $date   Date string
     * @param  string  $format Format to use
     * @return The original date with a new format
     */
    function Format($date, $format = null)
    {
        if (empty($date)) {
            return '';
        }

        $date = $GLOBALS['app']->UTC2UserTime($date);

        if (empty($format)) {
            $format = $GLOBALS['app']->Registry->Get('/config/date_format');
        }

        if ($format == 'since') {
            return $this->SinceFormat($date);
        } else {
            $i = 0; 
            $return = '';
            while ($i < strlen($format)) {
                switch($format[$i]) {
                case 'A':
                    if (substr($format, $i, 3) == 'AGO') {
                        $return .= $this->SinceFormat($date);
                        $i = $i + 2;
                    }
                    break;
                case 'D':
                    if (substr($format, $i, 2) == 'DN') {
                        $return .= $this->DayString(date('w', $date));
                        $i++;
                    } else {
                        $return .= $this->DayShortString(date('w', $date));
                    }
                    break;
                case 'M':
                    if (substr($format, $i, 2) == 'MN') {
                        $return .= $this->MonthString(date('m', $date));
                        $i++;
                    } else {
                        $return .= $this->MonthShortString(date('m', $date));
                    }
                    break;
                case '\\':
                    // Do nothing 
                    break;
                default:
                    if (substr($format, $i - 1, 1) == '\\') {
                        $return .= $format[$i];
                    } else {
                        $return .= date($format[$i], $date);
                    }
                    break;
                }
                $i++;
            }

            return $return;
                 
        }
    }
}
