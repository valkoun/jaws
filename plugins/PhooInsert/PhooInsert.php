<?php
/**
 * PhooInsert is a plugin that let's insert easily 
 * a photo from Phoo gadget to any gadget that admits plugins
 *
 * @category   Plugin
 * @package    PhooInsert
 * @author     Jose Francisco Garcia Martinez <jfgarcia.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'include/Jaws/Plugin.php';

class PhooInsert extends Jaws_Plugin 
{
    /**
     * Main Constructor
     *
     * @access   public
     */
    function PhooInsert () 
    {
        $this->_Name = "PhooInsert";
        $this->LoadTranslation ();
        $this->_Description = _t("PLUGINS_PHOOINSERT_DESCRIPTION");
        $this->_Example = '[phoo album="1" picture="1" title="everybody" class="image" size="Medium" linked="Yes"]';
        $this->_IsFriendly = true;
        $this->_Version = "0.6.2";
    }

    /**
     * Overrides, Get the WebControl of this plugin
     *
     * @access   public
     * @return   object The HTML WebControl
     */
    function GetWebControl ($textarea) 
    {
        $popbutton =& Piwi::CreateWidget('Button','popbutton', '', 'plugins/PhooInsert/images/stock-Phoo.png');
        $caption =_t('PLUGINS_PHOOINSERT_PHOO_GALLERY');
        $url = '../../' . BASE_SCRIPT. '?gadget=Phoo&amp;action=BrowsePhoo';
        $popbutton->AddEvent (ON_CLICK, "javascript: return SelectImage($textarea,'$caption','$url')");
        $popbutton->SetTitle (_t('PLUGINS_PHOOINSERT_ADD_PICTURE'));
        $popbutton->SetAccessKey ('P');
        // GreyBox support
        $files = array();
        $files[] = 'plugins/PhooInsert/resources/PhooInsert.js';
        $files[] = 'libraries/greybox/AJS.js';
        $files[] = 'libraries/greybox/AJS_fx.js';
        $files[] = 'libraries/greybox/gb_scripts.js';

        //trick for ticket #820.
        $is_friendly_editor = strpos($textarea,"'");
        if ($is_friendly_editor===false){ 
            $files[] = 'libraries/greybox/gb_styles.css'; //this don't work in Friendly Editor
        } else {
            $script = '<style> @import url(libraries/greybox/gb_styles.css);</style>'; //this works with Friendly Editor and not in classic editor, because the Toolbar widget (see ticket #820)
            $popbutton->AddJS($script);
        }
        $popbutton->AddFiles($files);
        return $popbutton;
        // end GreyBox support 
    }

    /**
     * Overrides, Parses the text
     *
     * @access    public
     * @param   string  $html Html to Parse
     * @return  string
     */
    function ParseText ($html) 
    {
        if (file_exists (JAWS_PATH.'gadgets/Phoo/Model.php') && Jaws_Gadget::IsGadgetInstalled ('Phoo')) {
            require_once JAWS_PATH.'gadgets/Phoo/Model.php';
            require_once JAWS_PATH . 'include/Jaws/Image.php';
            $howMany = preg_match_all ("#\[phoo album=\"(.*?)\" picture=\"(.*?)\" title=\"(.*?)\" class=\"(.*?)\" size=\"(.*?)\" linked=\"(.*?)\"\]#si", $html, $matches);
            $new_html = $html;
            $url = $GLOBALS['app']->getSiteURL();
            for ($i = 0; $i < $howMany; $i++) {
                $albumid = $matches[1][$i];
                $imageid = $matches[2][$i];
                $title   = $matches[3][$i];
                $clase   = $matches[4][$i];
                $size    = $matches[5][$i];
                $linked  = $matches[6][$i];
                $image = PhooModel::GetImageEntry($imageid); 
                if (strtoupper($size)=='THUMB') {
                    $filename = $image['thumb'];
                } elseif (strtoupper($size)=='MEDIUM') {
                    $filename = $image['medium'];
                } else {
                    $filename= $image['filename'];
                }
                $size_px = Jaws_Image::GetImageSize($filename);
                if (strtoupper($linked) == 'YES' ){
                    $img_lnk = $GLOBALS['app']->Map->GetURLFor('Phoo','ViewImage', array('id' => $imageid, 'albumid' => $albumid));
                    $new_text = '<a href="'.$img_lnk.'" ><img src="'.$url.'/'.$filename.'" title="'.$title.'"  alt="'.$title.'" class="'.$clase.'" height="'.$size_px['height'].'" width="'.$size_px['width'].'"/></a>' ;
                } else {
                    $new_text = '<img src="'.$url.'/'.$filename.'" title="'.$title.'" alt="'.$title.'" class="'.$clase.'" height="'.$size_px['height'].'" width="'.$size_px['width'].'" />';
                }
                $textToReplace="#\[phoo album=\"".$albumid."\" picture=\"".$imageid."\" title=\"".$title."\" class=\"".$clase."\" size=\"".$size."\" linked=\"".$linked."\"\]#";
                $new_html = preg_replace ($textToReplace, $new_text, $new_html);
            }
            return $new_html;
        }
        return $html;
    }
}