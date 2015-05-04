<?php
/**
 * Social Gadget Model
 *
 * @category   GadgetModel
 * @package    Social
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2009 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class SocialModel extends Jaws_Model
{
    var $_Name = 'Social';

    /**
     * Save the cookie, save the world
     *
     * @access  public
     * @param   array   $Social
     * @param   int     $expiretime
     * @return  boolean True/False
     */
    function SaveSocial($Social, $expire_age = 86400)
    {
        foreach ($Social as $Key => $Value) {
            Jaws_Session_Web::SetCookie($Key, $Value, $expire_age);
        }
        return true;
    }
    
	/**
     * Gets an Email
     *
     * @access  public
     * @param   string     $email  The user email address
     * @return  mixed   Returns an array or false on error
     */
    function GetEmail($email)
	{
		$params       		= array();
        $params['email'] 		= $email;
		
		$sql = '
            SELECT [id], [name], [email], [createtime], [updatetime], [closetime], 
				[company], [website], [address], [address2], [city], 
				[region], [postal], [country], [phone], [active],
				[ownerid], [checksum], [recovery_key]
			FROM [[emails]]
            WHERE ([email] = {email})';
		
        $types = array(
			'integer', 'text', 'text', 'timestamp', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text',
			'integer', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('SOCIAL_ERROR_EMAIL_NOT_RETRIEVED'), _t('SOCIAL_NAME'));
        }

        return $result;
    }

	/**
     * Gets the user's Emails by ID
     *
     * @access  public
     * @param   int     $OwnerID  The user ID
     * @return  mixed   Returns an array or false on error
     */
    function GetEmailsOfUserID($OwnerID = 0)
	{
		$params       		= array();
        $params['id'] 		= (int)$OwnerID;
		
		$sql = '
            SELECT [id], [name], [email], [createtime], [updatetime], [closetime], 
				[company], [website], [address], [address2], [city], 
				[region], [postal], [country], [phone], [active],
				[ownerid], [checksum], [recovery_key]
			FROM [[emails]]
            WHERE ([ownerid] = {id})';
		
        $types = array(
			'integer', 'text', 'text', 'timestamp', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text',
			'integer', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('SOCIAL_ERROR_EMAIL_NOT_RETRIEVED'), _t('SOCIAL_NAME'));
        }

        return $result;
    }

	/**
     * Gets the user's Socials by ID
     *
     * @access  public
     * @param   int     $social  The social service
     * @param   int     $OwnerID  The user ID
     * @return  mixed   Returns an array or false on error
     */
    function GetSocialOfUserID($OwnerID = 0, $social = null)
	{
		$params       		= array();
        $params['id'] 		= (int)$OwnerID;
		
		$sql = '
            SELECT [id], [social], [social_url], [social_id], [social_id2], [social_id3], [active], 
				[ownerid], [created], [updated], [checksum]
			FROM [[social_users]]
            WHERE ([ownerid] = {id})';
			
		if (!is_null($social)) {
			$sql .= ' AND ([social] = {social})';
			$params['social'] 	= $social;
		}
		
        $types = array(
			'integer', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('SOCIAL_ERROR_SOCIAL_NOT_RETRIEVED'), _t('SOCIAL_NAME'));
        }
        return $result;
    }
    
	/**
     * Gets the user's access_tokens by ID
     *
     * @access  public
     * @param   int     $OwnerID  The user ID
     * @param   int     $social_id  The social ID from social_users
     * @return  mixed   Returns an array or false on error
     */
    function GetAccessTokensOfUserID($OwnerID = 0, $social_id = null)
	{
		$params       		= array();
        $params['id'] 		= (int)$OwnerID;
		
		$sql = '
            SELECT [id], [social_id], [social_token], [expires],
				[ownerid], [created], [updated], [checksum]
			FROM [[social_tokens]]
            WHERE ([ownerid] = {id})';
			
		if (!is_null($social_id)) {
			$sql .= ' AND ([social_id] = {social_id})';
			$params['social_id'] 	= $social_id;
		}
		
        $types = array(
			'integer', 'integer', 'text', 'timestamp',
			'integer', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('SOCIAL_ERROR_ACCESS_TOKENS_NOT_RETRIEVED'), _t('SOCIAL_NAME'));
        }
        return $result;
    }
    
	/**
     * Gets the available social websites to share to
     *
     * @access  public
     * @param   int     $title  The title of content to share
     * @param   int     $url  The URL of content to share
     * @return  mixed   Returns an array or false on error
     */
	function getSocialWebsites($title = '', $url = '')
	{
		$websites = array();
		$pageURL = (!empty($url) ? $url : $GLOBALS['app']->GetFullURL());
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onBeforeSocialSharing', array('url' => $pageURL));
		if (!Jaws_Error::IsError($res) && (isset($res['url']) && !empty($res['url']))) {
			$pageURL = $res['url'];
		}
		
		if (empty($title)) {
			$title = "'+document.title+'";
		} else {
			$title = urlencode($title);
		}
		
		$websites["facebook"] = array();
		$websites["facebook"]["realname"]="Facebook";
		$websites["facebook"]["sendurl"]="http://www.facebook.com/sharer.php?u=$pageURL&t=$title";
		$websites["facebook"]["url"] = '';
		$websites["facebook"]["id"] = 'API Key';
		$websites["facebook"]["id2"] = 'API Secret';
		
		$websites["twitter"] = array();
		$websites["twitter"]["realname"]="Twitter";
		$websites["twitter"]["sendurl"]="http://twitter.com/home?status=$title+$pageURL";
		$websites["twitter"]["url"] = '';
		
		$websites["delicious"] = array();
		$websites["delicious"]["realname"]="delicious";
		$websites["delicious"]["sendurl"]="http://del.icio.us/post?url=$pageURL&title=$title";

		$websites["digg"] = array();
		$websites["digg"]["realname"]="Digg";
		$websites["digg"]["sendurl"]="http://digg.com/submit?phase=2&url=$pageURL&title=$title";

		$websites["reddit"] = array();
		$websites["reddit"]["realname"]="Reddit";
		$websites["reddit"]["sendurl"]="http://reddit.com/submit?url=$pageURL&title=$title";
		
		$websites["stumbleupon"] = array();
		$websites["stumbleupon"]["realname"]="StumbleUpon";
		$websites["stumbleupon"]["sendurl"]="http://www.stumbleupon.com/submit?url=$pageURL&title=$title";
		
		$websites["myspace"] = array();
		$websites["myspace"]["realname"]="MySpace";
		$websites["myspace"]["sendurl"]="http://www.myspace.com/Modules/PostTo/Pages/?u=$pageURL&t=$title";
		$websites["myspace"]["url"] = '';
		
		if (Jaws_Gadget::IsGadgetUpdated('Forms')) {
			$websites["email"] = array();
			$websites["email"]["realname"]="E-mail";
			//$websites["email"]["sendurl"]="javascript:if (document.getElementById('social_email_form')) {var target = document.getElementById('social_email_form'); if (target.style.display == 'none') {target.style.display = '';} else {target.style.display = 'none';};};";
			$websites["email"]["sendurl"]="javascript:void(0);";
		}
		return $websites;
    }

    /**
     * Unsubscribes an E-mail.
     *
     * @access  public
     * @param   int	$email	The email to update.
     * @return  boolean Success/failure
     */
    function UnSubscribeEmail($email)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		$sql = '
            UPDATE [[emails]] SET
				[active] = {Active}, 
				[closetime] = {now}
			WHERE [email] = {email}';

        $params               	= array();
		$params['email']        = $email;
        $params['Active'] 		= 'N';
        $params['now']        	= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('SOCIAL_ERROR_EMAIL_NOT_UPDATED'), _t('SOCIAL_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateSocialEmail', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        return true;
    }
}
