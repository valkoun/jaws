<?php
/**
 * Site-wide search.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Search
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class SearchInfo extends Jaws_GadgetInfo
{
    /**
     * Constructor
     */
    function SearchInfo()
    {
        parent::Init('Search');
        $this->GadgetName(_t('SEARCH_NAME'));
        $this->GadgetDescription(_t('SEARCH_DESCRIPTION'));
        $this->GadgetVersion('0.7.1');
        //$this->Doc('gadgets/Search');

        $acls = array('default');
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}