<?php
/**
 * Visit Counter Gadget Admin
 *
 * @category   GadgetModel
 * @package    VisitCounter
 * @author     Jorge A Gallegos <kad@gulags.org.mx>
 * @author     Jon Wood <jon@jellybob.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'gadgets/VisitCounter/Model.php';

class VisitCounterAdminModel extends VisitCounterModel
{
    /**
     * Install the gadget
     *
     * @access  public
     * @return  boolean  True on success and Jaws_Error on failure
     */
    function InstallGadget()
    {
        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/visit_counters',  'online,today,total');
        $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/timeout', '600');
        $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/type', 'impressions');
        $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/period', '0');
        $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/start', date('Y-m-d H:i:s'));
        $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/mode', 'text');
        $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/custom_text', 
                                          '<strong>Total Visitors:</strong> <font color="red">{total}</font>');

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UninstallGadget()
    {
        $result = $GLOBALS['db']->dropTable('ipvisitor');
        if (Jaws_Error::IsError($result)) {
            $gName  = _t('VISITCOUNTER_NAME');
            $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
            $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
            return new Jaws_Error($errMsg, $gName);
        }

        //registry keys
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/visit_counters');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/timeout');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/type');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/period');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/start');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/mode');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/custom_text');

        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UpdateGadget($old, $new)
    {
        $result = $this->installSchema('schema.xml', '', "$old.xml");
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (version_compare($old, '0.8.0', '<')) {
            // Registry keys.
            $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/visit_counters',  'online,today,total');
            $GLOBALS['app']->Registry->NewKey('/gadgets/VisitCounter/custom_text', 
                                              $GLOBALS['app']->Registry->Get('/gadgets/VisitCounter/custom'));
            $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/online');
            $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/today');
            $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/total');
            $GLOBALS['app']->Registry->DeleteKey('/gadgets/VisitCounter/custom');
        }

        // fix using Y-m-d G:i:s instead of Y-m-d H:i:s in version 0.6.x
        $startDate = $GLOBALS['app']->Registry->Get('/gadgets/VisitCounter/start');
        if (strlen($startDate) == 18) {
            $startDate = substr_replace($startDate, '0', 11, 0);
            $GLOBALS['app']->Registry->Set('/gadgets/VisitCounter/start', $startDate);
        }

        return true;
    }

    /**
     * Clears the visitors table
     *
     * @access  private
     * @return  boolean True if change was successful, otherwise returns Jaws_Error
     */
    function ClearVisitors()
    {
        $sql    = 'DELETE FROM [[ipvisitor]]';
        $result = $GLOBALS['db']->query($sql);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('VISITCOUNTER_ERROR_VISITORS_NOT_CLEARED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('VISITCOUNTER_ERROR_VISITORS_NOT_CLEARED'), _t('VISITCOUNTER_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('VISITCOUNTER_VISITORS_CLEARED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Resets the counter to zero
     *
     * @access  public
     * @return  boolean True if change was successful, otherwise returns Jaws_Error
     */
    function ResetCounter()
    {
        if (!Jaws_Error::IsError($this->ClearVisitors())) {
            $sql = 'UPDATE [[ipvisitor]] SET [visits] = 0';
            $result = $GLOBALS['db']->query($sql);
            if (Jaws_Error::IsError($result)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('VISITCOUNTER_ERROR_COUNTER_NOT_RESETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('VISITCOUNTER_ERROR_COUNTER_NOT_RESETED'), _t('VISITCOUNTER_NAME'));
            }

            $GLOBALS['app']->Session->PushLastResponse(_t('VISITCOUNTER_COUNTER_RESETED'), RESPONSE_NOTICE);
            return true;
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('VISITCOUNTER_ERROR_COUNTER_NOT_RESETED'), RESPONSE_ERROR);
        return new Jaws_Error(_t('VISITCOUNTER_ERROR_COUNTER_NOT_RESETED'), _t('VISITCOUNTER_NAME'));
    }

    /**
     * Sets the properties of VisitCounter
     *
     * @access  public
     * @param   int     $numdays Number of days
     * @param   string  $type    The type of visits being displayed
     * @return  boolean True if change was successful, otherwise returns Jaws_Error
     */
    function UpdateProperties($online, $today, $total, $custom, $numdays, $type, $mode, $custom_text='')
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        if ($online) {
            $visit_counters[] = 'online';
        }
        if ($today) {
            $visit_counters[] = 'today';
        }
        if ($total) {
            $visit_counters[] = 'total';
        }
        if ($custom) {
            $visit_counters[] = 'custom';
        }
        $rs1 = $GLOBALS['app']->Registry->Set('/gadgets/VisitCounter/visit_counters', implode(',', $visit_counters));
        $rs2 = $GLOBALS['app']->Registry->Set('/gadgets/VisitCounter/period', $numdays);
        $rs3 = $GLOBALS['app']->Registry->Set('/gadgets/VisitCounter/type',   $type);
        $rs4 = $GLOBALS['app']->Registry->Set('/gadgets/VisitCounter/mode', $mode);
        $rs5 = $GLOBALS['app']->Registry->Set('/gadgets/VisitCounter/custom_text', $xss->parse($custom_text));
        if ($rs1 && $rs2 && $rs3 && $rs4 && $rs5) {
            $GLOBALS['app']->Session->PushLastResponse(_t('VISITCOUNTER_PROPERTIES_UPDATED'), RESPONSE_NOTICE);
            $GLOBALS['app']->Registry->Commit('VisitCounter');
            return true;
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('VISITCOUNTER_ERROR_PROPERTIES_UPDATED'), RESPONSE_ERROR);
        return new Jaws_Error(_t('VISITCOUNTER_ERROR_PROPERTIES_UPDATED'), _t('VISITCOUNTER_NAME'));
    }

    /**
     * Sets the initial date for the visit counter
     *
     * @access  public
     * @param   string   $date StartDate
     * @return  boolean  True/Jaws_Error
     */
    function SetStartDate($date)
    {
        $rs = $GLOBALS['app']->Registry->Set('/gadgets/VisitCounter/start', $date);
        if (!$rs || Jaws_Error::IsError($rs)) {
            return new Jaws_Error(_t('VISITCOUNTER_ERROR_COULD_NOT_CHANGE_STARTDATE'), _t('VISITCOUNTER_NAME'));
        }
        $GLOBALS['app']->Registry->Commit('VisitCounter');
        return true;
    }
}
?>
