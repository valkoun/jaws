<?php
/**
 * The introduction page for the installer.
 *
 * @category   Application
 * @package    Install
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Installer_Introduction extends JawsInstallerStage
{
    /**
     * Builds the installer page.
     *
     * @access  public
     * @return  string      A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        $tpl = new Jaws_Template(INSTALL_PATH . 'stages/Introduction/templates');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('Introduction');
        $tpl->SetVariable('welcome',    _t('INSTALL_INTRO_WELCOME'));
        $tpl->SetVariable('title_info', _t('INSTALL_INTRO_INSTALLER'));
        $tpl->SetVariable('db_info',    _t('INSTALL_INTRO_DATABASE'));
        $tpl->SetVariable('ftp_info',   _t('INSTALL_INTRO_FTP'));
        $tpl->SetVariable('mail_info',  _t('INSTALL_INTRO_MAIL'));
        $tpl->SetVariable('language',   _t('GLOBAL_LANGUAGE'));
        $tpl->SetVariable('next',       _t('GLOBAL_NEXT'));
        if (is_writable(JAWS_DATA . 'logs') && is_dir(JAWS_DATA . 'logs')) {
            $tpl->SetVariable('log_use', _t('INSTALL_INTRO_LOG', 'data/logs/install.log'));
            $tpl->SetBlock('Introduction/logcheckbox');
            $tpl->ParseBlock('Introduction/logcheckbox');
        } else {
            $tpl->SetVariable('log_use', _t('INSTALL_INTRO_LOG_ERROR', 'data/logs'));
        }

        $langs = Jaws_Utils::GetLanguagesList();
        $selected_lang = isset($_SESSION['install']['language'])? $_SESSION['install']['language'] : 'en';
        foreach ($langs as $code => $fullname) {
            $tpl->SetBlock('Introduction/lang');
            $tpl->SetVariable('selected', $code == $selected_lang? 'selected="selected"': '');
            $tpl->SetVariable('code', $code);
            $tpl->SetVariable('fullname', $fullname);
            $tpl->ParseBlock('Introduction/lang');
        }

        $tpl->ParseBlock('Introduction');

        return $tpl->Get();
    }
}