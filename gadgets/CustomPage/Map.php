<?php
/**
 * CustomPage URL maps
 *
 * @category   GadgetMaps
 * @package    CustomPage
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('CustomPage', 'DefaultAction', 'page/default');
$GLOBALS['app']->Map->Connect('CustomPage', 'Index', 'page/index');
$GLOBALS['app']->Map->Connect('CustomPage', 'GoogleSitemap', 'page/sitemap');
$GLOBALS['app']->Map->Connect('CustomPage', 
                              'Page', 
                              'page/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );