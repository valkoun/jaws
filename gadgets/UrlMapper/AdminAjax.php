<?php
/**
 * UrlMapper AJAX API
 *
 * @category   Ajax
 * @package    UrlMapper
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UrlMapperAdminAjax extends Jaws_Ajax
{
    /**
     * Returns the (normal) actions of a certain gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @return  array   Array with actions
     */
    function GetGadgetActions($gadget)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        //Get the actions
        $actions  = $GLOBALS['app']->GetGadgetActions($gadget);
        $ractions = (isset($actions['NormalAction'])) ? $actions['NormalAction'] : array();
        if (isset($actions['StandaloneAction'])) {
            $ractions = $ractions + $actions['StandaloneAction'];
        }
        //Clean the actions, we only want normal actions
        $mapActions = array();
        foreach($ractions as $key => $action) {
            if ($action['mode'] == 'NormalAction' || $action['mode'] == 'StandaloneAction') {
                $mapActions[$key] = $action['name'];
            }
        }
        return $mapActions;
    }

    /**
     * Returns the total maps of a certain action in a certain gadget
     *
     * @access  public
     * @param   string  $gadget  Gadget name so we get sure we don't return the same action
     *                           maps of another gadget
     * @param   string  $action  Action name
     * @return  array   Maps that an action has
     */
    function GetMapsOfAction($gadget, $action)
    {
        //Now get the custom maps
        $gHTML = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminHTML');
        return $gHTML->GetMaps($gadget, $action);
    }

    /**
     * Adds a new map
     *
     * @access  public
     * @param   string   $gadget   Gadget's name
     * @param   string   $action   Gadget's action
     * @param   string   $map      Map to use
     * @param   string   $regexp   Regular expression
     * @return  boolean  Success/Failure
     */
    function AddMap($gadget, $action, $map, $regexp, $extension)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        $res = $model->AddMap($gadget, $action, $map, $regexp, $extension);
        if (!Jaws_Error::IsError($res)) {
            $model->UpdateCustomMaps();
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_MAP_ADDED', $map), RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Updates a map
     *
     * @access  public
     * @param   int      $id       Map's ID
     * @param   string   $map      New map
     * @return  boolean  Success/Failure
     */
    function UpdateMap($id, $map, $regexp, $extension)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        $res = $model->UpdateMap($id, $map, $regexp, $extension);
        if (!Jaws_Error::IsError($res)) {
            $model->UpdateCustomMaps();
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a map
     *
     * @access  public
     * @param   int     $id Map's ID
     * @return  boolean Success/Failure
     */
    function DeleteMap($id)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        $res = $model->DeleteMap($id);
        if (!Jaws_Error::IsError($res)) {
            $model->UpdateCustomMaps();
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Returns the map route (no additional information) of a certain map
     *
     * @access  public
     * @param   int     $id Map ID
     * @return  string  Map route
     */
    function GetMap($id)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        return $model->GetMapRoute($id);
    }

    /**
     * Updates the map settings
     *
     * @access  public
     * @param   string   $enabled     Should maps be used? (true/false)
     * @param   boolean  $use_aliases Should aliases be used?
     * @param   string   $precedence  custom map precedence over default map (true/false)
     * @param   string   $extension   Extension to use
     * @return  boolean  Success/Failure
     */
    function UpdateSettings($enabled, $use_aliases, $precedence, $extension)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        $model->SaveSettings($enabled == 'true',
                             $use_aliases == 'true',
                             $precedence == 'true',
                             $extension);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Returns all aliases
     *
     * @access  public
     * @return  array    List of aliases
     */
    function GetAliases()
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model   = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        $aliases = $model->GetAliases();
        if (count($aliases) > 0) {
            return $aliases;
        }
        return false;
    }

    /**
     * Returns basic information of certain alias
     *
     * @access  public
     * @param   int      $id      Alias ID
     * @return  array    Alias information
     */
    function GetAlias($id)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        return $model->GetAlias($id);
    }

    /**
     * Adds a new alias
     *
     * @access  public
     * @param   string   $alias   Alias value
     * @param   string   $url     Real URL
     * @return  boolean  Success/Failure
     */
    function AddAlias($alias, $url)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper',  'AdminModel');
        $model->AddAlias($alias, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Updates an alias by its ID
     *
     * @access  public
     * @param   int      $id      Alias ID
     * @param   string   $alias   Alias value
     * @param   string   $url     Real URL
     * @return  boolean  Success/Failure
     */
    function UpdateAlias($id, $alias, $url)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper',  'AdminModel');
        $model->UpdateAlias($id, $alias, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes an alias by its ID
     *
     * @access  public
     * @param   int      $id      Alias ID
     * @return  boolean  Success/Failure
     */
    function DeleteAlias($id)
    {
        $this->CheckSession('UrlMapper', 'ManageUrlMapper');
        $model = $GLOBALS['app']->LoadGadget('UrlMapper',  'AdminModel');
        $model->DeleteAlias($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

}