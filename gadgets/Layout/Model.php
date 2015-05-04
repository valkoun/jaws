<?php
/**
 * Model class (has the heavy queries) to manage layout
 *
 * @category   Layout
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class LayoutModel extends Jaws_Model
{
    /**
     * Get the layout sections
     *
     * @access  public
     * @return  array   Returns an array of layout mode sections and Jaws_Error on error
     */
    function GetLayoutSections()
    {
        $sql = 'SELECT [section]
                FROM [[layout]]
                ORDER BY [section]';

        $res = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error($res->getMessage(), 'SQL');
        }

        return $res;
    }

    /**
     * Get the layout items
     *
     * @access  public
     * @return  array   Returns an array with the layout items and false on error
     */
    function GetLayoutItems($gadget = null, $action = null, $linkid = null, $index = null, $hidden = false)
    {
        $sql = '
            SELECT
                [id],
                [gadget],
                [gadget_action],
                [display_when],
                [section]
            FROM [[layout]] 
        ';
		$params['always1'] = '%*%';
		$sql .= ' WHERE (([display_when] LIKE {always1})'; 
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
				if (!is_null($action)) {
					$params['gadget1'] = '%{GADGET:'.$gadget.'|ACTION:'.$action.'}%';
					if ($hidden === false) {
						$params['notgadget1'] = '%{HIDEGADGET:'.$gadget.'|ACTION:'.$action.'}%';
					}
					if (!is_null($linkid)) {
						$params['gadget1'] = '%{GADGET:'.$gadget.'|ACTION:'.$action.'('.$linkid.')}%';
						if ($hidden === false) {
							$params['notgadget1'] = '%{HIDEGADGET:'.$gadget.'|ACTION:'.$action.'('.$linkid.')}%';
						}
					}
				}
				$sql .= ' OR ([display_when] = {dw} OR [display_when] LIKE {dw1} OR [display_when] LIKE {dw2})'; 
				$sql .= ' OR ([display_when] LIKE {gadget1}))'; 
				if ($hidden === false) {
					$sql .= ' AND NOT ([display_when] LIKE {notgadget1}'; 
				}
			}
		} else {
			$params['notgadget3'] = '%{GADGET:%';
			$params['notgadget4'] = '%{TEMPGADGET:%';
			$sql .= ' OR (NOT [display_when] LIKE {always1} AND NOT [display_when] LIKE {notgadget3} AND NOT [display_when] LIKE {notgadget4})'; 
		}
		$sql .= ')
			ORDER BY [section], [layout_position]
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
}
