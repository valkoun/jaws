<?php
/**
 * Visit Counter Gadget
 *
 * @category   GadgetModel
 * @package    VisitCounter
 * @author     Jorge A Gallegos <kad@gulags.org.mx>
 * @author     Jon Wood <jon@jellybob.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class VisitCounterModel extends Jaws_Model
{
    /**
     * Get the list of IP visitors / date visited
     *
     * @access  public
     * @return  array   An array of visitors and Jaws_Error on failure
     */
    function GetVisitors($limit = null)
    {
        $sql = '
            SELECT
                [ip], [visit_date], [visits]
            FROM [[ipvisitor]]';
        if (!is_null($limit)) {
            $sql .= ' ORDER BY [visit_date] DESC';

            $result = $GLOBALS['db']->setLimit(15, $limit);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error($result->getMessage(), 'SQL');
            }
        }

        $result = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error($result->getMessage(), 'SQL');
        }

        return $result;
    }

    /**
     * Returns the hostname of an IP address using a reverse lookup.
     *
     * WARNING: This may cause delays if DNS isn't setup properly, don't use it anywhere
     *          performance is a major issue.
     *
     * @access public
     * @return string The hostname of the remote machine, or Jaws_Error if no reverse lookup could be done.
     */
    function GetHostname($ip)
    {
        $hostname = gethostbyaddr($ip);
        if ($hostname == $ip) {
            return new Jaws_Error(_t('VISITCOUNTER_ERROR_CANT_REVERSE_HOSTNAME', $ip), _t('VISITCOUNTER_NAME'));
        }

        return $hostname;
    }

    /**
     * Add a new visitor to the table
     *
     * @access  public
     * @param   string  $ip  IP of the visitor
     * @return  boolean True if query was successful, otherwise returns Jaws_Error
     */
    function AddVisitor($ip, $inc = true)
    {
        $params         = array();
        $params['date'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $params['ip']   = $ip;
        $params['now']  = $GLOBALS['db']->Date();
        $params['step'] = $inc ? 1 : 0;

        $sql = '
            SELECT
                COUNT(*)
            FROM [[ipvisitor]]
            WHERE
                [ip] = {ip} AND [visit_date] >= {date}';

        $visited = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($visited)) {
            return new Jaws_Error(_t('VISITCOUNTER_ERROR_CANT_ADD_VISIT', $ip), _t('VISITCOUNTER_NAME'));
        }

        if (isset($visited) && $visited > 0) {
            $sql = '
                UPDATE [[ipvisitor]] SET
                    [visit_date] = {now},
                    [visits]     = [visits] + {step}
                WHERE
                    [ip] = {ip} AND [visit_date] >= {date}';
        } else {
            $sql = '
                INSERT INTO [[ipvisitor]]
                    ([ip], [visit_date], [visits])
                VALUES
                    ({ip}, {now}, 1)';
        }

        $result = $GLOBALS['db']->query($sql,  $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('VISITCOUNTER_ERROR_CANT_ADD_VISIT', $ip), _t('VISITCOUNTER_NAME'));
        }

        return true;
    }

    /**
     * Gets the period of the counter cookie
     *
     * @access  public
     * @return  int      Number of days.
     */
    function GetCookiePeriod()
    {
        $rs = $GLOBALS['app']->Registry->Get('/gadgets/VisitCounter/period');
        if (Jaws_Error::IsError($rs)) {
            $rs = 5;
        }

        return $rs;
    }

    /**
     * Gets the timeout(second)  of online visitors
     *
     * @access  public
     * @return  int.
    */
    function GetOnlineVisitorsTimeout()
    {
        $rs = $GLOBALS['app']->Registry->Get('/gadgets/VisitCounter/timeout');
        if (!$rs || Jaws_Error::IsError($rs)) {
            $rs = 600;
        }

        return $rs;
    }

    /**
     * Gets the initial date for the visit counter
     *
     * @access  public
     * @return  string  The date of start date
     */
    function GetStartDate()
    {
        $rs = $GLOBALS['app']->Registry->Get('/gadgets/VisitCounter/start');
        if (!$rs || Jaws_Error::IsError($rs)) {
            $rs = date('Y-m-d H:i:s');
        }

        return $rs;
    }

    /**
     *Gets the number of online visitors.
     *
     * @access  public
     * @return  int     The number of online visitors by IP or Jaws_Error on failure
    */
    function GetOnlineVisitors()
    {
        $params = array();
        $params['date'] = date('Y-m-d H:i:s', time() - $this->GetOnlineVisitorsTimeout());
        $sql = '
            SELECT COUNT(*)
            FROM [[ipvisitor]]
            WHERE [visit_date] >= {date}';

        $onlinevisitors = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($onlinevisitors)) {
            return new Jaws_Error($onlinevisitors->getMessage(), 'SQL');
        }

        if (isset($onlinevisitors)) {
            return(int)$onlinevisitors;
        }

        return 0;
    }

    /**
     * Gets the number of visitors that today visits the site.
     *
     * @access  public
     * @return  int     The number of today visitors or Jaws_Error on failure
    */
    function GetTodayVisitors($type = null)
    {
        $params = array();
        $params['date'] = $GLOBALS['app']->UTC2UserTime(mktime(0, 0, 0, date("m"), date("d"), date("Y")), 'Y-m-d H:i:s');

        if (is_null($type)) {
            $type = $this->GetVisitType();
        }

        if ($type == 'unique') {
            $sql = '
                SELECT COUNT([ip])
                FROM (SELECT DISTINCT [ip] FROM [[ipvisitor]] WHERE [visit_date] >= {date}) AS visitors';
        } else {
            $sql = '
                SELECT SUM([visits])
                FROM [[ipvisitor]]
                WHERE [visit_date] >= {date}';
        }

        $visits = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($visits)) {
            /**
             * we shouldn't be giving back an error here since the parser is expecting a int and well
             * if a users hits an error he gets presented by a pretty object to string conversion error
             */
            return new Jaws_Error($visits->getMessage(), 'SQL');
        }

        if (isset($visits)) {
            return(int)$visits;
        }
        return 0;
    }

    /**
     * Gets the number of total visitors that visits the site since start date.
     *
     * @access  public
     * @return  int     The number of total visitors or Jaws_Error on failure
    */
    function GetTotalVisitors($type = null)
    {
        $params = array();
        $params['date'] = $this->GetStartDate();

        if (is_null($type)) {
            $type = $this->GetVisitType();
        }

        if ($type == 'unique') {
            $sql = '
                SELECT COUNT([ip])
                FROM (SELECT DISTINCT [ip] FROM [[ipvisitor]] WHERE [visit_date] >= {date}) AS visitors';
        } else {
            $sql = '
                SELECT SUM([visits])
                FROM [[ipvisitor]]
                WHERE [visit_date] >= {date}';
        }

        $visits = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($visits)) {
            /**
             * we shouldn't be giving back an error here since the parser is expecting a int and well
             * if a users hits an error he gets presented by a pretty object to string conversion error
             */
            return new Jaws_Error($visits->getMessage(), 'SQL');
        }

        if (isset($visits)) {
            return(int)$visits;
        }
        return 0;
    }

    /**
     * Gets the type of visits to be displayed.
     *
     * @access public
     * @return string   The type of visits being displayed.
     */
    function GetVisitType()
    {
        $type = $GLOBALS['app']->Registry->Get('/gadgets/VisitCounter/type');
        if (!$type || Jaws_Error::IsError($type)) {
            return 'unique';
        }

        return $type;
    }
}