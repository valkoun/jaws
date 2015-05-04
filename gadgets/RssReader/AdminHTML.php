<?php
/**
 * RssReader Gadget Action
 *
 * @category   GadgetAdmin
 * @package    RssReader
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh  <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class RssReaderAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function RssReaderAdminHTML()
    {
        $this->Init('RssReader');
    }

    /**
     * Prepares the data of rss sites
     *
     * @access  public
     * @param   int    $limit  Limit of data
     * @return  array  Data
     */
    function GetRSSSites($offset = null)
    {
        $model = $GLOBALS['app']->LoadGadget('RssReader', 'AdminModel');

        $rsssites = $model->GetRSSs(false, 12, $offset);
        if (Jaws_Error::IsError($rsssites)) {
            return array();
        }

        $newData = array();
        foreach ($rsssites as $site) {
            $siteData = array();
            $siteData['id']    = $site['id'];
            $siteData['title'] = '<span style="white-space: nowrap;"><a href="'.$site['url'].'" title="'.$site['url'];
            $siteData['title'].= '" target="_blank" style="text-decoration: none;">'.$site['title'].'</a></span>';
            $siteData['visible'] = ($site['visible']?_t('GLOBAL_YES') : _t('GLOBAL_NO'));
            $actions = '';
            if ($this->GetPermission('ManageRSSSite')) {
                $link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
                                            "javascript: editRSS(this, '".$site['id']."');",
                                            STOCK_EDIT);
                $actions.= $link->Get().'&nbsp;';
            }
            if ($this->GetPermission('ManageRSSSite')) {
                $link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                                            "javascript: deleteRSS(this, '".$site['id']."');",
                                            STOCK_DELETE);
                $actions.= $link->Get().'&nbsp;';
            }
            $siteData['actions'] = $actions;
            $newData[] = $siteData;
        }
        return $newData;
    }

    /**
     * Prepares the datagrid view (XHTML of datagrid)
     *
     * @access  public
     * @return  string XHTML of datagrid
     */
    function DataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('RssReader', 'AdminModel');
        $total = $model->TotalOfData('rss_sites');

        $datagrid =& Piwi::CreateWidget('DataGrid', array());
        $datagrid->TotalRows($total);
        $datagrid->pageBy(12);
        $datagrid->SetID('rsssites_datagrid');
        $column1 = Piwi::CreateWidget('Column', _t('GLOBAL_ID'));
        $column1->SetStyle('width: 32px; white-space:nowrap;');
        $datagrid->AddColumn($column1);
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $column3 = Piwi::CreateWidget('Column', _t('GLOBAL_VISIBLE'), null, false);
        $column3->SetStyle('width: 56px; white-space:nowrap;');
        $datagrid->AddColumn($column3);
        $column4 = Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS'), null, false);
        $column4->SetStyle('width: 60px; white-space:nowrap;');
        $datagrid->AddColumn($column4);
        $datagrid->SetStyle('margin-top: 0px; width: 100%;');

        return $datagrid->Get();
    }

    /**
     * Prints the RSSreader administration
     *
     * @access  public
     * @return  string HTML content of administration
     */
    function Admin()
    {
        $this->CheckPermission('default');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/RssReader/templates/');
        $tpl->Load('AdminRssReader.html');
        $tpl->SetBlock('rssreader');

        $tpl->SetVariable('grid', $this->DataGrid());
        $tpl->SetVariable('dir', _t('GLOBAL_LANG_DIRECTION'));

        // Tabs titles
        $tpl->SetVariable('legend_title', _t('GLOBAL_PROPERTIES'));

        $titleentry =& Piwi::CreateWidget('Entry', 'title', '');
        $titleentry->SetID('title');
        $titleentry->setStyle('width: 192px;');
        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $tpl->SetVariable('title', $titleentry->Get());

        $urlentry =& Piwi::CreateWidget('Entry', 'url', 'http://');
        $urlentry->SetID('url');
        $urlentry->setStyle('width: 192px;');
        $tpl->SetVariable('lbl_url', _t('GLOBAL_URL'));
        $tpl->SetVariable('url', $urlentry->Get());

        $cachecombo =& Piwi::CreateWidget('Combo', 'cache_time');
        $cachecombo->SetID('cache_time');
        $cachecombo->setStyle('width: 128px;');
        $cachecombo->AddOption(_t('GLOBAL_DISABLE'),              0);
        $cachecombo->AddOption(_t('GLOBAL_DATE_MINUTES', 10),   600);
        $cachecombo->AddOption(_t('GLOBAL_DATE_MINUTES', 30),  1800);
        $cachecombo->AddOption(_t('GLOBAL_DATE_HOURS',   1),   3600);
        $cachecombo->AddOption(_t('GLOBAL_DATE_HOURS',   5),  18000);
        $cachecombo->AddOption(_t('GLOBAL_DATE_HOURS',   10), 36000);
        $cachecombo->AddOption(_t('GLOBAL_DATE_DAYS',    1),  86400);
        $cachecombo->AddOption(_t('GLOBAL_DATE_WEEKS',   1), 604800);
        $cachecombo->SetDefault(3600);
        $tpl->SetVariable('lbl_cache_time', _t('RSSREADER_CACHE_TIME'));
        $tpl->SetVariable('cache_time', $cachecombo->Get());

        $viewType =& Piwi::CreateWidget('Combo', 'view_type');
        $viewType->SetID('view_type');
        $viewType->setStyle('width: 128px;');
        $viewType->AddOption(_t('RSSREADER_VIEW_TYPE_SIMPLE'),        0);
        $viewType->AddOption(_t('RSSREADER_VIEW_TYPE_MARQUEE_UP'),    1);
        $viewType->AddOption(_t('RSSREADER_VIEW_TYPE_MARQUEE_DOWN'),  2);
        $viewType->AddOption(_t('RSSREADER_VIEW_TYPE_MARQUEE_LEFT'),  3);
        $viewType->AddOption(_t('RSSREADER_VIEW_TYPE_MARQUEE_RIGHT'), 4);
        $tpl->SetVariable('lbl_view_type', _t('RSSREADER_VIEW_TYPE'));
        $tpl->SetVariable('view_type', $viewType->Get());

        $titleViewcombo =& Piwi::CreateWidget('Combo', 'title_view');
        $titleViewcombo->SetID('title_view');
        $titleViewcombo->setStyle('width: 128px;');
        $titleViewcombo->AddOption(_t('RSSREADER_TITLE_VIEW_DISABLE'),  0);
        $titleViewcombo->AddOption(_t('RSSREADER_TITLE_VIEW_INTERNAL'), 1);
        $titleViewcombo->AddOption(_t('RSSREADER_TITLE_VIEW_EXTERNAL'), 2);
        $tpl->SetVariable('lbl_title_view', _t('RSSREADER_TITLE_VIEW'));
        $tpl->SetVariable('title_view', $titleViewcombo->Get());

        $countentry =& Piwi::CreateWidget('Entry', 'count_entry', '');
        $countentry->setStyle('width: 120px;');
        $tpl->SetVariable('lbl_count_entry', _t('RSSREADER_SITE_COUNT_ENTRY'));
        $tpl->SetVariable('count_entry', $countentry->Get());

        $visibleType =& Piwi::CreateWidget('Combo', 'visible');
        $visibleType->SetID('visible');
        $visibleType->setStyle('width: 128px;');
        $visibleType->AddOption(_t('GLOBAL_NO'),  '0');
        $visibleType->AddOption(_t('GLOBAL_YES'), '1');
        $visibleType->SetDefault('1');
        $tpl->SetVariable('lbl_visible', _t('GLOBAL_VISIBLE'));
        $tpl->SetVariable('visible', $visibleType->Get());

        if ($this->GetPermission('ManageRSSSite')) {
            $btncancel =& Piwi::CreateWidget('Button', 'btn_cancel', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
            $btncancel->SetStyle('visibility: hidden;');
            $btncancel->AddEvent(ON_CLICK, 'stopAction();');
            $tpl->SetVariable('btn_cancel', $btncancel->Get());

            $btnsave =& Piwi::CreateWidget('Button', 'btn_save',
                                           _t('GLOBAL_SAVE'), STOCK_SAVE);
            $btnsave->AddEvent(ON_CLICK, 'updateRSS();');
            $tpl->SetVariable('btn_save', $btnsave->Get());
        }

        $tpl->SetVariable('incompleteFeedFields', _t('RSSREADER_INCOMPLETE_FIELDS'));
        $tpl->SetVariable('confirmFeedDelete',    _t('RSSREADER_CONFIRM_DELETE_FEED'));

        $tpl->ParseBlock('rssreader');

        return $tpl->Get();
    }
}