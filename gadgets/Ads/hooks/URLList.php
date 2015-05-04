<?php
/**
 * Ads - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Ads
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class AdsURLListHook
{
    /**
     * Returns an array with all available items the Ads gadget 
     * can use
     *
     * @access  public
     */
    function Hook()
    {
        $urls   = array();
		/*
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('FlashGallery', 'Index'),
                        'title' => _t('FLASHGALLERIES_TITLE_PROPERTY_INDEX'));

        //Load model
        $model = $GLOBALS['app']->loadGadget('FlashGallery', 'Model');
        $pages = $model->GetPropertyParents();
        if (!Jaws_Error::IsError($pages)) {
            $max_size = 20;
            foreach($pages as $p) {
                if ($p['propertyparentactive'] == 'Y') {
                    if (isset($p['propertyparentfast_url'])) {
						$id = $p['propertyparentfast_url'];
					} else {
						$id = $p['propertyparentid'];
					}
                    $url   = $GLOBALS['app']->Map->GetURLFor('FlashGallery', 'Category', array('id' => $id));
                    $urls[] = array('url'   => $url,
                                    'title' => ($p['propertyparentcategory_name'])
					);
                }
            }
        }
		
		*/
        return $urls;
    }

    /**
     * Returns an array with all Quick Add Forms of Gadget 
     * can use
     *
     * @access  public
     */
    function GetQuickAddForms($account = false)
    {
		$GLOBALS['app']->Registry->LoadFile('Ads');
		$GLOBALS['app']->Translate->LoadTranslation('Ads', JAWS_GADGET);
        $result   = array();
        if ($account === false) {
			$result[] = array('name'   => _t('ADS_QUICKADD_ADDADPARENT'),
							'method' => 'AddAdParent');
		}
		$result[] = array('name'   => _t('ADS_QUICKADD_ADDAD'),
                        'method' => 'AddAd');
		
        return $result;
    }
}
