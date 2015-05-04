<?php
/*
 * Piwi.php - Main configuration of Piwi
 *
 * @version  $Id $
 * @author   Pablo Fischer <pablo@pablo.com.mx>
 *
 * <c> Pablo Fischer 2004
 * <c> Piwi
 */
function piwi_class_exist($class_name, $autoload = false) {
    return ((substr(phpversion(), 0, 1) > 4) ? class_exists($class_name, $autoload) : class_exists($class_name));
}

if (!defined('PIWI_PATH')) {
    define('PIWI_PATH', dirname(__FILE__));
}

if (!piwi_class_exist('Widget', false)) {
    require PIWI_PATH . '/Widget/Widget.php';
}

if (!defined('STOCK_ADD')) {
    require PIWI_PATH . '/Widget/Bin/ImageStocks.php';
}

if (!defined('ON_CLICK')) {
    require PIWI_PATH . '/JS/JSEnums.php';
}

if (!piwi_class_exist('JSEvent', false)) {
    require PIWI_PATH . '/JS/JSEvent.php';
}

if (!piwi_class_exist('JSValidator', false)) {
    require PIWI_PATH . '/JS/JSValidator.php';
}

if (!piwi_class_exist('PiwiColors', false)) {
    require PIWI_PATH . '/Utils/PiwiColors.php';
}

if (!piwi_class_exist('Piwi', false)) {
    require PIWI_PATH . '/Utils/PiwiSmart.php';
}
?>