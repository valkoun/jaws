<?php
/**
 * Ads - Comment gadget hook
 *
 * @category   GadgetHook
 * @package    Ads
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
class AdsCommentHook
{	
    /**
     * Returns an HTML string of comment sorting options
     *
     * @access  public
     */
    function GetCommentsTitleOptions($params = array())
    {
		$OwnerID = $params['uid'];
		$public = $params['public'];
		$title = $params['title'];
        $result = '';
		if ($public === false) {
			$tpl = new Jaws_Template('gadgets/Ads/templates/');
			$tpl->Load('SortingComments.html');
			$tpl->SetBlock('private');
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL());
			$tpl->SetVariable('OwnerID', (!is_null($OwnerID) ? $OwnerID : ''));
			$tpl->SetVariable('gadget', 'Ads');
			$tpl->ParseBlock('private');
			$result = $tpl->Get();
		}
		return $result;
    }
}
