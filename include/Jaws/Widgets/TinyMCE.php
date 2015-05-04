<?php
/**
 * Jaws TinyMCE Wrapper (uses JS and disable plugins)
 *
 * @category   Widget
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
require_once JAWS_PATH . 'libraries/piwi/Widget/Container/Container.php';

class Jaws_Widgets_TinyMCE extends Container
{
    /**
     * @access  public
     * @var     object
     */
    var $TextArea;

    /**
     * @access  private
     * @var     object
     */
    var $_Name;

    /**
     * @access  private
     * @var     object
     */
    var $_Class;

    /**
     * @access  private
     * @var     object
     * @see     function  GetValue
     */
    var $_Value;

    /**
     * @access  private
     * @var     object
     */
    var $_Container;

    /**
     * @access  private
     * @var     Label
     * @see     function  GetLabel
     * @see     function  SetLabel
     */
    var $_Label;

    /**
     * @access  private
     * @var     string
     */
    var $_Gadget;

    /**
     * for info see:
     * http://wiki.moxiecode.com/index.php/TinyMCE:Configuration/mode
     * @access  private
     */
    var $_Mode = 'textareas';

    /**
     * @access  private
     * @var     string
     */
    var $_Theme = 'advanced';

    /**
     * TinyMCE base actions
     *
     * @access  private
     */
    /*
	var $_BaseToolbar = array(
        'bold,italic,strikethrough,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,outdent,indent,|,fontselect,fontsizeselect,|,code',
        'cut,copy,paste,pastetext,pasteword,|,forecolor,backcolor,|,undo,redo,|,example,unlink,|',
    );
	*/
    
	var $_BaseToolbar = array(
        'bold,italic,strikethrough,underline,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist',
		'code,|,undo,redo,|,image,example,unlink,|'
    );

    /**
     * TinyMCE ompatibile browsers
     *
     * @access  private
     */
    var $_Browsers = array('msie', 'gecko', 'opera', 'safari');

    /**
     * for info see:
     * http://wiki.moxiecode.com/index.php/TinyMCE:Configuration/extended_valid_elements
     *
     * @access  private
     */
    var $_ExtendedValidElements = 'a[id|class|name|href|target|title|onclick|rel],marquee[scrollamount|id|class],iframe[marginheight|frameborder|scrolling|align|marginwidth|name|id|src|height|width|class],button[class|id|onclick|onmouseover|onmouseout|height|width|value|name|type],input[class|id|onclick|onmouseover|onmouseout|height|width|value|name|type]';

    /**
     * for info see:
     * http://wiki.moxiecode.com/index.php/TinyMCE:Configuration/invalid_elements
     *
     * @access  private
     */
    var $_InvalidElements = '';

    /**
     * for info see:
     * http://wiki.moxiecode.com/index.php/TinyMCE:Configuration/external_link_list_url
     *
     * @access  private
     */
    var $_ExternalLinkList = '';
    
	var $_InPlaceEditing = false;
	var $_InPlaceURL = null;
	var $_InPlaceOptions = null;

    /**
     * Main Constructor
     *
     * @access  public
     */
    function Jaws_Widgets_TinyMCE($gadget, $name, $value = '', $label = '', $inplace = false, $url = null, $inplace_options = null)
    {
        require_once JAWS_PATH . 'include/Jaws/String.php';
        //$value = Jaws_String::AutoParagraph($value);
        $value = str_replace('&lt;', '&amp;lt;', $value);
        $value = str_replace('&gt;', '&amp;gt;', $value);

        $this->_Name  = $name;
        $this->_Value = $value;

        $this->TextArea =& Piwi::CreateWidget('TextArea', $name, $this->_Value, '', '14');
        $this->setClass($name);
        $this->_Label =& Piwi::CreateWidget('Label', $label, $this->TextArea);

        // Add container
        $this->_Container =& Piwi::CreateWidget('VBox');
        $this->_Container->PackStart($this->_Label);
        $this->_Container->PackStart($this->TextArea);

        $this->_Gadget = $gadget;
		// In place version
		$this->_InPlaceURL = $url;
		if (!is_null($url) && $inplace === true) {
			$this->_InPlaceOptions = $inplace_options;
			if (is_null($inplace_options)) {
				$this->_InPlaceOptions = '{}';
			}
			$this->_InPlaceEditing = true;
			$this->setID($name);
        } else {
			$this->_InPlaceEditing = false;
		}
		parent::init();
    }

    function buildXHTML()
    {
        static $alreadyLoaded;
        $alreadyLoaded = isset($alreadyLoaded)? true : false;

        $tinymcePlugins = array();
        $lang = $GLOBALS['app']->GetLanguage();
        $pluginDir = JAWS_DATA .'/tinymce/plugins/';
        if (is_dir($pluginDir)) {
            $dirs = scandir($pluginDir);
            foreach($dirs as $dir) {
                if ($dir != '.' && $dir != '..' && is_dir($pluginDir.$dir)) {
                    $tinymcePlugins[] = $dir;
                }
            }
        }

        $jawsPlugins = array();
        $jawsPluginsFunctions = '';
        // FIXME: we must find a way for load Jaws's plugins in tinyMCE
        /*
        $pluginTemplate = file_get_contents(JAWS_PATH . 'libraries/tinymce/plugin_template.js');
        $availablePlugins = explode(',', $GLOBALS['app']->Registry->Get('/plugins/parse_text/enabled_items'));
        foreach ($availablePlugins as $plugin) {
            $file   = JAWS_PATH . 'plugins/' . $plugin . '/' . $plugin . '.php';
            $use_in = '/plugins/parse_text/' . $plugin . '/use_in';
            $GLOBALS['app']->Registry->LoadFile($plugin, 'plugins');
            if (file_exists($file) &&
                (in_array($this->_Gadget, explode(',', $GLOBALS['app']->Registry->Get($use_in))) ||
                $GLOBALS['app']->Registry->Get($use_in) == '*'))
            {
                require_once $file;
                $plugintmp     = new $plugin();
                $plugincontrol = $plugintmp->GetWebControl("'".$this->TextArea->getID()."'");

                if (is_object($plugincontrol)) {
                    $plugincontrolValue = $plugincontrol->Get();
                    $plugincontrolValue = preg_replace('/\"(.*?)\"/si', '\"\\1\"', $plugincontrolValue);
                    $plugincontrolValue = str_replace("'", "\'", $plugincontrolValue);
                    $plugincontrolValue = str_replace("\\n", "\\\\n", $plugincontrolValue);
                    $plugincontrolValue = preg_replace("/(^[\r\n]*|[\r\n]+)+/", "", $plugincontrolValue);
                    if (preg_match_all('/\\s*<(td ?[^>]*)\\s*>(.*?)<\/td>/si', $plugincontrolValue, $matches) > 0) {
                        if (isset($matches[2]) && count($matches[2] > 0)) {
                            $plugincontrolValue = implode("&nbsp;", $matches[2]);
                        }
                    }
                    if (!empty($plugincontrolValue)) {
                        $pluginTemplateTmp = str_replace('{pluginName}', $plugin, $pluginTemplate);
                        $pluginTemplateTmp = str_replace('{pluginElement}', $plugincontrolValue, $pluginTemplateTmp);
                        $jawsPluginsFunctions .= $this->compactFile($pluginTemplateTmp)."\n\n";
                        $jawsPlugins[] = $plugin;
                    }
                }
            }
        }
        */

        $jawsPlugins  = implode($jawsPlugins, ',');
        $tinymcePlugins = implode($tinymcePlugins, ',');
        if (!empty($tinymcePlugins) && !empty($jawsPlugins)) {
            $plugins = $tinymcePlugins . ',' . $jawsPlugins;
        } else {
            $plugins = $tinymcePlugins . ',' . $jawsPlugins;
        }

		$countbars = count($this->_BaseToolbar);
		$countbars = ((!$countbars <= 0) ? ($countbars-1) : 0);
		reset($this->_BaseToolbar);
		$this->_BaseToolbar[$countbars] = $this->_BaseToolbar[$countbars].$tinymcePlugins;
        $toolbars = $this->_BaseToolbar;
        //$toolbars[] = $tinymcePlugins;
        //$toolbars[] = $jawsPlugins;

        $this->_XHTML = ($this->_InPlaceEditing === true ? $this->_Value : $this->_Container->Get());
		$this->_ExternalLinkList = $GLOBALS['app']->getSiteURL(). '/admin.php?gadget=Menu&action=TinyMCEMenus';

        if (!$alreadyLoaded) {
            $this->_XHTML.= '<script language="javascript" type="text/javascript" src="data/tinymce/tiny_mce_src.js"></script>'."\n";
            $this->_XHTML.= '<script language="javascript" type="text/javascript" src="data/tinymce/jawsMCEWrapper.js"></script>'."\n";
            if ($this->_InPlaceEditing === true) {
				$this->_XHTML.= '<style type="text/css">.inplacericheditor-hover {box-shadow: 0px 0px 20px 6px #FFFF99;}</style>'."\n";
				$this->_XHTML.= '<script language="javascript" type="text/javascript" src="data/tinymce/patch_inplaceeditor_1-8-2.js"></script>'."\n";
				$this->_XHTML.= '<script language="javascript" type="text/javascript" src="data/tinymce/patch_inplaceeditor_editonblank_1-8-2.js"></script>'."\n";
				$this->_XHTML.= '<script language="javascript" type="text/javascript" src="data/tinymce/inplacericheditor.js"></script>'."\n";
			}
			$this->_XHTML.= "<script type=\"text/javascript\">\n";
			$this->_XHTML.= $jawsPluginsFunctions;

			$fbrowser = $GLOBALS['app']->getSiteURL(). '/'. BASE_SCRIPT. '?gadget=FileBrowser&action=FilePicker';
			$this->_XHTML.= "function jaws_filebrowser_callback(field_name, url, type, win) {\n";
			$this->_XHTML.= "var fbrowser = '".$fbrowser."&type=' + type;\n";
			$this->_XHTML.= "tinyMCE.activeEditor.windowManager.open({\n";
			$this->_XHTML.= "   file : fbrowser,\n";
			$this->_XHTML.= "   title : '',\n";
			$this->_XHTML.= "   width : 920,\n";
			$this->_XHTML.= "   height : 400,\n";
			$this->_XHTML.= "   resizable : 'yes',\n";
			$this->_XHTML.= "   scrollbars : 'yes',\n";
			$this->_XHTML.= "   inline : 'yes',\n";
			$this->_XHTML.= "   close_previous : 'no'\n";
			$this->_XHTML.= "}, {\n";
			$this->_XHTML.= "   window : win.top,\n";
			$this->_XHTML.= "   input : field_name\n";
			$this->_XHTML.= "});\n";
			$this->_XHTML.= "return false;\n";
			$this->_XHTML.= "}\n";
			$this->_XHTML.= "/*\n";
			$this->_XHTML.= "Strips HTML and PHP tags from a string\n";
			$this->_XHTML.= "returns 1: 'Kevin <b>van</b> <i>Zonneveld</i>'\n";
			$this->_XHTML.= "example 2: strip_tags('<p>Kevin <img src=\"someimage.png\" onmouseover=\"someFunction()\">van <i>Zonneveld</i></p>', '<p>');\n";
			$this->_XHTML.= "returns 2: '<p>Kevin van Zonneveld</p>'\n";
			$this->_XHTML.= "example 3: strip_tags(\"<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>\", \"<a>\");\n";
			$this->_XHTML.= "returns 3: '<a href='http://kevin.vanzonneveld.net'>Kevin van Zonneveld</a>'\n";
			$this->_XHTML.= "example 4: strip_tags('1 < 5 5 > 1');\n";
			$this->_XHTML.= "returns 4: '1 < 5 5 > 1'\n";
			$this->_XHTML.= "*/\n";
			$this->_XHTML.= "function strip_tags (str, allowed_tags) {\n";
			$this->_XHTML.= "    var key = '', allowed = false;\n";
			$this->_XHTML.= "    var matches = [];    var allowed_array = [];\n";
			$this->_XHTML.= "    var allowed_tag = '';\n";
			$this->_XHTML.= "    var i = 0;\n";
			$this->_XHTML.= "    var k = '';\n";
			$this->_XHTML.= "    var html = '';\n";
			$this->_XHTML.= "    var replacer = function (search, replace, str) {\n";
			$this->_XHTML.= "        return str.split(search).join(replace);\n";
			$this->_XHTML.= "    };\n";
			$this->_XHTML.= "    if (allowed_tags) {\n";
			$this->_XHTML.= "        allowed_array = allowed_tags.match(/([a-zA-Z0-9]+)/gi);\n";
			$this->_XHTML.= "    }\n";
			$this->_XHTML.= "    str += '';\n";
			$this->_XHTML.= "    matches = str.match(/(<\/?[\S][^>]*>)/gi);\n";
			$this->_XHTML.= "    for (key in matches) {\n";
			$this->_XHTML.= "        if (isNaN(key)) {\n";
			$this->_XHTML.= "            continue;\n";
			$this->_XHTML.= "        }\n";
			$this->_XHTML.= "        html = matches[key].toString();\n";
			$this->_XHTML.= "        allowed = false;\n";
			$this->_XHTML.= "        for (k in allowed_array) {\n";
			$this->_XHTML.= "            allowed_tag = allowed_array[k];\n";
			$this->_XHTML.= "            i = -1;\n";
			$this->_XHTML.= "            if (i != 0) { i = html.toLowerCase().indexOf('<'+allowed_tag+'>');}\n";
			$this->_XHTML.= "            if (i != 0) { i = html.toLowerCase().indexOf('<'+allowed_tag+' ');}\n";
			$this->_XHTML.= "            if (i != 0) { i = html.toLowerCase().indexOf('</'+allowed_tag)   ;}\n";
			$this->_XHTML.= "            if (i == 0) {\n";          
			$this->_XHTML.= "				allowed = true;\n";
			$this->_XHTML.= "               break;\n";
			$this->_XHTML.= "            }\n";
			$this->_XHTML.= "        }\n";
			$this->_XHTML.= "        if (!allowed) {\n";
			$this->_XHTML.= "            str = replacer(html, \"\", str);\n";
			$this->_XHTML.= "        }\n";
			$this->_XHTML.= "    }\n";
			$this->_XHTML.= "    return str;\n";
			$this->_XHTML.= "}\n";
			$this->_XHTML.= "function removeHtmlComments(source){\n";
			$this->_XHTML.= "	var html = source + '';\n";
			$this->_XHTML.= "	var regX = /<(?:!(?:--[\s\S]*?--\s*)?(>)\s*|(?:script|style|SCRIPT|STYLE)[\s\S]*?<\/(?:script|style|SCRIPT|STYLE)>)/g;\n";
			$this->_XHTML.= "	return html.replace(regX, function(m,$1){ return $1?'':m; });\n";
			$this->_XHTML.= "}\n";
			$this->_XHTML.= "function myCustomCleanup(type,value){\n";
			$this->_XHTML.= "	switch (type) {\n";
			$this->_XHTML.= "		case 'submit_content':\n";
			$this->_XHTML.= "			var value = value + '';\n";
			$this->_XHTML.= "			value = value.replace(/<(p|em|strong)(>|[^>]*>)(\s)*<\/\1>/ig,'');\n";
			$this->_XHTML.= "			value = removeHtmlComments(value);\n";
			$this->_XHTML.= "			break;\n";
			$this->_XHTML.= "	}\n";
			$this->_XHTML.= "	return value;\n";
			$this->_XHTML.= "}\n";
			$this->_XHTML.= "</script>\n";
		}

		$this->_XHTML.= "<script type=\"text/javascript\">\n";
		if ($this->_InPlaceEditing === true) {
			$this->_XHTML.= "var tinymce_".preg_replace('/[^\w]/', '', $this->_Name)."_options = {\n";
		} else {
			$this->_XHTML.= "tinyMCE.init({\n";
		}
        $this->_XHTML.= "mode : '" .$this->_Mode. "',\n";
        $this->_XHTML.= "language :'" .$lang. "',\n";
        $this->_XHTML.= "theme : '" .$this->_Theme. "',\n";
        $this->_XHTML.= "plugins : '" .$plugins. "',\n";
        
		$hasFileBrowser = false;
		for ($i = 0; $i < 3; $i++) {
			$this->_XHTML .= "theme_".$this->_Theme."_buttons".($i+1)." : '";
            if (isset($toolbars[$i])) {
				if ($hasFileBrowser === false && (strpos($toolbars[$i], 'image,') !== false || strpos($toolbars[$i], ',image') !== false)) {
					$hasFileBrowser = true;
				}
				$this->_XHTML .= $toolbars[$i];
			}
			$this->_XHTML .= "',\n";
        }

        $this->_XHTML.= "theme_{$this->_Theme}_toolbar_location : 'top',\n";
        $this->_XHTML.= "theme_{$this->_Theme}_toolbar_align : 'left',\n";
        $this->_XHTML.= "theme_{$this->_Theme}_path_location : 'bottom',\n";
        $this->_XHTML.= "theme_{$this->_Theme}_resizing : true,\n";
        //$this->_XHTML.= "theme_{$this->_Theme}_resize_horizontal : false,\n";
        $this->_XHTML.= "browsers : '" . implode($this->_Browsers, ',') . "',\n";
        $this->_XHTML.= "directionality : '"._t('GLOBAL_LANG_DIRECTION')."',\n";
        $this->_XHTML.= "tab_focus : ':prev,:next',\n";
        $this->_XHTML.= "entity_encoding : 'raw',\n";
        $this->_XHTML.= "relative_urls : true,\n";
        $this->_XHTML.= "remove_script_host : false,\n";
        $this->_XHTML.= "force_p_newlines : true,\n";
        $this->_XHTML.= "force_br_newlines : false,\n";
        $this->_XHTML.= "convert_newlines_to_brs : false,\n";
        $this->_XHTML.= "remove_linebreaks : true,\n";
        $this->_XHTML.= "nowrap : false,\n";
        $this->_XHTML.= "fullscreen_new_window : true,\n";
        $this->_XHTML.= "dialog_type : 'window',\n";
        $this->_XHTML.= "apply_source_formatting : true,\n";
		$this->_XHTML.= "inlinepopups_skin: 'tinysimpleblue',\n";
		$this->_XHTML.= "accessibility_warnings : false,\n";
		$this->_XHTML.= "verify_css_classes:true,\n";
		$this->_XHTML.= "extended_valid_elements : '" . $this->_ExtendedValidElements . "',\n";
        $this->_XHTML.= "external_link_list_url : '" . $this->_ExternalLinkList. "',\n";
		$this->_XHTML.= "invalid_elements : '" . $this->_InvalidElements . "',\n";
        $this->_XHTML.= "editor_selector : '" . $this->_Class. "',\n";
		$this->_XHTML.= "paste_preprocess : function(pl, o) {\n";
		$this->_XHTML.= "  /*o.content = strip_tags(o.content,'<b><u><i><p>');\n";
		$this->_XHTML.= "  remove all tags => plain text*/\n";
		$this->_XHTML.= "  o.content = strip_tags(o.content,'');\n";
		$this->_XHTML.= "},\n";
        $this->_XHTML.= "template_templates : [\n";
		$this->_XHTML.= "	{\n";
		$this->_XHTML.= "		title : \"News Crawler\",\n";
		$this->_XHTML.= "		src : \"data/tinymce/plugins/template/templates/marquee.htm\",\n";
		$this->_XHTML.= "		description : \"Adds a news crawler (marquee)\"\n";
		$this->_XHTML.= "	}\n";
		$this->_XHTML.= "],\n";
        $this->_XHTML.= "setup : function(ed) {
			ed.onLoadContent.add(function(ed, cm) {
				if (
					ed.getContent().replace(/\W+/g, '') == 'START_postpbuttonclasstemppostbuttonAddPostToThisSectionbuttonpEND_post' ||
					ed.getContent().replace(/\W+/g, '') == 'pbuttonclasstemppostbuttonAddPostToThisSectionbuttonp' ||
					ed.getContent().replace(/\W+/g, '') == 'buttonclasstemppostbuttonAddPostToThisSectionbutton'
				) {
					ed.setContent('');
				}
			});
			ed.onSaveContent.add(function(ed, cm) {
				if (
					ed.getContent().replace(/\W+/g, '') != 'START_postpbuttonclasstemppostbuttonAddPostToThisSectionbuttonpEND_post' && 
					ed.getContent().replace(/\W+/g, '') != 'pbuttonclasstemppostbuttonAddPostToThisSectionbuttonp' && 
					ed.getContent().replace(/\W+/g, '') != 'buttonclasstemppostbuttonAddPostToThisSectionbutton' && 
					ed.getContent().replace(/\W+/g, '') != ''
				) {
					if (ed.id.substring(0, 22) == 'custom_page-post-text-') {
						var post_el = ed.id.replace(/\-textarea\-inplacericheditor/gi, '');
						if ($(post_el)) {
							if ($(post_el).up('.item')) {
								if ($(post_el).up('.item').hasClassName('item-temp')) {
									$(post_el).up('.item').removeClassName('item-temp');
									$(post_el).up('.item').down('.item-controls').removeClassName('temp-controls');
									var post_section = $(post_el).up('.layout-section').identify();
									if (post_section.substring(0, 12) == 'custom_page-') {
										post_section = post_section.replace(/custom_page\-/gi, '');
									}
									fun = 'pages.saveeditpost(\'\',\'\', ($(\'page_gadget\') ? $(\'page_gadget\').getValue() : null), ($(\'page_action\') ? $(\'page_action\').getValue() : null), ($(\'page_linkid\') ? $(\'page_linkid\').getValue() : null),\'CustomPage\',\'TempPost\',\'' + post_section + '\')';
									setTimeout(fun, 0);
								}
							}
						}
					}
				}
			});
		},\n";
        //$this->_XHTML.= "save_callback : 'jaws_save_callback',\n";
        $this->_XHTML.= "cleanup_callback : 'myCustomCleanup',\n";
        if ($hasFileBrowser === true) {
			$this->_XHTML.= "file_browser_callback : 'jaws_filebrowser_callback',\n";
		}
		// Check for textarea style of requested gadget
		if (!in_array($this->_Gadget, explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			$theme = $GLOBALS['app']->GetTheme();
			if (isset($theme['url'])) {
				$theme['url'] .= 'style.css';
				if (file_exists(JAWS_DATA . 'files/css/custom.css')) {
					$theme['url'] = $GLOBALS['app']->GetSiteURL() . '/gz.php?type=css&uri1='.urlencode($theme['url']).'&uri2='.urlencode($GLOBALS['app']->GetDataURL('files/css/custom.css', true));
				}
				$this->_XHTML.= "content_css : \"". $theme['url'] ."\"\n";
			}
		}
		if ($this->_InPlaceEditing === true) {
			$this->_XHTML.= "};\n";
		} else {
			$this->_XHTML.= "});\n";
		}
		if ($this->_InPlaceEditing === true) {
			$this->_XHTML .= "var ".preg_replace('/[^\w]/', '', $this->_Name)."_InPlaceRichEditor = null;
			Event.observe(window, 'load', function(){
				".preg_replace('/[^\w]/', '', $this->_Name)."_InPlaceRichEditor = new Ajax.InPlaceRichEditor($('".$this->_Class."'), '".$this->_InPlaceURL."', ". $this->_InPlaceOptions .", tinymce_".preg_replace('/[^\w]/', '', $this->_Name)."_options);
			});\n";
		}
        $this->_XHTML.= "</script>\n";
    }

    /**
     * Sets the ID for the widget int he first call and in next calls it places the id
     * in the TextArea
     *
     * @access  public
     * @param   string   $id  Widget ID
     */
    function setID($id)
    {
        static $containerID;
        if (!isset($containerID)) {
            parent::setID($id);
            $containerID = $this->getID();
        } else {
            $this->TextArea->setID($id);
        }
    }

    /**
     * Set the className of the TextArea
     *
     * @access    public
     */
    function setClass($class)
    {
        $this->_Class = $class;
        $this->TextArea->setClass($class);
    }

    /**
     * Sets the label displayed with the textarea
     *
     * @access public
     * @param  string $label The label to display.
     * @return null
     */
    function SetLabel($label)
    {
        $this->_Label->SetValue($label);
    }

    /**
     * Sets invalid elements
     *
     * @access public
     * @param  string $invalidElements elements to set as invalid.
     * @return null
     */
    function SetInvalidElements($invalidElements)
    {
        $this->_InvalidElements = $invalidElements;
    }

    /**
     * Sets base toolbar
     *
     * @access public
     * @param  string $baseToolbar toolbar plugins override
     * @return null
     */
    function SetBaseToolbar($baseToolbar)
    {
        $this->_BaseToolbar = $baseToolbar;
    }

    /**
     * Set the width of the editor
     *
     * @access  public
     * @param   string  $width  Width
     */
    function SetWidth($width)
    {
        $currentStyle = $this->_Container->getStyle();
        if (empty($currentStyle)) {
            $currentStyle = 'width: '. $width.';';
        } else {
            if (strpos($currentStyle, 'width:') === false) {
                if (substr($currentStyle, -1) != ';') {
                    $currentStyle = $currentStyle . ';';
                }
                $currentStyle = $currentStyle . 'width: '. $width.';';
            }
        }
        $this->_Container->SetStyle($currentStyle);
    }

    /**
     * Set the TinyMCE theme
     *
     * @access    public
     */
    function setTheme($theme)
    {
        $this->_Theme = $theme;
    }

    function compactFile($content)
    {
        //FROM WP
        $content = preg_replace("!(^|\s+)//.*$!m", "", $content);
        $content = preg_replace("!/\*.*?\*/!s", "", $content);
        $content = preg_replace("!^\t+!m", "", $content);
        $content = str_replace("\r", "", $content);
        $content = preg_replace("!(^|{|}|;|:|\))\n!m", '\\1', $content);

        return $content;
    }
}
?>
