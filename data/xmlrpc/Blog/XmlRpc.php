<?php
/**
 * Blog XML RPC
 * APIs
 * - Blogger
 *   More Info: http://www.blogger.com/developers/api/
 *          http://plant.blogger.com/api and http://groups.yahoo.com/group/bloggerDev/
 * - metaweblog
 *    http://www.xmlrpc.com/metaWeblogApi
 *    http://www.xmlrpc.com/stories/storyReader$2460
 *
 * @author     Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author     Jonathan Hernandez  <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2008 Jaws Development Group
 * @package Blog
 */
require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
require_once JAWS_PATH . 'include/Jaws/User.php';
require_once JAWS_PATH . 'libraries/pear/' . 'XML/RPC/Server.php';

/**
 * Get Blog ACL permission for a specified user
 */
function GetBlogPermission($user, $task, $user_type)
{
    if ($user_type == 0) {
        return true;
    }

	$model = new Jaws_User;
    $groups = $model->GetGroupsOfUsername($user);
    if (Jaws_Error::IsError($groups)) {
        return false;
    }

    $groups = array_map(create_function('$row','return $row["group_id"];'), $groups);
    return $GLOBALS['app']->ACL->GetFullPermission($user, $groups, 'Blog', $task);
}

/**
 * Aux functions
 */
function getScalarValue($p, $i)
{
    $r = $p->getParam($i);
    if (!XML_RPC_Value::isValue($r)) {
        return false;
        //return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, 'fubar user param');
    }

    return $r->scalarval();
}

function parseContent($content)
{
	$encoding = mb_detect_encoding( $content, "auto" );
	$content = str_replace( "?", "__question__mark__", $content );
	$content = mb_convert_encoding( $content, 'ASCII', $encoding);
	$content = str_replace( "?", "", $content );
	$content = str_replace( "__question__mark__", "?", $content );
	$content = mb_convert_encoding( $content, 'UTF-8', 'ASCII');

    $content = htmlentities($content, ENT_NOQUOTES, 'UTF-8');
    $in  = array('&gt;', '&lt;', '&quot;', '&amp;');
    $out = array('>', '<', '"', '&');
    $content = str_replace($in, $out, $content);

    return $content;
}

/*
 * blogger.newPost
 */
function blogger_newPost($params)
{
    // parameters
    $appKey     = getScalarValue($params, 0); // App Key, deprecated
    $blogToPost = getScalarValue($params, 1); // blog gadget only supports 1 blog, so this parameter is not used.
    $user       = getScalarValue($params, 2);
    $password   = getScalarValue($params, 3);
    $content    = getScalarValue($params, 4);
    $publish    = getScalarValue($params, 5);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'AddEntries', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $content = parseContent($content);

    // predefined values for new post, 'cause isn't suported by blogger api.
    $category = array($GLOBALS['app']->Registry->Get('/gadgets/Blog/default_category'));
    // Allow Comments ?
    $allow_c = $GLOBALS['app']->Registry->Get('/gadgets/Blog/allow_comments');
    $allow_c = $allow_c == 'true' ?  1 : 0;

    // Set title (date)
    $date = $GLOBALS['app']->loadDate();
    $title = $date->format(time());

    $post_id = $model->NewEntry($userInfo['id'], $category, $title, $content, $title, $allow_c, '', $publish);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * blogger.editPost
 */
function blogger_editPost($params)
{
    // parameters
    $post_id  = getScalarValue($params, 1);
    $user     = getScalarValue($params, 2);
    $password = getScalarValue($params, 3);
    $content  = getScalarValue($params, 4);
    $publish  = getScalarValue($params, 5);

    $content = parseContent($content);

    //Get its category..
    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $o_entry = $model->GetEntry($post_id);
    $categories = $o_entry['categories'];
    $title      = $o_entry['title'];
    $allow_c    = $o_entry['allow_comments'];
    // Set title (date)
    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'AddEntries', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $blog_result = $model->UpdateEntry($post_id, $categories, $title, $content, '', $allow_c, '', $publish);
    if (Jaws_Error::IsError ($blog_result)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $blog_result->GetMessage());
    }

    return new XML_RPC_Response(new XML_RPC_Value('1', 'boolean'));
}

/*
 * blogger.deletePost
 */
