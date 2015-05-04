<?php
/**
 * Preferences Gadget
 *
 * @category   Gadget
 * @package    Preferences
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class PreferencesHTML extends Jaws_GadgetHTML
{
    /**
     * Main Constructor
     *
     * @access      public
     */
    function PreferencesHTML()
    {
        $this->Init('Preferences');
    }

    /**
     * Default Action
     *
     * @access      public
     * @return      string   HTML content of DefaultAction
     */
    function DefaultAction()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('Preferences', 'LayoutHTML');
        return $layoutGadget->Display();
    }

    /**
     * Save the cookie, save the world
     *
     * @access      public
     */
    function Save()
    {
        $model = $GLOBALS['app']->LoadGadget('Preferences', 'Model');

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('theme', 'editor', 'language', 'calendar_type', 'calendar_language',
                                    'date_format', 'timezone'), 'post');
        $expire_age = 365*24*60; //don't expired for 1 year
        $model->SavePreferences($post, $expire_age);

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Referrer();
    }

    /**
     * Set site language by cookie
     *
     * @access      public
     */
    function SetLanguage()
    {
        $request =& Jaws_Request::getInstance();
        $language = $request->get('lang', 'get');

        $model = $GLOBALS['app']->LoadGadget('Preferences', 'Model');
        $expire_age = 365*24*60; //don't expired for 1 year
        $model->SavePreferences(array('language' => $language), $expire_age);

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Referrer();
    }

}