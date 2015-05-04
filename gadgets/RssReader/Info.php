<?php
/**
 * RssReader Gadget
 *
 * @category   GadgetInfo
 * @package    RssReader
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh  <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class RssReaderInfo extends Jaws_GadgetInfo
{
    function RssReaderInfo()
    {
        parent::Init('RssReader');
        $this->GadgetName(_t('RSSREADER_NAME'));
        $this->GadgetDescription(_t('RSSREADER_DESCRIPTION'));
        $this->GadgetVersion('0.8.0');
        $this->Doc('gadget/RssReader');

        $acls = array(
            'default',
            'ManageRSSSite',
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}