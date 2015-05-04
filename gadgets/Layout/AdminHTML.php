<?php
/**
 * Layout Core Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Layout
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class LayoutAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access public
     */
    function LayoutAdminHTML()
    {
        $this->Init('Layout');
    }

    /**
     * Returns the HTML content to manage the layout in the browser
     *
     * @access  public
     * @return  string  HTML conent of Layout Management
     */
    function Admin()
    {
        return $this->LayoutManager();
    }

    function LayoutManager()
    {
        $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');

        $t_item = new Jaws_Template('gadgets/Layout/templates/');
        $t_item->Load('LayoutManager.html');

        $t_item->SetBlock('working_notification');
        $t_item->SetVariable('loading-message', _t('GLOBAL_LOADING'));
        $working_box = $t_item->ParseBlock('working_notification');
        $t_item->Blocks['working_notification']->Parsed = '';

        $t_item->SetBlock('msgbox-wrapper');
        $responses = $GLOBALS['app']->Session->PopLastResponse();
        if ($responses) {
            foreach ($responses as $msg_id => $response) {
                $t_item->SetBlock('msgbox-wrapper/msgbox');
                $t_item->SetVariable('msg-css', $response['css']);
                $t_item->SetVariable('msg-txt', $response['message']);
                $t_item->SetVariable('msg-id', $msg_id);
                $t_item->ParseBlock('msgbox-wrapper/msgbox');
            }
        }
        $msg_box = $t_item->ParseBlock('msgbox-wrapper');
        $t_item->Blocks['msgbox-wrapper']->Parsed = '';

        $t_item->SetBlock('drag_drop');
        $t_item->SetVariable('empty_section',    _t('LAYOUT_SECTION_EMPTY'));
        $t_item->SetVariable('display_always',   _t('LAYOUT_ALWAYS'));
        $t_item->SetVariable('display_never',    _t('LAYOUT_NEVER'));
        $t_item->SetVariable('displayWhenTitle', _t('LAYOUT_CHANGE_DW'));
        $t_item->SetVariable('actionsTitle',     _t('LAYOUT_ACTIONS'));
        $dragdrop = $t_item->ParseBlock('drag_drop');
        $t_item->Blocks['drag_drop']->Parsed = '';

		$theme = $GLOBALS['app']->GetTheme();
		$GLOBALS['app']->Layout = new Jaws_Layout(false);
        $GLOBALS['app']->Layout->Load(true, $theme['path'], (file_exists($theme['path'] . 'layout_manager.html') ? 'layout_manager.html' : null));
        $layoutContent = $GLOBALS['app']->Layout->_Template->Blocks['layout']->Content;
        $useLayoutMode = $GLOBALS['app']->Layout->_Template->VariableExists('layout-mode');
        
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
        $site_slogan = $GLOBALS['app']->Registry->Get('/config/site_slogan');
        
        $layoutContent = preg_replace(
                            '$<body([^>]*)>$i',
                            '<body\1>' . $working_box . $msg_box . $this->getLayoutControls($useLayoutMode),
                            $layoutContent);
        $layoutContent = preg_replace('$</body([^>]*)>$i', $dragdrop . '</body\1>', $layoutContent);
        $GLOBALS['app']->Layout->_Template->Blocks['layout']->Content = $layoutContent;

        $GLOBALS['app']->Layout->_Template->SetVariable('site-title', $GLOBALS['app']->Registry->Get('/config/site_name'));
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Layout&amp;action=Ajax&amp;client=all&amp;stub=LayoutAdminAjax');
        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Layout&amp;action=AjaxCommonFiles');
        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Layout/resources/script.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        
        $GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
        
        $GLOBALS['app']->Layout->AddHeadLink(PIWI_URL . 'piwidata/css/default.css', 'stylesheet', 'text/css', 'default');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Layout/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');

        $GLOBALS['app']->Layout->addHeadOther(
                    '<!--[if lt IE 7]>'."\n".
                    '<script src="'. $GLOBALS['app']->GetJawsURL() . '/gadgets/ControlPanel/resources/ie-bug-fix.js" type="text/javascript"></script>'."\n".
                    '<![endif]-->');

        $GLOBALS['app']->Layout->addHeadOther(
			'<script type="text/javascript">
				var innerHeights = 0;
				Event.observe(window, "load", function(){
					/*
					$$(".item").each(function(element){
						innerHeights = 0;
						$(element).descendants().each(function(descendant){
							if ($(descendant).nodeName != "SCRIPT" && $(descendant).nodeName != "STYLE") {
								innerHeights = ($(descendant).getHeight() > innerHeights ? $(descendant).getHeight() : innerHeights);
							}
						});
						$(element).setStyle({minHeight: innerHeights+"px"}); 
					});
					*/
					$$(".item-controls").each(function(element){
						var leftPos = (($(element).next().positionedOffset().left-$(element).up(".item").up().positionedOffset().left)+$(element).next().getWidth());
						if ($$(".ym-wrapper")[0]) {
							leftPos = (leftPos > $$(".ym-wrapper")[0].getWidth() ? ($$(".ym-wrapper")[0].getWidth()-$(element).up(".item").up().positionedOffset().left) : leftPos);
						}
						$(element).setStyle({
							left: (leftPos-59)+"px", 
							right: "auto"
						});
					});
					$$(".item-nodelete").each(function(element){
						$(element).up(".item-controls").setStyle({minWidth: "18px", width: "18px", paddingLeft: "10px"});
					});
					$$(".temp-post-button").each(function(element){
						var parent = $(element).up(".custom_page-post-text");
						if (parent.getStyle("minHeight") == "0px" || parent.getStyle("minHeight") == "auto") {
							parent.setStyle({minHeight: $(element).getHeight()+"px"});
						}
						/*
						$(element).up(".item").observe("mouseover", function(){
							$(element).setStyle({visibility: "visible"});
						});
						$(element).up(".item").observe("mouseout", function(){
							$(element).setStyle({visibility: "hidden"});
						});
						*/
					});
				});
			</script>'."\n"
		);

		$head_scripts = '';
		$head_scripts_sections = '';
		$head_scripts_editable = '';
		$head_items = array();
		$baseToolbar = array(
			'bold,italic,strikethrough,underline,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist',
			'code,|,undo,redo,|,image,example,unlink,|'
		);
		$inplace_options = "{ tinymceToElementSize: true }";
        foreach ($GLOBALS['app']->Layout->_Template->Blocks['layout']->InnerBlock as $name => $data) {
            if ($name == 'head') continue;
            $GLOBALS['app']->Layout->_Template->SetBlock('layout/'.$name);
			$head_items[$name_prefix.$name] = '';
            $head_scripts_sections .= (!empty($head_scripts_sections) ? ',' : '').'\''.$name_prefix.$name.'\'';
            $gadgets = $model->GetGadgetsInSection($name);
            if (!is_array($gadgets)) continue;
			$highest_pos = 0;
			$section_empty = true;
            foreach ($gadgets as $gadget) {
                $id = $gadget['id'];
                if (file_exists(JAWS_PATH . 'gadgets/'. $gadget['gadget']. '/'. 'LayoutHTML.php') ||
                     file_exists(JAWS_PATH . 'gadgets/'. $gadget['gadget']. '/'. 'Actions.php') ||
                    ($gadget['gadget'] == '[REQUESTEDGADGET]'))
                {
                    if (($GLOBALS['app']->Registry->Get('/gadgets/'.$gadget['gadget'].'/enabled') == 'true') ||
                        ($gadget['gadget'] == '[REQUESTEDGADGET]'))
                    {
                        if ($gadget['gadget'] == '[REQUESTEDGADGET]') {
                            $section_empty = false;
                            $t_item->SetBlock('item');
                            $t_item->SetVariable('item_id', $id);
                            $t_item->SetVariable('description', _t('LAYOUT_REQUESTED_GADGET_DESC'));
                            $t_item->SetVariable('item_status', 'none');
                            $t_item->SetVariable('section_id', $name);
							$t_item->SetBlock('item/requested');
                            $t_item->SetVariable('item_id', $id);
                            $t_item->SetVariable('pos', $gadget['layout_position']);
                            $t_item->SetVariable('gadget', _t('LAYOUT_REQUESTED_GADGET'));
                            $t_item->SetVariable('action', _t('LAYOUT_REQUESTED_GADGET_DESC'));
                            $t_item->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/requested-gadget.png');
                            $t_item->SetVariable('lbl_display_when', _t('LAYOUT_DISPLAY_IN'));
                            $t_item->SetVariable('display_when', _t('GLOBAL_ALWAYS'));
                            $t_item->SetVariable('void_link', 'return;');
                            $t_item->SetVariable('section_name', $name);
                            $t_item->SetVariable('delete', 'void(0);');
                            $t_item->SetVariable('delete-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/no-delete.gif');
                            $t_item->ParseBlock('item/requested');
                            $t_item->ParseBlock('item');
                        } else {
                            if (Jaws_Gadget::IsGadgetUpdated($gadget['gadget'])) {
                                $section_empty = false;
                                $controls = '';
                                $actions = $GLOBALS['app']->GetGadgetActions($gadget['gadget']);
                                $actions = (isset($actions['LayoutAction'])) ? $actions['LayoutAction'] : array();
								$item_gadget = '';
								$item_action = '';
								if (isset($actions)) {
                                    $info = $GLOBALS['app']->LoadGadget($gadget['gadget'], 'Info');
                                    $item_gadget = $info->GetName();
                                    if (isset($actions[$gadget['gadget_action']]['name'])) {
                                        $item_action = $actions[$gadget['gadget_action']]['name'];
                                    } else {
                                        $layoutGadget = $GLOBALS['app']->LoadGadget($gadget['gadget'], 'LayoutHTML');
                                        if (method_exists($layoutGadget, 'LoadLayoutActions')) {
                                            $actions = $layoutGadget->LoadLayoutActions();
                                            if (isset($actions[$gadget['gadget_action']]['name'])) {
                                                $item_action = $actions[$gadget['gadget_action']]['name'];
                                            } else {
                                                $item_action = $gadget['gadget_action'];
                                            }
                                        } else {
                                            $item_action = $gadget['gadget_action'];
                                        }
                                        unset($layoutGadget);
                                    }
                                    unset($info);
                                } else {
                                    $item_gadget = $gadget['gadget'];
                                    $item_action = _t('LAYOUT_ACTIONS');
                                }
                                $t_item->SetBlock('item');
                                if (isset($actions[$gadget['gadget_action']])) {
                                    $t_item->SetVariable('description', $actions[$gadget['gadget_action']]['desc']);
                                    $t_item->SetVariable('item_status', 'none');
                                } else {
                                    $t_item->SetVariable('description', $gadget['gadget_action']);
                                    $t_item->SetVariable('item_status', 'line-through');
                                }
								$t_item->SetVariable('item_id', $id);
                                $t_item->SetVariable('section_id', $name);
                                $t_item->SetBlock('item/gadget');
								$t_item->SetVariable('action', $item_action);
                                $delete_url = "javascript: deleteElement('".$gadget['id']."','"._t('LAYOUT_CONFIRM_DELETE')."');";

                                $t_item->SetVariable('pos', $gadget['layout_position']);
                                $t_item->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget['gadget'].'/images/logo.png');
                                $t_item->SetVariable('delete', 'deleteElement(\''.$gadget['id'].'\',\''._t('LAYOUT_CONFIRM_DELETE').'\');');
                                $t_item->SetVariable('delete-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/delete-item.gif');
                                unset($actions);

                                $t_item->SetVariable('controls', $controls);
                                $t_item->SetVariable('void_link', '');
                                $t_item->SetVariable('lbl_display_when', _t('LAYOUT_DISPLAY_IN'));
								if (
									strpos($gadget['display_when'], ',') !== false || 
									substr($gadget['display_when'], 0, 8) == '{GADGET:' || 
									substr($gadget['display_when'], 0, 12) == '{HIDEGADGET:'
								) {
									if (strpos($gadget['display_when'], ',') !== false) {
										$dw_value = explode(',', $gadget['display_when']);
										$d = 0;
										foreach ($dw_value as $dwv) {
											if (substr($dwv, 0, 8) == '{GADGET:') {
												unset($dw_value[$d]);
											} else if (substr($dwv, 0, 12) == '{HIDEGADGET:') {
												unset($dw_value[$d]);
											}
											$d++;
										}
										$gadget['display_when'] = implode(',', $dw_value);
									} else {
										$gadget['display_when'] = '';
									}
									if (empty($gadget['display_when'])) {
										continue;
									}
								}
                                if ($gadget['display_when'] == '*') {
                                    $t_item->SetVariable('display_when', _t('GLOBAL_ALWAYS'));
                                } elseif (empty($gadget['display_when'])) {
                                    $t_item->SetVariable('display_when', _t('LAYOUT_NEVER'));
                                } else {
                                    $t_item->SetVariable('display_when', str_replace(',', ', ', $gadget['display_when']));
                                }
								$content = $GLOBALS['app']->Layout->PutGadget($gadget['gadget'], $gadget['gadget_action'], $gadget['section']);
								$head_items[$name] .= (!empty($head_items[$name]) ? ',' : '').'"item_'.$id.'":true';
								$edit_action = (strpos($gadget['gadget_action'], '(') !== false ? substr($gadget['gadget_action'], 0, strpos($gadget['gadget_action'], '(')) : $gadget['gadget_action']);
								$edit_id = (strpos($gadget['gadget_action'], '(') !== false ? str_replace(array('(',')'), '', substr($gadget['gadget_action'], strpos($gadget['gadget_action'], '('), strlen($gadget['gadget_action']))) : null);
								$editImg = $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/gadget-item.gif';
								if ($edit_action == 'ShowPost') {
									$editor =& $GLOBALS['app']->LoadEditor('CustomPage', 'custom_page-post-text-'.$edit_id, $content, false, '', true, $GLOBALS['app']->GetSiteURL().'/admin.php?gadget=CustomPage&action=SaveEditPost&id='.$edit_id, $inplace_options);
									$editor->SetBaseToolbar($baseToolbar);
									//$head_scripts_editable = '<script type="text/javascript">Event.observe(window, "load", function(){if($(\'item_'.$id.'\')){$$(\'#item_'.$id.' .custom_page-post-text\').each(function(element){element.addClassName(\'custom_page-post-text-'.$edit_id.'\');});}});</script>'."\n";
									$head_scripts_editable = '';
									$content = $head_scripts_editable.$editor->Get();
									$edit = "javascript:void(0);\" onclick=\"custom_pageposttext".$edit_id."_InPlaceRichEditor.enterEditMode();";
								} else {
									$edit = "javascript:void(0);\" onclick=\"editElementAction('".$GLOBALS['app']->GetSiteURL('/'.BASE_SCRIPT)."?gadget=Layout&amp;action=EditElementAction&amp;id=".$id."');";
									$hook = $GLOBALS['app']->loadHook($gadget['gadget'], 'URLList');
									if ($hook !== false) {
										if (method_exists($hook, 'GetEditPage')) {
											$page = $hook->GetEditPage($edit_action, $edit_id, true);
											if ($page !== false) {
												$edit = $page."\" target=\"_blank";
											}
										}
									}
								}
								$t_item->SetVariable('move-edit', " item-move-edit");
								$t_item->SetVariable('edit_style', '');
								$t_item->SetVariable('gadget', $content);
								$t_item->SetVariable('controls', $controls);
								$t_item->SetVariable('base_script_url', $GLOBALS['app']->GetSiteURL('/'.BASE_SCRIPT));
								$t_item->SetVariable('edit', $edit);
								$t_item->SetVariable('edit-img', $editImg);
								$t_item->SetVariable('move-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/move-item.gif');
								$t_item->SetVariable('controls_style', '');
								//$t_item->SetVariable('item_class', '');
                                $t_item->ParseBlock('item/gadget');
                                $t_item->ParseBlock('item');
                            }
                        }
                    }
                }
            }

            /*
			$GLOBALS['app']->Layout->_Template->SetVariable('ELEMENT', '<div class="layout-section" id="layout_'.$name.'_drop" title="'.$name.'">
                                    <div id="layout_'.$name.'">'.$js_section_array.$t_item->Get().
                                    '</div></div>');
			*/
            $GLOBALS['app']->Layout->_Template->SetVariable('ELEMENT', 
				$t_item->Get().
				'<div class="ym-clearfix">&nbsp;</div>'
			);

            $GLOBALS['app']->Layout->_Template->ParseBlock('layout/'.$name);
            $t_item->Blocks['item']->Parsed = '';
        }

		$head_scripts_items = '';
		foreach ($head_items as $key => $val) {
			$head_scripts_items .= 'items[\''.$key.'\'] = {'.$val.'};'."\n";

		}
		$head_sections = "\n";
		foreach (explode(',',$head_scripts_sections) as $hs) {
			$head_sections .= 'sections.push('.$hs.');'."\n";
		}
		$head_scripts .= $head_sections.$head_scripts_items."\n";
		$items_on_layout = array();
		foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
			$items_on_layout[] = $on_layout;
		}
		$GLOBALS['app']->Layout->addHeadOther("<script type=\"text/javascript\">
			var prevStyles = new Array();
			items_on_layout = '".implode(',', $items_on_layout)."';
			Event.observe(window, 'load',function(){
				".$head_scripts."
				fileBrowserUrl = '".$GLOBALS['app']->GetSiteURL()."/index.php?gadget=FileBrowser&action=FilePicker';
				initUI();
			});
			document.observe('tinymce:onInit', function(event){
				var element_id = $(event.target).identify();
				var element = $(element_id);
				var id;
				if ($$('body')[0]) {
					while (element && element.match('body') === false) {
						id = element.identify();
						prevStyles[id] = new Array();
						/*prevStyles[id]['overflow'] = element.getStyle('overflow');*/
						prevStyles[id]['height'] = element.style.height;
						prevStyles[id]['minHeight'] = element.style.minHeight;
						prevStyles[id]['maxHeight'] = element.style.maxHeight;
						element.setStyle({
							'height': 'auto',
							minHeight: 'none',
							maxHeight: 'none'
						});
						element = element.up();
					}
				}
				if ($(element_id).up('.item')) {
					var item = $(element_id).up('.item');
					if (item.down('.inplacericheditor-form')) {
						item.setStyle({'height': item.down('.inplacericheditor-form').getHeight()+40+'px'});
					}
					if (item.down('.item-controls')){
						item.down('.item-controls').style.visibility = 'hidden';
					}
					if (item.down('.editor_cancel_link')){
						item.down('.editor_cancel_link').writeAttribute('href', 'javascript:void(0);');
					}
				}
			});
			document.observe('tinymce:onRemove', function(event){
				var element_id = $(event.target).identify();
				var element = $(element_id);
				var id;
				if ($$('body')[0]) {
					while (element && element.match('body') === false) {
						id = element.identify();
						if (prevStyles[id]) {
							element.setStyle({
								'height': prevStyles[id]['height'],
								minHeight: prevStyles[id]['minHeight'],
								maxHeight: prevStyles[id]['maxHeight']
							});
						}
						element = element.up();
					}
				}
				if ($(element_id).up('.item')) {
					var item = $(element_id).up('.item');
					item.setStyle({'height': 'auto'});
					if (item.down('.item-controls')){
						item.down('.item-controls').style.visibility = 'visible';
					}
				}
			});
		</script>");
		
        return $GLOBALS['app']->Layout->Show(false);
    }

    function getLayoutControls($useLayoutMode)
    {
        $GLOBALS['app']->LoadGadget('ControlPanel', 'AdminHTML');

        $tpl = new Jaws_Template('gadgets/Layout/templates/');
        $tpl->Load('LayoutControls.html');
        $tpl->SetBlock('controls');
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        $gInfo  = $GLOBALS['app']->loadGadget('Layout', 'Info');
        $docurl = null;
        if (!Jaws_Error::isError($gInfo)) {
            $docurl = $gInfo->GetDoc();
        }

        $tpl->SetVariable('admin_script', BASE_SCRIPT);
        $tpl->SetVariable('title-cp', _t('CONTROLPANEL_NAME'));
        $tpl->SetVariable('title-name', _t('LAYOUT_NAME'));
        $tpl->SetVariable('icon-gadget', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/logo.png');
        $tpl->SetVariable('title-gadget', 'Layout');

		$css_url = $GLOBALS['app']->GetSiteURL('/'.BASE_SCRIPT.'?gadget=Layout&amp;action=EditCSS');
		$css_link = "<a href=\"javascript:void(0)\" onClick=\"javascript: editCSS('".$css_url."', '"._t('LAYOUT_EDIT_CSS')."');\">"._t('LAYOUT_EDIT_CSS')."</a>";
		$tpl->SetVariable('css_link', $css_link);
        $tpl->SetVariable('theme', _t('LAYOUT_THEME'));
        $themeCombo =& Piwi::CreateWidget('Combo', 'theme');
        $themeCombo->setID('theme');
        $themeCombo->setStyle('width: 150px; max-width: 150px;');
        $themes = Jaws_Utils::GetThemesList(false);
		foreach ($themes as $th) {
			$themeCombo->AddOption($th, $th);
		}
		// Get repository themes
		if (Jaws_Gadget::IsGadgetUpdated('Tms')) {
			$tmsModel = $GLOBALS['app']->LoadGadget('Tms', 'Model');
			foreach($tmsModel->getRepositories() as $repository) {
				$rThemes = $tmsModel->getThemes($repository['id']);
				if (isset($rThemes) && is_array($rThemes)) {
					foreach ($rThemes as $theme) {
						$themeCombo->AddOption($repository['name'].' : '.$theme['name'], $theme['file']);
					}
				}
			}	
		}
        $themeCombo->SetDefault($GLOBALS['app']->Registry->Get('/config/theme'));
        $themeCombo->AddEvent(ON_CHANGE, "changeTheme();");
        $themeCombo->SetEnabled($this->GetPermission('ManageThemes'));
        $tpl->SetVariable('theme_combo', $themeCombo->Get());

        if ($useLayoutMode) {
            $tpl->SetVariable('mode', _t('LAYOUT_MODE').':');
            $modeCombo =& Piwi::CreateWidget('ComboImage', 'mode');
            $modeCombo->AddEvent(ON_CHANGE, 'changeLayoutMode();');
            $modeCombo->SetImageSize(16, 16);
            $modeCombo->AddOption(_t('LAYOUT_MODE_TWOBAR'),   1, $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/layout1.png');
            $modeCombo->AddOption(_t('LAYOUT_MODE_LEFTBAR'),  2, $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/layout2.png');
            $modeCombo->AddOption(_t('LAYOUT_MODE_RIGHTBAR'), 3, $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/layout3.png');
            $modeCombo->AddOption(_t('LAYOUT_MODE_NOBAR'),    4, $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/layout4.png');
            $modeCombo->SetDefault($GLOBALS['app']->Registry->Get('/config/layoutmode'));
            $modeCombo->SetEnabled($this->GetPermission('ManageThemes'));
            $tpl->SetVariable('mode_combo', $modeCombo->Get());
        }

        $add =& Piwi::CreateWidget('Button', 'add', _t('LAYOUT_NEW'), STOCK_ADD);
        $url = $GLOBALS['app']->GetSiteURL('/'.BASE_SCRIPT.'?gadget=Layout&amp;action=AddLayoutElement&amp;mode=new');
        $add->AddEvent(ON_CLICK, "addGadget('".$url."', '"._t('LAYOUT_NEW')."');");
        $tpl->SetVariable('add_gadget', $add->Get());

        if (!empty($docurl) && !is_null($docurl)) {
            $tpl->SetBlock('controls/documentation');
            $tpl->SetVariable('src', 'images/stock/help-browser.png');
            $tpl->SetVariable('alt', _t('GLOBAL_READ_DOCUMENTATION'));
            $tpl->SetVariable('url', $docurl);
            $tpl->ParseBlock('controls/documentation');
        }

        $tpl->ParseBlock('controls');
        return $tpl->Get();
    }

    function ChangeTheme()
    {
        $this->CheckPermission('ManageThemes');

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        $request =& Jaws_Request::getInstance();
        $layout = $request->get('layout', 'post');
		if (is_null($layout) || empty($layout)) {
			$layout = 'layout.html';
		}
        $theme = $request->get('theme', 'post');
        $mode = $request->get('mode', 'post');

        $tpl = new Jaws_Template();
		if (substr(strtolower($theme), 0, 4) == 'http') {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Layout');
			if($snoopy->fetch($theme.'/'.$layout)) {
				$tpl->LoadFromString($snoopy->results);
			}
		} else {
			$tpl->Load(JAWS_DATA . 'themes/' . $theme . '/' . $layout, false, false);
		}        

        // Validate theme
        if (!isset($tpl->Blocks['layout'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_NO_BLOCK', $theme, 'layout'), RESPONSE_ERROR);
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
        }
        if (!isset($tpl->Blocks['layout']->InnerBlock['head'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_NO_BLOCK', $theme, 'head'), RESPONSE_ERROR);
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
        }
        if (!isset($tpl->Blocks['layout']->InnerBlock['main'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_NO_BLOCK', $theme, 'main'), RESPONSE_ERROR);
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
        }

        // Verify blocks/Reassign gadgets
        $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');
        $sections = $model->GetLayoutSections();

        // Backwards compatibility for layoutmode
        if ($mode != $GLOBALS['app']->Registry->Get('/config/layoutmode')) {
            switch($mode) {
                // Two bars...
                case 1: 
                        // Do nothing...
                        break;
                // Left bar
                case 2: 
                        // Disable right bar (bar2)
                        if (isset($tpl->Blocks['layout']->InnerBlock['bar2'])) {
                            $tpl->Blocks['layout']->InnerBlock['bar2'] = null;
                        }
                        break;
                // Right bar 
                case 3: 
                        // Disable left bar (bar1)
                        if (isset($tpl->Blocks['layout']->InnerBlock['bar1'])) {
                            $tpl->Blocks['layout']->InnerBlock['bar1'] = null;
                        }
                        break;
                // No bars
                case 4:
                        // Disable left bar (bar1)
                        if (isset($tpl->Blocks['layout']->InnerBlock['bar1'])) {
                            $tpl->Blocks['layout']->InnerBlock['bar1'] = null;
                        }
                        // Disable right bar (bar2)
                        if (isset($tpl->Blocks['layout']->InnerBlock['bar2'])) {
                            $tpl->Blocks['layout']->InnerBlock['bar2'] = null;
                        }
                        break;
            }
        }

        foreach ($sections as $s) {
            if (!isset($tpl->Blocks['layout']->InnerBlock[$s['section']])) {
                if (isset($tpl->Blocks['layout']->InnerBlock[$s['section'] . '_narrow'])) {
                    $model->MoveSection($s['section'], $s['section'] . '_narrow');
                } elseif (isset($tpl->Blocks['layout']->InnerBlock[$s['section'] . '_wide'])) {
                    $model->MoveSection($s['section'], $s['section'] . '_wide');
                } else {
                    if (strpos($s['section'], '_narrow')) {
                        $clear_section = str_replace('_narrow', '', $s['section']);
                    } else {
                        $clear_section = str_replace('_wide', '', $s['section']);
                    }
                    if (isset($tpl->Blocks['layout']->InnerBlock[$clear_section])) {
                        $model->MoveSection($s['section'], $clear_section);
                    } else {
                        $model->MoveSection($s['section'], 'main');
                    }
                }
            }
        }
        
        $GLOBALS['app']->Registry->Set('/config/theme', $theme);
		$GLOBALS['app']->Registry->Set('/config/layout', $layout);

        // Save mode if exists...
        if ($mode != '') {
            $GLOBALS['app']->Registry->Set('/config/layoutmode', $mode);
        }
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_THEME_CHANGED'), RESPONSE_NOTICE);
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout');
    }

    /**
     * Adds layout element
     *
     * @access public
     * @return template content
     */
    function AddLayoutElement()
    {
        $this->CheckPermission('ManageLayout');
        $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');

        // FIXME: When a gadget don't have layout actions
        // doesn't permit to add it into layout
        $tpl = new Jaws_Template('gadgets/Layout/templates/');
        $tpl->Load('AddGadget.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL('/');

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'LayoutAdminAjax' : 'LayoutAjax'));
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        $tpl->SetVariable('gadgets', _t('LAYOUT_GADGETS'));
        $tpl->SetVariable('actions', _t('LAYOUT_ACTIONS'));
        $tpl->SetVariable('no_actions_msg', _t('LAYOUT_NO_GADGET_ACTIONS'));
        $addButton =& Piwi::CreateWidget('Button', 'add',_t('LAYOUT_NEW'), STOCK_ADD);
        $addButton->AddEvent(ON_CLICK, "window.top.addGadgetToLayout($('gadget').value, getSelectedAction(),($('page_gadget') ? $('page_gadget').value : null),($('page_action') ? $('page_action').value : null),($('page_linkid') ? $('page_linkid').value : null));");
        $tpl->SetVariable('add_button', $addButton->Get());

        $request =& Jaws_Request::getInstance();
        $section = $request->get('section', 'post');
        if (is_null($section)) {
            $section = $request->get('section', 'get');
            $section = !is_null($section) ? $section : '';
        }

        $tpl->SetVariable('section', $section);

        $jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
        $gadget_list = $jms->GetGadgetsList(null, true, true, true);

        //Hold.. if we dont have a selected gadget?.. like no gadgets?
        if (count($gadget_list) <= 0) {
            Jaws_Error::Fatal('You don\'t have any installed gadgets, please enable/install one and then come back',
                             __FILE__, __LINE__);
        }
        
        reset($gadget_list);
        $first = current($gadget_list);
        $tpl->SetVariable('first', $first['realname']);

        $tpl->SetBlock('template/working_notification');
        $tpl->SetVariable('loading-message', _t('GLOBAL_LOADING'));
        $tpl->ParseBlock('template/working_notification');

        foreach ($gadget_list as $gadget) {
            $tpl->SetBlock('template/gadget');
            $tpl->SetVariable('id',     $gadget['realname']);
            $tpl->SetVariable('icon',   $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget['realname'].'/images/logo.png');
            $tpl->SetVariable('gadget', $gadget['name']);
            $tpl->SetVariable('desc',   $gadget['description']);
            $tpl->ParseBlock('template/gadget');
        }

        $tpl->ParseBlock('template');

        return $tpl->Get();
    }

    /**
     * Save layout element
     *
     * @access public
     * @return template content
     */
    function SaveLayoutElement()
    {
        $this->CheckPermission('ManageLayout');

        $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');

        $request =& Jaws_Request::getInstance();
        $fields = array('gadget_field', 'action_field', 'section');
        $post = $request->get($fields, 'post');

        // Check that the gadget had an action set.
        if (!is_null($post['action_field'])) {
            $model->NewElement($post['section'], $post['gadget_field'], $post['action_field']);
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
    }

    /**
     * Changes action of a given gadget
     *
     * @access public
     * @return template content
     */
    function EditElementAction()
    {
        $this->CheckPermission('ManageLayout');
        $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');

        $request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');
        $layoutElement = $model->GetElement($id);
        if (!$layoutElement || !isset($layoutElement['id'])) {
            return false;
        }
        $actions = $model->GetGadgetActions($layoutElement['gadget']);

        $tpl = new Jaws_Template('gadgets/Layout/templates/');
        $tpl->Load('EditGadget.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL('/');

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'LayoutAdminAjax' : 'LayoutAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        $gInfo = $GLOBALS['app']->LoadGadget($layoutElement['gadget'], 'Info');
        $tpl->SetVariable('gadget', $layoutElement['gadget']);
        $tpl->SetVariable('gadget_name', $gInfo->GetName());
        $tpl->SetVariable('gadget_description', $gInfo->GetDescription());

        $btnSave =& Piwi::CreateWidget('Button', 'ok',_t('GLOBAL_SAVE'), STOCK_SAVE);
        $btnSave->AddEvent(ON_CLICK, "parent.parent.saveElementAction(".$id.", getSelectedAction());");
        $tpl->SetVariable('save', $btnSave->Get());

        $actionsList =& Piwi::CreateWidget('RadioButtons', 'action_field', 'vertical');
        if (count($actions) > 0) {
            foreach ($actions as $action) {
                $tpl->SetBlock('template/gadget_action');
                $tpl->SetVariable('name',   $action['name']);
                $tpl->SetVariable('action', $action['action']);
                $tpl->SetVariable('desc',   $action['desc']);
                if($layoutElement['gadget_action'] == $action['action']) {
                    $tpl->SetVariable('action_checked', 'checked="checked"');
                } else {
                    $tpl->SetVariable('action_checked', '');
                }
                $tpl->ParseBlock('template/gadget_action');
            }
        } else {
            $tpl->SetBlock('template/no_action');
            $tpl->SetVariable('no_gadget_desc', _t('LAYOUT_NO_GADGET_ACTIONS'));
            $tpl->ParseBlock('template/no_action');
        }

        $tpl->ParseBlock('template');
        return $tpl->Get();
    }

    /**
     * Changes when to display a given gadget
     *
     * @access public
     * @return template content
     */
    function ChangeDisplayWhen()
    {
        $this->CheckPermission('ManageLayout');
        $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');
        
		$tpl = new Jaws_Template('gadgets/Layout/templates/');
        $tpl->Load('DisplayWhen.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL('/');

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('display_when', _t('LAYOUT_DISPLAY'));

        $request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');

        $layoutElement = $model->GetElement($id);
        if (is_array($layoutElement) && !empty($layoutElement)) {
            $dw_value = $layoutElement['display_when'];
        }
        $displayCombo =& Piwi::CreateWidget('Combo', 'display_in');
        $displayCombo->AddOption(_t('LAYOUT_ALWAYS'), 'always');
        $displayCombo->AddOption(_t('LAYOUT_ONLY_IN_GADGET'), 'selected');
        if ($dw_value == '*') {
            $displayCombo->SetDefault('always');
            $tpl->SetVariable('selected_display', 'none');
        } else {
            $displayCombo->SetDefault('selected');
            $tpl->SetVariable('selected_display', 'block');
        }
        $displayCombo->AddEvent(ON_CHANGE, "showGadgets();");
        $tpl->SetVariable('display_in_combo', $displayCombo->Get());

        // Display in list
        $selectedGadgets = explode(',', $dw_value);
        // for index...
        $gadget_field =& Piwi::CreateWidget('CheckButtons', 'checkbox_index', 'vertical');
        $gadget_field->AddOption(_t('LAYOUT_INDEX'), 'index', null, in_array('index', $selectedGadgets));
        $jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
        $gadget_list = $jms->GetGadgetsList(null, true, true, true);
        $s = 0;
		$head_scripts = '';
		$hidden_options = array();
		foreach ($selectedGadgets as $selGadget) {
			if (substr($selGadget, 0, 8) == '{GADGET:') {
				$post_gadget = '';
				$post_action = '';
				$post_id = null;
				$this_dw = str_replace(array("{GADGET:", "ACTION:", '}'), '', $selGadget);
				$this_dw = explode('|', $this_dw);
				$post_gadget = $this_dw[0];
				if (strpos($this_dw[1], '(') !== false) {
					$post_action = substr($this_dw[1], 0, strpos($this_dw[1], '('));
					$post_id = str_replace(array('(', ')'), '', substr($this_dw[1], strpos($this_dw[1], '('), strlen($this_dw[1])));
				} else {
					$post_action = $this_dw[1];
				}
				$hook = $GLOBALS['app']->loadHook($post_gadget, 'URLList');
				if ($hook !== false) {
					if (method_exists($hook, 'CurrentURLHasPage')) {
						$page = $hook->CurrentURLHasPage($post_action, $post_id);
						if ($page !== false && isset($page['id']) && !empty($page['id'])) {
							$page_title = (!empty($post_gadget) ? $post_gadget.': ' : '');
							if (!empty($page['title'])) {
								$page_title .= substr($page['title'], 0, 25);
							} else {
								$page_title .= substr(str_replace(array('_','-'), " ", $page['fast_url']), 0, 25);
							}
							$page_title .= (!empty($post_action) ? ' ('.$post_action.')' : '');
							$gadget_field->AddOption($page_title, $selGadget, null, true);
						}
					}
				}
				
			} else if (substr($selGadget, 0, 12) == '{HIDEGADGET:' || $selGadget == '*') {
				$head_scripts .= (empty($head_scripts) ? '<style type="text/css">#' : ', #').'gadget_check_'.$s;
				$hidden_options['gadget_check_'.$s] = $selGadget;
			}
			$s++;
		}
		$tpl->SetVariable('head_scripts', $head_scripts.(!empty($head_scripts) ? ' { visibility: hidden; }</style>' : ''));
		foreach ($gadget_list as $g) {
            $gadget_field->AddOption($g['name'], $g['realname'], null, in_array($g['realname'], $selectedGadgets));
        }
		foreach ($hidden_options as $hk => $hv) {
			$gadget_field->AddOption('', $hv, $hk, true);
        }
        $tpl->SetVariable('selected_gadgets', $gadget_field->Get());

        $saveButton =& Piwi::CreateWidget('Button', 'OK',_t('GLOBAL_SAVE'), STOCK_SAVE);
        $saveButton->AddEvent(ON_CLICK, "parent.parent.saveChangeDW(".$id.", getSelectedGadgets());");
        $tpl->SetVariable('save', $saveButton->Get());

        $tpl->ParseBlock('template');
        return $tpl->Get();
    }

    /**
     * Delete an element from the layout
     *
     * @access public
     * @return template content
     */
    function DeleteLayoutElement()
    {
        $this->CheckPermission('ManageLayout');
        $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');

        $request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');

        $model->DeleteElement($id);

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
    }

    /**
     * Edit the custom.css file
     *
     * @access public
     * @return template content
     */
    function EditCSS()
    {
        $this->CheckPermission('ManageLayout');
        $model = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');

        $tpl = new Jaws_Template('gadgets/Layout/templates/');
        $tpl->Load('SaveCSS.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'LayoutAdminAjax' : 'LayoutAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        //$btnSave =& Piwi::CreateWidget('Button', 'ok',_t('GLOBAL_SAVE'), STOCK_SAVE);
        //$btnSave->AddEvent(ON_CLICK, "parent.parent.saveCSS($('css_data').value);");
        //$tpl->SetVariable('update_button', $btnSave->Get());

		$tpl->SetBlock('template/css_form');
		
		$upload_image =& Piwi::CreateWidget('Button', 'upload_image', _t('LAYOUT_IMAGE_UPLOAD'), STOCK_ADD);
		$upload_image->AddEvent(ON_CLICK, "window.open('admin.php?gadget=FileBrowser&action=FilePicker&path=css&display=thumbs&where=fake&form=fake');");
		$tpl->SetVariable('upload_button', $upload_image->Get());
		$update_button =& Piwi::CreateWidget('Button', 'update_button', _t('LAYOUT_UPDATE_CSS'), 
											STOCK_TEXT_EDIT);
        $update_button->SetSubmit();
		$update_button->AddEvent(ON_CLICK, "javascript: parent.parent.saveCSS($('css_data').value);");
		$tpl->SetVariable('update_button', $update_button->Get());


		$css_data = (file_exists(JAWS_DATA . 'files/css/custom.css')) ? file_get_contents(JAWS_DATA . 'files/css/custom.css') : '';
		//$cssEntry =& Piwi::CreateWidget('Textarea', 'css_data', $css_data);
		//$cssEntry->SetTitle(_t('LAYOUT_CSS_TITLE'));
		//$cssEntry->SetStyle('direction: ltr; width: 550px; height: 500px;');
		//$cssEntry->SetEnabled($this->GetPermission('ManageThemes'));

		//include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
		//$fieldset = new Jaws_Widgets_FieldSet('');
		//$fieldset->SetTitle('vertical');

		//$fieldset->Add($cssEntry);

		$tpl->SetVariable('css_data', $css_data);
		//$tpl->SetVariable('css_data', $css_data);
		$tpl->ParseBlock('template/css_form');

        $tpl->ParseBlock('template');
        return $tpl->Get();
    }
    
	/**
     * Save data to custom.css
     * 
     * @access  public
     * @param   string  $gadget 
     * @return  boolean true/false on error
     */
    function SaveCSS($data)
    { 
        require_once JAWS_PATH . 'include/Jaws/Header.php';
        $request =& Jaws_Request::getInstance();
        $data = $request->getRaw('css_data', 'post');
		$data = (isset($data) && !is_null($data) ? urldecode($data) : '');

		// create css directory and file
		$dir = JAWS_DATA . 'files/css';
		if (file_exists($dir)) {
			if (!file_put_contents(JAWS_DATA . 'files/css/custom.css', $data)) {
				//$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_CANT_WRITE_CSS'), RESPONSE_ERROR);
				return false;
				//Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
			}
		} else {
			if (!Jaws_Utils::mkdir($dir)) {
				//$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_CANT_WRITE_CSS'), RESPONSE_ERROR);
				return false;
				//Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
			} else {
				$result = file_put_contents(JAWS_DATA . 'files/css/custom.css', $data);
				if (!$result) {
					//$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_CANT_WRITE_CSS'), RESPONSE_ERROR);
					return false;
				}
			}
		}
		//$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_CSS_SAVED'), RESPONSE_NOTICE);
		Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
		//return true;

	}
}
