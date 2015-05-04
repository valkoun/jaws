<?php
/**
 * Faq URL maps
 *
 * @category   GadgetMaps
 * @package    Faq
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Map->Connect('Faq', 'View', 'faq');
$GLOBALS['app']->Map->Connect('Faq',
                              'ViewQuestion',
                              'faq/question/{id}',
                              '', array('id' =>  '[[:alnum:][:space:][:punct:]]+$',)
                              );
$GLOBALS['app']->Map->Connect('Faq',
                              'ViewCategory',
                              'faq/category/{id}',
                              '', array('id' =>  '[[:alnum:][:space:][:punct:]]+$',)
                              );