function blogger_deletePost($params)
{
    // parameters
    $post_id  = getScalarValue($params, 1);
    $user     = getScalarValue($params, 2);
    $password = getScalarValue($params, 3);
    $publish  = getScalarValue($params, 4);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'DeleteEntries', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $res = $model->DeleteEntry($post_id);
    if (Jaws_Error::IsError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }

    $val = new XML_RPC_Value('1', 'boolean');
    return new XML_RPC_Response($val);
}


/*
 * blogger.getUsersBlogs
 */
function blogger_getUsersBlogs($params)
{
    // parameters
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $struct = array();
    $siteurl = $GLOBALS['app']->GetSiteURL();
    $sitename = $GLOBALS['app']->Registry->Get('/config/site_name');

    $data = array(
        'isAdmin'  => new XML_RPC_Value('1', 'boolean'),
        'url'      => new XML_RPC_Value($siteurl),
        'blogid'   => new XML_RPC_Value('1'),
        'blogName' => new XML_RPC_Value($sitename)
    );
    $struct[]  = new XML_RPC_Value($data, 'struct');
    $data = array($struct[0]);
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 * blogger.getUserInfo
 */
function blogger_getUserInfo($params)
{
    // parameters
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);
    if (!$user || !$password) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, 'fubar user param');
    }

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $siteurl = $GLOBALS['app']->GetSiteURL();
    $user = Jaws_User::GetUserInfoById($userInfo['id'], true, true);
    $data = array(
        'nickname'  => new XML_RPC_Value($user['username']),
        'userid'    => new XML_RPC_Value($user['id']),
        'url'       => new XML_RPC_Value($siteurl),
        'email'     => new XML_RPC_Value($user['email']),
        'lastname'  => new XML_RPC_Value($user['lname']),
        'firstName' => new XML_RPC_Value($user['fname']),
    );
    $struct = new XML_RPC_Value($data, 'struct');
    return new XML_RPC_Response($struct);
}

/*
 * blogger.getPost
 */
function blogger_getPost($params)
{
    //parameters
    $post_id  = getScalarValue($params, 1);
    $user     = getScalarValue($params, 2);
    $password = getScalarValue($params, 3);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+5, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $entry = $model->GetEntry($post_id);
    if (Jaws_Error::IsError($entry)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, $entry->GetMessage());
    }

    $publishtime = strtotime($entry['publishtime']);
    $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
    $content = stripslashes($entry['text']);

    $data = array(
        'userid'      => new XML_RPC_Value($entry['user_id']),
        'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
        'content'     => new XML_RPC_Value($content),
        'postid'      => new XML_RPC_Value($entry['id']),
    );
    $struct = new XML_RPC_Value($data, 'struct');
    return new XML_RPC_Response($struct);
}

/*
 * blogger.getRecentPosts
 */
function blogger_getRecentPosts($params)
{
    // parameters
    $user     = getScalarValue($params, 2);
    $password = getScalarValue($params, 3);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission ($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, $entries->GetMessage());
    }

    $entries_limit = getScalarValue($params, 4);
    $data = new XML_RPC_Value('', 'array');

    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $entries = $model->GetLastEntries($entries_limit);
    if (Jaws_Error::IsError ($entries)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $entries->GetMessage());
    }

    $i = 0;
    foreach ($entries as $entry) {
        $publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $category = new XML_RPC_Value($entry['category_id']);
        $author = $entry['name'];

        $data = array(
            'authorName'  => new XML_RPC_Value($author),
            'userid'      => new XML_RPC_Value($entry['user_id']),
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
            'postid'      => new XML_RPC_Value($entry['id']),
            'category'    => $category,
        );
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
        $data = array($struct[0]);
        for ($j = 1; $j < $i; $j++) {
            array_push($data, $struct[$j]);
        }
    } else {
        $data = array();
    }

    $resp = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($resp);
}

/*
 * blogger.getTemplate
 */
function blogger_getTemplate($params)
{
    return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('BLOG_ERROR_XMLRPC_NO_GETTEMPLATE_SUPPORT'));
}

/*
 * blogger.setTemplate
 */
function blogger_setTemplate($params)
{
    return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('BLOG_ERROR_XMLRPC_NO_SETTEMPLATE_SUPPORT'));
}

/**
 * metaweblog API
 */
/**
 * New Post (metaWeblog.newPost)
 */
