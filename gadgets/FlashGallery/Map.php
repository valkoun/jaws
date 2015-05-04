<?php
/**
 * FlashGallery URL maps
 *
 * @category   GadgetMaps
 * @package    FlashGallery
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('FlashGallery', 
                              'GalleryXML', 
                              'galleryxml/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );