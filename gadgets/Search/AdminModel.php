<?php
/**
 * Search Gadget Admin
 *
 * @category   GadgetModel
 * @package    Search
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'gadgets/Search/Model.php';

class SearchAdminModel extends SearchModel
{
    /**
     * Install Search gadget in Jaws
     *
     * @access       public
     * @return       true on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        // Registry keys
        $GLOBALS['app']->Registry->NewKey('/gadgets/Search/searchable_gadgets', '*');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Search/min_key_len', '3');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Search/max_result_len', '500');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Search/results_limit', '10');

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UninstallGadget()
    {
        // registry key
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Search/searchable_gadgets');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Search/min_key_len');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Search/max_result_len');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Search/results_limit');

        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UpdateGadget($old, $new)
    {
        if (version_compare($old, '0.7.0', '<')) {
            // Registry keys.
            $GLOBALS['app']->Registry->NewKey('/gadgets/Search/min_key_len', '3');
            $GLOBALS['app']->Registry->NewKey('/gadgets/Search/max_result_len', '500');
        }
        $GLOBALS['app']->Registry->NewKey('/gadgets/Search/results_limit', '10');

        return true;
    }

    /**
     * Set searchable gadgets
     *
     * @access  public
     * @param   array $gadgets Array with gadgets to be set as searchable
     */
    function SetSearchableGadgets($gadgets)
    {
        $list = is_array($gadgets)? implode(', ', $gadgets) : '*';
        $res = $GLOBALS['app']->Registry->Set('/gadgets/Search/searchable_gadgets', $list);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('SEARCH_ERROR_NOT_SAVED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('SEARCH_ERROR_NOT_SAVED'), _t('SEARCH_NAME'));
        }
        $GLOBALS['app']->Registry->Commit('Search');
        $GLOBALS['app']->Session->PushLastResponse(_t('SEARCH_SAVED'), RESPONSE_NOTICE);
        return true;
    }
	
}