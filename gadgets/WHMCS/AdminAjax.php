<?php
/**
 * WHMCS AJAX API
 *
 * @category   Ajax
 * @package    WHMCS
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
class WHMCSAdminAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function WHMCSAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeleteClient
    /**
     * Deletes a client.
     *
     * @access  public
     * @param   int     $id  Client ID
     * @return  array   Response (notice or error)
     */
    function DeleteClient($id)
    {
		$this->CheckSession('WHMCS', 'ManageWHMCSClients');
        $this->_Model->DeleteClient($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Executes a massive-delete of clients
     *
     * @access  public
     * @param   array   $pages  Array with the ids of clients
     * @return  array   Response (notice or error)
     */
    function MassiveDelete($pages)
    {
		$this->CheckSession('WHMCS', 'ManageWHMCSClients');
        $this->_Model->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
    /**
     * Get total clients of a search
     *
     * @access  public
     * @param   string  $status  Status of client(s) we want to display
     * @param   string  $search  Keyword (title/description) of clients we want to look for
     * @return  int     Total of clients
     */
    function SizeOfSearch($status, $search)
    {
		$this->CheckSession('WHMCS', 'ManageWHMCSClients');
        $pages = $this->_Model->SearchClients($status, $search, null, 0);
        return count($pages);
    }
    
    /**
     * Returns an array with all the clients
     *
     * @access  public
     * @param   string  $status  Status of client(s) we want to display
     * @param   string  $search  Keyword (title/description) of clients we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Clients data
     */
    function SearchClients($status, $search, $limit)
    {
		$this->CheckSession('WHMCS', 'ManageWHMCSClients');
        $gadget = $GLOBALS['app']->LoadGadget('WHMCS', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetClients($status, $search, $limit, 0);
    }
    
	/**
     * Save config settings
     *
     * @access  public
     * @param   string  $whmcs_url  WHMCS API URL
     * @param   string  $whmcs_api    WHMCS API username
     * @param   string  $whmcs_auth      WHMCS API Authentication key
     * @return  array   Response (notice or error)
     */
    function SaveSettings(
		$whmcs_url, $whmcs_api, $whmcs_auth
	) {
        $this->CheckSession('WHMCS', 'default');
        $res = $this->_Model->SaveSettings(
			$whmcs_url, $whmcs_api, $whmcs_auth
		);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_SETTINGS_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

}