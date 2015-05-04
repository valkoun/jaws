<?php
/**
 * Store URL maps
 *
 * @category   GadgetMaps
 * @package    Store
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('Store', 'DefaultAction', 'store/default');
$GLOBALS['app']->Map->Connect('Store', 'Index', 'store/index');
$GLOBALS['app']->Map->Connect('Store', 
                              'Category', 
                              'products/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Store', 
                              'Product', 
                              'product/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Store', 
								'CategoryXML', 
								'categoryxml/{id}',
								'index.php',
								array(
									'id' =>  '[[:alnum:][:space:][:punct:]]+$',
								  )
								);
$GLOBALS['app']->Map->Connect('Store', 
								'ProductXML', 
								'productxml/{id}',
								'index.php',
								array(
									'id' =>  '[[:alnum:][:space:][:punct:]]+$',
								  )
								);
$GLOBALS['app']->Map->Connect('Store', 
                              'Attribute', 
                              'attribute/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
$GLOBALS['app']->Map->Connect('Store', 
                              'Brand', 
                              'brand/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
