<?php
/**
 * Faq - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Faq
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class FaqURLListHook
{
    /**
     * Returns an array with all available items the Menu gadget 
     * can use
     *
     * @access  public
     */
    function Hook()
    {
        $urls   = array();
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Faq', 'View'),
                        'title' => _t('FAQ_NAME'));

        //Load model
        $model      = $GLOBALS['app']->loadGadget('Faq', 'Model');
        $categories = $model->GetCategories();
        if (!Jaws_Error::isError($categories)) {
            $max_size = 20;
            foreach ($categories as $category) {
                $url = $GLOBALS['app']->Map->GetURLFor('Faq', 'ViewCategory', array('id' => $category['id']));
                $urls[] = array('url'   => $url,
                                'title' => ($GLOBALS['app']->UTF8->strlen($category['category']) > $max_size)?
                                            $GLOBALS['app']->UTF8->substr($category['category'], 0, $max_size).'...' :
                                            $category['category']);
            }
        }
        return $urls;
    }
}
