<?php
/**
 * SysInfo Admin Gadget
 *
 * @category   GadgetModel
 * @package    SysInfo
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
require_once JAWS_PATH . 'gadgets/SysInfo/Model.php';

class SysInfoAdminModel extends SysInfoModel
{
    /**
     * Installs the gadget
     *
     * @access       public
     * @return       true on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/SysInfo/frontend_avail', 'false');

        return true;
    }
}
