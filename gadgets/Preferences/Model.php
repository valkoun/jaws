<?php
/**
 * Preferences Gadget Model
 *
 * @category   GadgetModel
 * @package    Preferences
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class PreferencesModel extends Jaws_Model
{
    /**
     * Save the cookie, save the world
     *
     * @access  public
     * @param   array   $Preferences
     * @param   int     $expiretime
     * @return  boolean True/False
     */
    function SavePreferences($Preferences, $expire_age = 86400)
    {
        foreach ($Preferences as $key => $value) {
            if ($value == 'false') {
                Jaws_Session_Web::DestroyCookie($key);
            } else {
                Jaws_Session_Web::SetCookie($key, $value, $expire_age);
            }
        }
        return true;
    }
}