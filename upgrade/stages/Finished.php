<?php
/**
 * Finished Stage
 *
 * @category   Application
 * @package    UpgradeStage
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Upgrader_Finished extends JawsUpgraderStage
{
    /**
     * Builds the upgrader page.
     *
     * @access  public
     * @return  string A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
		require_once JAWS_PATH . 'include/Jaws/DB.php';
		$GLOBALS['db'] = Jaws_DB::getInstance($_SESSION['upgrade']['Database']);

        require_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
		$GLOBALS['app']->create();
        $tpl = new Jaws_Template(UPGRADE_PATH . 'stages/Finished/templates/');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('Finished');

        $base_url = $GLOBALS['app']->getSiteURL();
        $tpl->setVariable('lbl_info',    _t('UPGRADE_FINISH_INFO'));
        $tpl->setVariable('lbl_choices', _t('UPGRADE_FINISH_CHOICES', "$base_url/index.php", "$base_url/admin.php"));
        $tpl->setVariable('lbl_thanks',  _t('UPGRADE_FINISH_THANKS'));
        $tpl->SetVariable('move_log',    _t('UPGRADE_FINISH_MOVE_LOG'));

        $tpl->ParseBlock('Finished');
		
		// Delete it all!
		$path = UPGRADE_PATH;
		if (!Jaws_Utils::Delete($path)) {
			log_install("Can't delete $path");
		}
		
		$GLOBALS['app']->RebuildJawsCache(false);
        
		return $tpl->Get();
    }
}