function metaWeblog_newPost($params)
{
    // parameters
    $blogToPost = getScalarValue($params, 0); // blog gadget only supports 1 blog, so this parameter is not used.
    $user       = getScalarValue($params, 1);
    $password   = getScalarValue($params, 2);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'AddEntries', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $struct  = XML_RPC_decode($params->getParam(3));
    $title   = $struct['title'];
    $content = $struct['description'];
    $cats    = $struct['categories'];

    $model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $categories = array();
    foreach ($cats as $cat) {
        $catInfo = $model->GetCategoryByName($cat);
        if (Jaws_Error::IsError($catInfo)) {
            return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $catInfo->GetMessage());
        }

        if (isset($catInfo['id'])) {
            $categories[] = $catInfo['id'];
        }
    }

    // Not used yet
//     $extended       = $data['mt_text_more'];
//     $excerpt        = $data['mt_excerpt'];
//     $keywords       = $data['mt_keywords'];
//     $allow_ping     = $data['mt_allow_ping'];
//     $convert_breaks = $data['mt_convert_breaks'];
//     $tb_ping_urls   = $data['mt_tb_ping_urls'];

    // Allow Comments ?
    if (!empty($data['mt_allow_comments'])) {
        $allow_c = $data['mt_allow_comments'];
    } else {
        $allow_c = $GLOBALS['app']->Registry->Get('/gadgets/Blog/allow_comments');
        $allow_c = $allow_c == 'true' ? 1 : 0;
    }

    if (empty($categories)) {
        $GLOBALS['app']->Registry->LoadFile('Blog');
        $categories = array($GLOBALS['app']->Registry->Get('/gadgets/Blog/default_category'));
    }
    $content  = parseContent($content);
    $publish  = getScalarValue($params, 4);

    $post_id = $model->NewEntry($userInfo['id'], $categories, $title, '', $content, $title, $allow_c, '', $publish);
    if (Jaws_Error::IsError ($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * metaWeblog.editPost
 */
function metaWeblog_editPost($params)
{
    $post_id  = getScalarValue($params, 0);
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);

    $struct  = XML_RPC_decode($params->getParam(3));
    $title   = $struct['title'];
    $content = $struct['description'];
    $cats    = $struct['categories'];

    $model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $categories = array();
    foreach ($cats as $cat) {
        $catInfo = $model->GetCategoryByName($cat);
        if (Jaws_Error::IsError($catInfo)) {
            return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $catInfo->GetMessage());
        }

        if (isset($catInfo['id'])) {
            $categories[] = $catInfo['id'];
        }
    }

    // Allow Comments ?
    $allow_c = $GLOBALS['app']->Registry->Get('/gadgets/Blog/allow_comments');
    $allow_c = $allow_c == 'true' ? 1 : 0;

    $publish = getScalarValue($params, 4);
    $content = parseContent($content);

    // Not used yet
//     $extended       = $data['mt_text_more'];
//     $excerpt        = $data['mt_excerpt'];
//     $keywords       = $data['mt_keywords'];
//     $allow_c        = $data['mt_allow_comments'];
//     $allow_ping     = $data['mt_allow_ping'];
//     $convert_breaks = $data['mt_convert_breaks'];

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'AddEntries', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $blog_result = $model->UpdateEntry($post_id, $categories, $title, $content, '', $allow_c, '', $publish);
    if (Jaws_Error::IsError ($blog_result)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $blog_result->GetMessage());
    }

    return new XML_RPC_Response(new XML_RPC_Value('1', 'boolean'));
}

/*
 * metaWeblog.getCategories
 */
function metaWeblog_getCategories($params)
{
    $blog     = getScalarValue($params, 0); // blog gadget only supports 1 blog, so this parameter is not used.
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, $categories->GetMessage());
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $categories = $model->GetCategories();
    if (Jaws_Error::IsError ($categories)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $categories->GetMessage());
    }

    $struct = array();
    $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
    foreach ($categories as $category) {
        $cid = empty($category['fast_url']) ? $category['id'] : $xss->filter($category['fast_url']);
        $htmlurl = $GLOBALS['app']->Map->GetURLFor('Blog', 'ShowCategory', array('id' => $cid));
        $rssurl  = $GLOBALS['app']->Map->GetURLFor('Blog', 'ShowRSSCategory', array('id' => $category['id']));
        $data = array(
            'categoryid'   => new XML_RPC_Value($category['id']),
            'categoryName' => new XML_RPC_Value($category['name']),
            'title'        => new XML_RPC_Value($category['name']),
            'description'  => new XML_RPC_Value($category['description']),
            'htmlUrl'      => new XML_RPC_Value($htmlurl),
            'rssUrl'       => new XML_RPC_Value($rssurl),
        );
        $struct[] = new XML_RPC_Value($data, 'struct');
    }

    $val = new XML_RPC_Value($struct, 'array');
    return new XML_RPC_Response($val);
}

