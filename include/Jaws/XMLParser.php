<?php
/**
 * XML Parser
 *
 * @category   XML
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */

class XMLParser
{
	var $_Usercount = 0;
	var $_Userdata = array();
	var $_State = '';
	var $_endTag = array("URL");

	function parse($input_data, $endTags = '')
	{
        if (is_array($endTags)) {
			$this->_endTag = $endTags;
		} else if (trim($endTags) != '') {
			$this->_endTag = $endTags;
		}
        if ($input_data) {
			$parser = xml_parser_create();

	        xml_set_object ( $parser, $this );

			xml_set_element_handler ( $parser, array ( &$this, 'tagStart' ), array ( &$this, 'tagEnd' ) );
			xml_set_character_data_handler ( $parser, array ( &$this, 'tagContent' ) );
	        
	        xml_parse ( $parser, $input_data );
			xml_parser_free($parser);
		}
		return array($this->_Userdata,$this->_Usercount);
	}

	// FIXME: generalize the XML tags so they work with more sources.
	function tagStart ($parser,$name,$attrib){
		switch ($name) {
		default : {$this->_State=$name;break;}
		}
	}

	function tagEnd ($parser,$name){
		$this->_State = '';
		foreach ($this->_endTag as $endtag) {
			if($name==$endtag) {$this->_Usercount++;}
		}
	}

	function tagContent ($parser, $data) {
		if (!$this->_State) {return;}
		$this->_Userdata[$this->_Usercount][$this->_State] = $data;
	}
}
?>
