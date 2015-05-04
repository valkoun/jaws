<?php
/**
 * Default auth function ;-)
 *
 * @category   Session
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
function DefaultAuthentication($user, $password, $onlyAdmins)
{
    require_once JAWS_PATH . 'include/Jaws/User.php';
    $userModel = new Jaws_User();
    return $userModel->Valid($user, $password, $onlyAdmins);
}
