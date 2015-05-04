<?php
/**
 * VisitCounter Gadget
 *
 * @category   GadgetInfo
 * @package    VisitCounter
 * @author     Jorge A Gallegos <kad@gulags.org.mx>
 * @author     Jon Wood <jon@jellybob.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class VisitCounterInfo extends Jaws_GadgetInfo
{
    function VisitCounterInfo()
    {
        parent::Init('VisitCounter');
        $this->GadgetName(_t('VISITCOUNTER_NAME'));
        $this->GadgetDescription(_t('VISITCOUNTER_DESC'));
        $this->GadgetVersion('0.8.1');
        $this->Doc('gadget/VisitCounter');

        $acls = array(
            'default',
            'ResetCounter',
            'CleanEntries',
            'UpdateProperties'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}