/*
 * metaWeblog.getPost
 */
function metaWeblog_getPost($params)
{
    $post_id  = getScalarValue($params, 0);
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $entry = $model->GetEntry($post_id);
    if (Jaws_Error::IsError($entry)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $entry->GetMessage());
    }

    $publishtime = strtotime($entry['publishtime']);
    $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
    $content    = stripslashes($entry['text']);

    $categories = array();
    $cats = $model->GetCategoriesInEntry($post_id);
    if (!Jaws_Error::isError($cats)) {
        foreach ($cats as $cat) {
            $categories[] = new XML_RPC_Value($cat['name']);
        }
    }

    $data = array(
        'categories'  => new XML_RPC_Value($categories, 'array'),
        'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
        'description' => new XML_RPC_Value($content),
        'link'        => new XML_RPC_Value(''),
        'permLink'    => new XML_RPC_Value(''),
        'postid'      => new XML_RPC_Value($post_id, 'int'),
        'title'       => new XML_RPC_Value($entry['title']),
        'userid'      => new XML_RPC_Value($entry['user_id'], 'int'),
        'blogid'      => new XML_RPC_Value('1'),
    );

    $struct = new XML_RPC_Value($data, 'struct');
    return new XML_RPC_Response($struct);
}

/*
 * metaWeblog.getRecentPosts
 */
function metaWeblog_getRecentPosts($params)
{
    //parameters
    $user          = getScalarValue($params, 1);
    $password      = getScalarValue($params, 2);
    $entries_limit = getScalarValue($params,3);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $entries = $model->GetLastEntries($entries_limit);
    if (Jaws_Error::IsError($entries)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $entries->GetMessage());
    }

    $i = 0;
    foreach ($entries as $entry) {
        $publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content    = stripslashes($entry['text']);
        $extended   = new XML_RPC_Value($content);
        $permalink  = new XML_RPC_Value($GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $entry['id'])));
        $link       = new XML_RPC_Value($GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $entry['fast_url'])));
        //FIXME: Fill the fields
        $allow_pings = new XML_RPC_Value('');

        // Fetch categories for this post
        $categories = array();
        $cats = $model->GetCategoriesInEntry($entry['id']);
        if (!Jaws_Error::isError($cats)) {
            foreach ($cats as $cat) {
                $categories[] = new XML_RPC_Value($cat['name']);
            }
        }

        $data = array(
            'authorName'        => new XML_RPC_Value($entry['name']),
            'dateCreated'       => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'userid'            => new XML_RPC_Value($entry['user_id'], 'int'),
            'postid'            => new XML_RPC_Value($entry['id'], 'int'),
            'blogid'            => new XML_RPC_Value('1'),
            'description'       => new XML_RPC_Value($content),
            'title'             => new XML_RPC_Value($entry['title']),
            'categories'        => new XML_RPC_Value($categories, 'array'),
            'link'              => $link,
            'permalink'         => $permalink,
            'mt_excerpt'        => new XML_RPC_Value(''),
            'mt_allow_comments' => new XML_RPC_Value($entry['allow_comments'], 'boolean'),
            'mt_allow_pings'    => $allow_pings,
            'mt_text_more'      => $extended
        );
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
        $data = array($struct[0]);
        for ($j = 1; $j < $i; $j++) {
            array_push($data, $struct[$j]);
        }
    } else {
        $data = array();
    }

    $resp = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($resp);
}

/* MovableType API functions
 * specs on http://www.movabletype.org/docs/mtmanual_programmatic.html
 * http://www.kalsey.com/admin/mt/docs/mtmanual_programmatic.html
 */

function mt_getRecentPostTitles($params)
{
    //parameters
    $blogid   = getScalarValue($params, 0);
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);
    $numPosts = getScalarValue($params, 3);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $entries = $model->GetLastEntries($numPosts);
    if (Jaws_Error::IsError($entries)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $entries->GetMessage());
    }

    $i = 0;
    foreach ($entries as $entry) {
        $publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);

        $data = array(
            'dateCreated'       => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'userid'            => new XML_RPC_Value($entry['user_id'], 'int'),
            'postid'            => new XML_RPC_Value($entry['id'], 'int'),
            'title'             => new XML_RPC_Value($entry['title']),
        );
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
        $data = array($struct[0]);
        for ($j = 1; $j < $i; $j++) {
            array_push($data, $struct[$j]);
        }
    } else {
        $data = array();
    }

    $resp = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($resp);
}

