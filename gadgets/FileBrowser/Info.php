<?php
/**
 * Upload and manage files and directories.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    FileBrowser
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class FileBrowserInfo extends Jaws_GadgetInfo
{
    function FileBrowserInfo()
    {
        parent::Init('FileBrowser');
        $this->GadgetName(_t('FILEBROWSER_NAME'));
        $this->GadgetDescription(_t('FILEBROWSER_DESC'));
        $this->GadgetVersion('0.8.1');
        $this->Doc('gadgets/FileBrowser');

        $acls = array(
            'default',
            /*
			'AddFile',
            'RenameFile',
            'DeleteFile',
            'AddDir',
            'RenameDir',
            'DeleteDir',
			*/
			'UploadFiles',
			'ManageFiles',
			'ManageDirectories',
            'OwnFile',
            'OutputAccess' => 'true',
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}