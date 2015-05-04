<?php
/**
 * Users - Search gadget hook
 *
 * @category   GadgetHook
 * @package    Users
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class UsersSearchHook extends Jaws_Model
{
    /**
     * Gets the gadget's search fields
     */
    function GetSearchFields() {
        return array(
                    array(
						'[[users]].[nickname]',
						'[[users]].[username]',
						'[[users]].[company]',
						'[[users]].[url]',
						'[[users]].[city]',
						'[[users]].[region]',
						'[[users]].[company_type]'),
                    array(
						'[[groups]].[name]', 
						'[[groups]].[title]')
			);
    }

    /**
     * Returns an array with the results of a search
     *
     * @access  public
     * @param   string  $pSql  Prepared search (WHERE) SQL
     * @return  array   An array of entries that matches a certain pattern
     */
    function Hook($pSql = '', $limit = null)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        $date  = $GLOBALS['app']->loadDate();
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		
		$pages = array();
		
		$params = array();
		$params['enabled'] = true;
		$params['active'] = 'active';
		$params['founder'] = 'founder';
		$params['admin'] = 'admin';

        // Users
        $sql = '
            SELECT
                [id], [username], [nickname], [email], [createtime], [updatetime], [last_login], [user_type],
                [language], [theme], [editor], [timezone], [enabled], [closetime], [company], [url],
				[address], [address2], [city], [region], [postal], [country], [description], [office],
				[tollfree], [phone], [fax], [merchant_id], [keywords], [image], [logo], [notification], 
				[allow_comments], [identifier], [[users]].[checksum], [passwd], [company_type]
            FROM [[users]]
            WHERE [[users]].[user_type] > 1 AND [[users]].[enabled] = {enabled} AND ';
				
		/*
		$groupInfo = $jUser->GetGroupInfoByName('profile');
		if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
			$params['profile'] = $groupInfo['id'];
			$sql .= ' AND (([[users_groups]].[group_id] = {no_profile}';
			$groupInfo2 = $jUser->GetGroupInfoByName('no_profile');
			if (!Jaws_Error::IsError($groupInfo2) && isset($groupInfo2['id']) && !empty($groupInfo2['id'])) {
				$params['no_profile'] = $groupInfo2['id'];
				$sql .= ' OR [[users_groups]].[group_id] = {profile}';
			}
			$sql .= ') AND ([[users_groups]].[status] = {active} OR [[users_groups]].[status] = {founder} OR [[users_groups]].[status] = {admin})) AND ';
		}
		*/
		
        $sql .= isset($pSql[0])? $pSql[0] : '';

        $types = array(
			'integer', 'text', 'text', 'text', 'timestamp', 'timestamp', 'timestamp', 'integer', 
			'text', 'text', 'text', 'text', 'boolean', 'timestamp', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'boolean', 'text', 'text', 'text', 'text', 'integer'
		);

        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return array();
			}
		}
		
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //echo $result->getMessage();
            return array();
        }

        foreach ($result as $p) {
			$no_profile = $jUser->GetStatusOfUserInGroup($p['id'], 'no_profile');
			$profile = $jUser->GetStatusOfUserInGroup($p['id'], 'profile');
			if (in_array($profile, array('active','admin','founder')) || in_array($no_profile, array('active','admin','founder'))) {
				$url = $GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=UserDirectory&Users_q='.$p['username'];
				if (in_array($profile, array('active','admin','founder'))) {
					$url = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $p['username']));
				}
				
				$page = array();
				$page['title'] = (!empty($p['company']) ? $p['company'] : $p['nickname']);
				$page['url']     = $url;
				$image = $jUser->GetAvatar($p['username'], $p['email']);
				if (empty($image)) {
					require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
					$image = Jaws_Gravatar::GetGravatar('');
				}
				//$page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/logo.png';
				$page['image']   = $image;
				if (!empty($p['description'])) {
					$page['snippet'] = (strlen(strip_tags($p['description'])) > 247 ? substr(strip_tags($p['description']),0,247).'...' : strip_tags($p['description']));
				} else {
					$group_snippet = '';
					$groups = $jUser->GetGroupsOfUser($p['id'], array('core'));
					if (!Jaws_Error::IsError($groups)) {
						foreach ($groups as $group) {
							$group_snippet .= (empty($group_snippet) ? 'A member of ' : ', ').(!empty($group['group_title']) ? $group['group_title'] : $group['group_name']);
						}
					}
					$company_snippet = (!empty($group_snippet) ? " " : '').$p['company_type'];
					$page['snippet'] = $group_snippet.$company_snippet;
					if (empty($page['snippet'])) {
						$page['snippet'] = 'A member of '.(!empty($site_name) ? $site_name : $GLOBALS['app']->GetSiteURL());
					}
				}
				$page['date']    = $date->ToISO($p['createtime']);
				$stamp           = str_replace(array('-', ':', ' '), '', $p['updatetime']);
				$pages[$stamp]   = $page;
			}
        }
	
        // Groups
		$params2 = array();
		$params2['no_profile'] = 'no_profile';
		$params2['profile'] = 'profile';
		$params2['users'] = 'users';
		$params2['gadget_users'] = '%_users';
		$params2['gadget_owners'] = '%_owners';
		
        $sql2 = '
            SELECT
                [id], [name], [title], [description], [removable], [checksum]
            FROM [[groups]]
            WHERE 
				[name] != {no_profile} AND [name] != {profile} AND [name] != {users} AND NOT 
				[name] LIKE {gadget_users} AND NOT [name] LIKE {gadget_owners} AND';

        $sql2 .= isset($pSql[1])? $pSql[1] : '';
        
		$types2 = array(
			'integer', 'text', 'text', 'text', 'boolean', 'text'
		);
       
        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return array();
			}
		}
		
		$result2 = $GLOBALS['db']->queryAll($sql2, $params2, $types2);
        if (Jaws_Error::IsError($result2)) {
            //echo $result->getMessage();
            return array();
        }

        foreach ($result2 as $p) {
            $page = array();
            $page['title'] = (!empty($p['title']) ? $p['title'] : $p['name']);
            $page['url']     = $GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $p['name']));
			$image = $jUser->GetGroupAvatar($p['id']);
			if (empty($image)) {
				require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
				$image = Jaws_Gravatar::GetGravatar('');
			}
			//$page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/Groups.png';
			$page['image']   = $image;
			$ugroups = $jUser->GetUsersOfGroup($p['id']);
			$page['snippet'] = "A ".(!empty($site_name) ? $site_name : $GLOBALS['app']->GetSiteURL())." group";
			if (!Jaws_Error::isError($ugroups)) {
				$groupCount = count($ugroups);
				if (!$groupCount <= 0) {
					$page['snippet'] = "A group with ".$groupCount." member".($groupCount > 1 ? 's' : '');
				}
            }
			//$page['date']    = $date->ToISO($p['updated']);
            //$stamp           = str_replace(array('-', ':', ' '), '', $p['updated']);
			$page['date']    = '';
            $stamp           = '';
            $pages[$stamp]   = $page;
        }
		
        return $pages;
    }
}
