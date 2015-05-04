<?php
/**
 * Create and manage availability and event Calendars.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

class CalendarInfo extends Jaws_GadgetInfo
{
    function CalendarInfo()
    {
        parent::Init('Calendar');
        $this->GadgetName(_t('CALENDAR_NAME'));
        $this->GadgetDescription(_t('CALENDAR_DESCRIPTION'));
        $this->GadgetVersion('0.1.2');
        //$this->Doc('gadgets/Calendar');
        $this->ListURL(true);

        $acls = array(
            'default',
            'OwnEvent',
            'OwnPublicEvent',
			'OwnCategory',
			'ManageEvents',
			'ManagePublicEvents',
			'ManageCategories'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}
