<?php
/**
 * Manage user Access Control Lists.
 *
 *
 * @category   ACL
 * @category   feature
 * @package    Core
 * @author     Ivan Chavero <imcsk8@gluch.org.mx>
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_ACL extends Jaws_Registry
{
    /**
     * ACL Priority
     * @access private
     */
    var $_Priority;

    /**
     * Loaded users/groups so we don't query the DB each
     * time we need a value of them
     *
     * @access  private
     * @var     array
     */
    var $_LoadedTargets;

    /**
     * Constructor
     *
     * @access public
     */
    function Jaws_ACL()
    {
        $this->SetTable('acl');
        $this->Init();
        $this->setPriority($this->GetFromTable('/priority'));
        $this->_LoadedTargets = array(
            'users'  => array(),
            'groups' => array()
        );
    }

    /**
     * Sets the priority for ACLs
     *
     * @param $priority string What the ACLs priorities should be
     * @access  public
     * @return  string  The ACL priority
     */
    function setPriority($priority)
    {
        $this->_Priority = $priority;
    }

    /**
     * Get the priority configured by ACL
     *
     * @access  public
     * @return  string  The ACL priority
     */
    function GetPriority()
    {
        return $this->_Priority;
    }

    /**
     * Looks for a key in the acl registry
     *
     * @access      private
     * @param   string  $name   Key to find
     * @return  boolean  The value of the key, if not key found must return null
     */
    function Get($name)
    {
        $value = parent::Get($name);
        if ($value == 'true') {
            return true;
        }

        if ($value === null) {
			return null;
        }

        return false;
    }

    /**
     * Get the real/full permission of a gadget (and group if it has) for a
     * certain task
     *
     * @access  public
     * @param   string   $user   Username
     * @param   int      $groups array of group's ID or empty string
     * @param   string   $gadget Gadget to use
     * @param   string   $task   Task to use
     * @return  boolean  Permission value: Granted (true) or Denied (false)
     */
    function GetFullPermission($user, $groups, $gadget, $task)
    {
		
		$this->LoadFile($gadget);
        $this->LoadKeysOf($user, 'users');

        // 1. Check for user permission
        $perm['user'] = $this->Get('/ACL/users/'.$user.'/gadgets/'.$gadget.'/'.$task);

		/*
		echo '<br />';
		var_dump($gadget);
		echo ':';
		var_dump($task);
		echo '<br />user => '.var_export($perm['user'], true);
		*/
		
        // 2. Check for groups permission
		if (!empty($groups)) {
            $perm['groups'] = null;
            foreach ($groups as $group) {
				$gPerm = $this->GetGroupPermission($group['group_id'], $gadget, $task);
                //echo '<br />'.var_export($group, true).' => '.var_export($gPerm, true);
                if (!is_null($gPerm)) {
                    $perm['groups'] = is_null($perm['groups'])? $gPerm : ($perm['groups'] || $gPerm);
                }
            }
        }

        // 3. Check for default
        // If there is no key then it must return false
        if ($this->Get('/ACL/gadgets/'.$gadget.'/'.$task) === null) {
            $perm['default'] = false;
        } else {
            $perm['default'] = $this->Get('/ACL/gadgets/'.$gadget.'/'.$task);
        }
		/*
		echo '<br />';
		var_dump($perm);
		echo '<br />';
		var_dump($this->_Priority);
		*/
		
		foreach (explode(',', $this->_Priority) as $p) {
            $p = trim($p);
            if (isset($perm[$p]) && $perm[$p] !== null) {
                return $perm[$p];
            }
        }
        // If not were a valid perm
        return false;
    }

    /**
     * Get a permission to a given Gadget -> Task/Method
     *
     * @access  public
     * @param   string  $user           Username
     * @param   string  $gadget         Gadget name
     * @param   string  $task           Task or method name
     * @param   boolean $checkPriority  Check the values using priorities (default,user,group)
     * @return  boolean True if permission is granted
     */
    function GetPermission($user, $gadget, $task, $checkPriority = true)
    {
        $this->LoadFile($gadget);
        $this->LoadKeysOf($user, 'users');
        // 1. Check for user permission
        $perm['user'] = $this->Get('/ACL/users/'.$user.'/gadgets/'.$gadget.'/'.$task);

        if ($checkPriority === false) {
            return $perm['user'];
        }

        // FIXME: 2.Check for each user groups
        // $perm['groups'] = true;

        // FIXME: 3. Check for hosts, maybe host string is a regexp(e.g. 10.0.0.*)
        // $perm['hosts'] = true;

        // 4. Check for default
        // If there is no key then it must return true
        if ($this->Get('/ACL/gadgets/'.$gadget.'/'.$task) === null) {
            $perm['default'] = false;
        } else {
            $perm['default'] = $this->Get('/ACL/gadgets/'.$gadget.'/'.$task);
        }

        foreach (explode(',', $this->getPriority()) as $p) {
            $p = trim($p);
            if (isset($perm[$p]) && $perm[$p] !== null) {
                return $perm[$p];
            }
        }
        // If not were a valid perm
        return false;
    }

    /**
     * Get a permission to a given Gadget -> Task/Method of a group
     *
     * @access  public
     * @param   string  $group          Group's ID
     * @param   string  $gadget         Gadget name
     * @param   string  $task           Task or method name
     * @param   boolean $checkPriority  Check the values using priorities (default,user,group)
     * @return  boolean True if permission is granted
     */
    function GetGroupPermission($group, $gadget, $task)
    {
        $this->LoadFile($gadget);
        $this->LoadKeysOf($group, 'groups');

        return $this->Get('/ACL/groups/'.$group.'/gadgets/'.$gadget.'/'.$task);
    }

    /**
     * Check permission on a given gadget/task
     * Use it if you want to produce a Jaws_Error::Fatal
     * else use getPermission
     *
     * @param   string $user Username
     * @param   string $gadget Gadget name
     * @param   string $task Task or method name
     * @param   string $errorMessage Error message to return
     *
     * @see getPermission()
     *
     * @return  boolean True if granted, else throws an Exception(Jaws_Error::Fatal)
     */
    function CheckPermission($user, $gadget, $task, $errorMessage = '')
    {
        if ($this->GetPermission($user, $gadget, $task)) {
            return true;
        }

        ///FIXME seems kinda wrong doing this.
        if (empty($errorMessage)) {
            $errorMessage = 'User '.$user.' doesn\'t have permission to execute '.$gadget.'::'.$task;
        }

        Jaws_Error::Fatal($errorMessage, __FILE__, __LINE__);
    }

    /**
     * Get ACL permissions for a given user(name)
     *
     * @access  public
     * @param   string  $user Username
     * @param   boolean $checkByPriority  Check the permissions by their priority (usefull when we
     *                                    just want to know the real values)
     * @return  array   Struct that contains all needed info about the ACL of a given user.
     */
    function GetAclPermissions($user, $checkByPriority = true)
    {
        $this->LoadAllFiles();
        $this->LoadKeysOf($user, 'users');
        $result = $this->GetAsQuery();
        $perms = array();
        foreach ($result as $r) {
            if (preg_match('#/ACL/gadgets/(.*?)/(.*?)#si', $r['name'])) {
                $item = array();
                $item['name'] = str_replace('/ACL/gadgets/', '/ACL/users/'.$user.'/gadgets/', $r['name']);

                $gadget = preg_replace("@\/ACL/gadgets\/(\w+)\/(\w+)@", "\$1", $r['name']);
                $task = str_replace('/ACL/users/'.$user.'/gadgets/'.$gadget.'/', '', $item['name']);

                if ($this->Get($item['name']) === null) {
                    $item['value'] = $this->Get($r['name']);
                } else {
                    $item['value'] = $this->GetPermission($user, $gadget, $task, $checkByPriority);
                }
                $item['default'] = false;
                $perms[$gadget][] = $item;
            }
        }
        return $perms;

    }

    /**
     * Get all the ACL permissions
     *
     * @access  public
     * @param   string $user Username
     * @return  array  Struct that contains all needed info about *ALL* ACL keys
     */
    function GetAllAclPermissions()
    {
        $this->LoadAllFiles();
        $result = $this->GetAsQuery();
        $perms = array();
        foreach ($result as $r) {
            if (preg_match('#/ACL/gadgets/(.*?)/(.*?)#si', $r['name'])) {
                $gadget = preg_replace("@\/ACL/gadgets\/(\w+)\/(\w+)@", "\$1", $r['name']);
                $item = array();
                $item['name'] = $r['name'];
                $item['default'] = false;
                $item['value']   = true;
                $perms[$gadget][] = $item;
            }
        }
        return $perms;
    }

    /**
     * Get ACL permissions for a given group
     *
     * @access  public
     * @param   string  $id               Group's ID
     * @param   boolean $checkByPriority  Check the permissions by their priority (usefull when we
     *                                    just want to know the real values)
     * @return  array Struct that contains all needed info about the ACL for a given user.
     */
    function GetGroupAclPermissions($id)
    {
        $this->LoadAllFiles();
        $this->LoadKeysOf($id, 'groups');
        $result = $this->GetAsQuery();
        $perms = array();
        foreach ($result as $r) {
            if (preg_match('#/ACL/gadgets/(.*?)/(.*?)#si', $r['name'])) {
                $item = array();
                $item['name'] = str_replace('/ACL/gadgets/', '/ACL/groups/'.$id.'/gadgets/', $r['name']);
                $gadgetName = preg_replace("@\/ACL/gadgets\/(\w+)\/(\w+)@", "\$1", $r['name']);
                $task = str_replace('/ACL/groups/'.$id.'/gadgets/'.$gadgetName.'/', '', $item['name']);

                if ($this->Get($item['name']) === null) {
                    $item['value'] = $this->Get($r['name']);
                } else {
                    $item['value'] = $this->GetGroupPermission($id, $gadgetName, $task);
                }

                $item['default'] = false;
                $perms[$gadgetName][] = $item;
            }
        }
        return $perms;
    }

    /**
     * Get all group ACL permissions of an user
     *
     * @access  public
     * @param   string $username  Username
     * @return  array  Struct that contains all needed info about the ACL for a given user.
     */
    function GetGroupAclPermissionsOfUsername($username)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        $groups = $userModel->GetGroupsOfUser($username);
        if (Jaws_Error::IsError($groups)) {
            return false;
        }

        $aclGroups = array();
        foreach ($groups as $group) {
            $acls = $this->GetGroupAclPermissions($group['id']);
            if (!Jaws_Error::IsError($acls)) {
                $aclGroups = $acls;
            }
        }

        return $aclGroups;
    }

    /**
     * Delete all user ACLs
     *
     * @access  public
     * @param   string  $user  Username
     */
    function DeleteUserACL($user)
    {
        $params         = array();
        $params['name'] = '/ACL/users/'.$user.'/%';

        $sql = 'DELETE FROM [[acl]] WHERE [key_name] LIKE {name}';
        $GLOBALS['db']->query($sql, $params);
        $this->UpdateLastUpdate();
    }

    /**
     * Delete all group ACLs
     *
     * @access  public
     * @param   string  $group  Group's ID
     */
    function DeleteGroupACL($group)
    {
        $params         = array();
        $params['name'] = '/ACL/groups/'.$group.'/%';

        $sql = 'DELETE FROM [[acl]] WHERE [key_name] LIKE {name}';
        $GLOBALS['db']->query($sql, $params);
        $this->UpdateLastUpdate();
    }

    /**
     * Saves the key array file in JAWS_DATA . '/cache/acl(gadgets|plugins)' . $component
     *
     * @access public
     * @param string Component's name
     * @param string The type of the component, (plugin or a gadget) only
     *               if the component name is not empty
     */
    function Commit($comp, $type = 'gadgets')
    {
        $return = parent::Commit($comp, $type, '#^/ACL/(.*?)/(.*?)/(.*?)#i');
        return $return;
    }

    /**
     * Loads all the component files
     *
     * @access  public
     */
    function LoadAllFiles()
    {
        $gs = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/enabled_items'));
        $ci = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/core_items'));

        $ci = str_replace(' ', '', $ci);

        // load the core
        $this->LoadFile('core');

        foreach ($gs as $gadget) {
            $this->LoadFile($gadget);
        }

        foreach ($ci as $gadget) {
            $this->LoadFile($gadget);
        }
    }

    /**
     * Returns the SimpleArray in a query style:
     *
     * $array[0] = array('name'  => 'foo',
     *                   'value' => 'bar'),
     * $array[1] = array('name'  => 'bar',
     *                   'value' => 'foo');
     *
     * @access  public
     * @return  array   Array in a QueryStyle
     */
    function GetAsQuery()
    {
        $data = array();
        foreach ($this->_Registry as $key => $value) {
            $data[] = array(
                'name'   => $key,
                'value'  => $value,
            );
        }

        return $data;
    }

    /**
     * Loads all ACL keys of an user
     *
     * @access  public
     * @param   string   $target  Target to search (can be a username or a GID)
     * @param   string   $where   Where to search? users or groups?
     */
    function LoadKeysOf($target, $where)
    {
        if ($target === '' || in_array($target, $this->_LoadedTargets[$where])) {
            return;
        }
        $sql = "SELECT [key_name], [key_value] FROM [[acl]] WHERE [key_name] LIKE '/ACL/".$where."/".$target."/%'";
        $result = $GLOBALS['db']->queryAll($sql, array(), null, null, true);
		if (Jaws_Error::isError($result)) {
            return false;
        }
        $this->_LoadedTargets[$where][$target] = $target;
        $this->_Registry = $result + $this->_Registry;
    }

    /**
     * Regenerates/updates the internal registry array ($this->_Registry)
     *
     * @access  protected
     * @param   string     $component  Component name
     * @param   string     $type       Type of component (gadget or plugin)
     * @return  boolean    Success/Failure
     */
    function _regenerateInternalRegistry($component, $type = 'gadgets')
    {
        $type = strtolower($type);
        if (!in_array($type, array('gadgets', 'plugins'))) {
            return false;
        }

        if ($component == 'core') {
            $sql = "
                SELECT [key_name], [key_value] FROM [[acl]]
                WHERE [key_name] IN('/last_updated', '/priority')";
        } else {
            $sql = "SELECT [key_name], [key_value] FROM [[acl]] WHERE [key_name] LIKE '/ACL/$type/$component/%'";
        }

        $result = $GLOBALS['db']->queryAll($sql, array(), null, null, true);
        if (Jaws_Error::isError($result)) {
            return false;
        }

        $this->_Registry = $result + $this->_Registry;
        return true;
    }
}
