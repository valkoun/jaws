<?php
/**
 * TMS (Theme Management System) Gadget actions
 *
 * @category   GadgetActions
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$actions = array();
/* Normal actions */
$actions['Preview']      = array('StandaloneAction');
$actions['RSS']       	 = array('StandaloneAction');

/* Admin actions */
$actions['Admin']        = array('AdminAction');
$actions['Repositories'] = array('AdminAction');
$actions['Properties']   = array('AdminAction');
$actions['UploadTheme']  = array('AdminAction');
