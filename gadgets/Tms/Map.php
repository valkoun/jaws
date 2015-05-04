<?php
/**
 * TMS (Theme Management System) Gadget URL maps
 *
 * @category   GadgetMaps
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$GLOBALS['app']->Map->Connect('Tms', 'RSS', 'feeds/rss/themes');
$GLOBALS['app']->Map->Connect('Tms', 
                              'Preview', 
                              'demo/{id}',
                              'index.php',
                              array(
                                    'id' =>  '[[:alnum:][:space:][:punct:]]+$',
                                  )
                              );