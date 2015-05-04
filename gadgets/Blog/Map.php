<?php
/**
 * Blog URL maps
 *
 * @category   GadgetMaps
 * @package    Blog
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author     Jorge A Gallegos <kad@gulags.org.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Map->Connect('Blog', 'DefaultAction', 'blog');
$GLOBALS['app']->Map->Connect('Blog','LastPost', 'blog/last');
$GLOBALS['app']->Map->Connect('Blog',
                              'ViewDatePage',
                              'blog/{year}/{month}/{day}/page/{page}',
                              '',
                              array('year'  => '\d{4}',
                                    'month' => '[01]?\d',
                                    'day'   => '[0-3]?\d',
                                    'page'  => '[[:digit:]]+$')
                             );
$GLOBALS['app']->Map->Connect('Blog',
                              'ViewDatePage',
                              'blog/{year}/{month}/{day}',
                              '',
                              array('year'  => '\d{4}',
                                    'month' => '[01]?\d',
                                    'day'   => '[0-3]?\d')
                             );
$GLOBALS['app']->Map->Connect('Blog',
                              'ViewDatePage',
                              'blog/{year}/{month}/page/{page}',
                              '',
                              array('year' => '\d{4}',
                                    'month' => '[01]?\d',
                                    'page'  => '[[:digit:]]+$')
                             );
$GLOBALS['app']->Map->Connect('Blog',
                              'ViewDatePage',
                              'blog/{year}/{month}',
                              '',
                              array('year' => '\d{4}',
                                    'month' => '[01]?\d')
                             );
$GLOBALS['app']->Map->Connect('Blog',
                              'ViewDatePage',
                              'blog/{year}/page/{page}',
                              '',
                              array('year' => '\d{4}',
                                    'page'  => '[[:digit:]]+$')
                             );
$GLOBALS['app']->Map->Connect('Blog',
                              'ViewDatePage',
                              'blog/{year}',
                              '',
                              array('year' => '\d{4}')
                             );
$GLOBALS['app']->Map->Connect('Blog','RSS', 'blog/rss');
$GLOBALS['app']->Map->Connect('Blog',
                              'ShowRSSCategory',
                              'blog/rss/category/{id}',
                              '',
                              array('id' => '[[:alnum:][:space:][:punct:]]+$',)
                              );
$GLOBALS['app']->Map->Connect('Blog','RecentCommentsRSS', 'blog/rss/comments');
$GLOBALS['app']->Map->Connect('Blog',
                              'CommentsRSS',
                              'blog/rss/comment/{id}',
                              '',
                              array('id' => '[[:alnum:][:space:][:punct:]]+$',)
                              );
$GLOBALS['app']->Map->Connect('Blog','Atom', 'blog/atom');
$GLOBALS['app']->Map->Connect('Blog',
                              'ShowAtomCategory',
                              'blog/atom/category/{id}',
                              '',
                              array('id' => '[[:alnum:][:space:][:punct:]]+$',)
                              );
$GLOBALS['app']->Map->Connect('Blog','RecentCommentsAtom', 'blog/atom/comments');
$GLOBALS['app']->Map->Connect('Blog',
                              'CommentsAtom',
                              'blog/atom/comment/{id}',
                              '',
                              array('id' => '[[:alnum:][:space:][:punct:]]+$',)
                              );
$GLOBALS['app']->Map->Connect('Blog',
                              'SingleView', 
                              'blog/show/{id}',
                              '',
                              array('id' => '[[:alnum:][:space:][:punct:]]+$',)
                              );

$GLOBALS['app']->Map->Connect('Blog',
                              'ViewAuthorPage',
                              'blog/author/{id}/page/{page}',
                              '',
                              array(
                                    'id'   => '[[:alnum:][:space:][:punct:]]+',
                                    'page' => '[[:digit:]]+$',
                                   )
                              );
$GLOBALS['app']->Map->Connect('Blog',
                              'ViewAuthorPage',
                              'blog/author/{id}',
                              '',
                              array('id' =>  '[[:alnum:][:space:][:punct:]]+$',)
                              );

$GLOBALS['app']->Map->Connect('Blog','ViewPage', 'blog/page/{page}');
$GLOBALS['app']->Map->Connect('Blog','Reply', 'blog/{id}/reply/{comment_id}');

$GLOBALS['app']->Map->Connect('Blog',
                              'ShowCategory',
                              'blog/category/{id}/page/{page}',
                              '',
                              array(
                                    'id'   => '[[:alnum:][:space:][:punct:]]+',
                                    'page' => '[[:digit:]]+$',
                                   )
                              );
$GLOBALS['app']->Map->Connect('Blog',
                              'ShowCategory',
                              'blog/category/{id}',
                              '',
                              array('id' =>  '[[:alnum:][:space:][:punct:]]+$',)
                              );
$GLOBALS['app']->Map->Connect('Blog', 'CategoriesList', 'blog/categories');

$GLOBALS['app']->Map->Connect('Blog','Trackback', 'trackback/{id}');
$GLOBALS['app']->Map->Connect('Blog','Archive', 'blog/archive');
$GLOBALS['app']->Map->Connect('Blog','PopularPosts', 'blog/popular');
$GLOBALS['app']->Map->Connect('Blog','Authors', 'blog/authors');
$GLOBALS['app']->Map->Connect('Blog','Pingback', 'pingback');
