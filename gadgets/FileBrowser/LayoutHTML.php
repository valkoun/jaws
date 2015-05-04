<?php
/**
 * FileBrowser Layout HTML file (for layout purposes)
 *
 * @category   GadgetLayout
 * @package    FileBrowser
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class FileBrowserLayoutHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions()
    {
        if (!$GLOBALS['app']->Session->GetPermission('FileBrowser', 'OutputAccess')) {
            return false;
        }
        
        if ($GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/frontend_avail') != 'true') {
            return $actions;
        }
		
		$actions = array();
        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'Model');

		$actions['InitialFolder'] = array(
			'mode' => 'LayoutAction',
            'name' => _t('FILEBROWSER_INITIAL_FOLDER'),
            'desc' => _t('FILEBROWSER_INITIAL_FOLDER_DESC')
		);

        $items = $model->ReadDir('/');
        if (!Jaws_Error::IsError($items)) {
            $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            foreach ($items as $item) {
                if ($item['is_dir'] === true && $item['filename'] != 'css' && $item['filename'] != 'medium' && substr($item['filename'], -6) != 'medium' && $item['filename'] != 'thumb' && substr($item['filename'], -5) != 'thumb' ) {
					$actions['InitialFolder("' . $item['filename'] . '/")'] = array(
						'mode' => 'LayoutAction',
						'name' => $item['filename'],
						'desc' => _t('FILEBROWSER_SUB_FOLDER_DESC')
					);
					$items2 = $model->ReadDir($item['filename'].'/');
					if (!Jaws_Error::IsError($items2)) {
						foreach ($items2 as $item2) {
							if ($item2['is_dir'] === true && substr($item2['filename'], -6) != 'medium' && substr($item2['filename'], -5) != 'thumb' ) {
								$actions['InitialFolder("' . $item['filename'] . '/'. $item2['filename'] . '/")'] = array(
									'mode' => 'LayoutAction',
									'name' => $item['filename'] . '/'. $item2['filename'],
									'desc' => _t('FILEBROWSER_SUB_FOLDER_DESC')
								);
								$items3 = $model->ReadDir($item['filename'] . '/'. $item2['filename'] . '/');
								if (!Jaws_Error::IsError($items3)) {
									foreach ($items3 as $item3) {
										if ($item3['is_dir'] === true && substr($item3['filename'], -6) != 'medium' && substr($item3['filename'], -5) != 'thumb' ) {
											$actions['InitialFolder("' . $item['filename'] . '/'. $item2['filename'] . '/'. $item3['filename'] . '/")'] = array(
												'mode' => 'LayoutAction',
												'name' => $item['filename'] . '/'. $item2['filename'] . '/' . $item3['filename'],
												'desc' => _t('FILEBROWSER_SUB_FOLDER_DESC')
											);
											$items4 = $model->ReadDir($item['filename'] . '/'. $item2['filename'] . '/' . $item3['filename'] . '/');
											if (!Jaws_Error::IsError($items4)) {
												foreach ($items4 as $item4) {
													if ($item4['is_dir'] === true && substr($item4['filename'], -6) != 'medium' && substr($item4['filename'], -5) != 'thumb' ) {
														$actions['InitialFolder("' . $item['filename'] . '/'. $item2['filename'] . '/' . $item3['filename'] . '/'. $item4['filename'] . '/")'] = array(
															'mode' => 'LayoutAction',
															'name' => $item['filename'] . '/'. $item2['filename'] . '/' . $item3['filename'] . '/'. $item4['filename'],
															'desc' => _t('FILEBROWSER_SUB_FOLDER_DESC')
														);
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
            }
        }
        return $actions;
	}	
		
    /**
     * Prints all the files with their titles and contents of initial folder
     *
     * @access  public
     * @return  string  HTML content with titles and contents
     */
    function InitialFolder($path = '')
    {
        if (!$GLOBALS['app']->Session->GetPermission('FileBrowser', 'OutputAccess')) {
            return false;
        }

        if ($GLOBALS['app']->Registry->Get('/gadgets/FileBrowser/frontend_avail') != 'true') {
            return false;
        }

        $tpl = new Jaws_Template('gadgets/FileBrowser/templates/');
        $tpl->Load('FileBrowser.html');
        $tpl->SetBlock('initial_folder');
		$tpl->SetVariable('actionName', str_replace(' ', '-', _t('FILEBROWSER_INITIAL_FOLDER')));
		$tpl->SetVariable('title', _t('FILEBROWSER_NAME'));

        $model = $GLOBALS['app']->LoadGadget('FileBrowser', 'Model');
		$path = substr($path, 0, 1) == '"' ? substr($path, 1, strlen($path)) : $path;
		$path = substr($path, -1) == '"' ? substr($path, 0, strlen($path)-1) : $path;
        $items = $model->ReadDir($path);
        if (!Jaws_Error::IsError($items)) {
            $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            foreach ($items as $item) {
                if ($item['filename'] != 'css' && $item['filename'] != 'medium' && substr($item['filename'], -6) != 'medium' && $item['filename'] != 'thumb' && substr($item['filename'], -5) != 'thumb' ) {
					$tpl->SetBlock('initial_folder/item');
					$tpl->SetVariable('icon',  $item['mini_icon']);
					$tpl->SetVariable('name',  $xss->filter($item['filename']));
					$tpl->SetVariable('title', $xss->filter($item['title']));
					$tpl->SetVariable('url',   $xss->filter($item['url']));
					$tpl->ParseBlock('initial_folder/item');
				}
			}
        }
        $tpl->ParseBlock('initial_folder');

        return $tpl->Get();
    }

}