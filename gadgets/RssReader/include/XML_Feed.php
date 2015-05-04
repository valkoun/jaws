<?php
/**
 * XML_Feed_Reader
 *
 * @author        Ali Fazelzadeh <afz@php.net>
 * @copyright     2007-2008 Ali Fazelzadeh
 * @license       http://www.gnu.org/copyleft/lesser.html
 */
require_once 'XML/Parser.php';

class XML_Feed extends XML_Parser
{
    var $cache_dir = '';
    var $file_mode = null;
    var $cache_time = 3600; // 60 * 60 * 1
    //--------------------------------------
    var $activeTag   = '';
    var $level_1_tag = '';
    var $level_2_tag = '';
    //--------------------------------------
    var $valid_feed_types   = array('FEED', 'RSS', 'RDF', 'OPML');
    var $level_1_valid_tags = array('FEED', 'CHANNEL', 'HEAD');
    var $level_2_valid_tags = array('ENTRY', 'ITEM', 'OUTLINE');
    //--------------------------------------
    var $feed      = array('channel'=> array(), 'items' => array());
    var $channel   = array();
    var $item      = array();
    //--------------------------------------
    var $_params   = array();

    /**
     * valid encodings
     *
     * @var array
     */
    var $_validEncodings = array('ISO-8859-1', 'UTF-8', 'US-ASCII');

    /**
     * Constructor
     *
     */
    function XML_Feed()
    {
        $this->XML_Parser();
    }


    /**
     * Sets the input xml file to be parsed
     *
     * @param    string      Filename (full path)
     * @return   resource    handle of the given file
     * @access   public
     */
    function setInputFile($file)
    {
        require_once 'HTTP/Request.php';
        $httpRequest = new HTTP_Request($file, $this->_params);
        $httpRequest->setMethod(HTTP_REQUEST_METHOD_GET);
        $resRequest  = $httpRequest->sendRequest();
        if (PEAR::isError($resRequest)) {
            return $resRequest;
        } elseif ($httpRequest->getResponseCode() <> 200) {
            return $this->raiseError('HTTP response error', HTTP_REQUEST_ERROR_RESPONSE);
        }

        $data = $httpRequest->getResponseBody();
        if (version_compare(PHP_VERSION, '5.0.0', '<')) {
            if (preg_match('/<?xml.*encoding=[\'"](.*?)[\'"].*?>/m', $data, $matches)) {
                $srcenc = strtoupper($matches[1]);
                if (!in_array($srcenc, $this->_validEncodings)) {
                    if (function_exists('iconv')) {
                        $data = @iconv($srcenc,'UTF-8', $data);
                    } elseif (function_exists('mb_list_encodings') && in_array($srcenc, array_map('strtoupper', mb_list_encodings()))) {
                        $data = @mb_convert_encoding($data, 'UTF-8', $srcenc);
                    }
                }
            }
        }

        $this->setInputString($data);
        return;
    }


    /**
     */
    function fetch($feed_url)
    {
        $this->feedFree();
        if (!empty($this->cache_dir) && $this->cache_time!=0) { // cache enabled?
            $cache_file = $this->cache_dir . '/feed_' . md5($feed_url);
            $timedif = @(time() - (file_exists($cache_file) ? filemtime($cache_file) : 0));
            if ($timedif < $this->cache_time) { // is cached file fresh?
                $this->loadFile($cache_file);
            } else {
                $res = $this->setInputFile($feed_url);
                if (PEAR::isError($res) || PEAR::isError($res = $this->Parse())) {
                    $this->feedFree();
                    return $res;
                }
                $this->saveFile($cache_file);
            }
        } else {
            $res = $this->setInputFile($feed_url);
            if (PEAR::isError($res) || PEAR::isError($res = $this->Parse())) {
                $this->feedFree();
                return $res;
            }
        }

        return true;
    }


    /**
     */
    function ex_array_map($func, $arr)
    {
        $newArr = array();
        foreach ($arr as $key => $value) {
            $newArr[$key] = (is_array($value)? $this->ex_array_map($func, $value) : $func($value));
        }
        return $newArr;
    }


    /**
     */
    function saveFile($cache_file)
    {
        if (!isset($this->feed)) {
            return false;
        }

        $serialized = serialize($this->ex_array_map('base64_encode', $this->feed));
        if (Jaws_Utils::file_put_contents($cache_file, $serialized)) {
            return true;
        } else {
            return $this->raiseError("Fail to save stream with file_put_contents('$cache_file',...).");
        }
    }


    /**
     */
    function loadFile($cache_file)
    {
        if (file_exists($cache_file)) {
            $feed_content = file($cache_file);
            $feed_content = implode("",$feed_content);
            $feed_content = str_replace("\r\n","\n",$feed_content);
            $feed_content = str_replace("\r","",$feed_content);
            $this->feed = unserialize($feed_content);
            $this->feed = $this->ex_array_map('base64_decode', $this->feed);
            unset($feed_content);
        } else {
            return $this->raiseError("Fail to open '$cache_file', not found");
        }
    }


