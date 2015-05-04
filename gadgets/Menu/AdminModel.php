<?php
/**
 * Menu Gadget
 *
 * @category   GadgetModel
 * @package    Menu
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'gadgets/Menu/Model.php';

class MenuAdminModel extends MenuModel
{
    /**
     * Install the gadget
     *
     * @access  public
     * @return  boolean  Success with true and failure with Jaws_Error
     */
    function InstallGadget()
    {
        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/schema/insert.xml')) {
			$result = $this->installSchema('insert.xml', '', 'schema.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}

		// Install listener for updating page items related to gadgets
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        //$GLOBALS['app']->Shouter->NewShouter('Core', 'onAddMenuItem');             // trigger an action when we add a menu
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateMenuItem');          // trigger an action when we update a menu
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteMenuItem');          // trigger an action when we delete a menu
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAfterInsertMenuItem');             // trigger an action after we add a menu
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAfterUpdateMenuItem');          // trigger an action after we update a menu
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAfterDeleteMenuItem');          // trigger an action after we delete a menu
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		//$GLOBALS['app']->Listener->NewListener($this->_Name, 'onAddMenuItem', 'InsertMenuByURL');
		$GLOBALS['app']->Listener->NewListener('Menu', 'onUpdateMenuItem', 'UpdateMenuByURL');
		$GLOBALS['app']->Listener->NewListener('Menu', 'onDeleteMenuItem', 'DeleteMenuByURL');
		$GLOBALS['app']->Listener->NewListener('Menu', 'onBeforeUninstallingGadget', 'RemoveMenusByType');

        // Registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Menu/default_group_id', '1');

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
        $tables = array('menus',
                        'menus_groups');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('MENU_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('Menu', 'UpdateMenuByURL');
        $GLOBALS['app']->Listener->DeleteListener('Menu', 'DeleteMenuByURL');
        $GLOBALS['app']->Listener->DeleteListener('Menu', 'RemoveMenusByType');
		
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onUpdateMenuItem');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onDeleteMenuItem');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onAfterInsertMenuItem');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onAfterUpdateMenuItem');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onAfterDeleteMenuItem');

        // Registry keys
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Menu/default_group_id');

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
            $result = $this->installSchema('schema.xml', '', "$old.xml");
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            $result = $this->installSchema('insert.xml', '', 'schema.xml', true);
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            $sql = '
                SELECT [id], [title], [url], [menu_position]
                FROM [[menu]]
                ORDER BY [menu_position]';
            $menus = $GLOBALS['db']->queryAll($sql);
            if (Jaws_Error::IsError($menus)) {
                return $menus;
            }

            foreach ($menus as $m_idx => $menu) {
                $this->InsertMenu(0, 1, 'url', $menu['title'], $menu['url'], 0, $m_idx + 1, 1);
                $pid = $GLOBALS['db']->lastInsertID('menus', 'id');
                if (Jaws_Error::IsError($pid) || empty($pid)) {
                    $pid = $m_idx + 1;
                }
                $sql = '
                    SELECT [id], [text], [url], [item_position]
                    FROM [[menu_item]]
                    WHERE [parent_id] = {parent_id}
                    ORDER BY [item_position]';
                $params = array();
                $params['parent_id'] = $menu['id'];
                $subMenus = $GLOBALS['db']->queryAll($sql, $params);
                if (Jaws_Error::IsError($subMenus)) {
                    return $subMenus;
                }

                foreach ($subMenus as $s_idx => $submenu) {
                    $this->InsertMenu($pid, 1, 'url', $submenu['text'], $submenu['url'], 0, $s_idx + 1, 1);
                }
            }

            $tables = array('menu',
                            'menu_item');
            foreach ($tables as $table) {
                $result = $GLOBALS['db']->dropTable($table);
                if (Jaws_Error::IsError($result)) {
                    // do nothing
                }
            }

            // ACL keys
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Menu/ManageMenus',  'true');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Menu/ManageGroups', 'true');

            // Registry keys.
            $GLOBALS['app']->Registry->NewKey('/gadgets/Menu/default_group_id', '1');
        }

        //remove old event listener
        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener($this->_Name);

        // Install listener for removing menu's item related to uninstalled gadget
        $GLOBALS['app']->Listener->NewListener($this->_Name, 'onBeforeUninstallingGadget', 'RemoveMenusByType');

        $GLOBALS['app']->Session->PopLastResponse(); // emptying all responses message
        return true;
    }

    /**
    * Insert a group
    * @access  public
    *
    * @return  boolean Success/Failure (Jaws_Error)
    */
    function InsertGroup($title, $title_view, $visible)
    {
        $sql = 'SELECT COUNT([id]) FROM [[menus_groups]] WHERE [title] = {title}';
        $gc = $GLOBALS['db']->queryOne($sql, array('title' => $title));
        if (Jaws_Error::IsError($gc)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if ($gc > 0) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MENU_ERROR_DUPLICATE_GROUP_TITLE'), RESPONSE_ERROR);
            return false;
        }

        $sql = '
            INSERT INTO [[menus_groups]]
                ([title], [title_view], [visible])
            VALUES
                ({title}, {title_view}, {visible})';

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params                = array();
        $params['title']       = $xss->parse($title);
        $params['title_view']  = $title_view;
        $params['visible']     = $visible;
        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }
        $gid = $GLOBALS['db']->lastInsertID('menus_groups', 'id');
        $GLOBALS['app']->Session->PushLastResponse($gid.'%%' . _t('MENU_NOTICE_GROUP_CREATED'), RESPONSE_NOTICE);

        return true;
    }

    /**
    * Insert a menu
    * @access  public
    *
    * @return  boolean Success/Failure (Jaws_Error)
    */
    function InsertMenu($pid, $gid, $type, $title, $url, $url_target, $rank, $visible, $auto = false)
    {
		$GLOBALS['app']->Translate->LoadTranslation('Menu', JAWS_GADGET);
        $sql = '
            INSERT INTO [[menus]]
                ([pid], [gid], [menu_type], [title], [url], [url_target], [rank], [visible])
            VALUES
                ({pid}, {gid}, {type}, {title}, {url}, {url_target}, {rank}, {visible})';

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params                = array();
        $params['pid']         = $pid;
        $params['gid']         = $gid;
        $params['type']        = $type;
        $params['title']       = $xss->parse($title);
        $params['url']         = $xss->parse($url);
        $params['url_target']  = $url_target;
        $params['rank']        = (int)$rank;
        $params['visible']     = $visible;
        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }
        $mid = $GLOBALS['db']->lastInsertID('menus', 'id');
        $this->MoveMenu($mid, $gid, $gid, $pid, $pid, $rank, null);
		//echo "MoveMenu(".$mid.", ".$gid.", ".$gid.", ".$pid.", ".$pid.", ".(int)$rank.", null)";
		//exit;
		if ($auto != true) {
			$GLOBALS['app']->Session->PushLastResponse($mid.'%%' . _t('MENU_NOTICE_MENU_CREATED'), RESPONSE_NOTICE);
        }
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAfterInsertMenuItem', $mid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		return true;
    }

    /**
    * Update a group
    * @access  public
    *
    * @return  boolean Success/Failure (Jaws_Error)
    */
    function UpdateGroup($gid, $title, $title_view, $visible)
    {
		$GLOBALS['app']->Translate->LoadTranslation('Menu', JAWS_GADGET);
        $sql = '
            SELECT
                COUNT([id])
            FROM [[menus_groups]]
            WHERE
                [id] != {gid} AND [title] = {title}';

        $gc = $GLOBALS['db']->queryOne($sql, array('gid' => $gid, 'title' => $title));
        if (Jaws_Error::IsError($gc)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if ($gc > 0) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MENU_ERROR_DUPLICATE_GROUP_TITLE'), RESPONSE_ERROR);
            return false;
        }

        $sql = '
            UPDATE [[menus_groups]] SET
                [title]       = {title},
                [title_view]  = {title_view},
                [visible]     = {visible}
            WHERE [id] = {gid}';

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params                = array();
        $params['gid']         = $gid;
        $params['title']       = $xss->parse($title);
        $params['title_view']  = $title_view;
        $params['visible'] = $visible;
        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('MENU_NOTICE_GROUP_UPDATED'), RESPONSE_NOTICE);

        return true;
    }

    /**
    * Update a menu
    * @access  public
    *
    * @return  boolean Success/Failure (Jaws_Error)
    */
    function UpdateMenu($mid, $pid, $gid, $type, $title, $url, $url_target, $rank, $visible)
    {
		$GLOBALS['app']->Translate->LoadTranslation('Menu', JAWS_GADGET);
        $oldMenu = $this->GetMenu($mid);
        if (Jaws_Error::IsError($oldMenu)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MENU_ERROR_GET_MENUS'), RESPONSE_ERROR);
            return false;
        }
        $sql = '
            UPDATE [[menus]] SET
                [pid]         = {pid},
                [gid]         = {gid},
                [menu_type]   = {type},
                [title]       = {title},
                [url]         = {url},
                [url_target]  = {url_target},
                [rank]        = {rank},
                [visible]     = {visible}
            WHERE [id] = {mid}';

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params                = array();
        $params['mid']         = $mid;
        $params['pid']         = $pid;
        $params['gid']         = $gid;
        $params['type']        = $type;
        $params['title']       = $xss->parse($title);
        $params['url']         = $xss->parse($url);
        $params['url_target']  = $url_target;
        $params['rank']        = $rank;
        $params['visible']     = $visible;
        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }
        $this->MoveMenu($mid, $gid, $oldMenu['gid'], $pid, $oldMenu['pid'], $rank, $oldMenu['rank']);
        $GLOBALS['app']->Session->PushLastResponse(_t('MENU_NOTICE_MENU_UPDATED'), RESPONSE_NOTICE);
		
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAfterUpdateMenuItem', $mid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
        return true;
    }

    /**
     * Delete a group
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteGroup($gid)
    {
		$GLOBALS['app']->Translate->LoadTranslation('Menu', JAWS_GADGET);
        if ($gid == 1) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MENU_ERROR_GROUP_NOT_DELETABLE'), RESPONSE_ERROR);
            return false;
        }
        $group = $this->GetGroups($gid);
        if (Jaws_Error::IsError($group)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if(!isset($group['id'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MENU_ERROR_GROUP_DOES_NOT_EXISTS'), RESPONSE_ERROR);
            return false;
        }

        $sql = 'DELETE FROM [[menus]] WHERE [gid] = {gid}';
        $res = $GLOBALS['db']->query($sql, array('gid' => $gid));
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        $sql = 'DELETE FROM [[menus_groups]] WHERE [id] = {gid}';
        $res = $GLOBALS['db']->query($sql, array('gid' => $gid));
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('MENU_NOTICE_GROUP_DELETED', $gid), RESPONSE_NOTICE);

        return true;
    }

    /**
     * Delete a menu
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteMenu($mid)
    {
        $menu = $this->GetMenu($mid);
        if (Jaws_Error::IsError($menu)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if(isset($menu['id'])) {
            $sql  = 'SELECT [id] FROM [[menus]] WHERE [pid] = {mid}';
            $pids = $GLOBALS['db']->queryAll($sql, array('mid' => $mid));
            if (Jaws_Error::IsError($pids)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }

            foreach ($pids as $pid) {
                if (!$this->DeleteMenu($pid['id'])) {
                    return false;
                }
            }

            $this->MoveMenu($mid, $menu['gid'], $menu['gid'], $menu['pid'], $menu['pid'], 0xfff, $menu['rank']);
            $sql = 'DELETE FROM [[menus]] WHERE [id] = {mid}';
            $res = $GLOBALS['db']->query($sql, array('mid' => $mid));
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
		}
		
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAfterDeleteMenuItem', $mid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
        
		return true;
    }

    /**
     * Delete a all menu related with a gadget (type = %gadget%)
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function RemoveMenusByType($type)
    {
        $sql  = 'SELECT [id] FROM [[menus]] WHERE [menu_type] = {type}';
        $mids = $GLOBALS['db']->queryAll($sql, array('type' => $type));
        if (Jaws_Error::IsError($mids)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }
        foreach ($mids as $mid) {
            if (!$this->DeleteMenu($mid['id'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * function for change gid, pid and rank of menus
     *
     * @access  public
     * @return  array   Response (notice or error)
     */
    function MoveMenu($mid, $new_gid, $old_gid, $new_pid, $old_pid, $new_rank, $old_rank)
    {
        if ($new_gid != $old_gid) {
            // set gid of submenu items
            $sub_menus = $this->GetLevelsMenus($mid);
            if (!Jaws_Error::IsError($sub_menus)) {
                foreach ($sub_menus as $menu) {
                    $sql = '
                        UPDATE [[menus]]
                        SET [gid]  = {gid}
                        WHERE [id] = {mid} OR [pid] = {mid}';
                    $params         = array();
                    $params['mid']  = $menu['id'];
                    $params['gid']  = $new_gid;
                    $res = $GLOBALS['db']->query($sql, $params);
                    if (Jaws_Error::IsError($res)) {
                        $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                        return false;
                    }
                }
            }
        }

        if (($new_pid != $old_pid) || ($new_gid != $old_gid)) {
            // resort menu items in old_pid
            $sql = '
                UPDATE [[menus]] SET
                    [rank] = [rank] - 1
                WHERE
                    [pid] = {pid}
                  AND
                    [gid] = {gid}
                  AND
                    [rank] > {rank}';

            $params         = array();
            $params['gid']  = $old_gid;
            $params['pid']  = $old_pid;
            $params['rank'] = $old_rank;
            $res = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        }

        if (($new_pid != $old_pid) || ($new_gid != $old_gid)) {
            // resort menu items in new_pid
            $sql = '
                UPDATE [[menus]] SET
                    [rank] = [rank] + 1
                WHERE
                    [id] <> {mid}
                  AND
                    [gid] = {gid}
                  AND
                    [pid] = {pid}
                  AND
                    [rank] >= {new_rank}';

            $params             = array();
            $params['mid']      = $mid;
            $params['gid']      = $new_gid;
            $params['pid']      = $new_pid;
            $params['new_rank'] = $new_rank;
            $res = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        } elseif (empty($old_rank)) {
            $sql = '
                UPDATE [[menus]] SET
                    [rank] = [rank] + 1
                WHERE
                    [id] <> {mid}
                  AND
                    [gid] = {gid}
                  AND
                    [pid] = {pid}
                  AND
                    [rank] >= {new_rank}';

            $params             = array();
            $params['mid']      = $mid;
            $params['gid']      = $new_gid;
            $params['pid']      = $new_pid;
            $params['new_rank'] = $new_rank;
            $res = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        } elseif ($new_rank > $old_rank) {
            // resort menu items in new_pid
            $sql = '
                UPDATE [[menus]] SET
                    [rank] = [rank] - 1
                WHERE
                    [id] <> {mid}
                  AND
                    [gid] = {gid}
                  AND
                    [pid] = {pid}
                  AND
                    [rank] > {old_rank}
                  AND
                    [rank] <= {new_rank}';

            $params             = array();
            $params['mid']      = $mid;
            $params['gid']      = $new_gid;
            $params['pid']      = $new_pid;
            $params['old_rank'] = $old_rank;
            $params['new_rank'] = $new_rank;
            $res = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        } elseif ($new_rank < $old_rank) {
            // resort menu items in new_pid
            $sql = '
                UPDATE [[menus]] SET
                    [rank] = [rank] + 1
                WHERE
                    [id] <> {mid}
                  AND
                    [gid] = {gid}
                  AND
                    [pid] = {pid}
                  AND
                    [rank] >= {new_rank}
                  AND
                    [rank] < {old_rank}';

            $params             = array();
            $params['mid']      = $mid;
            $params['gid']      = $new_gid;
            $params['pid']      = $new_pid;
            $params['old_rank'] = $old_rank;
            $params['new_rank'] = $new_rank;
            $res = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
                return false;
            }
        }

        //$GLOBALS['app']->Session->PushLastResponse(_t('MENU_NOTICE_MENU_MOVED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * function for get menus tree
     *
     * @access  public
     * @return  array   Response (notice or error)
     */
    function GetParentMenus($pid, $gid, $excluded_mid, &$result, $menu_str = '')
    {
        $parents = $this->GetLevelsMenus($pid, $gid);
        if (empty($parents)) return false;
        foreach ($parents as $parent) {
            if ($parent['id'] == $excluded_mid) continue;
            $result[] = array('pid'=> $parent['id'],
                              'title'=> $menu_str . '\\' . $parent['title']);
            $this->GetParentMenus($parent['id'], $gid, $excluded_mid, $result, $menu_str . '\\' . $parent['title']);
        }
        return true;
    }
	
    /**
     * Updates a menu by URL
     *
     * @access  public
     * @param string $param An array in format: "URL,title,parent,type,visible"
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function UpdateMenuByURL($param)
    {		
		if (!empty($param['url']) && !empty($param['old_url']) && !empty($param['title']) && !empty($param['parent']) && !empty($param['type']) && !empty($param['visible'])) {

			$pid = 0;
			// get parent menus
			if ($param['parent'] != '0') {
				$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {parent}';
		        $parentMenu = $GLOBALS['db']->queryRow($sql, array('parent' => $param['parent']));
		        if (Jaws_Error::IsError($parentMenu)) {
		            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
		            return false;
		        } else {
					$pid = $parentMenu['id'];
				}
			}
			
			$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
	        $oid = $GLOBALS['db']->queryRow($sql, array('url' => $param['old_url']));
			//$GLOBALS['app']->Session->PushLastResponse('old: '.$param['old_url'].' new: '.$param['url'], RESPONSE_ERROR);
	        if (Jaws_Error::IsError($oid)) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
	            return false;
	        } else {
				if (!empty($oid['id'])) {
					if (!$this->UpdateMenu($oid['id'], $pid, 1, $param['type'], $param['title'], $param['url'], 0, $oid['rank'], $param['visible'])) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
		                return false;
		            } else {
						$GLOBALS['app']->Session->PushLastResponse('old: '.$param['old_url'].' new: '.$param['url'], RESPONSE_NOTICE);
					}
				} else {
		            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
		            return false;
				}
			}
        }
		return true;
    }
    /**
     * Deletes a menu by URL
     *
     * @access  public
     * @param string $param URL
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteMenuByURL($param)
    {
		if (!empty($param)) {
			$url = $param; //URL of item we are adding
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
			return false;
		}
		
		$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
        $oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
        if (Jaws_Error::IsError($oid)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        } else {
			if (!empty($oid['id'])) {
				if (!$this->DeleteMenu($oid['id'])) {
	                return false;
	            }
			} else {
				$GLOBALS['app']->Session->PushLastResponse('There is no menu item with URL: '.$param, RESPONSE_NOTICE);			
			}
		}

        return true;
    }
}
