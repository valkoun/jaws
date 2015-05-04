<?php
/**
 * Maps URL maps
 *
 * @category   GadgetMaps
 * @package    Maps
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('Maps', 'DefaultAction', 'maps/default');
$GLOBALS['app']->Map->Connect('Maps', 'Index', 'maps/index');
$GLOBALS['app']->Map->Connect('Maps', 
                              'Map', 
                              'maps/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Maps', 
								'GoogleMapXML', 
								'googlemapsxml/{id}',
								'index.php',
								array(
									'id' =>  '[[:alnum:][:space:][:punct:]]+$',
								  )
								);