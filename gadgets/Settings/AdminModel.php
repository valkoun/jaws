<?php
/**
 * Settings Core Gadget
 *
 * @category   GadgetModel
 * @package    Settings
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class SettingsAdminModel extends Jaws_Model
{
    /**
     * Installs the gadget
     *
     * @access       public
     * @return       true on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        $GLOBALS['app']->Registry->NewKey('/gadgets/Settings/pluggable', 'false');
        return true;
    }

    /**
     * Get the available calendars
     *
     * @access   public
     * @return   Array   Array with available calendars and Jaws_Error otherwise
     */
    function GetCalendarList()
    {
        $calendars = array();
        $path = JAWS_PATH . 'include/Jaws/Date';
        if (is_dir($path)) {
            $dir = scandir($path);
            foreach ($dir as $calendar) {
                if (stristr($calendar, '.php')) {
                    $calendar = str_replace('.php', '', $calendar);
                    $calendars[$calendar] = $calendar;
                }
            }

            return $calendars;
        }

        return false;
    }

    /**
     * Get the available editors
     *
     * @access   public
     * @return   Array   Array with available editors and Jaws_Error otherwise
     */
    function GetEditorList()
    {
        $editors = array();
        $editors['TextArea'] = _t('SETTINGS_EDITOR_CLASSIC');
        $editors['TinyMCE']  = _t('SETTINGS_EDITOR_FRIENDLY');

        return $editors;
    }

    /**
     * Get the available date formats
     *
     * @access   public
     * @return   Array   Array with available date formats and Jaws_Error otherwise
     */
    function GetDateFormatList()
    {
        $dt_formats = array();
        $time = time();
        $date = $GLOBALS['app']->loadDate();
        $dt_formats['MN j, g:i a']     = $date->Format($time, 'MN j, g:i a');
        $dt_formats['j.m.y']           = $date->Format($time, 'j.m.y');
        $dt_formats['j MN, g:i a']     = $date->Format($time, 'j MN, g:i a');
        $dt_formats['y.m.d, g:i a']    = $date->Format($time, 'y.m.d, g:i a');
        $dt_formats['d MN Y']          = $date->Format($time, 'd MN Y');
        $dt_formats['DN d MN Y']       = $date->Format($time, 'DN d MN Y');
        $dt_formats['DN d MN Y g:i a'] = $date->Format($time, 'DN d MN Y g:i a');
        $dt_formats['j MN y']          = $date->Format($time, 'j MN y');
        $dt_formats['j m Y - H:i']     = $date->Format($time, 'j m Y - H:i');
        $dt_formats['AGO']             = $date->Format($time, 'since');

        return $dt_formats;
    }

    /**
     * Get the timezones list
     *
     * @access   public
     * @return   Array   Array with timezone and Jaws_Error otherwise
     */
    function GetTimeZonesList()
    {
        $timezones          = array();
        $timezones['-12']   = '[UTC - 12] Baker Island, Howland Island';
        $timezones['-11']   = '[UTC - 11] Midway Island, Samoa';
        $timezones['-10']   = '[UTC - 10] Hawaii';
        $timezones['-9.5']  = '[UTC - 9:30] Marquesa Islands, Taiohae';
        $timezones['-9']    = '[UTC - 9] Alaska';
        $timezones['-8']    = '[UTC - 8] Pacific Time (US &amp; Canada), Tijuana';
        $timezones['-7']    = '[UTC - 7] Mountain Time (US &amp; Canada), Arizona';
        $timezones['-6']    = '[UTC - 6] Central Time (US &amp; Canada), Mexico City';
        $timezones['-5']    = '[UTC - 5] Eastern Time (US &amp; Canada), Bogota, Lima, Quito';
        $timezones['-4']    = '[UTC - 4] Atlantic Time (Canada), Caracas, La Paz, Santiago';
        $timezones['-3.5']  = '[UTC - 3:30] Newfoundland';
        $timezones['-3']    = '[UTC - 3] Brasilia, Buenos Aires, Georgetown, Greenland';
        $timezones['-2']    = '[UTC - 2] Mid-Atlantic, Ascension Islands, St. Helena';
        $timezones['-1']    = '[UTC - 1] Azores, Cape Verde Islands';
        $timezones['0']     = '[UTC] Western European, Casablanca, Lisbon, London';
        $timezones['1']     = '[UTC + 1] Amsterdam, Berlin, Brussels, Madrid, Paris, Rome';
        $timezones['2']     = '[UTC + 2] Cairo, Helsinki, Kaliningrad, South Africa';
        $timezones['3']     = '[UTC + 3] Baghdad, Riyadh, Moscow, St. Petersburg, Nairobi';
        $timezones['3.5']   = '[UTC + 3:30] Tehran';
        $timezones['4']     = '[UTC + 4] Abu Dhabi, Baku, Muscat, Tbilisi';
        $timezones['4.5']   = '[UTC + 4:30] Kabul';
        $timezones['5']     = '[UTC + 5] Ekaterinburg, Islamabad, Karachi, Tashkent';
        $timezones['5.5']   = '[UTC + 5:30] Bombay, Calcutta, Madras, New Delhi';
        $timezones['5.75']  = '[UTC + 5:45] Kathmandu';
        $timezones['6']     = '[UTC + 6] Almaty, Colombo, Dhaka, Novosibirsk';
        $timezones['6.5']   = '[UTC + 6:30] Rangoon, Cocos Islands';
        $timezones['7']     = '[UTC + 7] Bangkok, Hanoi, Jakarta, Krasnoyarsk';
        $timezones['8']     = '[UTC + 8] Beijing, Hong Kong, Perth, Singapore, Taipei';
        $timezones['8.75']  = '[UTC + 8:45] Western Australia';
        $timezones['9']     = '[UTC + 9] Osaka, Sapporo, Seoul, Tokyo, Yakutsk';
        $timezones['9.5']   = '[UTC + 9:30] Adelaide, Darwin, Yakutsk';
        $timezones['10']    = '[UTC + 10] Canberra, Guam, Melbourne, Sydney, Vladivostok';
        $timezones['10.5']  = '[UTC + 10:30] Lord Howe Island, South Australia';
        $timezones['11']    = '[UTC + 11] Magadan, New Caledonia, Solomon Islands';
        $timezones['11.5']  = '[UTC + 11:30] Norfolk Island';
        $timezones['12']    = '[UTC + 12] Auckland, Fiji, Kamchatka, Marshall Islands';
        $timezones['12.75'] = '[UTC + 12:45] Chatham Islands';
        $timezones['13']    = '[UTC + 13] Tonga, Phoenix Islands';
        $timezones['14']    = '[UTC + 14] Kiribati';
        return $timezones;
    }

    /**
     * Basic settings (site status, name, description, default language, home page, SSL).
     *
     * @category 	feature
     * @access  public
     * @param   array   $settings  Settings array. This should have the following entries:
		$settings = array(
			'site_status',      => //Site status
			'site_name',        => //Site name
			'site_slogan',      => //Site slogan
			'site_language',    => //Default site language
			'admin_language',   => //Admin area language
			'site_url',      => //Site URL
			'site_ssl_url',      => //Site SSL URL
			'home_page',      => //Home page
			'main_gadget',      => //Main gadget
			'site_comment',     => //Site comment
		);
     * @return  boolean True or Jaws_Error
     */
    function SaveBasicSettings($settings)
    {
        $basicKeys = array('site_status', 'site_name', 'site_slogan', 'site_language',
                           'site_url', 'site_ssl_url', 'home_page', 'admin_language', 'main_gadget', 'site_comment');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($settings as $settingKey => $settingValue) {
            if (!in_array($settingKey, $basicKeys)) {
                continue;
            }

            if (is_string($settingValue) && !empty($settingValue)) {
                $settingValue = $xss->parse($settingValue);
            }

            $GLOBALS['app']->Registry->Set('/config/' . $settingKey, $settingValue);
        }
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Advanced settings (date format, calendar type, gravatar, allow comments, editor preferences, default timezone).
     *
     * @category 	feature
     * @access  public
     * @param   array   $settings  Settings array. This should have the following entries:
		$settings = array(
			'date_format',         //Date format
			'calendar_type',       //Date Calendar
			'calendar_language',   //Date Calendar language
			'use_gravatar',        //Use gravatar service?
			'gravatar_rating',     //Gravatar rating
			'allow_comments',      //Allow comments?
			'show_viewsite',       //show the view site on CP?
			'title_separator',     //Separator used when user uses page_title
			'editor',              //Editor to use
			'timezone',            //Timezone
		);
     * @return  boolean True or Jaws_Error
     */
    function SaveAdvancedSettings($settings)
    {
        $advancedKeys = array('date_format', 'calendar_type', 'calendar_language',
                              'use_gravatar', 'gravatar_rating', 'allow_comments',
                              'show_viewsite', 'title_separator', 'editor', 'timezone');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($settings as $settingKey => $settingValue) {
            if (!in_array($settingKey, $advancedKeys)) {
                continue;
            }

            if (is_string($settingValue) && !empty($settingValue)) {
                $settingValue = $xss->parse($settingValue);
            }
            $GLOBALS['app']->Registry->Set('/config/' . $settingKey, $settingValue);
        }
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * META settings (site description, keywords, author, license, copyright).
     *
     * @category 	feature
     * @access  public
     * @param   array   $settings  Settings array. This should have the following entries:
		$settings = array(
			'site_description',
			'site_keywords',
			'site_author',    //Use gravatar service?
			'site_license', //Gravatar rating
			'copyright',
		);
     * @return  boolean True or Jaws_Error
     */
    function SaveMetaSettings($settings)
    {
        $advancedKeys = array('site_description', 'site_keywords', 'site_author',
                              'site_license', 'copyright');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($settings as $settingKey => $settingValue) {
            if (!in_array($settingKey, $advancedKeys)) {
                continue;
            }

            if (is_string($settingValue) && !empty($settingValue)) {
                $settingValue = $xss->parse($settingValue);
            }
            $GLOBALS['app']->Registry->Set('/config/' . $settingKey, $settingValue);
        }
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * E-mail settings.
     *
     * @category 	feature
     * @access  public
     * @param   array   $settings  Settings array. This should have the following entries:
		$settings = array(
			'mailer',
			'site_email',
			'email_name',
			'smtp_vrfy',
			'sendmail_path',
			'smtp_host',
			'smtp_port',
			'smtp_auth',
			'smtp_user',
			'smtp_pass',
		);
     * @return  boolean True or Jaws_Error
     */
    function UpdateMailSettings($settings)
    {
        $mailKeys = array('mailer', 'site_email', 'email_name', 'smtp_vrfy', 'sendmail_path', 
                          'smtp_host', 'smtp_port', 'smtp_auth', 'smtp_user', 'smtp_pass');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        foreach ($settings as $settingKey => $settingValue) {
            if (!in_array($settingKey, $mailKeys)) {
                continue;
            }

            if (is_string($settingValue) && !empty($settingValue)) {
                $settingValue = $xss->parse($settingValue);
            }
            if ($settingKey == 'smtp_pass' && empty($settingValue)) {
                continue;
            }

            $GLOBALS['app']->Registry->Set('/network/' . $settingKey, $settingValue);
        }
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * FTP settings.
     *
     * @category 	feature
     * @access  public
     * @param   array   $settings  Settings array. This should have the following entries:
		$settings = array(
			'ftp_enabled',
			'ftp_host',
			'ftp_port',
			'ftp_mode',
			'ftp_user',
			'ftp_pass',
			'ftp_root',
		);
     * @return  boolean True or Jaws_Error
     */
    function UpdateFTPSettings($settings)
    {
        $ftpKeys = array('ftp_enabled', 'ftp_host', 'ftp_port',
                         'ftp_mode', 'ftp_user', 'ftp_pass', 'ftp_root');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        foreach ($settings as $settingKey => $settingValue) {
            if (!in_array($settingKey, $ftpKeys)) {
                continue;
            }

            if (is_string($settingValue) && !empty($settingValue)) {
                $settingValue = $xss->parse($settingValue);
            }
            if ($settingKey == 'ftp_pass' && empty($settingValue)) {
                continue;
            }

            $GLOBALS['app']->Registry->Set('/network/' . $settingKey, $settingValue);
        }
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Proxy settings.
     *
     * @category 	feature
     * @access  public
     * @param   array   $settings  Settings array. This should have the following entries:
		$settings = array(
			'proxy_enabled',
			'proxy_host',
			'proxy_port',
			'proxy_auth',
			'proxy_user',
			'proxy_pass',
		);
     * @return  boolean True or Jaws_Error
     */
    function UpdateProxySettings($settings)
    {
        $proxyKeys = array('proxy_enabled', 'proxy_host', 'proxy_port',
                         'proxy_auth', 'proxy_user', 'proxy_pass');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        foreach ($settings as $settingKey => $settingValue) {
            if (!in_array($settingKey, $proxyKeys)) {
                continue;
            }

            if (is_string($settingValue) && !empty($settingValue)) {
                $settingValue = $xss->parse($settingValue);
            }
            if ($settingKey == 'proxy_pass' && empty($settingValue)) {
                continue;
            }

            $GLOBALS['app']->Registry->Set('/network/' . $settingKey, $settingValue);
        }
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }
}
