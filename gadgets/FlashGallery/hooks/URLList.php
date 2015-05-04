<?php
/**
 * FlashGallery - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FlashGalleryURLListHook
{
    /**
     * Returns an array with all available items the FlashGallery gadget 
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
     * Returns the URL that is used to edit gadget's page 
     * @param   string  $action  Action
     * @param   int  $in  id of record we want to look for
     * @param   bool     $layout   is this a layout action?
     *
     * @access  public
     */
    function GetEditPage($action = null, $id = null, $layout = false)
    {
		if ((!is_null($action) && !empty($action))) {
			if ($layout === true) {
				switch ($action) {
					case "Gallery": 
					case "Slideshow": 
					case "ShowOne": 
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=FlashGallery&action=view'.(!is_null($id) ? '&id='.$id : '');
						break;
					default:
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=FlashGallery&action=Admin';
						break;
				}
			} else {
				switch ($action) {
					default:
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=FlashGallery&action=Admin';
						break;
				}
			}
		}
		return false;
	}

    /**
     * Returns an array with all Quick Add Forms of Gadget 
     * can use
     *
     * @access  public
     */
    function GetQuickAddForms($account = false)
    {
		$GLOBALS['app']->Registry->LoadFile('FlashGallery');
		$GLOBALS['app']->Translate->LoadTranslation('FlashGallery', JAWS_GADGET);
        $result   = array();
        if ($account === false) {
			$result[] = array('name'   => _t('FLASHGALLERY_QUICKADD_ADDFLASHGALLERY'),
							'method' => 'AddFlashGallery');
		}
		$result[] = array('name'   => _t('FLASHGALLERY_QUICKADD_ADDPOST'),
                        'method' => 'AddPost');
		
        return $result;
    }
}
