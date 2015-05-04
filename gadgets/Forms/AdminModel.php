<?php
/**
 * Forms Gadget
 *
 * @category   GadgetModel
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/Forms/Model.php';
class FormsAdminModel extends FormsModel
{
    var $_Name = 'Forms';
	
	/**
     * Install the gadget
     *
     * @access  public
     * @return  boolean  Success/failure
     */
    function InstallGadget()
    {

        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }
        
        if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/schema/insert.xml')) {
			$variables = array();
			$variables['timestamp'] = $GLOBALS['db']->Date();
			$variables['site_name'] = "__SITE_NAME__";
			$variables['site_address'] = "__SITE_ADDRESS__";
			$variables['site_office'] = "__SITE_OFFICE__";
			$variables['site_tollfree'] = "__SITE_TOLLFREE__";
			$variables['site_cell'] = "__SITE_CELL__";
			$variables['site_fax'] = "__SITE_FAX__";
			//$variables['site_email'] = "__SITE_EMAIL__";

			$result = $this->installSchema('insert.xml', $variables, 'schema.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}

		// Listeners and Shouters
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onAddForm');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onUpdateForm');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onDeleteForm');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onAddFormPost');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onUpdateFormPost');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onDeleteFormPost');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onAddFormPostAnswer');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onUpdateFormPostAnswer');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onDeleteFormPostAnswer');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onBeforeFormSend');          
        $GLOBALS['app']->Shouter->NewShouter('Forms', 'onAfterFormSend');          
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		$GLOBALS['app']->Listener->NewListener('Forms', 'onAfterEnablingGadget', 'InsertDefaultFormToMenu');
		$GLOBALS['app']->Listener->NewListener('Forms', 'onAfterEnablingGadget', 'InsertDefaultChecksums');
		
		$GLOBALS['app']->Registry->NewKey('/gadgets/Forms/default_recipient', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Forms/site_address', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Forms/site_office', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Forms/site_tollfree', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Forms/site_cell', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Forms/site_fax', '');

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UninstallGadget()
    {
        $tables = array('forms',
                        'form_questions',
						'form_answers');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('FORMS_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

		// Listeners and Shouters
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onAddForm');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onUpdateForm');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onDeleteForm');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onAddFormPost');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onUpdateFormPost');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onDeleteFormPost');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onAddFormPostAnswer');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onUpdateFormPostAnswer');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onDeleteFormPostAnswer');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onBeforeFormSend');          
        $GLOBALS['app']->Shouter->DeleteShouter('Forms', 'onAfterFormSend');          
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('Forms', 'InsertDefaultFormToMenu');
		$GLOBALS['app']->Listener->DeleteListener('Forms', 'InsertDefaultChecksums');

        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Forms/default_recipient');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Forms/site_address', '');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Forms/site_office', '');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Forms/site_tollfree', '');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Forms/site_cell', '');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Forms/site_fax', '');

        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old Current version (in registry)
     * @param   string  $new     New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (JawsError)
     */
    function UpdateGadget($old, $new)
    {
        if (version_compare($old, '0.1.1', '<')) {			
			$result = $this->installSchema('schema.xml', '', '0.1.0.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
		
        $currentClean = str_replace(array('.', ' '), '', $old);
        $newClean     = str_replace(array('.', ' '), '', $new);

        $funcName   = 'upgradeFrom' . $currentClean;
        $scriptFile = JAWS_PATH . 'gadgets/' . $this->_Name . '/upgradeScripts/' . $funcName . '.php';
        if (file_exists($scriptFile)) {
            require_once $scriptFile;
            //Ok.. append the funcName at the start
            $funcName = $this->_Name . '_' . $funcName;
            if (function_exists($funcName)) {
                $res = $funcName();
                return $res;
            }
        }
        return true;
    }
				
    /**
     * Creates a new form.
     *
     * @access  public
     * @param   string  $title          The new title of the page.
     * @param   string  $description    The description (META) of page
     * @param   string  $active         (Y/N) If the page is published or not
     * @param   boolean $auto       		If it's auto saved or not
     * @return  bool    Success/failure
     */
    function AddForm($sort_order = 0, $title, $sm_description, $description, $Clause,
		$image, $recipient, $parent = 0, $custom_action, $Active = 'Y', $OwnerID = null, 
		$submit_content = '', $checksum = '', $auto = false)
    {
		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
		if (empty($title)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_INVALID_TITLE'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Forms&action=form');
		}

		// Get the fast url
		$fast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $title));
        $fast_url = $this->GetRealFastUrl(
			$fast_url, 'forms', true
		);
        
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
		
		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br>');
		$Clause = strip_tags($Clause, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br>');
		$parent = (int)$parent;
		$submit_content = (!empty($submit_content) ? htmlentities($submit_content) : '');
		
		$sql = "
            INSERT INTO [[forms]]
                ([sort_order], [title], [sm_description], [description], 
			   [clause], [image], [recipient], [parent], [custom_action], 
			   [fast_url], [active], [ownerid], [created], [updated], [submit_content], [checksum])
            VALUES
                ({sort_order}, {title}, {sm_description}, {description}, 
				{Clause}, {image}, {recipient}, {parent}, {custom_action}, 
				{fast_url}, {Active}, {OwnerID}, {now}, {now}, {submit_content}, {checksum})";

        $params               		= array();
        $params['sort_order'] 		= (int)$sort_order;
        $params['title'] 			= strip_tags($title);
        $params['sm_description']   = strip_tags($sm_description);
        $params['description']   	= str_replace("\r\n", "\n", $description);
        $params['Clause'] 			= str_replace("\r\n", "\n", $Clause);
        $params['image'] 			= $image;
        $params['recipient'] 		= $recipient;
        $params['parent'] 			= $parent;
		$params['custom_action'] 	= $custom_action;
		$params['fast_url'] 		= $fast_url;
        $params['Active'] 			= $Active;
		$params['OwnerID']         	= $OwnerID;
		$params['submit_content']   = $submit_content;
		$params['checksum']   		= $checksum;
        $params['now']        		= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('forms', 'id');

		if (BASE_SCRIPT != 'index.php') {
			
			$visible = ($Active == 'Y') ? 1 : 0;
			$url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $fast_url));
							
			// get parent menus			
			$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
			$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
			if (Jaws_Error::IsError($oid)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
				return false;
			} else {
				if (empty($oid['id'])) {
					// Get highest rank of current menu items
					$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = 1 ORDER BY [rank] DESC";
					$rank = $GLOBALS['db']->queryOne($sql);
					if (Jaws_Error::IsError($rank)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
					$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
					if (!$menuAdmin->InsertMenu($parent, 1, 'Forms', $title, $url, 0, (int)$rank+1, $visible, true)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				}
			}
		}

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[forms]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddForm', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_FORM_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a form.
     *
     * @access  public
     * @param   int     $id             The ID of the page to update.
     * @param   string  $title        The new title of the page.
     * @param   string  $description    The description of page
     * @param   string  $active        (Y/N) If the page is published or not
     * @param   boolean $auto           If it's auto saved or not
     * @return  boolean Success/failure
     */
    function UpdateForm($id, $sort_order, $title, $sm_description, $description, $Clause,
		$image, $recipient, $parent, $custom_action, $Active, $submit_content, $auto = false)
    {
		if (empty($title)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_INVALID_INVALID'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Forms&action=form&id='.$id);
		}
		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
        $page = $model->GetForm((int)$id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }

		// Get the fast url
		$fast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $title));
        $fast_url = $this->GetRealFastUrl(
			$fast_url, 'forms', true, 'fast_url', 'id', $id
		);
        
        //Current fast url changes?
        if ($page['fast_url']  != $fast_url && $auto === false) {
            $oldfast_url = $page['fast_url'];
        }
        		
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$page['ownerid'] > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br>');
		$Clause = strip_tags($Clause, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br>');
		$parent = (int)$parent;
		$submit_content = (!empty($submit_content) ? htmlentities($submit_content) : '');
		
        $sql = '
            UPDATE [[forms]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[sm_description] = {sm_description}, 
				[description] = {description}, 
			   [clause] = {Clause}, 
			   [image] = {image}, 
			   [recipient] = {recipient}, 
			   [parent] = {parent}, 
			   [custom_action] = {custom_action}, 
			   [fast_url] = {fast_url}, 
			   [active] = {Active}, 
			   [updated] = {now},
			   [submit_content] = {submit_content}
			WHERE [id] = {id}';

        $params               		= array();
        $params['id']         		= (int)$id;
        $params['sort_order'] 		= (int)$sort_order;
        $params['title'] 			= strip_tags($title);
        $params['sm_description']   = strip_tags($sm_description);
        $params['description']   	= str_replace("\r\n", "\n", $description);
        $params['Clause'] 			= str_replace("\r\n", "\n", $Clause);
        $params['image'] 			= $image;
        $params['recipient'] 		= $recipient;
        $params['parent'] 			= $parent;
		$params['custom_action'] 	= $custom_action;
		$params['fast_url'] 		= $fast_url;
        $params['Active'] 			= $Active;
		$params['submit_content']   = $submit_content;
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_UPDATED'), RESPONSE_ERROR);
            return $result;
        }
				
		if (BASE_SCRIPT != 'index.php') {
			// update Menu Item for Page			
			$visible = ($Active == 'Y') ? 1 : 0;
			// if old title is different, update menu item
			if (isset($oldfast_url) && !empty($oldfast_url)) {
				$old_url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $oldfast_url));
			} else {
				$old_url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $fast_url));
			}
			$new_url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $fast_url));
			
			$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
			$oid = $GLOBALS['db']->queryRow($sql, array('url' => $old_url));
			if (Jaws_Error::IsError($oid)) {
				//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
				$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
				return false;
			} else if (!empty($oid['id']) && isset($oid['id'])) {
				$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
				if (!$menuAdmin->UpdateMenu($oid['id'], $parent, 1, 'Forms', $title, $new_url, 0, $oid['rank'], $visible)) {
					//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
					return false;
				}
			} else {				
				$visible = ($Active == 'Y') ? 1 : 0;
				$url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $fast_url));
												
				$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
				$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
				if (Jaws_Error::IsError($oid)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				} else {
					if (empty($oid['id'])) {
						$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
						if (!$menuAdmin->InsertMenu($parent, 1, 'Forms', $title, $url, 0, 1, $visible, true)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
					} else {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
				}
			}
		}

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateForm', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_FORM_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_FORM_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }

	/**
     * Delete a form
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteForm($id)
    {
		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
		$parent = $model->GetForm((int)$id);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('FORMS_ERROR_FORM_NOT_DELETED'), _t('FORMS_NAME'));
		}

		if(!isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('FORMS_ERROR_FORM_NOT_DELETED'), _t('FORMS_NAME'));
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteForm', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			$rids = $model->GetAllPostsOfForm($parent['id']);
			if (Jaws_Error::IsError($rids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('FORMS_ERROR_FORM_NOT_DELETED'), _t('FORMS_NAME'));
			}

			foreach ($rids as $rid) {
				if (!$this->DeletePost($rid['id'], true)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_DELETED'), _t('FORMS_NAME'));
				}
			}
		
			$sql = 'DELETE FROM [[forms]] WHERE [id] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('FORMS_ERROR_FORM_NOT_DELETED'), _t('FORMS_NAME'));
			}
			
			if (BASE_SCRIPT != 'index.php') {
				// delete menu item for page
				$url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $parent['fast_url']));

				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onDeleteMenuItem', $url);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}
			}

		}

		$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_FORM_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes a group of forms
     *
     * @access  public
     * @param   array   $pages  Array with the ids of forms
     * @return  bool    Success/failure
     */
    function MassiveDelete($pages)
    {
        if (!is_array($pages)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FORMS_ERROR_FORM_NOT_MASSIVE_DELETED'), _t('FORMS_NAME'));
        }

        foreach ($pages as $page) {
            $res = $this->DeleteForm($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_FORM_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('FORMS_ERROR_FORM_NOT_MASSIVE_DELETED'), _t('FORMS_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_FORM_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Create unlimited questions per form: textboxes, textareas, hidden fields, radio buttons, checkboxes, select boxes, etc.
     *
     * @category  feature
     * @param   integer  $sort_order 	Priority order
     * @param   integer  $formID 	The form ID this question belongs to.
     * @param   string  $title    	The title of the question.
     * @param   string  $itype  (HiddenField/TextBox/TextArea/RadioBtn/CheckBox/SelectBox).
     * @param   string  $required 	(Y/N) If the question is marked as required 
     * @param   integer 	$OwnerID  		The poster's user ID
     * @param   string 	$checksum       		Unique ID
     * @access  public
     * @return  ID of entered post 	    Success/failure
     */
    function AddPost($sort_order = 0, $formID, $title, $itype = 'TextBox', $required = 'N', $OwnerID = null, $checksum = '')
    {
		if ($itype == 'HiddenField') {
			$required = 'N';
		}
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;

		$sql = "
            INSERT INTO [[form_questions]]
                ([sort_order], [formid], [title], 
				[itype], [required], [ownerid], [created], [updated], [checksum])
            VALUES
                ({sort_order}, {formID}, {title}, 
				{itype}, {required}, {OwnerID}, {now}, {now}, {checksum})";

        $params               		= array();
        $params['sort_order']       = (int)$sort_order;
        $params['title'] 			= strip_tags($title);
		$params['formID'] 			= (int)$formID;
        $params['itype'] 			= $itype;
		$params['OwnerID']         	= $OwnerID;
        $params['required'] 		= $required;
        $params['checksum'] 		= $checksum;
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_ADDED'), _t('FORMS_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('form_questions', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[form_questions]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddFormPost', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_POST_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a question.
     *
     * @access  public
     * @param   int     $id             The ID of the question to update.
     * @param   integer  $sort_order 	Priority order
     * @param   integer  $formID 	The form ID this question belongs to.
     * @param   string  $title    	The title of the question.
     * @param   string  $itype  (HiddenField/TextBox/TextArea/RadioBtn/CheckBox/SelectBox).
     * @param   string  $required 	(Y/N) If the question is marked as required 
     * @return  boolean Success/failure
     */
    function UpdatePost($id, $sort_order, $formID, $title, $itype, $required)
	{

		if ($itype == 'HiddenField') {
			$required = 'N';
		}
		
		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
        $page = $model->GetPost($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_FOUND'), _t('FORMS_NAME'));
        }

        $sql = '
            UPDATE [[form_questions]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[formid] = {formID}, 
				[itype] = {itype}, 
				[required] = {required},
				[updated] = {now}
			WHERE [id] = {id}';

        $params               		= array();
        $params['id']         		= (int)$id;
        $params['sort_order']       = (int)$sort_order;
        $params['title'] 			= strip_tags($title);
		$params['formID'] 			= (int)$formID;
        $params['itype'] 			= $itype;
        $params['required'] 		= $required;
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_UPDATED'), _t('FORMS_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateFormPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_POST_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_POST_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a question
     *
     * @access  public
     * @param   int     $id     The ID of the question to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @return  bool    Success/failure
     */
    function DeletePost($id = null, $massive = false)
    {
		if (is_null($id)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_DELETED'), _t('FORMS_NAME'));
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteFormPost', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
			$rids = $model->GetAllAnswersOfPost($id);
			if (Jaws_Error::IsError($rids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_DELETED'), _t('FORMS_NAME'));
			}

			foreach ($rids as $rid) {
				if (!$this->DeleteAnswer($rid['id'], true)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_DELETED'), _t('FORMS_NAME'));
				}
			}
	        
			$sql = 'DELETE FROM [[form_questions]] WHERE [id] = {id}';
	        $result = $GLOBALS['db']->query($sql, array('id' => $id));
	        if (Jaws_Error::IsError($result)) {
	            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
	            return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_DELETED'), _t('FORMS_NAME'));
	        }
		}

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_POST_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Create default answers to questions.
     *
     * @category  feature
     * @param   integer  $sort_order 	The priority order
     * @param   integer  $LinkID      		ID of the question this answer belongs to.
     * @param   integer  $formID      		ID of the form this answer belongs to.
     * @param   string  $title      		The title of the answer.
     * @param   integer $OwnerID  		The poster's user ID
     * @param   string 	$checksum 	Unique ID
     * @access  public
     * @return  mixed 	ID of new answer on success, Jaws_Error on failure
     */
    function AddAnswer($sort_order = 0, $LinkID, $formID, $title, $OwnerID = null, $checksum = '')
    {
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;

		$sql = "
            INSERT INTO [[form_answers]]
                ([sort_order], [linkid], [formid], [title], 
				[ownerid], [created], [updated], [checksum])
            VALUES
                ({sort_order}, {LinkID}, {formID}, {title}, 
				{OwnerID}, {now}, {now}, {checksum})";

        $params               		= array();
        $params['sort_order']       = (int)$sort_order;
        $params['title'] 			= strip_tags($title);
		$params['LinkID'] 			= (int)$LinkID;
		$params['formID'] 			= (int)$formID;
		$params['OwnerID']         	= $OwnerID;
		$params['checksum']         = $checksum;
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_ANSWER_NOT_ADDED'), _t('FORMS_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('form_answers', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[form_answers]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddFormPostAnswer', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_POST_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates an answer.
     *
     * @access  public
     * @param   int     $id             The ID of the post to update.
     * @param   integer  $sort_order 	The priority order
     * @param   integer  $LinkID 	ID of the question this answer belongs to.
     * @param   string  $title      		The title of the post.
     * @return  boolean Success/failure
     */
    function UpdateAnswer($id, $sort_order, $LinkID, $title)
	{
		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
        $page = $model->GetAnswer($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FORMS_ERROR_POST_NOT_FOUND'), _t('FORMS_NAME'));
        }

        $sql = '
            UPDATE [[form_answers]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[linkid] = {LinkID}, 
				[updated] = {now}
			WHERE [id] = {id}';

        $params               		= array();
        $params['id']         		= (int)$id;
        $params['sort_order']       = (int)$sort_order;
        $params['title'] 			= strip_tags($title);
		$params['LinkID'] 			= (int)$LinkID;
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_ANSWER_NOT_UPDATED'), _t('FORMS_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateFormPostAnswer', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ANSWER_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ANSWER_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }

    /**
     * Deletes an answer
     *
     * @access  public
     * @param   int     $id     The ID of the answer to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @return  bool    Success/failure
     */
    function DeleteAnswer($id, $massive = false)
    {
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteFormPostAnswer', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$sql = 'DELETE FROM [[form_answers]] WHERE [id] = {id}';
		$result = $GLOBALS['db']->query($sql, array('id' => $id));
		if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_ANSWER_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('FORMS_ERROR_ANSWER_NOT_DELETED'), _t('FORMS_NAME'));
		}

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ANSWER_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Re-sorts posts
     *
     * @access  public
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @return  bool    Success/failure
     */
    function SortItem($pids, $newsorts, $table = 'form_questions')
    {
		//$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		if ($table != 'form_questions' && $table != 'form_answers') {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
			return false;
		}
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				//$page = $model->GetPost($pid);
		        //if (Jaws_Error::isError($page)) {
		        //    $GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
				//	return false;
		        //} else {
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = '
					UPDATE [['.$table.']] SET
						[sort_order] = {new_sort} 
					WHERE [id] = {pid}';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					//$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
					$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_POST_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

    /**
     * Search for forms that matches a status and/or a keyword
     * in the title or content
     *
     * @access  public
     * @param   string  $status  Status of form(s) we want to display
     * @param   string  $search  Keyword (title/description) of forms we want to look for
     * @param   int     $offSet  Data limit
     * @return  array   Array of matches
     */
    function SearchForms($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();


        $sql = '
            SELECT [id], [sort_order], [title], [sm_description], [description], 
			   [clause], [image], [recipient], [parent], [custom_action], 
			   [fast_url], [active], [ownerid], [created], [updated], [submit_content], [checksum]
            FROM [[forms]]
			WHERE ([title] <> ""';

        if (trim($status) != '') {
            $sql .= ' AND [active] = {status}';
			$params['status'] = $status;
        }
        $sql .= ')';
        
		if (!is_null($OwnerID)) {
			$sql .= ' AND [ownerid] = {OwnerID}';
			$params['OwnerID'] = $OwnerID;
		}
		
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([title] LIKE {textLike_".$i."} OR [custom_action] LIKE {textLike_".$i."} OR [recipient] LIKE {textLike_".$i."} OR [clause] LIKE {textLike_".$i."} OR [description] LIKE {textLike_".$i."} OR [sm_description] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }


        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('FORMS_ERROR_FORMS_NOT_RETRIEVED'), _t('FORMS_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'text', 'text', 'integer', 'timestamp', 'timestamp', 'text', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FORMS_ERROR_FORMS_NOT_RETRIEVED'), _t('FORMS_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }

    /**
     * Saves Forms settings into registry.
     *
     * @access  public
     * @param   string  $default_recipient   Default form recipient email(s)
     * @param   string  $site_address 	Physical address
     * @param   string  $site_office 	Office telephone
     * @param   string  $site_tollfree 	Toll-free telephone
     * @param   string  $site_cell 	Cell phone
     * @param   string  $site_fax 	Fax number
     * @return  array   Response
     */
	function SaveSettings($default_recipient = '', $site_address = '', $site_office = '', $site_tollfree = '', $site_cell = '', $site_fax = '')

    {
        /*
		if (preg_match("#^/(gadgets|plugins\/parse_text)/(.*?)/(.*?)#i", $key, $matches)) {
            $GLOBALS['app']->Registry->LoadFile($matches[2]);
            $GLOBALS['app']->Registry->Set($key, $value);
            $GLOBALS['app']->Registry->Commit($matches[2]);
		}
		*/
		$GLOBALS['app']->Registry->LoadFile('Forms');
        $GLOBALS['app']->Registry->Set('/gadgets/Forms/default_recipient', $default_recipient);
        $GLOBALS['app']->Registry->Set('/gadgets/Forms/site_address', $site_address);
        $GLOBALS['app']->Registry->Set('/gadgets/Forms/site_office', $site_office);
        $GLOBALS['app']->Registry->Set('/gadgets/Forms/site_tollfree', $site_tollfree);
        $GLOBALS['app']->Registry->Set('/gadgets/Forms/site_cell', $site_cell);
        $GLOBALS['app']->Registry->Set('/gadgets/Forms/site_fax', $site_fax);
		$GLOBALS['app']->Registry->Commit('Forms');
		if ($GLOBALS['app']->Registry->Get('/gadgets/Forms/default_recipient') == $default_recipient && $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address') == $site_address && $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_office') == $site_office && $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_tollfree') == $site_tollfree && $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_cell') == $site_cell && $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_fax') == $site_fax) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_KEY_SAVED'), RESPONSE_NOTICE);
			return true;
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_KEY_NOT_SAVED'), RESPONSE_ERROR);
		return false;
    }
	
    /**
     * Inserts first (default) form to menu
     *
     * @access  public
     * @param   string  $gadget   Get gadget name (Forms) from onAfterEnablingGadget shouter call
     * @return  array   Response
     */
    function InsertDefaultFormToMenu($gadget)
    {
		if ($gadget == 'Forms') {
			// Insert "Contact Us" Form into Menu
			$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
			$visible = 1;
			$page = $model->GetForm(1);
			if (!Jaws_Error::IsError($page)) {
				if (!isset($GLOBALS['app']->Map)) {
					require_once JAWS_PATH . 'include/Jaws/URLMapping.php';
					$GLOBALS['app']->Map = new Jaws_URLMapping();
				}
				$url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $page['fast_url']));
							
				// get parent menus			
				$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
				$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
				if (Jaws_Error::IsError($oid)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				} else {
					if (empty($oid['id'])) {
						// Get highest rank of current menu items
						$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = 1 ORDER BY [rank] DESC";
						$rank = $GLOBALS['db']->queryOne($sql);
						if (Jaws_Error::IsError($rank)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
						$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
						if (!$menuAdmin->InsertMenu(0, 1, 'Forms', 'Contact Us', $url, 0, (int)$rank+1, $visible, true)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
					} else {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
				}
				$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ADDED_TO_MENU'), RESPONSE_NOTICE);
				return true;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_NOT_ADDED_TO_MENU'), RESPONSE_ERROR);
			return false;
		}
		return true;
    }
    
	/**
     * Inserts checksums for default (insert.xml) content
     *
     * @access  public
     * @param   string  $gadget   Get gadget name from onAfterEnablingGadget shouter call
     * @return  array   Response
     */
    function InsertDefaultChecksums($gadget)
    {
		if ($gadget == 'Forms') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
			$parents = $model->GetForms();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[forms]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddForm', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
				$posts = $model->GetAllPostsOfForm($parent['id']);
				if (Jaws_Error::IsError($posts)) {
					return false;
				}
				foreach ($posts as $post) {
					if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post['id'];
						$params['checksum'] 	= $post['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[form_questions]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddFormPost', $post['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							return $res;
						}
					}
					$posts1 = $model->GetAllAnswersOfPost($post['id']);
					if (Jaws_Error::IsError($posts1)) {
						return false;
					}
					foreach ($posts1 as $post1) {
						if (empty($post1['checksum']) || is_null($post1['checksum']) || strpos($post1['checksum'], ':') === false) {
							$params               	= array();
							$params['id'] 			= $post1['id'];
							$params['checksum'] 	= $post1['id'].':'.$config_key;
							
							$sql = '
								UPDATE [[form_answers]] SET
									[checksum] = {checksum}
								WHERE [id] = {id}';

							$result = $GLOBALS['db']->query($sql, $params);
							if (Jaws_Error::IsError($result)) {
								$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
								return false;
							}

							// Let everyone know it has been added
							$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
							$res = $GLOBALS['app']->Shouter->Shout('onAddFormPostAnswer', $post1['id']);
							if (Jaws_Error::IsError($res) || !$res) {
								return $res;
							}
						}
					}
				}
			}
		}
		return true;
    }

}