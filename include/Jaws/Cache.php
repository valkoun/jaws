<?php
/**
 * Base class of cache drivers
 *
 * @category   Cache
 * @package    Core
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Cache
{
    /**
     * Cache driver
     * @access private
     */
    var $_Driver;

    /**
     * An interface for available drivers
     *
     * @access  public
     */
    function &factory()
    {
        $this->_Driver = $this->Registry->Get('/config/cache/driver');
        $driverFile = JAWS_PATH . 'include/Jaws/Cache/'. $this->_Driver . '.php';
        if (!file_exists($driverFile)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "Loading cache driver $driverFile failed.");
            }
            $this->_Driver = 'File';
            $driverFile = JAWS_PATH . 'include/Jaws/Cache/'. $this->_Driver . '.php';
        }

        include_once $driverFile;
        $className = 'Jaws_Cache_' . $this->_Driver;
        $obj = new $className();
        return $obj;
    }

    /**
     * Store cache of given component/section
     *
     * @access  public
     */
    function set($component, $section, $params, $data, $lifetime = 0)
    {
        return false;
    }

    /**
     * Get cached data of given component/section
     *
     * @access  public
     */
    function get($component, $section, $params = null)
    {
        return null;
    }

    /**
     * Delete cached data of given component/section
     *
     * @access  public
     */
    function delete($component = null, $section = null, $params = null)
    {
        return true;
    }

    /**
     * Delete all expired cached data
     *
     * @access  public
     */
    function delete_expired()
    {
        return true;
    }

}