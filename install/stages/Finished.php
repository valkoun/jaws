<?php
/**
 * The finished page for the installer.
 *
 * @author Jon Wood <jon@substance-it.co.uk>
 * @access public
 */
class Installer_Finished extends JawsInstallerStage
{
    /**
     * Builds the installer page.
     *
     * @access  public
     * @return  string      A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        require_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
		$GLOBALS['app']->create();
        $tpl = new Jaws_Template(INSTALL_PATH . 'stages/Finished/templates');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('Finished');

        $base_url = $GLOBALS['app']->getSiteURL();
        $tpl->setVariable('lbl_info',    _t('INSTALL_FINISH_INFO'));
        $tpl->setVariable('lbl_choices', _t('INSTALL_FINISH_CHOICES', "$base_url/index.php", "$base_url/admin.php"));
        $tpl->setVariable('lbl_thanks',  _t('INSTALL_FINISH_THANKS'));
		if (!isset($_SESSION['install']['data']['WriteConfig']['skip'])) {
			$tpl->SetVariable('move_log',    _t('INSTALL_FINISH_MOVE_LOG'));
		}
        $tpl->ParseBlock('Finished');
				
		// Delete it all!
		$path = INSTALL_PATH;
		if (!Jaws_Utils::Delete($path)) {
			log_install("Can't delete $path");
		}
		
		$GLOBALS['app']->RebuildJawsCache(false);
        
		return $tpl->Get();
    }
}