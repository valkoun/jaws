<?php
/**
 * Theme management system.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class TmsInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Users gadget
     *
     * @access  public
     */
    function TmsInfo()
    {
        parent::Init('Tms');
        $this->GadgetName(_t('TMS_NAME'));
        $this->GadgetDescription(_t('TMS_DESCRIPTION'));
        $this->GadgetVersion('0.1.2');
        $this->Doc('gadget/Tms');
        $this->SetAttribute('core_gadget', true);

        $acls = array(
            'ManageTms',
            'ManageRepositories',
            'ManageSharing',
            'ManageSettings',
            'UploadTheme'
        );
        $this->PopulateACLs($acls);
    }
}