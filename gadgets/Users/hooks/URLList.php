<?php
/**
 * Users - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Users
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UsersURLListHook
{
    /**
     * Returns an array with all available items the Menu gadget 
     * can use
     *
     * @access  public
     */
    function Hook()
    {
        $urls   = array();
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm'),
                        'title' => _t('USERS_LOGIN_TITLE'));
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'),
                        'title' => _t('USERS_USERLINK_LOGIN'));
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Users', 'Logout'),
                        'title' => _t('USERS_USERLINK_LOGOUT'));
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Users', 'Registration'),
                        'title' => _t('USERS_USERLINK_REGISTER'));
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Users', 'Account'),
                        'title' => _t('USERS_USERLINK_EDITPROFILE'));
        return $urls;
    }
		
    /**
     * Returns an array with all Quick Add Forms of Gadget 
     * can use
     *
     * @access  public
     */
    /*
	function GetQuickAddForms()
    {
		$GLOBALS['app']->Registry->LoadFile('Users');
		$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
        $result   = array();
        $result[] = array('name'   => _t('USERS_QUICKADD_ADDGROUP'),
                        'method' => 'AddGroup');
		//$result[] = array('name'   => _t('USERS_QUICKADD_ADDPOST'),
        //                'method' => 'AddPost');
        return $result;
    }
	*/
}
