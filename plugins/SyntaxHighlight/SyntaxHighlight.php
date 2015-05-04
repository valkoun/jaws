<?php
/**
 * Plugin that highlights code
 *
 * @category   Plugin
 * @package    SyntaxHighlight
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Plugin that replaces all the [code] tags with <code>, so the code will be highlighted
 *
 * @see Jaws_Plugin
 */
require_once JAWS_PATH . 'include/Jaws/Plugin.php';

class SyntaxHighlight extends Jaws_Plugin
{
    /**
     * Main Constructor
     *
     * @access  public
     */
    function SyntaxHighlight()
    {
        $this->_Name = 'SyntaxHighlight';
        $this->LoadTranslation();
        $this->_Description = _t('PLUGINS_SYNTAXHIGHLIGHT_DESCRIPTION');
        $this->_Example = '[code="C#"]<br />using System;<br />public class Foo { }<br />[/code]';
        $this->_IsFriendly = true;
        $this->_Version = '0.3';
    }

    /**
     * Overrides, Get the WebControl of this plugin
     *
     * @access  public
     * @return  object The HTML WebControl
     */
    function GetWebControl($textarea)
    {
        $controlbox =& Piwi::CreateWidget('HBox');

        $langsmap = array('PHP', 'ActionScript', 'ADA', 'Apache',
                          'ASM', 'ASP', 'Bash', 'AppleScript',
                          'Caddcl', 'CadLisp', 'C', 'C#',
                          'CPP', 'CSS', 'Delphi', 'Ruby',
                          'Html4Strict', 'Java', 'JavaScript',
                          'Lisp', 'Lua','NSIS', 'Oobas',
                          'Pascal', 'Perl', 'Python', 'QBasic',
                          'SQL', 'VB', 'VisualFoxPro', 'XML');

        $combo =& Piwi::CreateWidget('Combo', 'languages');
        $combo->SetTitle(_t('PLUGINS_SYNTAXHIGHLIGHT_ADD'));
        $combo->AddEvent(ON_CHANGE, "javascript: if (this[this.selectedIndex].value != '-1') ".
                         "insertTags($textarea, '[code=\'' + this[this.selectedIndex].value + '\']\\n', '\\n[/code]\\n','');");
        $combo->AddOption(_t('PLUGINS_SYNTAXHIGHLIGHT_YOUR_CODE'), '-1');

        foreach ($langsmap as $language) {
            $combo->AddOption(ucfirst($language), ucfirst($language));
        }

        $controlbox->PackStart($combo);

        $button_terminal =& Piwi::CreateWidget('Button', 'addcode', '',
                                $GLOBALS['app']->getSiteURL('/images/stock/stock-terminal.png', true));
        $button_terminal->SetTitle(_t('PLUGINS_SYNTAXHIGHLIGHT_ADD_TERMINAL').' ALT+T');
        $button_terminal->AddEvent(ON_CLICK, "javascript: insertTags($textarea,'[terminal]\\n','\\n[/terminal]\\n','".
                                   _t('PLUGINS_SYNTAXHIGHLIGHT_YOUR_TERMINAL')."');");
        $button_terminal->SetAccessKey('T');

        $controlbox->PackStart($button_terminal);

        return $controlbox;
    }


    /**
     * Simple parses the text and decides if the real parse call should be done
     *
     * @access  public
     * @param   string  $html Html to simple parse
     * @return  boolean
     */
    function NeedsParsing($html)
    {
        if (
            stripos($html, '[code=') !== false ||
            stripos($html, '[terminal]') !== false
        ) {
            return true;
        }
        return false;
    }

    /**
     * Overrides, Parses the text
     *
     * @access  public
     * @param   string  $html Html to Parse
     * @return  string
     */
    function ParseText($html)
    {
        if (!$this->NeedsParsing($html)) {
            return $html;
        }
        $html = preg_replace_callback('#\[terminal\](.*?)\[/terminal\]#si',
                                          array(&$this, 'PrepareTerminal'),
                                          $html);
        $html = preg_replace_callback('#\[code=(.*?)\](.*?)\[/code\]#si',
                                          array(&$this, 'PrepareCode'),
                                          $html);
        return $html;
    }

    /**
     * Callback that prepares the terminal text to xhtml
     *
     * @access  public
     * @param   array  $terminal (Terminal text)
     * @return  string The code in xhtml
     */
    function PrepareTerminal($terminal)
    {
        return '<div class="terminal">'.nl2br($terminal[1]).'</div>';
    }

    /**
     * Callback that prepares the code to be xhtml
     *
     * @access  public
     * @param   array  $code_information Code Data(code and lang)
     * @return  string The code in xhtml
     */
    function PrepareCode($code_information)
    {
        $code = $code_information[2];
        $lang = trim($code_information[1]);

        
        $lang = $GLOBALS['app']->UTF8->html_entity_decode($lang);
        $lang = preg_replace('/[\'\"]/si', '', $lang);
        $lang = $GLOBALS['app']->UTF8->strtolower($lang);

        $valid_lang = array('php','actionscript', 'ada', 'apache',
                            'asm', 'asp', 'bash', 'applescript',
                            'caddcl', 'cadlisp', 'c', 'c#',
                            'cpp', 'css', 'delphi', 'ruby',
                            'html4strict', 'java', 'javascript',
                            'lisp', 'lua','nsis', 'oobas',
                            'pascal', 'perl', 'python', 'qbasic',
                            'sql', 'vb', 'visualfoxpro', 'xml');

        if (in_array($lang, $valid_lang)) {
            //For some fscking reason, geshi applied htmlentities again, so a &lt will be &amp;lt
            $htmltable = get_html_translation_table(HTML_ENTITIES);
            foreach ($htmltable as $key => $value) {
                $code = ereg_replace(addslashes($value), $key, $code);
            }
            require_once JAWS_PATH.'libraries/geshi/geshi.php';
            $geshi = new GeSHi($code, $lang,  JAWS_PATH.'libraries/geshi/geshi');
            $geshi->set_header_type(GESHI_HEADER_DIV);
            $geshi->enable_keyword_links(false);
            $new_code = $geshi->parse_code();
            $new_html = '<div class="code">' . $new_code. '</div>';

           //  $ndew_html = str_replace('<div>', '<div class="code">',
//                                     str_replace('</div>', '</div>', $geshi->parse_code()));
            unset($geshi);
        } else {
            $new_html = "<code>\n";
            $new_html.= $code;
            $new_html.= "</code>\n";
        }

        return $new_html;
    }
}
?>
