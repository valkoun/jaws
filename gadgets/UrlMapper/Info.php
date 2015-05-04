<?php
/**
 * UrlMapper Core Gadget
 *
 * @category   GadgetInfo
 * @package    UrlMapper
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UrlMapperInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Urlmapper gadget
     *
     * @access  public
     */
    function UrlmapperInfo()
    {
        parent::Init('UrlMapper');
        $this->GadgetName(_t('URLMAPPER_NAME'));
        $this->GadgetDescription(_t('URLMAPPER_DESCRIPTION'));
        $this->GadgetVersion('0.3.1');
        $this->Doc('gadget/UrlMapper');
        $this->SetAttribute('core_gadget', true);

        $acls = array(
            'ManageUrlMapper',
            'EditMaps',
        );
        $this->PopulateACLs($acls);
    }
}