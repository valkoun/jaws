<?php
/**
 * Blog Gadget
 *
 * @category   Gadget
 * @package    Blog
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class BlogHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access       public
     */
    function BlogHTML()
    {
        $this->Init('Blog');
    }

    /**
     * Calls default action(view)
     *
     * @access       public
     * @return       template content
     */
    function DefaultAction()
    {
        $default_view = $GLOBALS['app']->Registry->Get('/gadgets/Blog/default_view');
        switch ($default_view) {
            case 'default_category':
                $cat = $GLOBALS['app']->Registry->Get('/gadgets/Blog/default_category');
                return $this->ShowCategory($cat);
                break;
            case 'monthly':
                $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
                $dates = $model->GetPostsDateLimitation(true);
                $date = $GLOBALS['app']->loadDate();
                $mDate = $date->Format($dates['max_date'], 'Y-m');
                $mDate = explode('-', $mDate);
                return $this->ViewDatePage($mDate[0], $mDate[1]);
                break;
            case 'latest_entry':
                return $this->LastPost();
                break;
            default:
                return $this->ViewPage();
        }
    }

    /**
     * Display last blog posts ordered by date.
     *
     * @access 	public
     * @return  string  Template content
     */
    function LastPost()
    {
        $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog', 'Atom'),
                                             'alternate',
                                             'application/atom+xml',
                                             'Atom - All');
        $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog', 'RSS'),
                                             'alternate',
                                             'application/rss+xml',
                                             'RSS 2.0 - All');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $id = $model->GetLatestPublishedEntryID();
        if (!Jaws_Error::IsError($id) && !empty($id)) {
            return $this->SingleView($id);
        }

        return false;
    }

    /**
     * Display blog entry.
     *
     * @access      public
     * @return  string  Template content
     */
    function ViewPage()
    {
        $request =& Jaws_Request::getInstance();
        $page = $request->get('page', 'get');

        if (is_null($page) || $page <= 0 ) {
            $page = 1;
        }

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');

        $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog', 'Atom'),
                                             'alternate',
                                             'application/atom+xml',
                                             'Atom - All');
        $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog', 'RSS'),
                                             'alternate',
                                             'application/rss+xml',
                                             'RSS 2.0 - All');
        /**
         * This will be supported in next Blog version - Bookmarks for each categorie
         *
         * $categories = $model->GetCategories();
         * if (!Jaws_Error::IsError($categories)) {
         * //$GLOBALS['app']->Layout->AddHeadLink($base_url.'blog.atom', 'alternate', 'application/atom+xml', 'Atom - All');
         * foreach ($categories as $cat) {
         *                $name = $cat['name'];
         * }
         *
         * foreach ($categories as $cat) {
         *   $name = $cat['name'];
         * }
         *}
         */

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('ViewPage.html');
        $tpl->SetBlock('view');

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $entries = $model->GetEntriesAsPage($page);
        if (!Jaws_Error::IsError($entries) && count($entries) > 0) {
            $row = 0;
            $col = 0;
            $index = 0;
            $columns = (int) $GLOBALS['app']->Registry->Get('/gadgets/Blog/columns');
            $columns = ($columns <= 0)? 1 : $columns;
            foreach ($entries as $entry) {
                if ($col == 0) {
                    $tpl->SetBlock('view/entryrow');
                    $tpl->SetVariable('row', $row);
                }

                $res = $this->ShowEntry($entry, true, true);
                $tpl->SetBlock('view/entryrow/column');
                $tpl->SetVariable('col', $col);
                $tpl->SetVariable('entry', $res);
                $tpl->ParseBlock('view/entryrow/column');

                $index++;
                $col = $index % $columns;
                if ($col == 0 || $index == count($entries)) {
                    $row++;
                    $tpl->ParseBlock('view/entryrow');
                }
            }
        }

        if ($tpl->VariableExists('navigation')) {
            $total = $model->GetNumberOfPages();
            $limit = $GLOBALS['app']->Registry->Get('/gadgets/Blog/last_entries_limit');
            $tpl->SetVariable('navigation', $this->GetNumberedPageNavigation($page, $limit, $total, 'ViewPage'));
        }
        $tpl->ParseBlock('view');
        return $tpl->Get();
    }

    /**
     * Display blog posts by author.
     *
     * @category 	feature
     * @access      public
     * @return  string  Template content
     */
    function ViewAuthorPage()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'page'), 'get');

        $page = $post['page'];
        if (is_null($page) || $page <= 0 ) {
            $page = 1;
        }

        $user = $post['id'];
        if (!isset($user) || empty($user)) {
            return false;
        }

        $condition = null;
        if (is_numeric($user)) {
            $condition = ' AND [[blog]].[user_id] = {user}';
        } else {
            $condition = ' AND [[users]].[username] = {user}';
        }

        $res = '';
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $entries = $model->GetEntriesAsPage($page, $condition, array('user' => $user));
        if (!Jaws_Error::IsError($entries)) {
            foreach ($entries as $e) {
                $res .= $this->ShowEntry($e, true, true);
            }
        }
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('ViewAuthor.html');
        $tpl->SetBlock('view');
        $tpl->SetVariable('entries', $res);
        if ($tpl->VariableExists('navigation')) {
            $total  = $model->GetAuthorNumberOfPages($user);
            $limit  = $GLOBALS['app']->Registry->Get('/gadgets/Blog/last_entries_limit');
            $params = array('id'  => $user);
            $tpl->SetVariable('navigation',
                              $this->GetNumberedPageNavigation($page, $limit, $total, 'ViewAuthorPage', $params));
        }
        $tpl->ParseBlock('view');
        return $tpl->Get();
    }

    /**
     * Display blog posts by date.
     *
     * @category 	feature
     * @access       public
     * @return  string  Template content
     */
    function ViewDatePage($year = '', $month = '', $day = '')
    {
        $request =& Jaws_Request::getInstance();
        $get = $request->get(array('year', 'month', 'day', 'page'), 'get');
        $page = (empty($get['page']) || $get['page'] <= 0)? 1 : $get['page'];

        if (empty($year)) {
            if (empty($get['year'])) {
                return false;
            }

            //Month, day and year
            $year  = $get['year'];
            $month = (string) $get['month'];
            $day   = (string) empty($month)? '' : $get['day'];
        }

        $date = $GLOBALS['app']->loadDate();
        $min_date = $date->ToBaseDate($year,
                                      empty($month)? 1 : $month,
                                      empty($day)? 1 : $day);
        $max_date = $date->ToBaseDate(empty($month)? ($year + 1) : $year,
                                      empty($month)? 1 : (empty($day)? ($month + 1) : $month),
                                      empty($day)? 1 : ($day + 1));
        if (!$min_date['timestamp'] || !$max_date['timestamp']) {
            return false;
        }

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('ViewDate.html');
        $tpl->SetBlock('view_date');

        if (empty($month)) {
            $title = $year;
        } else {
            if (empty($day)) {
                $title = $date->MonthString($month).' '.$year;
            } else {
                $title = $date->MonthString($month).' '.$day.', '.$year;
            }
        }
        $tpl->SetVariable('title', $title);

        $entries = '';
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $entriesbydate = $model->GetEntriesByDate($page, $min_date['timestamp'], $max_date['timestamp']);
        if (!Jaws_Error::IsError($entriesbydate)) {
            foreach ($entriesbydate as $e) {
                $entries .= $this->ShowEntry($e, true, true);
            }
        }
        if (empty($entries)) {
            $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            header($xss->filter($_SERVER['SERVER_PROTOCOL'])." 404 Not Found");
        }

        $tpl->SetVariable('entries', $entries);

        if ($tpl->VariableExists('page_navigation')) {
            $total  = $model->GetDateNumberOfPages($min_date['timestamp'], $max_date['timestamp']);
            $limit  = $GLOBALS['app']->Registry->Get('/gadgets/Blog/last_entries_limit');

            $params = array('year'  => $year,
                            'month' => $month,
                            'day'   => $day,
                           );
            foreach (array_keys($params, '') as $e) {
                unset($params[$e]);
            }

            $tpl->SetVariable('page_navigation',
                              $this->GetNumberedPageNavigation($page, $limit, $total, 'ViewDatePage', $params));
        }

        if ($tpl->VariableExists('date_navigation')) {
            $tpl->SetVariable('date_navigation', $this->GetDateNavigation($year, $month, $day));
        }
        $tpl->SetVariable('entries', $entries);
        $tpl->ParseBlock('view_date');

        return $tpl->Get();
    }

    /**
     * Gets year/month/day nav
     *
     * @access private
     * @return  string  Template content
     */
    function GetDateNavigation($year, $month, $day)
    {
        $date   = $GLOBALS['app']->loadDate();
        $model  = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $dLimit = $model->GetPostsDateLimitation(true);
        if ($dLimit['qty_posts'] != 0) {
            $dLimit['max_date'] = array('year'  => substr($dLimit['max_date'], 0, 4),
                                        'month' => substr($dLimit['max_date'], 5, 2),
                                        'day'   => substr($dLimit['max_date'], 8, 2));
            $dLimit['max_date']['date'] = mktime(0, 0, 0,
                                                 $dLimit['max_date']['month'],
                                                 $dLimit['max_date']['day'],
                                                 $dLimit['max_date']['year']);
            $dLimit['min_date'] = array('year'  => substr($dLimit['min_date'], 0, 4),
                                        'month' => substr($dLimit['min_date'], 5, 2),
                                        'day'   => substr($dLimit['min_date'], 8, 2));
            $dLimit['min_date']['date'] = mktime(0, 0, 0,
                                                 $dLimit['min_date']['month'],
                                                 $dLimit['min_date']['day'],
                                                 $dLimit['min_date']['year']);
        }

        if (empty($month)) {
            $pDate = $date->ToBaseDate($year, 0, 0);
            if ($dLimit['qty_posts'] == 0 ||
                mktime(0, 0, 0, $pDate['month'], $pDate['day'], $pDate['year']) < $dLimit['min_date']['date'])
            {
                $purl = null;
                $ptitle = null;
            } else {
                $bDate = $date->ToBaseDate($year - 1);
                $bYear = $date->Format($bDate['timestamp'], 'Y');
                $purl  = $GLOBALS['app']->Map->GetURLFor('Blog',
                                                         'ViewDatePage',
                                                         array('year'  => $bYear));
                $ptitle = $bYear;
            }

            $nDate = $date->ToBaseDate($year + 1, 0, 0);
            if ($dLimit['qty_posts'] == 0 ||
                mktime(0, 0, 0, $nDate['month'], $nDate['day'], $nDate['year']) >= $dLimit['max_date']['date'])
            {
                $nurl = null;
                $ntitle = null;
            } else {
                $bDate = $date->ToBaseDate($year + 1);
                $bYear = $date->Format($bDate['timestamp'], 'Y');
                $nurl = $GLOBALS['app']->Map->GetURLFor('Blog', 'ViewDatePage',
                                               array('year'  => $bYear));
                $ntitle = $bYear;
            }
        } elseif (empty($day)) {
            $pDate = $date->ToBaseDate($year, $month, 0);
            if ($dLimit['qty_posts'] == 0 ||
                mktime(0, 0, 0, $pDate['month'], $pDate['day'], $pDate['year']) < $dLimit['min_date']['date'])
            {
                $purl = null;
                $ptitle = null;
            } else {
                $bDate = $date->ToBaseDate($year, $month - 1);
                $bDate = $date->Format($bDate['timestamp'], 'Y-m');
                $bDate = explode('-', $bDate);
                $purl  = $GLOBALS['app']->Map->GetURLFor('Blog',
                                                         'ViewDatePage',
                                                         array('year'  => $bDate[0],
                                                               'month' => $bDate[1]));
                $ptitle = $date->MonthString($bDate[1]) . ' ' . $bDate[0];
            }

            $nDate = $date->ToBaseDate($year, $month + 1, 0);
            if ($dLimit['qty_posts'] == 0 ||
                mktime(0, 0, 0, $nDate['month'], $nDate['day'], $nDate['year']) >= $dLimit['max_date']['date'])
            {
                $nurl = null;
                $ntitle = null;
            } else {
                $bDate = $date->ToBaseDate($year, $month + 1);
                $bDate = $date->Format($bDate['timestamp'], 'Y-m');
                $bDate = explode('-', $bDate);
                $nurl = $GLOBALS['app']->Map->GetURLFor('Blog', 'ViewDatePage',
                                               array('year'  => $bDate[0],
                                                     'month' => $bDate[1]));
                $ntitle = $date->MonthString($bDate[1]) . ' ' . $bDate[0];
            }
        } else {
            $bDate = $date->ToBaseDate($year, $month, $day - 1);
            if ($dLimit['qty_posts'] == 0 ||
                mktime(0, 0, 0, $bDate['month'], $bDate['day'], $bDate['year']) < $dLimit['min_date']['date'])
            {
                $purl = null;
                $ptitle = null;
            } else {
                $bDate = $date->Format($bDate['timestamp'], 'Y-m-d');
                $bDate = explode('-', $bDate);
                $purl = $GLOBALS['app']->Map->GetURLFor('Blog',
                                                        'ViewDatePage',
                                                        array('year'  => $bDate[0],
                                                              'month' => $bDate[1],
                                                              'day'   => $bDate[2]));
                $ptitle = $date->MonthString($bDate[1]) . ' ' . $bDate[2] . ', '. $bDate[0];
            }

            $bDate = $date->ToBaseDate($year, $month, $day + 1);
            if ($dLimit['qty_posts'] == 0 ||
                mktime(0, 0, 0, $bDate['month'], $bDate['day'], $bDate['year']) > $dLimit['max_date']['date'])
            {
                $nurl = null;
                $ntitle = null;
            } else {
                $bDate = $date->Format($bDate['timestamp'], 'Y-m-d');
                $bDate = explode('-', $bDate);
                $nurl = $GLOBALS['app']->Map->GetURLFor('Blog',
                                                        'ViewDatePage',
                                                        array('year'  => $bDate[0],
                                                              'month' => $bDate[1],
                                                              'day'   => $bDate[2]));
                $ntitle = $date->MonthString($bDate[1]) . ' ' . $bDate[2] . ', '. $bDate[0];
            }
        }

        return $this->GetNavigation($purl, $ptitle, $nurl, $ntitle);
    }

    /**
     * Get page navigation links
     *
     * @access private
     * @return  string  Template content
     */
    function GetNumberedPageNavigation($page, $page_size, $total, $action, $params = array())
    {
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('PageNavigation.html');
        $tpl->SetBlock('pager');

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $pager = $model->GetEntryPagerNumbered($page, $page_size, $total);
        if (count($pager) > 0) {
            $tpl->SetBlock('pager/numbered-navigation');
            $tpl->SetVariable('total', _t('BLOG_ENTRIES_COUNT', $pager['total']));

            $pager_view = '';
            foreach ($pager as $k => $v) {
                $tpl->SetBlock('pager/numbered-navigation/item');
                $params['page'] = $v;
                if ($k == 'next') {
                    if ($v) {
                        $tpl->SetBlock('pager/numbered-navigation/item/next');
                        $tpl->SetVariable('lbl_next', _t('BLOG_PAGENAVIGATION_NEXTPAGE'));
                        $url = $this->GetURLFor($action, $params);
                        $tpl->SetVariable('url_next', $url);
                        $tpl->ParseBlock('pager/numbered-navigation/item/next');
                    } else {
                        $tpl->SetBlock('pager/numbered-navigation/item/no_next');
                        $tpl->SetVariable('lbl_next', _t('BLOG_PAGENAVIGATION_NEXTPAGE'));
                        $tpl->ParseBlock('pager/numbered-navigation/item/no_next');
                    }
                } elseif ($k == 'previous') {
                    if ($v) {
                        $tpl->SetBlock('pager/numbered-navigation/item/previous');
                        $tpl->SetVariable('lbl_previous', _t('BLOG_PAGENAVIGATION_PREVIOUSPAGE'));
                        $url = $this->GetURLFor($action, $params);
                        $tpl->SetVariable('url_previous', $url);
                        $tpl->ParseBlock('pager/numbered-navigation/item/previous');
                    } else {
                        $tpl->SetBlock('pager/numbered-navigation/item/no_previous');
                        $tpl->SetVariable('lbl_previous', _t('BLOG_PAGENAVIGATION_PREVIOUSPAGE'));
                        $tpl->ParseBlock('pager/numbered-navigation/item/no_previous');
                    }
                } elseif ($k == 'separator1' || $k == 'separator2') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_separator');
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_separator');
                } elseif ($k == 'current') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_current');
                    $url = $this->GetURLFor($action, $params);
                    $tpl->SetVariable('lbl_page', $v);
                    $tpl->SetVariable('url_page', $url);
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_current');
                } elseif ($k != 'total' && $k != 'next' && $k != 'previous') {
                    $tpl->SetBlock('pager/numbered-navigation/item/page_number');
                    $url = $this->GetURLFor($action, $params);
                    $tpl->SetVariable('lbl_page', $v);
                    $tpl->SetVariable('url_page', $url);
                    $tpl->ParseBlock('pager/numbered-navigation/item/page_number');
                }
                $tpl->ParseBlock('pager/numbered-navigation/item');
            }

            $tpl->ParseBlock('pager/numbered-navigation');
        }

        $tpl->ParseBlock('pager');

        return $tpl->Get();
    }

    /**
     * Get navigation links
     *
     * @access private
     * @return  string  Template content
     */
    function GetNavigation($purl, $ptitle, $nurl, $ntitle)
    {
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('PageNavigation.html');
        $tpl->SetBlock('pager');
        $tpl->SetBlock('pager/simple-navigation');

        if (!is_null($purl)) {
                $tpl->SetBlock('pager/simple-navigation/previous');
                $tpl->SetVariable('url', $purl);
				$tpl->SetVariable('title', (strlen($ptitle) > 50 ? substr($ptitle, 0, 50).'...' : $ptitle));
                $tpl->ParseBlock('pager/simple-navigation/previous');
        }

        if (!is_null($nurl)) {
                $tpl->SetBlock('pager/simple-navigation/next');
                $tpl->SetVariable('url', $nurl);
				$tpl->SetVariable('title', (strlen($ntitle) > 50 ? substr($ntitle, 0, 50).'...' : $ntitle));
                $tpl->ParseBlock('pager/simple-navigation/next');
        }

        $tpl->ParseBlock('pager/simple-navigation');
        $tpl->ParseBlock('pager');

        return $tpl->Get();
    }

    /**
     * Displays a given blog entry according to given parameters
     *
     * @access       public
     * @param        int 	$entry 	Entry ID
     * @param        boolean 	$commentLink 	Show comment link
     * @param        boolean 	$useMore 	(optional, false by default)
     * @param        string 	$tplStr 	Load from template string
     * @return  string  Template content
     * @TODO 	Add author info!!!!
     */
    function ShowEntry($entry, $commentLink = true, $useMore = false, $tplStr = '')
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Blog/resources/style.css', 'stylesheet', 'text/css');
		
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        if (empty($tplStr)) {
            $tpl->Load('Entry.html');
        } else {
            $tpl->loadFromString($tplStr, false);
        }
        $tpl->SetBlock('entry');

        $tpl->SetVariablesArray($entry);

        $tpl->SetVariable('posted_by', _t('BLOG_POSTED_BY'));
        $tpl->SetVariable('author-url',   $this->GetURLFor('ViewAuthorPage', array('id' => $entry['username'])));
        $date = $GLOBALS['app']->loadDate();
        $tpl->SetVariable('createtime-iso',       $date->ToISO($entry['publishtime']));
        $tpl->SetVariable('createtime',           $date->Format($entry['publishtime']));
        $tpl->SetVariable('createtime-monthname', $date->Format($entry['publishtime'], 'MN'));
        $tpl->SetVariable('createtime-monthabbr', $date->Format($entry['publishtime'], 'M'));
        $tpl->SetVariable('createtime-month',     $date->Format($entry['publishtime'], 'm'));
        $tpl->SetVariable('createtime-dayname',   $date->Format($entry['publishtime'], 'DN'));
        $tpl->SetVariable('createtime-dayabbr',   $date->Format($entry['publishtime'], 'D'));
        $tpl->SetVariable('createtime-day',       $date->Format($entry['publishtime'], 'd'));
        $tpl->SetVariable('createtime-year',      $date->Format($entry['publishtime'], 'Y'));
        $tpl->SetVariable('createtime-time',      $date->Format($entry['publishtime'], 'g:ia'));
        $tpl->SetVariable('entry-visits',         _t('BLOG_ENTRY_VISITS', $entry['clicks']));

        $id = empty($entry['fast_url']) ? $entry['id'] : $entry['fast_url'];
        $perm_url = $this->GetURLFor('SingleView', array('id' => $id));

        $summary = $entry['summary'];
        $text    = $entry['text'];

        // for compatibility with old versions
        $more_pos = Jaws_UTF8::strpos($text, '[more]');
        if ($more_pos !== false) {
            $summary = Jaws_UTF8::substr($text, 0, $more_pos);
            $text    = Jaws_UTF8::str_replace('[more]', '', $text);

            // Update this entry to split summary and body of post
            $model->SplitEntry($entry['id'], $summary, $text);
        }

        $summary = empty($summary)? $text : $summary;
        $summary = $this->ParseText($summary, 'Blog');
        $text    = $this->ParseText($text, 'Blog');

        if ($useMore){
            if (Jaws_UTF8::trim($text) != '') {
                $tpl->SetBlock('entry/read-more');
                $tpl->SetVariable('url', $perm_url);
                $tpl->SetVariable('read_more', _t('BLOG_READ_MORE'));
                $tpl->ParseBlock('entry/read-more');
            }
            $tpl->SetVariable('text', $summary);
        } else {
            $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog', 'Atom'),
                                                 'alternate',
                                                 'application/atom+xml',
                                                 'Atom - All');
            $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog', 'RSS'),
                                                 'alternate',
                                                 'application/rss+xml',
                                                 'RSS 2.0 - All');
            $tpl->SetVariable('text', empty($text)? $summary : $text);
        }

        $stpl = new Jaws_Template('gadgets/Blog/templates/');
        $stpl->Load('BlogPosts.html');
		$stpl->SetBlock('blog_posts');
		
		// Insert any requested Layout Actions
		$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
		$gadget_list = $jms->GetGadgetsList(null, true, true, true);
		
		$blog_posts = $model->GetAllPostsOfBlog($entry['id'], true);
        if (!Jaws_Error::IsError($blog_posts)) {
			$count_posts = count($blog_posts);
			reset($blog_posts);
            if (!$count_posts <= 0) {
				$pagecnt = 1;
				$i = 0;
				$ColCnt = 1;
				$post_rss_replace = array();
				$TDStyle = '';
				$TDAlign = 'left';
				$first_post = 0;
				$post_html = '';
				$stpl->SetBlock('blog_posts/posts');
				foreach ($blog_posts as $post) {
					$posttpl = new Jaws_Template('gadgets/Blog/templates/');
					$posttpl->Load('BlogPost.html');
					$TDAlign = ($post['layout'] == 0 ? 'left' : 'right');
					$oddevenclass0 = " blog-post-odd";
					if ($i == 0 || (($i % 2) == 0)) { 
						$oddevenclass0 = " blog-post-even";
					}
					if ($i == 0 && $first_post == 0) {
						$first_post = $post['id'];
					}
					$title = strip_tags($post['title']);
					$content = '';
					$post['description'] = $this->ParseText($post['description'], 'Blog');
					if (!empty($post['description'])) {
						$content = $post['description'];
					}
					$post['image_code'] = htmlspecialchars_decode($post['image_code']);
					$post['updated'] = $date->Format($post['updated']);
					$splash_html = '';
					$gadget_html = '';
					$i++;
						
					if ($post['gadget'] == "text") {
						$posttpl->SetBlock('post_text');
						$posttpl->SetVariable('id', $post['id']);
						$posttpl->SetVariable('evenodd', $oddevenclass0);
						$posttpl->SetVariable('timestamp', $post['updated']);
						
						if ($post['image'] != "" || $post['image_code'] != "") {
							if (substr($post['image'], 0,7) == "GADGET:") { 
								$splash_html .= '__'.$post['image'].'__'."\n";
							} else if ($post['image'] != "") {
								$post['image'] = $xss->parse(strip_tags($post['image']));
								if (substr(strtolower($post['image']), 0, 4) == "http") {
									if (substr(strtolower($post['image']), 0, 7) == "http://") {
										$main_image_src = explode('http://', $post['image']);
										foreach ($main_image_src as $img_src) {
											if (!empty($img_src)) {
												$main_image_src = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$main_image_src = explode('https://', $post['image']);
										foreach ($main_image_src as $img_src) {
											if (!empty($img_src)) {
												$main_image_src = 'https://'.$img_src;
												break;
											}
										}
									}
									if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
										$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
									}
								} else {
									$medium = Jaws_Image::GetMediumPath($post['image']);
									if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
									}
								}
								if (!empty($main_image_src)) {
									$splash_html .= '<p align="left">';
									if ($post['url'] == "javascript:void(0);") {
										$splash_html .= '<a href="javascript:void(0);" onclick="window.open(\''.$main_image_src.'\',\'\',\'scrollbars=no\')">';
									} else {
										$splash_html .= '<a href="'.$post['url'].'" target="'.$post['url_target'].'">';
									}
									$splash_html .= '<img id="blog-post-image-'.$post['id'].'" class="blog-post-image"';
									if ($post['image_width'] > 0) {
										$splash_html .= ' width="'.$post['image_width'].'"';
									} else if ($post['image_height'] > 0) { 
										$splash_html .= ' height="'.$post['image_height'].'"';
									}
									$splash_html .= ' border="0" src="'.$main_image_src.'" alt="'.htmlentities($title).'" align="'.$TDAlign.'"></a>
									</p>'."\n";
								}
							} else {
								$splash_html .= $post['image_code'];
							}
						}
						if ($i == 1) {
							$posttpl->SetBlock('post_text/table_start');
							$posttpl->ParseBlock('post_text/table_start');
						}
						if (!empty($title)) { 
							$posttpl->SetBlock('post_text/title');
							$posttpl->SetVariable('id', $post['id']);
							$posttpl->SetVariable('title', $title);
							$posttpl->SetVariable('timestamp', $post['updated']);
							$posttpl->ParseBlock('post_text/title');
						} 
						$posttpl->SetBlock('post_text/splash');
						$posttpl->SetVariable('splash_html', $splash_html);
						$posttpl->ParseBlock('post_text/splash');
						$posttpl->SetBlock('post_text/description');
						$posttpl->SetVariable('content', $content);
						$posttpl->ParseBlock('post_text/description');
						if ($pagecnt > 1) {
							if ($ColCnt == $pagecnt) {
								$posttpl->SetBlock('post_text/row_end');
								$posttpl->ParseBlock('post_text/row_end');
								$posttpl->SetBlock('post_text/anchor');
								$posttpl->SetVariable('anchor_title', $post['id']);
								$posttpl->ParseBlock('post_text/anchor');
								$ColCnt = 1;
							} else {
								if ($i == 1) {
									$posttpl->SetBlock('post_text/row_start');
									$posttpl->ParseBlock('post_text/row_start');
								} else {
									$ColCnt = $ColCnt + 1;
								}
							}
						} else {
							$ColCnt = 1;
							if ($i > 0 && $i != $count_posts) {
								$posttpl->SetBlock('post_text/row_end');
								$posttpl->ParseBlock('post_text/row_end');
							}
							$posttpl->SetBlock('post_text/anchor');
							$posttpl->SetVariable('anchor_title', $post['id']);
							$posttpl->ParseBlock('post_text/anchor');
							if ($i <= $count_posts) {
								$posttpl->SetBlock('post_text/row_start');
								$posttpl->ParseBlock('post_text/row_start');
							}
						}
						if ($i == $count_posts) {
							$posttpl->SetBlock('post_text/row_end');
							$posttpl->ParseBlock('post_text/row_end');
							$posttpl->SetBlock('post_text/table_end');
							$posttpl->ParseBlock('post_text/table_end');
						}
						$posttpl->ParseBlock('post_text');
					} else {
						$image_a = $GLOBALS['app']->UTF8->str_replace(' ', '_', $post['image']);
						$image_a = $GLOBALS['app']->UTF8->str_replace('(', '_', $image_a);
						$image_a = $GLOBALS['app']->UTF8->str_replace(')', '_', $image_a);
						
						$posttpl->SetBlock('post_gadget');
						$posttpl->SetVariable('id', $post['id']);
						$posttpl->SetVariable('gadget_action', $image_a);
						$posttpl->SetVariable('type', 'gadget');
						$gadget_html .= '__GADGET:'.$post['gadget'].'_ACTION:'.$post['image'].'__';
						$posttpl->SetVariable('content', $gadget_html);
						if ($i == 1) {
							$posttpl->SetBlock('post_gadget/table_start1');
							$posttpl->ParseBlock('post_gadget/table_start1');
						}
						$posttpl->SetBlock('post_gadget/table_end');
						$posttpl->ParseBlock('post_gadget/table_end');
						if ($i < $count_posts) {
							$posttpl->SetBlock('post_gadget/table_start2');
							$posttpl->ParseBlock('post_gadget/table_start2');
						}		
						$posttpl->ParseBlock('post_gadget');
					}
					if ($post['rss_url'] != "") {
						$posttpl->SetBlock('post_gadget');
						$posttpl->SetVariable('id', $post['id']);
						$posttpl->SetVariable('type', 'rss');
						$posttpl->SetVariable('content', '__RSS_ITEMS_POST_'.$post['id'].'__');
						if ($i == 1) {
							$posttpl->SetBlock('post_gadget/table_start1');
							$posttpl->ParseBlock('post_gadget/table_start1');
						}
						$posttpl->SetBlock('post_gadget/table_end');
						$posttpl->ParseBlock('post_gadget/table_end');
						if ($i < $count_posts) {
							$posttpl->SetBlock('post_gadget/table_start2');
							$posttpl->ParseBlock('post_gadget/table_start2');
						}		
						$posttpl->ParseBlock('post_gadget');
					}
					$post_html .= $posttpl->Get();
				}
				//Hold.. if we dont have a selected gadget?.. like no gadgets?
				if (!count($gadget_list) <= 0) {
					reset($gadget_list);
					foreach ($gadget_list as $gadget) {
						if (strpos($post_html, "__GADGET:".$gadget['realname']) !== false) {
							$layoutGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'LayoutHTML');
							$layoutActions = $adminModel->GetGadgetActions($gadget['realname']);
							$layout_html = '';
							if (!Jaws_Error::isError($layoutGadget)) {
								foreach ($layoutActions as $lactions) {
									if (isset($lactions['action']) && isset($lactions['name'])) {
										$GLOBALS['app']->Registry->LoadFile($gadget['realname']);
										if (strpos($lactions['action'], '(') === false) {
											//$this->_Template->SetVariable('ELEMENT', $goGadget->$action());
											if (isset($lactions['action'])) {
												if (method_exists($layoutGadget, $lactions['action'])) {
													if (strpos($post_html, "__GADGET:".$gadget['realname']."_ACTION:".$lactions['action']."__")) {
														$layout_html = $layoutGadget->$lactions['action']();
														$post_html = str_replace("__GADGET:".$gadget['realname']."_ACTION:".$lactions['action']."__", $layout_html, $post_html);
													}
												}
												//echo $layoutGadget->$lactions['action']();
											} elseif (isset($GLOBALS['log'])) {
												$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action $action in $gadget's LayoutHTML dosn't exist.");
											}
										} else {
											preg_match_all('/^([a-z0-9]+)\((.*?)\)$/i', $lactions['action'], $matches);
											if (isset($matches[1][0]) && isset($matches[2][0])) {
												//$this->_Template->SetVariable('ELEMENT', $goGadget->$matches[1][0]($matches[2][0]));
												if (isset($matches[1][0])) {
													if (method_exists($layoutGadget, $matches[1][0])) {
														if (strpos($post_html, "__GADGET:".$gadget['realname']."_ACTION:".$matches[1][0].'('.$matches[2][0].')__')) {
															$layout_html = $layoutGadget->$matches[1][0]($matches[2][0]);
															$post_html = str_replace("__GADGET:".$gadget['realname']."_ACTION:".$matches[1][0].'('.$matches[2][0].')__', $layout_html, $post_html);
														}
													}
													//echo $layoutGadget->$matches[1][0]($matches[2][0]);
												} elseif (isset($GLOBALS['log'])) {
													$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$matches[1][0]." in $gadget's LayoutHTML dosn't exist.");
												}
											}
										}
									}
								}
							} else {
								//$this->_Template->SetVariable('ELEMENT', '');
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERR, $gadget ." is missing the LayoutHTML. Jaws can't execute Layout " .
														 "actions if the file doesn't exists");
								}
							}
							unset($layoutActions);
							unset($layoutGadget);
							unset($layout_html);
						}
					}
				}
				$stpl->SetVariable('content', $post_html);
				$stpl->ParseBlock('blog_posts/posts');
			} else {	
				$stpl->SetBlock('blog_posts/no_posts');
				$stpl->ParseBlock('blog_posts/no_posts');
			}
        }
		$stpl->ParseBlock('blog_posts');

		$tpl->SetVariable('posts', $stpl->Get());
		
        $tpl->SetVariable('permanent-link', $perm_url);

        $pos = 1;
        $tpl->SetVariable('posted_in', _t('BLOG_POSTED_IN'));
        foreach ($entry['categories'] as $cat) {
            $tpl->SetBlock('entry/category');
            $tpl->SetVariable('id',   $cat['id']);
            $tpl->SetVariable('name', $cat['name']);
            $cid = empty($cat['fast_url']) ? $cat['id'] : $cat['fast_url'];
            $tpl->SetVariable('url',  $this->GetURLFor('ShowCategory', array('id' => $cid)));
            if ($pos == count($entry['categories'])) {
                $tpl->SetVariable('separator', '');
            } else {
                $tpl->SetVariable('separator', ',');
            }
            $pos++;
            $tpl->ParseBlock('entry/category');
        }

        if ($entry['comments'] != 0 ||
            ($entry['allow_comments'] === true &&
             $GLOBALS['app']->Registry->Get('/gadgets/Blog/allow_comments') == 'true' &&
             $GLOBALS['app']->Registry->Get('/config/allow_comments') != 'false'))
        {
            $tpl_block = $commentLink? 'comment-link' : 'comments-statistic';
            $tpl->SetBlock("entry/$tpl_block");
            $tpl->SetVariable('url', $perm_url);
            if ($commentLink && $entry['comments'] == '0') {
                $tpl->SetVariable('text_comments', _t('BLOG_COMMENT_ON_THIS'));
            } else {
                $tpl->SetVariable('text_comments', _t('BLOG_HAS_N_COMMENTS', $entry['comments']));
            }
            $tpl->SetVariable('num_comments', $entry['comments']);
            $tpl->ParseBlock("entry/$tpl_block");
        }
        $tpl->ParseBlock('entry');

        return $tpl->Get();
    }

    /**
     * Display a single blog entry.
     *
     * @access 	public
     * @param  int 	$id 	Post ID (optional, null by default)
     * @param  boolean 	$preview_mode 	Display comments flag (optional, false by default)
     * @param  int 	$reply_to_comment 	Comment ID this is in reply to
     * @return  string  Template content
     */
    function SingleView($id = null, $preview_mode = false, $reply_to_comment = '')
    {
        $request =& Jaws_Request::getInstance();
        $g_id = $request->get('id', 'get');

        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $g_id = $xss->defilter($g_id, true);

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        if (is_null($id)) {
            $entry = $model->GetEntry($g_id, true);
        } else {
            $entry = $model->GetEntry($id, true);
        }
        if (!Jaws_Error::IsError($entry) && !empty($entry)) {
            //increase entry's visits counter
            $res = $model->ViewEntry($entry['id']);
            $entry['clicks']++;

            if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/pingback') == 'true') {
                require_once JAWS_PATH . 'include/Jaws/Pingback.php';
                $pback =& Jaws_PingBack::getInstance();
                $pback->showHeaders($this->GetURLFor('Pingback', null, false, 'site_url'));
            }

            $this->SetTitle($entry['title']);
            $tpl = new Jaws_Template('gadgets/Blog/templates/');
            $tpl->Load('SingleView.html');
            $tpl->SetBlock('single-view');
            $tpl->SetVariable('entry', $this->ShowEntry($entry, false, false));
            $tpl->SetVariable('trackbacks', $this->ShowTrackbacks($entry['id']));

            $allow_comments_config = $GLOBALS['app']->Registry->Get('/config/allow_comments');
            switch ($allow_comments_config) {
                case 'restricted':
                    $allow_comments_config = $GLOBALS['app']->Session->Logged();
                    $restricted = !$allow_comments_config;
                    break;

                default:
                    $restricted = false;
                    $allow_comments_config = $allow_comments_config == 'true';
            }

            $allow_comments = $entry['allow_comments'] === true &&
                              $GLOBALS['app']->Registry->Get('/gadgets/Blog/allow_comments') == 'true' &&
                              $allow_comments_config;

            if (empty($reply_to_comment)) {
                $tpl->SetVariable('comments', $this->ShowComments($entry['id'], 0, 0, 1, (int)$allow_comments));
                if ($allow_comments) {
                    if ($preview_mode) {
                        $tpl->SetVariable('preview', $this->ShowPreview());
                    }
                    $tpl-> SetVariable('comment-form', $this->DisplayCommentForm($entry['id'], 0,
                                                                                _t('GLOBAL_RE').$entry['title']));
                } elseif ($restricted) {
                    $login_url    = $GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm');
                    $register_url = $GLOBALS['app']->Map->GetURLFor('Users', 'Registration');
                    $tpl->SetVariable('comment-form', _t('GLOBAL_COMMENTS_RESTRICTED', $login_url, $register_url));
                }

            } else {
                $tpl->SetVariable('comments', $this->ShowSingleComment($reply_to_comment));
                if ($allow_comments) {
                    if ($preview_mode) {
                        $tpl->SetVariable('preview', $this->ShowPreview());
                    }
                    $title  = $entry['title'];
                    $comment = $model->GetComment($reply_to_comment);
                    if (!Jaws_Error::IsError($comment)) {
                        $title  = $comment['title'];
                    }
                    $tpl->SetVariable('comment-form', $this->DisplayCommentForm($entry['id'], $reply_to_comment,
                                                                                _t('GLOBAL_RE'). $title));
                } elseif ($restricted) {
                    $login_url    = $GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm');
                    $register_url = $GLOBALS['app']->Map->GetURLFor('Users', 'Registration');
                    $tpl->SetVariable('comment-form', _t('GLOBAL_COMMENTS_RESTRICTED', $login_url, $register_url));
                }
            }

            if ($tpl->VariableExists('navigation')) {
                $navtpl = new Jaws_Template('gadgets/Blog/templates/');
                $navtpl->Load('EntryNavigation.html');
                if ($prev = $model->GetNOPEntry($entry['id'], 'previous')) {
                    $navtpl->SetBlock('entry-navigation/previous');
                    $navtpl->SetVariable('url', $this->GetURLFor('SingleView',
                                                                       array('id' => empty($prev['fast_url']) ?
                                                                             $prev['id'] : $prev['fast_url'])));
                    $navtpl->SetVariable('title', (strlen($prev['title']) > 50 ? substr($prev['title'], 0, 50).'...' : $prev['title']));
                    $navtpl->ParseBlock('entry-navigation/previous');
                }

                if ($next = $model->GetNOPEntry($entry['id'], 'next')) {
                    $navtpl->SetBlock('entry-navigation/next');
                    $navtpl->SetVariable('url', $this->GetURLFor('SingleView',
                                                                   array('id' => empty($next['fast_url']) ?
                                                                         $next['id'] : $next['fast_url'])));
                    $navtpl->SetVariable('title', (strlen($next['title']) > 50 ? substr($next['title'], 0, 50).'...' : $next['title']));
                    $navtpl->ParseBlock('entry-navigation/next');
                }
                $navtpl->ParseBlock('entry-navigation');
                $tpl->SetVariable('navigation', $navtpl->Get());
            }

            $tpl->ParseBlock('single-view');
            return $tpl->Get();
        } else {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        }

    }

    /**
     * Display comment threads of a blog post.
     *
     * @category 	feature
     * @access       public
     * @param        int 	$id 	Post ID
     * @param        int 	$parent 	Parent comment ID
     * @param        int 	$level 	Deep level on thread
     * @param        int 	$thread 	1 to show full thread
     * @param        int 	$reply_link 	1 to show reply-to link
     * @param        array 	$data 	Array with comments data if null it's loaded from model.
     * @return  string  Template content
     */
    function ShowComments($id, $parent, $level, $thread, $reply_link, $data = null)
    {
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('Comment.html');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        if (is_null($data)) {
            $comments = $model->GetComments($id, null);
        } else {
            $comments = $data;
        }

        if (!Jaws_Error::IsError($comments)) {
            $date = $GLOBALS['app']->loadDate();
            $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            foreach ($comments as $c) {
                $tpl->SetBlock('comment');
                $tpl->SetVariable('id', $c['id']);
                $tpl->SetVariable('num', $c['num']);
                $tpl->SetVariable('parent_id', $c['gadget_reference']);
                $tpl->SetVariable('name', $xss->filter($c['name']));
                $email = $xss->filter($c['email']);

                $GLOBALS['app']->Registry->LoadFile('Policy');
                $_obfuscator = $GLOBALS['app']->Registry->Get('/gadgets/Policy/obfuscator');
                if (($_obfuscator != 'DISABLED') && (!empty($email))){
                    require_once JAWS_PATH . 'gadgets/Policy/obfuscators/' . $_obfuscator . '.php';
                    $obf = new $_obfuscator();
                    $tpl->SetVariable('email', $obf->Get($email, _t('GLOBAL_EMAIL')));
                } elseif (empty($email)) {
                    $tpl->SetVariable('email', '');
                } else {
                    $tpl->SetVariable('email', '<a href="mailto:' . $email . '">' . _t('GLOBAL_EMAIL') . '</a>');
                }
                $tpl->SetVariable('url', $xss->filter($c['url']));
                $tpl->SetVariable('ip_address', '127.0.0.1');
                $tpl->SetVariable('avatar_source', $c['avatar_source']);
                $tpl->SetVariable('title', $xss->filter($c['title']));
                $tpl->SetVariable('replies', $c['replies']);
                $tpl->SetVariable('commentname', 'comment'.$c['id']);
                $commentsText = $this->ParseText($c['msg_txt']);
                $tpl->SetVariable('comments', $commentsText);
                $tpl->SetVariable('createtime-iso',       $c['createtime']);
                $tpl->SetVariable('createtime',           $date->Format($c['createtime']));
                $tpl->SetVariable('createtime-monthname', $date->Format($c['createtime'], 'MN'));
                $tpl->SetVariable('createtime-monthabbr', $date->Format($c['createtime'], 'M'));
                $tpl->SetVariable('createtime-month',     $date->Format($c['createtime'], 'm'));
                $tpl->SetVariable('createtime-dayname',   $date->Format($c['createtime'], 'DN'));
                $tpl->SetVariable('createtime-dayabbr',   $date->Format($c['createtime'], 'D'));
                $tpl->SetVariable('createtime-day',       $date->Format($c['createtime'], 'd'));
                $tpl->SetVariable('createtime-year',      $date->Format($c['createtime'], 'Y'));
                $tpl->SetVariable('createtime-time',      $date->Format($c['createtime'], 'g:ia'));

                if ($c['status'] == 'spam') {
                    $tpl->SetVariable('status_message', _t('BLOG_COMMENT_IS_SPAM'));
                } elseif ($c['status'] == 'waiting') {
                    $tpl->SetVariable('status_message', _t('BLOG_COMMENT_IS_WAITING'));
                } else {
                    $tpl->SetVariable('status_message', '&nbsp;');
                }
                $tpl->SetVariable('level', $level);

                $tpl->SetBlock('comment/reply-link');
                $tpl->SetVariablesArray($c);
                if ($reply_link) {
                    $tpl->SetVariable('reply-link', '<a href="'.
                                                    $this->GetURLFor('Reply', array('id' => $c['gadget_reference'],
                                                                                    'comment_id' => $c['id'], )).'">'.
                                                    _t('BLOG_REPLY').'</a>');
                } else {
                    $tpl->SetVariable('reply-link', _t('BLOG_REPLY'));
                }
                $tpl->ParseBlock('comment/reply-link');

                if (count($c['childs']) > 0) {
                    $tpl->SetBlock('comment/thread');
                    $tpl->SetVariable('thread', $this->ShowComments($id, $c['id'], $level + 1, $thread, $reply_link, $c['childs']));
                    $tpl->ParseBlock('comment/thread');
                }
                $tpl->ParseBlock('comment');
            }
        }

        return $tpl->Get();
    }

    /**
     * Displays a given blog comment
     *
     * @access       public
     * @param        int 	$id 	Comment ID
     * @return  string  Template content
     */
    function ShowSingleComment($id)
    {
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('Comment.html');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $comment = $model->GetComment($id);
        if (!Jaws_Error::IsError($comment)) {
            $date = $GLOBALS['app']->loadDate();
            $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            $tpl->SetBlock('comment');
            $tpl->SetVariable('id', $comment['id']);
            $tpl->SetVariable('parent_id', $comment['gadget_reference']);
            $tpl->SetVariable('name',  $xss->filter($comment['name']));
            $tpl->SetVariable('email', $xss->filter($comment['email']));
            $tpl->SetVariable('url',   $xss->filter($comment['url']));
            $tpl->SetVariable('title', $xss->filter($comment['title']));
            $tpl->SetVariable('ip_address', '127.0.0.1');
            $tpl->SetVariable('status_message', '&nbsp;');
            $tpl->SetVariable('avatar_source', $comment['avatar_source']);
            $tpl->SetVariable('replies', $comment['replies']);
            $tpl->SetVariable('commentname', 'comment' . $comment['id']);
            $commentsText = $this->ParseText($comment['msg_txt']);
            $tpl->SetVariable('comments', $commentsText);
            $tpl->SetVariable('createtime-iso',       $comment['createtime']);
            $tpl->SetVariable('createtime',           $date->Format($comment['createtime']));
            $tpl->SetVariable('createtime-monthname', $date->Format($comment['createtime'], 'MN'));
            $tpl->SetVariable('createtime-monthabbr', $date->Format($comment['createtime'], 'M'));
            $tpl->SetVariable('createtime-month',     $date->Format($comment['createtime'], 'm'));
            $tpl->SetVariable('createtime-dayname',   $date->Format($comment['createtime'], 'DN'));
            $tpl->SetVariable('createtime-dayabbr',   $date->Format($comment['createtime'], 'D'));
            $tpl->SetVariable('createtime-day',       $date->Format($comment['createtime'], 'd'));
            $tpl->SetVariable('createtime-year',      $date->Format($comment['createtime'], 'Y'));
            $tpl->SetVariable('createtime-time',      $date->Format($comment['createtime'], 'g:ia'));
            $tpl->SetVariable('level', 0);
            $tpl->ParseBlock('comment');
        }

        return $tpl->Get();
    }

    /**
     * Displays a given blog comments and a form for replying
     *
     * @access       public
     * @return  string  Template content
     */
    function Reply()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'comment_id'), 'get');
        return $this->SingleView((int)$post['id'], false, (int)$post['comment_id']);
    }

    /**
     * Displays a form to send a comment to the blog
     *
     * @param        int 	$parent_id 	ID of the replied item(immediately before on the thread)
     * @param        int 	$parent 	ID of the replied entry(comment thread starter)
     * @param        string	 $title 	Title of the comment
     * @param        string 	$comments 	Body of the comment(optional, empty by default)
     * @access       public
     * @return  string  Template content
     */
    function DisplayCommentForm($parent_id, $parent = 0, $title = '', $comments = '')
    {
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('CommentForm.html');
        $tpl->SetBlock('commentform');

        $post = $GLOBALS['app']->Session->PopSimpleResponse('Blog_Comment');

        if (!$GLOBALS['app']->Session->Logged()) {
            $tpl->SetBlock('commentform/unregistered');
            // Get person info from cookie or post...
            if (!is_null($post['name'])) {
                $visitorName = $post['name'];
            } elseif (Jaws_Session_Web::GetCookie('visitor_name')) {
                $visitorName = Jaws_Session_Web::GetCookie('visitor_name');
            } else {
                $visitorName = '';
            }

            if (!is_null($post['email'])) {
                $visitorEmail = $post['email'];
            } elseif (Jaws_Session_Web::GetCookie('visitor_email')) {
                $visitorEmail = Jaws_Session_Web::GetCookie('visitor_email');
            } else {
                $visitorEmail = '';
            }

            if (!is_null($post['url'])) {
                $visitorUrl = $post['url'];
            } elseif (Jaws_Session_Web::GetCookie('visitor_url')) {
                $visitorUrl = Jaws_Session_Web::GetCookie('visitor_url');
            } else {
                $visitorUrl = 'http://';
            }

            $tpl->SetVariable('name', _t('GLOBAL_NAME'));
            $tpl->SetVariable('name_value', $visitorName);
            $tpl->SetVariable('email', _t('GLOBAL_EMAIL'));
            $tpl->SetVariable('email_value', $visitorEmail);
            $tpl->SetVariable('url',  _t('GLOBAL_URL'));
            $tpl->SetVariable('url_value', $visitorUrl);
            $tpl->ParseBlock('commentform/unregistered');

            $GLOBALS['app']->Registry->LoadFile('Policy');
            $_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
            if ($_captcha != 'DISABLED') {
                require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
                $captcha = new $_captcha();
                $captchaRes = $captcha->Get();
                $tpl->SetBlock('commentform/captcha');
                $tpl->SetVariable('lbl_captcha', _t('GLOBAL_CAPTCHA_CODE'));
                $tpl->SetVariable('captcha', $captchaRes['captcha']->Get());
                if (!empty($captchaRes['entry'])) {
                    $tpl->SetVariable('captchavalue', $captchaRes['entry']->Get());
                }
                $tpl->SetVariable('captcha_msg', _t('GLOBAL_CAPTCHA_CODE_DESC'));
                $tpl->ParseBlock('commentform/captcha');
            }
        }

        if (!is_null($post['title'])) {
            $title = $post['title'];
        }

        if (!is_null($post['comments'])) {
            $comments = $post['comments'];
        }

        if (!is_null($post['parent'])) {
            $parent = $post['parent'];
        }

        $tpl->SetVariable('title', _t('BLOG_LEAVE_COMMENT'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('parent_id',   $parent_id);
        $tpl->SetVariable('parent', $parent);
        $tpl->SetVariable('gadget', 'Blog');
        $tpl->SetVariable('action', 'SaveComment');

        // Test to see if this does any good to reduce spam
        $tpl->SetVariable('url2', _t('GLOBAL_SPAMCHECK_EMPTY'));
        $tpl->SetVariable('url2_value',  '');
        $tpl->SetVariable('comment_title', _t('GLOBAL_TITLE'));
        $tpl->SetVariable('title_value', $title);
        $tpl->SetVariable('comments', _t('BLOG_COMMENT'));
        $tpl->SetVariable('comments_value', $comments);

        $tpl->SetVariable('lbl_feeds', _t('BLOG_COMMENTS_XML'));
        $tpl->SetVariable('atom_url', $this->GetURLFor('CommentsAtom', array('id' => $parent_id)));
        $tpl->SetVariable('rss_url',  $this->GetURLFor('CommentsRSS',  array('id' => $parent_id)));

        $tpl->SetVariable('send',    _t('BLOG_SUBMIT_COMMENT'));
        $tpl->SetVariable('preview',    _t('GLOBAL_PREVIEW'));

        /*
        if ($GLOBALS['app']->Registry->Get('/network/mailer') !== 'DISABLED') {
            $tpl->SetBlock('commentform/mail_me');
            $tpl->SetVariable('mail_me', _t('BLOG_MAIL_COMMENT_TO_ME'));
            $tpl->ParseBlock('commentform/mail_me');
        }
        */

        if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Blog')) {
            $tpl->SetBlock('commentform/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('commentform/response');
        }

        $tpl->ParseBlock('commentform');

        return $tpl->Get();
    }

    /**
     * Displays a preview of the given blog comment
     *
     * @access       public
     * @return  string  Template content
     */
    function Preview()
    {
        require_once JAWS_PATH . 'include/Jaws/Header.php';
        $request =& Jaws_Request::getInstance();
        $names = array(
            'name', 'email', 'url', 'title', 'comments', 'createtime',
            'ip_address', 'parent_id', 'parent'
        );
        $post = $request->get($names, 'post');
        $id   = (int)$post['parent_id'];
        $GLOBALS['app']->Session->PushSimpleResponse($post, 'Blog_Comment');

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $entry = $model->GetEntry($id, true);
        if (Jaws_Error::isError($entry)) {
            $GLOBALS['app']->Session->PushSimpleResponse($entry->getMessage(), 'Blog');
            $url = $this->GetURLFor('DefaultAction');
            Jaws_Header::Location($url);
        }

        $id = !empty($entry['fast_url']) ? $entry['fast_url'] : $entry['id'];
        $url = $this->GetURLFor('SingleView', array('id' => $id), true, 'site_url');

        return $this->SingleView($id, true);
    }

    /**
     * Displays a preview of the given blog comment
     *
     * @access       public
     * @return  string  Template content
     */
    function ShowPreview()
    {
        $post = $GLOBALS['app']->Session->PopSimpleResponse('Blog_Comment', false);
        if ($GLOBALS['app']->Session->Logged()) {
            $post['name']  = $GLOBALS['app']->Session->GetAttribute('nickname');
            $post['email'] = $GLOBALS['app']->Session->GetAttribute('email');
            $post['url']   = $GLOBALS['app']->Session->GetAttribute('url');
        }

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('Comment.html');
        $tpl->SetBlock('comment');
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $tpl->SetVariable('name',  $xss->filter($post['name']));
        $tpl->SetVariable('email', $xss->filter($post['email']));
        $tpl->SetVariable('url',   $xss->filter($post['url']));
        if (is_null($post['ip_address'])) {
            $post['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }
        $tpl->SetVariable('title', $xss->filter($post['title']));
        $tpl->SetVariable('comments', $this->ParseText($post['comments']));
        if (!isset($post['createtime'])) {
            $date = $GLOBALS['app']->loadDate();
            $post['createtime'] = $date->Format(time());
        }
        $tpl->SetVariable('createtime', $post['createtime']);
        $tpl->SetVariable('level', 0);
        $tpl->SetVariable('status_message', '&nbsp;');
        $tpl->SetVariable('ip_address', $post['ip_address']);
        $tpl->SetVariable('avatar_source', 'images/unknown.png');
        $tpl->SetVariable('replies', '0');
        $tpl->SetVariable('commentname', 'comment_preview');

        $tpl->ParseBlock('comment');
        return $tpl->Get();
    }

    /**
     * Saves the given blog comment
     *
     * @access       public
     * @return  string  Template content
     */
    function SaveComment()
    {
        require_once JAWS_PATH . 'include/Jaws/Header.php';
        $request =& Jaws_Request::getInstance();
        $names = array(
            'name', 'email', 'url', 'title', 'comments', 'createtime',
            'ip_address', 'parent_id', 'parent', 'captcha', 'captchaKey', 'url2'
        );
        $post = $request->get($names, 'post');
        $id  = (int)$post['parent_id'];

        if ($GLOBALS['app']->Session->Logged()) {
            $post['name']  = $GLOBALS['app']->Session->GetAttribute('nickname');
            $post['email'] = $GLOBALS['app']->Session->GetAttribute('email');
            $post['url']   = $GLOBALS['app']->Session->GetAttribute('url');
        }

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $entry = $model->GetEntry($id, true);
        if (Jaws_Error::isError($entry)) {
            $GLOBALS['app']->Session->PushSimpleResponse($entry->getMessage(), 'Blog');
            $url = $this->GetURLFor('DefaultAction');
            Jaws_Header::Location($url);
        }

        $id = !empty($entry['fast_url']) ? $entry['fast_url'] : $entry['id'];
        $url = $this->GetURLFor('SingleView', array('id' => $id), true, 'site_url');

        $allow_comments_config = $GLOBALS['app']->Registry->Get('/config/allow_comments');
        $restricted = $allow_comments_config == 'restricted';
        $allow_comments_config = $restricted? $GLOBALS['app']->Session->Logged() : ($allow_comments_config == 'true');

        // Check if comments are allowed.
        if ($entry['allow_comments'] !== true ||
            $GLOBALS['app']->Registry->Get('/gadgets/Blog/allow_comments') != 'true' ||
            !$allow_comments_config)
        {
            Jaws_Header::Location($url);
        }

        /* lets check if it's spam
         * it's rather common that spam engines
         * fill out all inputs and this one is hidden
         * via CSS so not many engines are smart enough
         * to not fill this out
         */
        if (!empty($post['url2'])) {
            Jaws_Header::Location($url);
        }

        if (trim($post['name']) == '' || trim($post['title']) == '' || trim($post['comments']) == '') {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('GLOBAL_ERROR_INCOMPLETE_FIELDS'), 'Blog');
            $GLOBALS['app']->Session->PushSimpleResponse($post, 'Blog_Comment');
            Jaws_Header::Location($url);
        }

        if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Registry->LoadFile('Policy');
            $_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
            if ($_captcha != 'DISABLED') {
                require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
                $captcha = new $_captcha();
                if (!$captcha->Check()) {
                    $GLOBALS['app']->Session->PushSimpleResponse(_t('GLOBAL_CAPTCHA_ERROR_DOES_NOT_MATCH'), 'Blog');
                    Jaws_Header::Location($url);
                }
            }
        }

        $result = $model->NewComment($post['name'], $post['title'], $post['url'],
                           $post['email'], $post['comments'], $post['parent'],
                           $post['parent_id']);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushSimpleResponse($result->getMessage(), 'Blog');
        } else {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('GLOBAL_MESSAGE_SENT'), 'Blog');
        }

        Jaws_Header::Location($url);
    }

    /**
     * Displays a list of blog posts ordered by date
     *
     * @access       public
     * @return  string  Template content
     */
    function Archive()
    {
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('Archive.html');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $archiveEntries = $model->GetEntriesAsArchive();
        $auxMonth = '';
        $this->SetTitle(_t('BLOG_ARCHIVE'));
        $tpl->SetBlock('archive');
        $tpl->SetVariable('title', _t('BLOG_ARCHIVE'));
        if (!Jaws_Error::IsError($archiveEntries)) {
            $date = $GLOBALS['app']->loadDate();
            foreach ($archiveEntries as $entry) {
                $currentMonth = $date->Format($entry['publishtime'], 'MN');
                if ($currentMonth != $auxMonth) {
                    if ($auxMonth != '') {
                        $tpl->ParseBlock('archive/month');
                    }
                    $tpl->SetBlock('archive/month');
                    $year = $date->Format($entry['publishtime'], 'Y');
                    $tpl->SetVariable('month', $currentMonth);
                    $tpl->SetVariable('year', $year);
                    $auxMonth = $currentMonth;
                }
                $tpl->SetBlock('archive/month/record');
                $tpl->SetVariable('id', $entry['id']);
                $tpl->SetVariable('date',           $date->Format($entry['publishtime']));
                $tpl->SetVariable('date-monthname', $currentMonth);
                $tpl->SetVariable('date-month',     $date->Format($entry['publishtime'], 'm'));
                $tpl->SetVariable('date-day',       $date->Format($entry['publishtime'], 'd'));
                $tpl->SetVariable('date-year',      $year);
                $tpl->SetVariable('date-time',      $date->Format($entry['publishtime'], 'g:ia'));
                $tpl->SetVariable('title', $entry['title']);
                $tpl->SetVariable('comments', $entry['comments']);

                $id = !empty($entry['fast_url']) ? $entry['fast_url'] : $entry['id'];
                $url = $GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $id));
                $tpl->SetVariable('view-link', $url);
                $tpl->ParseBlock('archive/month/record');
            }
            $tpl->ParseBlock('archive/month');
        }
        $tpl->ParseBlock('archive');

        return $tpl->Get('archive');
    }

    /**
     * RSS feeds for the blog.
     *
     * @category 	feature
     * @access       public
     * @param        boolean $save true to save RSS, false to display
     * @return       xml with RSS feed on display mode, nothing otherwise
     */
    function RSS($save = false)
    {
        header('Content-type: application/rss+xml');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $rss = $model->MakeRSS($save);
        if (Jaws_Error::IsError($rss) && !$save) {
            return '';
        }

        return $rss;
    }

    /**
     * Atom feeds for the blog.
     *
     * @category 	feature
     * @access       public
     * @param        boolean $save true to save Atom, false to display
     * @return       xml with Atom feed on display mode, nothing otherwise
     */
    function Atom($save = false)
    {
        header('Content-type: application/atom+xml');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $atom = $model->MakeAtom($save);
        if (Jaws_Error::IsError($atom) && !$save) {
            return '';
        }

        return $atom;
    }

    /**
     * Displays a RSS feed for a given blog category
     *
     * @access       public
     * @return       xml with RSS feed
     */
    function ShowRSSCategory()
    {
        header('Content-type: application/rss+xml');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');

        $request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $id = $xss->defilter($id, true);

        $xml = $model->MakeCategoryRSS($id);
        if (Jaws_Error::IsError($xml)) {
            return '';
        }

        return $xml;
    }

    /**
     * Displays an Atom feed for a given blog category
     *
     * @access       public
     * @return       xml with Atom feed
     */
    function ShowAtomCategory()
    {
        header('Content-type: application/atom+xml');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');

        $request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $id = $xss->defilter($id, true);

        $xml = $model->MakeCategoryAtom($id);
        if (Jaws_Error::IsError($xml)) {
            return '';
        }

        return $xml;
    }

    /**
     * Atom feeds for blog most recent comments.
     *
     * @access       public
     * @return       xml with Atom feed
     */
    function RecentCommentsAtom()
    {
        header('Content-type: application/atom+xml');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $xml = $model->GetRecentCommentsAtom();
        if (Jaws_Error::IsError($xml)) {
            return '';
        }

        return $xml;
    }

    /**
     * RSS feeds for blog most recent comments.
     *
     * @access       public
     * @return       xml with RSS feed
     */
    function RecentCommentsRSS()
    {
        header('Content-type: application/rss+xml');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $xml = $model->GetRecentCommentsRSS();
        if (Jaws_Error::IsError($xml)) {
            return '';
        }

        return $xml;
    }

    /**
     * Displays an Atom feed for most recent comments on the given blog entry
     *
     * @access       public
     * @return       xml with Atom feed
     */
    function CommentsAtom()
    {
        header('Content-type: application/atom+xml');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');

        $request =& Jaws_Request::getInstance();
        $id = (int)$request->get('id', 'get');

        $xml = $model->GetPostCommentsAtom($id);
        if (Jaws_Error::IsError($xml)) {
            return '';
        }

        return $xml;
    }

    /**
     * Displays a RSS feed for most recent comments on the given blog entry
     *
     * @access       public
     * @return       xml with RSS feed
     */
    function CommentsRSS()
    {
        header('Content-type: application/rss+xml');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');

        $request =& Jaws_Request::getInstance();
        $id = (int)$request->get('id', 'get');

        $xml = $model->GetPostCommentsRSS($id);
        if (Jaws_Error::IsError($xml)) {
            return '';
        }

        return $xml;
    }

    /**
     * Display blog posts by category.
     *
     * @category 	feature
     * @access       public
     * @return  string  Template content
     */
    function ShowCategory($cat = '')
    {
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'page'), 'get');

        $page = $post['page'];
        if (is_null($page) || $page <= 0 ) {
            $page = 1;
        }

        if (empty($cat)) {
            $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            $cat = $xss->defilter($post['id'], true);
        }

        $catInfo = $model->GetCategory($cat);
        if (!Jaws_Error::IsError($catInfo) && isset($catInfo['id'])) {
            $name = $catInfo['name'];
            $tpl = new Jaws_Template('gadgets/Blog/templates/');
            $tpl->Load('ViewCategory.html', true);

            $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog',
                                                                                 'ShowAtomCategory',
                                                                                 array('id' => $cat)),
                                                 'alternate',
                                                 'application/atom+xml',
                                                 'Atom - '.$name);
            $GLOBALS['app']->Layout->AddHeadLink($GLOBALS['app']->Map->GetURLFor('Blog',
                                                                                 'ShowRSSCategory',
                                                                                 array('id' => $cat)),
                                                 'alternate',
                                                 'application/rss+xml',
                                                 'RSS 2.0 - '.$name);

            $this->SetTitle($name);
            $tpl->SetBlock('view_category');
            $tpl->SetVariable('title', $name);

            $total  = $model->GetCategoryNumberOfPages($catInfo['id']);
            $limit  = $GLOBALS['app']->Registry->Get('/gadgets/Blog/last_entries_limit');
            $params = array('id'  => $cat);
            $tpl->SetVariable('navigation',
                              $this->GetNumberedPageNavigation($page, $limit, $total, 'ShowCategory', $params));
            $entries = $model->GetEntriesByCategory($catInfo['id'], $page);
            if (!Jaws_Error::IsError($entries)) {
                $res = '';
                $tpl->SetBlock('view_category/entry');
                $tplEntry = $tpl->GetRawBlockContent();
                foreach ($entries as $entry) {
                    $res .= $this->ShowEntry($entry, true, true, $tplEntry);
                }
                $tpl->SetCurrentBlockContent($res);
                $tpl->ParseBlock('view_category/entry');
            }

            $tpl->ParseBlock('view_category');
            return $tpl->Get();
        } else {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        }
    }

    /**
     * Displays a list of blog categories with a link to each one's posts
     *
     * @access       public
     * @return  string  Template content
     */
    function CategoriesList()
    {
        $this->SetTitle(_t('BLOG_CATEGORIES'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('Blog', 'LayoutHTML');
        return $layoutGadget->CategoriesList();
    }

    /**
     * Accept trackbacks.
     *
     * @category 	feature
     * @access       public
     * @return       trackback xml response
     */
    function Trackback()
    {
        // Based on Wordpress trackback implementation
        $tb_msg_error = '<?xml version="1.0" encoding="iso-8859-1"?><response><error>1</error><message>#MESSAGE#</message></response>';
        $tb_msg_ok = '<?xml version="1.0" encoding="iso-8859-1"?><response><error>0</error></response>';

        $sender = Jaws_Utils::GetRemoteAddress();
        $ip = $sender['proxy'] . (!empty($sender['proxy'])? '-' : '') . $sender['client'];

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('title', 'url', 'blog_name', 'excerpt'), 'post');

        if (is_null($post['title']) || is_null($post['url']) ||
            is_null($post['blog_name']) || is_null($post['excerpt'])) {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location('');
        }

        $request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');

        if (is_null($id)) {
            $id = $request->get('id', 'post');
            if (is_null($id)) {
                $id = '';
            }
        }

        $title    = urldecode($post['title']);
        $url      = urldecode($post['url']);
        $blogname = urldecode($post['blog_name']);
        $excerpt  = urldecode($post['excerpt']);

        if (trim($id) == '') {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location('');
        } elseif (empty($title) && empty($url) && empty($blogname)) {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            $url = $this->GetURLFor('SingleView', array('id' => $id), true, 'site_url');
            Jaws_Header::Location($url);
        } elseif ($GLOBALS['app']->Registry->Get('/gadgets/Blog/trackback') == 'true') {
            header('Content-Type: text/xml');
            $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
            $trackback = $model->NewTrackback($id, $url, $title, $excerpt, $blogname, $ip);
            if (Jaws_Error::IsError($trackback)) {
                return str_replace('#MESSAGE#', $trackback->GetMessage(), $tb_msg_error);
            }
            return $tb_msg_ok;
        } else {
            header('Content-Type: text/xml');
            return str_replace('#MESSAGE#', _t('BLOG_TRACKBACK_DISABLED'), $tb_msg_error);
        }
    }

    /**
     * Shows existing trackbacks for a given entry
     *
     * @access       public
     * @param        int $id entry id
     * @return  string  Template content
     */
    function ShowTrackbacks($id)
    {
        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/trackback') == 'true') {
            $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
            $trackbacks = $model->GetTrackbacks($id);
            $tpl = new Jaws_Template('gadgets/Blog/templates/');
            $tpl->Load('Trackbacks.html');
            $tpl->SetBlock('trackbacks');
            $tburi = $this->GetURLFor('Trackback', array('id' => $id), false, 'site_url');
            $tpl->SetVariable('TrackbackURI', $tburi);
            if (!Jaws_Error::IsError($trackbacks)) {
                $date = $GLOBALS['app']->loadDate();
                foreach ($trackbacks as $tb) {
                    $tpl->SetBlock('trackbacks/item');
                    $tpl->SetVariablesArray($tb);
                    $tpl->SetVariable('createtime-iso',       $tb['createtime']);
                    $tpl->SetVariable('createtime',           $date->Format($tb['createtime']));
                    $tpl->SetVariable('createtime-monthname', $date->Format($tb['createtime'], 'MN'));
                    $tpl->SetVariable('createtime-monthabbr', $date->Format($tb['createtime'], 'M'));
                    $tpl->SetVariable('createtime-month',     $date->Format($tb['createtime'], 'm'));
                    $tpl->SetVariable('createtime-dayname',   $date->Format($tb['createtime'], 'DN'));
                    $tpl->SetVariable('createtime-dayabbr',   $date->Format($tb['createtime'], 'D'));
                    $tpl->SetVariable('createtime-day',       $date->Format($tb['createtime'], 'd'));
                    $tpl->SetVariable('createtime-year',      $date->Format($tb['createtime'], 'Y'));
                    $tpl->SetVariable('createtime-time',      $date->Format($tb['createtime'], 'g:ia'));
                    $tpl->ParseBlock('trackbacks/item');
                }
            }
            $tpl->ParseBlock('trackbacks');

            return $tpl->Get();
        }
    }

    /**
     * Generate captcha image
     *
     * @access  public
     * @return  binary  An image
     */
    function Captcha()
    {
        if (
            $GLOBALS['app']->Registry->Get('/gadgets/Policy/allow_captchas') == 'yes' &&
            $GLOBALS['app']->Registry->Get('/gadgets/Blog/use_antispam') == 'true'
        ) {
            $request =& Jaws_Request::getInstance();
            $key = $request->get('key', 'get');

            require_once JAWS_PATH . 'include/Jaws/Captcha.php';
            $md5 = !is_null($key) ? $key : '';
            $jawsCaptchas =& JawsCaptcha::factory('Image');
            $image = $jawsCaptchas->Build($md5);

            return $image;
        }

        return '';
    }

    /**
     * Accept pingbacks.
     *
     * @category 	feature
     * @access  public
     * @return  void
     */
    function Pingback()
    {
        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/pingback') == 'true') {
            require_once JAWS_PATH . 'include/Jaws/Pingback.php';
            $pback =& Jaws_PingBack::getInstance();
            $response = $pback->listen();
            if (is_array($response)) {
                //Load model
                $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');

                //We need to parse the target URI to get the post ID
                $GLOBALS['app']->Map->Parse($response['targetURI']);

                $request =& Jaws_Request::getInstance();
                //pingbacks come from POST but JawsURL maps everything on get (that how Maps work)
                $postID = $request->get('id', 'get');
                if (empty($postID)) {
                    return;
                }

                $entry  = $model->GetEntry($postID, true);
                if (!Jaws_Error::IsError($entry)) {
                    $title   = '';
                    $content = '';

                    $response['title'] = strip_tags($response['title']);

                    if (empty($response['title'])) {
                        if (empty($entry['title'])) {
                            $title = _t('GLOBAL_RE')._t('BLOG_PINGBACK_TITLE', $entry['title']);
                            $content = _t('BLOG_PINGBACK_DEFAULT_COMMENT', $entry['sourceURI']);
                        }
                    } else {
                        $comesFrom = '<a href="'.$response['sourceURI'].'">'.$response['title'].'</a>';
                        $content = _t('BLOG_PINGBACK_COMMENT', $comesFrom);
                        $title = _t('GLOBAL_RE')._t('BLOG_PINGBACK_TITLE', $response['title']);
                    }
                    $model->SavePingback($postID, $response['sourceURI'], $response['targetURI'], $title, $content);
                }
            }
        }
    }

    /**
     * Display blog posts by popularity.
     *
     * @category 	feature
     * @access       public
     * @return  string  Template content
     */
    function PopularPosts()
    {
        $this->SetTitle(_t('BLOG_POPULAR_POSTS'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('Blog', 'LayoutHTML');
        return $layoutGadget->PopularPosts();
    }

    /**
     * Displays a list of posts authors
     *
     * @access       public
     * @return  string  Template content
     */
    function PostsAuthors()
    {
        $this->SetTitle(_t('BLOG_POSTS_AUTHORS'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('Blog', 'LayoutHTML');
        return $layoutGadget->PostsAuthors();
    }

}
