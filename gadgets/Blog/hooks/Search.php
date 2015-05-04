<?php
/**
 * Blog - Search gadget hook
 *
 * @category   GadgetHook
 * @package    Blog
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class BlogSearchHook
{
    /**
     * Gets the gadget's search fields
     */
    function GetSearchFields() {
        return array(
                    array('[[blog]].[title]', '[[blog]].[summary]', '[[blog]].[text]'),
                    );
    }

    /**
     * Returns an array with the results of a search
     *
     * @access  public
     * @param   string  $pSql  Prepared search (WHERE) SQL
     * @return  array   An array of entries that matches a certain pattern
     */
    function Hook($pSql = '', $limit = null)
    {
        $params = array('published' => true);

        $sql = '
            SELECT
                [id],
                [title],
                [fast_url],
                [text],
                [createtime],
                [updatetime]
            FROM [[blog]]
            WHERE
                [published] = {published}
              AND
                [createtime] <= {now}
            ';

        $sql .= ' AND ' . $pSql;
        $sql .= ' ORDER BY [createtime] DESC';

        $params['now']       = $GLOBALS['db']->Date();
        $params['published'] = true;

        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return array();
        }

        $date = $GLOBALS['app']->loadDate();
        $entries = array();
        foreach ($result as $r) {
            $entry = array();
            $entry['title'] = $r['title'];
            if (empty($r['fast_url'])) {
                $url = $GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $r['id']));
            } else {
                $url = $GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $r['fast_url']));
            }
            $entry['url'] = $url;
            //FIXME: Will be great if we can get the first image in "text"
            $entry['image'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/Blog/images/logo.png';
            //FIXME: Get snippet
            $text = $r['text'];
            if (strpos($r['text'], '[more]') !== false) {
                $post = explode('[more]', $text);
                $text = $post[0]."... [<a href=\"".$url."#more\">"._t('BLOG_READ_MORE')."</a>]";
            }
            $entry['snippet'] = $text;
            $entry['date']    = $date->ToISO($r['createtime']);

            $stamp = str_replace(array('-', ':', ' '), '', $r['createtime']);
            $entries[$stamp] = $entry;
        }

        return $entries;
    }
}
