<?php
/**
 * Faq - Search gadget hook
 *
 * @category   GadgetHook
 * @package    Faq
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class FaqSearchHook
{
    /**
     * Gets the gadget's search fields
     */
    function GetSearchFields() {
        return array(
                    array('[question]', '[answer]'),
                    );
    }

    /**
     * Returns an array with the results of a search
     *
     * @access  public
     * @param   string  $pSql  Prepared search (WHERE) SQL
     * @return  array   An array of entries that matches a certain pattern
     */
    function Hook($pSql = '')
    {
        $params = array('active' => true);

        $sql = '
            SELECT
                [category],
                [question],
                [answer],
                [faq_position],
                [updatetime]
            FROM [[faq]]
            WHERE [published] = {active}
            ';

        $sql .= ' AND ' . $pSql;
        $sql .= ' ORDER BY [createtime] DESC';

        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return array();
        }

        $questions  = array();
        $date = $GLOBALS['app']->loadDate();
        foreach ($result as $r) {
            $question = array();
            $question['title']   = $r['question'];
            $question['url']     = $GLOBALS['app']->Map->GetURLFor('Faq', 'ViewCategory', 
                                                                   array('id' => $r['category'])).
                '#Question'.$r['faq_position'];
            $question['image']   = 'gadgets/Faq/images/logo.png';
            $question['snippet'] = $r['answer'];
            $question['date']    = $date->ToISO($r['updatetime']);
            $questions[] = $question;
        }

        return $questions;
    }
}
