<?php
/**
 * LDAP Authentication
 *
 * @category   Session
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('LDAP_SERVER', 'localhost');
define('LDAP_PORT',   '389');
define('LDAP_DN',     'dc=foobar,dc=org');

function LDAPAuthentication($user, $password)
{
    require_once JAWS_PATH . 'include/Jaws/User.php';
    $userModel = new Jaws_User();
    $ldapuser = $userModel->GetUserInfoByName($user);
    if (Jaws_Error::IsError($ldapuser)) {
        return $ldapuser;
    } elseif (!isset($ldapuser['username'])) {
        return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_WRONG'));
    } elseif (!$ldapuser['enabled']) {
        return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_DISABLED'));
    } elseif (!function_exists('ldap_connect')) {
        return new Jaws_Error(_t('GLOBAL_ERROR_FUNCTION_DOES_NOT_EXIST', 'ldap_connect'));
    }

    $ldapconn = @ldap_connect(LDAP_SERVER, LDAP_PORT);
    if ($ldapconn) {
        //the params of ldapbind are: resource of ldap_connect, RDN and password
        $rdn = "uid=" . $user . "," . LDAP_DN;
        $bind = @ldap_bind($ldapconn, $rdn, $password);
        if ($bind) {
            return true;
        }
    }

    return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_WRONG'));
}
