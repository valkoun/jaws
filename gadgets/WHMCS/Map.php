<?php
/**
 * WHMCS URL maps
 *
 * @category   GadgetMaps
 * @package    WHMCS
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('WHMCS', 
                              'API', 
                              'whmcsapi/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
