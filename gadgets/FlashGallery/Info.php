<?php
/**
 * Create and manage photo Galleries.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FlashGalleryInfo extends Jaws_GadgetInfo
{
    function FlashGalleryInfo()
    {
        parent::Init('FlashGallery');
        $this->GadgetName(_t('FLASHGALLERY_NAME'));
        $this->GadgetDescription(_t('FLASHGALLERY_DESCRIPTION'));
        $this->GadgetVersion('0.1.1');
        //$this->Doc('gadgets/FlashGallery');

        $acls = array(
            'default',
            'OwnFlashGallery',
            'ManageFlashGalleries',
            'ManagePublicFlashGalleries'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}
