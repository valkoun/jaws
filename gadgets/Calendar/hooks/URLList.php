<?php
/**
 * Calendar - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CalendarURLListHook
{
    /**
     * Returns an array with all available items the Calendar gadget 
     * can use
     *
     * @access  public
     */
    function Hook()
    {
        $urls = array();
        return $urls;
    }

	 /**
     * Returns the URL that is used to edit gadget's page 
     * @param   string  $action  Action
     * @param   int  $in  id of record we want to look for
     * @param   bool     $layout   is this a layout action?
     *
     * @access  public
     */
    function GetEditPage($action = null, $id = null, $layout = false)
    {
		if ((!is_null($action) && !empty($action))) {
			if ($action == 'Detail') {
				return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Calendar&action=A_form'.(!is_null($id) ? '&id='.$id : '');
			} else {
				return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Calendar&action=A'.(!is_null($id) ? '&linkid='.$id : '');
			}
		}
		return false;
	}

    /**
     * Returns an array with all Quick Add Forms of Gadget 
     * can use
     *
     * @access  public
     */
    function GetQuickAddForms($account = false)
    {
        $result   = array();
		$GLOBALS['app']->Registry->LoadFile('Calendar');
		$GLOBALS['app']->Translate->LoadTranslation('Calendar', JAWS_GADGET);
        if ($account === false) {
			$result[] = array('name'   => _t('CALENDAR_QUICKADD_ADDCALENDARPARENT'),
							'method' => 'AddCalendar');
		}
		$result[] = array('name'   => _t('CALENDAR_QUICKADD_ADDEVENT'),
                        'method' => 'AddEvent');
		
        return $result;
    }
}
