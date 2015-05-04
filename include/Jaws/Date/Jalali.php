<?php
/**
 * Class to manage Jalali calendar
 *
 * @category   Jaws_Date
 * @package    Core
 * @author     Amir Mohammad Saied <amir@php.net>
 * @autho      Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Date_Jalali extends Jaws_Date
{
    /**
     * @access  private
     */
    var $_LeapYear = array(1, 5, 9, 13, 17, 22, 26, 30);

    /**
     * @access private
     */
    var $_JalaliDaysInMonthes = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

    /**
     * Is leap year
     *
     * @param   int     $year  Jalali year
     * @access  private
     * @return  boolean True/False
     */
    function IsJalaliLeapYear($year)
    {
        return in_array(($year % 33), $this->_LeapYear);
    }

    /**
     * Computing total days of Jalali calendar
     *
     * @param   int     $year   Jalali year
     * @param   int     $month  Jalali month
     * @param   int     $day    Jalali day
     * @access  public
     * @return  boolean True/False
     */
    function JalaliTotalDays($year, $month, $day)
    {
        $year--;
        $leap_days = floor($year/33)*8 + floor(($year%33)/4);

        $day_number =  365*$year + $leap_days;
        for ($i=0; $i < ($month-1); ++$i) {
            $day_number += $this->_JalaliDaysInMonthes[$i];
        }

        return $day_number + $day;
    }

    /**
     * Jalali to Gregorian Convertor
     *
     * @param   int $year  Jalali year
     * @param   int $month Jalali month
     * @param   int $day   Jalali day
     * @access  public
     * @return  array   Converted time
     */
    function ToBaseDate($year, $month = 1, $day = 1)
    {
        if ($month == 0) {
            $year--;
            $month = 12;
        }

        if ($month == 13) {
            $year++;
            $month = 1;
        }

        $year = $year - 979;
        $gregorian_day = $this->JalaliTotalDays($year, $month, $day) + 79;
        return $this->ToGregorian($gregorian_day, 1601);
    }

    /**
     * Gregorian to Jalali Convertor
     *
     * @param   int $year  Gregorian year
     * @param   int $month Gregorian month
     * @param   int $day   Gregorian day
     * @access  protected
     * @return  array   Converted time
     */
    function GregorianToJalali($year, $month, $day)
    {
        $year = $year - 1600;
        $jalali_day = $this->GregorianTotalDays($year, $month, $day) - 79;

        $jalali_year = floor($jalali_day/12053)*33; // 12053 = 33*365 + 8
        $jalali_day %= 12053;
        $jalali_year = 979 + $jalali_year + floor(($jalali_day - 1) / 1461)*4; // 1461 = 4*365 + 1
        $jalali_day = ($jalali_day - 1) % 1461 + 1;

        $jalali_year++;
        $isLeap = (int)$this->IsJalaliLeapYear($jalali_year);
        while ($jalali_day > (365 + $isLeap)) {
            $jalali_day -= (365 + $isLeap);
            $jalali_year++;
            $isLeap = (int)$this->IsJalaliLeapYear($jalali_year);
        }

        $jalali_month = 0;
        $year_days = $jalali_day;

        while ($jalali_day > $this->_JalaliDaysInMonthes[$jalali_month] + (($jalali_month==11)? $isLeap : 0))
        {
            $jalali_day -= $this->_JalaliDaysInMonthes[$jalali_month];
            $jalali_month++;
        }

        return array('year'      => $jalali_year,
                     'month'     => $jalali_month + 1,
                     'day'       => $jalali_day,
                     'monthDays' => $this->_JalaliDaysInMonthes[$jalali_month]+
                                    ($jalali_month==11 ? $isLeap : 0),
                     'yearDay'   => $year_days
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
        $date_array = explode('-', date('Y-m-d-H-i-s', $date));

        $jalali_array = $this->GregorianToJalali($date_array[0], $date_array[1], $date_array[2]);
        $jalali_array['hour']   = $date_array[3];
        $jalali_array['minute'] = $date_array[4];
        $jalali_array['second'] = $date_array[5];
        $jalali_array['date']   = $date;

        if (empty($format)) {
            $format = $GLOBALS['app']->Registry->Get('/config/date_format');
        }

        return ($format == 'since')? $this->SinceFormat($jalali_array['date']) : $this->DateFormat($format, $jalali_array);
    }

   /**
    * Format the input date.
    *
    * @param  string  $date   Date string
    * @param  string  $format Format to use
    * @return The original date with a new format
    */
    function DateFormat($format, $date)
    {
        if (empty($date)) {
            return;
        }
        $return = '';

        $i = 0;
        while ($i < strlen($format)) {
            switch($format[$i]) {
                case 'A':
                case 'a':
                    if (substr($format, $i, 3) == 'AGO') {
                        $return .= $this->SinceFormat($date['date']);
                        $i = $i + 2;
                    } else {
                        if (date('a', $date['date']) == 'pm') {
                            $return .= $this->_t_cal('DATE_HOURS_PM');
                        } else {
                            $return .= $this->_t_cal('DATE_HOURS_AM');
                        }
                    }
                    break;
                case 'c':
                    $return .= $this->DateFormat('Y-m-d H:i:s:P', $date);
                    break;
                case 'd':
                    $return .= $date['day'];
                    break;
                case 'D':
                case 'l':
                    if (substr($format, $i, 2) == 'DN') {
                        $return .= $this->DayString(date('w', $date['date']));
                        $i++;
                    } else {
                        $return .= $this->DayShortString(date('w', $date['date']));
                    }
                    break;
                case 'e':
                    $return .= date('e', $date['date']);
                    break;
                case 'F':
                case 'M':
                    if (substr($format, $i, 2) == 'MN') {
                        $return .= $this->MonthString($date['month']);
                        $i++;
                    } else {
                        $return .= $this->MonthShortString($date['month']);
                    }
                    break;
                case 'g':
                    $return .= date('g', $date['date']);
                    break;
                case 'G':
                case 'H':
                    $return .= $date['hour'];
                    break;
                case 'h':
                    $return .= date('h', $date['date']);
                    break;
                case 'i':
                    $return .= $date['minute'];
                    break;
                case 'j':
                    $return .= $date['day'];
                    break;
                case 'm':
                case 'n':
                    $return .= $date['month'];
                    break;
                case 'N':
                    $return .= date('N', $date['date']);
                    break;
                case 'O':
                    $return .= date('O', $date['date']);
                    break;
                case 'P':
                    $return .= date('P', $date['date']);
                    break;
                case 'o':
                case 'Y':
                    $return .= $date['year'];
                    break;
                case 'r':
                    $return .= $this->DateFormat('D, d M Y H:i:s O', $date);
                    break;
                case 's':
                    $return .= $date['second'];
                    break;
                case 'T':
                    $return .= date('T', $date['date']);
                    break;
                case 't':
                    $return .= $date['monthDays'];
                    break;
                case 'U':
                    $return .= date('U', $date['date']);
                    break;
                case 'y':
                    $return .= substr($date['year'], 2, 2);
                    break;
                case 'z':
                    $return .= $date['yearDay'];
                    break;
                default:
                    $return .= $format[$i];
                    break;
            }
            $i++;
        }

        return $return;
    }

}
