<?php
/**
 * Plugin for limiting access to content or part of content for users or groups
 *
 * @category   Plugin
 * @package    AccessLimiter
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2009-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'include/Jaws/Plugin.php';

class AccessLimiter extends Jaws_Plugin
{
    /**
     * Main Constructor
     *
     * @access  public
     */
    function AccessLimiter()
    {
        $this->_Name = 'AccessLimiter';
        $this->LoadTranslation();
        $this->_Description = _t('PLUGINS_ACCESSLIMITER_DESCRIPTION');
        $this->_Example  = '[limited]your content[/limited]<br />'.
                           '[limited users="afz,ion"]your content[/limited]<br />'.
                           '[limited groups="admins"]your content[/limited]<br />'.
                           '[limited users="afz" groups="admins"]your content[/limited]';
        $this->_IsFriendly = false;
        $this->_Version = '0.1';
    }

    /**
     * Overrides, Parse the text
     *
     * @access  public
     * @param   string  $html Html to Parse
     * @return  string  Parsed HTML
     */
    function ParseText($html)
    {
        $blockPattern = '@\[limited\s*(users="(.*?)")?\s*(groups="(.*?)")?\](.*?)\[/limited\]@ism';
        $new_html = preg_replace_callback($blockPattern, array(&$this, 'Prepare'), $html);
        return $new_html;
    }

    /**
     * The preg_replace call back function
     *
     * @access  private
     * @param   string  $matches Matched strings from preg_replace_callback
     * @return  string  Gadget's action output or access message
     */
    function Prepare($data)
    {
        $users  = $data[2];
        $groups = $data[4];
        $content= &$data[5];

        if ($GLOBALS['app']->Session->Logged()) {
            if (!$GLOBALS['app']->Session->IsSuperAdmin()) {
                $users  = empty($users) ? array() : array_map('trim', explode(',', $users));
                $groups = empty($groups)? array() : array_map('trim', explode(',', $groups));

                static $user_groups;
                $user = $GLOBALS['app']->Session->GetAttribute('username');
                if (!isset($user_groups)) {
                    $user_groups = $GLOBALS['app']->Session->GetAttribute('groups');
                    $user_groups = array_map(create_function('$row','return $row["name"];'), $user_groups);
                }

                if (!empty($users) || !empty($groups)) {
                    if ((empty($users)  || !in_array($user, $users)) &&
                        (empty($groups) || !count(array_intersect($groups, $user_groups))))
                    {
                        return  _t('GLOBAL_ERROR_ACCESS_DENIED');
                    }
                }
            }

            return $content;
        }

        $login_url    = $GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm');
        $register_url = $GLOBALS['app']->Map->GetURLFor('Users', 'Registration');
        return _t('GLOBAL_ERROR_ACCESS_RESTRICTED', $login_url, $register_url);
    }

}