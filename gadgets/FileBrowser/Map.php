<?php
/**
 * FileBrowser URL maps
 *
 * @category   GadgetMaps
 * @package    FileBrowser
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Map->Connect('FileBrowser',
                              'DefaultAction',
                              'files');
$GLOBALS['app']->Map->Connect('FileBrowser',
                              'Display',
                              'files/{path}/page/{page}',
                              '',
                              array('path' => '.*',
                                    'page' => '[[:digit:]]+$')
                              );
$GLOBALS['app']->Map->Connect('FileBrowser',
                              'Display',
                              'files/{path}',
                              '',
                              array('path' => '.*')
                              );
$GLOBALS['app']->Map->Connect('FileBrowser',
                              'FileInfo', 
                              'file/info/{id}',
                              '',
                              array('id' =>  '[[:alnum:][:space:][:punct:]]+$',)
                              );
