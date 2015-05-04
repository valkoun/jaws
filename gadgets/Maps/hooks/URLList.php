<?php
/**
 * Maps - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class MapsURLListHook
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
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Maps', 'Index'),
                        'title' => _t('MAPS_TITLE_PAGE_INDEX'));

        //Load model
        $model = $GLOBALS['app']->loadGadget('Maps', 'Model');
        $pages = $model->GetMaps();
        if (!Jaws_Error::IsError($pages)) {
            $max_size = 20;
            foreach($pages as $p) {
                if ($p['active'] == 'Y') {
                    $url   = $GLOBALS['app']->Map->GetURLFor('Maps', 'Map', array('id' => $p['id']));
                    $urls[] = array('url'   => $url,
                                    'title' => ($p['title'])
					);
                }
            }
        }
        return $urls;
    }
}