function mt_getCategoryList($params)
{
    //parameters
    $blogid   = getScalarValue($params, 0);
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $categories = $model->GetCategories();
    if (Jaws_Error::IsError ($categories)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $categories->GetMessage());
    }

    $i = 0;
    $struct = array();
    foreach ($categories as $category) {
        $data = array(
            'categoryId'   => new XML_RPC_Value($category['id']),
            'categoryName' => new XML_RPC_Value($category['name']),
        );
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    $data = array($struct[0]);
    for ($j = 1; $j < $i; $j++) {
        array_push($data, $struct[$j]);
    }

    $val = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($val);
}

function mt_getPostCategories($params)
{
    //parameters
    $postid   = getScalarValue($params, 0);
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Blog', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    $categories = $model->GetCategoriesInEntry($postid);
    if (Jaws_Error::IsError ($categories)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $categories->GetMessage());
    }

    $i = 0;
    $struct = array();
    foreach ($categories as $category) {
        $data = array(
            'categoryId'   => new XML_RPC_Value($category['id']),
            'categoryName' => new XML_RPC_Value($category['name']),
            'isPrimary'    => new XML_RPC_Value(false, 'boolean'), // JAWS does not support this option ATM
        );
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    $data = array($struct[0]);
    for ($j = 1; $j < $i; $j++) {
        array_push($data, $struct[$j]);
    }

    $val = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($val);
}

function mt_setPostCategories($params)
{
    //parameters
    $postid     = getScalarValue($params, 0);
    $user       = getScalarValue($params, 1);
    $password   = getScalarValue($params, 2);
    $categories = getScalarValue($params, 3);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    if (is_array($categories) && count($categories) > 0) {
        $isPrimarySet = false;
        $cats = array();
        foreach ($categories as $cat) {
            if (!isset($cat['isPrimary'])) {
                $cat['isPrimary'] = false;
            }
            $cats[] = array(
                'id'        => $cat['categoryId'], 'int',
                'isPrimary' => $cat['isPrimary'], 'boolean',
            );
        }

        if (!$isPrimarySet) {
            $cats[0]['isPrimary'] = true;
        }

        $model = $GLOBALS['app']->loadGadget('Blog', 'AdminModel');
        if (Jaws_Error::isError($model)) {
            return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
        }

        // Add categories to entry
    }

    $val = new XML_RPC_Value(true, 'boolean');
    return new XML_RPC_Response($val);
}

function mt_supportedMethods($params)
{
    global $rpc_method;

    $supported_methods = array();
    foreach ($rpc_methods as $key => $value) {
        $supported_methods[] = $key;
    }

    return $supported_methods;
}

/* mt.supportedTextFilters ...returns an empty array because we don't
    support per-post text filters yet */
function mt_supportedTextFilters($params)
{
    return array();
}

function mt_getTrackbackPings($params)
{

}

