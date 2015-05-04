<?php
/**
 * UrlMapper Core Gadget
 *
 * @category   GadgetModel
 * @package    UrlMapper
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
require_once JAWS_PATH . 'gadgets/UrlMapper/Model.php';

class UrlMapperAdminModel extends UrlMapperModel
{
    /**
     * Installs the gadget
     *
     * @access       public
     * @return       true on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        // Install listener for Add/Update/Removing gadget's maps
        $GLOBALS['app']->Listener->NewListener($this->_Name, 'onBeforeUninstallingGadget', 'RemoveGadgetMaps');
        $GLOBALS['app']->Listener->NewListener($this->_Name, 'onAfterEnablingGadget',      'AddGadgetMaps');
        $GLOBALS['app']->Listener->NewListener($this->_Name, 'onAfterUpdatingGadget',      'UpdateGadgetMaps');

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/UrlMapper/pluggable', 'false');

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
        if (version_compare($old, '0.2.0', '<')) {
            $result = $this->installSchema('0.2.0.xml', '', "$old.xml");
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
        }

        if (version_compare($old, '0.3.0', '<')) {
            $result = $this->installSchema('0.3.0.xml', '', '0.2.0.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            $result = $GLOBALS['db']->dropTable('custom_maps');
            if (Jaws_Error::IsError($result)) {
                //not important
            }

        }

        $result = $this->installSchema('schema.xml', '', '0.3.0.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

		// Install listener for Add/Update/Removing gadget's maps
		$GLOBALS['app']->Listener->NewListener($this->_Name, 'onBeforeUninstallingGadget', 'RemoveGadgetMaps');
		$GLOBALS['app']->Listener->NewListener($this->_Name, 'onAfterEnablingGadget',      'AddGadgetMaps');
		$GLOBALS['app']->Listener->NewListener($this->_Name, 'onAfterUpdatingGadget',      'UpdateGadgetMaps');
        
		$this->LoadMapsFromGadgets();
        Jaws_Utils::Delete(JAWS_DATA . 'maps', false);

        // Registry keys.

        return true;
    }

    /**
     * Load map from each gadget (gadgets/Foo/Map.php)
     *
     * @access public
     */
    function LoadMapsFromGadgets()
    {
        $this->_loaded_from_gadgets = false;
        $gadgets  = $GLOBALS['app']->Registry->Get('/gadgets/enabled_items');
        $cgadgets = $GLOBALS['app']->Registry->Get('/gadgets/core_items');

        $gadgets  = explode(',', $gadgets);
        $cgadgets = explode(',', $cgadgets);
        $final = array_merge($gadgets, $cgadgets);
        foreach ($final as $gadget) {
            if ($gadget == '') {
                continue;
            }

            $file = JAWS_PATH . 'gadgets/' . $gadget . '/Map.php';
            if (file_exists($file)) {
                include_once $file;
            }
        }
    }

    /**
     * Add all gadget's maps
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function AddGadgetMaps($gadget)
    {
        $file = JAWS_PATH . 'gadgets/' . $gadget . '/Map.php';
        if (file_exists($file)) {
            include_once $file;
            Jaws_Utils::Delete(JAWS_DATA . 'maps/core.php');
        }

        return true;
    }

    /**
     * Update all gadget's maps
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function UpdateGadgetMaps($gadget)
    {
        $params = array();
        $params['gadget'] = $gadget;
        $params['custom'] = false;

        $sql = '
            DELETE FROM [[url_maps]]
            WHERE
                [gadget] = {gadget}
              AND
                [custom] = {custom}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        $file = JAWS_PATH . 'gadgets/' . $gadget . '/Map.php';
        if (file_exists($file)) {
            include_once $file;
            Jaws_Utils::Delete(JAWS_DATA . 'maps/core.php');
        }

        return true;
    }

    /**
     * Delete all maps related with a gadget
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function RemoveGadgetMaps($gadget)
    {
        $params = array();
        $params['gadget'] = $gadget;

        $sql = '
            DELETE FROM [[url_maps]]
            WHERE
                [gadget] = {gadget}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        Jaws_Utils::Delete(JAWS_DATA . 'maps/core.php');
        Jaws_Utils::Delete(JAWS_DATA . 'maps/custom.php');
        return true;
    }

    /**
     * Save settings
     *
     * @access  public
     * @param   boolean  $enabled     Should maps be used?
     * @param   boolean  $use_aliases Should aliases be used?
     * @param   boolean  $precedence  custom map precedence over default map
     * @param   string   $extension   Extension to use
     * @return  boolean  Success/Failure
     */
    function SaveSettings($enabled, $use_aliases, $precedence, $extension)
    {
        $xss   = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $res = $GLOBALS['app']->Registry->Set('/map/enabled',     ($enabled === true)? 'true' : 'false');
        $res = $res && $GLOBALS['app']->Registry->Set('/map/custom_precedence', ($precedence === true)?  'true' : 'false');
        $res = $res && $GLOBALS['app']->Registry->Set('/map/extensions',  $xss->parse($extension));
        $res = $res && $GLOBALS['app']->Registry->Set('/map/use_aliases', ($use_aliases === true)? 'true' : 'false');

        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_SETTINGS_NOT_SAVED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_SETTINGS_NOT_SAVED'), _t('URLMAPPER_NAME'));
        }

        $GLOBALS['app']->Registry->commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_SETTINGS_SAVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Adds a new alias
     *
     * @access   public
     * @param    string   $alias   Alias value
     * @param    string   $url     Real URL
     * @return   boolean   Success/Failure
     */
    function AddAlias($alias, $url)
    {
        if (trim($alias) == '' || trim($url) == '') {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_ALIAS_NOT_ADDED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_ALIAS_NOT_ADDED'), _t('URLMAPPER_NAME'));
        }

        $params = array();
        $params['real']  = $url;
        $params['alias'] = $alias;
        $params['hash']  = md5($alias);

        if ($this->AliasExists($params['hash'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_ALIAS_ALREADY_EXISTS'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_ALIAS_ALREADY_EXISTS'), _t('URLMAPPER_NAME'));
        }

        $sql = '
            INSERT INTO [[url_aliases]]
                ([real_url], [alias_url], [alias_hash])
            VALUES
                ({real}, {alias}, {hash})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_ALIAS_NOT_ADDED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_ALIAS_NOT_ADDED'), _t('URLMAPPER_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ALIAS_ADDED'), RESPONSE_NOTICE);
        return true;
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
        if (trim($alias) == '' || trim($url) == '') {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_ALIAS_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_ALIAS_NOT_UPDATED'), _t('URLMAPPER_NAME'));
        }

        if ($url{0} == '?') {
            $url = substr($url, 1);
        }

        $params           = array();
        $params['id']     = $id;
        $params['real']   = $url;
        $params['alias']  = $alias;
        $params['hash']   = md5($alias);

        $sql = '
            SELECT
                [alias_hash]
            FROM [[url_aliases]]
            WHERE [id] = {id}';
        $result = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_ALIAS_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_ALIAS_NOT_UPDATED'), _t('URLMAPPER_NAME'));
        }

        if ($result != $params['hash']) {
            if ($this->AliasExists($params['hash'])) {
                $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_ALIAS_ALREADY_EXISTS'), RESPONSE_ERROR);
                return new Jaws_Error(_t('URLMAPPER_ERROR_ALIAS_ALREADY_EXISTS'), _t('URLMAPPER_NAME'));
            }
        }

        $sql = '
            UPDATE [[url_aliases]] SET
                [real_url] = {real},
                [alias_url] = {alias},
                [alias_hash] = {hash}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_ALIAS_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_ALIAS_NOT_UPDATED'), _t('URLMAPPER_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ALIAS_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes an alias
     *
     * @access   public
     * @param    int      $id      Alias ID
     * @return   boolean  Success/Failure
     */
    function DeleteAlias($id)
    {
        $params       = array();
        $params['id'] = $id;

        $sql = 'DELETE FROM [[url_aliases]] WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_ALIAS_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_ALIAS_NOT_DELETED'), _t('URLMAPPER_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ALIAS_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes a map
     *
     * @access   public
     * @param    int        $id Map's ID
     * @return   boolean    Success/Failure
     */
    function DeleteMap($id)
    {
        $params       = array();
        $params['id'] = $id;

        $sql = '
            DELETE FROM [[url_maps]]
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_MAP_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_MAP_NOT_DELETED'), _t('URLMAPPER_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_MAP_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Updates the map route of a map by its ID
     *
     * @access   public
     * @param    int       $id       Map's ID
     * @param    string    $map      Map to use (foo/bar/{param}/{param2}...)
     * @return   boolean   Success/Failure
     */
    function UpdateMap($id, $map, $regexp, $extension)
    {
        if (!empty($extension) && $extension{0} != '.') {
            $extension = '.'.$extension;
        }

        $params = array();
        $params['id'] = $id;
        $params['map'] = $map;
        $params['regexp'] = $regexp;
        $params['extension'] = $extension;

        $sql = '
            UPDATE [[url_maps]] SET
                [map] = {map},
                [regexp] = {regexp},
                [extension] = {extension}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_ERROR_MAP_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('URLMAPPER_ERROR_MAP_NOT_UPDATED'), _t('URLMAPPER_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('URLMAPPER_MAP_UPDATED', $map), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Updates the custom map file
     *
     *  - Deletes the custom map file (if it exists)
     *
     * @access   public
     * @return   boolean   Success/Failure
     */
    function UpdateCustomMaps()
    {
        Jaws_Utils::Delete(JAWS_DATA. 'maps'. DIRECTORY_SEPARATOR. 'custom.php');
        return true;
    }

}