    /**
     */
    function startHandler($parser, $tagName, $attrs)
    {
        if (substr($tagName, 0, 4) == "RSS:" || substr($tagName, 0, 4) == "RDF:") {
            $tagName = substr($tagName, 4);
        }

        if (substr($tagName, 0, 5) == "ATOM:") {
            $tagName = substr($tagName, 5);
        }

        $this->feed_type = empty($this->feed_type)? $tagName : $this->feed_type;
        if (!in_array($this->feed_type, $this->valid_feed_types)) {
            return false;
        }

        switch ($tagName) {
            case 'CHANNEL':
            case 'FEED':
            case 'HEAD':
            case 'BODY':
                $this->level_1_tag = empty($this->level_1_tag)? $tagName : $this->level_1_tag;
                break;

            case 'ENTRY':
            case 'ITEM':
            case 'IMAGE':
                if (in_array($this->level_1_tag, $this->level_1_valid_tags) ||
                    (empty($this->level_1_tag) && $this->feed_type == 'RDF')) {
                    $this->level_2_tag = empty($this->level_2_tag)? $tagName : $this->level_2_tag;
                }
                break;

            case 'LINK':
                if ($this->level_1_tag == 'FEED') {
                    if ($this->level_2_tag == 'ENTRY') {
                        $this->_add('item', 'link', $attrs['HREF']);
                        break;
                    } elseif(empty($this->level_2_tag)) {
                        $this->_add('channel', 'link', $attrs['HREF']);
                        break;
                    }
                }
                $this->activeTag = $tagName;
                break;

            case 'OUTLINE':
                if ($this->level_1_tag == 'BODY' && empty($this->level_2_tag)) {
                    $this->_add('item', 'title', (isset($attrs['TITLE'])? $attrs['TITLE'] : $attrs['TEXT']));

                    if (array_key_exists('URL', $attrs)) {
                        $this->_add('item', 'link', $attrs['URL']);
                    } elseif (array_key_exists('XMLURL', $attrs)) {
                        $this->_add('item', 'link', $attrs['XMLURL']);
                    }

                    if (array_key_exists('DESCRIPTION', $attrs)) {
                        $this->_add('item', 'description', $attrs['DESCRIPTION']);
                    }

                    if (array_key_exists('CREATED', $attrs)) {
                        $this->_add('item', 'date', $attrs['CREATED']);
                    }

                    $this->feed['items'][] = $this->item;
                    unset($this->item);
                    break;
                }
                $this->activeTag = $tagName;
                break;

            default:
                $this->activeTag = $tagName;
        }
    }


    /**
     */
    function endHandler($parser, $tagName)
    {
        if (substr($tagName, 0, 4) == "RSS:" || substr($tagName, 0, 4) == "RDF:") {
            $tagName = substr($tagName, 4);
        }

        if (substr($tagName, 0, 5) == "ATOM:") {
            $tagName = substr($tagName, 5);
        }

        switch ($tagName) {
            case 'ENTRY':
            case 'ITEM':
            case 'IMAGE':
                if (in_array($this->level_2_tag, $this->level_2_valid_tags)) {
                    $this->feed['items'][] = $this->item;
                    unset($this->item);
                }
                $this->level_2_tag = '';
                break;

            case 'CHANNEL':
            case 'FEED':
            case 'HEAD':
            case 'BODY':
                if (in_array($this->level_1_tag, $this->level_1_valid_tags)) {
                    $this->feed['channel'] = $this->channel;
                    unset($this->channel);
                }
                $this->level_1_tag = '';
                break;
        }

        if ($this->activeTag == $tagName) {
            $this->activeTag = '';
        }
    }


    /**
     */
    function cdataHandler($parser, $cdata)
    {
        switch ($this->activeTag) {
            case 'TITLE':
            case 'LINK':
            case 'DESCRIPTION':
                if (in_array($this->level_2_tag, $this->level_2_valid_tags)) {
                    $this->_add('item', strtolower($this->activeTag), $cdata);
                } elseif (empty($this->level_2_tag) && in_array($this->level_1_tag, $this->level_1_valid_tags)) {
                    $this->_add('channel', strtolower($this->activeTag), $cdata);
                }
                break;

            case 'CREATED':
            case 'DC:DATE':
            case 'DCTERMS:MODIFIED':
            case 'ISSUED':
            case 'MODIFIED':
            case 'PUBDATE':
            case 'PUBLISHED':
            case 'UPDATED':
                if (in_array($this->level_2_tag, $this->level_2_valid_tags)) {
                    $this->_add('item', 'date', $cdata);
                }
                break;

            case 'CONTENT':
            case 'DC:DESCRIPTION':
                if (in_array($this->level_2_tag, $this->level_2_valid_tags)) {
                    $this->_add('item', 'description', $cdata);
                }
                break;

            case 'AUTHOR':
            case 'DC:CREATOR':
                if (in_array($this->level_2_tag, $this->level_2_valid_tags)) {
                    $this->_add('item', 'author', $cdata);
                }
                break;
        }
    }


    /**
     */
    function _add($type, $field, $value)
    {
        if (empty($this->{$type}) || empty($this->{$type}[$field])) {
            $this->{$type}[$field] = $value;
        } else {
            $this->{$type}[$field] .= $value;
        }

        $this->last = $this->{$type};
    }


    /**
     */
    function feedFree()
    {
        $insideTag = '';
        $activeTag = '';
        unset($this->feed);
        unset($this->channel);
        unset($this->item);
        $this->free();
    }


    /**
     */
    function setParams($params = array())
    {
        $this->_params = $params;
        return true;
    }

}
