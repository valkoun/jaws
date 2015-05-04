<?php
/**
 * Search Actions file
 *
 * @category   GadgetMaps
 * @package    Search
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Map->Connect('Search', 'Box',         'search');
$GLOBALS['app']->Map->Connect('Search', 'SimpleBox',   'search/simple');
$GLOBALS['app']->Map->Connect('Search', 'AdvancedBox', 'search/advanced');
