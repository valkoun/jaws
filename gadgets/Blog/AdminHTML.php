<?php
/**
 * Blog Admin HTML file
 *
 * @category   GadgetAdmin
 * @package    Blog
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class BlogAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function BlogAdminHTML()
    {
        $this->Init('Blog');
    }

    /**
     * Calls default admin action(NewEntry)
     *
     * @access       public
     * @return       template content
     */
    function Admin()
    {
        $this->CheckPermission('default');
        return $this->NewEntry();
    }

    /**
     * Displays admin menu bar according to selected action
     *
     * @access       public
     * @param        string $action_selected selected action
     * @return       template content
     */
    function MenuBar($action_selected)
    {
        $actions = array('Summary', 'NewEntry', 'ListEntries',
                         'ManageComments', 'ManageTrackbacks',
                         'ManageCategories', 'AdditionalSettings');
        if (!in_array($action_selected, $actions)) {
            $action_selected = 'ListEntries';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        $menubar->AddOption('Summary',_t('BLOG_SUMMARY'),
                                BASE_SCRIPT . '?gadget=Blog&amp;action=Summary', 'images/stock/new.png');
        if ($this->GetPermission('AddEntries')) {
            $menubar->AddOption('NewEntry', _t('BLOG_NEW_ENTRY'),
                                BASE_SCRIPT . '?gadget=Blog&amp;action=NewEntry', 'images/stock/new.png');
        }
        $menubar->AddOption('ListEntries', _t('BLOG_LIST_ENTRIES'),
                            BASE_SCRIPT . '?gadget=Blog&amp;action=ListEntries', 'images/stock/edit.png');
        if ($this->GetPermission('ManageComments')) {
            $menubar->AddOption('ManageComments', _t('BLOG_MANAGE_COMMENTS'),
                                BASE_SCRIPT . '?gadget=Blog&amp;action=ManageComments', 'images/stock/stock-comments.png');
        }
        if ($this->GetPermission('ManageTrackbacks')) {
            $menubar->AddOption('ManageTrackbacks', _t('BLOG_MANAGE_TRACKBACKS'),
                                BASE_SCRIPT . '?gadget=Blog&amp;action=ManageTrackbacks', 'images/stock/stock-comments.png');
        }
        if ($this->GetPermission('ManageCategories')) {
            $menubar->AddOption('ManageCategories', _t('BLOG_CATEGORIES'),
                                BASE_SCRIPT . '?gadget=Blog&amp;action=ManageCategories', 'images/stock/edit.png');
        }
        if ($this->GetPermission('Settings')) {
            $menubar->AddOption('AdditionalSettings', _t('BLOG_SETTINGS'),
                                BASE_SCRIPT . '?gadget=Blog&amp;action=AdditionalSettings', 'images/stock/properties.png');
        }
        $menubar->Activate($action_selected);

        return $menubar->Get();
    }

    /**
     * Displays blog summary with some statistics
     *
     * @access       public
     * @return       template content
     */
    function Summary()
    {
        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $summary = $model->GetSummary();
        if (Jaws_Error::IsError($summary)) {
            $summary = array();
        }

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('Summary.html');
        $tpl->SetBlock('summary');
        $tpl->SetVariable('menubar', $this->MenuBar('Summary'));

        // Ok, start the stats!
        $tpl->SetVariable('blog_stats', _t('BLOG_STATS'));
        // First entry

        $tpl->SetBlock('summary/item');
        $bg = Jaws_Utils::RowColor(null);
        $tpl->SetVariable('bgcolor', $bg);
        $tpl->SetVariable('label', _t('BLOG_FIRST_ENTRY'));
        if (isset($summary['min_date'])) {
            $date = $GLOBALS['app']->loadDate();
            $tpl->SetVariable('value', $date->Format($summary['min_date']));
        } else {
            $tpl->SetVariable('value', '');
        }
        $tpl->ParseBlock('summary/item');

        // Last entry
        $tpl->SetBlock('summary/item');
        $bg = Jaws_Utils::RowColor($bg);
        $tpl->SetVariable('bgcolor', $bg);
        $tpl->SetVariable('label', _t('BLOG_LAST_ENTRY'));
        if (isset($summary['max_date'])) {
            $date = $GLOBALS['app']->loadDate();
            $tpl->SetVariable('value', $date->Format($summary['max_date']));
        } else {
            $tpl->SetVariable('value', '');
        }
        $tpl->ParseBlock('summary/item');


        // Blog entries
        $tpl->SetBlock('summary/item');
        $bg = Jaws_Utils::RowColor($bg);
        $tpl->SetVariable('bgcolor', $bg);
        $tpl->SetVariable('label', _t('BLOG_TOTAL_ENTRIES'));
        $tpl->SetVariable('value', isset($summary['qty_posts']) ? $summary['qty_posts'] : '');
        $tpl->ParseBlock('summary/item');

        // Avg. entries per week
        $tpl->SetBlock('summary/item');
        $bg = Jaws_Utils::RowColor($bg);
        $tpl->SetVariable('bgcolor', $bg);
        $tpl->SetVariable('label', _t('BLOG_AVERAGE_ENTRIES'));
        $tpl->SetVariable('value', isset($summary['AvgEntriesPerWeek']) ? $summary['AvgEntriesPerWeek'] : '');
        $tpl->ParseBlock('summary/item');


        // Comments
        $tpl->SetBlock('summary/item');
        $bg = Jaws_Utils::RowColor($bg);
        $tpl->SetVariable('bgcolor', $bg);
        $tpl->SetVariable('label', _t('BLOG_COMMENTS_RECEIVED'));
        $tpl->SetVariable('value', isset($summary['CommentsQty']) ? $summary['CommentsQty'] : '');
        $tpl->ParseBlock('summary/item');

        // Recent entries
        if (isset($summary['Entries']) && count($summary['Entries']) > 0) {
            $tpl->SetBlock('summary/recent');
            $tpl->SetVariable('title', _t('BLOG_RECENT_ENTRIES'));

            $date = $GLOBALS['app']->loadDate();
            foreach ($summary['Entries'] as $e) {
                $tpl->SetBlock('summary/recent/link');
                $url = BASE_SCRIPT . '?gadget=Blog&action=EditEntry&id='.$e['id'];
                if ($e['published'] === false) {
                    $extra = '<span style="color: #999; font-size: 10px;"> [' . _t('BLOG_DRAFT') . '] </span>';
                } else {
                    $extra = '';
                }
                $tpl->SetVariable('url',   $url);
                $tpl->SetVariable('title', $e['title']);
                $tpl->SetVariable('extra', $extra);
                $tpl->SetVariable('date',  $date->Format($e['publishtime']));
                $tpl->ParseBlock('summary/recent/link');
            }
            $tpl->ParseBlock('summary/recent');
        }

        // Recent comments
        if (isset($summary['Comments']) &&(count($summary['Comments']) > 0)) {
            $tpl->SetBlock('summary/recent');
            $tpl->SetVariable('title', _t('BLOG_RECENT_COMMENTS'));
            $date = $GLOBALS['app']->loadDate();
            $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            foreach ($summary['Comments'] as $c) {
                $tpl->SetBlock('summary/recent/link');
                $url = BASE_SCRIPT . '?gadget=Blog&action=EditComment&id='.$c['id'];
                $extra = "<strong style=\"color: #666;\">" . $xss->filter($c['name']) . ": </strong>";
                $tpl->SetVariable('url',   $xss->filter($url));
                $tpl->SetVariable('title', $xss->filter($c['title']));
                $tpl->SetVariable('extra', $extra);
                $tpl->SetVariable('date',  $date->Format($c['createtime']));
                $tpl->ParseBlock('summary/recent/link');
            }
            $tpl->ParseBlock('summary/recent');
        }

        $tpl->ParseBlock('summary');
        return $tpl->Get();
    }

    /**
     * Displays blog settings administration panel
     *
     * @access       public
     * @return       template content
     */
    function AdditionalSettings()
    {
        $this->CheckPermission('Settings');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('AdditionalSettings.html');
        $tpl->SetBlock('additional');

        // Header
        $tpl->SetVariable('menubar', $this->MenuBar('AdditionalSettings'));

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'POST');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Blog'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'SaveAdditionalSettings'));

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet(_t('BLOG_ADDITIONAL_SETTINGS'));
        // $fieldset =& Piwi::CreateWidget('FieldSet', _t('BLOG_ADDITIONAL_SETTINGS'));

        // Save Button
        $save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $save->AddEvent(ON_CLICK, 'javascript: updateSettings(this.form);');

        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $settings = $model->GetSettings();
        if (Jaws_Error::IsError($settings)) {
            $settings = array();
        }

        // Default View
        $tpl->SetVariable('label', _t('BLOG_DEFAULT_VIEW'));
        $viewCombo =& Piwi::CreateWidget('Combo', 'default_view');
        $viewCombo->setContainerClass('oneline');
        $viewCombo->SetTitle(_t('BLOG_DEFAULT_VIEW'));
        $viewCombo->AddOption(_t('BLOG_MONTHLY'), 'monthly');
        $viewCombo->AddOption(_t('BLOG_LATEST_ENTRY'), 'latest_entry');
        $viewCombo->AddOption(_t('BLOG_LAST_ENTRIES'), 'last_entries');
        $viewCombo->AddOption(_t('BLOG_DEFAULT_CATEGORY'), 'default_category');
        $viewCombo->SetDefault(isset($settings['default_view']) ?
                               $settings['default_view'] : '');

        // Last entries limit
        $limitCombo =& Piwi::CreateWidget('Combo', 'last_entries_limit');
        $limitCombo->setContainerClass('oneline');
        $limitCombo->SetTitle(_t('BLOG_LAST_ENTRIES_LIMIT'));
        for ($i = 5; $i <= 30; $i += 5) {
            $limitCombo->AddOption($i, $i);
        }
        $limitCombo->SetDefault(isset($settings['last_entries_limit']) ?
                                $settings['last_entries_limit'] : '');

        // Popular limit
        $popCombo =& Piwi::CreateWidget('Combo', 'popular_limit');
        $popCombo->setContainerClass('oneline');
        $popCombo->SetTitle(_t('BLOG_POPULAR_ENTRIES_LIMIT'));
        for ($i = 5; $i <= 30; $i += 5) {
            $popCombo->AddOption($i, $i);
        }
        $popCombo->SetDefault(isset($settings['popular_limit']) ?
                                $settings['popular_limit'] : '');

        // Last comments limit
        $commentslimitCombo =& Piwi::CreateWidget('Combo', 'last_comments_limit');
        $commentslimitCombo->setContainerClass('oneline');
        $commentslimitCombo->SetTitle(_t('BLOG_LAST_COMMENTS_LIMIT'));
        for ($i = 5; $i <= 30; $i += 5) {
            $commentslimitCombo->AddOption($i, $i);
        }
        $commentslimitCombo->SetDefault(isset($settings['last_comments_limit']) ?
                                        $settings['last_comments_limit'] : '');

        // Last recent comments
        $recentcommentsLimitCombo =& Piwi::CreateWidget('Combo', 'last_recentcomments_limit');
        $recentcommentsLimitCombo->setContainerClass('oneline');
        $recentcommentsLimitCombo->SetTitle(_t('BLOG_LAST_RECENTCOMMENTS_LIMIT'));
        for ($i = 5; $i <= 30; $i += 5) {
            $recentcommentsLimitCombo->AddOption($i, $i);
        }
        $recentcommentsLimitCombo->SetDefault(isset($settings['last_recentcomments_limit']) ?
                                              $settings['last_recentcomments_limit'] : '');

        $categories = $model->GetCategories();
        if (!Jaws_Error::IsError($categories)) {
            // Default category

            $catCombo =& Piwi::CreateWidget('Combo', 'default_category');
            $catCombo->setContainerClass('oneline');
            $catCombo->SetTitle(_t('BLOG_DEFAULT_CATEGORY'));
            foreach ($categories as $cat) {
                $catCombo->AddOption($cat['name'], $cat['id']);
            }
            $catCombo->SetDefault(isset($settings['default_category']) ?
                                  $settings['default_category'] : '');
        }

        // RSS/Atom limit
        $xmlCombo =& Piwi::CreateWidget('Combo', 'xml_limit');
        $xmlCombo->setContainerClass('oneline');
        $xmlCombo->SetTitle(_t('BLOG_RSS_ENTRIES_LIMIT'));
        for ($i = 5; $i <= 50; $i += 5) {
            $xmlCombo->AddOption($i, $i);
        }
        $xmlCombo->SetDefault(isset($settings['xml_limit']) ? $settings['xml_limit'] : '');

        // Comments
        $commCombo =& Piwi::CreateWidget('Combo', 'comments');
        $commCombo->setContainerClass('oneline');
        $commCombo->SetTitle(_t('BLOG_COMMENTS'));
        $commCombo->AddOption(_t('GLOBAL_ENABLED'), 'true');
        $commCombo->AddOption(_t('GLOBAL_DISABLED'), 'false');
        $commCombo->SetDefault(isset($settings['comments']) ? $settings['comments'] : '');

        // Comment status
        $commStatusCombo =& Piwi::CreateWidget('Combo', 'comment_status');
        $commStatusCombo->setContainerClass('oneline');
        $commStatusCombo->SetTitle(_t('BLOG_DEFAULT_STATUS', _t('BLOG_COMMENTS')));
        $commStatusCombo->AddOption(_t('GLOBAL_STATUS_APPROVED'), 'approved');
        $commStatusCombo->AddOption(_t('GLOBAL_STATUS_WAITING'), 'waiting');
        $commStatusCombo->SetDefault($settings['comment_status']);

        // Trackback
        $tbCombo =& Piwi::CreateWidget('Combo', 'trackback');
        $tbCombo->setContainerClass('oneline');
        $tbCombo->SetTitle(_t('BLOG_TRACKBACK'));
        $tbCombo->AddOption(_t('GLOBAL_ENABLED'), 'true');
        $tbCombo->AddOption(_t('GLOBAL_DISABLED'), 'false');
        $tbCombo->SetDefault($settings['trackback']);

        // Trackback status
        $tbStatusCombo =& Piwi::CreateWidget('Combo', 'trackback_status');
        $tbStatusCombo->setContainerClass('oneline');
        $tbStatusCombo->SetTitle(_t('BLOG_DEFAULT_STATUS', _t('BLOG_TRACKBACK')));
        $tbStatusCombo->AddOption(_t('GLOBAL_STATUS_APPROVED'), 'approved');
        $tbStatusCombo->AddOption(_t('GLOBAL_STATUS_WAITING'), 'waiting');
        $tbStatusCombo->SetDefault($settings['trackback_status']);

        // Pingback
        $pbCombo =& Piwi::CreateWidget('Combo', 'pingback');
        $pbCombo->setContainerClass('oneline');
        $pbCombo->SetTitle(_t('BLOG_PINGBACK'));
        $pbCombo->AddOption(_t('GLOBAL_ENABLED'), 'true');
        $pbCombo->AddOption(_t('GLOBAL_DISABLED'), 'false');
        $pbCombo->SetDefault($settings['pingback']);

        $fieldset->Add($viewCombo);
        $fieldset->Add($limitCombo);
        $fieldset->Add($popCombo);
        $fieldset->Add($commentslimitCombo);
        $fieldset->Add($recentcommentsLimitCombo);
        if (!Jaws_Error::IsError($categories)) {
            $fieldset->Add($catCombo);
        }
        $fieldset->Add($xmlCombo);
        $fieldset->Add($commCombo);
        $fieldset->Add($commStatusCombo);
        $fieldset->Add($tbCombo);
        $fieldset->Add($tbStatusCombo);
        $fieldset->Add($pbCombo);
        $fieldset->SetDirection('vertical');
        $form->Add($fieldset);

        $buttonbox =& Piwi::CreateWidget('HBox');
        $buttonbox->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
        $buttonbox->PackStart($save);

        $form->Add($buttonbox);

        $tpl->SetVariable('form', $form->Get());

        $tpl->ParseBlock('additional');

        return $tpl->Get();
    }

    /**
     * Applies modifications on blog settings
     *
     * @access       public
     * @return       template content
     */
    function SaveAdditionalSettings()
    {
        $this->CheckPermission('Settings');

        $request =& Jaws_Request::getInstance();
        $names = array(
            'default_view', 'last_entries_limit', 'last_comments_limit',
            'last_recentcomments_limit', 'default_category', 'xml_limit',
            'comments', 'comment_status', 'trackback', 'trackback_status');
        $post = $request->get($names, 'post');

        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $model->SaveSettings($post['default_view'], $post['last_entries_limit'],
                             $post['last_comments_limit'], $post['last_recentcomments_limit'],
                             $post['default_category'], $post['xml_limit'],
                             $post['comments'], $post['comment_status'],
                             $post['trackback'], $post['trackback_status']);

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=AdditionalSettings');
    }

    /**
     * Prepares the comments datagrid of an advanced search
     *
     * @access  public
     * @return  string  The XHTML of a datagrid
     */
    function CommentsDatagrid()
    {
        require_once JAWS_PATH . 'include/Jaws/Widgets/CommentUI.php';

        $commentUI = new Jaws_Widgets_CommentUI($this->_Name);
        $commentUI->SetEditAction(BASE_SCRIPT . '?gadget=Blog&amp;action=EditComment&amp;id={id}');
        return $commentUI->Get();
    }

    /**
     * Builds the data (an array) of filtered comments
     *
     * @access  public
     * @param   int     $limit   Limit of comments
     * @param   string  $filter  Filter
     * @param   string  $search  Search word
     * @param   string  $status  Spam status (approved, waiting, spam)
     * @return  array   Filtered Comments
     */
    function CommentsData($limit = 0, $filter = '', $search = '', $status = '')
    {
        require_once JAWS_PATH . 'include/Jaws/Widgets/CommentUI.php';

        $commentUI = new Jaws_Widgets_CommentUI($this->_Name);
        $commentUI->SetEditAction(BASE_SCRIPT . '?gadget=Blog&amp;action=EditComment&amp;id={id}');
        return $commentUI->GetDataAsArray($filter, $search, $status, $limit);
    }

    /**
     * Builds the data (an array) of filtered trackbacks
     *
     * @access  public
     * @param   int     $limit   Limit of trackbacks
     * @param   string  $filter  Filter
     * @param   string  $search  Search word
     * @param   string  $status  Spam status (approved, waiting, spam)
     * @return  array   Filtered Trackbacks
     */
    function TrackbacksData($limit = 0, $filter = '', $search = '', $status = '')
    {
        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        return $model->GetTrackbacksDataAsArray($filter, $search, $status, $limit);
    }

    /**
     * Displays blog comments manager
     *
     * @access       public
     * @return       template content
     */
    function ManageComments()
    {
        $this->CheckPermission('ManageComments');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('ManageComments.html');
        $tpl->SetBlock('manage_comments');
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('menubar', $this->MenuBar('ManageComments'));

        $tpl->SetVariable('comments_where', _t('BLOG_COMMENTS_WHERE'));
        $tpl->SetVariable('status_label', _t('GLOBAL_STATUS'));
        $tpl->SetVariable('deleteConfirm', _t('BLOG_DELETE_MASSIVE_COMMENTS'));

        //Status
        $status =& Piwi::CreateWidget('Combo', 'status');
        $status->AddOption('&nbsp;','various');
        $status->AddOption(_t('GLOBAL_STATUS_APPROVED'), 'approved');
        $status->AddOption(_t('GLOBAL_STATUS_WAITING'), 'waiting');
        $status->AddOption(_t('GLOBAL_STATUS_SPAM'), 'spam');
        $status->SetDefault('various');
        $status->AddEvent(ON_CHANGE, 'return searchComment();');
        $tpl->SetVariable('status', $status->Get());

        // filter by
        $filterByData = '';
        $filterBy =& Piwi::CreateWidget('Combo', 'filterby');
        $filterBy->AddOption('&nbsp;','various');
        $filterBy->AddOption(_t('BLOG_POST_ID_IS'), 'postid');
        $filterBy->AddOption(_t('BLOG_TITLE_CONTAINS'), 'title');
        $filterBy->AddOption(_t('BLOG_COMMENT_CONTAINS'), 'comment');
        $filterBy->AddOption(_t('BLOG_NAME_CONTAINS'), 'name');
        $filterBy->AddOption(_t('BLOG_EMAIL_CONTAINS'), 'email');
        $filterBy->AddOption(_t('BLOG_URL_CONTAINS'), 'url');
        $filterBy->AddOption(_t('BLOG_IP_IS'), 'ip');
        $filterBy->SetDefault($filterByData);
        $tpl->SetVariable('filter_by', $filterBy->Get());

        // filter
        $filterData = '';
        $filterEntry =& Piwi::CreateWidget('Entry', 'filter', $filterData);
        $filterEntry->setSize(20);
        $tpl->SetVariable('filter', $filterEntry->Get());
        $filterButton =& Piwi::CreateWidget('Button', 'filter_button',
                                            _t('BLOG_FILTER'), STOCK_SEARCH);
        $filterButton->AddEvent(ON_CLICK, 'javascript: searchComment();');

        $tpl->SetVariable('filter_button', $filterButton->Get());

        // Display the data
        $tpl->SetVariable('comments', $this->CommentsDatagrid($filterByData, $filterData));
        $tpl->ParseBlock('manage_comments');
        return $tpl->Get();
    }

    /**
     * Displays blog comment to be edited
     *
     * @access       public
     * @return       template content
     */
    function EditComment()
    {
        $this->CheckPermission('ManageComments');
        $request =& Jaws_Request::getInstance();

        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        // Fetch the comment
        $comment = $model->GetComment($request->get('id', 'get'));
        if (Jaws_Error::IsError($comment)) {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ManageComments');
        }

        // Fetch the entry
        ///FIXME we need to either create a query for this or make getEntry only fetch the title, this is a overkill atm
        $entry = $model->getEntry($comment['gadget_reference']);
        if (Jaws_Error::IsError($entry)) {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ManageComments');
        }

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('EditComment.html');
        $tpl->SetBlock('edit_comment');
        $tpl->SetVariable('menubar', $this->MenuBar('ManageComments'));

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet(_t('BLOG_UPDATE_COMMENT'));

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'id', $comment['id']));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Blog'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'SaveEditComment'));
        $permalink = $GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $comment['gadget_reference']));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'permalink', $permalink));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'status', $comment['status']));

        $text = '<strong>' . $entry['title'] . '</strong>';
        $staticText =& Piwi::CreateWidget('StaticEntry', _t('BLOG_COMMENT_CURRENTLY_UPDATING_FOR', $text));

        $name =& Piwi::CreateWidget('Entry', 'name', $xss->filter($comment['name']));
        $name->SetTitle(_t('GLOBAL_NAME'));

        $email =& Piwi::CreateWidget('Entry', 'email', $xss->filter($comment['email']));
        $email->SetStyle('direction: ltr;');
        $email->SetTitle(_t('GLOBAL_EMAIL'));

        $url =& Piwi::CreateWidget('Entry', 'url', $xss->filter($comment['url']));
        $url->SetStyle('direction: ltr;');
        $url->SetTitle(_t('GLOBAL_URL'));

        $ip =& Piwi::CreateWidget('Entry', 'ip', $comment['ip']);
        $ip->SetTitle(_t('GLOBAL_IP'));
        $ip->SetStyle('direction: ltr;');
        $ip->SetEnabled(false);

        $subject =& Piwi::CreateWidget('Entry', 'title', $xss->filter($comment['title']));
        $subject->SetTitle(_t('GLOBAL_TITLE'));
        $subject->SetStyle('width: 400px;');

        $comment =& Piwi::CreateWidget('TextArea', 'comments', $comment['comments']);
        $comment->SetRows(5);
        $comment->SetColumns(60);
        $comment->SetStyle('width: 400px;');
        $comment->SetTitle(_t('BLOG_COMMENT'));

        $cancelButton =& Piwi::CreateWidget('Button', 'previewButton', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancelButton->AddEvent(ON_CLICK, 'history.go(-1);');

        $submitButton =& Piwi::CreateWidget('Button', 'send', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $submitButton->SetSubmit();

        $deleteButton =& Piwi::CreateWidget('Button', 'delete', _t('GLOBAL_DELETE'), STOCK_DELETE);
        $deleteButton->AddEvent(ON_CLICK, "this.form.action.value = 'DeleteComment'; this.form.submit();");

        $buttonbox =& Piwi::CreateWidget('HBox');
        $buttonbox->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
        $buttonbox->PackStart($deleteButton);
        $buttonbox->PackStart($cancelButton);
        $buttonbox->PackStart($submitButton);

        $fieldset->Add($staticText);
        $fieldset->Add($name);
        $fieldset->Add($email);
        $fieldset->Add($url);
        $fieldset->Add($ip);
        $fieldset->Add($subject);
        $fieldset->Add($comment);
        $form->add($fieldset);
        $form->Add($buttonbox);

        $tpl->SetVariable('form', $form->Get());
        $tpl->ParseBlock('edit_comment');

        return $tpl->Get();
    }

    /**
     * Applies changes to a blog comment
     *
     * @access       public
     * @return       template content
     */
    function SaveEditComment()
    {
        $this->CheckPermission('ManageComments');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'name', 'title', 'url', 'email', 'comments', 'ip', 'permalink', 'status'), 'post');

        $model->UpdateComment($post['id'], $post['name'], $post['title'],
                              $post['url'], $post['email'], $post['comments'],
                              $post['permalink'], $post['status']);

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ManageComments');
    }

    /**
     * Deletes a blog comment
     *
     * @access       public
     * @return       template content
     */
    function DeleteComment()
    {
        $this->CheckPermission('ManageComments');
        $model   = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $request =& Jaws_Request::getInstance();

        $get_id  = $request->get('id', 'get');
        $post_id = $request->get('id', 'post');

        if (!is_null($get_id)) {
            $model->DeleteComment($get_id);
        } elseif(!is_null($post_id)) {
            $model->DeleteComment($post_id);
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ManageComments');
    }

    /**
     * Displays blog trackbacks manager
     *
     * @access       public
     * @return       template content
     */
    function ManageTrackbacks()
    {
        $this->CheckPermission('ManageTrackbacks');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('ManageTrackbacks.html');
        $tpl->SetBlock('manage_trackbacks');
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('menubar', $this->MenuBar('ManageTrackbacks'));

        $tpl->SetVariable('trackbacks_where', _t('BLOG_TRACKBACK_WHERE'));
        $tpl->SetVariable('status_label', _t('GLOBAL_STATUS'));
        $tpl->SetVariable('deleteConfirm', _t('BLOG_DELETE_MASSIVE_TRACKBACKS'));

        //Status
        $statusData = '';
        $status =& Piwi::CreateWidget('Combo', 'status');
        $status->AddOption('&nbsp;','various');
        $status->AddOption(_t('GLOBAL_STATUS_APPROVED'), 'approved');
        $status->AddOption(_t('GLOBAL_STATUS_WAITING'), 'waiting');
        $status->AddOption(_t('GLOBAL_STATUS_SPAM'), 'spam');
        $status->SetDefault($statusData);
        $status->AddEvent(ON_CHANGE, 'return searchTrackback();');
        $tpl->SetVariable('status', $status->Get());

        // filter by
        $filterByData = '';
        $filterBy =& Piwi::CreateWidget('Combo', 'filterby');
        $filterBy->AddOption('&nbsp;','various');
        $filterBy->AddOption(_t('BLOG_POST_ID_IS'), 'postid');
        $filterBy->AddOption(_t('BLOG_TITLE_CONTAINS'), 'title');
        $filterBy->AddOption(_t('BLOG_TRACKBACK_EXCERPT_CONTAINS'), 'excerpt');
        $filterBy->AddOption(_t('BLOG_TRACKBACK_BLOGNAME_CONTAINS'), 'blog_name');
        $filterBy->AddOption(_t('BLOG_URL_CONTAINS'), 'url');
        $filterBy->AddOption(_t('BLOG_IP_IS'), 'ip');
        $filterBy->SetDefault($filterByData);
        $tpl->SetVariable('filter_by', $filterBy->Get());

        // filter
        $filterData = '';
        $filterEntry =& Piwi::CreateWidget('Entry', 'filter', $filterData);
        $filterEntry->setSize(20);
        $tpl->SetVariable('filter', $filterEntry->Get());
        $filterButton =& Piwi::CreateWidget('Button', 'filter_button',
                                            _t('BLOG_FILTER'), STOCK_SEARCH);
        $filterButton->AddEvent(ON_CLICK, 'javascript: searchTrackback();');

        $tpl->SetVariable('filter_button', $filterButton->Get());

        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $total = $model->TotalOfData('blog_trackback');

        $gridBox =& Piwi::CreateWidget('VBox');
        $gridBox->SetID('trackbacks_box');
        $gridBox->SetStyle('width: 100%;');

        //Datagrid
        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->SetID('trackbacks_datagrid');
        $grid->SetStyle('width: 100%;');
        $grid->TotalRows($total);
        $grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('BLOG_TRACKBACK_BLOGNAME')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_CREATED')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_STATUS')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        //Tools
        $gridForm =& Piwi::CreateWidget('Form');
        $gridForm->SetID('trackbacks_form');
        $gridForm->SetStyle('float: right');

        $gridFormBox =& Piwi::CreateWidget('HBox');

        $actions =& Piwi::CreateWidget('Combo', 'trackbacks_actions');
        $actions->SetID('trackbacks_actions_combo');
        $actions->SetTitle(_t('GLOBAL_ACTIONS'));
        $actions->AddOption('', '');
        $actions->AddOption(_t('GLOBAL_DELETE'), 'delete');
        $actions->AddOption(_t('GLOBAL_MARK_AS_APPROVED'), 'approved');
        $actions->AddOption(_t('GLOBAL_MARK_AS_WAITING'), 'waiting');
        $actions->AddOption(_t('GLOBAL_MARK_AS_SPAM'), 'spam');

        $execute =& Piwi::CreateWidget('Button', 'executeTrackbackAction', '',
                                       STOCK_YES);
        $execute->AddEvent(ON_CLICK, "javascript: trackbackDGAction(document.getElementById('trackbacks_actions_combo'));");

        $gridFormBox->Add($actions);
        $gridFormBox->Add($execute);
        $gridForm->Add($gridFormBox);

        //Pack everything
        $gridBox->Add($grid);
        $gridBox->Add($gridForm);
        
        // Display the data
        $tpl->SetVariable('trackbacks', $gridBox->Get());
        $tpl->ParseBlock('manage_trackbacks');
        return $tpl->Get();
    }

    /**
     * Displays blog trackback to be edited
     *
     * @access       public
     * @return       template content
     */
    function ViewTrackback()
    {
        $this->CheckPermission('ManageTrackbacks');
        $request =& Jaws_Request::getInstance();

        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        // Fetch the trackback
        $trackback = $model->GetTrackback($request->get('id', 'get'));
        if (Jaws_Error::IsError($trackback)) {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ManageTrackbacks');
        }

        // Fetch the entry
        $entry = $model->getEntry($trackback['parent_id']);
        if (Jaws_Error::IsError($entry)) {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ManageTrackbacks');
        }

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('ViewTrackback.html');
        $tpl->SetBlock('view_trackback');
        $tpl->SetVariable('menubar', $this->MenuBar('ManageTrackbacks'));

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $date = $GLOBALS['app']->loadDate();


        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet(_t('BLOG_VIEW_TRACKBACK'));

        $text = '<strong>' . $entry['title'] . '</strong>';
        $staticText =& Piwi::CreateWidget('StaticEntry', _t('BLOG_TRACKBACKS_CURRENTLY_UPDATING_FOR', $text));

        $blog_name =& Piwi::CreateWidget('Entry', 'blog_name', $xss->filter($trackback['blog_name']));
        $blog_name->SetTitle(_t('BLOG_TRACKBACK_BLOGNAME'));
        $blog_name->SetStyle('width: 400px;');

        $url =& Piwi::CreateWidget('Entry', 'url', $xss->filter($trackback['url']));
        $url->SetStyle('direction: ltr;');
        $url->SetTitle(_t('GLOBAL_URL'));
        $url->SetStyle('width: 400px;');

        $createTime =& Piwi::CreateWidget('Entry', 'create_time', $date->Format($trackback['createtime']));
        $createTime->SetTitle(_t('GLOBAL_CREATETIME'));
        $createTime->SetStyle('direction: ltr;');
        $createTime->SetEnabled(false);

        $updateTime =& Piwi::CreateWidget('Entry', 'update_time', $date->Format($trackback['updatetime']));
        $updateTime->SetTitle(_t('GLOBAL_UPDATETIME'));
        $updateTime->SetStyle('direction: ltr;');
        $updateTime->SetEnabled(false);

        $ip =& Piwi::CreateWidget('Entry', 'ip', $trackback['ip']);
        $ip->SetTitle(_t('GLOBAL_IP'));
        $ip->SetStyle('direction: ltr;');
        $ip->SetEnabled(false);

        $subject =& Piwi::CreateWidget('Entry', 'title', $xss->filter($trackback['title']));
        $subject->SetTitle(_t('GLOBAL_TITLE'));
        $subject->SetStyle('width: 400px;');

        $excerpt =& Piwi::CreateWidget('TextArea', 'excerpt', $trackback['excerpt']);
        $excerpt->SetRows(5);
        $excerpt->SetColumns(60);
        $excerpt->SetStyle('width: 400px;');
        $excerpt->SetTitle(_t('BLOG_TRACKBACK_EXCERPT'));

        $cancelButton =& Piwi::CreateWidget('Button', 'previewButton', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancelButton->AddEvent(ON_CLICK, 'history.go(-1);');

        $buttonbox =& Piwi::CreateWidget('HBox');
        $buttonbox->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
        $buttonbox->PackStart($cancelButton);

        $fieldset->Add($staticText);
        $fieldset->Add($blog_name);
        $fieldset->Add($url);
        $fieldset->Add($createTime);
        $fieldset->Add($updateTime);
        $fieldset->Add($ip);
        $fieldset->Add($subject);
        $fieldset->Add($excerpt);

        $tpl->SetVariable('field', $fieldset->Get());
        $tpl->SetVariable('buttonbox', $buttonbox->Get());

        $tpl->ParseBlock('view_trackback');

        return $tpl->Get();
    }

    /**
     * Get a list of categories in a combo
     *
     * @access   public
     * @param    array   $categories Array of categories (optional)
     * @return   string  XHTML of a Combo
     */
    function GetCategoriesAsCombo($categories = null)
    {
        if (!is_array($categories)) {
            $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
            $categories = $model->GetCategories();
        }

        $combo =& Piwi::CreateWidget('Combo', 'category_id');
        $combo->SetID('category_id');
        $combo->SetStyle('width: 100%; margin-bottom: 10px;');
        $combo->SetSize(20);
        $combo->AddEvent(ON_CHANGE, 'editCategory(this.value)');

        foreach($categories as $cat) {
            $combo->AddOption($cat['name'], $cat['id']);
        }
        return $combo->Get();
    }


    /**
     * Get the categories form
     *
     * @access  public
     * @param   int     $catid  Category id
     */
    function CategoryForm($second_action = 'new', $id = '')
    {
        //Category form:
        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Blog'));

        $name         = '';
        $description  = '';
        $fast_url     = '';
        if ($second_action == 'editcategory') {
            $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
            $item = $model->GetCategory($id);
            $name         = (isset($item['name'])) ? $item['name'] : '';
            $description  = (isset($item['description'])) ? $item['description'] : '';
            $fast_url     = (isset($item['fast_url'])) ? $item['fast_url'] : '';
        }

        $action = $second_action == 'editcategory' ? 'UpdateCategory' : 'AddCategory';
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', $action));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'catid', $id));

        $text = $second_action == 'editcategory' ? _t('BLOG_EDIT_CATEGORY') : _t('BLOG_NEW_CATEGORY');

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet($text);
        // $fieldset =& Piwi::CreateWidget('FieldSet', $text);
        $fieldset->SetDirection('vertical');

        $catName =& Piwi::CreateWidget('Entry', 'name', $name);
        $catName->SetTitle(_t('BLOG_CATEGORY'));
        $catName->setStyle('width: 200px;');

        $catFastURL =& Piwi::CreateWidget('Entry', 'fast_url', $fast_url);
        $catFastURL->SetTitle(_t('BLOG_FASTURL'));
        $catFastURL->setStyle('width: 200px;');

        $catDescription =& Piwi::CreateWidget('TextArea', 'description', $description);
        $catDescription->SetTitle(_t('GLOBAL_DESCRIPTION'));
        $catDescription->setStyle('width: 250px;');

        $fieldset->Add($catName);
        $fieldset->Add($catFastURL);
        $fieldset->Add($catDescription);
        $form->Add($fieldset);

        $buttonbox =& Piwi::CreateWidget('HBox');
        $buttonbox->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

        if ($second_action == 'editcategory') {
            $deletemenu =& Piwi::CreateWidget('Button', 'deletecategory', _t('GLOBAL_DELETE'), STOCK_DELETE);
            $deletemenu->AddEvent(ON_CLICK, "javascript: if (confirm('"._t('BLOG_DELETE_CONFIRM_CATEGORY')."')) ".
                                  "deleteCategory(this.form);");
            $buttonbox->Add($deletemenu);
        }

        $cancelmenu =& Piwi::CreateWidget('Button', 'cancelcategory', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancelmenu->AddEvent(ON_CLICK, 'javascript: resetCategoryForm();');
        $buttonbox->Add($cancelmenu);

        $save =& Piwi::CreateWidget('Button', 'save',_t('GLOBAL_SAVE'), STOCK_SAVE);
        $save->AddEvent(ON_CLICK, 'javascript: saveCategory(this.form);');
        $buttonbox->PackStart($save);

        $form->Add($buttonbox);

        return $form->Get();
    }

    /**
     * Displays blog categories manager
     *
     * @access       public
     * @return       template content
     */
    function ManageCategories($second_action = '')
    {
        $this->CheckPermission('ManageCategories');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('ManageCategories.html');
        $tpl->SetBlock('categories');

        // Header
        $tpl->SetVariable('menubar', $this->MenuBar('ManageCategories'));

        $tpl->SetBlock('categories/manage');
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('categories', _t('BLOG_CATEGORIES'));

        //Category form:
        $tpl->SetVariable('new_form', $this->CategoryForm('new'));
        $tpl->SetVariable('delete_message',_t('BLOG_DELETE_CONFIRM_CATEGORY'));
        $tpl->SetVariable('combo', $this->GetCategoriesAsCombo());

        $new =& Piwi::CreateWidget('Button', 'new',_t('BLOG_NEW_CATEGORY'), STOCK_NEW);
        $new->SetStyle('width: 100%;');
        $new->AddEvent(ON_CLICK, 'javascript: newCategory();');
        $tpl->SetVariable('new_button', $new->Get());

        $tpl->ParseBlock('categories/manage');
        $tpl->ParseBlock('categories');

        return $tpl->Get();
    }

    /**
     * Adds the given category to blog
     *
     * @access       public
     * @return       template content
     */
    function AddCategory()
    {
        $request =& Jaws_Request::getInstance();

        $this->CheckPermission('ManageCategories');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $model->NewCategory($request->get('catname', 'post'));

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ManageCategories');
    }

    /**
     * Updates a blog category name
     *
     * @access       public
     * @return       template content
     */
    function UpdateCategory()
    {
        $request =& Jaws_Request::getInstance();
        $post    = $request->get(array('catid', 'catname'), 'post');

        $this->CheckPermission('ManageCategories');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $model->UpdateCategory($post['catid'], $post['catname']);

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=EditCategory&id=' . $post['catid']);
    }

    /**
     * Deletes the given blog category
     *
     * @access       public
     * @return       template content
     */
    function DeleteCategory()
    {
        $request =& Jaws_Request::getInstance();

        $this->CheckPermission('ManageCategories');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $model->DeleteCategory($request->get('catid', 'post'));

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ManageCategories');
    }

    /**
     * Displays an editor to write a new blog entry or preview it before saving
     *
     * @access       public
     * @param        string $action "preview" or empty(optional, empty by default)
     * @return       template content
     */
    function NewEntry($action = '')
    {
        $this->CheckPermission('AddEntries');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('AdminEntry.html');
        $tpl->SetBlock('edit_entry');

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        // Header
        $tpl->SetVariable('menubar', $this->MenuBar('NewEntry'));

        // Title
        $tpl->SetVariable('title', _t('GLOBAL_TITLE'));
        $tpl->SetVariable('action', 'NewEntry');
        $tpl->SetVariable('id', 0);
        $titleEntry =& Piwi::CreateWidget('Entry', 'title', '');
        $titleEntry->SetStyle('width: 95%');
        $titleEntry->setId('title');
        $tpl->SetVariable('title_field', $titleEntry->Get());

        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        // Category
        $catChecks =& Piwi::CreateWidget('CheckButtons', 'categories', 'vertical');
        $categories = $model->GetCategories();
        if (!Jaws_Error::IsError($categories)) {
            foreach ($categories as $a) {
                $catChecks->AddOption($a['name'], $a['id']);
            }
        }
        $catDefault = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/Blog/default_category'));
        $catChecks->SetDefault($catDefault);
        $catChecks->SetColumns(3);

        $tpl->SetVariable('category', _t('GLOBAL_CATEGORY'));
        $tpl->SetVariable('category_field', $catChecks->Get());

        // Summary
        $tpl->SetVariable('lbl_summary', _t('BLOG_ENTRY_SUMMARY'));
        $summary =& $GLOBALS['app']->LoadEditor('Blog', 'summary_block', '', false);
        $summary->setId('summary_block');
        $summary->TextArea->SetRows(8);
        $summary->TextArea->SetStyle('width: 100%;');
        $summary->SetWidth('96%');
        $tpl->SetVariable('summary', $summary->Get());

		// Body
		/*
        $tpl->SetVariable('text', _t('BLOG_ENTRY_BODY'));
        $editor =& $GLOBALS['app']->LoadEditor('Blog', 'text_block', '', false);
        $editor->setId('text_block');
        $editor->TextArea->SetStyle('width: 100%;');
        $editor->SetWidth('96%');
        $tpl->SetVariable('editor', $editor->Get());
		*/
		$bodyHidden =& Piwi::CreateWidget('HiddenEntry', 'text_block', '');
        $tpl->SetVariable('editor', $bodyHidden->Get());
        
        // Allow Comments
        $allow = $GLOBALS['app']->Registry->Get('/gadgets/Blog/allow_comments') == 'true';
        $comments =& Piwi::CreateWidget('CheckButtons', 'allow_comments');
        $comments->AddOption(_t('BLOG_ALLOW_COMMENTS'), 'comments', 'allow_comments', $allow);
        $tpl->SetVariable('allow_comments_field', $comments->Get());

        // Status
        $tpl->SetVariable('status', _t('GLOBAL_STATUS'));

        $statCombo =& Piwi::CreateWidget('Combo', 'published');
        $statCombo->setId('published');
        $statCombo->AddOption(_t('BLOG_DRAFT'), '0');
        $statCombo->AddOption(_t('BLOG_PUBLISHED'), '1');
        $statCombo->SetDefault('1');

        $tpl->SetVariable('status_field', $statCombo->Get());

        // Save
        $saveButton =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $saveButton->AddEvent(ON_CLICK, "javascript: if(this.form.elements['title'].value == '') { alert('".
                              _t('BLOG_MISSING_TITLE') . "'); this.form.elements['title'].focus(); } ".
                              "else { this.form.submit(); }");
        $tpl->SetVariable('save_button', $saveButton->Get());

        // Preview
        // TODO: We need a different stock icon for this.
        $previewButton =& Piwi::CreateWidget('Button', 'previewButton', _t('GLOBAL_PREVIEW'), STOCK_PRINT_PREVIEW);
        $previewButton->AddEvent(ON_CLICK, "javascript: parseText(this.form);");

        $tpl->SetVariable('preview_button', $previewButton->Get());

        // Advanced stuff..
        $tpl->SetBlock('edit_entry/advanced');
        $advancedDefault = false;
        $tpl->SetVariable('advanced_style', 'display: none;');

        $editAdvancedchk =& Piwi::CreateWidget('CheckButtons', 'edit_advanced');
        $editAdvancedchk->SetID('advanced_toggle');
        $editAdvancedchk->AddOption(_t('BLOG_ADVANCED_MODE'), 'advanced', false, $advancedDefault);
        $editAdvancedchk->AddEvent(ON_CLICK, 'toggleAdvanced(this.checked);');
        $tpl->SetVariable('advanced_field', $editAdvancedchk->Get());

        $tpl->SetVariable('timestamp_label', _t('BLOG_EDIT_TIMESTAMP'));
        $tsChk =& Piwi::CreateWidget('CheckButtons', 'edit_timestamp');
        $tsChk->AddOption('', 'yes', 'edit_timestamp', false);
        $tsChk->AddEvent(ON_CLICK, 'toggleUpdate(this.checked);');
        $tpl->SetVariable('timestamp_check', $tsChk->Get());

        // Maybe we need to get date from MDB2
        $pubdate =& Piwi::CreateWidget('DatePicker', 'pubdate', $GLOBALS['app']->UTC2UserTime('', 'Y-m-d H:i:s'));
        $pubdate->SetId('pubdate');
        $pubdate->showTimePicker(true);
        $pubdate->setDateFormat('%Y-%m-%d %H:%M:%S');
        $tpl->SetVariable('pubdate', $pubdate->Get());

        $tpl->SetVariable('fasturl', _t('BLOG_FASTURL'));
        $tpl->SetVariable('fasturl_comment', _t('BLOG_FASTURL_COMMENT'));

        $fastUrlEntry =& Piwi::CreateWidget('Entry', 'fasturl', '');
        $fastUrlEntry->SetId('fasturl');
        $fastUrlEntry->SetStyle('width: 100%; direction: ltr;');
        $tpl->SetVariable('fasturl_field', $fastUrlEntry->Get());

        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/trackback') == 'true') {
            $tpl->SetBlock('edit_entry/advanced/trackback');
            $tpl->SetVariable('trackback_to', _t('BLOG_TRACKBACK'));
            $tb =& Piwi::CreateWidget('TextArea', 'trackback_to', '');
            $tb->SetId('trackback_to');
            $tb->SetRows(4);
            $tb->SetColumns(30);
            $tb->SetStyle('width: 99%; direction: ltr; white-space: nowrap;');
            $tpl->SetVariable('trackbackTextArea', $tb->Get());
            $tpl->ParseBlock('edit_entry/advanced/trackback');
        }
        $tpl->ParseBlock('edit_entry/advanced');
        $tpl->ParseBlock('edit_entry');
        return $tpl->Get();
    }

    /**
     * Saves a new blog entry and displays the entries list on admin section
     *
     * @access       public
     * @return       template content
     */
    function SaveNewEntry()
    {
        $this->CheckPermission('AddEntries');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');

        $request =& Jaws_Request::getInstance();
        $names   = array('edit_timestamp', 'pubdate', 'categories', 'title',
                         'fasturl', 'allow_comments', 'published',
                         'trackback_to');
        $post    = $request->get($names, 'post');
        $content = $request->get(array('summary_block', 'text_block'), 'post', false);
        $post['trackback_to'] = str_replace("\r\n", "\n", $post['trackback_to']);

        $pubdate = null;
        if (isset($post['edit_timestamp']) && $post['edit_timestamp'][0] == 'yes') {
            $pubdate = $post['pubdate'];
        }

        $id = $model->NewEntry($GLOBALS['app']->Session->GetAttribute('user_id') , $post['categories'],
                               $post['title'], $content['summary_block'], $content['text_block'], $post['fasturl'],
                               isset($post['allow_comments'][0]), $post['trackback_to'],
                               $post['published'], $pubdate);

        if (!Jaws_Error::IsError($id)) {
            if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/trackback') == 'true') {
                $to = explode("\n", $post['trackback_to']);
                $link = $this->GetURLFor('SingleView', array('id' => $id), true, 'site_url');
                $title = $post['title'];
                $text = $content['text_block'];
                if ($GLOBALS['app']->UTF8->strlen($text) > 250) {
                    $text = $GLOBALS['app']->UTF8->substr($text, 0, 250) . '...';
                } else if ($GLOBALS['app']->UTF8->strlen($content['summary_block']) > 250) {
                    $text = $GLOBALS['app']->UTF8->substr($content['summary_block'], 0, 250) . '...';
				}
                $model->SendTrackback($title, $text, $link, $to);
            }
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ListEntries');
    }

    /**
     * Displays a preview of a new entry before saving
     *
     * @access       public
     * @return       template content
     */
    function PreviewNewEntry()
    {
        $this->CheckPermission('AddEntries');
        return $this->NewEntry('preview');
    }

    /**
     * Displays an editor to edit an existing blog entry or preview it before saving changes
     *
     * @access       public
     * @param        string $action "preview" or empty(optional, empty by default)
     * @return       template content
     */
    function EditEntry($action = '', $id = null)
    {
        $request =& Jaws_Request::getInstance();
        $names   = array('id', 'action');
        $get     = $request->get($names, 'get');
        $names   = array('allow_comments', 'edit_advanced');
        $post    = $request->get($names, 'post');
        $jaws_url = $GLOBALS['app']->GetJawsURL();

        $id = !is_null($id) ? $id : $get['id'];
        $model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $adminModel = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $entry = $model->GetEntry($id);
        if (Jaws_Error::IsError($entry)) {
            Jaws_Error::Fatal('Post not found', __FILE__, __LINE__);
        }

        if ($GLOBALS['app']->Session->GetAttribute('user_id') != $entry['user_id']) {
            $this->CheckPermission('ModifyOthersEntries');
        }

        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('AdminEntry.html');
        $tpl->SetBlock('edit_entry');

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        // Header
        $action = isset($get['action']) ? $get['action'] : null;
        $tpl->SetVariable('menubar', $this->MenuBar($action));

        // Title
        $tpl->SetVariable('title', _t('BLOG_TITLE'));
        $tpl->SetVariable('action', 'EditEntry');
        $tpl->SetVariable('id', $id);

        $titleEntry =& Piwi::CreateWidget('Entry', 'title', $entry['title']);
        $titleEntry->SetStyle('width: 95%');
        $tpl->SetVariable('title_field', $titleEntry->Get());

        // Category
        $catChecks =& Piwi::CreateWidget('CheckButtons', 'categories', 'vertical');
        $categories = $model->GetCategories();
        if (!Jaws_Error::IsError($categories)) {
            foreach ($categories as $a) {
                $catChecks->AddOption($a['name'], $a['id']);
            }
        }
        $catDefault = array();
        if (!Jaws_Error::isError($entry['categories'])) {
            foreach ($entry['categories'] as $cat) {
                $catDefault[] = $cat['id'];
            }
        }
        $catChecks->SetDefault($catDefault);
        $catChecks->SetColumns(3);

        $tpl->SetVariable('category', _t('GLOBAL_CATEGORY'));
        $tpl->SetVariable('category_field', $catChecks->Get());

        // for compatibility with old versions
        $more_pos = Jaws_UTF8::strpos($entry['text'], '[more]');
        if ($more_pos !== false) {
            $entry['summary'] = Jaws_UTF8::substr($entry['text'], 0, $more_pos);
            $entry['text']    = Jaws_UTF8::str_replace('[more]', '', $entry['text']);
        }

        // Summary
        $tpl->SetVariable('lbl_summary', _t('BLOG_ENTRY_SUMMARY'));
        $summary =& $GLOBALS['app']->LoadEditor('Blog', 'summary_block', $entry['summary'], false);
        $summary->setId('summary_block');
        $summary->TextArea->SetRows(8);
        $summary->TextArea->SetStyle('width: 100%;');
        $summary->SetWidth('96%');
        $tpl->SetVariable('summary', $summary->Get());

        // Body
        /*
		$tpl->SetVariable('text', _t('BLOG_BODY'));
        $editor =& $GLOBALS['app']->LoadEditor('Blog', 'text_block', $entry['text'], false);
        $editor->setId('text_block');
        $editor->TextArea->SetStyle('width: 100%;');
        $editor->SetWidth('96%');
        $tpl->SetVariable('editor', $editor->Get());
		*/
		$bodyHidden =& Piwi::CreateWidget('HiddenEntry', 'text_block', $entry['text']);
        $tpl->SetVariable('editor', $bodyHidden->Get());
        
        $stpl = new Jaws_Template('gadgets/Blog/templates/');
        $stpl->Load('AdminPosts.html');
		$stpl->SetBlock('admin_posts');
		$add_gadget0 =& Piwi::CreateWidget('Button', 'add', _t('BLOG_NEW_CONTENT'), STOCK_ADD);
		$url = $GLOBALS['app']->GetSiteURL().'/admin.php?gadget=Blog&action=AddLayoutElement&mode=new&linkid='.$id;
		$add_gadget0->AddEvent(ON_CLICK, "addGadget('".$url."', '"._t('BLOG_NEW_CONTENT')."');");
		$stpl->SetVariable('add_button', $add_gadget0->Get());
        $stpl->SetVariable('confirmPostDelete', _t('BLOG_POST_CONFIRM_DELETE'));
        $stpl->SetVariable('confirmRssHide', _t('BLOG_RSS_CONFIRM_DELETE'));
		
		$blog_posts = $model->GetAllPostsOfBlog($entry['id'], true);
        if (!Jaws_Error::IsError($blog_posts)) {
            if (!count($blog_posts) <= 0) {
				reset($blog_posts);
				$i = 0;
				$stpl->SetBlock('admin_posts/posts');
				foreach ($blog_posts as $p) {
					$stpl->SetBlock('admin_posts/posts/post');
					$background = "";
					if ($i == 0) {
						$background = "background: #EDF3FE; border-top: dotted 1pt #E2E2E2; ";
					} else if ($i % 2 == 0) {
						$background = "background: #EDF3FE; ";
					}
					$stpl->SetVariable('background', $background);
					$stpl->SetVariable('post_id', $p['id']);
					$stpl->SetVariable('post_align', ($p['layout'] == 0 ? 'left' : 'right'));
					$icon = $jaws_url.'/gadgets/'.$p['gadget'].'/images/logo.png';
					if (!empty($p['image']) && $p['gadget'] == "text" && substr(strtolower($p['image']), 0, 7) == 'gadget:') {
						$stpl->SetBlock('admin_posts/posts/post/thumb');
						$stpl->SetVariable('post_thumb', $p['image']);
						$stpl->ParseBlock('admin_posts/posts/post/thumb');
					}
					if (!empty($p['title'])) {
						$stpl->SetBlock('admin_posts/posts/post/title');
						$stpl->SetVariable('post_title', strip_tags($p['title']));
						$stpl->ParseBlock('admin_posts/posts/post/title');
					}
					if ($p['gadget'] != "text") {  
						$stpl->SetBlock('admin_posts/posts/post/gadget');
						$gadget_description = 'No description';
						$layoutGadget = $GLOBALS['app']->LoadGadget($p['gadget'], 'LayoutHTML');
						$layoutActions = $adminModel->GetGadgetActions($p['gadget']);
						if (!Jaws_Error::isError($layoutGadget)) {
							foreach ($layoutActions as $lactions) {
								if (isset($lactions['action']) && isset($lactions['name'])) {
									$GLOBALS['app']->Registry->LoadFile($p['gadget']);
									if (method_exists($layoutGadget, $lactions['action'])) {
										if ($lactions['action'] == $p['image']) {
											$gadget_description = $lactions['name'];
											break;
										}
									}
								}
							}
						} else {
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_ERR, $gadget ." is missing the LayoutHTML. Jaws can't execute Layout " .
													 "actions if the file doesn't exists");
							}
						}
						unset($layoutActions);
						unset($layoutGadget);
						$stpl->SetVariable('gadget_description', $gadget_description);
						$stpl->SetVariable('edit_onclick', "editElementAction('', '".$p['gadget']."', '".$p['image']."');");
						$stpl->ParseBlock('admin_posts/posts/post/gadget');
						$post_onclick = "editElementAction('', '".$p['gadget']."', '".$p['image']."');";
					} else {
						if (!empty($p['image'])) {
							$icon = $jaws_url.'/gadgets/FileBrowser/images/mimetypes/text-x-image-generic.png';
						} else if (!empty($p['image_code'])) {
							$icon = $jaws_url.'/gadgets/FileBrowser/images/mimetypes/text-x-html-generic.png';
						} else {
							$icon = $jaws_url.'/gadgets/FileBrowser/images/mimetypes/text-x-generic.png';
						}
						$post_onclick = "editElementAction('".$GLOBALS['app']->GetSiteURL() . "/admin.php?gadget=Blog&action=EditElementAction&id=".$p['id']."&method=EditPost');";
					}
					$stpl->SetVariable('post_icon', $icon);
					$stpl->SetVariable('post_description', substr(strip_tags($p['description']), 0, 250));
					$stpl->SetVariable('post_onclick', $post_onclick);
					$stpl->ParseBlock('admin_posts/posts/post');
					$i++;
				}
				$stpl->ParseBlock('admin_posts/posts');
			} else {	
				$stpl->SetBlock('admin_posts/no_posts');
				$stpl->ParseBlock('admin_posts/no_posts');
			}
        }
		$stpl->ParseBlock('admin_posts');

		$tpl->SetVariable('posts', $stpl->Get());

        // Allow Comments
        if (isset($post['allow_comments'])) {
            $allow = true;
        } else if (isset($entry['allow_comments']) && $entry['allow_comments'] === true) {
            $allow = true;
        } else {
            $allow = false;
        }

        $comments =& Piwi::CreateWidget('CheckButtons', 'allow_comments');
        $comments->AddOption(_t('BLOG_ALLOW_COMMENTS'), 'comments', 'allow_comments', $allow);
        $tpl->SetVariable('allow_comments_field', $comments->Get());

        // Status
        $tpl->SetVariable('status', _t('GLOBAL_STATUS'));
        $entry['published'] = ($entry['published'] === true) ? 1 : 0;
        $statData = $entry['published'];
        $statCombo =& Piwi::CreateWidget('Combo', 'published');
        $statCombo->AddOption(_t('BLOG_DRAFT'), '0');
        $statCombo->AddOption(_t('BLOG_PUBLISHED'), '1');
        $statCombo->SetDefault($statData);
        $tpl->SetVariable('status_field', $statCombo->Get());

        // Save
        $saveButton =& Piwi::CreateWidget('Button', 'save', _t('BLOG_UPDATE'), STOCK_SAVE);
        $saveButton->AddEvent(ON_CLICK, "javascript: if(this.form.elements['title'].value == '') { alert('".
                                _t('BLOG_MISSING_TITLE') . "'); this.form.elements['title'].focus(); } ".
                                "else { this.form.submit(); }");
        $tpl->SetVariable('save_button', $saveButton->Get());

        // Preview
        // TODO: We need a different stock icon for this.
        $previewButton =& Piwi::CreateWidget('Button', 'preview',
                                                _t('GLOBAL_PREVIEW'), STOCK_PRINT_PREVIEW);
        $previewButton->SetID('preview_button');
        $previewButton->AddEvent(ON_CLICK, "javascript: window.open('".$this->GetURLFor('SingleView', array('id' => $id))."');");
        $tpl->SetVariable('preview_button', $previewButton->Get());

        $tpl->SetBlock('edit_entry/advanced');
        $advancedDefault = false;
        if (isset($post['edit_advanced'])) {
            $advancedDefault = true;
            $tpl->SetVariable('advanced_style', 'display: inline;');
        } else {
            $tpl->SetVariable('advanced_style', 'display: none;');
        }

        $editAdvancedchk =& Piwi::CreateWidget('CheckButtons', 'edit_advanced');
        $editAdvancedchk->SetID('advanced_toggle');
        $editAdvancedchk->AddOption(_t('BLOG_ADVANCED_MODE'), 'advanced', false, $advancedDefault);
        $editAdvancedchk->AddEvent(ON_CLICK, 'toggleAdvanced(this.checked);');
        $tpl->SetVariable('advanced_field', $editAdvancedchk->Get());

        $tpl->SetVariable('timestamp_label', _t('BLOG_EDIT_TIMESTAMP'));
        $tsChk =& Piwi::CreateWidget('CheckButtons', 'edit_timestamp');
        $tsChk->AddOption('', 'yes', 'edit_timestamp', false);
        $tsChk->AddEvent(ON_CLICK, 'toggleUpdate(this.checked);');
        $tpl->SetVariable('timestamp_check', $tsChk->Get());

        $pubdate =& Piwi::CreateWidget('DatePicker',
                                       'pubdate',
                                       $GLOBALS['app']->UTC2UserTime($entry['publishtime'], 'Y-m-d H:i:s'));
        $pubdate->SetId('pubdate');
        $pubdate->showTimePicker(true);
        $pubdate->setDateFormat('%Y-%m-%d %H:%M:%S');
        $tpl->SetVariable('pubdate', $pubdate->Get());

        $tpl->SetVariable('fasturl', _t('BLOG_FASTURL'));
        $tpl->SetVariable('fasturl_comment', _t('BLOG_FASTURL_COMMENT'));

        $fastUrlData = $entry['fast_url'];
        $fastUrlEntry =& Piwi::CreateWidget('Entry', 'fasturl', $fastUrlData);
        $fastUrlEntry->SetId('fasturl');
        $fastUrlEntry->SetStyle('width: 100%');
        $tpl->SetVariable('fasturl_field', $fastUrlEntry->Get());

        // Trackback
        if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/trackback') == 'true') {
            $tpl->SetBlock('edit_entry/advanced/trackback');
            $tpl->SetVariable('trackback_to', _t('BLOG_TRACKBACK'));
            $tb =& Piwi::CreateWidget('TextArea', 'trackback_to', $entry['trackbacks']);
            $tb->SetId('trackback_to');
            $tb->SetRows(4);
            $tb->SetColumns(30);
            // TODO: Remove this nasty hack, and replace it with some padding in the template.
            $tb->SetStyle('width: 99%; direction: ltr; white-space: nowrap;');
            $tpl->SetVariable('trackbackTextArea', $tb->Get());
            $tpl->ParseBlock('edit_entry/advanced/trackback');
        }
        $tpl->ParseBlock('edit_entry/advanced');

        $tpl->ParseBlock('edit_entry');
        return $tpl->Get();
    }

    /**
     * Displays a preview of an edited blog entry before saving changes
     *
     * @access       public
     * @return       template content
     */
    function PreviewEditEntry()
    {
        $request =& Jaws_Request::getInstance();
        return $this->EditEntry('preview', $request->get('id', 'post'));
    }

    /**
     * Save changes on an edited blog entry and shows the entries list on admin section
     *
     * @access       public
     * @return       template content
     */
    function SaveEditEntry()
    {
        $request =& Jaws_Request::getInstance();
        $names   = array('id', 'edit_timestamp', 'pubdate', 'categories', 'title',
                         'fasturl', 'allow_comments', 'published',
                         'trackback_to');
        $post    = $request->get($names, 'post');
        $content = $request->get(array('summary_block', 'text_block'), 'post', false);

        $post['trackback_to'] = str_replace("\r\n", "\n", $post['trackback_to']);

        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $id = (int)$post['id'];

        $pubdate = null;
        if (isset($post['edit_timestamp']) && $post['edit_timestamp'][0] == 'yes') {
            $pubdate = $post['pubdate'];
        }

        $model->UpdateEntry($id, $post['categories'], $post['title'],
                            $content['summary_block'], $content['text_block'], $post['fasturl'], isset($post['allow_comments'][0]), 
                            $post['trackback_to'], $post['published'], $pubdate);
        if (!Jaws_Error::IsError($id)) {
            if ($GLOBALS['app']->Registry->Get('/gadgets/Blog/trackback') == 'true') {
                $to = explode("\n", $post['trackback_to']);
                $link = $this->GetURLFor('SingleView', array('id' => $id), true, 'site_url');
                $title = $post['title'];
                $text = $content['text_block'];
                if ($GLOBALS['app']->UTF8->strlen($text) > 250) {
                    $text = $GLOBALS['app']->UTF8->substr($text, 0, 250) . '...';
                } else if ($GLOBALS['app']->UTF8->strlen($content['summary_block']) > 250) {
                    $text = $GLOBALS['app']->UTF8->substr($content['summary_block'], 0, 250) . '...';
				}
                $model->SendTrackback($title, $text, $link, $to);
            }
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=EditEntry&id=' . $id);
    }

    /**
     * Shows confirm. screen for deleting a blog entry or deletes it if confirm. was done
     *
     * @access       public
     * @return       template content
     */
    function DeleteEntry()
    {
        $this->CheckPermission('DeleteEntries');
        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'step'), 'post');

        if (!is_null($post['step']) && $post['step'] == 'delete') {
            // Delete Post
            $res = $model->DeleteEntry($post['id']);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ENTRY_NOT_DELETED'), RESPONSE_ERROR);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ENTRY_DELETED'), RESPONSE_NOTICE);
            }

            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ListEntries');
        }

        $get = $request->get(array('id', 'action'), 'get');

        // Ask for confirmation...
        $entry = $model->GetEntry($get['id']);
        if (Jaws_Error::IsError($entry)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_DOES_NOT_EXISTS'));

            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ListEntries');
        }

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('DeleteEntry.html');
        $tpl->SetBlock('delete_entry');

        $tpl->SetVariable('base_script', BASE_SCRIPT);

        // Header
        $tpl->SetVariable('menubar', $this->MenuBar($get['action']));

        // Message
        $tpl->SetVariable('delete_message', _t('BLOG_DELETE_CONFIRM_ENTRY'));

        // Delete
        $deleteButton =& Piwi::CreateWidget('Button', 'delete',
                                            _t('GLOBAL_DELETE'), STOCK_DELETE);
        $deleteButton->SetSubmit();
        $tpl->SetVariable('delete_button', $deleteButton->Get());

        // Cancel
        $cancelButton =& Piwi::CreateWidget('Button', 'cancel',
                                            _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancelButton->AddEvent(ON_CLICK, "javascript: this.form.action.value = 'ListEntries'; this.form.submit(); ");
        $tpl->SetVariable('cancel_button', $cancelButton->Get());

        // ID
        $idHidden =& Piwi::CreateWidget('HiddenEntry', 'id', $get['id']);

        $tpl->SetVariable('id', $idHidden->Get());
        $tpl->SetVariable('title', $entry['title']);
        $tpl->SetVariable('text', $this->ParseText($entry['summary'], 'Blog'));
        $tpl->SetVariable('user', $entry['username']);
        $date = $GLOBALS['app']->loadDate();
        $tpl->SetVariable('createtime', $date->Format($entry['publishtime']));
        $pos = 1;
        $categories = '';
        foreach ($entry['categories'] as $cat) {
            $categories .= $cat['name'];
            if ($pos != count($entry['categories'])) {
                $categories .= ', ';
            }
            $pos++;
        }
        $tpl->SetVariable('category', $categories);
        $tpl->ParseBlock('delete_entry');

        return $tpl->Get();
    }

    /**
     * Displays a list of blog entries for the blog admin section
     *
     * @access       public
     * @return       template content
     */
    function ListEntries()
    {
        $this->CheckPermission('default');
        $this->AjaxMe('script.js');

        $common_url = BASE_SCRIPT . '?gadget=Blog';

        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('ListEntries.html');
        $tpl->SetBlock('list_entries');

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('deleteConfirm', _t('BLOG_DELETE_MASSIVE_ENTRIES'));

        // Header
        $tpl->SetVariable('menubar', $this->MenuBar('ListEntries'));

        // Filtering
        // Show past n days etc.
        $showCombo =& Piwi::CreateWidget('Combo', 'show');
        $showCombo->setId('show');
        $showCombo->AddOption('&nbsp;', 'NOTHING');
        $showCombo->AddOption(_t('BLOG_RECENT_POSTS'), 'RECENT');

        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $monthentries = $model->GetMonthsEntries();
        if (!Jaws_Error::IsError($monthentries) && is_array($monthentries)) {
            $date = $GLOBALS['app']->loadDate();
            foreach ($monthentries as $e) {
                $showCombo->AddOption($date->MonthString($e['month']).' '.$e['year'],
                                      $e['month'].':'.$e['year']);
            }
        }
        $showCombo->AddEvent(ON_CHANGE, 'javascript: searchPost();');
        $show = 'NOTHING';
        $showCombo->SetDefault('NOTHING');

        $tpl->SetVariable('show', _t('BLOG_SHOW'));
        $tpl->SetVariable('show_field', $showCombo->Get());

        // Category filter
        $category = '';
        $catCombo =& Piwi::CreateWidget('Combo', 'category');
        $catCombo->setId('category');
        $catCombo->AddOption('&nbsp;', '');
        $categories = $model->GetCategories();
        if (!Jaws_Error::IsError($categories)) {
            foreach ($categories as $cat) {
                $name = $cat['name'];
                $catCombo->AddOption($name, $cat['id']);
            }
        }

        $catCombo->SetDefault($category);
        $catCombo->AddEvent(ON_CHANGE, 'javascript: searchPost();');
        $tpl->SetVariable('category', _t('BLOG_CATEGORY'));
        $tpl->SetVariable('category_field', $catCombo->Get());

        // Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'status');
        $statusCombo->setId('status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('BLOG_PUBLISHED'), '1');
        $statusCombo->AddOption(_t('BLOG_DRAFT'), '0');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchPost();');
        $tpl->SetVariable('status', _t('GLOBAL_STATUS'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        $catCombo->SetDefault($category);
        $catCombo->AddEvent(ON_CHANGE, 'javascript: searchPost();');
        $tpl->SetVariable('category', _t('BLOG_CATEGORY'));
        $tpl->SetVariable('category_field', $catCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchPost();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $gridBox =& Piwi::CreateWidget('VBox');
        $gridBox->SetID('entries_box');
        $gridBox->SetStyle('width: 100%;');

        $grid =& Piwi::CreateWidget('DataGrid', array(), null);
        $grid->SetID('posts_datagrid');
        $grid->SetStyle('width: 100%;');
        $grid->TotalRows($model->TotalOfPosts());
        $grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('BLOG_EDIT_TIMESTAMP')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_UPDATETIME')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('BLOG_AUTHOR')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_STATUS')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        //Tools
        $gridForm =& Piwi::CreateWidget('Form');
        $gridForm->SetID('entries_form');
        $gridForm->SetStyle('float: right');

        $gridFormBox =& Piwi::CreateWidget('HBox');

        $actions =& Piwi::CreateWidget('Combo', 'entries_actions');
        $actions->SetID('entries_actions_combo');
        $actions->SetTitle(_t('GLOBAL_ACTIONS'));
        $actions->AddOption('', '');
        $actions->AddOption(_t('GLOBAL_DELETE'),  'delete');
        $actions->AddOption(_t('BLOG_DRAFT'),     '0');
        $actions->AddOption(_t('BLOG_PUBLISHED'), '1');

        $execute =& Piwi::CreateWidget('Button', 'executeEntryAction', '', STOCK_YES);
        $execute->AddEvent(ON_CLICK, "javascript: entryDGAction(document.getElementById('entries_actions_combo'));");

        $gridFormBox->Add($actions);
        $gridFormBox->Add($execute);
        $gridForm->Add($gridFormBox);

        //Pack everything
        $gridBox->Add($grid);
        $gridBox->Add($gridForm);
        $tpl->SetVariable('entries', $gridBox->Get());

        $tpl->ParseBlock('list_entries');
        return $tpl->Get();
    }

    function EditCategory()
    {
        return $this->ManageCategories('editcategory');
    }

    /**
     * Prepares the datagrid for blog posts
     *
     * @access  public
     * @return  string  XHTML of the datagrid
     */
    function PostsDatagrid()
    {
    }

    /**
     * Prepares the data of an advanced search on blog posts
     *
     * @access  public
     * @param   string  $period  Period to look for
     * @param   int     $cat     Category
     * @param   int     $status  Status (0=Draft, 1=Published)
     * @param   string  $search  Search word
     * @param   int     $limit   Limit data
     * @return  array   An array with all the data
     */
    function PostsData($period, $cat, $status, $search, $limit = 0)
    {
        $common_url = BASE_SCRIPT . '?gadget=Blog';

        $model = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $entries = $model->AdvancedSearch($limit, $period, $cat, $status, $search,
                                          $GLOBALS['app']->Session->GetAttribute('user_id'));

        if (Jaws_Error::IsError($entries)) {
            return array();
        }

        $posts = array();
        $date = $GLOBALS['app']->loadDate();

        foreach ($entries as $row) {
            $post = array();
            $id = $row['id'];
            $post['__KEY__'] = $id;
            $post['title'] = '<a href="'.$common_url.'&amp;action=EditEntry&amp;id='.$id.'">'.
                $row['title'].'</a>';
            $post['publishtime'] = $date->Format($row['publishtime']);
            $post['updatetime']  = $date->Format($row['updatetime']);
            $post['username']    = $row['username'];
            $post['published']   = ($row['published'] === true) ? _t('BLOG_PUBLISHED') : _t('BLOG_DRAFT');

            $actions = '';
            $link = Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
                                       $common_url.'&amp;action=EditEntry&amp;id='.$id,
                                       STOCK_EDIT);
            $actions = $link->Get().'&nbsp;';

            if ($this->GetPermission('ManageComments')) {
                $link = Piwi::CreateWidget('Link', _t('BLOG_COMMENTS'),
                                           $common_url.'&amp;action=ManageComments&amp;filterby=postid&amp;filter='.$id,
                                           'images/stock/stock-comments.png');
                $actions.= $link->Get().'&nbsp;';
            }

            if ($this->GetPermission('DeleteEntries')) {
                $link = Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                                           $common_url.'&amp;action=DeleteEntry&amp;id='.$id,
                                           STOCK_DELETE);
                $actions.= $link->Get().'&nbsp;';
            }
            $post['actions'] = $actions;
            $posts[] = $post;
        }

        unset($entries);
        return $posts;
    }

    /**
     * Format a status boolean to human readable
     *
     * @param string $value value to format
     * @return string("Published" or "Draft")
     */
    function FormatStatus($value)
    {
        return ($value === true) ? _t('BLOG_PUBLISHED') : _t('BLOG_DRAFT');
    }

    /**
     * Format a date using Jaws
     *
     * @param string $value The data to format.
     * @return string The formatted date.
     */
    function FormatDate($value)
    {
        $date = $GLOBALS['app']->loadDate();
        return $date->Format($value);
    }

    /**
     * We are on the form_post page
     *
     * @access public
     * @return string
     */
    function form_post($account = false, $fuseaction = '', $params = array())
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Blog', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Blog', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'PublishEntries')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		
		//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');        
		$request =& Jaws_Request::getInstance();
        if (empty($fuseaction)) {
			$fuseaction = $request->get('fuseaction', 'post');
		}
		$get  = $request->get(array('fuseaction', 'linkid', 'id'), 'get');
        if (empty($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
		$get['id'] = (int)$get['id'];
		$get['linkid'] = (int)$get['linkid'];
        
		$adminModel = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
		
		$editpost = false;

        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddPost": 
				        $keys = array('sort_order', 'LinkID', 'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active', 'url_type', 
							'internal_url', 'external_url', 'url_target', 'rss_url', 'image_code',
							'startdate', 'iTimeHr', 'iTimeMin', 'iTimeSuffix');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						$iTimeHr = (int)$postData['iTimeHr'];
						if ($postData['iTimeSuffix'] == 'PM' && $iTimeHr != 12) {
							$iTimeHr = $iTimeHr + 12;
						}
						if ($postData['iTimeSuffix'] == 'AM' && $iTimeHr == 12) {
							$iTimeHr = 0;
						}
						$iTime = $postData['startdate'] . ' ' .($iTimeHr < 10 ? '0'.$iTimeHr : $iTimeHr).":".$postData['iTimeMin'].":00";
						$iTime = $GLOBALS['db']->Date(strtotime($iTime));
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Blog', 'default') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
							$parent = $model->GetPage($postData['LinkID']);
							if ($OwnerID != $parent['ownerid'] || !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'PublishEntries')) {
								$GLOBALS['app']->Session->CheckPermission('Blog', 'PublishEntries');
							}
						}
						$result = $adminModel->AddPost($postData['sort_order'], $postData['LinkID'], 
							$postData['title'], $postData['description'], $postData['Image'], 
							$postData['image_width'], $postData['image_height'], $postData['layout'], 
							$postData['Active'], $OwnerID, 'text', $postData['url_type'], 
							$postData['internal_url'], $postData['external_url'], $postData['url_target'], 
							$postData['rss_url'], $postData['image_code'], $iTime);
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditPost": 
				        $keys = array('ID', 'sort_order', 'LinkID', 'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active', 'url_type', 
							'internal_url', 'external_url', 'url_target', 'rss_url', 'image_code',
							'startdate', 'iTimeHr', 'iTimeMin', 'iTimeSuffix');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						$iTimeHr = (int)$postData['iTimeHr'];
						if ($postData['iTimeSuffix'] == 'PM' && $iTimeHr != 12) {
							$iTimeHr = $iTimeHr + 12;
						}
						if ($postData['iTimeSuffix'] == 'AM' && $iTimeHr == 12) {
							$iTimeHr = 0;
						}
						$iTime = $postData['startdate'] . ' ' .($iTimeHr < 10 ? '0'.$iTimeHr : $iTimeHr).":".$postData['iTimeMin'].":00";
						$iTime = $GLOBALS['db']->Date(strtotime($iTime));
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						if ($postData['ID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Blog', 'default') && $account === false) {
								$result = $adminModel->UpdatePost($postData['ID'], $postData['sort_order'], 
								$postData['title'], $postData['description'], $postData['Image'], 
								$postData['image_width'], $postData['image_height'], $postData['layout'],
								$postData['Active'], 'text', $postData['url_type'], $postData['internal_url'], 
								$postData['external_url'], $postData['url_target'], 
								$postData['rss_url'], $postData['image_code'], $iTime);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($postData['ID']);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->UpdatePost($postData['ID'], 
										$postData['sort_order'], $postData['title'], 
										$postData['description'], $postData['Image'], 
										$postData['image_width'], $postData['image_height'],
										$postData['layout'], $postData['Active'], 'text', $postData['url_type'], 
										$postData['internal_url'], $postData['external_url'], $postData['url_target'], 
										$postData['rss_url'], $postData['image_code'], $iTime);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
                        break;
                case "DeletePost": 
				        $keys = array('idarray', 'ID', 'xcount');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['ID'];
						if (empty($id)) {
							$id = $get['id'];
						}
						$dcount = 0;
						// loop through the idarray and delete each ID
						if ($postData['idarray'] && strpos($postData['idarray'], ',')) {
					        $ids = explode(',', $postData['idarray']);
							foreach ($ids as $i => $v) {
								if ($GLOBALS['app']->Session->GetPermission('Blog', 'default') && $account === false) {
									$result = $adminModel->DeletePost((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$post = $model->GetPost((int)$v);
									if ($OwnerID == $post['ownerid']) {
										$result = $adminModel->DeletePost((int)$v);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								}								
								$dcount++;
							}
						} else if (!empty($id)) {
							if ($GLOBALS['app']->Session->GetPermission('Blog', 'default') && $account === false) {
								$result = $adminModel->DeletePost((int)$id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($id);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->DeletePost((int)$id);
								} else {
									//return _t('BLOG_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
									$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
            }
			
			// Send us to the appropriate page
			if ($editpost === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Blog&action=EditEntry&id='.$postData['LinkID'];
				}
			} else {
				if (count($params) > 0) {
					return false;
				} else {
					if ($account === false) {
						Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ListEntries');
					} else {
						Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					}
				}
			}
			
			if ($account === false) {
				Jaws_Header::Location($redirect);
			} else {
				if ($editpost === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	parent.parent.location.reload();\n";
					$output_html .= "	//parent.parent.hideGB();\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				}
			}

		} else {
			if (count($params) > 0) {
				return false;
			} else {
				if ($account === false) {
					Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=ListEntries');
				} else {
					Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
				}
			}
		}

    }

    /**
     * We are on the A_form page
     *
     * @access public
     * @return string
     */
    function A_form($account = false)
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Blog', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Blog', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'PublishEntries')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id', 'linkid');
		$get = $request->get($gather, 'get');
		$id = $get['id'];
		$linkid = $get['linkid'];
		$sort_order = 0;
		
		// initialize template
		$tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('msgbox-wrapper');
        $responses = $GLOBALS['app']->Session->PopLastResponse();
        if ($responses) {
            foreach ($responses as $msg_id => $response) {
                $tpl->SetBlock('msgbox-wrapper/msgbox');
                $tpl->SetVariable('msg-css', $response['css']);
                $tpl->SetVariable('msg-txt', $response['message']);
                $tpl->SetVariable('msg-id', $msg_id);
                $tpl->ParseBlock('msgbox-wrapper/msgbox');
            }
        }
        
		$tpl->ParseBlock('msgbox-wrapper');

        $tpl->SetBlock('gadget_page');

		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Blog&action=EditEntry&id=".$get['linkid']."';";
			$OwnerID = 0;
			$base_script = 'admin.php';
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "parent.parent.hideGB();";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_script = 'index.php';
		}
		
		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/calendar-blue.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/calendar.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/calendar-setup.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/lang/calendar-en.js');

		$tpl->SetVariable('workarea-style', '');

		//while(list($key,$val) = each($snoopy->headers))
			//echo $key.": ".$val."<br>\n";
		//echo "<p>\n";
		
		//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
		$error = '';
		$form_content = '';
		
		// initialize template
		$stpl = new Jaws_Template('gadgets/Blog/templates/');
		$stpl->Load('EditPost.html');
		$stpl->SetBlock('form');
		// send post records
		if (!empty($id)) {
			$id = (int)$id;
			// send page records
			$pageInfo = $model->GetPost($id);
			if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Blog', 'default') || $pageInfo['ownerid'] == $OwnerID)) {
				$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
			} else {
				//return new Jaws_Error(_t('BLOG_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('BLOG_NAME'));
				if (Jaws_Error::IsError($pageInfo)) {
					return new Jaws_Error($pageInfo->GetMessage(), _t('BLOG_NAME'));
				}
				$error .= _t('BLOG_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
			}
		} else if (!empty($linkid)) {
			$linkid = (int)$linkid;
			// send highest sort_order
			$sql = "SELECT [sort_order] FROM [[blog_posts]] WHERE ([linkid] = {linkid}) ORDER BY [sort_order] DESC LIMIT 1";
			$params = array();
			$params['linkid'] = $linkid;
			$max = $GLOBALS['db']->queryOne($sql, $params);
			if (Jaws_Error::IsError($max)) {
			   $page = $max->GetMessage();
			} else if (is_numeric($max)) {
				$sort_order = (int)$max+1;
			}
			$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
		} else {
			// Send us to the appropriate page
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if ($account === true) {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			} else {
				Jaws_Header::Location($base_script . '?gadget=Blog&action=ListEntries');
			}
		}

		// send requesting URL to syntacts
		$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
		$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$stpl->SetVariable('DPATH', JAWS_DPATH);
		$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
		$stpl->SetVariable('gadget', 'Blog');
		$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
		$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
		$stpl->SetVariable('controller', $base_script);
		
		// Get Help documentation
		$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("CustomPage/admin_CustomPage_A_form_help", 'txt');
		$snoopy = new Snoopy('CustomPage');

		if($snoopy->fetch($help_url)) {
			$helpContent = Jaws_Utils::split2D($snoopy->results);
		}
						
		// Hidden elements
		$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
		$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
		$form_content .= $idHidden->Get()."\n";

		$sortOrder = (isset($pageInfo['sort_order'])) ? $pageInfo['sort_order'] : $sort_order;
		$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'sort_order', $sortOrder);
		$form_content .= $sort_orderHidden->Get()."\n";

		$fuseaction = (isset($pageInfo['id'])) ? 'EditPost' : 'AddPost';
		$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		$form_content .= $fuseactionHidden->Get()."\n";

		$LinkID = (isset($pageInfo['linkid'])) ? $pageInfo['linkid'] : $linkid;
		$linkIDHidden =& Piwi::CreateWidget('HiddenEntry', 'LinkID', $LinkID);
		$form_content .= $linkIDHidden->Get()."\n";
				
		$rss_url = (isset($pageInfo['rss_url'])) ? $pageInfo['rss_url'] : '';
		$rss_urlHidden =& Piwi::CreateWidget('HiddenEntry', 'rss_url', $rss_url);
		$form_content .= $rss_urlHidden->Get()."\n";
		
		// Active
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('BLOG_ACTIVE')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		$active = (isset($pageInfo['active'])) ? $pageInfo['active'] : 'Y';
		$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
		$activeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
		$activeCombo->AddOption(_t('GLOBAL_NO'), 'N');
		$activeCombo->SetDefault($active);
		$activeCombo->setTitle(_t('BLOG_ACTIVE'));
		$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\" colspan=\"3\">".$activeCombo->Get()."</td></tr>";
		
		// Publish Date
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('BLOG_PUBLISH_DATE')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		$current_date = $GLOBALS['app']->UTC2UserTime();
		$startdate = (isset($pageInfo['startdate']) ? $GLOBALS['app']->UTC2UserTime($pageInfo['itime'], "m/d/Y") : date('m/d/Y', $current_date));

		$startdate_HTML = '<input NAME="startdate" ID="startdate" SIZE="10" VALUE="'.$startdate.'" maxlength="10">
		  <button type="button" name="start_button" id="start_button">
		  <img id="start_button_stockimage" src="'. $GLOBALS['app']->GetJawsURL() . '/libraries/piwi/piwidata/art/stock/apps/office-calendar.png" border="0" alt="" height="16" width="16" />
		  </button>
		  <script type="text/javascript">
		  Calendar.setup({
		  inputField: "startdate",
		  ifFormat: "%m/%d/%Y",
		  button: "start_button",
		  singleClick: true,
		  weekNumbers: false,
		  firstDay: 0,
		  date: "",
		  showsTime: false,
		  multiple: false});
		</script>';
		
		$iTimeHr = (isset($pageInfo['itime']) ? date('g', strtotime($pageInfo['itime'])) : date('g', $current_date));
		$hourCombo =& Piwi::CreateWidget('Combo', 'iTimeHr');
		for ($i = 1; $i < 13; $i++) {
			$hourCombo->AddOption($i, (string)$i);
		}
		$hourCombo->SetDefault($iTimeHr);
		
		$iTimeMin = (isset($pageInfo['itime']) ? date('i', strtotime($pageInfo['itime'])) : date('i', $current_date));
		$minuteCombo =& Piwi::CreateWidget('Combo', 'iTimeMin');
		for ($i = 0; $i < 60; $i++) {
			$i = ($i < 10 ? sprintf("%02d", $i) : $i);
			$minuteCombo->AddOption($i, (string)$i);
		}
		$minuteCombo->SetDefault($iTimeMin);
		
		$iTimeSuffix = (isset($pageInfo['itime']) ? date('A', strtotime($pageInfo['itime'])) : date('A', $current_date));
		$suffixCombo =& Piwi::CreateWidget('Combo', 'iTimeSuffix');
		$suffixCombo->AddOption('PM', 'PM');
		$suffixCombo->AddOption('AM', 'AM');
		$suffixCombo->SetDefault($iTimeSuffix);
		
		$startdate_HTML .= '&nbsp;'._t('BLOG_START_TIME').'&nbsp;'.$hourCombo->Get().$minuteCombo->Get().$suffixCombo->Get();
		$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"startdate\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\" colspan=\"3\">".$startdate_HTML."</td></tr>";
			
		// Title
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('GLOBAL_TITLE')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		$title = (isset($pageInfo['title'])) ? $pageInfo['title'] : '';
		$titleEntry =& Piwi::CreateWidget('Entry', 'title', $title);
		$titleEntry->SetTitle(_t('GLOBAL_TITLE'));
		$titleEntry->SetStyle('direction: ltr; width: 300px;');
		$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"title\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\" colspan=\"3\">".$titleEntry->Get()."</td></tr>";

		// Description
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('BLOG_CONTENT')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		$content = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
		$editor =& $GLOBALS['app']->LoadEditor('Blog', 'description', $content, false);
		$editor->TextArea->SetStyle('width: 100%;'.(empty($content) ? " height: 90px;" : ''));
		//$editor->SetWidth('100%');
		$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\" colspan=\"3\">".$editor->Get()."</td></tr>";

		// Image
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('BLOG_IMAGE')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		$image = (isset($pageInfo['image'])) ? $pageInfo['image'] : '';
		$main_image_src = '';
		if (isset($image) && !empty($image)) {
			$main_image_src = $xss->filter(strip_tags($image));
			if (substr(strtolower($main_image_src), 0, 4) == "http") {
				if (substr(strtolower($main_image_src), 0, 7) == "http://") {
					$main_image_src = explode('http://', $main_image_src);
					foreach ($main_image_src as $img_src) {
						if (!empty($img_src)) {
							$main_image_src = 'http://'.$img_src;
							break;
						}
					}
				} else {
					$main_image_src = explode('https://', $main_image_src);
					foreach ($main_image_src as $img_src) {
						if (!empty($img_src)) {
							$main_image_src = 'https://'.$img_src;
							break;
						}
					}
				}
			} else {
				$thumb = Jaws_Image::GetThumbPath($main_image_src);
				$medium = Jaws_Image::GetMediumPath($main_image_src);
				if (file_exists(JAWS_DATA . 'files'.$thumb)) {
					$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
				} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
					$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
				} else if (file_exists(JAWS_DATA . 'files'.$main_image_src)) {
					$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$main_image_src;
				}
			}
		}
		$image_preview = '';
		if (!empty($main_image_src)) { 
			$image_preview .= "<br /><img border=\"0\" src=\"".$main_image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px visibility: visible;\" id=\"main_image_src\"><br /><b><a id=\"imageDelete\" href=\"javascript:void(0);\" onclick=\"document.getElementById('main_image_src').style.visibility = 'hidden'; document.getElementById('Image').value = '';\">Delete</a></b>";
		}
		$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['id']) ? 'none;' : ';').'" id="imageButton">';
		$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert Media" onClick="toggleNo(\'imageButton\'); toggleYes(\'imageRow\'); toggleYes(\'imageInfo\'); toggleNo(\'imageGadgetRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageCodeInfo\'); toggleYes(\'imageCodeButton\');" style="font-family: Arial; font-size: 10pt; font-weight: bold"></td>';
		$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
		$form_content .= '</tr>';
		$form_content .= '<TR style="display: '.($image != "" || !isset($pageInfo['id']) ? ';' : 'none;').'" id="imageRow">';
		$form_content .= '<TD VALIGN="top" colspan="4">';
		$form_content .= '<table border="0" width="100%" cellpadding="0" cellspacing="0">';
		$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Blog', 'NULL', 'NULL', 'main_image', 'Image', 1, 500, 34);});</script>";
		$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'Image', $image);
		$form_content .= "<tr><td class=\"syntacts-form-row\"><div id=\"insertMedia\"><b>Insert Media: </b></div>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"imageField\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get()."</div></td></tr>";
		  
		// Image Width and Height
		$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['id']) ? ';' : 'none;').'" id="imageInfo" class="syntacts-form-row">';
		$form_content .= '<td>&nbsp;</td>';
		$form_content .= '<td colspan="3" valign="top">';
		$form_content .= '<b>';
		$form_content .= '<select size="1" id="image_width" name="image_width" onChange="document.getElementById(\'image_height\').value=0">';
		$image_width = (isset($pageInfo['image_width'])) ? $pageInfo['image_width'] : 0;
		$form_content .= '<option value="0"'.($image_width == 0 || !isset($pageInfo['id']) ? ' SELECTED' : '').'>Auto</option>';
		for ($w = 1; $w<950; $w++) { 
			$form_content .= '<option value="'.$w.'"'.($image_width == $w ? ' SELECTED' : '').'>'.$w.'</option>';
		}
		$form_content .= '</select>&nbsp;Width</b>&nbsp;&nbsp;&nbsp;';
		$form_content .= '<b><select size="1" id="image_height" name="image_height" onChange="document.getElementById(\'image_width\').value=0">';
		$image_height = (isset($pageInfo['image_height'])) ? $pageInfo['image_height'] : 0;
		$form_content .= '<option value="0"'.($image_height == 0 || !isset($pageInfo['id']) ? ' SELECTED' : '').'>Auto</option>';
		for ($i = 1; $i<950; $i++) { 
			$form_content .= '<option value="'.$i.'"'.($image_height == $i ? ' SELECTED' : '').'>'.$i.'</option>';
		}
		$form_content .= '</select>&nbsp;Height</b>&nbsp;in pixels</td>';
		$form_content .= '</tr>';
		
		// Image URL Type
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('BLOG_URLTYPE')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		$url = (isset($pageInfo['url'])) ? $pageInfo['url'] : '';
		$form_content .= '<tr class="syntacts-form-row" id="URLTypeInfo">';
		$form_content .= '<td><label for="url_type"><nobr>'.$helpString.'</nobr></label></td>';
		$form_content .= '<td colspan="3">';
		$form_content .= '<select name="url_type" id="url_type" SIZE="1" onChange="if (this.value == \'internal\') {toggleYes(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');};  if (this.value == \'external\') {toggleNo(\'internalURLInfo\'); toggleYes(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');}; if (this.value == \'imageviewer\') {toggleNo(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleNo(\'urlTargetInfo\');}; ">';
		$form_content .= '<option value="imageviewer"'.((!empty($url) && $url == "javascript:void(0);") || empty($url) || !isset($pageInfo['id']) ? ' selected' : '').'>Open Image in New Window</option>';
		if ($account === false) {
			$form_content .= '<option value="internal" '.(!empty($url) && strpos($url, "://") === false && $url != "javascript:void(0);" ? ' selected' : '').'>Internal</option>';
		}
		$form_content .= '<option value="external" '.(!empty($url) && strpos($url, "://") === true ? ' selected' : '').'>External</option>';
		$form_content .= '</select>';
		$form_content .= '</td>';
		$form_content .= '</tr>';
				
		if ($account === false) {
			// Image Internal URL		
			$helpString = '';
			foreach($helpContent as $help) {		            
				if ($help[0] == _t('BLOG_INTERNALURL')) {
					$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
					if ($help[1]) {
						if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
							$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
						}
						$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
						$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
						$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
						$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
						if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
							$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
						}
						$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
						if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
							$helpString .= "</a>";
						}
					}
				}
			}
			$form_content .= '<tr style="display: '.((!empty($url) && strpos($url, "://") === true) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['id']) ? 'none;' : ';').'" class="syntacts-form-row" id="internalURLInfo">';
			$form_content .= '<td><label for="internal_url"><nobr>'.$helpString.'</nobr></label></td>';
			$post_url = (!empty($url) && strpos($url, "://") === false) ? $url : '';
			$urlListCombo =& Piwi::CreateWidget('Combo', 'internal_url');
			$urlListCombo->setID('internal_url');
			$sql = '
				SELECT
					[id], [menu_type], [title], [url], [visible]
				FROM [[menus]]
				ORDER BY [menu_type] ASC, [title] ASC';
			
			$menus = $GLOBALS['db']->queryAll($sql);
			if (Jaws_Error::IsError($menus)) {
				return $menus;
			}
			if (is_array($menus)) {
				foreach ($menus as $menu => $m) {
					if ($m['visible'] == 0) {
						$urlListCombo->AddOption("<i>".$m['menu_type']." : ".$m['title']."</i>", $m['url']);
					} else {
						$urlListCombo->AddOption($m['menu_type']." : ".$m['title'], $m['url']);
					}
				}
			}
			$urlListCombo->setDefault($post_url);
			$form_content .= '<td colspan="3">'.$urlListCombo->Get().'</td>';
			$form_content .= '</tr>';
		} else {
			$internalURLHidden =& Piwi::CreateWidget('HiddenEntry', 'internal_url', '');
			$form_content .= $internalURLHidden->Get()."\n";
		}
		
		// Image External URL		
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('BLOG_EXTERNALURL')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		$external_url = (!empty($url) && strpos($url, "://") === true) ? $url : '';
		$externalUrlEntry =& Piwi::CreateWidget('Entry', 'external_url', $external_url);
		$externalUrlEntry->SetTitle(_t('BLOG_EXTERNALURL'));
		$externalUrlEntry->SetStyle('direction: ltr; width: 300px;');
		$form_content .= "<tr style=\"display: ".((!empty($url) && strpos($url, "://") === false) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['id']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"externalURLInfo\"><td><label for=\"external_url\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$externalUrlEntry->Get()."</td></tr>";
				
		// Image URL Target
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('BLOG_URLTARGET')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		$url_target = (isset($pageInfo['url_target'])) ? $pageInfo['url_target'] : '_self';
		$url_targetCombo =& Piwi::CreateWidget('Combo', 'url_target');
		$url_targetCombo->AddOption('Open in Same Window', '_self');
		$url_targetCombo->AddOption('Open in a New Window', '_blank');
		$url_targetCombo->SetDefault($url_target);
		$url_targetCombo->setID('url_target');
		$url_targetCombo->setTitle(_t('BLOG_URLTARGET'));
		$form_content .= "<tr style=\"display: ".((!empty($url)) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['id']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"urlTargetInfo\"><td class=\"syntacts-form-row\"><label for=\"url_target\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$url_targetCombo->Get()."</td></tr>";
		$form_content .= '</table>';
		$form_content .= '</td>';
		$form_content .= '</tr>';
				
		// Image HTML
		if ($account === false) {
			$image_code = (isset($pageInfo['image_code'])) ? $pageInfo['image_code'] : '';
			$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? 'none;' : ';').'" id="imageCodeButton">';
			$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert HTML" onClick="toggleYes(\'imageCodeInfo\'); toggleYes(\'imageButton\'); toggleNo(\'imageRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageGadgetRow\'); toggleNo(\'imageCodeButton\');" STYLE="font-family: Arial; font-size: 10pt; font-weight: bold" /></td>';
			$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
			$form_content .= '</tr>';
			$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? ';' : 'none;').'" id="imageCodeInfo">';
			$form_content .= '<td class="syntacts-form-row"><b>Insert HTML:</b></td>';
			// send main splash editor HTML to syntacts
			$editorCode=& Piwi::CreateWidget('TextArea', 'image_code', $image_code);
			$editorCode->SetStyle('width: 490px;');
			$editorCode->SetID('image_code');
			$form_content .= '<td colspan="2" class="syntacts-form-row">'.$editorCode->Get().'</td>';
			$form_content .= '<td class="syntacts-form-row"><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'image_code\').value = \'\';">Delete</a></b></td>';
			$form_content .= '</tr>';
		} else {
			$imageCodeHidden =& Piwi::CreateWidget('HiddenEntry', 'image_code', '');
			$form_content .= $imageCodeHidden->Get()."\n";
		}
				
		// Layout
		$helpString = '';
		foreach($helpContent as $help) {		            
			if ($help[0] == _t('BLOG_LAYOUT')) {
				$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
				if ($help[1]) {
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
					}
					$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
					$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
					$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
					$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
					}
					$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
					if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
						$helpString .= "</a>";
					}
				}
			}
		}
		
		$layout = (isset($pageInfo['layout'])) ? (int)$pageInfo['layout'] : 0;
		$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"layout\"><nobr>".$helpString."</nobr></label></td>
		<td colspan=\"3\" class=\"syntacts-form-row middle\">
			<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">
			  <tr>
				<td align=\"right\"><input type=\"radio\" value=\"0\" name=\"layout\"".($layout == 0 ? ' checked="checked"' : '')."></td>
				<td><img border=\"0\" src=\"images/align_i_L.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['layout'], '0');\"></td>
				<td align=\"right\"><input type=\"radio\" value=\"1\" name=\"layout\"".($layout == 1 ? ' checked="checked"' : '')."></td>
				<td><img border=\"0\" src=\"images/align_i_R.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['layout'], '1');\"></td>
				<!--            
				<td align=\"right\"><input type=\"radio\" value=\"2\" name=\"layout\"".($layout == 2 ? ' checked="checked"' : '')."></td>
				<td><img border=\"0\" src=\"images/align_i_R_w_R.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['layout'], '2');\"></td>
			  </tr>
			  <tr>
				<td colspan=\"6\">&nbsp;</td>
			  </tr>
			  <tr>
				<td align=\"right\"><input type=\"radio\" value=\"3\" name=\"layout\"".($layout == 3 ? ' checked="checked"' : '')."></td>
				<td><img border=\"0\" src=\"images/align_i_L_w_R.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['layout'], '3');\"></td>
				<td align=\"right\"><input type=\"radio\" value=\"4\" name=\"layout\"".($layout == 4 ? ' checked="checked"' : '')."></td>
				<td><img border=\"0\" src=\"images/align_i_T.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['layout'], '4');\"></td>
				<td align=\"right\"><input type=\"radio\" value=\"5\" name=\"layout\"".($layout == 5 ? ' checked="checked"' : '')."></td>
				<td><img border=\"0\" src=\"images/align_i_L_t_T.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['layout'], '5');\"></td>
				-->
			</tr>
			</table>
		</td>
		</tr>";
				
		/*
		// RSS feed? Parse it here and show as list
		if (isset($pageInfo['rss_url']) && !empty($pageInfo['rss_url']) && strpos($pageInfo['rss_url'], 'http://') !== false) {
			require_once JAWS_PATH . 'libraries/magpierss-0.72/rss_fetch.inc';
			$rss_html = "";
			$rss = fetch_rss($pageInfo['rss_url']);
			$k = 0;  
			if ($rss) {
				$date = $GLOBALS['app']->loadDate();
				$hideRss = $model->GetHiddenRssOfPage($pageInfo['id']);
				foreach ($rss->items as $item) {
					$hidden = false;
					$rss_title = $item['title'];
					//$rss_title = str_replace($rss->items['source']['title'], '', $rss_title); 
					$rss_url = (strrpos($item['link'], "http://") > 7 ? substr($item['link'], 0, strrpos($item['link'], "http://")) : $item['link']);
					$rss_published = (isset($item['date_timestamp']) ? $item['date_timestamp'] : $item['published']);
					$rss_image = $item['image']['url'];
					$rss_description = (isset($item['description']) ? $item['description'] : $item['summary']);
					$rss_description = strip_tags($rss_description, '<img><br>');
					if (!Jaws_Error::IsError($hideRss)) {
						foreach($hideRss as $r) {		            
							if (htmlentities($rss_title) == $r['title'] && htmlentities($rss_url) == $r['url'] && htmlentities($rss_published) == $r['published']) {
								$hidden = true;
							}
						}
					}
					if (!$hidden) { 
						$rss_html .= "<tr id=\"syntactsCategory_".$k."\" noDrop=\"true\" noDrag=\"true\" style=\"".($k % 2 == 0 ? "background: #EDF3FE;" : "" )."\"><td>&nbsp;</td><td><div style=\"text-align:left; padding: 10px;\"><b><a href=\"".$rss_url."\" target=\"_blank\">".$rss_title."</a></b><br />".(isset($rss_published) ? "Published: ".$date->Format($rss_published)."<br />" : "").(isset($rss_image) ? "<img src=\"".$rss_image."\" border=\"0\" align=\"left\" style=\"padding: 10px;\" />" : "").substr($rss_description, 0, 300)." ...<p>&nbsp;</p><p><a href=\"".$rss_url."\" target=\"_blank\">View This >></a></p></div></td><td>&nbsp;</td><td style=\"padding-right: 10px;\" id=\"rss".$k."_editLink\"><a href=\"javascript:void(0);\" onClick=\"hideRss(".$k.", ".$pageInfo['id'].", '".htmlentities($rss_title)."', '".htmlentities($rss_published)."', '".htmlentities($rss_url)."');\" title=\"Hide This Item\">Delete</a></td></tr>\n";
						$rss_html .= "<tr style=\"display: none;\" id=\"syntactsEdit_".$k."\" noDrop=\"true\" noDrag=\"true\" style=\"".($k % 2 == 0 ? "background: #EDF3FE;" : "" )."\"><td>&nbsp;</td><td><div style=\"text-align:left; padding: 10px;\"><b><a href=\"".$rss_url."\" target=\"_blank\">".$rss_title."</a></b><br />".(isset($rss_published) ? "Published: ".$date->Format($rss_published)."<br />" : "")."</td><td colspan=\"2\" style=\"text-align: right; padding-right: 10px;\" id=\"rss".$k."_editLink\"><a href=\"javascript:void(0);\" onClick=\"showRss(".$k.", ".$pageInfo['id'].", '".htmlentities($rss_title)."', '".htmlentities($rss_published)."', '".htmlentities($rss_url)."');\" title=\"Show This Item\">Un-Delete</a></td></tr>\n";
					} else {
						$rss_html .= "<tr style=\"display: none;\" id=\"syntactsCategory_".$k."\" noDrop=\"true\" noDrag=\"true\" style=\"".($k % 2 == 0 ? "background: #EDF3FE;" : "" )."\"><td>&nbsp;</td><td><div style=\"text-align:left; padding: 10px;\"><b><a href=\"".$rss_url."\" target=\"_blank\">".$rss_title."</a></b><br />".(isset($rss_published) ? "Published: ".$date->Format($rss_published)."<br />" : "").(isset($rss_image) ? "<img src=\"".$rss_image."\" border=\"0\" align=\"left\" style=\"padding: 10px;\" />" : "").substr($rss_description, 0, 300)." ...<p>&nbsp;</p><p><a href=\"".$rss_url."\" target=\"_blank\">View This >></a></p></div></td><td>&nbsp;</td><td style=\"padding-right: 10px;\" id=\"rss".$k."_editLink\"><a href=\"javascript:void(0);\" onClick=\"hideRss(".$k.", ".$pageInfo['id'].", '".htmlentities($rss_title)."', '".htmlentities($rss_published)."', '".htmlentities($rss_url)."');\" title=\"Hide This Item\">Delete</a></td></tr>\n";
						$rss_html .= "<tr id=\"syntactsEdit_".$k."\" noDrop=\"true\" noDrag=\"true\" style=\"".($k % 2 == 0 ? "background: #EDF3FE;" : "" )."\"><td>&nbsp;</td><td><div style=\"text-align:left; padding: 10px;\"><b><a href=\"".$rss_url."\" target=\"_blank\">".$rss_title."</a></b><br />".(isset($rss_published) ? "Published: ".$date->Format($rss_published)."<br />" : "")."</td><td colspan=\"2\" style=\"text-align: right; padding-right: 10px;\" id=\"rss".$k."_editLink\"><a href=\"javascript:void(0);\" onClick=\"showRss(".$k.", ".$pageInfo['id'].", '".htmlentities($rss_title)."', '".htmlentities($rss_published)."', '".htmlentities($rss_url)."');\" title=\"Show This Item\">Un-Delete</a></td></tr>\n";
					}
					$k++;
				}
				$rss_html .= "<tr noDrop=\"true\" noDrag=\"true\"><td>&nbsp;</td><td style=\"text-align:left\"><div style=\"padding: 10px\"><b>Source: <a href=\"". $rss->channel['link']. "\" target=\"_blank\">". $rss->channel['title']. "</a></b></div></td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
			} else {
				//$edit_url = $base_script . '?gadget=Blog&action='.$submit_vars['ACTIONPREFIX'].'view&id='.$pageInfo['id'];
				$rss_html .= "<tr noDrop=\"true\" noDrag=\"true\"><td>&nbsp;</td><td style=\"text-align:left\"><p><b>There was a problem parsing the RSS feed for: ".$pageInfo['rss_url'].". Please make sure it is entered correctly.</b></p></td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
			}
			$form_content .= $rss_html;
		}
		*/
		if ($error != '') {
			$stpl->SetVariable('content', $error);
		} else {
			$stpl->SetVariable('content', $form_content);
		}
		$stpl->ParseBlock('form');
		
		$page = $stpl->Get();
		$tpl->SetVariable('content', $page);
        $tpl->ParseBlock('gadget_page');
        return $tpl->Get();
						
    }

    /**
     * We are on the A_form_post page
     *
     * @access public
     * @return string
     */
    function A_form_post($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

    }

    /**
     * Adds layout element
     *
     * @access public
     * @return template content
     */
    function AddLayoutElement()
    {
		if (JAWS_SCRIPT == 'admin') {
			$GLOBALS['app']->Session->CheckPermission('Blog', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Blog', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'PublishEntries')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		//$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		//$GLOBALS['app']->ACL->CheckPermission($GLOBALS['app']->Session->GetAttribute('username'), 'Blog', 'default');
		$model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');

		//$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'linkid', 'mode', 'where', 'callback'), 'post');
        
		$id = $post['id'];
		if (empty($id)) {
            $id = $request->get('id', 'get');
            $id = !empty($id) ? $id : '';
        }
		$linkid = $post['linkid'];
		if (empty($linkid)) {
            $linkid = $request->get('linkid', 'get');
            $linkid = !empty($linkid) ? $linkid : '';
        }
        $mode = $post['mode'];
		if (empty($mode)) {
            $mode = $request->get('mode', 'get');
            $mode = !empty($mode) ? $mode : 'post';
        }
        $where = $post['where'];
		if (empty($where)) {
            $where = $request->get('where', 'get');
            $where = !empty($where) ? $where : 'Image';
        }
		
        $callback = $post['callback'];
		if (empty($callback)) {
            $callback = $request->get('callback', 'get');
            $callback = !empty($callback) ? $callback : '';
        }
		
        require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('AddGadget.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'BlogAdminAjax' : 'BlogAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));

        $tpl->SetVariable('gadgets', _t('BLOG_GADGETS'));
        $tpl->SetVariable('actions', _t('BLOG_ACTIONS'));
        $tpl->SetVariable('no_actions_msg', _t('BLOG_NO_GADGET_ACTIONS'));
        
		$addButton =& Piwi::CreateWidget('Button', 'add', _t('BLOG_NEW_CONTENT'), STOCK_ADD);
        if ($mode == 'insert') {
	        $addButton->AddEvent(ON_CLICK, "parent.parent.insertGadgetToLayout($('gadget').value, getSelectedAction(), '".$where."');");
		} else {
	        $addButton->AddEvent(ON_CLICK, "parent.parent.addGadgetToLayout($('gadget').value, getSelectedAction(), ".$linkid.");");
		}
		$tpl->SetVariable('add_button', $addButton->Get());
		
		$saveButton =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$tpl->SetVariable('save_button', $saveButton->Get());
        
        $tpl->SetVariable('first', 'Text');
		//$tpl->SetVariable('addtext_content', $this->A_form((JAWS_SCRIPT == 'admin' ? false : true)));
		$tpl->SetVariable('id', $id);
		$tpl->SetVariable('linkid', $linkid);
		$tpl->SetVariable('callback', $callback);
		$tpl->SetVariable('method', 'AddPost');
		
		$tpl->SetBlock('template/gadget');
		$tpl->SetVariable('id', $id);
		$tpl->SetVariable('linkid', $linkid);
		$tpl->SetVariable('callback', $callback);
		$tpl->SetVariable('method', 'AddPost');
		$tpl->SetVariable('gadget_id', 'Text');
		$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-image-generic.png');
		$tpl->SetVariable('gadget', 'Text/Image');
		$tpl->SetVariable('desc', 'Add text or image content to this post.');
		$tpl->ParseBlock('template/gadget');

		if (JAWS_SCRIPT == 'admin') {
			$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
			$gadget_list = $jms->GetGadgetsList(null, true, true, true);

			//Hold.. if we dont have a selected gadget?.. like no gadgets?
			if (count($gadget_list) <= 0) {
				Jaws_Error::Fatal('You don\'t have any installed gadgets, please enable/install one and then come back',
								 __FILE__, __LINE__);
			}
			
			reset($gadget_list);
			//$first = current($gadget_list);
			foreach ($gadget_list as $gadget) {
				$tpl->SetBlock('template/gadget');
				$tpl->SetVariable('id', $id);
				$tpl->SetVariable('linkid', $linkid);
				$tpl->SetVariable('callback', $callback);
				$tpl->SetVariable('gadget_id', $gadget['realname']);
				$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget['realname'].'/images/logo.png');
				$tpl->SetVariable('gadget', $gadget['name']);
				$tpl->SetVariable('desc', $gadget['description']);
				$tpl->SetVariable('method', 'AddGadget');
				$tpl->ParseBlock('template/gadget');
			}
		}

        $tpl->ParseBlock('template');

        return $tpl->Get();
    }

    /**
     * Save layout element
     *
     * @access public
     * @return template content
     */
    function SaveLayoutElement($linkid)
    {
        //$this->CheckPermission('default');

        $model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');

        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $request =& Jaws_Request::getInstance();
        $fields = array('gadget_field', 'action_field', 'linkid');
        $post = $request->get($fields, 'post');

        // Check that the gadget had an action set.
        if (!empty($post['action_field'])) {
            $model->NewElement($post['gadget_field'], $post['action_field']);
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Blog&action=EditEntry&id='.$post['linkid']);
    }

    /**
     * Changes action of a given gadget
     *
     * @access public
     * @return template content
     */
    function EditElementAction()
    {
		if (JAWS_SCRIPT == 'admin') {
			$GLOBALS['app']->Session->CheckPermission('Blog', 'default');
			$account_prefix = '';
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Blog', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'PublishEntries')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
			$account_prefix = 'account_';
		}
        $model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');

        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'method', 'callback'), 'get');
		
		$id = (int)$get['id'];
		$method = $get['method'];
		$callback = $get['callback'];
		$layoutElement = $model->GetPost($id);
        if (!$layoutElement || !isset($layoutElement['id'])) {
            return false;
        }
        require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('EditGadget.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'BlogAdminAjax' : 'BlogAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('id', $id);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));

        if ($layoutElement['gadget'] != 'text') {
			$actions = $model->GetGadgetActions($layoutElement['gadget']);
			$gInfo = $GLOBALS['app']->LoadGadget($layoutElement['gadget'], 'Info');
			if (!Jaws_Error::IsError($gInfo)) {
				$tpl->SetVariable('display', 'none');
				$tpl->SetVariable('gadget', $layoutElement['gadget']);
				$tpl->SetVariable('gadget_name', $gInfo->GetName());
				$tpl->SetVariable('gadget_description', $gInfo->GetDescription());
				$actionsList =& Piwi::CreateWidget('RadioButtons', 'action_field', 'vertical');
				if (!Jaws_Error::IsError($actions) && count($actions) > 0) {
					foreach ($actions as $action) {
						if (isset($action['action']) && isset($action['name'])) {
							$tpl->SetBlock('template/gadget_action');
							$tpl->SetVariable('name',   $action['name']);
							$tpl->SetVariable('action', $action['action']);
							$tpl->SetVariable('desc',   $action['desc']);
							if($layoutElement['image'] == $action['action']) {
								$tpl->SetVariable('action_checked', 'checked="checked"');
							} else {
								$tpl->SetVariable('action_checked', '');
							}
							$tpl->ParseBlock('template/gadget_action');
						}
					}
				} else {
					$tpl->SetBlock('template/no_action');
					$tpl->SetVariable('no_gadget_desc', _t('BLOG_NO_GADGET_ACTIONS'));
					$tpl->ParseBlock('template/no_action');
				}
			}
		} else {
			$tpl->SetVariable('select_gadget', "selectGadget('Text', '".$method."', '".$id."', '', '".$callback."');");
			$tpl->SetVariable('display', '');
			$tpl->SetVariable('gadget', 'Blog');
			$tpl->SetVariable('gadget_name', 'Text / Image');
			$tpl->SetVariable('gadget_description', 'Add text or image content to this post.');
			$tpl->SetVariable('addtext_content', $this->A_form((JAWS_SCRIPT == 'admin' ? false : true)));
		}

		$btnSave =& Piwi::CreateWidget('Button', 'add', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$url_ea = '../../' . BASE_SCRIPT . '?gadget=Blog&amp;action='.$account_prefix.'EditElementAction&amp;id='.$id;
		$btnSave->AddEvent(ON_CLICK, "parent.parent.saveElementAction(".$id.", getSelectedAction(), '".$url_ea."', '".$layoutElement['gadget']."');");
		$tpl->SetVariable('add', $btnSave->Get());

		$saveButton =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$tpl->SetVariable('save', $saveButton->Get());
        
		$tpl->ParseBlock('template');
        return $tpl->Get();
    }

    /**
     * Quick add form
     *
     * @access public
     * @return XHTML string
     */
    function GetQuickAddForm($account = false)
    {
		//$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		//$GLOBALS['app']->Session->CheckPermission('Blog', 'default');
		//$GLOBALS['app']->ACL->CheckPermission($GLOBALS['app']->Session->GetAttribute('username'), 'Blog', 'PublishEntries');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Blog/templates/');
        $tpl->Load('QuickAddForm.html');
        $tpl->SetBlock('form');

		$request =& Jaws_Request::getInstance();
		$method = $request->get('method', 'get');
		if (empty($method)) {
			$method = 'AddPost';
		}
		$form_content = '';
		switch($method) {
			case "AddPost": 
			case "EditPost": 
				$form_content = $this->A_form($account);
				break;
		}
		if (Jaws_Error::IsError($form_content)) {
			$form_content = $form_content->GetMessage();
		}
        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'BlogAdminAjax' : 'BlogAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));
		
        $tpl->SetVariable('content', $form_content);
		
        $tpl->ParseBlock('form');
        return $tpl->Get();
	}
}
