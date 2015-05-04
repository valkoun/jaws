<?php
/**
 * CustomPage - Search gadget hook
 *
 * @category   GadgetHook
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CustomPageSearchHook extends Jaws_Model
{
    /**
     * Gets the gadget's search fields
     */
    function GetSearchFields() {
        return array(
                    array(
						'[title]', 
						'[description]', 
						'[url]', 
						'[rss_url]'),
                    array(
						'[[pages]].[fast_url]',
						'[[pages]].[title]',
						'[[pages]].[content]',
						'[[pages]].[keywords]',
						'[[pages]].[description]',
						'[[pages]].[auto_keyword]',
						'[[pages]].[sm_description]')
			);
    }

    /**
     * Returns an array with the results of a search
     *
     * @access  public
     * @param   string  $pSql  Prepared search (WHERE) SQL
     * @return  array   An array of entries that matches a certain pattern
     */
    function Hook($pSql = '', $limit = null)
    {
        $date  = $GLOBALS['app']->loadDate();
		$params = array();
		$params['Active'] = 'Y';
		$params['gadget'] = 'text';

        // Posts
		$sql = '
            SELECT
				[id], [sort_order], [linkid], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated], [gadget],
				[url], [url_target], [rss_url], [section_id], [image_code], [checksum]
			FROM [[pages_posts]]
            WHERE [active] = {Active} AND [gadget] = {gadget} AND 
            ';

        $sql .= isset($pSql[0])? $pSql[0] : '';
        $sql .= ' ORDER BY [sort_order] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 
			'text', 'integer', 'timestamp', 'timestamp', 'text',
			'text', 'text', 'text', 'integer', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //echo $result->getMessage();
            return array();
        }

        $model = $GLOBALS['app']->loadGadget('CustomPage', 'Model');
        foreach ($result as $p) {
            $page = array();
			$parent = $model->GetPage($p['linkid']);
			if (Jaws_Error::isError($parent)) {
				//echo $result->getMessage();
				return array();
			} else {
				$page['title'] = $p['title'];
				if (empty($parent['fast_url'])) {
					$url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $parent['id']));
				} else {
					$url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $parent['fast_url']));
				}

				$page['url']     = $url;
				$page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/CustomPage/images/logo.png';
				if (!empty($p['description'])) {
					$page['snippet'] = $p['description'];
				} else {
					$page['snippet'] = (strlen(strip_tags($parent['description'])) > 247 ? substr(strip_tags($parent['description']),0,247) : strip_tags($parent['description']));
				}
				$page['date']    = $date->ToISO($parent['updated']);

				$stamp           = str_replace(array('-', ':', ' '), '', $parent['updated']);
				$pages[$stamp]   = $page;
			}
        }
	
        // Pages
		$params2 = array();
		$params2['gadget'] = 'CustomPage';
		$params2['Active'] = 'Y';
		$sql2 = "
            SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
            FROM [[pages]]
            WHERE [active] = {Active} AND [gadget] = {gadget} AND ";
        $sql2 .= isset($pSql[1])? $pSql[1] : '';
        $sql2 .= ' ORDER BY [created] DESC';

        $types2 = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $result2 = $GLOBALS['db']->queryAll($sql2, $params2, $types2);
        if (Jaws_Error::IsError($result2)) {
            //echo $result->getMessage();
            return array();
        }

        foreach ($result2 as $p) {
            $page = array();
            $page['title'] = $p['title'];
            if (empty($p['fast_url'])) {
                $url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $p['id']));
            } else {
                $url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $p['fast_url']));
            }

            $page['url']     = $url;
            $page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/CustomPage/images/logo.png';
			if (!empty($p['sm_description'])) {
				$page['snippet'] = $p['sm_description'];
			} else {
				$page['snippet'] = (strlen(strip_tags($p['description'])) > 247 ? substr(strip_tags($p['description']),0,247) : strip_tags($p['description']));
			}
            $page['date']    = $date->ToISO($p['updated']);

            $stamp           = str_replace(array('-', ':', ' '), '', $p['updated']);
            $pages[$stamp]   = $page;
        }
	
        return $pages;
    }
}
