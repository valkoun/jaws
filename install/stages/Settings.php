<?php
/**
 * Sets up the default site settings.
 *
 * @author Jon Wood <jon@substance-it.co.uk>
 * @access public
 */
class Installer_Settings extends JawsInstallerStage
{
    /**
     * Default values
     *
     * @access private
     * @var array
     */
    var $_Fields = array();

    /**
     * Constructor
     *
     * @access public
     */
    function Installer_Settings()
    {
        $this->_Fields = array(
            'site_name'        => 'My Website',
            'site_slogan'      => '',
            'site_language'    => $_SESSION['install']['language'],
            'home_page'   => '',
            'layout'   => 'layout.html',
            'theme'   => 'default'
        );

        // Connect to the database and setup registry and similar.
        require_once JAWS_PATH . 'include/Jaws/DB.php';
        $GLOBALS['db'] = new Jaws_DB($_SESSION['install']['Database']);
        // Create application
        include_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
        $GLOBALS['app']->create();
        $GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['install']['language']));
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
    }

    /**
     * Sorts an array of Jaws_GadgetInfo objects by name.
     *
     * @access protected
     * @param  Jaws_GadgetInfo   $a
     * @param  Jaws_GadgetInfo   $b
     * @return int          1 = $a > $b, -1 = $b > $a, 0 = $a == $b
     */
    function SortGadgets($a, $b)
    {
        if ($a->GetName() == $b->GetName()) {
            return 0;
        }

        return ($a->GetName() < $b->GetName()) ? -1 : 1;
    }

    /**
     * Builds the installer page.
     *
     * @access  public
     * @return  string      A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        $values = $this->_Fields;
        $keys = array_keys($values);
        $request =& Jaws_Request::getInstance();
        $post = $request->get($keys, 'post');
        foreach ($this->_Fields as $key => $value) {
            if ($post[$key] !== null) {
                $values[$key] = $post[$key];
            }
        }

        $data = array();
        if (isset($_SESSION['install']['data']['Settings'])) {
            $data = $_SESSION['install']['data']['Settings'];
        }

		if (isset($_SESSION['install']['data']['Settings']['skip']) && !isset($GLOBALS['message'])) {	        
           $_SESSION['install']['Settings']['skip'] = '1';
           header('Location: index.php');
        } else {        
			define('PIWI_URL', 'http://jaws-project.com/libraries/piwi/');
	        define('PIWI_CREATE_PIWIXML', 'no');
	        define('PIWI_LOAD', 'SMART');
	        require_once JAWS_PATH . 'libraries/piwi/Piwi.php';
	
	        // Build the languages select.
	        $lang =& Piwi::CreateWidget('Combo', 'site_language');
	        $lang->SetID('site_language');
	        $languages = Jaws_Utils::GetLanguagesList();
	        foreach ($languages as $k => $v) {
	            $lang->AddOption($v, $k);
	        }
	        $lang->SetDefault($values['site_language']);
	
	        /*
			// Build the gadgets select.
	        include_once JAWS_PATH . 'include/Jaws/GadgetInfo.php';
	        $model = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
	
	        $gdt =& Piwi::CreateWidget('Combo', 'default_gadget');
	        $gdt->SetID('default_gadget');
	        $gdt->AddOption(_t('GLOBAL_NOGADGET'), '');
	        foreach ($model->GetGadgetsList(false, null, true) as $g => $tg) {
	            $gdt->AddOption($tg['realname'], $g);
	        }
	        $gdt->SetDefault($values['default_gadget']);
			*/
			
			// Home Page via Menu gadget        
			$gdt =& Piwi::CreateWidget('Combo', 'home_page');
			$gdt->setID('home_page');

			$sql = '
				SELECT
					[id], [menu_type], [title], [url], [visible]
				FROM [[menus]]
				WHERE ([url_target] = 0)
				ORDER BY [menu_type] ASC, [title] ASC';
			
			$menus = $GLOBALS['db']->queryAll($sql);
			if (Jaws_Error::IsError($menus)) {
				log($menus->getMessage());
				return $menus;
			}
			if (is_array($menus)) {
				foreach ($menus as $menu => $m) {
					if ($m['visible'] == 0) {
						$gdt->AddOption("<i>".$m['menu_type']." : ".$m['title']."</i>", $m['url']);
					} else {
						$gdt->AddOption($m['menu_type']." : ".$m['title'], $m['url']);
					}
				}
			}
			
			$gdt->SetDefault($values['home_page']);

			// get options for Themes
			$tmsModel = $GLOBALS['app']->LoadGadget('Tms', 'Model');
			$tmsAdminModel = $GLOBALS['app']->LoadGadget('Tms', 'AdminModel');
			$tmsAdminModel->addRepository('CDN Themes', 'http://jaws-project.com/data/themes/themes.rss');
			
			$i = 0;
			$themesXHTML = '';
			$themes = $tmsModel->getThemes('local');
			foreach ($themes as $theme) {
				$theme_image = (empty($theme['image']) ? 'http://jaws-project.com/gadgets/Tms/images/noexample.png' : $theme['image']);
				if ($i == 0) {
					$themesXHTML .= "<tr>\n";
				}
				$themesXHTML .= "<td style=\"text-align: center; margin-left: 20px; padding: 5px; vertical-align: middle;\"><input type=\"radio\" name=\"theme\" id=\"theme_".$theme['name']."\" value=\"".$theme['name']."\" ".($values['theme'] == $theme['name'] ? 'checked' : '')." /></td><td class=\"main-item\" style=\"text-align: center; padding: 5px; vertical-align: middle;\"><img border='0' src='".$theme_image."' width='150' style='cursor: pointer; cursor: hand;' onclick=\"setCheckedValue(document.forms[0].elements['theme'], '".$theme['name']."');\" /><br /><p style='margin-top: 5px;'><b>".str_replace('-', ' ', $theme['name'])."</b><br />".$theme['desc']."</p></td>\n";
				$i++;
				if ($i > 3) {
					$i = 0;
					$themesXHTML .= "</tr>\n";
				}
			}
			
			foreach($tmsModel->getRepositories() as $repository) {
				$themes = $tmsModel->getThemes($repository['id']);
				//$themesRadio =& Piwi::CreateWidget('RadioButtons', 'theme', 'horizontal');
				if (isset($themes) && is_array($themes)) {
					foreach ($themes as $theme) {
						$theme_image = (empty($theme['image']) ? 'http://jaws-project.com/gadgets/Tms/images/noexample.png' : $theme['image']);
						if ($i == 0) {
							$themesXHTML .= "<tr>\n";
						}
						$themesXHTML .= "<td style=\"text-align: center; margin-left: 20px; padding: 5px; vertical-align: middle;\"><input type=\"radio\" name=\"theme\" id=\"theme_".$theme['name']."\" value=\"".$theme['file']."\" ".($values['theme'] == $theme['file'] ? 'checked' : '')." /></td><td class=\"main-item\" style=\"text-align: center; padding: 5px; vertical-align: middle;\"><img border='0' src='".$theme_image."' width='150' style='cursor: pointer; cursor: hand;' onclick=\"setCheckedValue(document.forms[0].elements['theme'], '".$theme['file']."');\" /><br /><p style='margin-top: 5px;'><b>".str_replace('-', ' ', $theme['name'])."</b><br />".$theme['desc']."</p></td>\n";
						$i++;
						if ($i > 3) {
							$i = 0;
							$themesXHTML .= "</tr>\n";
						}
					}
				}
			}
			if (!empty($themesXHTML)) {
				$themesXHTML = "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n<tbody>\n".$themesXHTML;
			} else {
				$themesXHTML = "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n<tbody>\n";
				$themesXHTML .= "<br />\n"._t('INSTALL_SETTINGS_NO_THEMES')."\n";
			}
			$themesXHTML .= "</tbody>\n</table>\n";

	        $tpl = new Jaws_Template('stages/Settings/templates/');
	        $tpl->Load('display.html', false, false);
	        $tpl->SetBlock('Settings');
	
	        $tpl->setVariable('lbl_info',            _t('INSTALL_SETTINGS_INFO'));
	        $tpl->setVariable('lbl_site_name',       _t('INSTALL_SETTINGS_SITE_NAME'));
	        $tpl->setVariable('site_name_info',      _t('INSTALL_SETTINGS_SITE_NAME_INFO'));
	        $tpl->setVariable('lbl_site_slogan',     _t('INSTALL_SETTINGS_SLOGAN'));
	        $tpl->setVariable('site_slogan_info',    _t('INSTALL_SETTINGS_SLOGAN_INFO'));
	        $tpl->setVariable('lbl_default_gadget',  _t('INSTALL_SETTINGS_DEFAULT_PAGE'));
	        $tpl->setVariable('default_gadget_info', _t('INSTALL_SETTINGS_DEFAULT_PAGE_INFO'));
	        $tpl->setVariable('lbl_site_language',   _t('INSTALL_SETTINGS_SITE_LANGUAGE'));
	        $tpl->setVariable('site_language_info',  _t('INSTALL_SETTINGS_SITE_LANGUAGE_INFO'));
			$tpl->setVariable('lbl_default_theme',  _t('INSTALL_SETTINGS_DEFAULT_THEME'));
	        $tpl->SetVariable('next',                _t('GLOBAL_NEXT'));
	
	        $tpl->SetVariable('site_name',      $values['site_name']);
	        $tpl->SetVariable('site_slogan',    $values['site_slogan']);
	        $tpl->SetVariable('site_language',  $lang->Get());
			$tpl->SetVariable('default_gadget', $gdt->Get());
			$tpl->SetVariable('themes', $themesXHTML);
			$tpl->SetVariable('layout', $values['layout']);
	
	        $tpl->ParseBlock('Settings');
	        return $tpl->Get();
		}
	}

    /**
     * Validates any data provided to the stage.
     *
     * @access  public
     * @return  bool|Jaws_Error  Returns either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Validate()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('site_name'), 'post');

        if (isset($_SESSION['install']['data']['Settings'])) {
            $post = $_SESSION['install']['data']['Settings'] + $post;
        }

        if (!empty($post['site_name'])) {
            return true;
        }
        log_install("Site name wasn't found");
        return new Jaws_Error(_t('INSTALL_USER_RESPONSE_SITE_NAME_EMPTY'), 0, JAWS_ERROR_WARNING);
    }

    /**
     * Does any actions required to finish the stage, such as DB queries.
     *
     * @access  public
     * @return  bool|Jaws_Error  Either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Run()
    {
        include_once JAWS_PATH . 'include/Jaws/Log.php';
        $GLOBALS['log'] = new Jaws_Log();

        $keys = array_keys($this->_Fields);
        $request =& Jaws_Request::getInstance();
        $post = $request->get($keys, 'post');

        if (isset($_SESSION['install']['data']['Settings'])) {
            $post = $_SESSION['install']['data']['Settings'] + $post;
        }

        log_install("Setting up main settings (site name, description, languages, copyrights, etc");
        $settings = array();
        $settings['/config/site_status']      = 'enabled';
        $settings['/config/site_name']        = $post['site_name'];
        $settings['/config/site_slogan']      = $post['site_slogan'];
        $settings['/config/site_comment']     = '';
        $settings['/config/site_keywords']    = '';
        $settings['/config/site_description'] = '';
        $settings['/config/site_author']      = $_SESSION['install']['CreateUser']['name'];
        $settings['/config/site_license']     = '';
        $settings['/config/site_favicon']     = 'images/jaws.png';
        $settings['/config/title_separator']  = '-';
        //$settings['/config/main_gadget']	  = $post['default_gadget'];
        $settings['/config/home_page']		  = $post['home_page'];
        $settings['/config/copyright']        = date('Y') . ', ' . $_SESSION['install']['CreateUser']['name'];
        $settings['/config/site_language']    = $post['site_language'];
        $settings['/config/admin_language']   = 'en';
		if (isset($_SESSION['install']['data']['Settings']['theme']) && !empty($_SESSION['install']['data']['Settings']['theme'])) {
			$post['theme'] = $_SESSION['install']['data']['Settings']['theme'];
		}
		
		if (
			isset($_SESSION['install']['data']['CreateUser']['reseller']) && 
			isset($_SESSION['install']['data']['CreateUser']['reseller_link']) && 
			!empty($_SESSION['install']['data']['CreateUser']['reseller']) && 
			!empty($_SESSION['install']['data']['CreateUser']['reseller_link'])
		) {
			$settings['/config/site_reseller']    = $_SESSION['install']['data']['CreateUser']['reseller'];
			$settings['/config/site_reseller_link']    = $_SESSION['install']['data']['CreateUser']['reseller_link'];
		} else {
			$settings['/config/site_reseller']    = "Jaws Project";
			$settings['/config/site_reseller_link']    = "http://jaws-project.com/";
		}
        $settings['/config/cookie/domain']    = $_SERVER['HTTP_HOST'];
        $settings['/network/site_email']      = $_SESSION['install']['CreateUser']['email'];
        $settings['/network/email_name']      = $_SESSION['install']['CreateUser']['name'];
		if (isset($post['theme'])) {
			log_install("Installing theme: ".$post['theme']);
			if (strpos($post['theme'], "(twobar)")) {
	            $layoutmode = 1;
			} else if (strpos($post['theme'], "(leftbar)")) {
	            $layoutmode = 2;
			} else if (strpos($post['theme'], "(rightbar)")) {
	            $layoutmode = 3;
			} else if (strpos($post['theme'], "(nobar)")) {
	            $layoutmode = 4;
			} else {
				$layoutmode = 1;
			}
			log_install("Setting layout mode to: ".$layoutmode);
			/*
			// install Menu into block
			if ($layoutmode == 2 || $layoutmode == 1) {
				// install default menus into left block
				$section = 'bar1';
			} else if ($layoutmode == 3) {
				// install default menus into right block
				$section = 'bar2';
			} else {
			*/
				// install default menus into header block
				$section = 'header';
			//}

			// create directories and file
			log_install("creating 'thumb' and 'medium' directories");
	        $new_thumb_dir = JAWS_DATA . 'files' . DIRECTORY_SEPARATOR .'thumb';
	        if (!Jaws_Utils::mkdir($new_thumb_dir)) {
				return new Jaws_Error(_t('INSTALL_SETTINGS_CANT_CREATE_THUMB').' ['. JAWS_DATA . 'files'. DIRECTORY_SEPARATOR .'thumb]', 0, JAWS_ERROR_ERROR);
	        }
	        $new_medium_dir = JAWS_DATA . 'files' . DIRECTORY_SEPARATOR .'medium';
	        if (!Jaws_Utils::mkdir($new_medium_dir)) {
				return new Jaws_Error(_t('INSTALL_SETTINGS_CANT_CREATE_MEDIUM').' ['. JAWS_DATA . 'files'. DIRECTORY_SEPARATOR .'medium]', 0, JAWS_ERROR_ERROR);
	        }

			log_install("creating 'css' directory");
	        $new_dir = JAWS_DATA . 'files' . DIRECTORY_SEPARATOR .'css';
	        if (!Jaws_Utils::mkdir($new_dir)) {
				return new Jaws_Error(_t('INSTALL_SETTINGS_CANT_CREATE_CSS').' ['. JAWS_DATA . 'files'. DIRECTORY_SEPARATOR .'css]', 0, JAWS_ERROR_ERROR);
	        }
			if (file_exists(JAWS_DATA . 'files'. DIRECTORY_SEPARATOR .'css'. DIRECTORY_SEPARATOR .'custom.css')) {
				log_install("'custom.css' file already exists");
			} else {	
				$result = file_put_contents(JAWS_DATA . 'files'. DIRECTORY_SEPARATOR .'css'. DIRECTORY_SEPARATOR .'custom.css', '// Enter any custom css here');
				if (!$result) {
					log_install("'custom.css' file couldn't be created");
					return new Jaws_Error(_t('INSTALL_SETTINGS_CANT_CREATE_CSS').' ['. JAWS_DATA . 'files'. DIRECTORY_SEPARATOR .'css'. DIRECTORY_SEPARATOR .'custom.css]', 0, JAWS_ERROR_ERROR);
				}
			}

			$params                = array();
	        $params['gadget']      = 'Menu';
	        $params['action']      = 'Display(1)';
	        $params['displayWhen'] = '*';
	        $params['section']     = $section;
	        $params['pos']         = 1;
			
			$sql = 'DELETE FROM [[layout]] WHERE [gadget] = {gadget} AND [gadget_action] = {action}';
			$res = $GLOBALS['db']->query($sql, array('gadget' => 'Menu', 'action' => 'Display(1)'));
			if (Jaws_Error::IsError($res)) {
                log($res->getMessage());
                return $res;
			}
				
	        $sql2 = '
	            INSERT INTO [[layout]]
	                ([section], [gadget], [gadget_action], [display_when], [layout_position])
	            VALUES
	                ({section}, {gadget}, {action}, {displayWhen}, {pos})';

	        $result = $GLOBALS['db']->query($sql2, $params);
	        if (Jaws_Error::IsError($result)) {
                log($result->getMessage());
                return $result;
	        }
			log_install("Installed Menu block to section: ".$section);
			
			// install LoginLinks into footer block
			$params                = array();
			$params['gadget']      = 'Users';
			$params['action']      = 'LoginLinks';
			$params['displayWhen'] = '*';
			$params['section']     = 'footer';
			$params['pos']         = 1;
			
			$sql = 'DELETE FROM [[layout]] WHERE [gadget] = {gadget} AND [gadget_action] = {action}';
			$res = $GLOBALS['db']->query($sql, array('gadget' => 'Users', 'action' => 'LoginLinks'));
			if (Jaws_Error::IsError($res)) {
                log($res->getMessage());
                return $res;
			}

			$sql2 = '
				INSERT INTO [[layout]]
					([section], [gadget], [gadget_action], [display_when], [layout_position])
				VALUES
					({section}, {gadget}, {action}, {displayWhen}, {pos})';

			$result = $GLOBALS['db']->query($sql2, $params);
			if (Jaws_Error::IsError($result)) {
                log($result->getMessage());
                return $result;
			}
			log_install("Installed LoginLinks block");
		} 
        $settings['/config/layoutmode'] = $layoutmode;

        $settings['/config/theme']      = $post['theme'];
        $settings['/config/layout']      = $post['layout'];

        foreach ($settings as $key => $value) {
            $GLOBALS['app']->Registry->NewKey($key, $value);
        }

        // Commit the changes
        log_install("Saving settings changes");
        $GLOBALS['app']->Registry->commit('core');

        require_once JAWS_PATH . 'include/Jaws/URLMapping.php';
        $GLOBALS['app']->Map = new Jaws_URLMapping();

        //if (!empty($post['default_gadget'])) {
            $result = Jaws_Gadget::EnableGadget('CustomPage');
            log("Enabling 'CustomPage' gadget");
            if (Jaws_Error::IsError($result)) {
                log($result->getMessage());
                return $result;
            }
            log("'CustomPage' has been enabled");

        //}

        return true;
    }
}