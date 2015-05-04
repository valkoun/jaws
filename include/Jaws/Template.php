<?php
/**
 * Template block container.
 *
 * @category   Layout
 * @package Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
 
class Jaws_TemplateBlock
{
    var $Name = '';
    var $Attributes = array();
    var $Content = '';
    var $RawContent = '';
    var $Parsed = '';
    var $Vars = array();
    var $InnerBlock = array();
}

/**
 * Theming API. Default templates can be easily overridden using HTML and CSS.
 *
 * @category   Layout
 * @category 	feature
 * @package Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
 
class Jaws_Template
{
    var $Content;
    var $raw_store;
    var $IdentifierRegExp;
    var $AttributesRegExp;
    var $BlockRegExp;
    var $VarsRegExp;
    var $IsBlockRegexp;
    var $MainBlock;
    var $CurrentBlock;
    var $Blocks = array();
    var $Path;

    /**
     * Class constructor
     *
     * @param   string $Path Path where template files are located
     * @access  public
     */
    function Jaws_Template($Path = '')
    {
        $this->IdentifierRegExp = '[0-9A-Za-z_-]+';
        $this->AttributesRegExp = '/(\w+)((\s*=\s*".*?")|(\s*=\s*\'.*?\')|(\s*=\s*\w+)|())/s';
        $this->BlockRegExp = '@<!--\s+begin\s+('.$this->IdentifierRegExp.')\s+([^>]*)-->(.*)<!--\s+end\s+\1\s+-->@sim';
        $this->VarsRegExp = '@{\s*('.$this->IdentifierRegExp.')\s*}@sim';
        $this->IsBlockRegExp = '@##\s*('.$this->IdentifierRegExp.')\s*##@sim';
        $this->Path = $Path;
    }

    /**
     * Returns template without any proccess
     *
     * @access  public
     */
    function GetContent()
    {
        return $this->Content;
    }

    /**
     * Set the path
     *
     * @access  public
     * @param   string  $path Template path (where templates are)
     */
    function SetPath($path)
    {
        $this->Path = $path;
    }

    /**
     * Loads a template from a file
     *
     * @param   string $fileName The file name
     * @access  public
     */
    function Load($fileName, $raw_store = false, $checkInTheme = null, $direction = null, $dontLoad = false)
    {
        if (is_null($checkInTheme)) {
            $checkInTheme = (defined(JAWS_SCRIPT) && (JAWS_SCRIPT == 'admin'))? false : true;
        }

        $fileExt  = strrchr($fileName, '.');
        $fileName = substr($fileName, 0, -strlen($fileExt));

        $direction = strtolower(empty($direction) ? (function_exists('_t') ? _t('GLOBAL_LANG_DIRECTION') : 'ltr') : $direction);
        $prefix = '.' . $direction;
        if ($prefix !== '.rtl') {
            $prefix = '';
        }

        // First we try to load the template from the theme dir.
		$content = '';
        if (isset($GLOBALS['app']) && $checkInTheme) {
			$theme = $GLOBALS['app']->GetTheme();
			if (!$theme['exists']) {
                Jaws_Error::Fatal('Template doesn\'t exists. <br />A possible reason of this error is that the theme: ' .
                                  '<strong>' . $theme['name'] . ' </strong> is missing', __FILE__, __LINE__);
            }

            $gadget = str_replace(array('gadgets/', 'templates/'), '', $this->Path);
            $gTemplateDir = empty($gadget)? '' : 'gadgets/' . $gadget . 'templates/';
			$tplFile = $theme['path'] . $gadget . $fileName . $prefix . $fileExt;
			if (substr(strtolower($tplFile), 0, 4) == 'http') { 	
				// snoopy
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$snoopy = new Snoopy;
				if (substr(strtolower($tplFile), 0, 5) == 'https') {
					$tplFile = $theme['name'].'/'.$gadget.$fileName.$prefix.$fileExt;
				}
				$snoopy->fetch($tplFile);
				if ($snoopy->status == "200") {
					$content = $snoopy->results;
				}
	        }            

			if (empty($content) && !file_exists($tplFile)) {
				$tplFile = $theme['path'] . str_replace($theme['path'], '', $gadget) . $fileName . $prefix . $fileExt;
				if (!file_exists($tplFile)) {
					$tplFile = JAWS_PATH . $gTemplateDir . $fileName . $prefix . $fileExt;
					if (!file_exists($tplFile)) {
						$tplFile = $theme['path'] . $gadget . $fileName . $fileExt;
						if (!file_exists($tplFile)) {
							$tplFile = JAWS_PATH . $gTemplateDir . $fileName . $fileExt;
						}
					}
				}
            }

        } else {
            $tplFile = $this->Path . (empty($this->Path) ? '' : '/') . $fileName . $prefix . $fileExt;
            if (!empty($prefix) && !file_exists($tplFile)) {
                $tplFile = $this->Path . (empty($this->Path) ? '' : '/') . $fileName . $fileExt;
            }
        }


		if (empty($content) && !file_exists($tplFile)) {
			if (isset($GLOBALS['app'])) {
                Jaws_Error::Fatal('Template '.$tplFile.' doesn\'t exists. <br />A possible reason of this error is that the theme: ' .
                                 '<strong>' . $theme['name'] . ' </strong> is missing', __FILE__, __LINE__);
            } else {
                Jaws_Error::Fatal('Template '.$tplFile.' doesn\'t exists. <br />A possible reason of this error is that the ' .
                                 'default theme is missing', __FILE__, __LINE__);
            }
        }

        if (empty($content) && filesize($tplFile) <= 0) {
            Jaws_Error::Fatal('Template '.$tplFile.' is empty, I can\'t work with empty files, do you?', __FILE__, __LINE__);
        }
		
        if (empty($content) && substr(strtolower($tplFile), 0, 4) != 'http') {
			$content = file_get_contents($tplFile);
			if ($content === false) {
				Jaws_Error::Fatal('There was a problem while reading the template file: ' . $tplFile, __FILE__, __LINE__);
			}
		}
		
        $content = preg_replace("#<!-- INCLUDE (.*) -->#ime",
                                "\$this->Load('\\1', false, $checkInTheme, '$direction', true)",
                                $content);
		if ($dontLoad) {
            return $content;
        }
		
        $this->loadFromString($content, $raw_store);
    }

    /**
     * Loads a template from a string
     *
     * @param   $tplString String that contains a template struct
     * @access  public
     */
    function LoadFromString($tplString, $raw_store = false)
    {
        $this->raw_store = $raw_store;
        $this->Content   = str_replace('\\', '\\\\', $tplString);
        $this->Content   = str_replace(array("-->\n", "-->\r\n"), '-->',  $this->Content);
        $this->Blocks    = $this->GetBlocks($this->Content);
        $this->MainBlock = $this->GetMainBlock();
    }

    /**
     * Returns the main block, subblocks are replaced with ##subblock##
     *
     * @access  public
     */
    function GetMainBlock()
    {
        $result = $this->Content;
        foreach ($this->Blocks as $k => $iblock) {
            $pattern = '@<!--\s+begin\s+'.$iblock->Name.'\s+([^>]*)-->(.*)<!--\s+end\s+'.$iblock->Name.'\s+-->@sim';
            $result = preg_replace($pattern, '##'.$iblock->Name.'##' , $result);
        }
        return $result;
    }

    /**
     * Return the subblocks struct for a given block
     *
     * @param   $contentString Block string
     * @access  public
     */
    function GetBlocks($contentString)
    {
        $blocks = array();
        if (preg_match_all($this->BlockRegExp, $contentString, $regs, PREG_SET_ORDER))  {
            foreach ($regs as $key => $match) {
                $wblock = new Jaws_TemplateBlock();
                $wblock->Name = $match[1];
                $attrs = array();
                preg_match_all($this->AttributesRegExp, $match[2], $attrs, PREG_SET_ORDER);
                foreach ($attrs as $attr) {
                    $attr[2] = ltrim($attr[2], " \n\r\t=");
                    $attr[2] = trim($attr[2], ($attr[2][0] == '"')? '"' : "'");
                    $wblock->Attributes[$attr[1]] = $attr[2];
                }
                $wblock->Content    = $match[3];
                $wblock->RawContent = $this->raw_store? $match[3] : null;
                $wblock->InnerBlock = $this->GetBlocks($wblock->Content);
                foreach ($wblock->InnerBlock as $k => $iblock) {
                    $pattern = '@<!--\s+begin\s+'.$iblock->Name.'\s+([^>]*)-->(.*)<!--\s+end\s+'.$iblock->Name.'\s+-->@sim';
                    $wblock->Content = preg_replace($pattern, '##'.$iblock->Name.'##' , $wblock->Content);
                }
                $wblock->Vars = $this->GetVariables($wblock->Content);
                $blocks[$wblock->Name] = $wblock;
            }
        }
        return $blocks;
    }

    /**
     * Return the attributes array of a current block
     *
     * @access  public
     */
    function GetCurrentBlockAttributes()
    {
        return $this->CurrentBlock->Attributes;
    }

    /**
     * Return the variables array of a given block
     *
     * @param   $blockContent Block string
     * @access  public
     */
    function GetVariables($blockContent)
    {
        $vars = array();
        if (preg_match_all($this->VarsRegExp, $blockContent, $regs, PREG_SET_ORDER)) {
            foreach ($regs as $k => $match) {
                if (isset($GLOBALS['app']) && $match[1] == 'THEME') {
                    $theme = $GLOBALS['app']->GetTheme();
                    $vars[$match[1]] = $theme['url'];
                } else {
                    $vars[$match[1]] = '';
                }
            }
        }

        return $vars;
    }

    /**
     * Returns the processed template(parsed blocks)
     *
     * @access  public
     */
    function Get()
    {
        $result = str_replace('\\\\', '\\', $this->MainBlock);
        if (preg_match_all($this->IsBlockRegExp, $result, $regs, PREG_SET_ORDER)) {
            foreach ($regs as $blockToReplace) {
                $pattern = '@##\s*(' . $blockToReplace[1] . ')\s*##@sim';
                $result = preg_replace($pattern, str_replace('$', '\$', $this->Blocks[$blockToReplace[1]]->Parsed) , $result);
            }
        }
        return $result;
    }

    /**
     * Returns the content of the current block
     *
     * @access  public
     * @return  string  Content
     */
    function GetCurrentBlockContent()
    {
        return is_null($this->CurrentBlock)? '' : $this->CurrentBlock->Content;
    }

    /**
     * Returns the raw content of a block
     *
     * @access  public
     * @param   string $pathString Block path if empty use current block
     * @return  string  Content
     */
    function GetRawBlockContent($pathString = '', $block_include = true)
    {
        if (empty($pathString)) {
            if (is_null($this->CurrentBlock)) {
                return '';
            } elseif ($block_include) {
                return "<!-- BEGIN {$this->CurrentBlock->Name} -->".
                       $this->CurrentBlock->RawContent.
                       "<!-- END {$this->CurrentBlock->Name} -->";
            } else {
                return $this->CurrentBlock->RawContent;
            }
        } else {
            $block =& $this->GetBlockObject($pathString);
            if (is_null($block)) {
                return '';
            } elseif ($block_include) {
                return "<!-- BEGIN {$block->Name} -->".
                       $block->RawContent.
                       "<!-- END {$block->Name} -->";
            } else {
                return $block->RawContent;
            }
        }
    }

    /**
     * Set the content of the current block
     *
     * @param   $content    Block content
     * @access  public
     */
    function SetCurrentBlockContent($content)
    {
        $this->CurrentBlock->Content = $content;
    }

    /**
     * Parse a given block, replacing its variables and parsed subblocks
     *
     * @param   $blockString Block string
     * @access  public
     */
    function ParseBlock($blockString = '')
    {
        $result = '';
        $block = &$this->GetBlockObject($blockString);
        if (isset($block->Content)) {
            $result = $block->Content;
            foreach ($block->Vars as $k => $v) {
                if (!is_array($v)) {
                    $v = str_replace('\\', '\\\\', $v);
                    $result = str_replace('{'.$k.'}', $v, $result);
                }
            }

            if (preg_match_all($this->IsBlockRegExp, $result, $regs, PREG_SET_ORDER)) {
                foreach ($regs as $blockToReplace) {
                    $search = '##' . $blockToReplace[1] . '##';
                    $replace = $block->InnerBlock[$blockToReplace[1]]->Parsed;
                    $result = str_replace($search, $replace , $result);
                }
            }
            $block->Parsed .= $result;
        }

        $blockString = substr($blockString, 0, strrpos($blockString, '/'));
        $this->SetBlock($blockString, false);

        return $result;
    }

    /**
     * Get a template variable in current block
     *
     * @param   string $key Variable name
     * @access  public
     */
    function GetVariable($key)
    {
        return $this->CurrentBlock->Vars[$key];
    }

    /**
     * Sets a template variable in current block
     *
     * @param   string $key Variable name
     * @param   string $value Variable value
     * @access  public
     */
    function SetVariable($key, $value)
    {
        $this->CurrentBlock->Vars[$key] = $value;
    }

    /**
     * Returns the block object of a given path
     *
     * @param   string $pathString Block path
     * @access  public
     */
    function &GetBlockObject($pathString)
    {
        if ($pathString == '') {
            return $this->CurrentBlock;
        }

        $blockDeep = 1;
        $path = explode('/', $pathString);
        foreach ($path as $b) {
            if ($blockDeep === 1) {
                $block = &$this->Blocks[$b];
            } else {
                $block = &$block->InnerBlock[$b];
            }
            $blockDeep++;
        }

        return $block;
    }

    /**
     * Changes the current block to the given path
     *
     * @param   string $pathString Block path
     * @access  public
     */
    function SetBlock($pathString, $init = true)
    {
        $this->CurrentBlock = &$this->GetBlockObject($pathString);
        if ($init === true) {
            $this->InitializeSubBlock($this->CurrentBlock);
        }
    }

    /**
     * Initialize subblocks of a given block object
     *
     * @param   object $block Block object
     * @access  public
     */
    function InitializeSubBlock(&$block)
    {
        if (
            is_object($block) && isset($block->Content) &&
            preg_match_all($this->IsBlockRegExp, $block->Content, $regs, PREG_SET_ORDER)
        ) {
            foreach ($regs as $subBlock) {
                $block->InnerBlock[$subBlock[1]]->Parsed = '';
                $this->InitializeSubBlock($block->InnerBlock[$subBlock[1]]);
            }
        }
    }

    /**
     * Set variables from a given associative array
     *
     * @param   array $variablesArray Associative array to replace
     * @access  public
     */
    function SetVariablesArray($variablesArray)
    {
        foreach ($variablesArray as $key => $value) {
            $this->CurrentBlock->Vars[$key] = $value;
        }
    }

    /**
     * Resets a variable in a previous block
     *
     * @access  public
     * @param   string  $variable  Variable's name
     * @param   string  $value     Variable's value
     * @param   string  $block     Block's name
     */
    function ResetVariable($variable, $value, $block)
    {
        if (isset($this->Blocks[$block])) {
			$this->Blocks[$block]->Vars[$variable] = $value;
        }
    }

    /**
     * Check if a given block exists
     *
     * @param string $pathString Block path
     * @return boolean True if block is found, otherwise false.
     * @access public
     */
    function BlockExists($pathString)
    {
        $blockDeep = 1;
        $consPath = '';
        foreach (explode('/', $pathString) as $b) {
            if ($blockDeep == 1) {
                if (!isset($this->Blocks[$b])) {
                    break;
                }

                $block = &$this->Blocks[$b];
                $consPath = $b;
            } else {
                if (!isset($block->InnerBlock[$b])) {
                    break;
                }

                $block = &$block->InnerBlock[$b];
                $consPath .= '/'.$b;
            }
            $blockDeep++;
        }
        return($pathString == $consPath);
    }

    /**
     * Check if a variable exists in curren block
     *
     * @param string $variable Variable name
     * @return True if variable found, otherwise false
     * @access public
     */
    function VariableExists($variable)
    {
        return stristr($this->CurrentBlock->Content, '{'.$variable.'}');
    }

    /**
     * Resets the values and updates
     *
     * @access  public
     */
    function ResetValues()
    {
        $this->Path = '';
        $this->Content = '';
        $this->MainBlock = '';
        $this->CurrentBlock = '';
        $this->Blocks = array();
    }

}