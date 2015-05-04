<?php
/**
 * Poll Gadget
 *
 * @category   GadgetInfo
 * @package    Poll
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class PollInfo extends Jaws_GadgetInfo
{
    function PollInfo()
    {
        parent::Init('Poll');
        $this->GadgetName(_t('POLL_NAME'));
        $this->GadgetDescription(_t('POLL_DESCRIPTION'));
        $this->GadgetVersion('0.8.0');
        $this->Doc('gadget/Poll');

        $acls = array(
            'default',
            'ManagePolls',
            'ManageGroups',
            'ViewReports',
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}
