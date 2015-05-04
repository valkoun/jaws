<?php
/**
 * Forms URL maps
 *
 * @category   GadgetForms
 * @package    Forms
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$GLOBALS['app']->Map->Connect('Forms', 'Index', 'forms/index');
$GLOBALS['app']->Map->Connect('Forms', 
                              'Form', 
                              'forms/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );
/*
$GLOBALS['app']->Map->Connect('Forms', 
								'Send', 
								'forms/send/{id}',
								'index.php',
								array(
									'id' =>  '[[:alnum:][:space:][:punct:]]+$',
								  )
								);
*/