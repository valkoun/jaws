<?php
/**
 * Jaws NiceFormat plugin
 *
 * @category   Plugin
 * @package    NiceFormat
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Plugin that replaces any textilize regexp to xhtml code
 *
 * @see Jaws_Plugin
 */
require_once JAWS_PATH . 'include/Jaws/Plugin.php';

class NiceFormat extends Jaws_Plugin
{
    /**
     * Main Constructor
     *
     * @access  public
     */
    function NiceFormat()
    {
        $this->_Name = 'NiceFormat';
        $this->LoadTranslation();
        $this->_Description = _t('PLUGINS_NICEFORMAT_DESCRIPTION');
        $this->_Example = '** '._t('PLUGINS_NICEFORMAT_TEXT_BOLD').' ** <br /> __ '.
            _t('PLUGINS_NICEFORMAT_TEXT_ITALIC').' __<br /> etc...';
        $this->_IsFriendly = true;
        $this->_Version = '0.4';
    }

    /**
     * Overrides, Get the WebControl of this plugin
     *
     * @access  public
     * @return  object The HTML WebControl
     */
    function GetWebControl($textarea)
    {
        $buttonbox =& Piwi::CreateWidget('HBox');
        $buttonbox->SetSpacing(0);

        $bold =& Piwi::CreateWidget('Button', 'bold', '', STOCK_TEXT_BOLD);
        $bold->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '**','**','".
                        _t('PLUGINS_NICEFORMAT_TEXT_BOLD')."');");
        $bold->SetTitle(_t('PLUGINS_NICEFORMAT_TEXT_BOLD').' ALT+B');
        $bold->SetAccessKey('B');

