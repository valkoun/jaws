<?php
/**
 * Social AJAX API
 *
 * @category   Ajax
 * @package    Social
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class SocialAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function SocialAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    /**
     * Update Social
     *
     * @access  public
     * @param   array   $Social_config
     * @return  array   Response (notice or error)
     */
    function UpdateSocial($social_config, $social_urls = array(), $social_ids = array())
    {
		$this->CheckSession('Social', 'UpdateProperties');
		$this->_Model->UpdateSocial($social_config, $social_urls, $social_ids);
		return $GLOBALS['app']->Session->PopLastResponse();
    }
}
