<?php
/**
 * FlashGallery Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FlashGalleryLayoutHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions($limit = null, $offset = null)
    {
        $actions = array();
        $model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
        
		//if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries')) {
			$galleries = $model->GetFlashGalleries($limit, 'title', 'ASC', $offset);
		//} else {
		//	$galleries = $model->GetFlashGalleryOfUserID($GLOBALS['app']->Session->GetAttribute('user_id'));			
		//}
        if (!Jaws_Error::isError($galleries)) {
            foreach ($galleries as $gallery) {
				if ($gallery['ownerid'] == 0) {
					$actions['Gallery(' . $gallery['id'] . ')'] = array(
						'mode' => 'LayoutAction',
						'name' => $gallery['title'],
						'desc' => _t('FLASHGALLERY_LAYOUT_GALLERY_DESCRIPTION')
					);
					$actions['Slideshow(' . $gallery['id'] . ')'] = array(
						'mode' => 'LayoutAction',
						'name' => $gallery['title'],
						'desc' => _t('FLASHGALLERY_LAYOUT_SLIDESHOW_DESCRIPTION')
					);
					$actions['ShowOne(' . $gallery['id'] . ')'] = array(
	                    'mode' => 'LayoutAction',
	                    'name' => 'Show one image from "'.$gallery['title'].'"',
	                    'desc' => _t('FLASHGALLERY_LAYOUT_SHOWONE_DESCRIPTION')
	                );
				}
            }
        }
        return $actions;
    }

	/**
     * Display grid of photos, "Gallery" style.
     *
     * @category 	feature
     * @param 	int 	$cid 	Gallery ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function Gallery($cid = 1, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/FlashGallery/resources/style.css', 'stylesheet', 'text/css');
        
		// for boxover on date highlighting
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		// send calendarParent records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');
		
		$parents = $model->GetFlashGallery($cid);
		if (!Jaws_Error::IsError($parents) && isset($parents['id']) && !empty($parents['id'])) {
			//if ($parents['type'] == 'gallery') {
				//setup variables
				$good_ext = array('jpg', 'jpeg', 'swf', 'gif', 'png', 'tif', 'bmp');
				$wm_ext = array('jpg', 'jpeg', 'gif', 'png');
				
				require_once JAWS_PATH . 'include/Jaws/Template.php';
				$tpl = new Jaws_Template('gadgets/FlashGallery/templates/');
				$tpl->Load('normal.html');

				$tpl->SetBlock('layout');
				$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');
				$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
				$tpl->SetVariable('actionName', 'Gallery_' . $parents['id'] . '_');
				$tpl->SetVariable('layout_title', $parents['title']);

				$tpl->SetBlock('layout/gallery');
				// set "gallery" swfobject variables
				//$tpl->SetVariable('base_url', JAWS_DPATH);
				$tpl->SetVariable('id', $cid);
				if (!empty($parents['background_image'])) {
					$tpl->SetVariable('gallery_loading_image', 'url(' . $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($parents['background_image']).') no-repeat center');
				//} else {
				//	$tpl->SetVariable('gallery_loading_image', 'url(' . $GLOBALS['app']->GetJawsURL() . '/gadgets/FlashGallery/images/gallery_loading.gif) no-repeat center');
				}
				$tpl->SetVariable('gallery_background_color', (!empty($parents['background_color']) ? '#'.$xss->filter($parents['background_color']) : ''));
				// build dimensions
				$gallery_dimensions = "gmaxWidth".$cid." = 950;\n";
				$gallery_dimensions .= "gmaxHeight".$cid." = 317;\n";
				if ($parents['width'] != 'auto' && !empty($parents['custom_width'])) {
					$gallery_dimensions .= "gmaxWidth".$cid." = ".(int)$parents['custom_width'].";\n";	
				} else {
					$gallery_dimensions .= "if ($('flash-gallery-".$cid."').parentNode) {\n";
					$gallery_dimensions .= " 	gmaxWidth".$cid." = parseInt($('flash-gallery-".$cid."').parentNode.offsetWidth);\n";	
					$gallery_dimensions .= "}\n";	
				}
				//$gallery_dimensions .= "alert($('flash-gallery-".$cid."').offsetWidth);\n";
				//$gallery_dimensions .= "alert($('flash-gallery-".$cid."').parentNode.offsetWidth);\n";
				//$gallery_dimensions .= "alert($('flash-gallery-".$cid."').parentNode.parentNode.offsetWidth);\n";
				if ($parents['height'] != 'auto' && !empty($parents['custom_height'])) {
					$gallery_dimensions .= "gmaxHeight".$cid." = ".(int)$parents['custom_height'].";\n";	
				} else {
					switch($parents['aspect_ratio']) {
						case "3:1": 
							$gallery_dimensions .= " 	gmaxHeight".$cid." = parseInt((gmaxWidth".$cid.")*(0.33333333333333333333333333333333));\n";
							break;
						case "16:9": 
							$gallery_dimensions .= " 	gmaxHeight".$cid." = parseInt((gmaxWidth".$cid.")*(.5625));\n";
							break;
						case "4:3": 
							$gallery_dimensions .= " 	gmaxHeight".$cid." = parseInt((gmaxWidth".$cid.")*(.75));\n";
							break;
						case "1:1": 
							$gallery_dimensions .= " 	gmaxHeight".$cid." = parseInt(gmaxWidth".$cid.");\n";
							break;
					}
				}
				
				$tpl->SetVariable('gallery_dimensions', $gallery_dimensions);
				$tpl->SetVariable('gallery_title', $xss->filter($parents['title']));
				$tpl->SetVariable('gallery_overlay_image', (isset($parents['overlay_image']) && !empty($parents['overlay_image']) ? $GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($parents['overlay_image']) : ''));
				$tpl->SetVariable('gallery_columns', $xss->filter($parents['columns']));
				$tpl->SetVariable('gallery_order', ($parents['order'] == 'sequential' ? 'yes' : $parents['order']));
				$tpl->SetVariable('gallery_timer', $xss->filter(((int)$parents['timer']*1000)));
				$tpl->SetVariable('gallery_show_buttons', ($parents['show_buttons'] == 'Y' ? 'yes' : 'no'));
				$tpl->SetVariable('gallery_button_pos', $xss->filter($parents['button_pos']));
				$tpl->SetVariable('gallery_image_offsetx', $xss->filter($parents['image_offsetx']));
				$tpl->SetVariable('gallery_image_offsety', $xss->filter($parents['image_offsety']));
				$tpl->SetVariable('gallery_text_pos', $xss->filter($parents['text_pos']));
				$tpl->SetVariable('gallery_textbar', $xss->filter($parents['textbar']));
				$tpl->SetVariable('gallery_textbar_color', '0x'.$parents['textbar_color']);
				$tpl->SetVariable('gallery_textbar_height', $xss->filter($parents['textbar_height']));
				$tpl->SetVariable('gallery_textbar_alpha', $xss->filter($parents['textbar_alpha']));
				$tpl->SetVariable('gallery_allow_fullscreen', ($parents['allow_fullscreen'] == 'Y' ? 'true' : 'false'));
				//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
				
				// the images
				$items = $model->GetPostsOfFlashGallery($parents['id']);
				$posts = array();
				foreach ($items as $item) {
					if (isset($item['image']) && !empty($item['image']) && $item['active'] == "Y") {
						$item['image'] = $xss->filter(strip_tags($item['image']));
						$thumb = Jaws_Image::GetThumbPath($item['image']);
						$medium = Jaws_Image::GetMediumPath($item['image']);
						if (file_exists(JAWS_DATA . 'files'.$thumb)) {
							$image = $thumb;
						} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
							$image = $medium;
						} else if (file_exists(JAWS_DATA . 'files'.$item['image'])) {
							$image = $item['image'];
						}
						if (file_exists(JAWS_DATA . 'files'.$item['image'])) {
							$full_image = $item['image'];
						}
						if (!empty($image) && !empty($full_image)) {
							$ext = end(explode('.', $image));  
							if(in_array(strtolower($ext),$good_ext)) { 
								$item['ext'] = $ext;
								$item['image'] = $image;
								$item['full_image'] = $full_image;
								$posts[] = $item;
							}
						}
					}
				}
				
				$countPosts = count($posts);
				
				reset($posts);
				$pageconst = ((int)$parents['columns']*2);
				//$carousels = (float)($countPosts / $pageconst);
				$tpl->SetVariable('gallery_page', $pageconst);
				$c = 0;
				for ($i = 0; $i < $countPosts; $i++) {		            
					$tpl->SetBlock('layout/gallery/item');
					$tpl->SetVariable('gallery_id', $cid);
					$tpl->SetVariable('image_id', $i+1);
					/*
					if (($c+$pageconst) <= $countPosts) {
						$endcount = ($c+$pageconst);
					} else {
						$endcount = $countPosts;
					}
					*/
					
					//for ($p=$c;$p<$endcount;$p++) {
						$tpl->SetBlock('layout/gallery/item/image');
						$tpl->SetVariable('gallery_id', $cid);
						$tpl->SetVariable('image_id', $i+1);
						if (isset($parents['watermark_image']) && !empty($parents['watermark_image']) && file_exists(JAWS_DATA . 'files'.$parents['watermark_image']) && in_array(strtolower($posts[$i]['ext']),$wm_ext)) {
							$watermark_image = $parents['watermark_image'];
							if (isset($posts[$i]['url']) && !empty($posts[$i]['url']) && $posts[$i]['url'] != 'javascript:void(0);') {
								$url = $xss->filter($posts[$i]['url']);
								$url_target = $posts[$i]['url_target'];
							} else {
								$url = $GLOBALS['app']->getSiteURL().'/index.php?gadget=FileBrowser&action=Watermark&path='.urlencode($posts[$i]['full_image']).'&wm='.urlencode($watermark_image);
								$url_target = '_blank';
							}
							$tpl->SetVariable('image_src', $GLOBALS['app']->getSiteURL().'/index.php?gadget=FileBrowser&action=Watermark&path='.urlencode($posts[$i]['image']).'&wm='.urlencode($watermark_image));
						} else {
							if (isset($posts[$i]['url']) && !empty($posts[$i]['url']) && $posts[$i]['url'] != 'javascript:void(0);') {
								$url = $xss->filter($posts[$i]['url']);
								$url_target = $posts[$i]['url_target'];
							} else {
								$url = $GLOBALS['app']->getDataURL('', true) . 'files'.$posts[$i]['full_image'];
								$url_target = '_blank';
							}
							$tpl->SetVariable('image_src', $GLOBALS['app']->getDataURL('', true) . 'files'.$posts[$i]['image']);
						}
						$tpl->SetVariable('image_url', $url);
						$tpl->SetVariable('image_target', $url_target);
						$tpl->SetVariable('image_alt', (isset($posts[$i]['description']) && !empty($posts[$i]['description']) ? htmlentities(strip_tags($posts[$i]['description'])) : (isset($posts[$i]['title']) && !empty($posts[$i]['title']) ? htmlentities(strip_tags($posts[$i]['title'])) : "Image ".($i+1)." of ".$countPosts)));
						$tpl->SetVariable('image_caption', (isset($posts[$i]['description']) && !empty($posts[$i]['description']) ? Jaws_Gadget::ParseText($posts[$i]['description'], 'FlashGallery') : (isset($posts[$i]['title']) && !empty($posts[$i]['title']) ? '<p>'.Jaws_Gadget::ParseText($posts[$i]['title'], 'FlashGallery').'</p>' : "Image ".($i+1)." of ".$countPosts)));
						$tpl->ParseBlock('layout/gallery/item/image');
						//$c++;
					//}
					$tpl->ParseBlock('layout/gallery/item');
				}
				
				//$tpl->SetVariable('layout_content', _t('FLASHGALLERY_LAYOUT_GALLERY_DESCRIPTION'));
				$tpl->ParseBlock('layout/gallery');
				// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
				$display_id = md5('FlashGallery'.$cid);
				if ($embedded == true && !is_null($referer)) {	
					$tpl->SetBlock('layout/embedded');
					$tpl->SetVariable('id', $display_id);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->SetVariable('bool_resize', "0");		        
					$tpl->ParseBlock('layout/embedded');
				} else {
					$tpl->SetBlock('layout/not_embedded');
					$tpl->SetVariable('id', $display_id);		        
					$tpl->ParseBlock('layout/not_embedded');
				}

				$tpl->ParseBlock('layout');
				return $tpl->Get();
			/*
			} else {
				return $this->Slideshow($parents['id']);
			}
			*/
		}
		
    }

	/**
     * Display photos one at a time, "Slideshow" style.
     *
     * @category 	feature
     * @param 	int 	$cid 	Gallery ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function Slideshow($cid = 1, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/FlashGallery/resources/style.css', 'stylesheet', 'text/css');
		//$GLOBALS['app']->Layout->AddScriptLink('libraries/js/swfobject.js');			
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');

		//if on a users home page, show their stuff
		if (strtolower($get['gadget']) == 'users' && !empty($get['id'])) {
			$parents = $model->GetSingleFlashGalleryByUserID($get['id'], $cid);
		} else {
			$parents = $model->GetFlashGallery($cid);
		}
		if (!Jaws_Error::IsError($parents) && isset($parents['id']) && !empty($parents['id'])) {
			//if ($parents['type'] == 'slideshow') {
				$good_ext = array('jpg', 'jpeg', 'swf', 'gif', 'png', 'tif', 'bmp');
				$wm_ext = array('jpg', 'jpeg', 'gif', 'png');
				require_once JAWS_PATH . 'include/Jaws/Template.php';
				$tpl = new Jaws_Template('gadgets/FlashGallery/templates/');
				$tpl->Load('normal.html');

				$tpl->SetBlock('layout');
				$GLOBALS['app']->Layout->AddScriptLink('libraries/slideshow/slideshow-min.js');

				$tpl->SetVariable('actionName', 'Slideshow_' . $parents['id'] . '_');
				//$tpl->SetVariable('link', "?gadget=FlashGallery");
				$tpl->SetVariable('layout_title', $parents['title']);

				$tpl->SetBlock('layout/slideshow');
				//foreach($galleryParent as $parents) {		            
					// set "slideshow" swfobject variables
					//$tpl->SetVariable('base_url', JAWS_DPATH);
					$tpl->SetVariable('id', $cid);
					$slideshow_background_image = '';
					if (isset($parents['background_image']) && !empty($parents['background_image'])) {
						$parents['background_image'] = $xss->filter(strip_tags($parents['background_image']));
						if (file_exists(JAWS_DATA . 'files'.$parents['background_image'])) {
							$slideshow_background_image = 'url('.$GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($parents['background_image']).') no-repeat fixed center';
						}
					}
					$tpl->SetVariable('slideshow_loading_image', $slideshow_background_image);
					$tpl->SetVariable('slideshow_background_color', (!empty($parents['background_color']) ? '#'.$xss->filter($parents['background_color'].' ') : ' '));
					// build dimensions
					$slideshow_dimensions = '';
					$slideshow_dimensions .= " 	var flash_slideshow_".$cid."_width = 0;\n";	
					$slideshow_dimensions .= " 	var flash_slideshow_".$cid."_height = 0;\n";	
					//$slideshow_dimensions .= "$('flash-slideshow-".$cid."').style.height = '317px';\n";
					if ($parents['width'] == 'auto') {
						$slideshow_dimensions .= "if ($('flash-slideshow-".$cid."').parentNode) {\n";
						$slideshow_dimensions .= " 	flash_slideshow_".$cid."_width = parseInt($('flash-slideshow-".$cid."').parentNode.offsetWidth);\n";	
						$slideshow_dimensions .= "}\n";	
					} else {
						$slideshow_dimensions .= "flash_slideshow_".$cid."_width = ".(int)$parents['custom_width'].";\n";	
					}
					$image_height = '';
					if ($parents['height'] != 'auto' && !empty($parents['custom_height'])) {
						$slideshow_dimensions .= "flash_slideshow_".$cid."_height = ".(int)$parents['custom_height'].";\n";	
						$image_height = "height: ".(int)$parents['custom_height']."px; ";	
					} else {
						switch($parents['aspect_ratio']) {
							case "3:1": 
								$slideshow_dimensions .= " 	flash_slideshow_".$cid."_height = parseInt(flash_slideshow_".$cid."_width*(0.33333333333333333333333333333333));\n";
								break;
							case "16:9": 
								$slideshow_dimensions .= " 	flash_slideshow_".$cid."_height = parseInt(flash_slideshow_".$cid."_width*(.5625));\n";
								break;
							case "4:3": 
								$slideshow_dimensions .= " 	flash_slideshow_".$cid."_height = parseInt(flash_slideshow_".$cid."_width*(.75));\n";
								break;
							case "1:1": 
								$slideshow_dimensions .= " 	flash_slideshow_".$cid."_height = flash_slideshow_".$cid."_width;\n";
								break;
						}
					}
					$slideshow_dimensions .= "if (flash_slideshow_".$cid."_width == 0) {\n";
					$slideshow_dimensions .= " 	flash_slideshow_".$cid."_width = 950;\n";	
					$slideshow_dimensions .= "}\n";	
					$slideshow_dimensions .= "if (flash_slideshow_".$cid."_height == 0) {\n";
					$slideshow_dimensions .= " 	flash_slideshow_".$cid."_height = 317;\n";	
					$slideshow_dimensions .= "}\n";	
					$slideshow_dimensions .= "$('flash-slideshow-".$cid."').style.width = flash_slideshow_".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "$('flash-slideshow-".$cid."').style.height = flash_slideshow_".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "$('slideshow_overlay".$cid."').style.width = flash_slideshow_".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "$('slideshow_overlay".$cid."').style.height = flash_slideshow_".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "$('PlayButton".$cid."').style.top = (flash_slideshow_".$cid."_height-50)*(-1)+'px';\n";
					$slideshow_dimensions .= "$('PauseButton".$cid."').style.top = (flash_slideshow_".$cid."_height-50)*(-1)+'px';\n";
					$slideshow_dimensions .= "$('slide-caption".$cid."').style.width = (flash_slideshow_".$cid."_width-90)+'px';\n";
					$slideshow_dimensions .= "$$('#flash-slideshow-".$cid." .fade-box').each(function(element){element.setStyle({width: flash_slideshow_".$cid."_width+'px'});});\n";
					$slideshow_dimensions .= "$$('#flash-slideshow-".$cid."').each(function(element){element.up('.layout_body').setStyle({minHeight: flash_slideshow_".$cid."_height+'px'});});\n";
					$slideshow_dimensions .= "$$('#flash-slideshow-".$cid." .fade-box img').each(function(element){element.setStyle({height: flash_slideshow_".$cid."_height+'px'});});\n";
					$tpl->SetVariable('slideshow_title', $xss->filter($parents['title']));
					$slideshow_overlay_image = '';
					if (isset($parents['overlay_image']) && !empty($parents['overlay_image'])) {
						$parents['overlay_image'] = $xss->filter(strip_tags($parents['overlay_image']));
						if (file_exists(JAWS_DATA . 'files'.$parents['overlay_image'])) {
							$slideshow_overlay_image = 'url('.$GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($parents['overlay_image']).')';
						}
					}
					$tpl->SetVariable('slideshow_overlay_image', $slideshow_overlay_image);
					$tpl->SetVariable('slideshow_timer', (int)$parents['timer']*1000);
					//$tpl->SetVariable('slideshow_load_immediately', ($parents['load_immediately'] == 'N' ? 'true' : 'false'));
					//$tpl->SetVariable('slideshow_image_move', $xss->filter($parents['image_move']));
					//$tpl->SetVariable('slideshow_show_buttons', ($parents['show_buttons'] == 'Y' ? 'yes' : 'no'));
					//$tpl->SetVariable('slideshow_button_pos', $xss->filter($parents['button_pos']));
					//$tpl->SetVariable('slideshow_image_offsetx', $xss->filter($parents['image_offsetx']));
					//$tpl->SetVariable('slideshow_image_offsety', $xss->filter($parents['image_offsety']));
					//$tpl->SetVariable('slideshow_text_move', $xss->filter($parents['text_move']));
					if ($parents['show_text'] == 'N') {
							$slideshow_dimensions .= "$('slide-caption".$cid."').style.display = 'none';\n";
					} else {
						if (strpos($parents['text_pos'], "center") !== false) {
							$slideshow_dimensions .= "$('slide-caption".$cid."').style.textAlign = 'center';\n";
						} else if ($parents['text_pos'] == "bottom_right") {
							$slideshow_dimensions .= "$('slide-caption".$cid."').style.textAlign = 'right';\n";
						} else {
							$slideshow_dimensions .= "$('slide-caption".$cid."').style.textAlign = 'left';\n";
						}
					}
					$tpl->SetVariable('slideshow_dimensions', $slideshow_dimensions);
					if ($parents['textbar'] == 'solid') {
						$tpl->SetVariable('slideshow_textbar_bkgnd', '#'.$parents['textbar_color'].';');
					} else {
						$tpl->SetVariable('slideshow_textbar_bkgnd', 'url('.$GLOBALS['app']->GetJawsURL().'/images/transparent.png) 0 0;');
					}
					$posts = $model->GetPostsOfFlashGallery($cid);
					if (!Jaws_Error::IsError($posts)) {
						$image_found = false;
						$post_count = count($posts);
						if (!$post_count <= 0) {
							$tpl->SetVariable('slideshow_total', $post_count);
							if ($post_count == 1 || $parents['load_immediately'] == 'Y') {
								$tpl->SetBlock('layout/slideshow/stop');
								$tpl->SetVariable('id', $cid);
								$tpl->SetVariable('slideshow_timer', (int)$parents['timer']*1000);
								$tpl->ParseBlock('layout/slideshow/stop');
							}
							reset($posts);
							$i = 0;
							foreach($posts as $post) {		            
								if (isset($post['image']) && !empty($post['image']) && $post['active'] == "Y") {
									$post['image'] = $xss->filter(strip_tags($post['image']));
									$medium = Jaws_Image::GetMediumPath($post['image']);
									if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$image = $medium;
									} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
										$image = $post['image'];
									}
									if (!empty($image)) {
										$ext = end(explode('.', $image));  
										if(in_array(strtolower($ext),$good_ext)) { 
											$image_found = true;
											$image_style = ' style="';
											if ($i > 0) {
												$image_style .= ' display: none;';
											}
											$image_style .= '"';
											$tpl->SetBlock('layout/slideshow/image');
											$image_src = $GLOBALS['app']->getDataURL('', true) . 'files'.$image;
											$watermark_image = $xss->filter($parents['watermark_image']);
											$url = '';
											$url_target = '';
											if (!empty($watermark_image) && file_exists(JAWS_DATA . 'files'.$watermark_image) && in_array(strtolower($ext),$wm_ext)) {
												if (isset($post['url']) && !empty($post['url']) && $post['url'] != 'javascript:void(0);') {
													$url = $xss->filter($post['url']);
													$url_target = $xss->filter($post['url_target']);
												//} else {
													//$url = $GLOBALS['app']->getSiteURL().'/index.php?gadget=FileBrowser&action=Watermark&path='.urlencode($image).'&wm='.urlencode($watermark_image);
													//$url_target = '_blank';
												}
												$image_src = $GLOBALS['app']->getSiteURL().'/index.php?gadget=FileBrowser&action=Watermark&path='.urlencode($image).'&wm='.urlencode($watermark_image);
											} else {
												if (isset($post['url']) && !empty($post['url']) && $post['url'] != 'javascript:void(0);') {
													$url = $xss->filter($post['url']);
													$url_target = $xss->filter($post['url_target']);
												//} else {
													//$url = $GLOBALS['app']->getDataURL('', true) . 'files'.$image;
													//$url_target = '_blank';
												}
											}
											$tpl->SetVariable('image_url', $url);
											$tpl->SetVariable('image_target', $url_target);
											$tpl->SetVariable('image_style', $image_style);
											$tpl->SetVariable('image_id', $i);
											$tpl->SetVariable('image_src', $image_src);
											$tpl->SetVariable('replace_image', $GLOBALS['app']->GetJawsURL().'/images/blank.gif');
											$tpl->SetVariable('image_linkid', $cid);
											$tpl->SetVariable('image_height', $image_height);
											$tpl->SetVariable('image_alt', htmlentities(strip_tags((isset($post['description']) && !empty($post['description']) ? $post['description'] : $post['title']))));
											$tpl->SetVariable('image_caption', (isset($post['description']) && !empty($post['description']) ? Jaws_Gadget::ParseText($post['description'], 'FlashGallery') : '<p>'.Jaws_Gadget::ParseText($post['title'], 'FlashGallery').'</p>'));
											$tpl->SetVariable('image_count', $i);
											$tpl->ParseBlock('layout/slideshow/image');
											$i++;
											if ($post_count == 1) {
												$tpl->SetBlock('layout/slideshow/image');
												$tpl->SetVariable('image_url', $url);
												$tpl->SetVariable('image_target', $url_target);
												$tpl->SetVariable('image_style', ' style="display: none;"');
												$tpl->SetVariable('image_id', $i);
												$tpl->SetVariable('image_linkid', $cid);
												$tpl->SetVariable('image_src', $image_src);
												$tpl->SetVariable('replace_image', $GLOBALS['app']->GetJawsURL().'/images/blank.gif');
												$tpl->SetVariable('image_height', $image_height);
												$tpl->SetVariable('image_alt', htmlentities(strip_tags((isset($post['description']) && !empty($post['description']) ? $post['description'] : $post['title']))));
												$tpl->SetVariable('image_caption', (isset($post['description']) && !empty($post['description']) ? Jaws_Gadget::ParseText($post['description'], 'FlashGallery') : '<p>'.Jaws_Gadget::ParseText($post['title'], 'FlashGallery').'</p>'));
												$tpl->SetVariable('image_count', $i);
												$tpl->ParseBlock('layout/slideshow/image');
											}
										}
									}
								}
							}
						}
						if ($post_count <= 0 || $image_found === false) {
							$tpl->SetBlock('layout/slideshow/image');
							$tpl->SetVariable('image_id', '0');
							$tpl->SetVariable('image_linkid', $cid);
							$tpl->SetVariable('image_src', $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/gallery_no_images.jpg");
							$tpl->SetVariable('replace_image', $GLOBALS['app']->GetJawsURL().'/images/blank.gif');
							$tpl->SetVariable('image_caption', "No images were found in this slideshow.");
							$tpl->ParseBlock('layout/slideshow/image');
						}
					}
					
					//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
				//}
						
				//$tpl->SetVariable('layout_content', _t('FLASHGALLERY_LAYOUT_SLIDESHOW_DESCRIPTION'));
				$tpl->ParseBlock('layout/slideshow');
				// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
				$display_id = md5('FlashGallery'.$cid);
				if ($embedded == true && !is_null($referer)) {	
					$tpl->SetBlock('layout/embedded');
					$tpl->SetVariable('id', $display_id);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->ParseBlock('layout/embedded');
				} else {
					$tpl->SetBlock('layout/not_embedded');
					$tpl->SetVariable('id', $display_id);		        
					$tpl->ParseBlock('layout/not_embedded');
				}

				$tpl->ParseBlock('layout');

				return $tpl->Get();
			/*
			} else {
				return $this->Gallery($parents['id']);
			}
			*/
		}
    }

	/**
     * Display a random single photo from a gallery.
     *
     * @param 	int 	$cid 	Gallery ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function ShowOne($cid = 1, $embedded = false, $referer = null)
    {
		// for boxover on date highlighting
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/FlashGallery/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/swfobject.js');			
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		// send FlashGallery records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');
		
		//if on a users home page, show their stuff
		if (strtolower($get['gadget']) == 'users' && !empty($get['id'])) {
			$parents = $model->GetSingleFlashGalleryByUserID($get['id'], $cid);
		} else {
			$parents = $model->GetFlashGallery($cid);
		}
		if (!Jaws_Error::IsError($parents) && isset($parents['id']) && !empty($parents['id'])) {
			require_once JAWS_PATH . 'include/Jaws/Template.php';
			$tpl = new Jaws_Template('gadgets/FlashGallery/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'ShowOne_' . $parents['id'] . '_');
			//$tpl->SetVariable('link', "?gadget=FlashGallery");
	        $tpl->SetVariable('layout_title', $parents['title']);

	        $tpl->SetBlock('layout/single');
			/*
			foreach($galleryParent as $parents) {		            
					// set "gallery" swfobject variables
					$tpl->SetVariable('id', $cid);
					$tpl->SetVariable('single_loading_image', '');
					$tpl->SetVariable('single_background_color', $xss->filter($parents['background_color']));
					$tpl->SetVariable('single_width', $single_width);
					$tpl->SetVariable('single_height', $single_height);
					$tpl->SetVariable('single_title', $xss->filter($parents['title']));
					$url = $GLOBALS['app']->GetSiteURL() .'/'. BASE_SCRIPT .'?gadget=FlashGallery&action=GalleryXML&id='.$cid;
					$tpl->SetVariable('single_url', $url);
					$tpl->SetVariable('single_columns', $xss->filter($parents['columns']));
					$tpl->SetVariable('single_order', $xss->filter($parents['order']));
					$tpl->SetVariable('single_timer', $xss->filter($parents['timer']));
					$tpl->SetVariable('single_show_buttons', $xss->filter($parents['show_buttons']));
					$tpl->SetVariable('single_button_pos', $xss->filter($parents['button_pos']));
					$tpl->SetVariable('single_image_offsetx', $xss->filter($parents['image_offsetx']));
					$tpl->SetVariable('single_image_offsety', $xss->filter($parents['image_offsety']));
					$tpl->SetVariable('single_text_pos', $xss->filter($parents['text_pos']));
					$tpl->SetVariable('single_allow_fullscreen', $xss->filter($parents['allow_fullscreen']));
					//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
			}
			*/		
			$tpl->SetVariable('layout_content', _t('FLASHGALLERY_LAYOUT_SHOWONE_DESCRIPTION'));
			$tpl->SetVariable('id', $parents['id']);
	        $tpl->ParseBlock('layout/single');

			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
			$display_id = md5('FlashGallery'.$cid);
			if ($embedded == true && !is_null($referer)) {	
				$tpl->SetBlock('layout/embedded');
				$tpl->SetVariable('id', $display_id);		        
				if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
					$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
				} else {	
					$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
				}
				$tpl->ParseBlock('layout/embedded');
			} else {
				$tpl->SetBlock('layout/not_embedded');
				$tpl->SetVariable('id', $display_id);		        
				$tpl->ParseBlock('layout/not_embedded');
			}

	        $tpl->ParseBlock('layout');

	        return $tpl->Get();
		}
    }

}
