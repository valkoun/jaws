<?php
/**
 * Ecommerce URL maps
 *
 * @category   GadgetMaps
 * @package    Ecommerce
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('Ecommerce', 
                              'EcommerceXML', 
                              'ecommercexml/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );