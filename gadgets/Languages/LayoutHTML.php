<?php
/**
 * Languages Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    Languages
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class LanguagesLayoutHTML extends Jaws_GadgetHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions()
    {
        $actions = array();
		$actions['Display'] = array(
			'mode' => 'LayoutAction', 
			'name' => _t('LANGUAGES_TITLE_DISPLAY'), 
			'desc' => _t('LANGUAGES_DESCRIPTION_DISPLAY')
		);
        return $actions;
    }

	/**
     * Display Language selection for visitors.
     *
     * @access public
     * @return string
     */
    function Display()
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Languages', 'Model');
		$tpl = new Jaws_Template('gadgets/Languages/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$page_content = '<div class="gadget menu" id="gmenu_language">
		<div class="content">
		<ul>
		<li id="menu_language" class="menu_li_on"><a href="javascript:void(0);" target="_self" class="menu_a_on">Languages</a>
		<ul class="ul_sub_menu">';
		$languages = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/language_visitor_choices'));
		foreach ($languages as $lang) {
			$lang = explode(':', $lang);
			$next = urlencode($_SERVER['FULL_URL']);
			$next = str_replace('.', '%2e', $next);
			$page_content .= '<li id="menu_'.$lang[1].'" class="menu_li"><a href="index.php?gadget=Languages&action=SaveUserLanguage&lang='.$lang[1].'&next='.$next.'&t='.time().'" target="_self" class="menu_a">'.$lang[0].'</a></li>
			<script type="text/javascript">
				// <![CDATA[
				checkSubMenus(document.getElementById(\'menu_'.$lang[1].'\'));
				// ]]>
			</script>';
		}
		$page_content .= '</ul>
		</li>
		<script type="text/javascript">
			// <![CDATA[
			checkSubMenus(document.getElementById(\'menu_language\'));
			// ]]>
		</script>
		</ul>
		</div>
		</div>';

		$tpl->SetVariable('content', $page_content);
		$tpl->ParseBlock('layout');

		return $tpl->Get();
	}
		
}