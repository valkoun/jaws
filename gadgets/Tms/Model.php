<?php
/**
 * TMS (Theme Management System) Gadget Model
 *
 * @category   GadgetModel
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class TmsModel extends Jaws_Model
{
    /**
     * Returns true if theme exists in themes/ directory
     *
     * @access  public
     * @param   string  $theme  Theme to check
     * @return  boolean Returns true if theme exists otherwise we return false
     */
    function themeExists($theme)
    {
		if (substr(strtolower($theme), 0, 4) == 'http') {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Tms');
			if(!$snoopy->fetch($theme.'/style.css')) {
				return false;
			}
			if(!$snoopy->fetch($theme.'/layout.html')) {
				return false;
			}
		} else {
			$themeDir = JAWS_DATA . 'themes/' . $theme;
			if (!is_dir($themeDir)) {
				return false;
			}

			//It should have a layout.html file
			if (!file_exists($themeDir . '/layout.html')) {
				return false;
			}
		}
        return true;
    }
    
    /**
     * The theme is shared?
     *
     * @access  public
     * @param   string  $theme   Theme to check
     * @return  boolean True (shared) or False (not shared)
     */
    function isThemeShared($theme)
    {
        $sql = '
             SELECT [id]
             FROM [[tms_themes]] WHERE
             [theme] = {theme}';

        $params          = array();
        $params['theme'] = $theme;
        
        $res  = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($res) || empty($res)) {
            return false;
        }
        
        return isset($res['id']);
    }

    /**
     * Returns information of a local theme, this information should be 
     * provided by a file named Info.php which should be in the theme root 
     * directory.
     *
     * The information this file has is provided by an array with the following
     * indexes:
     *
     *  - name: Theme name
     *  - desc: Theme description
     *  - version: Theme version
     *  - license: Theme license
     *  - authors: A multi array with the following information: 
     *       ('authorposition', 'authorusername', 'authorname', 'author email')
     *
     * If we can't find this file then we return those values as empty
     * in an array which has the following indexes: name, desc, author
     *
     * @access  public
     * @param   string  $theme        Theme name
     * @param   mixed   $repository   Can be 'local' or an integer (a remote repository)
     * @return  array   Indexed array with theme information
     */
    function getThemeInfo($theme, $repository = 'local')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tInfo = array('name'     => $theme,
                       'desc'     => '',
                       'file'     => '',
                       'image'    => '',
                       'mode'     => 'local',
                       'version'  => '0.1',
                       'license'  => '',
                       'isshared' => $this->isThemeShared($theme),
                       'authors'  => array());
        
        if ($repository == 'local') {
            if (!$this->themeExists($theme)) {
                //Maybe its shared but doesn't exists directly on themes/
                if ($tInfo['isshared'] === true) {
                    $tInfo['file'] = 'themes/repository/up/' . $theme . '.zip';
                }
                return $tInfo;
            }
            
            $themeDir  = JAWS_DATA . 'themes/' . $theme;
            $themeConfig = $themeDir . '/Info.php';
            if (file_exists($themeConfig)) {
                require_once $themeConfig;
                if (isset($info)) {
                    foreach($tInfo as $k => $v) {
                        if (isset($info[$k])) {
                            $value     = (gettype($v) == 'string') ? $xss->filter($info[$k]) : $info[$k];
                            $tInfo[$k] = $value;
                        }
                    }
                    //Clean authors array, to only have 2 indexes
                    if (isset($tInfo['authors']) && is_array($tInfo['authors'])) {
                        for($i=0; $i<count($tInfo['authors']); $i++) {
                            //If it has more/less than 4 indexes, drop it
                            if (count($tInfo['authors'][$i]) !== 4) {
                                unset($tInfo['authors'][$i]);
                            } else {
                                $tInfo['authors'][$i] = array($tInfo['authors'][$i][3],
                                                              $tInfo['authors'][$i][2]);
                            }
                        }
                    }
                }
            }
            
            if (file_exists($themeDir . '/example.png')) {
                $tInfo['image'] = 'data/themes/'. $theme .'/example.png';
            }

            $themeLocation = '';
            if ($tInfo['isshared'] === true) {
                $themeLocation = 'themes/repository/up/' . $theme . '.zip';
            } else {
                $themeLocation = 'themes/' . $theme;
            }            
            $tInfo['file'] = $themeLocation;            
        } else {
            $repInfo = $this->getRepository($repository);
            if (!isset($repInfo['id'])) {
                return $tInfo;
            }

			/*
			require_once 'Cache/Lite.php';
            $cacheId  = md5($repository['url']).'.repserial';
            //Cache dir
            $cacheDir = JAWS_DATA . 'cache/tms';
            //Cache options
            $options = array(
                             'cacheDir' => $cacheDir,
                             'lifeTime' => 7200, //(2 hours)
                             );
            
            $cache = new Cache_Lite($options);
            // Test if there is a valide cache item for this data
            if($themes = $cache->get($cacheId)) {
                $themes = unserialize($data);
            } else {
			*/
                $themes = $this->getThemes($repository);
            //}
            if (isset($themes[$theme])) {
                return $themes[$theme];
            }
        }
        return $tInfo;
    }
    
    /**
     * Returns an array with all (or some part) theme
     * repositories
     *
     * @access  public
     * @param   mixed   $limit  Data limit (can be integer or null, for no limit)
     * @return  array   List of repositories
     */
    function getRepositories($limit = null)
    {
        $sql = '
            SELECT
                [id], [name], [url]
            FROM [[tms_repositories]]
            ORDER BY [id] DESC';

        if (is_int($limit)) {
            $result = $GLOBALS['db']->setLimit(10, $limit);
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
     * Return an array with the information of a theme repository
     *
     * @access  public
     * @param   int      $id     Repository ID
     * @return  array    Repository information or Jaws_Error on any error
     */
    function getRepository($id)
    {
        $params           = array();
        $params['id']     = $id;

        $sql = '
            SELECT
                [id], [name], [url]
            FROM [[tms_repositories]]
            WHERE [id] = {id}';

        $row = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error($row->getMessage(), 'SQL');
        }

        if (isset($row['name'])) {
            return $row;
        }

        return new Jaws_Error(_t('TMS_ERROR_REPOSITORY_DOES_NOT_EXISTS'));
    }
    
    /**
     * Returns an array with the theme authors name and email, or an empty array 
     * if there aren't authors
     *
     * @access  public
     * @param   string   $theme  Theme name
     * @return  array    Theme author(s)
     */
    function getThemeAuthors($theme)
    {
        $id  = $this->isThemeShared($theme);
        if ($id === false) {
            return array();
        }
        //Ok, get the authors
        $sql = '
           SELECT
            [author_name]  AS name,
            [author_email] AS email
           FROM [[tms_authors]]
           WHERE
            [theme_id] = {id}';

        $rs  = $GLOBALS['db']->queryAll($sql, array('id' => $id));
        if (Jaws_Error::isError($rs)) {
            return array();
        }
        return $rs;
    }

    /**
     * Create RSS of the shared themes
     *
     * @access  public
     * @param   boolean  $write  Flag that determinates if it should returns the RSS
     * @return  mixed    Returns the RSS(string) if it was required, or true
     */
    function makeRSS($write = false)
    {
        $atom = $this->GetAtomStruct();
        if (Jaws_Error::IsError($atom)) {
            return $atom;
        }

        if ($write) {
            if (!Jaws_Utils::is_writable(JAWS_DATA . 'xml/')) {
                return new Jaws_Error(_t('TMS_ERROR_WRITING_RSSFILE'), _t('TMS_NAME'));
            }

            ///FIXME we need to do more error checking over here
            @file_put_contents(JAWS_DATA . 'xml/themes.rss', $atom->ToRSS2());
            //Chmod!
            Jaws_Utils::chmod(JAWS_DATA . 'xml/themes.rss');
        }

        return $atom->ToRSS2();
    }

    /**
     * Returns an array with the themes available locally or in
     * a repository
     *
     * @access  public
     * @param   mixed    $repository   Can be 'local' or an integer (a remote repository)
     * @return  array    Themes list
     */
    function getThemes($repository)
    {
        $repInfo = $this->getRepository($repository);
        if (!Jaws_Error::isError($repInfo)) {
            /*
			require_once 'Cache/Lite.php';            
            $cacheId = md5($repInfo['url']).'.repserial';
            //RSS data
            $data = array();
            $dir  = JAWS_DATA . 'cache/tms/';
            //Cache options
            $options = array(
                             'cacheDir' => $dir,
                             'lifeTime' => 7200, //(2 hours)
                             );

            $cache = new Cache_Lite($options);
            // Test if there is a valide cache item for this data
			if($data = $cache->get($cacheId)) {
                $data = unserialize($data);
                return $data;
            } else {
                */
				require_once 'XML/RSS.php';
                $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
                // Cache miss
                $rss = new XML_RSS($repInfo['url'], 'utf-8', 'utf-8');
                $rss->parse();
                $data = array();
                foreach($rss->getItems() as $item) {
                    $image = '';
                    if (isset($item['enclosures']) && is_array($item['enclosures'])) {
                        if (isset($item['enclosures'][0])) {
                            $image = $item['enclosures'][0];
                            if (isset($image['url'])) {
                                $image = $image['url'];
                            }
                        }
                    }

                    //Prepare authors..
                    $authors = array();
                    if (isset($item['author']) && !empty($item['author'])) {
                        //Check the 
                        $authors[] = $this->_parseRSSAuthor($item['author']);
                    }
                    
                    //Now check 3rd authors (contributors)
                    if (isset($item['contributor']) && is_array($item['contributor'])) {
                        foreach($item['contributor'] as $contrib) {
                            if (empty($contrib)) {
                                continue;
                            }
                            $authors[] = $this->_parseRSSAuthor($contrib);
                        }
                    }
                    $data[trim($item['title'])] = array(
                                                        'name'     => trim($xss->filter($item['title'])),
                                                        'desc'     => trim($xss->filter($item['description'])),
                                                        'file'     => $xss->filter($item['link']),
                                                        'image'    => $xss->filter($image),
                                                        'authors'  => $authors,
                                                        'mode'     => 'remote',
                                                        'license'  => '',
                                                        'version'  => '',
                                                        'isshared' => $this->isThemeShared($item['title']),
                                                        );
                }
                //$cache->save(serialize($data));
                return $data;
            //}
        } else {
            $result = array();
            $themes = Jaws_Utils::GetThemesList(false);
            foreach($themes as $theme) {
                $result[$theme] = $this->getThemeInfo($theme);
            }
			return $result;
        }
    }
    
    /**
     * Parses the author value that comes in RSS.
     *
     * The RSS is generated by Jaws, so we know that the format is:
     *
     *         foo@example.com (Foobar Name)
     */
    function _parseRSSAuthor($authorStr) 
    {
        if (preg_match('/(.*?)\s\((.+?)\)/i', $authorStr, $matches)) {
            return array($matches[1], $matches[2]);
        } else {
            return array('', '');
        }
    }

    /**
     * Creates the Atom struct
     *
     * @access  public
     * @return  object  Atom structure
     */
    function getAtomStruct()
    {
        if (isset($this->_Atom) && is_array($this->_Atom->Entries) && count($this->_Atom->Entries) > 0) {
            return $this->_Atom;
        }

        require_once JAWS_PATH . 'include/Jaws/AtomFeed.php';
        $this->_Atom = new Jaws_AtomFeed();

        $sql = '
            SELECT
                st.[theme],
                st.[description],
                st.[updatetime]
            FROM [[tms_themes]] st       
            ORDER BY [theme] DESC';

        $types  = array('string', 'string', 'timestamp');
        $result = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('TMS_ERROR_GETTING_ATOMSTRUCT'), _t('TMS_NAME'));
        }

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $siteURL = $GLOBALS['app']->GetSiteURL();
        $url = (( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://').
               strip_tags($_SERVER['SERVER_NAME']);
        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            $url .= $xss->filter($_SERVER['SCRIPT_NAME']);
        } else {
            $url .= $xss->filter($_SERVER['REQUEST_URI']);
        }

        $this->_Atom->SetTitle($GLOBALS['app']->Registry->Get('/config/site_name'));
        $this->_Atom->SetLink($url);
        $this->_Atom->SetSiteURL($siteURL);
        /// FIXME: Get an IRI from the URL or something...
        $this->_Atom->SetId($siteURL);
        $this->_Atom->SetTagLine($GLOBALS['app']->Registry->Get('/config/site_slogan'));
        $this->_Atom->SetAuthor($GLOBALS['app']->Registry->Get('/config/site_author'),
                                $siteURL,
                                $GLOBALS['app']->Registry->Get('/network/site_email'));
        $this->_Atom->SetGenerator('JAWS '.$GLOBALS['app']->Registry->Get('/version'));
        $this->_Atom->SetCopyright($GLOBALS['app']->Registry->Get('/config/copyright'));
        $date = $GLOBALS['app']->loadDate();
        foreach ($result as $r) {
            //Theme zip file
            $zipFile = JAWS_DATA . 'themes/repository/up/' . $r['theme'] . '.zip';
            $entry = new AtomEntry();
            $entry->SetTitle($r['theme']);
            $url   = $GLOBALS['app']->getDataURL(substr($zipFile, (strlen(JAWS_DATA))), true);
            //$url   = $siteURL . '/data/themes/'.$r['theme'];

            $entry->SetLink($url);
            $entry->SetId($url);
            $enclosure = JAWS_DATA . 'themes/repository/images/' . $r['theme'] . '.png';
            if (file_exists($enclosure)) {
                $size = filesize($enclosure);
                $entry->AddEnclosure($GLOBALS['app']->getDataURL(substr($enclosure, (strlen(JAWS_DATA))), true),
                                     $size, 
									 'image/png');
            }
            $content = $xss->parse($r['description']);
            
            $entry->SetSummary($content, 'html');
            $entry->SetContent($content, 'html');
            
            //Get theme authors
            $tAuthors = $this->getThemeAuthors($r['theme']);
            if (count($tAuthors) > 0) {
                $entry->SetAuthor($tAuthors[0]['name'], $this->_Atom->Link->HRef, $tAuthors[0]['email']);
                //We have more entries?
                if (isset($tAuthors[1])) {
                    for($i=1; $i<count($tAuthors); $i++) {
                        //Add the author as a contributor
                        $entry->AddContributor($tAuthors[$i]['name'],
                                               $this->_Atom->Link->HRef,
                                               $tAuthors[$i]['email']);
                    }
                }
            } else {
                $entry->SetAuthor('Anonymous', $this->_Atom->Link->HRef, '');
            }
            $entry->SetPublished($date->ToISO($r['updatetime']));
            $entry->SetUpdated($date->ToISO($r['updatetime']));
            
            $this->_Atom->AddEntry($entry);
            if (!isset($last_modified)) {
                $last_modified = $r['updatetime'];
            }
        }

        if (isset($last_modified)) {
            $this->_Atom->SetUpdated($date->ToISO($last_modified));
        } else {
            $this->_Atom->SetUpdated($date->ToISO(date('Y-m-d H:i:s')));
        }
        return $this->_Atom;
    }
    
}