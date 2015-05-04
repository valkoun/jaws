<?php
/**
 * Search Layout HTML file (for layout purposes)
 *
 * @category   GadgetLayout
 * @package    Search
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class SearchLayoutHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions()
    {
        $actions = array();
        $actions['Box'] = array(
            'mode' => 'LayoutAction',
            'name' => _t('SEARCH_LAYOUT_BOX'),
            'desc' => _t('SEARCH_LAYOUT_BOX_DESCRIPTION')
        );
        $actions['SimpleBox'] = array(
            'mode' => 'LayoutAction',
            'name' => _t('SEARCH_LAYOUT_SIMPLEBOX'),
            'desc' => _t('SEARCH_LAYOUT_SIMPLEBOX_DESCRIPTION')
        );
        $actions['AdvancedBox'] = array(
            'mode' => 'LayoutAction',
            'name' => _t('SEARCH_LAYOUT_ADVANCEDBOX'),
            'desc' => _t('SEARCH_LAYOUT_ADVANCEDBOX_DESCRIPTION')
        );

        return $actions;
    }

    /**
     * Display a search box
     *
     * @access  public
     * @var     boolean $gadgets_combo  Display gadgets combo (optional, default true)
     * @return  string  Searchable box
     */
    function Box($gadgets_combo = true)
    {
        $model = $GLOBALS['app']->LoadGadget('Search', 'Model');
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('all', 'exact', 'least', 'exclude', 'gadgets', 'date'), 'get');

        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Search/resources/style.css', 'stylesheet', 'text/css');
		
        // Clean searchdata
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $post = array_map(array($xss, 'filter'), $post);
		$post = $model->implodeSearch($post);
        $wordAllData = (!empty($post) ? $post : _t('SEARCH_INPUT_TEXT'));
        
		$tpl = new Jaws_Template('gadgets/Search/templates/');
        $tpl->Load('Search.html');
        if ($gadgets_combo) {
            $block = 'Box';
        } else {
            $block = 'SimpleBox';
        }
        $tpl->SetBlock("$block");
		$tpl->SetVariable('actionName', str_replace(' ', '-', _t('SEARCH_LAYOUT_'.strtoupper($block))));
		$tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('title', _t('SEARCH_NAME'));

		$wordAll =& Piwi::CreateWidget('Entry', 'all', $wordAllData);
        $wordAll->AddEvent(ON_BLUR, "if(this.defaultValue==this.value){this.value='';this.style.color='#000000';}else if(this.value==''){this.value=this.defaultValue;this.style.color='#999999';}");
        $wordAll->AddEvent(ON_FOCUS, "if(this.defaultValue==this.value){this.value='';this.style.color='#000000';}else if(this.value==''){this.value=this.defaultValue;this.style.color='#999999';}");
        $tpl->SetVariable('all', $wordAll->Get());

        // Create Select box.
        if ($gadgets_combo) {
            $gadgetList = $model->GetSearchableGadgets();
            $gSearchable = $GLOBALS['app']->Registry->Get('/gadgets/Search/searchable_gadgets');
            $searchableGadgets = ($gSearchable=='*')? array_keys($gadgetList) : explode(', ', $gSearchable);

            $gchk =& Piwi::CreateWidget('Combo', 'gadgets');
            $gchk->setID('Search_gadgets');
            $gchk->addOption(_t('GLOBAL_ALL'), '');
            foreach ($searchableGadgets as $gadget) {
                $info = $GLOBALS['app']->LoadGadget($gadget, 'Info');
                if (Jaws_Error::IsError($info)) {
                    continue;
                }
                $gchk->AddOption($info->GetName(), $gadget);
            }
            $default = !is_null($post['gadgets']) ? $post['gadgets'] : '';
            $gchk->SetDefault($default);
            $tpl->SetVariable('lbl_search_in', _t('SEARCH_SEARCH_IN'));
            $tpl->SetVariable('gadgets_combo', $gchk->Get());
        }

        $btnSearch =& Piwi::CreateWidget('Button', '', _t('SEARCH_BUTTON'));
        $btnSearch->SetID('btn_search');
        $btnSearch->SetSubmit(true);
        $tpl->SetVariable('btn_search', $btnSearch->Get());
        $tpl->ParseBlock("$block");
		
		$tpl->SetBlock("AutoComplete");
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('site_url', $GLOBALS['app']->getSiteURL());
		$tpl->SetVariable('min_chars', $GLOBALS['app']->Registry->Get('/gadgets/Search/min_key_len'));
        $tpl->ParseBlock("AutoComplete");

        return $tpl->Get();
    }

    /**
     * Display a simple search box
     *
     * @access  public
     * @return  string Searchable box
     */
    function SimpleBox()
    {
        return $this->Box(false);
    }

    /**
     * Display the advanced search box
     *
     * @access  public
     * @return  string  Advanced search box (XHTML output)
     */
    function AdvancedBox()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('all', 'exact', 'least', 'exclude', 'gadgets', 'date'), 'get');

        // Clean searchdata
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $post = array_map(array($xss, 'filter'), $post);
       
		$tpl = new Jaws_Template('gadgets/Search/templates/');
        $tpl->Load('Search.html');
        $tpl->SetBlock('AdvancedBox');

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('title', _t('SEARCH_NAME'));
        $tpl->SetVariable('lbl_word_filter', _t('SEARCH_WORD_FILTER'));
        $tpl->SetVariable('lbl_all', _t('SEARCH_WORD_FILTER_ALL'));
        $tpl->SetVariable('lbl_exact', _t('SEARCH_WORD_FILTER_EXACT'));
        $tpl->SetVariable('lbl_least', _t('SEARCH_WORD_FILTER_LEAST'));
        $tpl->SetVariable('lbl_exclude', _t('SEARCH_WORD_FILTER_EXCLUDE'));
        $tpl->SetVariable('lbl_data_filter', _t('SEARCH_DATA_FILTER'));
        $tpl->SetVariable('lbl_search_in', _t('SEARCH_SEARCH_IN'));

        $model 			 = $GLOBALS['app']->LoadGadget('Search', 'Model');
        $options 		 = $model->parseSearch($post, $searchable);
        $wordAllData     = implode(' ', $options['all']);
		$wordAllData	 = (!empty($wordAllData) ? $wordAllData : _t('SEARCH_INPUT_TEXT_ADVANCED'));
        $wordExactData   = implode(' ', $options['exact']);
 		$wordExactData	 = (!empty($wordExactData) ? $wordExactData : '');
        $wordLeastData   = implode(' ', $options['least']);
 		$wordLeastData	 = (!empty($wordLeastData) ? $wordLeastData : '');
        $wordExcludeData = implode(' ', $options['exclude']);
 		$wordExcludeData = (!empty($wordExcludeData) ? $wordExcludeData : '');

        $wordAll =& Piwi::CreateWidget('Entry', 'all', $wordAllData);
        $wordExact->setID('all');
        $wordExact =& Piwi::CreateWidget('Entry', 'exact', $wordExactData);
        $wordExact->setID('Search_exact');
        $wordLeast =& Piwi::CreateWidget('Entry', 'least', $wordLeastData);
        $wordLeast->setID('Search_least');
        $wordExclude =& Piwi::CreateWidget('Entry', 'exclude', $wordExcludeData);
        $wordExclude->setID('Search_exclude');
        $tpl->SetVariable('all', $wordAll->Get());
        $tpl->SetVariable('exclude', $wordExclude->Get());
        $tpl->SetVariable('least', $wordLeast->Get());
        $tpl->SetVariable('exact', $wordExact->Get());

        //Gadgets filter combo
        $gadgetList = $model->GetSearchableGadgets();
        $gSearchable = $GLOBALS['app']->Registry->Get('/gadgets/Search/searchable_gadgets');
        $searchableGadgets = ($gSearchable=='*')? array_keys($gadgetList) : explode(', ', $gSearchable);

        $gchk =& Piwi::CreateWidget('Combo', 'gadgets');
        $gchk->addOption(_t('GLOBAL_ALL'), '');
        foreach ($searchableGadgets as $gadget) {
            $info = $GLOBALS['app']->LoadGadget($gadget, 'Info');
            if (Jaws_Error::IsError($info)) {
                continue;
            }
            $gchk->AddOption($info->GetName(), $gadget);
        }
        $default = !is_null($post['gadgets']) ? $post['gadgets'] : '';
        $gchk->SetDefault($default);

        $tpl->SetVariable('gadgets_combo', $gchk->Get());

        //Search button
        $btnSearch =& Piwi::CreateWidget('Button', '', _t('SEARCH_BUTTON'));
        $btnSearch->SetID('btn_search');
        $btnSearch->SetSubmit(true);
        $tpl->SetVariable('btn_search', $btnSearch->Get());

        $tpl->ParseBlock('AdvancedBox');
        return $tpl->Get();
    }
}