function mt_publishPost($params)
{
    //parameters
    $postid   = getScalarValue($params, 0);
    $user     = getScalarValue($params, 1);
    $password = getScalarValue($params, 2);

    if (Jaws_Error::IsError($userInfo = Jaws_User::Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+4, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    $GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
    if (!GetBlogPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    // Move from draft to publish if needed

    // Generate all cache files ... Do not ping anything tho.

    $val = new XML_RPC_Value(true, 'boolean');
    return new XML_RPC_Response($val);
}

/* PingBack functions
 * specs on www.hixie.ch/specs/pingback/pingback
 */

/* pingback.ping gets a pingback and registers it */
function pingback_ping($params)
{
    //parameters
    $linkfrom = getScalarValue($params, 0);
    $linkto   = getScalarValue($params, 1);
}

/* pingback.extensions.getPingbacks returns an array of URLs
    that pingbacked the given URL
    specs on http://www.aquarionics.com/misc/archives/blogite/0198.html */
function pingback_extensions_getPingbacks($params)
{

}

/*
 *  XML-RPC Server
 */

$rpc_methods = array(
    // Blogger.com API
    'blogger.newPost' => array(
        'function'  => 'blogger_newPost',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string', 'string', 'boolean'),
        ),
    ),
    'blogger.editPost' => array(
        'function'  => 'blogger_editPost',
        'signature' => array(
            array('boolean', 'string', 'string', 'string', 'string', 'string', 'boolean'),
        ),
    ),
    'blogger.deletePost' => array(
        'function'  => 'blogger_deletePost',
        'signature' => array(
            array('boolean', 'string', 'string', 'string', 'string', 'boolean'),
        ),
    ),
    'blogger.getUsersBlogs' => array(
        'function'  => 'blogger_getUsersBlogs',
        'signature' => array(
            array('array', 'string', 'string', 'string'),
        ),
    ),
    'blogger.getUserInfo' => array(
        'function'  => 'blogger_getUserInfo',
        'signature' => array(
            array('struct', 'string', 'string', 'string'),
        ),
    ),
    'blogger.getPost' => array(
        'function' => 'blogger_getPost',
        'signature' => array(
            array('struct', 'string', 'string', 'string'),
        ),
    ),
    'blogger.getRecentPosts' => array(
        'function'  => 'blogget_getRecentPosts',
        'signature' => array(
            array('array', 'string', 'string', 'string', 'string', 'int'),
        ),
    ),
    'blogger.getTemplate' => array('function' => 'blogger_getRecentPosts'),
    'blogger.setTemplate' => array('function' => 'blogger_getRecentPosts'),

    // metaWeblog API
    'metaWeblog.newPost' => array(
        'function'  => 'metaWeblog_newPost',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'struct', 'boolean'),
        ),
    ),
    'metaWeblog.editPost' => array(
        'function'  => 'metaWeblog_editPost',
        'signature' => array(
            array('boolean', 'string', 'string', 'string', 'struct', 'boolean'),
        ),
    ),
    'metaWeblog.getPost' => array(
        'function'  => 'metaWeblog_getPost',
        'signature' => array(
            array('struct', 'string', 'string', 'string'),
        ),
    ),
    'metaWeblog.getCategories' => array(
        'function'  => 'metaWeblog_getCategories',
        'signature' => array(
            array('array', 'string', 'string', 'string'),
        ),
    ),
    'metaWeblog.getRecentPosts' => array(
        'function'  => 'metaWeblog_getRecentPosts',
        'signature' => array(
            array('array', 'string', 'string', 'string', 'int'),
        ),
    ),
    // 'metaWeblog.newMediaObject' => array('function' => 'metaWeblog_newMediaObject'), No Supported
    // MetaWeblog API aliases for Blogger API
    // see http://www.xmlrpc.com/stories/storyReader$2460
    'metaWeblog.deletePost' => array(
        'function'  => 'blogger_deletePost',
        'signature' => array(
            array('boolean', 'string', 'string', 'string', 'string', 'boolean'),
        ),
    ),
    'metaWeblog.getTemplate'   => array('function' => 'blogger_getRecentPosts'),
    'metaWeblog.setTemplate'   => array('function' => 'blogger_getRecentPosts'),
    'metaWeblog.getUsersBlogs' => array(
        'function' => 'blogger_getUsersBlogs',
        'signature' => array(
            array('array', 'string', 'string', 'string'),
        ),
    ),

    // MovableType API
    'mt.getCategoryList' => array(
        'function'  => 'mt_getCategoryList',
        'signature' => array(
            array('array', 'string', 'string', 'string'),
        ),
    ),
    'mt.getRecentPostTitles' => array(
        'function'  => 'mt_getRecentPostTitles',
        'signature' => array(
            array('array', 'string', 'string', 'string', 'int'),
        ),
    ),
    'mt.getPostCategories' => array(
        'function'  => 'mt_getPostCategories',
        'signature' => array(
            array('array', 'string', 'string', 'string'),
        ),
    ),
    'mt.setPostCategories' => array(
        'function'  => 'mt_setPostCategories',
        'signature' => array(
            array('boolean', 'string', 'string', 'string', 'array'),
        ),
    ),
    'mt.supportedMethods' => array(
        'function'  => 'mt_supportedMethods',
        'signature' => array(
            array('array'),
        ),
    ),
    'mt.supportedTextFilters' => array(
        'function'  => 'mt_supportedTextFilters',
        'signature' => array(
            array('array'),
        ),
    ),
    'mt.getTrackbackPings' => array(
        'function'  => 'mt_getTrackbackPings',
        'signature' => array(
            array('array', 'string'),
        ),
    ),
    'mt.publishPost' => array(
        'function' => 'mt_publishPost',
        'signature' => array(
            array('boolean', 'string', 'string', 'string'),
        ),
    ),

    // PingBack
    'pingback.ping'                    => array('function' => 'pingback_ping'),
    'pingback.extensions.getPingbacks' => array('function' => 'pingback_extensions_getPingbacks'),
);
