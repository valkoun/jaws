<?php
/**
 * Languages Gadget
 *
 * @category   Gadget
 * @package    Languages
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class LanguagesHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Languages';
    /**
     * Constructor
     *
     * @access public
     */
    function LanguagesHTML()
    {
        $this->Init('Languages');
    }

    /**
     * Executes the default action, currently displaying the default page.
     *
     * @access public
     * @return string
     */
    function DefaultAction()
    {
        return $this->SaveUserLanguage;
    }

    /**
     * Visitors can save their Language preference.
     *
	 * @category   feature
     * @param   string  $lang_str   Language code and name
     * @access  public
     * @return  boolean Success/Failure (Jaws_Error)
     */
    function SaveUserLanguage()
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        $lang = $request->get('lang', 'get');
		$next = urldecode($_GET['next']);
		$GLOBALS['app']->Session->DeleteAttribute('hl-trans');
		//Jaws_Session_Web::DestroyCookie('hl-trans');
		
		if (!empty($lang)) {
			$GLOBALS['app']->Session->SetAttribute('hl-trans', $lang);
			$session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
            $params = array();
            $params['session_id'] = $session_id;
            $params['language'] = $lang;

            $sql = '
                UPDATE [[session]] SET
					[language] = {language}
				WHERE ([session_id] = {session_id})';

            $result = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('LANGUAGES_LANGUAGE_UPDATE_ERROR', 'selected'), RESPONSE_ERROR);
				return false;
            }
			//Jaws_Session_Web::SetCookie('hl-trans', $lang, 60*24*150);
			//if (Jaws_Session_Web::GetCookie('hl-trans') == $lang) {
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				$redirect = 'index.php';
				if (!empty($next)) {
					$redirect = $next;
				} else {
					if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
						$redirect = $_SERVER['HTTP_REFERER'];
					}
				}
				//echo Jaws_Session_Web::GetCookie('hl-trans');
				Jaws_Header::Redirect($redirect);
			//}
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('LANGUAGES_NAME_ERROR'), RESPONSE_ERROR);
			return false;
		}
    }

	
}
