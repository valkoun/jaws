<?php
/**
 * Search Gadget
 *
 * @category   GadgetModel
 * @package    Search
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class SearchModel extends Jaws_Model
{
    var $_SearchTerms = array();

    /**
     * Return the search results
     *
     * @access  public
     * @return  string  Array with the search result
     * @TODO  Prioritized gadgets to registry key
     */
    function Search($options)
    {
		$result = array();
        $result['_totalItems'] = 0;

        $this->_SearchTerms = $options;
        $gadgetList = $this->GetSearchableGadgets();
		unset($gadgetList['Search']);
        $gSearchable = $GLOBALS['app']->Registry->Get('/gadgets/Search/searchable_gadgets');
		$gSearchable = str_replace('Search', '', $gSearchable);
        $gadgets = ($gSearchable=='*')? array_keys($gadgetList) : explode(', ', $gSearchable);
        if (array_key_exists('gadgets',  $options) &&
            !empty($options['gadgets']) &&
            in_array($options['gadgets'], $gadgets))
        {
            $gadgets = array($options['gadgets']);
        }
		// Prioritize gadget results
		$prioritized = array();
		$gadget_priority = array('Users', 'Store', 'Properties', 'CustomPage', 'Blog');
		foreach ($gadget_priority as $priority) {
			if (in_array($priority, $gadgets)) {
				$prioritized[] = $priority;
			}
		}
		$prioritized = array_unique(array_merge($prioritized, $gadgets));
		$gadgets = $prioritized;
        if (is_array($gadgets) && count($gadgets) > 0) {
            /*
			if ($options['response'] == 'li') {
				$gadgets = array($gadgets[(int)$options['num']]);
			}
			*/
			$GLOBALS['db']->dbc->loadModule('Function', null, true);
            foreach ($gadgets as $gadget) {
				$gadget = trim($gadget);
                if (empty($gadget)) {
                    continue;
                }

                $gHook = $GLOBALS['app']->LoadHook($gadget, 'Search');
                if ($gHook === false) {
                    continue;
                }

                $searchFields = null;
                $result[$gadget] = array();
                if (method_exists($gHook, 'GetSearchFields')) {
                    $searchFields = $gHook->GetSearchFields();
                }

                if (is_array($searchFields)) {
                    $params = array();
                    $i = 0;
                    $preparedSQLs = array();
                    foreach($searchFields as $fields) {
                        $preparedSQL  = '';
                        foreach($options as $option => $words) {
                            $sqlFields = '';
                            if (is_array($words)) {
                                $words = array_map('trim', $words);
                                $words = array_filter($words , 'trim');
                                foreach($words as $widx => $word) {
                                    $word = $GLOBALS['app']->UTF8->trim($word);
                                    switch($option) {
                                    case 'exclude':
                                        foreach($fields as $fidx => $field) {
                                            $sqlFields .= ' '.$GLOBALS['db']->dbc->datatype->matchPattern(
                                                                array(1 => '%', $word, '%'),
                                                                'NOT ILIKE',
                                                                $field);
                                            if ($fidx != count($fields) -1)
                                                $sqlFields .= ' AND';
                                        }
                                        if ($widx !=  count($words) -1)
                                            $sqlFields .= ' AND';
                                        break;
                                    case 'all':
                                    case 'exact':
                                        foreach($fields as $fidx => $field) {
                                            if ($fidx == 0) $sqlFields .= '(';
                                            $sqlFields .= ' '.$GLOBALS['db']->dbc->datatype->matchPattern(
                                                                array(1 => '%', $word, '%'),
                                                                'ILIKE',
                                                                $field);
                                            if ($fidx == count($fields) -1)
                                                $sqlFields .= ')';
                                            else
                                                $sqlFields .= ' OR';
                                        }
                                        if ($widx !=  count($words) -1)
                                            $sqlFields .= ' AND';
                                        break;
                                    case 'least':
                                        foreach($fields as $fidx => $field) {
                                            $sqlFields .= ' '.$GLOBALS['db']->dbc->datatype->matchPattern(
                                                                array(1 => '%', $word, '%'),
                                                                'ILIKE',
                                                                $field);
                                            if ($fidx != count($fields) -1)
                                                $sqlFields .= ' OR';
                                        }
                                        if ($widx !=  count($words) -1)
                                            $sqlFields .= ' OR';
                                        break;
                                    }

                                    $i++;
                                }

                                if (!empty($sqlFields)) {
                                    $preparedSQL.= empty($preparedSQL)? $sqlFields : ' AND ('.$sqlFields.')';
                                }
                            }
                        }
                        $preparedSQLs[] = $GLOBALS['db']->sqlParse($preparedSQL, $params);
                    }    
                } else {
                    $preparedSQLs = $options;
                }

                if (is_array($preparedSQLs) && count($preparedSQLs) == 1) {
                    $preparedSQLs = $preparedSQLs[0];
                }
				
                $gResult = $gHook->Hook($preparedSQLs, ($options['response'] == 'li' && isset($options['limit']) && (int)$options['limit'] > 0 ? $options['limit']+1 : null));
                //FIXME: should test only IsError but most gadgets only return false...
				if (!Jaws_Error::IsError($gResult) && $gResult !== false) {
                    if (is_array($gResult) && !empty($gResult) && !count($gResult) <= 0) {
						$result[$gadget] = $gResult;
                        $result['_totalItems'] = ((int)$result['_totalItems'] + count($gResult));
                    } else {
                        unset($result[$gadget]);
                    }
                }
				if ($options['response'] == 'li' && $result['_totalItems'] > (int)$options['limit']) {
					break;
				}
            }

            reset($result);
        }

        return $result;
    }

    /**
     * Join search phrase for provide string for showing in result title
     *
     * @access  public
     * @return  string  
     */
    function implodeSearch($options = null)
    {
        if (is_null($options)) {
            $options = $this->_SearchTerms;
        }

        $resTitle = '';
        $terms = implode(' ', is_array($options['all'])? $options['all'] : explode(' ', $options['all']));
        if (!empty($terms)) {
            $resTitle .= $terms;
        }

        $terms = implode(' +', is_array($options['least'])? $options['least'] : explode(' ', $options['least']));
        if (!empty($terms)) {
            $resTitle .= ' +' . $terms;
        }

        $terms = is_array($options['exact'])? implode(' ', $options['exact']) : $options['exact'];
        if (!empty($terms)) {
            $resTitle .= ' "' . $terms . '"';
        }

        $terms = implode(' -', is_array($options['exclude'])? $options['exclude'] : explode(' ', $options['exclude']));
        if (!empty($terms)) {
            $resTitle .= ' -' . $terms;
        }

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        return $xss->filter($resTitle);
    }

    /**
     * Parses a search phrase to find the excluding matches, exact matches, 
     * any matches and all other words
     *
     * @access  public
     * @param   string  $phrase   Phrase to parse
     * @return  array   An array with the following indexes (and results):
     *                     - all, exact, least and exclude
     */
    function parseSearch($options, &$searchable)
    {
        $phrase = $options['all'];
        if (!empty($phrase)) {
            $phrase.= chr(32);
        }
        $newOptions = array('all'     => '', 
                            'exact'   => '', 
                            'least'   => '', 
                            'exclude' => '',
                            'date'    => '',
                            'response'    => '',
                            'num'    => '',
                            'limit'    => '');
        $size = $GLOBALS['app']->UTF8->strlen($phrase);
        $lastKey = '';
        $tmpWord = '';
        for($i=0; $i<$size; $i++) {
            $word = $GLOBALS['app']->UTF8->substr($phrase, $i , 1);
            $ord  = $GLOBALS['app']->UTF8->ord($word);
            $tmpWord.= $word;
            switch($ord) {
            case 34: // Quotes..
                if ($lastKey == 'exact') { //Open exact was open, we are closing it
                    $newOptions['exact'].= $GLOBALS['app']->UTF8->substr($tmpWord, 1, $GLOBALS['app']->UTF8->strlen($tmpWord) - 2);
                    $lastKey = '';
                    $tmpWord = '';
                } else if (empty($lastKey)) {
                    $lastKey = 'exact'; //We open the exact match
                }
                break;
            case 43: //Plus
                if ($lastKey != 'exact') {
                    $lastKey = 'least';
                }
                break;
            case 45: //Minus
                if ($lastKey != 'exclude') {
                    $lastKey = 'exclude';
                }
                break;
            case 32: //Space
                if ($lastKey != 'exact' && !empty($lastKey)) {
                    if ($lastKey != 'all') {
                        $substrCount = 1;
                        if ($tmpWord[0] == ' ') {
                            $substrCount = 2;
                        }
                        $newOptions[$lastKey].= $GLOBALS['app']->UTF8->substr($tmpWord, $substrCount);
                    } else {
                        $newOptions[$lastKey].= $tmpWord;
                    }
                    $lastKey = '';
                    $tmpWord = '';
                }
                break;
            default:
                //Any other word opens all
                if (empty($lastKey)) {
                    $lastKey = 'all';
                }
                break;
            }
        }

        $options['all'] = '';
        $min_key_len = $GLOBALS['app']->Registry->Get('/gadgets/Search/min_key_len');
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        foreach(array_keys($newOptions) as $option) {
            if (!empty($newOptions[$option])) {
                $options[$option] = trim(isset($options[$option])?
                                         $options[$option] . ' ' . $newOptions[$option] :
                                         $newOptions[$option]);
            }

            $content = (isset($options[$option])) ? $options[$option] : '';
            $content = $xss->parse($content);
            $content = $GLOBALS['app']->UTF8->strtolower($GLOBALS['app']->UTF8->trim($content));
            if ($GLOBALS['app']->UTF8->strlen($content) >= $min_key_len) {
                $searchable = true;
            }

            $options[$option] = ($option == 'limit' || $option == 'response' || $option == 'num' ? $options[$option] : '');
            switch($option) {
            case 'exclude':
            case 'least':
            case 'all':
                $options[$option] = array_filter(explode(' ', $content));
                break;
            case 'exact':
                $options[$option] = array($content);
                break;
            case 'date':
                if (in_array($content, array('past_1month', 'past_2month', 'past_3month',
                                             'past_6month', 'past_1year',  'anytime'))) {
                    $options[$option] = array($content);
                } else {
                    $options[$option] = array('anytime');
                }
                break;
            }

        }

        return $options;
    }

    /**
     * Get searchable gadgets
     *
     * @access  public
     * @return  array
     */
    function GetSearchableGadgets()
    {
        $jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
        $gadgetList = $jms->GetGadgetsList(null, true, true);
        $gadgets = array();
        foreach ($gadgetList as $key => $gadget) {
            if (is_file(JAWS_PATH . 'gadgets/' . $gadget['realname'] . '/hooks/Search.php'))
                $gadgets[$key] = $gadget;
        }
        return $gadgets;
    }

    /**
     * Get entry pager numbered links
     *
     * @access  public
     * @param   int     $page      Current page number
     * @param   int     $page_size Search result count per page
     * @param   int     $total     Total search result count
     * @return  array   array with numbers of pages
     */
    function GetEntryPagerNumbered($page, $page_size, $total)
    {
        $tail = 1;
        $paginator_size = 4;
        $pages = array();
        if ($page_size == 0) {
            return $pages;
        }

        $npages = ceil($total / $page_size);

        if ($npages < 2) {
            return $pages;
        }

        // Previous
        if ($page == 1) {
            $pages['previous'] = false;
        } else {
            $pages['previous'] = $page - 1;
        }

        if ($npages <= ($paginator_size + $tail)) {
            for ($i = 1; $i <= $npages; $i++) {
                if ($i == $page) {
                    $pages['current'] = $i;
                } else {
                    $pages[$i] = $i;
                }
            }
        } elseif ($page < $paginator_size) {
            for ($i = 1; $i <= $paginator_size; $i++) {
                if ($i == $page) {
                    $pages['current'] = $i;
                } else {
                    $pages[$i] = $i;
                }
            }

            $pages['separator2'] = true;

            for ($i = $npages - ($tail - 1); $i <= $npages; $i++) {
                $pages[$i] = $i;
            }
            
        } elseif ($page > ($npages - $paginator_size + $tail)) {
            for ($i = 1; $i <= $tail; $i++) {
                $pages[$i] = $i;
            }

            $pages['separator1'] = true;

            for ($i = $npages - $paginator_size + ($tail - 1); $i <= $npages; $i++) {
                if ($i == $page) {
                    $pages['current'] = $i;
                } else {
                    $pages[$i] = $i;
                }
            }
        } else {
            for ($i = 1; $i <= $tail; $i++) {
                $pages[$i] = $i;
            }

            $pages['separator1'] = true;

            $start = floor(($paginator_size - $tail)/2);
            $end = ($paginator_size - $tail) - $start;
            for ($i = $page - $start; $i < $page + $end; $i++) {
                if ($i == $page) {
                    $pages['current'] = $i;
                } else {
                    $pages[$i] = $i;
                }
            }

            $pages['separator2'] = true;

            for ($i = $npages - ($tail - 1); $i <= $npages; $i++) {
                $pages[$i] = $i;
            }
            
        }

        // Next
        if ($page == $npages) {
            $pages['next'] = false;
        } else {
            $pages['next'] = $page + 1;
        }

        $pages['total'] = $total;

        return $pages;
    }
}
