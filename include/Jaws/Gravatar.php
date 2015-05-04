<?php
/**
 * Enables gravatar [www.gravatar.com] support in Jaws.
 *
 * @category   Gadget
 * @category   developer_feature
 * @package    Core
 * @author     Jon Wood <jon@jellybob.co.uk>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Gravatar
{
    function GetGravatar($email, $size = 48)
    {
        $theme = $GLOBALS['app']->GetTheme();
		$defaultImage = $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/no-photo.png';
		if (substr(strtolower($theme['path']), 0, 4) == 'http') {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy;
			$snoopy->fetch($theme['url'] . 'default_avatar.png');
			if($snoopy->status == "200") {
				$defaultImage = $theme['url'] . 'default_avatar.png';
			}
        } else if (file_exists($theme['path'] . 'default_avatar.png')) {
			$defaultImage = $theme['url'] . 'default_avatar.png';
        }

        if ($GLOBALS['app']->Registry->Get('/config/use_gravatar') == 'no') {
            return $defaultImage;
        }

        $id = md5($email);
        $rating = $GLOBALS['app']->Registry->Get('/config/gravatar_rating');
        if (Jaws_Error::isError($rating)) {
            $rating = 'G';
        }

        if ($size > 80) {
            $size = 80;
        } elseif ($size < 0) {
            $size = 0;
        }

        $defaultImage = urlencode($defaultImage);

        $gravatar = 'http://www.gravatar.com/avatar.php?gravatar_id=' . $id. '&amp;rating='. $rating. '&amp;' .
            'size=' . $size.'&amp;default=' . $defaultImage;

        return $gravatar;
    }

}