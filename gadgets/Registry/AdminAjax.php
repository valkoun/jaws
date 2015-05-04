<?php
/**
 * Registry AJAX API
 *
 * @category   Ajax
 * @package    Registry
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */

class RegistryAdminAjax extends Jaws_Ajax
{
    /**
     * Returns the registry keys
     *
     * @access  public
     * @return  array   Array with all registry keys
     */
    function GetAllRegistry()
    {
        $this->CheckSession('Registry', 'ManageRegistry');
        $GLOBALS['app']->Registry->LoadAllFiles();
        $simpleArray = $GLOBALS['app']->Registry->GetSimpleArray();
        ksort($simpleArray);

        return $simpleArray;
    }

    /**
     * Returns the value of a registry key
     *
     * @access  public
     * @param   string  $key  Key name
     * @return  string  Value of key
     */
    function GetRegistryKey($key)
    {
        $this->CheckSession('Registry', 'ManageRegistry');
        if (preg_match("#^/(gadgets|plugins\/parse_text)/(.*?)/(.*?)#i", $key, $matches)) {
            $GLOBALS['app']->Registry->LoadFile($matches[2]);
        }
        return $GLOBALS['app']->Registry->Get($key);
    }

    /**
     * Saves the value of a key
     *
     * @access  public
     * @param   string  $key   Key name
     * @param   string  $value Key value
     * @return  array   Response
     */
    function SetRegistryKey($key, $value)
    {
        $this->CheckSession('Registry', 'ManageRegistry');
        if (preg_match("#^/(gadgets|plugins\/parse_text)/(.*?)/(.*?)#i", $key, $matches)) {
            $GLOBALS['app']->Registry->LoadFile($matches[2]);
            $GLOBALS['app']->Registry->Set($key, $value);
            $GLOBALS['app']->Registry->Commit($matches[2]);
        } else {
            $GLOBALS['app']->Registry->Set($key, $value);
            $GLOBALS['app']->Registry->Commit('core');
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('REGISTRY_KEY_SAVED'), RESPONSE_NOTICE);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Returns the acl keys
     *
     * @access  public
     * @return  array   Array with all acl keys
     */
    function GetAllACL()
    {
        $this->CheckSession('Registry', 'ManageRegistry');
        $GLOBALS['app']->ACL->LoadAllFiles();
        $simpleArray = $GLOBALS['app']->ACL->GetSimpleArray();
        ksort($simpleArray);

        return $simpleArray;
    }

    /**
     * Returns the value of an ACL key
     *
     * @access  public
     * @param   string  $key  Key name
     * @return  string  Value of key
     */
    function GetACLKey($key)
    {
        $this->CheckSession('Registry', 'ManageRegistry');
        if (preg_match("#^/ACL/gadgets/(.*?)/(.*?)#i", $key, $matches)) {
            $GLOBALS['app']->ACL->LoadFile($matches[1]);
        }
        switch($key) {
        case '/priority':
        case '/last_update':
            return $GLOBALS['app']->ACL->GetFromTable($key);
            break;
        default:
            return $GLOBALS['app']->ACL->Get($key);
            break;
        }
    }

    /**
     * Saves the value of an ACL key
     *
     * @access  public
     * @param   string  $key   Key name
     * @param   string  $value Key value
     * @return  array   Response
     */
    function SetACLKey($key, $value)
    {
        $this->CheckSession('Registry', 'ManageRegistry');
        if (preg_match("#^/ACL/gadgets/(.*?)/(.*?)#i", $key, $matches)) {
            $GLOBALS['app']->ACL->LoadFile($matches[1]);
            $GLOBALS['app']->ACL->Set($key, $value);
            $GLOBALS['app']->ACL->Commit($matches[1]);
        } else {
            $GLOBALS['app']->ACL->Set($key, $value);
            $GLOBALS['app']->ACL->Commit('core');
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('REGISTRY_KEY_SAVED'), RESPONSE_NOTICE);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
}
?>
