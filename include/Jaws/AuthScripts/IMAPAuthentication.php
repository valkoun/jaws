<?php
/**
 * IMAP Authentication
 *
 * @category   Session
 * @package    Core
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('IMAP_SERVER',   'localhost');
define('SSL_ACTIVATED', false);
define('IMAP_PORT',     '143');

function IMAPAuthentication($user, $password, $onlyAdmins = false)
{
    require_once JAWS_PATH . 'include/Jaws/User.php';
    $userModel = new Jaws_User();
    $imapuser = $userModel->GetUserInfoByName($user);
    if (Jaws_Error::IsError($imapuser)) {
        return $imapuser;
    } elseif (!isset($imapuser['username'])) {
        return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_WRONG'));
    } elseif (!$imapuser['enabled']) {
        return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_DISABLED'));
    } elseif (!function_exists('imap_open')) {
        return new Jaws_Error(_t('GLOBAL_ERROR_FUNCTION_DOES_NOT_EXIST', 'imap_open'));
    }

    $mbox = @imap_open('{'.IMAP_SERVER.':'.IMAP_PORT.(SSL_ACTIVATED?'/imap/ssl':'').'}INBOX', $user, $password);
    if ($mbox) {
        @imap_close($mbox);
        return true;
    }

    return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_WRONG'));
}
