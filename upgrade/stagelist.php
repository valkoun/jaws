<?php
/**
 * Upgrade stage list
 *
 * @category   Application
 * @package    Upgrade
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$stages = array();

// Displays a brief introduction
$stages[] = array(
    'name'  => _t('UPGRADE_INTRODUCTION'),
    'file'  => 'Introduction',
);

// Authenticate user.
$stages[] = array(
    'name'  => _t('UPGRADE_AUTHENTICATION'),
    'file'  => 'Authentication',
);

// Filesystem permission checks.
$stages[] = array(
    'name'  => _t('UPGRADE_REQUIREMENTS'),
    'file'  => 'Requirements',
);

// Database setup and population.
$stages[] = array(
    'name'    => _t('UPGRADE_DATABASE'),
    'file'    => 'Database',
    'options' => $db,
);

// Report.
$stages[] = array(
    'name'  => _t('UPGRADE_REPORT'),
    'file'  => 'Report',
);

// Does assorted stuff, such as a default gadget.
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.7.x', '0.8.0'),
    'file'  => '07To080',
);

// Upgrade from 0.8.0 to 0.8.1
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.0', '0.8.1'),
    'file'  => '08To081',
);

// Upgrade from 0.8.2 to 0.8.3
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.2', '0.8.3'),
    'file'  => '082To083',
);

// Upgrade from 0.8.3 to 0.8.4
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.3', '0.8.4'),
    'file'  => '083To084',
);

// Upgrade from 0.8.4 to 0.8.5
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.4', '0.8.5'),
    'file'  => '084To085',
);

// Upgrade from 0.8.5 to 0.8.6
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.5', '0.8.6'),
    'file'  => '085To086',
);

// Upgrade from 0.8.6 to 0.8.7
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.6', '0.8.7'),
    'file'  => '086To087',
);

// Upgrade from 0.8.7 to 0.8.8
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.7', '0.8.8'),
    'file'  => '087To088',
);

// Upgrade from 0.8.8 to 0.8.9
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.8', '0.8.9'),
    'file'  => '088To089',
);

// Upgrade from 0.8.9 to 0.8.10
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.9', '0.8.10'),
    'file'  => '089To0810',
);

// Upgrade from 0.8.10 to 0.8.11
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.10', '0.8.11'),
    'file'  => '0810To0811',
);

// Upgrade from 0.8.11 to 0.8.12
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.11', '0.8.12'),
    'file'  => '0811To0812',
);

// Upgrade from 0.8.12 to 0.8.13
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.12', '0.8.13'),
    'file'  => '0812To0813',
);

// Upgrade from 0.8.13 to 0.8.14
$stages[] = array(
    'name'  => _t('UPGRADE_VER_TO_VER', '0.8.13', '0.8.14'),
    'file'  => '0813To0814',
);

// Saves the config file.
$stages[] = array(
    'name'  => _t('UPGRADE_WRITECONFIG'),
    'file'  => 'WriteConfig',
);

// Everything's done! Go log in :)
$stages[] = array(
    'name'  => _t('UPGRADE_FINISHED'),
    'file'  => 'Finished',
);