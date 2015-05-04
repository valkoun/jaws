<?php
/**
 * Settings AJAX API
 *
 * @category   Ajax
 * @package    Settings
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class SettingsAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function SettingsAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    /**
     * Update basic settings
     *
     * @access  public
     * @param   array   $settings  Basic settings array. Should have the same
     *                             format as the SaveBasicSettings model's method
     * @return  array   Success/Failure
     */
    function UpdateBasicSettings($settings)
    {
        $this->CheckSession('Settings', 'ManageSettings');
        $this->_Model->SaveBasicSettings($settings);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update advanced settings
     *
     * @access  public
     * @param   array   $settings  Advanced settings array. Should have the same
     *                             format as the SaveBasicSettings model's method
     * @return  array   Success/Failure
     */
    function UpdateAdvancedSettings($settings)
    {
        $this->CheckSession('Settings', 'ManageSettings');
        $this->_Model->SaveAdvancedSettings($settings);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update META settings
     *
     * @access  public
     * @param   array   $settings  
     * @return  array   Success/Failure
     */
    function UpdateMetaSettings($settings)
    {
        $this->CheckSession('Settings', 'ManageSettings');
        $this->_Model->SaveMetaSettings($settings);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update mail settings
     *
     * @access  public
     * @param   array   $settings  Mail settings array. Should have the same
     *                             format as the SaveBasicSettings model's method
     * @return  array   Success/Failure
     */
    function UpdateMailSettings($settings)
    {
        $this->CheckSession('Settings', 'ManageSettings');
        $this->_Model->UpdateMailSettings($settings);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update ftp settings
     *
     * @access  public
     * @param   array   $settings  FTP settings array. Should have the same
     *                             format as the SaveBasicSettings model's method
     * @return  array   Success/Failure
     */
    function UpdateFTPSettings($settings)
    {
        $this->CheckSession('Settings', 'ManageSettings');
        $this->_Model->UpdateFTPSettings($settings);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update proxy settings
     *
     * @access  public
     * @param   array   $settings  Proxy settings array. Should have the same
     *                             format as the SaveBasicSettings model's method
     * @return  array   Success/Failure
     */
    function UpdateProxySettings($settings)
    {
        $this->CheckSession('Settings', 'ManageSettings');
        $this->_Model->UpdateProxySettings($settings);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
}