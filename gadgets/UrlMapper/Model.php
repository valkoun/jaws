<?php
/**
 * UrlMapper Core Gadget
 *
 * @category   GadgetModel
 * @package    UrlMapper
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UrlMapperModel extends Jaws_Model
{
    /**
     * Adds a new custom map
     *
     * @access   public
     * @param    string    $gadget      Gadget's name (FS name)
     * @param    string    $action      Gadget's action to use
     * @param    string    $map         Map to use (foo/bar/{param}/{param2}...)
     * @param    string    $extension   Extension of map
     * @return   boolean   Success/Failure
     */
    function AddMap($gadget, $action, $map, $regexp, $extension = '', $custom = true)
    {
        if (!empty($extension) && $extension{0} != '.') {
            $extension = '.'.$extension;
        }

        $params = array();
        $params['gadget']    = $gadget;
        $params['action']    = $action;
        $params['regexp']    = $regexp;
        $params['extension'] = $extension;
        $params['custom']    = $custom;
        $params['map']       = $map; // this item must be at end of array

        $sql = '
            INSERT INTO [[url_maps]]
                ([gadget], [action], [map], [regexp], [extension], [custom])
            VALUES
                ({gadget}, {action}, {map}, {regexp}, {extension}, {custom})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('URLMAPPER_ERROR_MAP_NOT_ADDED'), _t('URLMAPPER_NAME'));
        }

        return true;
    }

    /**
     * Returns all aliases stored in the DB
     *
     * @access  public
     * @return  array   Array of URL aliases
     */
    function GetAliases()
    {
        $sql = '
            SELECT
                [id], [alias_url]
            FROM [[url_aliases]]';

        $result = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($result)) {
            return array();
        }

        return $result;
    }

    /**
     * Returns basic information of certain alias
     *
     * @access   public
     * @param    int      $id      Alias ID
     * @return   array    Alias information
     */
    function GetAlias($id)
    {
        $params       = array();
        $params['id'] = $id;

        $sql = '
            SELECT
                [id], [alias_url], [real_url]
            FROM [[url_aliases]]
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Returns true if hash already exists
     *
     * @access   public
     * @param    string    $hash    Alias HASH value
     * @return   boolean   Exists/Doesn't exists
     */
    function AliasExists($hash)
    {
        $params         = array();
        $params['hash'] = $hash;

        $sql = '
            SELECT
                COUNT([id])
            FROM [[url_aliases]]
            WHERE [alias_hash] = {hash}';

        $result = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return true;
        }

        return ($result == '0') ? false : true;
    }

    /**
     * Returns the maps of a certain gadget/action stored in the DB
     *
     * @access  public
     * @param   boolean $base   Include base map
     * @param   boolean $custom Include custom map
     * @return  array   Array of maps
     */
    function GetMaps($base = true, $custom = false)
    {
        $params = array();
        $params['base']   = !$base;
        $params['custom'] = $custom;

        $sql = '
            SELECT
                [id], [gadget], [action], [map], [regexp], [extension], [custom]
            FROM [[url_maps]]
            WHERE
                [custom] = {base}
              OR
                [custom] = {custom}
            ORDER BY [id] ASC';

        $types = array('integer', 'text', 'text', 'text', 'text', 'text', 'boolean');
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return array();
        }

        return $result;
    }

    /**
     * Returns the maps of a certain gadget/action stored in the DB
     *
     * @access  public
     * @param   string  $gadget   Gadget's name (FS name)
     * @param   string  $action   Gadget's action to use
     * @return  array   Array of custom maps
     */
    function GetActionMaps($gadget, $action)
    {
        $params = array();
        $params['gadget'] = $gadget;
        $params['action'] = $action;

        $sql = '
            SELECT
                [id], [map], [extension], [custom]
            FROM [[url_maps]]
            WHERE
                [gadget] = {gadget}
              AND
                [action] = {action}
            ORDER BY [id] ASC';

        $types = array('integer', 'text', 'text', 'boolean');
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return array();
        }

        return $result;
    }

    /**
     * Returns only the map route of a certain map (by its ID)
     *
     * @access  public
     * @param   int      $id       Map's ID
     * @return  string   Map route
     */
    function GetMapRoute($id)
    {
        $params = array();
        $params['id'] = $id;

        $sql = '
            SELECT
                [map], [regexp], [extension], [custom]
            FROM [[url_maps]]
            WHERE [id] = {id}';

        $types = array('text', 'text', 'text', 'boolean');
        $result = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Returns the real path of an alias (given path), if no alias is found
     * it returns false
     *
     * @access  public
     * @param   string  $alias  Alias
     * @return  mixed   Real path (URL) or false
     */
    function GetAliasPath($alias)
    {
        $sql = '
            SELECT
               [real_url]
            FROM [[url_aliases]]
            WHERE [alias_hash] = {hash}';

        $result = $GLOBALS['db']->queryOne($sql, array('hash' => md5($alias)));
        if (Jaws_Error::IsError($result) || empty($result)) {
            return false;
        }

        return $result;
    }

}