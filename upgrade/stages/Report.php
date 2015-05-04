<?php
/**
 * Report Stage
 *
 * @category   Application
 * @package    UpgradeStage
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Upgrader_Report extends JawsUpgraderStage
{
    /**
     * Builds the upgrader page.
     *
     * @access  public
     * @return  string A block of valid XHTML to display the status of old/current jaws versions
     */
    function Display()
    {
        include_once JAWS_PATH.'include/Jaws/DB.php';
        $GLOBALS['db'] = new Jaws_DB($_SESSION['upgrade']['Database']);

        require_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
        $GLOBALS['app']->create();
        $JawsInstalledVersion = $GLOBALS['app']->Registry->Get('/version');
        $GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['upgrade']['language']));
		$GLOBALS['app']->RebuildJawsCache(false);

        $supportedversions = array(
                                   array(
                                         'version'   => '0.8.14',
                                         'stage'     => '18',
                                         ),
                                   array(
                                         'version'   => '0.8.13',
                                         'stage'     => '17',
                                         ),
                                   array(
                                         'version'   => '0.8.12',
                                         'stage'     => '16',
                                         ),
                                   array(
                                         'version'   => '0.8.11',
                                         'stage'     => '15',
                                         ),
                                   array(
                                         'version'   => '0.8.10',
                                         'stage'     => '14',
                                         ),
                                   array(
                                         'version'   => '0.8.9',
                                         'stage'     => '13',
                                         ),
                                   array(
                                         'version'   => '0.8.8',
                                         'stage'     => '12',
                                         ),
                                   array(
                                         'version'   => '0.8.7',
                                         'stage'     => '11',
                                         ),
                                   array(
                                         'version'   => '0.8.6',
                                         'stage'     => '10',
                                         ),
                                   array(
                                         'version'   => '0.8.5',
                                         'stage'     => '9',
                                         ),
                                   array(
                                         'version'   => '0.8.4',
                                         'stage'     => '8',
                                         ),
                                   array(
                                         'version'   => '0.8.3',
                                         'stage'     => '7',
                                         ),
                                   array(
                                         'version'   => '0.8.2',
                                         'stage'     => null,
                                         ),
                                   array(
                                         'version'   => '0.8.1',
                                         'stage'     => '6',
                                         ),
                                   array(
                                         'version'   => '0.8.0',
                                         'stage'     => '5',
                                         ),
                                   array(
                                         'version'   => '0.7.4',
                                         'stage'     => null,
                                         ),
                                   array(
                                         'version'   => '0.7.3',
                                         'stage'     => null,
                                         ),
                                   array(
                                         'version'   => '0.7.2',
                                         'stage'     => null,
                                         ),
                                   array(
                                         'version'   => '0.7.1',
                                         'stage'     => null,
                                         ),
                                   array(
                                         'version'   => '0.7.0',
                                         'stage'     => null,
                                         )
                                   );

        log_upgrade("Checking/Reporting previous missed installations");
        $tpl = new Jaws_Template(UPGRADE_PATH . 'stages/Report/templates/');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('Report');

        $tpl->setVariable('lbl_info',    _t('UPGRADE_REPORT_INFO', JAWS_VERSION));
        $tpl->setVariable('lbl_message', _t('UPGRADE_REPORT_MESSAGE'));
        $tpl->SetVariable('next',        _t('GLOBAL_NEXT'));

        $versions_to_upgrade = 0;
        $_SESSION['upgrade']['stagedVersions'] = array();
        foreach($supportedversions as $supported) {
            $tpl->SetBlock('Report/versions');
            $tpl->SetBlock('Report/versions/version');
            $tpl->SetVariable('description', $supported['version']);

            $_SESSION['upgrade']['versions'][$supported['version']] = array(
                        'version' => $supported['version'],
                        'stage' =>   $supported['stage'],
                        'file' =>    (isset($supported['file'])? $supported['file'] : ''),
                        'script' =>  (isset($supported['script'])? $supported['script'] : '')
            );

            if (version_compare($supported['version'], $JawsInstalledVersion, '<=')) {
                if ($supported['version'] == JAWS_VERSION) {
                    $tpl->SetVariable('status', _t('UPGRADE_REPORT_NO_NEED_CURRENT'));
                    log_upgrade($supported['version']." does not requires upgrade(is current)");
                } else {
                    $tpl->SetVariable('status', _t('UPGRADE_REPORT_NO_NEED'));
                    log_upgrade($supported['version']." does not requires upgrade");
                }
                $_SESSION['upgrade']['versions'][$supported['version']]['status'] = true;
            } else {
                $tpl->SetVariable('status', _t('UPGRADE_REPORT_NEED'));
                $_SESSION['upgrade']['versions'][$supported['version']]['status'] = false;
                $versions_to_upgrade++;
                log_upgrade($supported['version']." requires upgrade");
                $_SESSION['upgrade']['versions'][$supported['version']]['status'] = false;
            }

            if (!is_null($supported['stage'])) {
                $_SESSION['upgrade']['stagedVersions'][] = $supported['version'];
            }

            $tpl->ParseBlock('Report/versions/version');
            $tpl->ParseBlock('Report/versions');
        }
        $_SESSION['upgrade']['versions_to_upgrade'] = $versions_to_upgrade;

        $tpl->ParseBlock('Report');
        arsort($_SESSION['upgrade']['versions']);
        krsort($_SESSION['upgrade']['stagedVersions']);
        /**
         * Are we maitaining the last version? the current JAWS_VERSION?
         */
        log_upgrade("Checking if current version (".JAWS_VERSION.") really requires an upgrade");
        $lastSupportedVersion = $supportedversions[0]['version'];
        if ($lastSupportedVersion != JAWS_VERSION) {
            if (version_compare($lastSupportedVersion, JAWS_VERSION) === -1) {
                log_upgrade("Current version (".JAWS_VERSION.") does not require an upgrade");
                $_SESSION['upgrade']['upgradeLast'] = true;
            }
        }
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
        foreach($_SESSION['upgrade']['stagedVersions'] as $stagedVersion) {
            if (!$_SESSION['upgrade']['versions'][$stagedVersion]['status']) {
                if ($_SESSION['upgrade']['stage'] < $_SESSION['upgrade']['versions'][$stagedVersion]['stage']) {
                    return true;
                } else {
                    $_SESSION['upgrade']['stage']++;
                }
            } else {
                $_SESSION['upgrade']['stage']++;
            }
        }
        return true;
    }
}