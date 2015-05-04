<?php
/**
 * Maps Actions file
 *
 * @category   GadgetActions
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions = array();

$actions['Index'] 				= array('NormalAction');
$actions['Map']  				= array('NormalAction');
$actions['GoogleMapXML']  		= array('StandaloneAction');
$actions['hideGoogleAPIAlerts']	= array('StandaloneAction');
$actions['Rounded']				= array('StandaloneAction');
$actions['AutoCompleteRegions']	= array('StandaloneAction');

$actions['Admin']         		= array('AdminAction');
$actions['form']          		= array('AdminAction');
$actions['form_post']     		= array('AdminAction');
$actions['view']          		= array('AdminAction');
$actions['A_form']        		= array('AdminAction');
$actions['A_form_post']			= array('AdminAction');
$actions['Settings']      		= array('AdminAction');
$actions['SaveChanges']     	= array('AdminAction');
?>
