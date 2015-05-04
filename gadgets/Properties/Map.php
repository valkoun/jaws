<?php
/**
 * Properties URL maps
 *
 * @category   GadgetMaps
 * @package    Properties
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('Properties', 'DefaultAction', 'properties/default');
$GLOBALS['app']->Map->Connect('Properties', 'Index', 'properties/index');
$GLOBALS['app']->Map->Connect('Properties', 
                              'Category', 
                              'properties/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Properties', 
                              'Property', 
                              'property/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Properties', 
								'CategoryMapXML', 
								'categorymapxml/{id}',
								'index.php',
								array(
									'id' =>  '[[:alnum:][:space:][:punct:]]+$',
								  )
								);
$GLOBALS['app']->Map->Connect('Properties', 
								'PropertyMapXML', 
								'propertymapxml/{id}',
								'index.php',
								array(
									'id' =>  '[[:alnum:][:space:][:punct:]]+$',
								  )
								);
$GLOBALS['app']->Map->Connect('Properties', 
                              'Amenity', 
                              'amenity/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
