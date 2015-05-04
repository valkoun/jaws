<?php
/**
 * Create and manage a blog.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Blog
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class BlogInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about blog gadget
     *
     * @access  public
     */
    function BlogInfo()
    {
        parent::Init('Blog');
        $this->GadgetName(_t('BLOG_NAME'));
        $this->GadgetDescription(_t('BLOG_DESCRIPTION'));
        $this->GadgetVersion('0.8.8');
        $this->Doc('gadget/Blog');
        $this->ListURL(true);

        $acls = array(
            'default',
            'AddEntries',
            'ModifyOthersEntries',
            'DeleteEntries',
            'PublishEntries',
            'ManageComments',
            'ManageTrackbacks',
            'ManageCategories',
            'Settings',
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
        $this->Provides(XMLRPC, 'BlogXmlRpc.php', _t('BLOG_XMLRPC'));
        //$this->Provides(WEBSERVICE, 'BlogWebservice.php', _t('BLOG_WEBSERVICE'));
        //$this->Provides(WSDL, 'BlogWebservice.php?wsdl', _t('BLOG_WSDL'));
    }
}