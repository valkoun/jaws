<?php
/**
 * TMS (Theme Management System) AJAX API
 *
 * @category   Ajax
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class TmsAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function TmsAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    /**
     * Get a list of available themes of certain repository
     *
     * @access  public
     * @param   mixed    $repository   Can be 'local' or an integer (a remote repository)
     * @return  array    Themes list
     */
    function GetThemes($repository)
    {
        $this->CheckSession('Tms', 'ManageTms');
        
        $model = $GLOBALS['app']->LoadGadget('Tms', 'AdminModel');
        return $model->GetThemes($repository);
    }

    /**
     * Get information of a given theme
     *
     * @access  public
     * @param   string  $theme      Theme's name
     * @param   mixed   $repository Repository identifier (can be local or an integer, 
     *                              the remote repository
     * @return  array   Theme's info
     */
    function GetThemeInfo($theme, $repository)
    {
        $this->CheckSession('Tms', 'ManageTms');
        $html = $GLOBALS['app']->LoadGadget('Tms', 'AdminHTML');
        return $html->GetThemeInfo($theme, $repository);
    }

    /**
     * Get basic information of a repository
     *
     * @access  public
     * @param   int     $id      Repository's ID
     * @return  array   Repository information
     */
    function GetRepository($id)
    {
        $this->CheckSession('Tms', 'ManageTms');
        $repository = $GLOBALS['app']->LoadGadget('Tms', 'Model');
        $repInfo    = $repository->getRepository($id);
        if (Jaws_Error::isError($repInfo)) {
            return false;
        } else {
            return $repInfo;
        }
    }
    
    /**
     * Get data from DB
     *
     * @access  public
     */
    function GetData($limit = 0)
    {
        $this->CheckSession('Tms', 'ManageTms');
        $gadget = $GLOBALS['app']->LoadGadget('Tms', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetRepositories($limit);
    }

    /**
     * Installs a remote theme
     *
     * @access  public
     * @param   string  $theme      Theme's name
     * @param   mixed   $repository Repository identifier (can be local or an integer, 
     *                              the remote repository
     * @return  array   Response
     */
    function InstallTheme($theme, $repository)
    {
        $this->CheckSession('Tms', 'ManageTms');

        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->installTheme($theme, $repository);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Enables a theme as the default theme
     *
     * @access  public
     * @param   string  $theme      Theme's name
     * @return  array   Response
     */
    function EnableTheme($theme)
    {
        $this->CheckSession('Tms', 'ManageTms');

        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->enableTheme($theme);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Uninstalls a theme
     *
     * @access  public
     * @param   string  $theme      Theme's name
     * @return  array   Response
     */
    function UninstallTheme($theme)
    {
        $this->CheckSession('Tms', 'ManageTms');

        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->uninstallTheme($theme);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
     /**
     * Shares a theme
     *
     * @access  public
     * @param   string  $theme      Theme's name
     * @return  array   Response
     */
    function ShareTheme($theme)
    {
        $this->CheckSession('Tms', 'ManageSharing');

        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->shareTheme($theme, true);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Unshares a theme
     *
     * @access  public
     * @param   string  $theme      Theme's name
     * @return  array   Response
     */
    function UnshareTheme($theme)
    {
        $this->CheckSession('Tms', 'ManageSharing');

        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->unshareTheme($theme);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Adds a new repository
     *
     * @access  public
     * @param   string  $name    Repository's name
     * @param   string  $url     Repository's URL
     * @return  array   Response (notice or error)
     */
    function NewRepository($name, $url)
    {
        $this->CheckSession('Tms', 'ManageTms');
        
        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->addRepository($name, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Updates repository's information
     *
     * @access  public
     * @param   int     $id      Repository's ID
     * @param   string  $name    Repository's name
     * @param   string  $url     Repository's URL
     * @return  array   Response (notice or error)
     */
    function UpdateRepository($id, $name, $url)
    {
        $this->CheckSession('Tms', 'ManageTms');
        
        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->updateRepository($id, $name, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Updates repository's information
     *
     * @access  public
     * @param   int     $id      Repository's ID
     * @return  array   Response (notice or error)
     */
    function DeleteRepository($id)
    {
        $this->CheckSession('Tms', 'ManageTms');
        
        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->deleteRepository($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Save gadget settings
     *
     * @access  public
     * @param   string  $shareThemes  Share themes? (a string: true or false)
     * @return  array   Response (notice or error)
     */
    function SaveSettings($shareThemes)
    {
        $this->CheckSession('Tms', 'ManageSettings');
        
        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $model->saveSettings($shareThemes);
        return $GLOBALS['app']->Session->PopLastResponse();
    }    
}