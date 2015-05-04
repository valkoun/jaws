<?php
/**
 * Calendar - Search gadget hook
 *
 * @category   GadgetHook
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CalendarSearchHook
{
    /**
     * Gets the gadget's search fields
     */
    function GetSearchFields() {
        return array(
                    array(
						'[[calendar]].[event]', 
						'[[calendar]].[startdate]', 
						'[[calendar]].[enddate]', 
						'[[calendar]].[sm_description]', 
						'[[calendar]].[description]', 
						'[[calendar]].[host]', 
						'[[calendar]].[alink]', 
						'[[calendar]].[alinktitle]'
					),
                    array(
						'[[calendarparent]].[calendarparentcategory_name]',
						'[[calendarparent]].[calendarparentdescription]'
					)
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

        // Events
		$sql = '
            SELECT
                [id], [event], [startdate], [enddate], 
				[sm_description], [description], [image], [host], 
				[itime], [endtime], [alink], [alinktitle], [alinktype], 
				[isrecurring], [active], [ownerid], [linkid], [created], 
				[updated], [checksum], [max_occupancy], [occupants] 
            FROM [[calendar]] 
            WHERE [active] = {Active} AND 
            ';

        $sql .= isset($pSql[0])? $pSql[0] : '';
        $sql .= ' ORDER BY [startdate] DESC';

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //echo $result->getMessage();
            return array();
        }

        foreach ($result as $p) {
            $page = array();
            $page['event'] = $p['event'];
			$url = $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $p['id']));

            $page['url']     = $url;
            $page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/Calendar/images/logo.png';
            if (isset($p['sm_description']) && trim($p['sm_description']) != '') {
				$page['snippet'] = $p['sm_description'];
            } else {
				$page['snippet'] = (strlen(strip_tags($p['description'])) > 247 ? substr(strip_tags($p['description']),0,247).'...' : strip_tags($p['description']));
            }
            $page['date']    = $date->ToISO($p['updated']);

            $stamp           = str_replace(array('-', ':', ' '), '', $p['updated']);
            $pages[$stamp]   = $page;
        }
	
        // Calendar
		/*
		$sql = "
            SELECT [calendarparentid], [calendarparentsort_order], [calendarparentcategory_name], 
				[calendarparentimage], [calendarparentdescription], [calendarparentactive],
				[calendarparentownerid], [calendarparentcreated], [calendarparentupdated], 
				[calendarparentfeatured], [calendarparenttype], [calendarparentgadget], [calendarparentgadget_action], 
				[calendarparentgadget_reference], [calendarparentchecksum]
            FROM [[calendarparent]]
            WHERE [calendarparentactive] = {Active} AND 
			";
        $sql .= isset($pSql[1])? $pSql[1] : '';
        $sql .= ' ORDER BY [calendarparentcreated] DESC';

        $types = array(
			'integer', 'integer', 'text', 
			'text', 'text', 'text', 
			'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 
			'text', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return $result;
            //return array();
        }

        foreach ($result as $p) {
            $page = array();
            $page['title'] = $p['calendarparentcategory_name'];
			$url = $GLOBALS['app']->Map->GetURLFor('Calendar', 'Calendar', array('id' => $p['calendarparentid']));

            $page['url']     = $url;
            $page['image']   = $GLOBALS['app']->GetJawsURL() . '/gadgets/Calendar/images/logo.png';
			$page['snippet'] = (strlen(strip_tags($p['calendarparentdescription'])) > 247 ? substr(strip_tags($p['calendarparentdescription']),0,247).'...' : strip_tags($p['calendarparentdescription']));
            $page['date']    = $date->ToISO($p['productparentupdated']);

            $stamp           = str_replace(array('-', ':', ' '), '', $p['calendarparentupdated']);
            $pages[$stamp]   = $page;
        }
		*/
        return $pages;
    }
}
