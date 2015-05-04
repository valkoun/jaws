<?php
/**
 * Jaws Upgrade Stage - From 0.7.x to 0.8.0
 *
 * @category   Application
 * @package    UpgradeStage
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Upgrader_07To080 extends JawsUpgraderStage
{
    /**
     * Builds the upgader page.
     *
     * @access  public
     * @return  string A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        $tpl = new Jaws_Template(UPGRADE_PATH  . 'stages/07To080/templates/');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('07To080');

        $tpl->setVariable('lbl_info',  _t('UPGRADE_VER_INFO', '0.7.x', '0.8.0'));
        $tpl->setVariable('lbl_notes', _t('UPGRADE_VER_NOTES'));
        $tpl->SetVariable('next',      _t('GLOBAL_NEXT'));

        $tpl->ParseBlock('07To080');
        return $tpl->Get();
    }

    /**
     * Does any actions required to finish the stage, such as DB queries.
     *
     * @access  public
     * @return  bool|Jaws_Error  Either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Run()
    {
        // Connect to database
        require_once JAWS_PATH . 'include/Jaws/DB.php';
        $GLOBALS['db'] = new Jaws_DB($_SESSION['upgrade']['Database']);
        if (Jaws_Error::IsError($GLOBALS['db'])) {
            log_upgrade("There was a problem connecting to the database, please check the details and try again");
            return new Jaws_Error(_t('UPGRADE_DB_RESPONSE_CONNECT_FAILED'), 0, JAWS_ERROR_WARNING);
        }
        $GLOBALS['db']->dbc->loadModule('Function', null, true);

        $sql = 'DELETE FROM [[session]]';
        $res = $GLOBALS['db']->query($sql);
        if (Jaws_Error::IsError($res)) {
            return $res;
        }

        // remove duplicate records from registry table
        $lower1 = $GLOBALS['db']->dbc->function->lower('a1.[name]');
        $lower2 = $GLOBALS['db']->dbc->function->lower('a2.[name]');
        $sql = "
            SELECT [id] FROM [[registry]] a1
            WHERE EXISTS (
                SELECT *
                FROM [[registry]] a2
                WHERE
                    a1.[id] != a2.[id]
                  AND
                    a1.[id] < a2.[id]
                  AND
                    $lower1 = $lower2)";
        $dupls = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($dupls)) {
            return $dupls;
        }

        $sql = 'DELETE FROM [[registry]] WHERE [id] = {id}';
        foreach ($dupls as $rec) {
            $res = $GLOBALS['db']->query($sql, array('id' => $rec['id']));
        }

        // remove duplicate records from acl table
        $lower1 = $GLOBALS['db']->dbc->function->lower('a1.[name]');
        $lower2 = $GLOBALS['db']->dbc->function->lower('a2.[name]');
        $sql = "
            SELECT [id] FROM [[acl]] a1
            WHERE EXISTS (
                SELECT *
                FROM [[acl]] a2
                WHERE
                    a1.[id] != a2.[id]
                  AND
                    a1.[id] < a2.[id]
                  AND
                    $lower1 = $lower2)";
        $dupls = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($dupls)) {
            return $dupls;
        }

        $sql = 'DELETE FROM [[acl]] WHERE [id] = {id}';
        foreach ($dupls as $rec) {
            $res = $GLOBALS['db']->query($sql, array('id' => $rec['id']));
        }

        $old_schema = UPGRADE_PATH . 'schema/0.7.4.xml';
        $new_schema = UPGRADE_PATH . 'schema/0.8.0.xml';
        if (!file_exists($old_schema)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_SQLFILE_NOT_EXISTS', $old_schema),0 , JAWS_ERROR_ERROR);
        }

        if (!file_exists($new_schema)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_SQLFILE_NOT_EXISTS', $new_schema),0 , JAWS_ERROR_ERROR);
        }

        log_upgrade("Upgrading core schema");
        $result = $GLOBALS['db']->installSchema($new_schema, '', $old_schema);
        if (Jaws_Error::isError($result)) {
            log_upgrade($result->getMessage());
            return new Jaws_Error($result->getMessage(), 0, JAWS_ERROR_ERROR);
        }

        $tables = array('session_user_data');
        foreach ($tables as $table) {
            $res = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($res)) {
                // do nothing
            }
        }

        log_upgrade("Cleaning previous registry and acl cache data files");
        //Make sure user don't have any data/cache/registry|acl stuff
        $path = JAWS_DATA . 'cache/registry';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        $path = JAWS_DATA . 'cache/acl';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        // Create application
        include_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
        $GLOBALS['app']->create();
        $GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['upgrade']['language']));
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        include_once JAWS_PATH . 'include/Jaws/Version.php';

        // Input datas
        $timestamp = $GLOBALS['db']->Date();

        $robots = array('Yahoo! Slurp',
                        'Baiduspider',
                        'Googlebot',
                        'msnbot',
                        'Gigabot',
                        'ia_archiver',
                        'yacybot',
                        'http://www.WISEnutbot.com',
                        'psbot',
                        'msnbot-media',
                        'Ask Jeeves',
                        );

        //registry keys.
        $result = $GLOBALS['app']->Registry->NewKeyEx(
                    array('/config/frontend_ajaxed', 'false'),
                    array('/config/http_auth', 'false'),
                    array('/config/realm', 'Jaws Control Panel'),
                    array('/config/calendar_language', 'en'),
                    array('/config/timezone', '0'),
                    array('/config/gzip_compression', 'false'),
                    array('/config/browsers_flag', 'opera,firefox,ie7up,ie,safari,nav,konq,gecko,text'),
                    array('/config/site_url', $GLOBALS['app']->Registry->Get('/config/url')),
                    array('/config/site_keywords', ''),
                    array('/config/site_language',  $GLOBALS['app']->Registry->Get('/config/language')),
                    array('/config/admin_language', $GLOBALS['app']->Registry->Get('/config/language')),
                    array('/config/cookie_precedence', 'false'),
                    array('/config/robots', implode(',', $robots)),
                    array('/config/connection_timeout', '5'),           // per second
                    array('/policy/passwd_bad_count',         '7'),
                    array('/policy/passwd_lockedout_time',    '60'),    // per second
                    array('/policy/passwd_max_age',           '0'),     // per day  0 = resistant
                    array('/policy/passwd_min_length',        '0'),
                    array('/policy/passwd_complexity',        'no'),
                    array('/policy/xss_parsing_level',        'paranoid'),
                    array('/policy/session_idle_timeout',     '30'),    // per minute
                    array('/policy/session_remember_timeout', '720'),   // hours = 1 month
                    array('/gadgets/autoload_items', ''),
                    array('/network/ftp_enabled', 'false'),
                    array('/network/ftp_host', '127.0.0.1'),
                    array('/network/ftp_port', '21'),
                    array('/network/ftp_mode', 'passive'),
                    array('/network/ftp_user', ''),
                    array('/network/ftp_pass', ''),
                    array('/network/ftp_root', ''),
                    array('/network/proxy_enabled', 'false'),
                    array('/network/proxy_type', 'http'),
                    array('/network/proxy_host', ''),
                    array('/network/proxy_port', '80'),
                    array('/network/proxy_auth', 'false'),
                    array('/network/proxy_user', ''),
                    array('/network/proxy_pass', ''),
                    array('/network/mailer', 'smtp'),
                    array('/network/from_email', ''),
                    array('/network/from_name', ''),
                    array('/network/sendmail_path', '/usr/sbin/sendmail'),
                    array('/network/sendmail_args', ''),
                    array('/network/smtp_host', $GLOBALS['app']->Registry->Get('/Mailserver/hostname')),
                    array('/network/smtp_port', $GLOBALS['app']->Registry->Get('/Mailserver/port')),
                    array('/network/smtp_auth', 'false'),
                    array('/network/pipelining', 'false'),
                    array('/network/smtp_user', $GLOBALS['app']->Registry->Get('/Mailserver/username')),
                    array('/network/smtp_pass', $GLOBALS['app']->Registry->Get('/Mailserver/password')),
                    array('/crypt/enabled', $_SESSION['upgrade']['secure']? 'true' : 'false'),
                    array('/crypt/pub_key', $_SESSION['pub_key']),
                    array('/crypt/pvt_key', $_SESSION['pvt_key']),
                    array('/crypt/key_len', '128'),
                    array('/crypt/key_age', '86400'),
                    array('/crypt/key_start_date', $_SESSION['upgrade']['secure']? time() : '0')
        );
        if (Jaws_Error::isError($result)) {
            log_upgrade($result->getMessage());
            //return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_ADDING_REGISTRY_KEY'), 'CORE');
        }

        //registry keys
        $GLOBALS['app']->Registry->Set('/version', JAWS_VERSION);
        $GLOBALS['app']->Registry->Set('/last_update', $timestamp);
        $GLOBALS['app']->Registry->Set('/config/editor','TextArea');
        $GLOBALS['app']->Registry->Set('/config/cookie/version','0.3');

        //delete registry keys
        $GLOBALS['app']->Registry->DeleteKey('/config/url');
        $GLOBALS['app']->Registry->DeleteKey('/config/language');
        $GLOBALS['app']->Registry->DeleteKey('/Mailserver/use');
        $GLOBALS['app']->Registry->DeleteKey('/Mailserver/hostname');
        $GLOBALS['app']->Registry->DeleteKey('/Mailserver/port');
        $GLOBALS['app']->Registry->DeleteKey('/Mailserver/username');
        $GLOBALS['app']->Registry->DeleteKey('/Mailserver/password');
        $GLOBALS['app']->Registry->DeleteKey('/Mailserver/enabled');

        // Commit the changes so they get saved
        $GLOBALS['app']->Registry->commit('core');

        // ACL keys
        // --------

        $gadgets = array(
            'Settings', 'Layout', 'Registry', 'ControlPanel',
            'Jms', 'UrlMapper', 'Users', 'Policy',
        );

        require_once JAWS_PATH . 'include/Jaws/URLMapping.php';
        $GLOBALS['app']->Map = new Jaws_URLMapping();

        foreach ($gadgets as $gadget) {
            $result = true;
            if (!Jaws_Gadget::IsGadgetInstalled($gadget)) {
                log_upgrade("Installing core gadget: ".$gadget);
                $result = Jaws_Gadget::EnableGadget($gadget);
            } elseif (!Jaws_Gadget::IsGadgetUpdated($gadget)) {
                log_upgrade("Upgrading core gadget: ".$gadget);
                $result = Jaws_Gadget::UpdateGadget($gadget);
            }

            if (Jaws_Error::IsError($result)) {
                log_upgrade("There was a problem installing/upgrading core gadget: $gadget");
                return new Jaws_Error(_t('UPGRADE_VER_RESPONSE_GADGET_FAILED', $gadget), 0, JAWS_ERROR_ERROR);
            }
        }

        log_upgrade("Re-Cleaning previous registry and acl cache data files to fetch new data");
        //Make sure user don't have any data/cache/registry|acl stuff
        $path = JAWS_DATA . 'cache/registry';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        $path = JAWS_DATA . 'cache/acl';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

/*
        log_upgrade("Upgrading new gadget requirements");
        // Add keys
        $gadgets = $GLOBALS['app']->Registry->get('/gadgets/enabled_items');
        $core    = $GLOBALS['app']->Registry->get('/gadgets/core_items');
        $gadgets = $core + $gadets;
        foreach ($gadgets as $g) {
            $info = $GLOBALS['app']->loadGadget($g, 'Info');
            $req = $info->GetRequirements();
            $requires = implode($req, ', ');
            $GLOBALS['app']->Registry->NewKey('/gadgets/' . $g . '/requires', $requires);
        }
*/
        return true;
    }
}
