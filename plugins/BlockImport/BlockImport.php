<?php
/**
 * Replaces [block=#n] with a proper content of specified block id
 *
 * @category   Plugin
 * @package    BlockImport
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'include/Jaws/Plugin.php';

class BlockImport extends Jaws_Plugin
{
    /**
     * Main Constructor
     *
     * @access  public
     */
    function BlockImport()
    {
        $this->_Name = 'BlockImport';
        $this->LoadTranslation();
        $this->_Description = _t('PLUGINS_BLOCKIMPORT_DESCRIPTION');
        $this->_Example = "[Block=#1]";
        $this->_IsFriendly = false;
        $this->_Version = '0.1';
    }

    /**
     * Overrides, Parse the text
     *
     * @access  public
     * @param   string  $html Html to Parse
     * @return  string  Parsed HTML
     */
    function ParseText($html)
    {
        $blockPattern = '@\[block=#(.*?)\]@ism';
        $new_html = preg_replace_callback($blockPattern, array(&$this, 'Prepare'), $html);
        return $new_html;
    }

    /**
     * The preg_replace call back function
     *
     * @access  private
     * @param   string  $matches    Matched strings from preg_replace_callback
     * @return  string  Block content or plain text on errors
     */
    function Prepare($data)
    {
        $blockID = isset($data[1])? $data[1] : '';
        if (Jaws_Gadget::IsGadgetInstalled('Blocks') && !empty($blockID)) {
            $layoutBlocks = $GLOBALS['app']->loadGadget('Blocks', 'LayoutHTML');
            $result = $layoutBlocks->Display($blockID);
            if (!Jaws_Error::isError($result)) {
                return $result;
            }
        }

        return '';
    }
}