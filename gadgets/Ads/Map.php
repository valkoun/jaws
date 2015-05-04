<?php
/**
 * Ads URL maps
 *
 * @category   GadgetMaps
 * @package    Ads
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('Ads', 
                              'AdsXML', 
                              'adsxml/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Ads', 
                              'Category', 
                              'ads/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Ads', 
                              'Ad', 
                              'ad/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
