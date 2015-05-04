<?php
/**
 * Menu Gadget
 *
 * @category   GadgetModel
 * @package    Menu
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class MenuModel extends Jaws_Model
{
    /**
     * Returns a menu
     *
     * @access  public
     * @return  array  Array with all the available menus and Jaws_Error on error
     */
    function GetMenu($mid)
    {
        $sql = '
            SELECT
                [id], [pid], [gid], [menu_type], [title], [url], [url_target], [rank], [visible]
            FROM [[menus]]
            WHERE
                [id] = {mid}';

        $params        = array();
        $params['mid'] = $mid;
        
        $result = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MENU_ERROR_GET_MENUS'), _t('MENU_NAME'));
        }

        return $result;
    }

    /**
     * Returns a list of  menus at a request level
     *
     * @access  public
     * @return  array  Array with all the available menus and Jaws_Error on error
     */
    function GetLevelsMenus($pid, $gid = null, $onlyVisible = false)
    {
        $sql = 'SELECT [id],'. (empty($gid)? ' [gid],' : ''). ' [title], [url], [url_target]'.
               ($onlyVisible? ' ' : ', [visible] ').
               'FROM [[menus]] ';
        $sql.= 'WHERE ' . (empty($gid)? '' : '[gid] = {gid} AND ') . '[pid] = {pid}'.
               ($onlyVisible?' AND [visible] = 1 ':' ');
        $sql.= 'ORDER BY [rank] ASC';

        $params        = array();
        $params['gid'] = $gid;
        $params['pid'] = $pid;

        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MENU_ERROR_GET_MENUS'), _t('MENU_NAME'));
        }

        return $result;
    }

    /**
     * Returns a list with all the menus
     *
     * @access  public
     * @return  array  Array with all the available menus and Jaws_Error on error
     */
    function GetGroups($gid = null)
    {
        $sql = '
            SELECT
                [id], [title], [title_view], [view_type], [rank], [visible]
            FROM [[menus_groups]] ';
        $sql.= (empty($gid)? '' : 'WHERE [id] = {gid} ') . 'ORDER BY [rank] DESC';

        $params = array();
        $params['gid'] = $gid;

        if (!empty($gid)) {
            $result = $GLOBALS['db']->queryRow($sql, $params);
        } else {
            $result = $GLOBALS['db']->queryAll($sql, $params);
        }
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('MENU_ERROR_GET_GROUPS'), _t('MENU_NAME'));
        }

        return $result;
    }

    /**
     * Returns a list with all the menus
     *
     * @access  public
     * @return  array  Array with all the available menus and Jaws_Error on error
     */
    function GetMenus($gid = null, $target = null, $onlyVisible = false, $orderBy = '[menu_type] ASC, [title] ASC')
    {
        $sql = '
            SELECT
                [id], [menu_type], [title], [url], [url_target], [rank]'. ($onlyVisible == false ? ', [visible]' : ' ') .'
            FROM [[menus]]
			WHERE (';
			
		$sql .= (empty($target)? '[url_target] = 0' : '[url_target] = {url_target}');
        
		$sql .= (empty($gid)? '' : ' AND [gid] = {gid}');
				
		$sql .= ')';
		
		if (!empty($orderBy)) {	
			$sql .= ' ORDER BY '.$orderBy;
		}

        $params = array();
        $params['gid'] = $gid;
        $params['url_target'] = $target;

		$result = $GLOBALS['db']->queryAll($sql, $params);
        
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MENU_ERROR_GET_MENUS'), _t('MENU_NAME'));
        }

        return $result;
    }
	
}