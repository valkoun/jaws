<?php
/**
 * Faq Gadget
 *
 * @category   GadgetInfo
 * @package    Faq
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class FaqInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Faq gadget
     *
     * @access  public
     */
    function FaqInfo()
    {
        parent::Init('Faq');
        $this->GadgetName(_t('FAQ_NAME'));
        $this->GadgetDescription(_t('FAQ_INFO_DESCRIPTION'));
        $this->GadgetVersion('0.8.1');
        $this->Doc('gadget/Faq');

        $acls = array(
            'default',
            'AddQuestion',
            'EditQuestion',
            'DeleteQuestion',
            'ManageCategories',
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}