<?php
/**
 * Store - Search gadget hook
 *
 * @category   GadgetHook
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class StoreSearchHook
{
    /**
     * Gets the gadget's search fields
     */
    function GetSearchFields() {
        return array(
                    array(
						'[[product]].[price]',
						'[[product]].[title]',
						'[[product]].[product_code]', 
						'[[product]].[sm_description]',
						'[[product]].[description]',
						'[[product]].[alinktitle]',
						'[[product]].[alink2title]',
						'[[product]].[alink3title]',
						'[[product]].[internal_productno]',
						'[[product]].[fast_url]'),
                    array(
						'[[productparent]].[productparentcategory_name]',
						'[[productparent]].[productparentdescription]',
						'[[productparent]].[productparentfast_url]')
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
		$params = array('Active' => 'Y');

        // Properties
		$sql = '
            SELECT
               [id], [brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [created], [updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum]
            FROM [[product]] 
            WHERE [active] = {Active} AND 
            ';

        $sql .= isset($pSql[0])? $pSql[0] : '';
        $sql .= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'decimal', 'decimal', 
			'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'timestamp', 'timestamp', 
			'text', 'integer', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //echo $result->getMessage();
            return array();
        }

        foreach ($result as $p) {
            $page = array();
            $page['title'] = $p['title'];
            if (empty($p['fast_url'])) {
                $url = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $p['id']));
            } else {
                $url = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $p['fast_url']));
            }

            $page['url']     = $url;
            $page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/Store/images/logo.png';
            if (isset($p['sm_description']) && trim($p['sm_description']) != '') {
				$page['snippet'] = $p['sm_description'];
            } else {
				$page['snippet'] = (strlen(strip_tags($p['description'])) > 247 ? substr(strip_tags($p['description']),0,247).'...' : strip_tags($p['description']));
            }
            $page['date']    = $date->ToISO($p['updated']);

            $stamp           = str_replace(array('-', ':', ' '), '', $p['updated']);
            $pages[$stamp]   = $page;
        }
	
        
		$sql = "
            SELECT [productparentid], [productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl],[productparenturl_target],[productparentimage_code]
            FROM [[productparent]]
            WHERE [productparentactive] = {Active} AND 
			";
        $sql .= isset($pSql[1])? $pSql[1] : '';
        $sql .= ' ORDER BY [productparentcreated] DESC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return $result;
            //return array();
        }

        foreach ($result as $p) {
            $page = array();
            $page['title'] = $p['productparentcategory_name'];
            if (empty($p['productparentfast_url'])) {
                $url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $p['productparentid']));
            } else {
                $url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $p['productparentfast_url']));
            }

            $page['url']     = $url;
            $page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/Store/images/logo.png';
			$page['snippet'] = (strlen(strip_tags($p['productparentdescription'])) > 247 ? substr(strip_tags($p['productparentdescription']),0,247).'...' : strip_tags($p['productparentdescription']));
            $page['date']    = $date->ToISO($p['productparentupdated']);

            $stamp           = str_replace(array('-', ':', ' '), '', $p['productparentupdated']);
            $pages[$stamp]   = $page;
        }
	
        return $pages;
    }
}
