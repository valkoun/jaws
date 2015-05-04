<?php
/**
 * Blog - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Blog
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class BlogURLListHook
{
    /**
     * Returns an array with all available items the Menu gadget 
     * can use
     *
     * @access  public
     */
    function Hook()
    {
        $items = array();
        $items[] = array('url'    => $GLOBALS['app']->Map->GetURLFor('Blog', 'DefaultAction'),
                         'title'  => _t('BLOG_NAME'));
        $items[] = array('url'    => $GLOBALS['app']->Map->GetURLFor('Blog', 'Archive'),
                         'title'  => _t('BLOG_ARCHIVE'));
        $items[] = array('url'    => $GLOBALS['app']->Map->GetURLFor('Blog', 'CategoriesList'),
                         'title'  => _t('BLOG_LAYOUT_CATEGORIES'),
                         'title2' => _t('BLOG_CATEGORIES'));
        $items[] = array('url'    => $GLOBALS['app']->Map->GetURLFor('Blog', 'PopularPosts'),
                         'title'  => _t('BLOG_POPULAR_POSTS'));
        $items[] = array('url'    => $GLOBALS['app']->Map->GetURLFor('Blog', 'PostsAuthors'),
                         'title'  => _t('BLOG_POSTS_AUTHORS'));

        //Blog model
        $model      = $GLOBALS['app']->loadGadget('Blog', 'Model');
        $categories = $model->GetCategories();
        if (!Jaws_Error::IsError($categories)) {
            $max_size = 32;
            foreach ($categories as $cat) {
                $url = $GLOBALS['app']->Map->GetURLFor(
                                            'Blog',
                                            'ShowCategory',
                                            array('id' => empty($cat['fast_url'])?
                                                                $cat['id'] : $cat['fast_url']));
                $items[] = array('url'   => $url,
                                 'title' => ($GLOBALS['app']->UTF8->strlen($cat['name']) > $max_size)?
                                             $GLOBALS['app']->UTF8->substr($cat['name'], 0, $max_size) . '...' :
                                             $cat['name']);
            }
        }

        $entries = $model->GetEntries('');
        if (!Jaws_Error::IsError($entries)) {
            $max_size = 32;
            foreach ($entries as $entry) {
                $url = $GLOBALS['app']->Map->GetURLFor(
                                            'Blog',
                                            'SingleView',
                                            array('id' => empty($entry['fast_url'])?
                                                                $entry['id'] : $entry['fast_url']));
                $items[] = array('url'   => $url,
                                 'title' => ($GLOBALS['app']->UTF8->strlen($entry['title']) > $max_size)?
                                             $GLOBALS['app']->UTF8->substr($entry['title'], 0, $max_size) . '...' :
                                             $entry['title']);
            }
        }
        return $items;
    }
	
}