        $italic =& Piwi::CreateWidget('Button', 'italic', '', STOCK_TEXT_ITALIC);
        $italic->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '\'\'','\'\'','".
                          _t('PLUGINS_NICEFORMAT_TEXT_ITALIC')."');");
        $italic->SetTitle(_t('PLUGINS_NICEFORMAT_TEXT_ITALIC').' ALT+I');
        $italic->SetAccessKey('I');

        $hrule =& Piwi::CreateWidget('Button', 'hrule', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/NiceFormat/images/stock-hrule.png');
        $hrule->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '----\\n','','');");
        $hrule->SetTitle(_t('PLUGINS_NICEFORMAT_HRULE').' ALT+H');
        $hrule->SetAccessKey('H');

        $heading1 =& Piwi::CreateWidget('Button', 'heading1', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/NiceFormat/images/stock-heading1.png');
        $heading1->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '=======','=======','".
                            _t('PLUGINS_NICEFORMAT_LEVEL_1')."');");
        $heading1->SetTitle(_t('PLUGINS_NICEFORMAT_LEVEL_1').' ALT+1');
        $heading1->SetAccessKey('1');

        $heading2 =& Piwi::CreateWidget('Button', 'heading2', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/NiceFormat/images/stock-heading2.png');
        $heading2->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '======','======','".
                            _t('PLUGINS_NICEFORMAT_LEVEL_2')."');");
        $heading2->SetTitle(_t('PLUGINS_NICEFORMAT_LEVEL_2').' ALT+2');
        $heading2->SetAccessKey('2');

        $heading3 =& Piwi::CreateWidget('Button', 'heading3', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/NiceFormat/images/stock-heading3.png');
        $heading3->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '=====','=====','".
                            _t('PLUGINS_NICEFORMAT_LEVEL_3')."');");
        $heading3->SetTitle(_t('PLUGINS_NICEFORMAT_LEVEL_3').' ALT+3');
        $heading3->SetAccessKey('3');

        $heading4 =& Piwi::CreateWidget('Button', 'heading4', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/NiceFormat/images/stock-heading4.png');
        $heading4->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '====','====','".
                            _t('PLUGINS_NICEFORMAT_LEVEL_4')."');");
        $heading4->SetTitle(_t('PLUGINS_NICEFORMAT_LEVEL_4').' ALT+4');
        $heading4->SetAccessKey('4');

        $heading5 =& Piwi::CreateWidget('Button', 'heading5', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/NiceFormat/images/stock-heading5.png');
        $heading5->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '===','===','".
                            _t('PLUGINS_NICEFORMAT_LEVEL_5')."');");
        $heading5->SetTitle(_t('PLUGINS_NICEFORMAT_LEVEL_5').' ALT+5');
        $heading5->SetAccessKey('5');

        $listenum =& Piwi::CreateWidget('Button', 'listenum', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/NiceFormat/images/stock-listnum.png');
        $listenum->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '  - ','\\n','".
                            _t('PLUGINS_NICEFORMAT_LIST_NUMERIC')."');");
        $listenum->SetTitle(_t('PLUGINS_NICEFORMAT_LIST_NUMERIC'));

        $listbullet =& Piwi::CreateWidget('Button', 'listbullet', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/NiceFormat/images/stock-listbullet.png');
        $listbullet->AddEvent(ON_CLICK, "javascript: insertTags($textarea, '  * ','\\n','".
                                  _t('PLUGINS_NICEFORMAT_LIST_BULLET')."');");
        $listbullet->SetTitle(_t('PLUGINS_NICEFORMAT_LIST_BULLET'));

        $buttonbox->PackStart($bold);
        $buttonbox->PackStart($italic);
        $buttonbox->PackStart($heading1);
        $buttonbox->PackStart($heading2);
        $buttonbox->PackStart($heading3);
        $buttonbox->PackStart($heading4);
        $buttonbox->PackStart($heading5);
        $buttonbox->PackStart($listenum);
        $buttonbox->PackStart($listbullet);
        $buttonbox->PackStart($hrule);

        return $buttonbox;
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
        $html = preg_replace ('/__(.+?)__/s','<em>\1</em>', $html);  //emphasize
        $html = preg_replace ('/\'\'(.+?)\'\'/s','<em>\1</em>', $html);  //emphasize
        $html = preg_replace ('/\*\*(.+?)\*\*/s','<strong>\1</strong>',$html);  //bold
        $html = preg_replace ('/^(\s)*----+(\s*)$/m',"<hr noshade=\"noshade\" size=\"1\" />", $html); //hr

        //Funny chars ;-D This is a feature in NiceFormat, not a Text_Wiki or Dokuwiki feature
        $html = preg_replace('/\(c\)/i','&copy;',$html);      //  copyrigtht
        $html = preg_replace('/\(r\)/i','&reg;',$html);      //  registered
        $html = preg_replace('/\(tm\)/i','&trade;',$html);      //  trademark

        //Same here
        $html = preg_replace('#&lt;sub&gt;(.*?)&lt;/sub&gt;#is','<sub>\1</sub>',$html);
        $html = preg_replace('#&lt;sup&gt;(.*?)&lt;/sup&gt;#is','<sup>\1</sup>',$html);

        /**
         * Headers
         */
        $html = preg_replace ('/=======(.+?)=======/s','<h1>\1</h1>', $html);  //h1
        $html = preg_replace ('/======(.+?)======/s','<h2>\1</h2>', $html);  //h2
        $html = preg_replace ('/=====(.+?)=====/s','<h3>\1</h3>', $html);  //h3
        $html = preg_replace ('/====(.+?)====/s','<h4>\1</h4>', $html);  //h4
        $html = preg_replace ('/===(.+?)===/s','<h5>\1</h5>', $html);  //h5

        //Lists
        $html = preg_replace("/(\n( {2,}|\t)[\*\-][^\n]+)(\n( {2,}|\t)[^\n]*)*/se",
                             "\"\\n\".\$this->BuildList('\\0')",$html);

        return $html;
    }

    /**
     * Build a list from textilize code
     *
     * @access  private
     * @param   string   $block Code to parse
     * @return  string   The XHTML code
     */
    function BuildList($block)
    {
        //remove 1st newline
        $block = substr($block,1);
        //unescape
        $block = str_replace('\\"','"',$block);

        //walk line by line
        $ret = '';
        $lvl = 0;
        $lines = preg_split("/\n/", $block);

        //build an item array
        $cnt=0;
        $items = array();
        foreach ($lines as $line) {
            //get intendion level
            $lvl  = 0;
            $lvl += floor(strspn($line,' ')/2);
            $lvl += strspn($line,"\t");
            //remove indents
            $line = preg_replace('/^[ \t]+/','',$line);
            //get type of list
            if (substr($line,0,1) == '-')
                $type='ol';
            else
                $type='ul';
            // remove bullet and following spaces
            $line = preg_replace('/^[*\-]\s*/','',$line);
            //add item to the list
            $items[$cnt]['level'] = $lvl;
            $items[$cnt]['type']  = $type;
            $items[$cnt]['text']  = $line;
            //increase counter
            $cnt++;
        }

        $level = 0;
        $opens = array();

        foreach ($items as $item) {
            if ($item['level'] > $level ) {
                //open new list
                $ret .= "\n<".$item['type'].">\n";
                array_push($opens,$item['type']);
            } else if ($item['level'] < $level ) {
                //close last item
                $ret .= "</li>\n";
                for ($i=0; $i<($level - $item['level']); $i++) {
                    //close higher lists
                    $ret .= '</'.array_pop($opens).">\n</li>\n";
                }
            } else if ($item['type'] != $opens[count($opens)-1]){
                //close last list and open new
                $ret .= '</'.array_pop($opens).">\n</li>\n";
                $ret .= "\n<".$item['type'].">\n";
                array_push($opens,$item['type']);
            } else {
                //close last item
                $ret .= "</li>\n";
            }

            //remember current level and type
            $level = $item['level'];

            //print item
            $ret .= '<li class="level'.$item['level'].'">';
            $ret .= '<span class="li">'.$item['text'].'</span>';
        }

        //close remaining items and lists
        while ($open = array_pop($opens)) {
            $ret .= "</li>\n";
            $ret .= '</'.$open.">\n";
        }
        return $ret;
    }
}
?>
