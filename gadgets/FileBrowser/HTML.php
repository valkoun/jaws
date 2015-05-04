<?php
/**
 * FileBrowser Gadget
 *
 * @category   Gadget
 * @package    FileBrowser
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class FileBrowserHTML extends Jaws_GadgetHTML
{
    /**
     * Public constructor
     *
     * @access  public
     */
    function FileBrowserHTML()
    {
        $this->Init('FileBrowser');
    }

    /**
     * Default action to be run if none is defined.
     *
     * @access public
     * @return string HTML content of Default action
     */
    function DefaultAction()
    {
        return $this->Display();
    }

    /**
     * Give visitors frontend access to certain files and directories, with custom titles and descriptions.
     *
     * @category  feature
     * @access  public
     * @return  string  HTML content with titles and contents
     */
    function Display()
    {
        if ($GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/frontend_avail') != 'true') {
            return false;
        }

        if (!$GLOBALS['app']->Session->GetPermission('FileBrowser', 'OutputAccess')) {
            if ($GLOBALS['app']->Session->Logged()) {
                return _t('GLOBAL_ERROR_ACCESS_DENIED');
            } else {
                return _t('GLOBAL_ERROR_ACCESS_RESTRICTED',
                          $GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm'),
                          $GLOBALS['app']->Map->GetURLFor('Users', 'Registration'));
            }
        }

        $request =& Jaws_Request::getInstance();
        if ($request->get('path', 'get')) {
            $path = $request->get('path', 'get');
        } elseif ($request->get('path', 'post')) {
            $path = $request->get('path', 'post');
        } else {
            $path = '';
        }

        $page = $request->get('page', 'get');
        if (is_null($page) || $page <= 0 ) {
            $page = 1;
        }

        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'Model');
        $locationTree = $model->GetCurrentRootDir($path);
        if (Jaws_Error::IsError($locationTree)) {
            return false;
        }

        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('FileBrowser.html');
        $tpl->SetBlock('filebrowser');
		$tpl->SetVariable('actionName', str_replace(' ', '-', _t('FILEBROWSER_INITIAL_FOLDER')));
		$tpl->SetVariable('title', _t('FILEBROWSER_NAME'));
        $this->SetTitle(_t('FILEBROWSER_NAME'));

        $parentPath = '';
        $tpl->SetVariable('location', _t('FILEBROWSER_LOCATION'));
        foreach ($locationTree as $_path => $dir) {
            if (!empty($dir) && $_path{0} == '/') {
                $_path = substr($_path, 1);
            }

            $dbFile = $model->DBFileInfo($parentPath, $dir);
            if (Jaws_Error::IsError($dbFile) || empty($dbFile)) {
                $dirTitle = $dir;
            } else {
                $dirTitle = $dbFile['title'];
            }

            $parentPath = $_path;
            if (empty($_path)) {
                $tpl->SetVariable('root', _t('FILEBROWSER_ROOT'));
                $tpl->SetVariable('root-path', $this->GetURLFor('Display', array('path' => $_path), false));
            } else {
                $tpl->SetBlock('filebrowser/tree');
                $tpl->SetVariable('dir-name', $dirTitle);
                $tpl->SetVariable('dir-path', $this->GetURLFor('Display', array('path' => $_path), false));
                $tpl->ParseBlock('filebrowser/tree');
            }

            if ($path == $_path && !empty($dbFile)) {
                $tpl->SetVariable('text', $this->ParseText($dbFile['description'], 'FileBrowser'));
            }
        }

        $limit  = (int) $GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/views_limit');
        $offset = ($page - 1) * $limit;
        $items = $model->ReadDir($path, $limit, $offset);
        if (!Jaws_Error::IsError($items)) {
            $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            $date = $GLOBALS['app']->loadDate();
            foreach ($items as $item) {
                if ($item['is_dir'] && ($item['filename'] == 'medium' || $item['filename'] == 'thumb')) {
					continue;
				}
				$tpl->SetBlock('filebrowser/item');
                $tpl->SetVariable('icon',  $item['icon']);
                $tpl->SetVariable('name',  $xss->filter($item['filename']));
                $tpl->SetVariable('title', $xss->filter($item['title']));
                if ($item['is_dir']) {
                    $relative = $xss->filter($item['relative']) . '/';
                    $url = $this->GetURLFor('Display', array('path' => $relative), false);
                    $tpl->SetVariable('url', $url);
                } else {
                    $tpl->SetVariable('url', $xss->filter($item['url']));
                    if (!empty($item['id'])) {
                        $tpl->SetBlock('filebrowser/item/info');
                        $tpl->SetVariable('lbl_info', _t('FILEBROWSER_FILEINFO'));
                        $param = array('id' => !empty($item['fast_url'])?  $xss->filter($item['fast_url']) : $item['id']);
                        $tpl->SetVariable('info_url', $this->GetURLFor('FileInfo', $param, false));
                        $tpl->ParseBlock('filebrowser/item/info');
                    }
                }

                $tpl->SetVariable('date', $date->Format($item['date']));
                $tpl->SetVariable('size', $item['size']);
                $tpl->ParseBlock('filebrowser/item');
            }
        }

        if ($tpl->VariableExists('navigation')) {
            $total  = $model->GetDirContentsCount($path);
            $params = array('path'  => $path);
            $tpl->SetVariable('navigation',
                              $this->GetNumberedPageNavigation($page, $limit, $total, 'Display', $params));
        }

        $tpl->ParseBlock('filebrowser');
        return $tpl->Get();
    }

    /**
     * Get page navigation links
     * @access private
     */
    function GetNumberedPageNavigation($page, $page_size, $total, $action, $params = array())
    {
        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('PageNavigation.html');
        $tpl->SetBlock('pager');

        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'Model');
        $pager = $model->GetEntryPagerNumbered($page, $page_size, $total);
        if (count($pager) > 0) {
            $tpl->SetBlock('pager/numbered-navigation');
            $tpl->SetVariable('total', _t('FILEBROWSER_ENTRIES_COUNT', $pager['total']));

            $pager_view = '';
            foreach ($pager as $k => $v) {
                $tpl->SetBlock('pager/numbered-navigation/item');
                $params['page'] = $v;
                if ($k == 'next') {
                    if ($v) {
                        $tpl->SetBlock('pager/numbered-navigation/item/next');
                        $tpl->SetVariable('lbl_next', _t('GLOBAL_NEXT'));
                        $url = $this->GetURLFor($action, $params);
                        $tpl->SetVariable('url_next', $url);
                        $tpl->ParseBlock('pager/numbered-navigation/item/next');
                    } else {
                        $tpl->SetBlock('pager/numbered-navigation/item/no_next');
                        $tpl->SetVariable('lbl_next', _t('GLOBAL_NEXT'));
                        $tpl->ParseBlock('pager/numbered-navigation/item/no_next');
                    }
                } elseif ($k == 'previous') {
                    if ($v) {
                        $tpl->SetBlock('pager/numbered-navigation/item/previous');
                        $tpl->SetVariable('lbl_previous', _t('GLOBAL_PREVIOUS'));
                        $url = $this->GetURLFor($action, $params);
                        $tpl->SetVariable('url_previous', $url);
                        $tpl->ParseBlock('pager/numbered-navigation/item/previous');
                    } else {
                        $tpl->SetBlock('pager/numbered-navigation/item/no_previous');
                        $tpl->SetVariable('lbl_previous', _t('GLOBAL_PREVIOUS'));
                        $tpl->ParseBlock('pager/numbered-navigation/item/no_previous');
                    }
                } elseif ($k == 'separator1' || $k == 'separator2') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_separator');
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_separator');
                } elseif ($k == 'current') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_current');
                    $url = $this->GetURLFor($action, $params);
                    $tpl->SetVariable('lbl_page', $v);
                    $tpl->SetVariable('url_page', $url);
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_current');
                } elseif ($k != 'total' && $k != 'next' && $k != 'previous') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_number');
                    $url = $this->GetURLFor($action, $params);
                    $tpl->SetVariable('lbl_page', $v);
                    $tpl->SetVariable('url_page', $url);
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_number');
                }
                $tpl->ParseBlock('pager/numbered-navigation/item');
            }

            $tpl->ParseBlock('pager/numbered-navigation');
        }

        $tpl->ParseBlock('pager');

        return $tpl->Get();
    }

    /**
     * Action for display file info
     *
     * @access  public
     * @return  string  HTML content with titles and contents
     */
    function FileInfo()
    {
        $request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');

        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $id = $xss->defilter($id, true);

        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'Model');
        $dbInfo = $model->DBFileInfoByIndex($id);
        if (Jaws_Error::IsError($dbInfo) || empty($dbInfo)) {
            return false;
        }

        $date = $GLOBALS['app']->loadDate();
        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('FileBrowser.html');
        $tpl->SetBlock('fileinfo');

        $Info = $model->GetFileProperties($dbInfo['path'], $dbInfo['filename']);

        $tpl->SetVariable('icon',  $Info['mini_icon']);
        $tpl->SetVariable('name',  $xss->filter($Info['filename']));
        $tpl->SetVariable('title', $xss->filter($dbInfo['title']));
        $tpl->SetVariable('url',   $xss->filter($Info['url']));
        $tpl->SetVariable('date',  $date->Format($Info['date']));
        $tpl->SetVariable('size',  $Info['size']);
        $tpl->SetVariable('text',  $this->ParseText($dbInfo['description'], 'FileBrowser'));

        $locationTree = $model->GetCurrentRootDir($dbInfo['path']);
        if (Jaws_Error::IsError($locationTree)) {
            return false;
        }

        $parentPath = '';
        $tpl->SetVariable('location', _t('FILEBROWSER_LOCATION'));
        foreach ($locationTree as $path => $dir) {
            if (!empty($dir) && $path{0} == '/') {
                $path = substr($path, 1);
            }

            $dbFile = $model->DBFileInfo($parentPath, $dir);
            if (Jaws_Error::IsError($dbFile) || empty($dbFile)) {
                $dirTitle = $dir;
            } else {
                $dirTitle = $dbFile['title'];
            }

            $parentPath = $path;
            if (empty($path)) {
                $tpl->SetVariable('root', _t('FILEBROWSER_ROOT'));
                $tpl->SetVariable('root-path', $this->GetURLFor('Display', array('path' => $path), false));
            } else {
                $tpl->SetBlock('fileinfo/tree');
                $tpl->SetVariable('dir-name', $dirTitle);
                $tpl->SetVariable('dir-path', $this->GetURLFor('Display', array('path' => $path), false));
                $tpl->ParseBlock('fileinfo/tree');
            }
        }

        $tpl->ParseBlock('fileinfo');
        return $tpl->Get();
    }

	/**
     * Displays user account controls.
     *
     * @param array  $info  user information
     * @access public
     * @return string
     */
    function GetUserAccountControls($info)
    {
		/*
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');
        if (is_null($info)) {
			$info = array();
			$info['id'] = $get['id'];
		}
        require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('users.html');

		//require_once JAWS_PATH . 'include/Jaws/User.php';
        //$jUser = new Jaws_User;
        //$info  = $jUser->GetUserInfoById($GLOBALS['app']->Session->GetAttribute('user_id'));
		$userModel  = $GLOBALS['app']->LoadGadget('Users', 'Model');
		
		$panes_found = false;

		$tpl->SetBlock('pane');
		$tpl->SetVariable('title', $this->_Name."&nbsp;Gadget");
		$tpl->SetVariable('gadget', $this->_Name);
					
		$pane_status = $userModel->GetGadgetPaneInfoByUserID($this->_Name, $info['id']);
		if (!Jaws_Error::IsError($pane_status) && $pane_status['enabled']) {
			$buttons = '';
			if ($pane_status['status'] == 'maximized') {
				$buttons = "<a href=\"javascript:void(0);\" id=\"".$this->_Name."_button1\"><img border=\"0\" src=\"images/btn_paneMin_off.png\" name=\"Collapse\" alt=\"Collapse\" title=\"Collapse\" onClick=\"minPane('".$this->_Name."', ".$info['id'].");\" onMouseover=\"this.src='images/btn_paneMin_on.png'\" onMouseOut=\"this.src='images/btn_paneMin_off.png'\" /></a>";
			} else if ($pane_status['status'] == 'minimized') {
				$buttons = "<a href=\"javascript:void(0);\" id=\"".$this->_Name."_button2\"><img border=\"0\" src=\"images/btn_paneMax_off.png\" name=\"Expand\" alt=\"Expand\" title=\"Expand\" onClick=\"maxPane('".$this->_Name."', ".$info['id'].");\" onMouseover=\"this.src='images/btn_paneMax_on.png'\" onMouseOut=\"this.src='images/btn_paneMax_off.png'\" /></a><style>#".$this->_Name."_pane { display: none; };</style>";
			}
			$tpl->SetVariable('gadget_pane_buttons', $buttons);

			//Construct panes for each available gadget
			$panes = $this->GetUserAccountPanesInfo();
			foreach ($panes as $pane_method => $pane_name) {
				$tpl->SetBlock('pane/pane_item');
				$tpl->SetVariable('gadget', $this->_Name);
				if ($panes_found != true) {
					$panes_found = true;
				}
				if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/images/sm_'.$pane_method.'.gif')) {
					$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/sm_'.$pane_method.'.gif');
				} else {
					$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png');
				}
				$tpl->SetVariable('pane', $pane_method);
				$tpl->SetVariable('pane_name', $pane_name);
				$content = $this->$pane_method($info['id']);
				if ($content) {
					$tpl->SetVariable('gadget_pane', $content);
				} else {
					return _t('GLOBAL_ERROR_GET_ACCOUNT_PANE');
				}
				$tpl->ParseBlock('pane/pane_item');
			}

		} else if (Jaws_Error::IsError($pane_status)) {
			return _t('GLOBAL_ERROR_GET_ACCOUNT_PANE');
		}
		
		$tpl->ParseBlock('pane');

		return $tpl->Get();
		*/
		return "";
    }

     /*
     * Define array of panes for this gadget's account controls.
     * (i.e. Store gadget has "Order History", "Current Cart" and "Items Sold" panes) 
     * 
     * $panes array structured as follows:
     * 'AdminHTML->MethodName' => array('Pane Name', 'ACL Key')
     * 
     * @access public
     * @return array of pane names
     */
    function GetUserAccountPanesInfo()
    {		
		$panes = array();
		$panes['UserFiles'] = "My Files";
		return $panes;
	}

    /**
     * Account Admin
     *
     * @access public
     * @return string
     */
    function account_Admin()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminHTML');
		$page = $gadget_admin->Admin(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('FileBrowser', false, false));
		return $output_html;
    }
    
	/**
     * Account AddFileToPost
     *
     * @access public
     * @return string
     */
    function account_AddFileToPost()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminHTML');
		$page = $gadget_admin->AddFileToPost(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('FileBrowser', false, false));
		return $output_html;
    }

    /**
     * Automatically watermark your images.
     *
     * @category  feature
     * @access  public
     * @return  string  HTML content with titles and contents
     */
    function Watermark()
    {
        if ($GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/frontend_avail') != 'true') {
            return false;
        }
        $request =& Jaws_Request::getInstance();
        if ($request->get('path', 'get')) {
            $path = $request->get('path', 'get');
        } elseif ($request->get('path', 'post')) {
            $path = $request->get('path', 'post');
        } else {
            $path = '';
        }
        if ($request->get('wm', 'get')) {
            $wm = $request->get('wm', 'get');
        } elseif ($request->get('wm', 'post')) {
            $wm = $request->get('wm', 'post');
        } else {
            $wm = '';
        }
		$path = JAWS_DATA . 'files'.urldecode($path);
		$wm = JAWS_DATA . 'files'.urldecode($wm);
		if (file_exists($path) && file_exists($wm)) {
 		   
			//Get the resource ids of the pictures
			$watermarkfile_id = imagecreatefrompng($wm);
		   
			imageAlphaBlending($watermarkfile_id, false);
			imageSaveAlpha($watermarkfile_id, true);

			$fileType = strtolower(substr($path, strlen($path)-3));

			switch($fileType) {
				case('gif'):
					$sourcefile_id = imagecreatefromgif($path);
					break;
				   
				case('png'):
					$sourcefile_id = imagecreatefrompng($path);
					break;
				   
				default:
					$sourcefile_id = imagecreatefromjpeg($path);
			}

			//Get the sizes of both pix  
		  $sourcefile_width=imageSX($sourcefile_id);
		  $sourcefile_height=imageSY($sourcefile_id);
		  $watermarkfile_width=imageSX($watermarkfile_id);
		  $watermarkfile_height=imageSY($watermarkfile_id);

			$dest_x = ( $sourcefile_width / 2 ) - ( $watermarkfile_width / 2 );
			$dest_y = ( $sourcefile_height / 2 ) - ( $watermarkfile_height / 2 );
		   
			// if a gif, we have to upsample it to a truecolor image
			if($fileType == 'gif') {
				// create an empty truecolor container
				$tempimage = imagecreatetruecolor($sourcefile_width, $sourcefile_height);
			   
				// copy the 8-bit gif into the truecolor image
				imagecopy($tempimage, $sourcefile_id, 0, 0, 0, 0,
									$sourcefile_width, $sourcefile_height);
			   
				// copy the source_id int
				$sourcefile_id = $tempimage;
			}

			/* Open another PNG file, then resize and compose it */
			$this->_imageComposeAlpha( $sourcefile_id, $watermarkfile_id, 0, 0, $sourcefile_width, $sourcefile_height );

			/**
			 * Open the same PNG file then compose without resizing
			 * As the original $watermark is passed by reference, it was resized already.
			 * So we have to reopen it.
			 */
			$this->_imageComposeAlpha( $sourcefile_id, $watermarkfile_id, $dest_x, $dest_y);

			/*
			imagecopyresampled($sourcefile_id, $watermarkfile_id, $dest_x, $dest_y, 0, 0,
								$sourcefile_width, $sourcefile_height, $watermarkfile_width, $watermarkfile_height);
			*/
			
			//Create a jpeg out of the modified picture
			switch($fileType) {
		   
				// remember we don't need gif any more, so we use only png or jpeg.
				// See the upsaple code immediately above to see how we handle gifs
				case('png'):
					header("Content-type: image/png");
					imagepng ($sourcefile_id);
					break;
				   
				default:
					header("Content-type: image/jpg");
					imagejpeg ($sourcefile_id);
			}          
		 
			imagedestroy($sourcefile_id);
			imagedestroy($watermarkfile_id);
   
		}
    }

	/**
	 * Compose a PNG file over a src file.
	 * If new width/ height are defined, then resize the PNG (and keep all the transparency info)
	 * Author:  Alex Le - http://www.alexle.net
	 */
	function _imageComposeAlpha( &$src, &$ovr, $ovr_x, $ovr_y, $ovr_w = false, $ovr_h = false)
	{
		if( $ovr_w && $ovr_h )
			$ovr = $this->_imageResizeAlpha( $ovr, $ovr_w, $ovr_h );
		   
		/* Noew compose the 2 images */
		imagecopy($src, $ovr, $ovr_x, $ovr_y, 0, 0, imagesx($ovr), imagesy($ovr) );   
	}

	/**
	 * Resize a PNG file with transparency to given dimensions
	 * and still retain the alpha channel information
	 * Author:  Alex Le - http://www.alexle.net
	 */
	function _imageResizeAlpha(&$src, $w, $h)
	{
        /* create a new image with the new width and height */
        $temp = imagecreatetruecolor($w, $h);
       
        /* making the new image transparent */
        $background = imagecolorallocate($temp, 0, 0, 0);
        ImageColorTransparent($temp, $background); // make the new temp image all transparent
        imagealphablending($temp, false); // turn off the alpha blending to keep the alpha channel
       
        /* Resize the PNG file */
        /* use imagecopyresized to gain some performance but loose some quality */
        imagecopyresized($temp, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        /* use imagecopyresampled if you concern more about the quality */
        //imagecopyresampled($temp, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        return $temp;
	}
}
