<?php
/**
 * CustomPage Upgrade Scripts from ver 0.1.1 to 0.1.2
 *
 * @category   Upgrade
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
 
// }}}
// {{{ Function CustomPage_upgradeFrom011
/**
 * Performs some upgrade tasks.
 *
 * @access  public
 * @return  boolean   Response (true or false on error)
 */
function CustomPage_upgradeFrom011()
{
	$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
	$customPageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
	$customPageLayoutHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
	$adminModel = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
	$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
	require_once JAWS_PATH . 'include/Jaws/FileManagement.php';
			
	// update theme to varchar
	$sql = "
		ALTER TABLE  [[pages]] CHANGE  [theme]  [theme] VARCHAR( 255 ) NULL";

	$result = $GLOBALS['db']->query($sql, $params);
	if (Jaws_Error::IsError($result)) {
		return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
		//return false;
	}
		
	/*
	$sql = 'DELETE FROM [[layout]] WHERE [id] > {id}';
	$result = $GLOBALS['db']->query($sql, array('id' => 25));
	if (Jaws_Error::IsError($result)) {
		return new Jaws_Error('Layout elements not deleted', _t('CUSTOMPAGE_NAME'));
	}
	
	$sql = 'UPDATE [[pages]] SET [theme] = {theme} WHERE [layout] LIKE {layout}';
	$result = $GLOBALS['db']->query($sql, array('theme' => 'gadgets/CustomPage/templates', 'layout' => 'layout%'));
	if (Jaws_Error::IsError($result)) {
		return new Jaws_Error('Layout elements not deleted', _t('CUSTOMPAGE_NAME'));
	}
	*/

	// First, get all pages
	$pages = $model->GetPages();
	if (!Jaws_Error::IsError($pages)) {
		foreach ($pages as $page) {
			if (is_null($page['gadget_action']) || empty($page['gadget_action'])) {
				$page['gadget_action'] = 'Page';
			}
			if ($page['gadget'] != 'CustomPage' && $page['gadget'] != 'Users' && $page['gadget'] != 'Groups' && $page['gadget'] != 'Email') {
				$error_msg = "\nGadget not CustomPage nor Users\n";
				$error_msg .= var_export($page['gadget'], true);
				$error_msg .= "\n";
				$error_msg .= var_export($page['gadget_action'], true);
				$error_msg .= "\n";
				$error_msg .= var_export($page['linkid'], true);
				$error_msg .= "\n\n";
				$error = new Jaws_Error($error_msg, _t('CUSTOMPAGE_NAME'));
				continue;
			}
			// Update page theme, layout, gadget and gadget_action
			$theme_path = JAWS_DATA . 'themes/' . $GLOBALS['app']->_Theme;
			if (substr(strtolower($GLOBALS['app']->_Theme), 0, 4) == 'http') {
				if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' && substr(strtolower($GLOBALS['app']->_Theme), 0, 5) == 'http:') {
					$theme_path	= $GLOBALS['app']->GetSiteURL('', false, 'https').'/gz.php?type=css&uri=' . urlencode($GLOBALS['app']->_Theme);
				} else {	
					$theme_path	= $GLOBALS['app']->_Theme;
				}
			} else {
				if (!is_dir($theme_path)) {
					$theme_path   = JAWS_BASE_DATA .  'themes/' . $GLOBALS['app']->_Theme;
				}			
			}
			// NOTE: 
			// "layout" should be the filename either in theme/CustomPage dir or data/templates/CustomPage dir
			// "theme" should be the path to the layout file
			// if either is set to something else, move it into data/templates/CustomPage dir, and update them here
			$new_layout = $page['layout'];
			$new_theme = '';
			if (!empty($new_layout) || $new_layout == '0') {
				if (strlen($new_layout) == 1 || $new_layout == '0') {
					$new_layout = 'layout'.$new_layout.'.html';
				} else if (strpos($new_layout, '/') !== false) {
					$new_theme = substr($new_layout, 0, strrpos($new_layout, '/'));
					$layout_file = substr($new_layout, (strrpos($new_layout, '/')+1), strlen($new_layout));

					/*
					echo "\nLayout not empty\nold layout:";
					var_dump($new_layout);
					echo "\nnew layout:";
					var_dump($layout_file);
					*/

					if (
						($new_theme != $theme_path && $new_theme != 'templates/CustomPage' && 
						$new_theme != str_replace(array(JAWS_DATA, JAWS_BASE_DATA), '', $theme_path.'/CustomPage')) || 
						(!file_exists(JAWS_DATA . $new_theme . '/'. $layout_file))
					) {
						if (file_exists(JAWS_DATA . $page['theme']) && JAWS_DATA . $page['theme'] != $theme_path) {
							Jaws_FileManagement::FullCopy(JAWS_DATA . $new_layout, JAWS_DATA . 'templates/CustomPage/'.$layout_file);
							Jaws_FileManagement::FullRemoval(JAWS_DATA . $new_layout);
						} else if (
							($layout_file == 'layout0.html' || 
							$layout_file == 'layout1.html' || 
							$layout_file == 'layout2.html' || 
							$layout_file == 'layout3.html' || 
							$layout_file == 'layout4.html' || 
							$layout_file == 'layout5.html' || 
							$layout_file == 'layout6.html' || 
							$layout_file == 'layout7.html' || 
							$layout_file == 'layout8.html') && 
							file_exists($theme_path.'/CustomPage/' . $layout_file)
						) {
							$new_theme = str_replace(array(JAWS_DATA, JAWS_BASE_DATA), '', $theme_path.'/CustomPage');
						} else if (
							$layout_file == 'layout.html' && 
							file_exists($theme_path.'/'.$layout_file)
						) {
							$new_theme = '';
						} else {
							$new_theme = 'templates/CustomPage';
						}
					}

					/*
					echo "\nnew theme:";
					var_dump($new_theme);
					echo "\n\n";
					*/

					$new_layout = $layout_file;
					//continue;
				}
			}
			if (!empty($page['theme']) && strlen($page['theme']) > 1) {
				if (strpos($page['theme'], '/') !== false) {
					$new_theme = $page['theme'];
					if (substr(strtolower($page['theme']), -5) == '.html') {
						$new_theme = substr($page['theme'], 0, strrpos($page['theme'], '/'));
						$layout_file = substr($page['theme'], (strrpos($page['theme'], '/')+1), strlen($page['theme']));
						$new_layout = $layout_file;
					}

					/*
					echo "\nTheme not empty\nold theme:";
					var_dump($page['theme']);
					echo "\nnew layout:";
					var_dump($layout_file);
					*/
					if (
						($new_theme != $theme_path && $new_theme != 'templates/CustomPage' && 
						$new_theme != str_replace(array(JAWS_DATA, JAWS_BASE_DATA), '', $theme_path.'/CustomPage')) || 
						(!file_exists(JAWS_DATA . $new_theme . '/'. $new_layout))
					) {
						if (file_exists(JAWS_DATA . $page['theme']) && JAWS_DATA . $page['theme'] != $theme_path) {
							Jaws_FileManagement::FullCopy(JAWS_DATA . $page['theme'], JAWS_DATA . 'templates/CustomPage/'.$layout_file);
							Jaws_FileManagement::FullRemoval(JAWS_DATA . $page['theme']);
						} else if (
							($new_layout == 'layout0.html' || 
							$new_layout == 'layout1.html' || 
							$new_layout == 'layout2.html' || 
							$new_layout == 'layout3.html' || 
							$new_layout == 'layout4.html' || 
							$new_layout == 'layout5.html' || 
							$new_layout == 'layout6.html' || 
							$new_layout == 'layout7.html' || 
							$new_layout == 'layout8.html') && 
							file_exists($theme_path.'/CustomPage/' . $new_layout)
						) {
							$new_theme = str_replace(array(JAWS_DATA, JAWS_BASE_DATA), '', $theme_path.'/CustomPage');
						} else if (
							$new_layout == 'layout.html' && 
							file_exists($theme_path.'/'.$new_layout)
						) {
							$new_theme = '';
						} else {
							$new_theme = 'templates/CustomPage';
						}
					}

					/*
					echo "\nnew theme:";
					var_dump($new_theme);
					echo "\n\n";
					*/
					//continue;
				}
			} else {
				$new_theme = ((empty($new_theme) || strlen($new_theme) == 1) && $new_layout != 'layout.html' ? str_replace(array(JAWS_DATA, JAWS_BASE_DATA), '', $theme_path.'/CustomPage') : $new_theme);
			}
			
			$new_gadget         		= $page['gadget'];
			$new_action    				= $page['gadget_action'];
			$new_linkid   	 			= $page['linkid'];
			
			$sql = '
			UPDATE [[pages]] SET
				[layout] = {layout},
				[theme] = {theme}
			';

			$params               		= array();
			$params['layout']         	= $new_layout;
			$params['theme']         	= $new_theme;
			$params['id']         		= $page['id'];
			if ($page['gadget'] == 'CustomPage' || $page['gadget'] == 'Groups' || $page['gadget'] == 'Email') {
				$sql .= ',
					[gadget] = {gadget},
					[gadget_action] = {gadget_action},
					[linkid] = {linkid}
				';
				$new_gadget = ($page['gadget'] == 'Groups' || $page['gadget'] == 'Email' || ($page['gadget'] == 'CustomPage' && (int)$page['linkid'] != (int)$page['id'] && (int)$page['linkid'] != 1) ? 'Users' : 'CustomPage');
				$params['gadget']         	= $new_gadget;
				$new_action = ($page['gadget'] == 'Groups' || ($page['gadget'] == 'CustomPage' && (int)$page['linkid'] != (int)$page['id'] && (int)$page['linkid'] != 1) ? 'GroupPage' : ($page['gadget'] == 'Email' ? 'EmailPage' : 'Page'));
				$params['gadget_action']    = $new_action;
				$new_linkid = ($new_gadget != 'CustomPage' ? $page['linkid'] : $page['id']);
				$params['linkid']    		= $new_linkid;
			} else if ($page['gadget'] == 'Users' && (int)$page['linkid'] == 1) {
				$sql .= ',
					[gadget] = {gadget},
					[gadget_action] = {gadget_action},
					[linkid] = {linkid}
				';
				$params['gadget']         	= 'CustomPage';
				$params['gadget_action']    = 'Page';
				$params['linkid']   	 	= $page['id'];
				$params['theme']  			= $theme_path.'/CustomPage';
			}
			$sql .= '
			WHERE [id] = {id}
			';
				
			/*
			echo "\n\n";
			$show_sql = $sql;
			foreach ($params as $k => $v) {
				$show_sql = str_replace('{'.$k.'}', $v, $show_sql);
			}
			var_dump($show_sql);
			echo "\n\n";
			*/
				
			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
			
			$pg = $new_gadget;
			$pa = $new_action.'('.$new_linkid.')';
			
			$page_image_code = $page['image_code'];
			// Get all posts of page
			$posts = $model->GetAllPostsOfPage($page['id']);
			if (!Jaws_Error::IsError($posts)) {
				foreach ($posts as $post) {
					if (!empty($post['image_code'])) {
						$post['image_code'] = $customPageHTML->ParseText($post['image_code'], 'CustomPage');
						$post['image_code'] = htmlspecialchars_decode($post['image_code']);
						// move scripts and styles from post.image_code to pages.image_code
						$scripts = "'<script[^>]*?>.*?</script>'si";
						preg_match($scripts,$post['image_code'],$scripts_matches);
						/*
						echo "\n".'before:'."\n<pre>\n".$post['image_code']."\n".'</pre>'."\n";
						echo "\n".'scripts_matches:'."\n";
						var_dump($scripts_matches);
						echo "\n";
						*/
						if (is_array($scripts_matches) && !count($scripts_matches) <= 0) {
							reset($scripts_matches);
							foreach ($scripts_matches as $sc) {
								if (strpos(strtolower($sc), 'document.write') === false) {
									$page_image_code .= (strpos($page['image_code'], htmlspecialchars($sc)) !== false ? '' : htmlspecialchars($sc)."\n");
								}
							}
						}
						$styles = "'<style[^>]*?>.*?</style>'si";
						preg_match($styles,$post['image_code'],$styles_matches);
						/*
						echo "\n".'styles_matches:'."\n";
						var_dump($styles_matches);
						echo "\n";
						*/
						if (is_array($styles_matches) && !count($styles_matches) <= 0) {
							reset($styles_matches);
							foreach ($styles_matches as $st) {
								$page_image_code .= (strpos($page['image_code'], htmlspecialchars($st)) !== false ? '' : htmlspecialchars($st)."\n");
							}
						}
						
						// strip out tags
						$search = array(
							"'<script[^>]*?>.*?</script>'si",
							"'<style[^>]*?>.*?</style>'si"
						);
						$replace = array("","");
						$post['image_code'] = preg_replace($search,$replace,$post['image_code']);
						
						// move what's left of image_code to description
						/*
						echo "\n".'after:'."\n<pre>\n".$post['image_code']."\n".'</pre>'."\n";
						echo "\n".'description before:'."\n<pre>\n".$post['description']."\n".'</pre>'."\n";
						*/
						$new_description = $post['description'].str_replace("\r\n", "\n", $post['image_code']);
						//echo "\n".'description after:'."\n<pre>\n".$new_description."\n".'</pre>'."\n";
												
						// update post
						$sql = '
						UPDATE [[pages_posts]] SET
							[description] = {description},
							[image_code] = {image_code}
						WHERE [id] = {id}
						';

						$params               		= array();
						$params['description']      = $new_description;
						$params['image_code']       = '';
						$params['id']         		= $post['id'];
						
						/*
						echo "\n\n";
						$show_sql = $sql;
						foreach ($params as $k => $v) {
							$show_sql = str_replace('{'.$k.'}', $v, $show_sql);
						}
						var_dump($show_sql);
						echo "\n\n";
						*/						
						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							return $result;
						}
					}
					
					if (strtolower($post['gadget']) == 'text') {
						$new_description = $customPageLayoutHTML->ShowPost($post['id']);
						$delimeterLeft = "<!-- START_post -->";
						$delimeterRight = "<!-- END_post -->";
						$startLeft = strpos($new_description, $delimeterLeft);
						$posLeft = ($startLeft+strlen($delimeterLeft));
						$posRight = strpos($new_description, $delimeterRight, $posLeft);
						$new_description = substr($new_description, $posLeft, $posRight-$posLeft);
						$new_description = str_replace(array($delimeterLeft, $delimeterRight), '', $new_description);
						
						// update post
						$sql = '
						UPDATE [[pages_posts]] SET
							[description] = {description},
							[title] = {title},
							[image] = {image}
						WHERE [id] = {id}
						';

						$params               		= array();
						$params['description']      = $new_description;
						$params['image']      		= '';
						$params['title']       		= '';
						$params['id']         		= $post['id'];
								
						/*
						echo "\n\n";
						$show_sql = $sql;
						foreach ($params as $k => $v) {
							$show_sql = str_replace('{'.$k.'}', $v, $show_sql);
						}
						var_dump($show_sql);
						echo "\n\n";
						*/	
						
						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							return $result;
						}
					}
					
					// Add post as new Layout element on page
					if (strtolower($post['gadget']) == 'text') {
						$post_gadget = 'CustomPage';
						$post_action = 'ShowPost('.$post['id'].')';
					} else {
						$post_gadget = $post['gadget'];
						$post_action = $post['image'];
					}
					
					// Skip if already added to layout
					$sql2 = '
						SELECT
							[id]
						FROM [[layout]]
						WHERE ([section] = {section} AND [gadget] = {gadget} AND [gadget_action] = {action} AND [display_when] LIKE {like_dw})
					';
					$params2 = array();
					$params2['section'] = 'section'.$post['section_id'];
					$params2['gadget'] = $post_gadget; 
					$params2['action'] = $post_action;
					$params2['like_dw'] = '%{GADGET:'.$pg.'|ACTION:'.$pa.'}%';
					$params2['dw'] = '{GADGET:'.$pg.'|ACTION:'.$pa.'}';

					/*
					echo "\n\n";
					$show_sql2 = $sql2;
					foreach ($params2 as $k => $v) {
						$show_sql2 = str_replace('{'.$k.'}', $v, $show_sql2);
					}
					var_dump($show_sql2);
					echo "\n\n";
					*/
					
					$result = $GLOBALS['db']->queryAll($sql2, $params2);
					if (Jaws_Error::IsError($result)) {
						return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
					} else if (isset($result[0]['id'])) {
						continue;
					} else {
						/*
						var_dump(
							'layoutAdminModel->NewElement('.
							'section'.$post['section_id'].', '.
							$post_gadget.', '. 
							$post_action.', '. 
							$post['sort_order'].', '. 
							'{GADGET:'.$pg.'|ACTION:'.$pa.'})'
						);
						*/
						$id = $layoutAdminModel->NewElement(
							'section'.$post['section_id'], 
							$post_gadget, 
							$post_action, 
							$post['sort_order'], 
							'{GADGET:'.$pg.'|ACTION:'.$pa.'}'
						);
						if ($id === false) {
							return new Jaws_Error("Layout element not created for post ID: ".$post['id'], _t('CUSTOMPAGE_NAME'));
						}
					}
					//echo "\n\n";
				}
			}
			// update pages.image_code
			if ($page_image_code != $page['image_code']) {
				/*
				echo "\npage image_code changed:\n";
				var_dump($page['image_code']);
				echo "\n\n";
				var_dump($page_image_code);
				echo "\n\n";
				*/
				$sql = '
				UPDATE [[pages]] SET
					[image_code] = {image_code}
				WHERE [id] = {id}
				';

				$params               	= array();
				$params['image_code']	= $page_image_code;
				$params['id']         	= $page['id'];
				
				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return $result;
				}
			}
			
		}
	}
		
	// TODO: Add pages for Store and Properties
	return true;
}
