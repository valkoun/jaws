<?php
/**
 * Modify HTTP headers of response sent from Jaws
 *
 * @category   JawsType
 * @category   developer_feature
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Header
{
	// This is an abstract class, so construct does not exists
	
    /**
     * Edits the location of the browser, once its set it will exit
     *
     * @param   string  $url URL to move the location
     * @access  public
     */
    function Location($url = '', $addSiteURL = false)
    {
		if (isset($GLOBALS['app']) && isset($GLOBALS['app']->Session)) {
			$GLOBALS['app']->Session->Synchronize();
		}
		/*
		//if (DEBUG_ACTIVATED) {
			$d = debug_backtrace();
			//$func = $d[count($d) - 1]["function"];
			//if (isset($GLOBALS['log'])) {
			//	$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Location called by: '.var_export($d, true));
			//} else {
				echo '<br />Location called by: '.var_export($d, true);
			//}
			exit;
		//}
        */
		if (empty($url) || $addSiteURL) {
            $url = $GLOBALS['app']->getSiteURL('/' . $url);
        }

        header('Location: '.$url);
        exit;
    }

    /**
     * Redirect to referrer page
     *
     * @access  public
     */
    function Referrer()
    {
		if (isset($GLOBALS['app']) && isset($GLOBALS['app']->Session)) {
			$GLOBALS['app']->Session->Synchronize();
        }
		if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        } else {
            $url = $GLOBALS['app']->getSiteURL('/');
        }

        header('Location: '.$url);
        exit;
    }

    /**
     * Redirects the browser to another url
     *
     * @param   string  $url     Url to redirect
     * @param   int     $timeout Timeout to redirect
     * @access  public
     */
    function Redirect($url, $timeout = 0)
    {
        if (!is_numeric($timeout)) {
            $timeout = 0;
        }
		
		/*
		if (DEBUG_ACTIVATED) {
			$d = debug_backtrace();
			$func = $d[count($d) - 1]["function"];
			//$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Location called by'.$func);
			echo 'Location called by'.$func;
			exit;
		}
		*/
		
        header('Refresh: '.$timeout.'; URL='.$url);
    }

    /**
     * Change the status to 404
     *
     * @access  public
     */
    function ChangeTo404()
    {
        header('Status: 404 Not Found');
    }

    /**
     * Set expiration date
     *
     * Take a look at: http://www.php.net/manual/en/function.header.php for examples
     * @param   string  $date Date in format: Day, day Month Year Hour:Minutes:Seconds GMT
     * @access  public
     */
    function Expire($date)
    {
        header('Expires: {$date}');
    }

    /**
     * Disables the cache of browser
     *
     * @access  public
     */
    function DisableCache()
    {
        // HTTP/1.1
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);

        // HTTP/1.0
        header('Pragma: no-cache');
    }

    /**
     * Change the content disposition of the file and change its filename
     *
     * @param  string  $ctype  Content type
     * @param  string  $file   Filename
     * @accses public
     */
    function ChangeContent($ctype, $file)
    {
        header('Content-type: '.$ctype);
        header('Content-Disposition: attachment; filename='.$file);
    }
}
