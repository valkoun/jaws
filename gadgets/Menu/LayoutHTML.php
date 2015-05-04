<?php
/**
 * Menu Layout HTML file (for layout purposes)
 *
 * @category   GadgetLayout
 * @package    Menu
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class MenuLayoutHTML
{
    /**
     * Request URL
     *
     * @access private
     */
    var $_ReqURL = '';

    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions()
    {
        $model = $GLOBALS['app']->LoadGadget('Menu', 'Model');
        $groups = $model->GetGroups();

        $actions = array();
        if (!Jaws_Error::isError($groups)) {
            foreach ($groups as $group) {
                $actions['Display(' . $group['id'] . ')'] = array(
                    'mode' => 'LayoutAction',
                    'name' => $group['title'],
                    'desc' => _t('MENU_LAYOUT_DISPLAY_DESCRIPTION')
                );
            }
        }

        return $actions;
    }

    /**
     * Displays the menus with their items
     *
     * @access  public
     * @return  string HTML content with menu and menu items
     */
    function Display($gid = 0)
    {
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Menu/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/opentip/opentip.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');			
		$GLOBALS['app']->Layout->AddScriptLink('libraries/opentip/opentip.js');			
		$GLOBALS['app']->Layout->AddScriptLink('libraries/opentip/excanvas.js');			
		$model = $GLOBALS['app']->LoadGadget('Menu', 'Model');
        $group = $model->GetGroups($gid);
        if (Jaws_Error::IsError($group) || empty($group) || $group['visible'] == 0) {
            return false;
        }

        $this->_ReqURL = Jaws_Utils::getRequestURL(BASE_SCRIPT);
        $this->_ReqURL = str_replace(BASE_SCRIPT, '', $this->_ReqURL);

        $tpl = new Jaws_Template('gadgets/Menu/templates/');
        $tpl->Load('Menu.html', true);
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl->SetBlock('levels');

        $tpl_str = $tpl->GetRawBlockContent();

        $tpl->SetBlock('menu');
        $tpl->SetVariable('gid', $group['id']);
        $tpl->SetVariable('menus_tree', $this->GetNextLevel($model, $tpl_str, $group['id'], 0));
        if ($group['title_view'] == 1) {
            $tpl->SetBlock("menu/group_title");
			$tpl->SetVariable('actionName', 'Display_'.$group['id'].'_');
            $tpl->SetVariable('title', $group['title']);
            $tpl->ParseBlock("menu/group_title");
        }

        $tpl->ParseBlock('menu');
        return $tpl->Get();
    }

    /**
     * Displays the next level of parent menu
     *
     * @access  public
     * @return  string HTML content with sub menu items
     */
    function GetNextLevel(&$model, &$tpl_str, $gid, $pid)
    {
        $menus = $model->GetLevelsMenus($pid, $gid, true);
        if ((strlen((string)$pid) > 6 && substr((string)$pid, 0, 6) == 'custom') || Jaws_Error::IsError($menus) || empty($menus)) return '';

        $tpl = new Jaws_Template();
        $tpl->LoadFromString($tpl_str, false);
        $tpl->SetBlock('levels');

		$ulclass = ($pid > 0 ? " class=\"ul_sub_menu\" style=\"display: none;\"" : " class=\"ul_top_menu\"");
		$tpl->SetVariable('ul_class', $ulclass);
        //$scripts = '';
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onBeforeLoadMenus', array('gid' => $gid, 'pid' => $pid, 'menus' => $menus));
		if (Jaws_Error::IsError($res) || !$res) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_ERR, 'Error: '.(Jaws_Error::IsError($res) ? $res->GetMessage() : "Could not call onBeforeLoadMenus event shouter."));
            }
            return false;
		} else if (isset($res['menus']) && !count($res['menus']) <= 0) {
			$menus = $res['menus'];
		}

        $len = count($menus);
        for ($i = 0; $i < $len; $i++) {
            $tpl->SetBlock('levels/menu_item');
            $tpl->SetVariable('mid', $menus[$i]['id']);
            $tpl->SetVariable('title', $menus[$i]['title']);
            $tpl->SetVariable('url', $menus[$i]['url']);
            $tpl->SetVariable('target', ($menus[$i]['url_target']==0)? '_self': '_blank');

            //menu selected?
			$selected = str_replace(BASE_SCRIPT, '', $menus[$i]['url']) == $this->_ReqURL;
			$aclass = " class=\"".($pid > 0 ? 'sub_menu_a' : 'menu_a')."\"";
			if ($selected) {
				$aclass = " class=\"menu_a_on\"";
			}
			$tpl->SetVariable('a_class', $aclass);            
            //get sub level menus
            $subLevel = $this->GetNextLevel($model, $tpl_str, $gid, $menus[$i]['id']);
            $tpl->SetVariable('sub_menu', $subLevel);
			$tpl->SetBlock('levels/menu_item/class');
            if ($selected || !empty($subLevel) || $i == 0 || $i == $len - 1) {
                if ($i == 0) {
                    $tpl->SetBlock('levels/menu_item/class/first');
                    $tpl->ParseBlock('levels/menu_item/class/first');
                }
                if ($i == $len - 1) {
                    $tpl->SetBlock('levels/menu_item/class/last');
                    $tpl->ParseBlock('levels/menu_item/class/last');
                }
                if ($selected) {
                    $tpl->SetBlock('levels/menu_item/class/current');
                    $tpl->ParseBlock('levels/menu_item/class/current');
                }
                if (!empty($subLevel)) {
                    $tpl->SetBlock('levels/menu_item/class/super');
                    $tpl->ParseBlock('levels/menu_item/class/super');
                }
            }
			$tpl->ParseBlock('levels/menu_item/class');


            $tpl->ParseBlock('levels/menu_item');
        }

        $tpl->ParseBlock('levels');
        return $tpl->Get();
    }
}
