<?php
/**
 * Properties - Search gadget hook
 *
 * @category   GadgetHook
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesSearchHook
{
    /**
     * Gets the gadget's search fields
     */
    function GetSearchFields() {
        return array(
                    array(
						'[[property]].[price]',
						'[[property]].[category]',
						'[[property]].[title]',
						'[[property]].[mls]', 
						'[[property]].[sm_description]',
						'[[property]].[description]',
						'[[property]].[address]',
						'[[property]].[city]',
						'[[property]].[region]',
						'[[property]].[postal_code]',
						'[[property]].[community]',
						'[[property]].[lotno]',
						'[[property]].[propertyno]',
						'[[property]].[status]',
						'[[property]].[fast_url]',
						'[[property]].[amenity]'),
                    array(
						'[[propertyparent]].[propertyparentcategory_name]',
						'[[propertyparent]].[propertyparentdescription]',
						'[[propertyparent]].[propertyparentfast_url]',
						'[[propertyparent]].[propertyparentrss_url]')
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
		$pages = array();
        $date  = $GLOBALS['app']->loadDate();
		$params = array('Active' => 'Y');

        // Properties
		$sql = '
            SELECT
               [id], [linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [created], [updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates]
            FROM [[property]] 
            WHERE [active] = {Active} AND 
            ';

        $sql .= isset($pSql[0])? $pSql[0] : '';
        $sql .= ' ORDER BY [propertyno] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'decimal', 'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'decimal', 'decimal', 'text', 'text', 'integer', 
			'integer', 'text', 'integer', 'integer', 'integer', 'integer', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'
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
                $url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $p['id']));
            } else {
                $url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $p['fast_url']));
            }

            $page['url']     = $url;
            $page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/Properties/images/logo.png';
            if (isset($p['sm_description'])) {
				$page['snippet'] = $p['sm_description'];
            } else {
				$page['snippet'] = (strlen(strip_tags($p['description'])) > 247 ? substr(strip_tags($p['description']),0,247) : strip_tags($p['description']));
            }
            $page['date']    = $date->ToISO($p['updated']);

            $stamp           = str_replace(array('-', ':', ' '), '', $p['updated']);
            $pages[$stamp]   = $page;
        }
	
        
		$sql = "
            SELECT [propertyparentid], [propertyparentparent], [propertyparentsort_order], [propertyparentcategory_name], 
				[propertyparentimage], [propertyparentdescription], [propertyparentactive], 
				[propertyparentownerid], [propertyparentcreated], [propertyparentupdated], 
				[propertyparentfeatured], [propertyparentfast_url], [propertyparentrss_url], [propertyparentregionid],
				[propertyparentrss_overridecity], [propertyparentrandomize]				
            FROM [[propertyparent]]
            WHERE [propertyparentactive] = {Active} AND ";
        $sql .= isset($pSql[1])? $pSql[1] : '';
        $sql .= ' ORDER BY [propertyparentcreated] DESC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'integer', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //echo $result->getMessage();
            return array();
        }

        foreach ($result as $p) {
            $page = array();
            $page['title'] = $p['propertyparentcategory_name'];
            if (empty($p['propertyparentfast_url'])) {
                $url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $p['propertyparentid']));
            } else {
                $url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $p['propertyparentfast_url']));
            }

            $page['url']     = $url;
            $page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/Properties/images/logo.png';
			$page['snippet'] = (strlen(strip_tags($p['propertyparentdescription'])) > 247 ? substr(strip_tags($p['propertyparentdescription']),0,247) : strip_tags($p['propertyparentdescription']));
            $page['date']    = $date->ToISO($p['propertyparentupdated']);

            $stamp           = str_replace(array('-', ':', ' '), '', $p['propertyparentupdated']);
            $pages[$stamp]   = $page;
        }
	
        return $pages;
    }
}
