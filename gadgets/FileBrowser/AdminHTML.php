<?php
/**
 * Filebrowser Admin Gadget
 *
 * @category   GadgetAdmin
 * @package    FileBrowser
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class FileBrowserAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Public constructor
     *
     * @access  public
     */
    function FileBrowserAdminHTML()
    {
        $this->Init('FileBrowser');
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function DataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminModel');
        $total = $model->GetDirContentsCount('');

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->pageBy(15);
        $grid->SetID('fb_datagrid');
        $column = Piwi::CreateWidget('Column', '');
        $column->SetStyle('width: 1px;');
        $grid->AddColumn($column);
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_NAME')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FILEBROWSER_SIZE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));
        $grid->SetStyle('width: 100%;');

        return $grid->Get();
    }

    /**
     * Builds the filepicker datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function FilePickerDataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminModel');
        $total = $model->GetDirContentsCount('');

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->pageBy(15);
        $grid->SetID('fp_datagrid');
        $column = Piwi::CreateWidget('Column', '');
        $column->SetStyle('width: 1px;');
        $grid->AddColumn($column);
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_NAME')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FILEBROWSER_SIZE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));
        $grid->SetStyle('width: 100%;');

        return $grid->Get();
    }

    /**
     * Creates and returns some data
     *
     * @access  public
     * @param   string  $dir
     * @param   int     $offset
     * @return  array
     */
    function GetDirectory($dir, $offset, $insert = false)
    {
        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminModel');
        $files = $model->ReadDir($dir, 15, $offset);
        if (Jaws_Error::IsError($files)) {
            return array();
            //Jaws_Error::Fatal($files->getMessage(), __FILE__, __LINE__);
        }

        $tree = array();
        foreach ($files as $file) {
            $item = array();

            //Icon
            if ($file['is_dir'] === false && in_array(strtolower($file['ext']), array('gif', 'png', 'jpg', 'jpeg', 'bmp', 'tiff', 'tif'))) {
				$image_url = (isset($file['thumb']) && !empty($file['thumb']) ? $file['thumb'] : (isset($file['medium']) && !empty($file['url']) ? $file['medium'] : $file['url']));
				require_once JAWS_PATH . 'include/Jaws/Widgets/ImageButton.php';
				$image =& new Jaws_Widgets_ImageButton(_t('GLOBAL_PREVIEW'), $image_url, "javascript: window.open('{$file['url']}');");
				$item['image'] = $image->Get();
			} else {
				$image =& Piwi::CreateWidget('Image', $file['mini_icon']);
				$item['image'] = $image->Get();
			}
			
            $actions = '';			
            if ($file['is_dir']) {
                $link =& Piwi::CreateWidget('Link', $file['filename'], "javascript: cwd('{$file['relative']}');");
                $link->setStyle('float: left;');
                $item['name'] = $link->Get();
				$link1 =& Piwi::CreateWidget('Link', $file['title'], "javascript: cwd('{$file['relative']}');");
				$item['title'] = $link1->Get();

                if ($this->GetPermission('ManageDirectories')) {
					//edit directory properties
                    $link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
                                                "javascript: editDir(this, '{$file['filename']}');",
                                                STOCK_EDIT);
                    $actions.= $link->Get().'&nbsp;';

                    //delete directory
                    $link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                                                "javascript: delDir(this, '{$file['filename']}');",
                                                STOCK_DELETE);
                    $actions.= $link->Get().'&nbsp;';
                }
            } else {
				if ($insert === true && in_array(strtolower($file['ext']), array('gif', 'png', 'jpg', 'jpeg', 'raw', 'bmp', 'tiff', 'tif', 'swf', 'svg'))) {
					$link =& Piwi::CreateWidget('Link', $file['filename'], "javascript: FileBrowserDialogue.insertFile('{$file['filename']}');");
					$link->setStyle('float: left;');
					$item['name'] = $link->Get();
					$link1 =& Piwi::CreateWidget('Link', $file['title'], "javascript: FileBrowserDialogue.insertFile('{$file['filename']}');");
					$item['title'] = $link1->Get();
				} else {
					$item['name'] = $file['filename'];
					$item['title'] = $file['title'];
				}

                if ($this->GetPermission('ManageFiles')) {
					//insert file
                    if ($insert === true && in_array(strtolower($file['ext']), array('gif', 'png', 'jpg', 'jpeg', 'raw', 'bmp', 'tiff', 'tif', 'swf', 'svg'))) {
						$link =& Piwi::CreateWidget('Link', _t('FILEBROWSER_INSERT'),
													"javascript: FileBrowserDialogue.insertFile('{$file['filename']}');");
						$actions.= $link->Get().'&nbsp;';
					}
					
                    //edit file properties
                    $link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
                                                "javascript: editFile(this, '{$file['filename']}');",
                                                STOCK_EDIT);
                    $actions.= $link->Get().'&nbsp;';

                    //delete file
                    $link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                                                "javascript: delFile(this, '{$file['filename']}');",
                                                STOCK_DELETE);
                    $actions.= $link->Get().'&nbsp;';
                }
            }

			$link =& Piwi::CreateWidget('Link', _t('GLOBAL_PREVIEW'),
										$file['url'],
										$GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mini_share.png');
			$actions.= $link->Get().'&nbsp;';
            $item['size']    = $file['size'];
            $item['actions'] = $actions;

            $tree[] = $item;
        }

        return $tree;
    }

    /**
     * Creates and returns some data
     *
     * @access  public
     * @param   string  $dir
     * @param   int     $offset
     * @return  array
     */
    function GetLocation($path)
    {
        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminModel');

        $dir_array = $model->GetCurrentRootDir($path);
        $path_link = '';
        $location_link = '';
        foreach ($dir_array as $d) {
            if ($d != '/') {
                $d .= '/';
            }
            $path_link .= $d;

            $link =& Piwi::CreateWidget('Link', $d, "javascript: cwd('{$path_link}');");
            $location_link .= $link->Get() . '&nbsp;';
        }

        return $location_link;
    }

    /**
     * Prints the admin section
     *
     * @access  public
     * @return  string  HTML content of administration
     */
    function Admin()
    {
        $this->CheckPermission('default');
        $this->AjaxMe('script.js');

        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminModel');
        $request =& Jaws_Request::getInstance();

        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('AdminFileBrowser.html');
        $tpl->SetBlock('filebrowser');
        $tpl->SetVariable('base_script', BASE_SCRIPT . '?gadget=FileBrowser&action=Admin');

        $request =& Jaws_Request::getInstance();
        $path = $request->get('path', 'get');
        $path = empty($path)? '/' : $path;

        $tpl->SetVariable('datagrid_name', 'fb_datagrid');
        $tpl->SetVariable('path', $path);
        $tpl->SetVariable('location', _t('FILEBROWSER_LOCATION'));
        $tpl->SetVariable('confirm_delete_file', _t('GLOBAL_CONFIRM_DELETE', _t('FILEBROWSER_FILE')));
        $tpl->SetVariable('confirm_delete_dir',  _t('GLOBAL_CONFIRM_DELETE', _t('FILEBROWSER_DIR')));
        $tpl->SetVariable('location_link', $this->GetLocation($path));

        $tpl->SetVariable('lbl_file',      _t('FILEBROWSER_FILE'));
        $tpl->SetVariable('lbl_directory', _t('FILEBROWSER_DIR'));
        $tpl->SetVariable('fui',  $this->GetFileUI());
        $tpl->SetVariable('dui',  $this->GetDirectoryUI());
        $tpl->SetVariable('grid', $this->Datagrid());
        $tpl->SetVariable('confirmFileDelete', _t('FILEBROWSER_CONFIRM_DELETE_FILE'));
        $tpl->SetVariable('confirmDirDelete',  _t('FILEBROWSER_CONFIRM_DELETE_DIR'));

        $tpl->ParseBlock('filebrowser');
        return $tpl->Get();
    }

    /**
     * UI for inserting files into things
     *
     * @access  public
     * @return  string  HTML content of administration
     */
    function FilePicker()
    {
        $this->CheckPermission('default');
        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminModel');
        $request =& Jaws_Request::getInstance();

        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('AdminFilePicker.html');
        $tpl->SetBlock('filepicker');
        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        $tpl->SetVariable('JAWS_DATA', $GLOBALS['app']->GetDataURL(), true);
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'FileBrowserAdminAjax' : 'FileBrowserAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));
        $tpl->SetVariable('form_action', BASE_SCRIPT . '?gadget=FileBrowser&action=Admin');

        $request =& Jaws_Request::getInstance();
        $path = $request->get('path', 'get');
        $path = empty($path)? '/' : $path;

        $tpl->SetVariable('datagrid_name', 'fp_datagrid');
        $tpl->SetVariable('path', $path);
        $tpl->SetVariable('location', _t('FILEBROWSER_LOCATION'));
        $tpl->SetVariable('confirm_delete_file', _t('GLOBAL_CONFIRM_DELETE', _t('FILEBROWSER_FILE')));
        $tpl->SetVariable('confirm_delete_dir',  _t('GLOBAL_CONFIRM_DELETE', _t('FILEBROWSER_DIR')));
        $tpl->SetVariable('location_link', $this->GetLocation($path));

        $tpl->SetVariable('lbl_file',      _t('FILEBROWSER_FILE'));
        $tpl->SetVariable('lbl_directory', _t('FILEBROWSER_DIR'));
        $tpl->SetVariable('fui',  $this->GetFileUI(true));
        $tpl->SetVariable('dui',  $this->GetDirectoryUI(true));
        $tpl->SetVariable('grid', $this->FilePickerDatagrid());
        $tpl->SetVariable('confirmFileDelete', _t('FILEBROWSER_CONFIRM_DELETE_FILE'));
        $tpl->SetVariable('confirmDirDelete',  _t('FILEBROWSER_CONFIRM_DELETE_DIR'));

        $tpl->ParseBlock('filepicker');
        return $tpl->Get();
    }

    /**
     * Show a form to edit a given file
     *
     * @access  public
     * @return  string HTML content
     */
    function GetFileUI()
    {
        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('AdminFileBrowser.html');
        $tpl->SetBlock('file_ui');

        $upload_switch =& Piwi::CreateWidget('CheckButtons', 'upload_switch');
        $upload_switch->AddEvent(ON_CLICK, 'javascript: uploadswitch(this.checked);');
        $upload_switch->AddOption(_t('FILEBROWSER_UPLOAD_FILE'), '0', 'upload_switch', true);
        $tpl->SetVariable('upload_switch', $upload_switch->Get());

        $filename =& Piwi::CreateWidget('Entry', 'filename', '');
        $filename->SetID('filename');
        $filename->SetStyle('width: 200px;');
        $tpl->SetVariable('lbl_filename', _t('FILEBROWSER_FILENAME'));
        $tpl->SetVariable('filename', $filename->Get());

        $uploadfile =& Piwi::CreateWidget('FileEntry', 'uploadfile', '');
        $uploadfile->SetID('uploadfile');
        $uploadfile->SetStyle('width: 208px;');
        $tpl->SetVariable('uploadfile', $uploadfile->Get());

        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $title =& Piwi::CreateWidget('Entry', 'file_title', '');
        $title->SetStyle('width: 200px;');
        $tpl->SetVariable('title', $title->Get());

        $desc =& Piwi::CreateWidget('TextArea', 'file_description', '');
        $desc->SetID('file_description');
        $desc->SetRows(5);
        $desc->SetStyle('width: 200px;');
        $tpl->SetVariable('lbl_description', _t('GLOBAL_DESCRIPTION'));
        $tpl->SetVariable('description', $desc->Get());

        $tpl->SetVariable('lbl_fast_url', _t('FILEBROWSER_FASTURL'));
        $fasturl =& Piwi::CreateWidget('Entry', 'file_fast_url', '');
        $fasturl->SetStyle('width: 200px;');
        $tpl->SetVariable('fast_url', $fasturl->Get());

        if ($this->GetPermission('ManageFiles')) {
            $btnSave =& Piwi::CreateWidget('Button', 'btn_save', _t('GLOBAL_SAVE'), STOCK_SAVE);
            $btnSave->AddEvent(ON_CLICK, "javascript: saveFile();");
            $tpl->SetVariable('btn_save', $btnSave->Get());
        }

        $btnCancel =& Piwi::CreateWidget('Button', 'btn_cancel', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $btnCancel->AddEvent(ON_CLICK, "javascript: stopAction('file');");
        $tpl->SetVariable('btn_cancel', $btnCancel->Get());

        $tpl->ParseBlock('file_ui');
        return $tpl->Get();
    }

    /**
     * Show a form to edit a given directory
     *
     * @access  public
     * @return  string HTML content
     */
    function GetDirectoryUI()
    {
        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('AdminFileBrowser.html');
        $tpl->SetBlock('dir_ui');

        $dirname =& Piwi::CreateWidget('Entry', 'dirname', '');
        $dirname->SetID('dirname');
        $dirname->SetStyle('width: 200px;');
        $tpl->SetVariable('lbl_dirname', _t('FILEBROWSER_DIR_NAME'));
        $tpl->SetVariable('dirname', $dirname->Get());

        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $title =& Piwi::CreateWidget('Entry', 'dir_title', '');
        $title->SetStyle('width: 200px;');
        $tpl->SetVariable('title', $title->Get());

        $desc =& Piwi::CreateWidget('TextArea', 'dir_description', '');
        $desc->SetID('dir_description');
        $desc->SetRows(5);
        $desc->SetStyle('width: 200px;');
        $tpl->SetVariable('lbl_description', _t('GLOBAL_DESCRIPTION'));
        $tpl->SetVariable('description', $desc->Get());

        $tpl->SetVariable('lbl_fast_url', _t('FILEBROWSER_FASTURL'));
        $fasturl =& Piwi::CreateWidget('Entry', 'dir_fast_url', '');
        $fasturl->SetStyle('width: 200px;');
        $tpl->SetVariable('fast_url', $fasturl->Get());

        if ($this->GetPermission('ManageDirectories')) {
            $btnSave =& Piwi::CreateWidget('Button', 'btn_save', _t('GLOBAL_SAVE'), STOCK_SAVE);
            $btnSave->AddEvent(ON_CLICK, "javascript: saveDir();");
            $tpl->SetVariable('btn_save', $btnSave->Get());
        }

        $btnCancel =& Piwi::CreateWidget('Button', 'btn_cancel', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $btnCancel->AddEvent(ON_CLICK, "javascript: stopAction('dir');");
        $tpl->SetVariable('btn_cancel', $btnCancel->Get());

        $tpl->ParseBlock('dir_ui');
        return $tpl->Get();
    }

    /**
     * Uploads a new file
     *
     * @access       public
     */
    function UploadFile()
    {
        $this->CheckPermission('default');
        $this->CheckPermission('UploadFiles');

        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminModel');
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('path', 'file_title', 'file_description', 'file_fast_url', 'oldname', 'picker'), 'post');
        $uploaddir = $model->GetFileBrowserRootDir() . $post['path'];

        require_once 'File/Util.php';
        $uploaddir = File_Util::realpath($uploaddir) . DIRECTORY_SEPARATOR;

        if (!File_Util::pathInRoot($uploaddir, $model->GetFileBrowserRootDir())) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_UPLOAD'), RESPONSE_ERROR);
        } else {
            $res = Jaws_Utils::UploadFiles($_FILES,
                                           $uploaddir,
                                           '',
                                           $GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/black_list'));
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
            } else {
                if (!empty($post['oldname'])) {
                    $model->Delete($post['path'], $post['oldname']);
                }
                $model->UpdateDBFileInfo($post['path'],
                                         $res['uploadfile'],
                                         $post['file_title'],
                                         $post['file_description'],
                                         $post['file_fast_url'],
                                         $post['oldname']);
            }
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=FileBrowser&action='.($post['picker'] == 'true' ? 'FilePicker' : 'Admin').'&path=' . $post['path']);
    }

    /**
     * Upload a file and add it to a DB table then redirect user to previous page
     * and notify the error
     *
     * @access  public
     */
    function AddFileToPost($account = false)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('FileBrowser', 'default');
		} else {
			if (!$GLOBALS['app']->Session->GetPermission('FileBrowser', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FileBrowser', 'OwnFile')) {
					$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		if ($account === false) {
	        $path = '/';
		} else {
	        $path = '/users/'.$GLOBALS['app']->Session->GetAttribute('user_id').'/';
		}
        /*
		$urlRedirect = 'index.php';
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $urlRedirect = $_SERVER['HTTP_REFERER'];
        }
		*/

        $request =& Jaws_Request::getInstance();
        $get    = $request->get(array('n', 'addtogadget', 'table', 'method', 'linkid', 'bc', 'where', 'types'), 'get');
		$post    = $request->get(array('fileframe', 'addtogadget', 'table', 'method', 'linkid', 'where', 'types'), 'post');

		$model = $GLOBALS['app']->LoadGadget('FileBrowser', 'AdminModel');

		$uploaddir = JAWS_DATA. 'files'. DIRECTORY_SEPARATOR. $path;

		require_once 'File/Util.php';
		$uploaddir = File_Util::realpath($uploaddir) . DIRECTORY_SEPARATOR;
		$new_filepath = '';
		$output_js = '';
		$output_html = '';
		if (!File_Util::pathInRoot($uploaddir, JAWS_DATA. 'files/')) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_UPLOAD').': Upload directory wrong', RESPONSE_ERROR);
			$result_msg = 'Upload directory wrong';
		} else {
			/*
			// retrieving message from cookie 
			$cookie = Jaws_Session_Web::GetCookie('msg');
			if (isset($cookie) && $cookie != '') {  
				$msg = $cookie;
				// clearing cookie, we're not going to display same message several times
				Jaws_Session_Web::SetCookie('msg', ''); 
			}
			*/

			if (!empty($get['n']) && !empty($get['addtogadget']) && !empty($get['table']) && !empty($get['method']) && (!empty($get['linkid']) || !empty($get['where']))) {
				$output_html .= "<html>\n";
				$output_html .= "<head>\n";
				$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
				$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/resources/public.css\" />\n";
				$output_html .= "</head>\n";
				$output_html .= "<body style=\"background: ".(!empty($get['bc']) ? $get['bc'] : '#FFFFFF')." url();\">\n";
				//$output_html .= "<h1>Upload file:</h1>\n";
				//$output_html .= "<p>File will begin to upload just after selection. </p>\n";
				//$output_html .= "<p>You may write file description, while you file is being uploaded.</p>\n";
				$output_html .= "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">\n";
				$output_html .= "<tbody>\n";
				$output_html .= "<tr>\n";
				$output_html .= "<td>\n";
				$output_html .= "<form action=\"".($account === true ? 'index' : 'admin').".php?gadget=FileBrowser&action=".($account === true ? 'account_' : '')."AddFileToPost&n=".$get['n']."&bc=".$get['table']."\" target=\"upload_iframe\" method=\"post\" enctype=\"multipart/form-data\">\n";
				$output_html .= "<input type=\"hidden\" name=\"fileframe\" value=\"true\">\n";
				$output_html .= "<!-- Target of the form is set to hidden iframe -->\n";
				$output_html .= "<!-- Form will send its post data to fileframe section of this PHP script (see above) -->\n";
				$output_html .= "<input type=\"file\" name=\"file\" id=\"file\" onChange=\"jsUpload(this);\">\n";
				$output_html .= "<input type=\"hidden\" name=\"table\" id=\"table\" value=\"".$get['table']."\" />\n";
				$output_html .= "<input type=\"hidden\" name=\"addtogadget\" id=\"addtogadget\" value=\"".$get['addtogadget']."\" />\n";
				$output_html .= "<input type=\"hidden\" name=\"method\" id=\"method\" value=\"".$get['method']."\" />\n";
				if (!empty($get['linkid'])) {
					$output_html .= "<input type=\"hidden\" name=\"linkid\" id=\"linkid\" value=\"".$get['linkid']."\" />\n";
				} else {
					$output_html .= "<input type=\"hidden\" name=\"where\" id=\"where\" value=\"".$get['where']."\" />\n";
				}
				if (!empty($get['types'])) {
					$allowFormats = 'txt,css,jpg,jpeg,gif,png,pdf,doc,bmp,mp3,wmv,swf,flv,tiff,tif,scg,wav,flac,aac,wma,ogg,midi,ac3,mov,avi,mpg,mpeg,raw';
					$allowFormats .= ($account === false ? ',rtf,sxw,odt,odf,htm,html' : '');
					$types = explode(',',$get['types']);
					foreach ($types as $type) {
						if (!in_array($type, explode(',', $allowFormats))) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_UPLOAD').': Requested filetype(s) unsupported ('.$type.').', RESPONSE_ERROR);
							$msg = _t('GLOBAL_ERROR_UPLOAD').': Requested filetype(s) unsupported ('.$type.').';
						}
					}
					$output_html .= "<input type=\"hidden\" name=\"types\" id=\"types\" value=\"".$get['types']."\" />\n";
				} else {
					$output_html .= "<input type=\"hidden\" name=\"types\" id=\"types\" value=\"jpg,jpeg,gif,png,bmp,tiff,tif\" />\n";
				}
				$output_html .= "</form>\n";
				$output_html .= "</td>\n";
				/*
				$output_html .= "<td>\n";
				$output_html .= "<input type=\"text\" name=\"upload_status\" id=\"upload_status\" value=\"Status\" size=\"30\" disabled>\n";
				$output_html .= "</td>\n";
				$output_html .= "<td>\n";
				$output_html .= "File name:&nbsp;<input type=\"text\" name=\"filenamei\" id=\"filenamei\" value=\"\" disabled>\n";
				$output_html .= "</td>\n";
				*/
				$output_html .= "<td>\n";
				$output_html .= "	<div id=\"upload_status\" style=\"font-size: x-small; font-weight: bold;\">\n";
				if (isset($msg)) {
					$output_html .= $msg;
				}
				$output_html .= "	</div>\n";
				//$output_html .= "</td>\n";
				//$output_html .= "<td>\n";
				$output_html .= "<form action=\"".($account === true ? 'index' : 'admin').".php?gadget=FileBrowser&action=".($account === true ? 'account_' : '')."AddFileToPost&n=".$get['n']."&bc=".$get['table']."\" method=\"POST\">\n";
				$output_html .= "<!-- one field is \"disabled\" for displaying-only. Other, hidden one is for sending data -->\n";
				$output_html .= "<input type=\"hidden\" name=\"filename\" id=\"filename\">\n";
				//$output_html .= "<label for=\"photo\">File description:</label>&nbsp;<textarea rows=\"5\" cols=\"30\" name=\"description\"></textarea>\n";
				$output_html .= "<input type=\"hidden\" name=\"table\" id=\"table\" value=\"".$get['table']."\" />\n";
				$output_html .= "<input type=\"hidden\" name=\"addtogadget\" id=\"addtogadget\" value=\"".$get['addtogadget']."\" />\n";
				$output_html .= "<input type=\"hidden\" name=\"method\" id=\"method\" value=\"".$get['method']."\" />\n";
				if (!empty($get['linkid'])) {
					$output_html .= "<input type=\"hidden\" name=\"linkid\" id=\"linkid\" value=\"".$get['linkid']."\" />\n";
				} else {
					$output_html .= "<input type=\"hidden\" name=\"where\" id=\"where\" value=\"".$get['where']."\" />\n";
				}
				//$output_html .= "<input type=\"submit\" id=\"upload_button\" value=\"save file\" disabled>\n";
				$output_html .= "</td>\n";
				$output_html .= "</tr>\n";
				$output_html .= "<tr>\n";
				$output_html .= "</tr>\n";
				$output_html .= "</tbody>\n";
				$output_html .= "</table>\n";
				$output_html .= "</form>\n";
				$output_html .= "<script type=\"text/javascript\">\n";
				$output_html .= "function jsUpload(upload_field) {\n";
				$output_html .= "	upload_field.form.submit();\n";
				$output_html .= "   document.getElementById('upload_status').innerHTML = \"Uploading file...\";\n";
				$output_html .= "   upload_field.disabled = true;\n";
				$output_html .= "   return true;\n";
				$output_html .= "}\n";
				$output_html .= "function remUpload() {\n";
				$output_html .= "	if (window.parent.document) {\n";
				$output_html .= "		var parDoc = window.parent.document;\n";
				$output_html .= "		if (parDoc.getElementById(\"iframe_".$get['n']."\") && parDoc.getElementById(\"iframe_".$get['n']."\").parentNode) {\n";
				$output_html .= "			parDoc.getElementById(\"iframe_".$get['n']."\").parentNode.removeChild(parDoc.getElementById(\"iframe_".$get['n']."\"));";
				$output_html .= "   		return true;\n";
				$output_html .= "   	} else {\n";
				$output_html .= "   		return false;\n";
				$output_html .= "   	}\n";
				$output_html .= "   } else {\n";
				$output_html .= "   	return false;\n";
				$output_html .= "   }\n";
				$output_html .= "}\n";
				$output_html .= "</script>\n";
				$output_html .= "<iframe name=\"upload_iframe\" style=\"width: 400px; height: 100px; display: none;\"></iframe>\n";
				$output_html .= "</body>\n";
				$output_html .= "</html>\n";
				/*
				echo '<br />html:';
				var_dump($output_html);
				echo '<br />js:';
				var_dump($output_js);
				*/
				return $output_html;
			}
			if (!empty($post['fileframe']) && !empty($post['addtogadget']) && !empty($post['table']) && !empty($post['method']) && !empty($post['types']) && (!empty($post['linkid']) || !empty($post['where']))) {
				$result_code = 'ERROR';
				$result_msg = 'No FILE field found';
				$allowFormats = 'txt,css,jpg,jpeg,gif,png,pdf,doc,bmp,mp3,wmv,swf,flv,tiff,tif,scg,wav,flac,aac,wma,ogg,midi,ac3,mov,avi,mpg,mpeg,raw';
				$allowFormats .= ($account === false ? ',rtf,sxw,odt,odf,htm,html' : '');
				$types = explode(',',$post['types']);
				foreach ($types as $type) {
					if (!in_array($type, explode(',', $allowFormats))) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_UPLOAD').': Requested filetype(s) unsupported ('.$type.').', RESPONSE_ERROR);
						$result_msg = _t('GLOBAL_ERROR_UPLOAD').': Requested filetype(s) unsupported ('.$type.').';
					}
				}
				// output trivial html with javascript code 
				// (return data to document)

				// this code is output to IFRAME (embedded frame)
				if (!empty($post['linkid']) && $post['table'] != 'NULL') {
					// send highest sort_order
					$sql = "SELECT MAX([sort_order]) FROM [[".$post['table']."]] ORDER BY [sort_order] DESC";
					$max = $GLOBALS['db']->queryOne($sql);
					if (Jaws_Error::IsError($max)) {
						$GLOBALS['app']->Session->PushLastResponse($max->getMessage(), RESPONSE_ERROR);
						$result_msg = $max->getMessage();
					} else {
						$max = (is_numeric($max) ? $max+1 : 0);
					}	
				}
				$i = 0;
				$GLOBALS['app']->Translate->LoadTranslation($post['addtogadget'], JAWS_GADGET);
				$gadgetmodel = $GLOBALS['app']->LoadGadget($post['addtogadget'], 'AdminModel');
				foreach ($_FILES as $file) {
					$res = Jaws_Utils::UploadFiles($file,
												   $uploaddir,
												   $post['types'],
												   $GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/black_list'));
					if (Jaws_Error::IsError($res)) {
						if (strpos($res->getMessage(), ':') !== false) {
							$GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
						}
						if (strpos($res->getMessage(), "Invalid format") !== false) {
							$result_msg = "Allowed file types are: ".$post['types'];
						} else {
							$result_msg = $res->getMessage();
						}
					} else {
						if (!empty($post['description'])) {
							// TODO: Add description entries here
						}
						$new_filepath = $path.$res[0];
						if (!empty($post['linkid']) && $post['method'] != 'NULL') {
							if (method_exists($gadgetmodel, $post['method'])) {
								$result = $gadgetmodel->$post['method'](($max+$i), (int)$post['linkid'], '', '', $new_filepath);
								if (Jaws_Error::IsError($result)) {
									$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
									$result_msg = $result->getMessage();
								} else {
									if (is_numeric($result) && $account === true) {
										$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
										$sql = 'UPDATE [['.$post['table'].']] SET
											[ownerid] = {OwnerID}
										WHERE [id] = {id}';
										$params               	= array();
										$params['id']         	= $result;
										$params['OwnerID']      = $OwnerID;
										$res1 = $GLOBALS['db']->query($sql, $params);
										if (Jaws_Error::IsError($res1)) {
											$GLOBALS['app']->Session->PushLastResponse($res1->getMessage(), RESPONSE_ERROR);
											/*
											echo '<br />html:';
											var_dump($output_html);
											echo '<br />js:';
											var_dump($output_js);
											*/
											return false;
										}
									}
									if ($result == 'Main Image Added') {
										$result_code = $result;
									} else {
										$result_code = 'OK';
									}
								}
							} else {
								$result_msg = "'".$post['method']."' method doesn't exist.";
							}
						} else {
							$result_code = 'OK';
						}
					}
					
					if ($result_code == 'OK' || $result_code == 'Main Image Added') {
						// Simply updating status of fields and submit button
						$output_js .= '<html><head><title>-</title></head><body>';
						$output_js .= '<script language="JavaScript" type="text/javascript">'."\n";
						$output_js .= 'var parDoc = window.parent.document;';
						$output_js .= 'if (parDoc) {';
						$output_js .= '	parDoc.getElementById("upload_status").innerHTML = "File '.$res[0].' uploaded";';
						$output_js .= '	parDoc.getElementById("filename").value = "'.$res[0].'";';
						$output_js .= '	parDoc.getElementById("file").disabled = false;';
						if ($result_code == 'Main Image Added') {
							$output_js .= 'if (parent.parent.document.getElementById("Image")){parent.parent.document.getElementById("Image").value = "'.$new_filepath.'";}';
							$output_js .= 'if (parent.parent.document.getElementById("image")){parent.parent.document.getElementById("image").value = "'.$new_filepath.'";}';
						}
						$output_js .= "	var response = new Array();";
						$output_js .= "	response[0] = new Array();";
						$output_js .= "	response[0]['message'] = \""._t('GLOBAL_FILE_UPLOADED', $res[0])."\";";
						$output_js .= "	response[0]['css'] = 'notice-message';";
						if (!empty($post['linkid'])) {
							$output_js .= "	response[1] = new Array();";
							$output_js .= "	response[1]['message'] = \"The post has been created.\";";
							$output_js .= "	response[1]['css'] = 'notice-message';";
						} else {
							$output_js .= '	if (parent.parent.document.getElementById("'.$post['where'].'")){parent.parent.document.getElementById("'.$post['where'].'").value = "'.$new_filepath.'";}';
						}
						$output_js .= '	parent.parent.showResponse(response);';
						$i++;
					} else {
						$output_js .= '<html><head><title>-</title></head><body>';
						$output_js .= '<script language="JavaScript" type="text/javascript">'."\n";
						$output_js .= 'var parDoc = window.parent.document;';
						$output_js .= 'if (parDoc) {';
						$output_js .= '	parDoc.getElementById("upload_status").innerHTML = "ERROR: '.$result_msg.'";';
						$output_js .= '	parDoc.getElementById("file").disabled = false;';
						$output_js .= "	var response = new Array();";
						$output_js .= "	response[0] = new Array();";
						$output_js .= "	response[0]['message'] = \""._t('GLOBAL_ERROR_UPLOAD')."\";";
						$output_js .= "	response[0]['css'] = 'error-message';";
						$output_js .= '	parent.parent.showResponse(response);';
						$output_js .= '}';
						$output_js .= "\n".'</script></body></html>';
						/*
						echo '<br />html:';
						var_dump($output_html);
						echo '<br />js:';
						var_dump($output_js);
						*/
						return $output_js;
					}
				}
				$output_js .= '}';
				$output_js .= "\n".'</script></body></html>';
				if (!$GLOBALS['app']->deleteSyntactsCacheFile(array($post['addtogadget']))) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_UPLOAD').': Could not delete cache file for '.$post['addtogadget'].' gadget.', RESPONSE_ERROR);
				}
				//if (isset($res['file']) && !empty($res['file'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_FILE_UPLOADED'), RESPONSE_NOTICE);
					/*
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_FILE_UPLOADED', $res['file']), RESPONSE_NOTICE);
					echo '<br />html:';
					var_dump($output_html);
					echo '<br />js:';
					var_dump($output_js);
					*/
					return $output_js;
				//}
			}
		}
     
		if (empty($output_js)) {
			$result_msg = (isset($result_msg) ? $result_msg : _t('GLOBAL_ERROR_UPLOAD'));
			$output_js .= '<html><head><title>-</title></head><body>';
			$output_js .= '<script language="JavaScript" type="text/javascript">'."\n";
			$output_js .= 'var parDoc = window.parent.document;';
			$output_js .= 'if (parDoc) {';
			$output_js .= '	parDoc.getElementById("upload_status").value = "ERROR: '.$result_msg.'";';
			$output_js .= '	parDoc.getElementById("file").disabled = false;';
			$output_js .= "	var response = new Array();";
			$output_js .= "	response[0] = new Array();";
			$output_js .= "	response[0]['message'] = \""._t('GLOBAL_ERROR_UPLOAD')."\";";
			$output_js .= "	response[0]['css'] = 'error-message';";
			$output_js .= '	parent.parent.showResponse(response);';
			$output_js .= '}';
			$output_js .= "\n".'</script></body></html>';
			/*
			echo '<br />html:';
			var_dump($output_html);
			echo '<br />js:';
			var_dump($output_js);
			*/
			return $output_js;
        }
		/*
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        if ($post['redirect_to'] != '') {
            $urlRedirect = $post['redirect_to'];
			$urlRedirect = isset($urlRedirect)? $urlRedirect : '';
            if (substr($urlRedirect, 0, 1) == '?') {
				$urlRedirect = str_replace('&amp;', '&', $urlRedirect);
				$urlRedirect = 'index.php'.$urlRedirect;
			}
			if (substr($urlRedirect, 0, 4) != 'http') {
				$urlRedirect = $GLOBALS['app']->getSiteURL().'/'.$urlRedirect;			
			}
        }
		Jaws_Header::Location($urlRedirect);
		exit;
		*/
	}
}