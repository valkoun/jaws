<?php
/**
 * Layout Core Gadget
 *
 * @category   GadgetModel
 * @package    Layout
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
require_once JAWS_PATH . 'gadgets/Layout/Model.php';

class LayoutAdminModel extends LayoutModel
{
    /**
     * Installs the gadget
     *
     * @access       public
     * @return       true on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/schema/insert.xml')) {
			$result = $this->installSchema('insert.xml', '', 'schema.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Layout/pluggable', 'false');
		$GLOBALS['app']->Registry->commit('core');

		// Listeners and Shouters
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$GLOBALS['app']->Shouter->NewShouter('Core', 'onAddLayoutElement');          		// trigger an action when we add content
		$GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateLayoutElement');          	// trigger an action when we update content
		$GLOBALS['app']->Shouter->NewShouter('Core', 'onHideLayoutElement');          		// trigger an action when we remove content
		$GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteLayoutElement');          	// trigger an action when we remove content
        
		return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UpdateGadget($old, $new)
    {
		// Listeners and Shouters
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        
		if (version_compare($old, '0.3.0', '<')) {
            $result = $this->installSchema('0.3.0.xml', '', "$old.xml");
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
        }

        if (version_compare($old, '0.3.1', '<')) {
            // ACL keys
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Layout/ManageThemes',  'false');
        }

        if (version_compare($old, '0.4.0', '<')) {
            $result = $this->installSchema('schema.xml', '', "0.3.0.xml");
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
        }
        
		if (version_compare($old, '0.4.1', '<')) {
			$GLOBALS['app']->Registry->NewKey('/config/layout', 'layout.html');
			$GLOBALS['app']->Registry->commit('core');
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onAddLayoutElement');          		// trigger an action when we add content
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateLayoutElement');          	// trigger an action when we update content
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onHideLayoutElement');          		// trigger an action when we remove content
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteLayoutElement');          	// trigger an action when we remove content
        }

        return true;
    }

    /**
     * Add a new element to the layout
     *
     * @access  public
     * @param   string  $section     The section where it should appear
     * @param   string  $gadget      Gadget name
     * @param   string  $action      The default action
     * @param   string  $displayWhen When should gadget be displayed
     * @param   string  $pos         (Optional) Element position
     * @return  boolean Returns true if gadget was added without problems, if not, returns false
     */
    function NewElement($section, $gadget, $action, $pos = '', $displayWhen = '*')
    {
        if (empty($pos)) {
            $sql = '
            SELECT MAX([layout_position])
            FROM [[layout]]
            WHERE [section] = {section}';

            $pos = $GLOBALS['db']->queryOne($sql, array('section' => $section));
            if (Jaws_Error::IsError($pos)) {
                return false;
            }
            $pos += 1;
        }

        $params                = array();
        $params['gadget']      = $gadget;
        $params['action']      = $action;
        $params['displayWhen'] = $displayWhen;
        $params['section']     = $section;
        $params['pos']         = $pos;
        $sql = '
            INSERT INTO [[layout]]
                ([section], [gadget], [gadget_action], [display_when], [layout_position])
            VALUES
                ({section}, {gadget}, {action}, {displayWhen}, {pos})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $data = '';
        $sql = '
            SELECT [id]
            FROM [[layout]]
            WHERE 
                [gadget] = {gadget} AND
                [gadget_action] = {action} AND
                [section] = {section} AND
                [layout_position] = {pos}';

        $id = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($id)) {
            return false;
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddLayoutElement', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return false;
		}
        return $id;
    }

    /**
     * Update the properties of an element
     *
     * @access  public
     * @param   int     $id          Element ID
     * @param   string  $gadget      Gadget name
     * @param   string  $action      Default action
     * @param   string  $displayWhen When should gadget be displayed
     * @return  boolean Returns true if element was updated without problems, if not, returns false
     */
    function UpdateElement($id, $gadget, $action, $displayWhen)
    {
        $params                = array();
        $params['id']          = $id;
        $params['gadget']      = $gadget;
        $params['action']      = $action;
        $params['displayWhen'] = $displayWhen;
        $sql = '
            UPDATE [[layout]] SET
                [gadget] = {gadget},
                [gadget_action] = {action},
                [display_when] = {displayWhen}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateLayoutElement', array('id' => $id));
		if (Jaws_Error::IsError($res) || !$res) {
			return false;
		}
        return true;
    }

    /**
     * Update the gadget's action name
     *
     * @access  public
     * @param   string  $gadget      Gadget name
     * @param   string  $old_action  Old action
     * @param   string  $new_action  New action
     * @return  boolean Returns true if updated without problems, if not, returns false
     */
    function ChangeGadgetActionName($gadget, $old_action, $new_action)
    {
        $params = array();
        $params['gadget'] = $gadget;
        $params['action'] = $old_action.'%';

        $sql = '
            SELECT [id], [gadget_action]
            FROM [[layout]]
            WHERE
                [gadget] = {gadget}
              AND
                [gadget_action] LIKE {action}';

        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        foreach ($result as $row) {
            $params['id']     = $row['id'];
            $params['action'] = substr_replace($row['gadget_action'], $new_action, 0, strlen($old_action));
            $sql = '
                UPDATE [[layout]] SET
                    [gadget_action] = {action}
                WHERE [id] = {id}';
			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onUpdateLayoutElement', array('id' => $row['id']));
			if (Jaws_Error::IsError($res) || !$res) {
				return false;
			}
        }

        return true;
    }

    /**
     * Delete an element
     *
     * @access  public
     * @param   int     $id  Element ID
     * @return  boolean Returns true if element was removed, if not it returns false
     */
    function DeleteElement($id)
    {
        $element = $this->GetElement($id);

        if ($element === false) {
            return false;
        }

		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteLayoutElement', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return false;
		}
       
		$sql = 'DELETE FROM [[layout]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $res = $this->UpdateSectionPositions($element['section']);
        if ($res === false) {
            return false;
        }

        return true;
    }

    /**
     * Hide an element
     *
     * @access  public
     * @param   int     $id  Element ID
     * @param   int     $pageId  Page ID
     * @return  boolean Returns true if element was removed, if not it returns false
     */
    function HideElement($id, $page_gadget, $page_action, $page_linkid = null)
    {
        $element = $this->GetElement($id);

        if ($element === false) {
            return false;
        }
		
		$new_dw = '{HIDEGADGET:'.$page_gadget.'|ACTION:'.$page_action.(!is_null($page_linkid) ? '('.$page_linkid.')' : '').'}';
		$display_when = $element['display_when'];
		if (!in_array(strtolower($new_dw), explode(',',strtolower($display_when)))) {
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onHideLayoutElement', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return false;
			}
		   
			$display_when .= ','.$new_dw;
		
			$sql = 'UPDATE [[layout]] SET [display_when] = {display_when} WHERE [id] = {id}';
			$result = $GLOBALS['db']->query($sql, array('id' => $id, 'display_when' => $display_when));
			if (Jaws_Error::IsError($result)) {
				return false;
			}

		}

        return true;
    }

     /**
     * Update the positions of a section
     *
     *  - If the position of an element doesn't match the sequence, a
     *    temp value will be used instead with the current and next values
     *  - If the position of an element is repeated, a temp value
     *    will be used with that element and the next elements
     *
     * @access  public
     * @param   int     $section       Section to move it
     * @param   int     $highpriority  Item with high priority
     * @return  boolean Success/Failure
     */
    function UpdateSectionPositions($section)
    {
        $sql = '
            SELECT
                [id], [layout_position]
            FROM [[layout]]
            WHERE [section] = {section}
            ORDER BY [layout_position]';
        $result = $GLOBALS['db']->queryAll($sql, array('section' => $section));
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $elementsArray = array();
        $posCounter    = 1;
        $change        = false;
        $posUsed       = array();
        foreach ($result as $row) {
            $res                 = array();
            $res['id']           = $row['id'];
            $res['position']     = $row['layout_position'];
            if ($row['layout_position'] != $posCounter) {
                $change = true;
            }

            $res['new_position'] = ($change === true ? $posCounter : false);
            $posUsed[] = $posCounter;
            $elementsArray[$row['id']] = $res;
            $posCounter++;
        }

        foreach ($elementsArray as $element) {
            if ($element['new_position'] == false) {
                continue;
            }

            $params = array();
            $params['position'] = $element['new_position'];
            $params['section']  = $section;
            $params['id']       = $element['id'];

            $sql = 'UPDATE [[layout]] SET
                     [layout_position] = {position}
                    WHERE
                     [id] = {id}';
            $result = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($result)) {
                return false;
            }
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onUpdateLayoutElement', array('id' => $element['id']));
			if (Jaws_Error::IsError($res) || !$res) {
				return false;
			}
        }
        return true;
    }

 

    /**
     * Move an element to a new section
     *
     * @access  public
     * @param   int     $id            Element ID
     * @param   int     $section       Section to move it
     * @param   int     $pos           Position that will be used, all other positions will be placed under this
     * @param   array   $sortedItems   An array with the sorted items of $section. WARNING: keys have the item_ prefix
     * @return  boolean Success/Failure
     */
    function MoveElementToSection(
		$id, $section, $pos, $sortedItems, $page_gadget = null, $page_action = null, $page_linkid = null
	) {		
		$params             = array();
        $params['id']       = $id;
        $params['section']  = $section;
        $params['pos']      = $pos;

        $element = $this->GetElement($id);
        if ($element === false) {
            return false;
        }

        $params['dw']      = $element['display_when'];
       
		// If the element we're moving is not restricted to a page 
		// (i.e. always displayed or in whole gadget[s] scope), then hide it on 
		// current page and add new element to only show on current page
		if (
			substr($element['display_when'], 0, 8) != '{GADGET:' && 
			!is_null($page_gadget) && !is_null($page_action)
		) {
			$params['dw'] = $element['display_when'].',';
			$params['dw'] .= '{HIDEGADGET:'.$page_gadget.'|ACTION:'.$page_action.(!is_null($page_linkid) ? '('.$page_linkid.')' : '').'}';
			$params['section'] = $element['section'];
			$params['pos'] = $element['layout_position'];
			
			// Duplicate it to new section
			$display_when = '{GADGET:'.$page_gadget.'|ACTION:'.$page_action.(!is_null($page_linkid) ? '('.$page_linkid.')' : '').'}';
			$res = $this->NewElement($section, $element['gadget'], $element['action'], $pos, $displayWhen);
			if (!Jaws_Error::IsError($res)) {
				$element = $this->GetElement($res);
				if ($element === false) {
					return false;
				}
			}
		}
	    
		$gadgets = $this->GetGadgetsInSection($section, $page_gadget, $page_action, $page_linkid, null, false, true);
        $count = count($gadgets);

		//if (is_null($page_gadget) && is_null($page_action) && is_null($page_linkid)) {
			$sql = 'UPDATE [[layout]] SET
					 [layout_position] = {pos},
					 [display_when] = {dw},
					 [section] = {section}
					 WHERE
						[id] = {id}';
			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return false;
			}
		//}
		
		if ($count > 0) {
            $sortedItems = array_keys($sortedItems);

            foreach ($gadgets as $gadget) {
                $newPos = array_search('item_'.$gadget['id'], $sortedItems);
				if ($newPos === false) {
                    continue;
                }

                $newPos = $newPos+1;
                if (is_null($page_gadget) && is_null($page_action) && is_null($page_linkid)) {
					if ($newPos == $gadget['layout_position']) {
						continue;
					}

					$params        = array();
					$params['pos'] = $newPos;
					$params['id']  = $gadget['id'];


					$sql = 'UPDATE [[layout]] SET
							 [layout_position] = {pos}
							WHERE
							 [id] = {id}';
					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						return false;
					}
				}
				
				// Let everyone know it has been added
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onUpdateLayoutElement', 
					array(
						'id' => $gadget['id'], 
						'section' => $section, 
						'new_pos' => $newPos, 
						'page_gadget' => $page_gadget, 
						'page_action' => $page_action, 
						'page_linkid' => $page_linkid
					)
				);
				if (Jaws_Error::IsError($res) || !$res) {
					return false;
				}
            }
        } else {
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onUpdateLayoutElement', 
				array(
					'id' => $id, 
					'section' => $section, 
					'new_pos' => $pos, 
					'page_gadget' => $page_gadget, 
					'page_action' => $page_action, 
					'page_linkid' => $page_linkid
				)
			);
			if (Jaws_Error::IsError($res) || !$res) {
				return false;
			}
		}

        return true;
    }

    /**
     * Move an element to another place
     *
     * @access  public
     * @param   int     $elementId Element ID
     * @param   string  $section   Section where it is(header, left, main, right, footer)
     * @param   string  $direction Where to move it
     */
    function MoveElement($elementId, $section, $direction)
    {
        ///FIXME:  Move up/down/left/right properly
        $sql = '
            SELECT
                [id], [layout_position]
            FROM [[layout]]
            WHERE [section] = {section}
            ORDER BY [layout_position]';
        ///FIXME check for errors
        $result = $GLOBALS['db']->queryAll($sql, array('section' => $section));

        $menu_array = array();
        foreach ($result as $row) {
            $res['id']              = $row['id'];
            $res['position']        = $row['layout_position'];
            $menu_array[$row['id']] = $res;
        }

        reset($menu_array);
        $found = false;
        while (!$found) {
            $v = current($menu_array);
            if ($v['id'] == $elementId) {
                $found = true;
                $position = $v['layout_position'];
                $id = $v['id'];
            } else {
                next($menu_array);
            }
        }

        $run_queries = false;
        if ($direction == 'up') {
            if (prev($menu_array)) {
                $v           = current($menu_array);
                $m_position  = $v['layout_position'];
                $m_id        = $v['id'];
                $run_queries = true;
            }
        } elseif ($direction == 'down') {
            if (next($menu_array))   {
                $v           = current($menu_array);
                $m_position  = $v['layout_position'];
                $m_id        = $v['id'];
                $run_queries = true;
            }
        }

        if ($run_queries) {
            $sql = '
                UPDATE [[layout]] SET
                    [layout_position] = {position}
                WHERE [id] = {id}';
            $GLOBALS['db']->query($sql, array('position' => $m_position, 'id' => $id));

            $GLOBALS['db']->query($sql, array('position' => $position, 'id' => $m_id));
        }
    }

    /**
     * Move a section to other place
     *
     * @access  public
     * @param   string  $from Which section to move
     * @param   string  $to   The destination
     * @return  boolean True if the section was moved without problems, if not it returns false
     */
    function MoveSection($from, $to)
    {
        $sql = 'SELECT MAX([layout_position]) FROM [[layout]] WHERE [section] = {to}';
        $maxpos = $GLOBALS['db']->queryOne($sql, array('to' => $to));
        if (Jaws_Error::IsError($maxpos) || empty($maxpos)) {
            $maxpos = '0';
        }

        $params           = array();
        $params['to']     = $to;
        $params['maxpos'] = $maxpos;
        $params['from']   = $from;
        $sql = '
            UPDATE [[layout]] SET
                [section] = {to},
                [layout_position] = [layout_position] + {maxpos}
            WHERE [section] = {from}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return true;
    }


    /**
     * Get the properties of an element
     *
     * @access  public
     * @param   int     $id Element ID
     * @return  array   Returns an array with the properties of an element and false on error
     */
    function GetElement($id)
    {
        $sql = '
            SELECT
               [id], [gadget], [gadget_action], [display_when], [layout_position], [section]
            FROM [[layout]]
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->queryRow($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Get the gadgets that are in a section
     *
     * @access  public
     * @param   int     $id Section to search
     * @return  array   Returns an array of gadgets that are in a section and false on error
     */
    function GetGadgetsInSection($id, $gadget = null, $action = null, $linkid = null, $index = null, $hidden = false, $temp = false)
    {
        $sql = '
            SELECT
                [id], [section], [gadget], [gadget_action], [display_when], [layout_position]
            FROM [[layout]]
			WHERE ([section] = {section}';
		$params = array();
		$params['section'] = $id;
		$params['always1'] = '%*%';
		$sql .= ' AND (([display_when] LIKE {always1})'; 
		// Filter
		if (!is_null($gadget) || !is_null($index)) {
			if (!is_null($index) && $index === true) {
				$params['index'] = 'index';
				$params['index1'] = '%index,%';
				$params['index2'] = '%,index%';
				$sql .= ' OR ([display_when] = {index} OR [display_when] LIKE {index1} OR [display_when] LIKE {index2})'; 
			}
			if (!is_null($gadget)) {
				$params['dw'] = $gadget;
				$params['dw1'] = '%'.$gadget.',%';
				$params['dw2'] = '%,'.$gadget.'%';
				$params['gadget1'] = '%{GADGET:'.$gadget.'}%';
				if ($hidden === false) {
					$params['notgadget1'] = '%{HIDEGADGET:'.$gadget.'}%';
				}
				if ($temp === true) {
					$params['tempgadget1'] = '%{TEMPGADGET:'.$gadget.'}%';
				}
				if (!is_null($action)) {
					$params['gadget1'] = '%{GADGET:'.$gadget.'|ACTION:'.$action.'}%';
					if ($hidden === false) {
						$params['notgadget1'] = '%{HIDEGADGET:'.$gadget.'|ACTION:'.$action.'}%';
					}
					if ($temp === true) {
						$params['tempgadget1'] = '%{TEMPGADGET:'.$gadget.'|ACTION:'.$action.'}%';
					}
					if (!is_null($linkid)) {
						$params['gadget1'] = '%{GADGET:'.$gadget.'|ACTION:'.$action.'('.$linkid.')}%';
						if ($hidden === false) {
							$params['notgadget1'] = '%{HIDEGADGET:'.$gadget.'|ACTION:'.$action.'('.$linkid.')}%';
						}
						if ($temp === true) {
							$params['tempgadget1'] = '%{TEMPGADGET:'.$gadget.'|ACTION:'.$action.'('.$linkid.')}%';
						}
					}
				}	
				$sql .= ' OR ([display_when] = {dw} OR [display_when] LIKE {dw1} OR [display_when] LIKE {dw2})'; 
				$sql .= ' OR ([display_when] LIKE {gadget1})'; 
				if ($temp === true) {
					$sql .= ' OR ([display_when] LIKE {tempgadget1})'; 
				}
			}
		} else {
			$params['notgadget3'] = '%{GADGET:%';
			$params['notgadget4'] = '%{TEMPGADGET:%';
			$sql .= ' OR (NOT [display_when] LIKE {always1} AND NOT [display_when] LIKE {notgadget3} AND NOT [display_when] LIKE {notgadget4})'; 
		}
		$sql .= ')';
		if ($hidden === false && isset($params['notgadget1'])) {
			$sql .= ' AND NOT ([display_when] LIKE {notgadget1})'; 
		}
		$sql .= ')
			ORDER BY [layout_position]
		';
				
        /*
		$show_sql = $sql;
		foreach ($params as $k => $v) {
			$show_sql = str_replace('{'.$k.'}', $v, $show_sql);
		}
		var_dump($show_sql);
		exit;
		*/
		
        $result = $GLOBALS['db']->queryAll($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Delete all the elements of a gadget
     *
     * @access  public
     * @param   string  $gadget Gadget's name
     * @return  boolean Returns true if element was removed, if not it returns false
     */
    function DeleteGadgetElements($gadget)
    {
        $sql = '
            SELECT
               [id]
            FROM [[layout]]
            WHERE [gadget] = {gadget}';

        $result = $GLOBALS['db']->queryRow($sql, array('gadget' => $gadget));
        if (Jaws_Error::IsError($result)) {
            return false;
        }
        
		foreach ($result as $res) {
			$delete = $this->DeleteElement($res['id']);
			if (Jaws_Error::IsError($delete)) {
				return false;
			}
		}

        return true;
    }

    /**
     * Change when to display a gadget
     * 
     * @access  public
     * @param   int     $item   Item ID
     * @param   string  $dw     Display in these gadgets
     * @return  array   Response
     */
    function ChangeDisplayWhen($item, $dw) 
    {
        $params                = array();
        $params['id']          = $item;
        $params['displayWhen'] = $dw;
        $sql = '
            UPDATE [[layout]] SET
                [display_when] = {displayWhen}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateLayoutElement', array('id' => $item));
		if (Jaws_Error::IsError($res) || !$res) {
			return false;
		}
        return true;
    }

    /**
     * Edit layout's element action
     * 
     * @access  public
     * @param   int     $item   Item ID
     * @params  string  $action
     * @return  array   Response
     */
    function EditElementAction($item, $action) 
    {
        $params           = array();
        $params['id']     = $item;
        $params['action'] = $action;
        $sql = '
            UPDATE [[layout]] SET
                [gadget_action] = {action}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateLayoutElement', array('id' => $item));
		if (Jaws_Error::IsError($res) || !$res) {
			return false;
		}
        return true;
    }

    /**
     * Get actions of a given gadget
     * 
     * @access  public
     * @param   string  $gadget 
     * @return  array   Array with the actions of the given gadget
     */
    function GetGadgetActions($g)
    { 
        $res = array();
        if (file_exists(JAWS_PATH . 'gadgets/'. $g. '/'. 'LayoutHTML.php')) {
            $reForceRead = false;

            $layoutGadget = $GLOBALS['app']->loadGadget($g, 'LayoutHTML');
            if (!Jaws_Error::IsError($layoutGadget)) {
                if (method_exists($layoutGadget, 'LoadLayoutActions')) {
                    $info = $GLOBALS['app']->LoadGadget($g, 'Info');
                    $actions = $layoutGadget->LoadLayoutActions();
                } else {
                    $reForceRead = true;
                }
            } else {
                $reForceRead = true;
            }

            $ractions = $GLOBALS['app']->GetGadgetActions($g);
            if (isset($ractions['LayoutAction'])) {
                if (!$reForceRead) {
                    $actions = $actions + $ractions['LayoutAction'];
                } else {
                    $actions = $ractions['LayoutAction'];
                }
            }
            if (isset($actions) && count($actions) > 0) {
                foreach ($actions as $actionName => $actionProperties) {
                    if ($actionProperties['mode'] == 'LayoutAction') {
                        $res[] = array('action' => $actionName, 
                                       'name'   => $actionProperties['name'],
                                       'desc'   => $actionProperties['desc']);
                    }
                }
            }
        }
        return $res;
    }	
	
	/**
     * Save data to custom.css
     * 
     * @access  public
     * @param   string  $gadget 
     * @return  boolean true/false on error
     */
    function SaveCSS($data)
    { 
        $request =& Jaws_Request::getInstance();
        //$data = $request->getRaw('css_data', 'post');
		$data = (isset($data) && !is_null($data) ? urldecode($data) : '');

		// create css directory and file
		$dir = JAWS_DATA . 'files/css';
		if (file_exists($dir)) {
			if (!file_put_contents(JAWS_DATA . 'files/css/custom.css', $data)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_CANT_WRITE_CSS'), RESPONSE_ERROR);
				return false;
				//Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
			}
		} else {
			if (!Jaws_Utils::mkdir($dir)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_CANT_WRITE_CSS'), RESPONSE_ERROR);
				return false;
				//Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
			} else {
				$result = file_put_contents(JAWS_DATA . 'files/css/custom.css', $data);
				if (!$result) {
					$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_CANT_WRITE_CSS'), RESPONSE_ERROR);
					return false;
				}
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_CSS_SAVED'), RESPONSE_NOTICE);
		//Jaws_Header::Location(BASE_SCRIPT . '?gadget=Layout&action=Admin');
		return true;
	}
}
