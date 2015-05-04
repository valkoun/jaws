<?php
/**
 * Preferences AJAX API
 *
 * @category   Ajax
 * @package    Preferences
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class PreferencesAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function PreferencesAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    /**
     * Update preferences
     *
     * @access  public
     * @param   array   $preferences_config
     * @return  array   Response (notice or error)
     */
    function UpdatePreferences($preferences_config)
    {
        $this->CheckSession('Preferences', 'UpdateProperties');
        $this->_Model->UpdatePreferences($preferences_config);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
}
