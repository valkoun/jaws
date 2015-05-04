<?php
/**
 * AlbumCover plugin. Gets Album Cover from Amazon.com
 *
 * @category   Plugin
 * @package    AlbumCover
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Amir Mohammad Saied <amir@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/**
 * Plugin that adds the cover of an AlbumCover
 *
 * @see Jaws_Plugin
 */
require_once JAWS_PATH . 'include/Jaws/Plugin.php';

class AlbumCover extends Jaws_Plugin
{
    /**
     * Main Constructor
     *
     * @access  public
     */
    function AlbumCover()
    {
        $this->_Name = 'AlbumCover';
        $this->LoadTranslation();
        $this->_Description = _t('PLUGINS_ALBUMCOVER_DESCRIPTION');
        $this->_Example = "[AlbumCover Artist='The Beatles' Album='Abbey Road']";
        $this->_IsFriendly = true;
        $this->_Version = '0.4';
        $this->_AccessKey = 'A';
    }

    /**
     * Installs the plugin
     *
     * @access  public
     * @return  boolean True on success and Jaws_Error otherwise
     */
    function InstallPlugin()
    {
        $new_dir = JAWS_DATA . 'AlbumCover' . DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), $this->_Name);
        }

        // Registry key
        $GLOBALS['app']->Registry->NewKey('/plugins/AlbumCover/devtag', 'MY DEV TAG');

        return true;
    }

    /**
     * Uninstall AlbumCover
     *
     * @access  public
     * @return  True on success and Jaws_Error on failure
     */
    function UninstallPlugin()
    {
        $GLOBALS['app']->Registry->DeleteKey('/plugins/AlbumCover/devtag');
        return true;
    }

    /**
     * Overrides, Get the WebControl of this plugin
     *
     * @access  public
     * @return  object The HTML WebControl
     */
    function GetWebControl($textarea)
    {
        $button =& Piwi::CreateWidget('Button', 'addalbumcover', '',
                        $GLOBALS['app']->getJawsURL() .'/plugins/AlbumCover/images/stock-album.png');
        $button->SetTitle(_t('PLUGINS_ALBUMCOVER_ADD').' ALT+A');
        $button->AddEvent(ON_CLICK, "javascript: insertTags({$textarea},'[AlbumCover Artist=\'\' Album=\'\']','','');");
        $button->SetAccessKey('A');
        return $button;
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
        $AlbumPattern = '@\[AlbumCover\s+Artist=\'(.*?)\'\s+Album=\'(.*?)\'\]@sm';
        $new_html = preg_replace_callback($AlbumPattern, array(&$this, 'GetAlbumCover'), $html);
        return $new_html;
    }

    /**
     * Callback that searches for the Album
     *
     * @access  public
     * @param   array  $data Album data(artist and album)
     * @return  string The album image
     */
    function GetAlbumCover($data)
    {
        $albumDir = JAWS_DATA . 'AlbumCover/';

        if (!isset($data[1]) || !isset($data[2]) || empty($data[1]) || empty($data[2])) {
            return '';
        }

        $Artist = $data[1];
        $Album  = $data[2];
        $img = strtolower(str_replace(' ', '', $Artist)). '_' .strtolower(str_replace(' ', '', $Album)).'.jpg';

        ///FIXME needs error checking
        if (!$rs = is_file($albumDir.$img)) {
            $amazonImg = $this->GetAlbumCoverFromAmazon($Artist, $Album);
            if (empty($amazonImg)) {
                $img = 'images/unknown.png';
            } elseif (!@copy($amazonImg, $albumDir.$img)) {
                $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
                //FIXME: Notify that can't copy image to cache...
                $img = $xss->parse($amazonImg);
            } else {
                $img = JAWS_DATA . 'AlbumCover/' . $img;
            }
        } else {
            $img = JAWS_DATA . 'AlbumCover/' . $img;
        }

        $text = $Artist . ' - ' . $Album;
        return '<img src="' . $img . '" alt="' . $text . '" title="' . $text . '" />';
    }

    /**
     * Get a string and Will make it all lower case and eliminates spaces
     *
     * @access  public
     * @param   string  $string The row string
     * @return  string  The parsed string
     */
    function ToLowerWithoutSpaces($string)
    {
        $string = strtolower($string);
        $string = str_replace(' ', '', $string);

        return $string;
    }

    /**
     * Search for the album cover in Amazon
     *
     * @access  public
     * @param   string  $Artist Artist to search for
     * @param   string  $Album  Album to search for
     * @return  string  The name of the image album
     */
    function GetAlbumCoverFromAmazon($Artist, $Album)
    {
        $wsdl = 'http://soap.amazon.com/schemas3/AmazonWebServices.wsdl';
        require_once 'SOAP/Client.php';
        $service = new SOAP_WSDL($wsdl);
        $page=1;
        $proxy = $service->getProxy();

        $devtag = $GLOBALS['app']->Registry->Get('/plugins/AlbumCover/devtag');

        $params = array(
            'artist'    => htmlentities($Artist),
            'keywords'  => htmlentities($Album),
            'mode'      => 'music',
            'page'      => $page,
            'tag'       => 'webservices-20',
            'devtag'    => $devtag,
            'type'      => 'lite'
        );

        $result = $proxy->ArtistSearchRequest($params);
        $pages  = isset($result->TotalPages) ? $result->TotalPages : 0;

        $bestMatch = '';
        $lowerArtist = $this->ToLowerWithoutSpaces($Artist);
        $lowerAlbum  = $this->ToLowerWithoutSpaces($Album);
        while ($page <= $pages) {
            foreach ($result->Details as $r) {
                if ($this->ToLowerWithoutSpaces($r->ProductName) == $lowerAlbum) {
                    foreach ($r->Artists as $a) {
                        if ($this->ToLowerWithoutSpaces($a) == $lowerArtist) {
                            $bestMatch = $r->ImageUrlMedium;
                            break 3;
                        }
                    }
                }
            }
            $params['page'] = $page++;
            $result = $proxy->ArtistSearchRequest($params);
        }

        return $bestMatch;
    }
}
