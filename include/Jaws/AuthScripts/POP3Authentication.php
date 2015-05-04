<?php
/**
 * POP3 Authentication
 *
 * @category   Session
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('POP3_SERVER', 'localhost');
define('POP3_PORT',   '110');

function POP3Authentication($user, $password, $onlyAdmins = false)
{
    require_once JAWS_PATH . 'include/Jaws/User.php';
    $userModel = new Jaws_User();
    $pop3user = $userModel->GetUserInfoByName($user);
    if (Jaws_Error::IsError($pop3user)) {
        return $pop3user;
    } elseif (!isset($pop3user['username'])) {
        return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_WRONG'));
    } elseif (!$pop3user['enabled']) {
        return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_DISABLED'));
    } elseif (!function_exists('imap_open')) {
        return new Jaws_Error(_t('GLOBAL_ERROR_FUNCTION_DOES_NOT_EXIST', 'imap_open'));
    }

    $mbox = @imap_open('{'.POP3_SERVER.'/pop3:'.POP3_PORT.'/notls}INBOX', $user, $password);
    if ($mbox) {
        @imap_close($mbox);
        return true;
    }

    return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_WRONG'));
}
