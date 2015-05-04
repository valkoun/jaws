<?php
/**
 * Box.php - Box Class
 *
 * @version  $Id $
 * @author   Pablo Fischer <pablo@pablo.com.mx>
 *
 * <c> Pablo Fischer 2004
 * <c> Piwi
 */
require_once PIWI_PATH . '/Widget/Container/Container.php';

class Box extends Container
{
    /**
     * Creates the box
     *
     * @access public
     */
    function Box()
    {
        $this->setDirection('');
    }

    /**
     * Pack a widget to the start of the box
     *
     * @param  object $widget The widget to pack
     * @access public
     */
    function packStart(&$widget)
    {
        $this->_items[] =& $widget;
        //array_push($this->_items, &$widget);
    }

    /**
     * Pack a widget to the end of the box
     *
     * @param  object $widget The widget to pack
     * @access public
     */
    function packEnd(&$widget)
    {
        $total = count($this->_items);
        $this->_items[$total + 1] = $widget;
    }
}
?>
