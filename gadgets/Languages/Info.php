<?php
/**
 * Language translation support in both admin and visitor sections. Default translation strings can be set by site 
 * admins, and overridden by users or translated automatically using APIs such as Google Translate.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Languages
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class LanguagesInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Languages gadget
     *
     * @access public
     */
    function LanguagesInfo()
    {
        parent::Init('Languages');
        $this->GadgetName(_t('LANGUAGES_NAME'));
        $this->GadgetDescription(_t('LANGUAGES_DESCRIPTION'));
        $this->GadgetVersion('0.2.0');
        //$this->Doc('gadgets/Languages');
        $this->SetAttribute('core_gadget', true);

        $acls  = array(
            'ManageLanguages',
            'ModifyLanguageProperties',
        );
        $this->PopulateACLs($acls);
    }

}