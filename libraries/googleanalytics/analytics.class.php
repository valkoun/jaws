<?php

/**
 * Google Analytics PHP API
 * 
 * This class can be used to retrieve data from the Google Analytics API with PHP
 * It fetches data as array for use in applications or scripts
 *  
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * Credits: http://www.alexc.me/
 * parsing the profile XML to a PHP array
 *   
 *
 * @link http://www.swis.nl
 * @copyright 2009 SWIS BV
 * @author Vincent Kleijnendorst - SWIS BV (vkleijnendorst [AT] swis [DOT] nl)
 * 
 * @version 0.1
 */

class analytics 
{
    
    var $_sUser;
    var $_sPass;
    var $_sAuth;
    var $_sProfileId;
    
    var $_sStartDate;
    var $_sEndDate;
    
    var $_bUseCache;
    var $_iCacheAge;
    
    /**
    * public constructor
    * 
    * @param string $sUser
    * @param string $sPass
    * @return analytics
    */
    function analytics($sUser, $sPass)
	{
        $this->_sUser = $sUser;
        $this->_sPass = $sPass;
        
        $this->_bUseCache = false;
        $this->auth();
    }
    
    
    /**
    * Google Authentification, returns session when set
    */
    function auth(){
        
        if (isset($_SESSION['auth'])){
            $this->_sAuth = $_SESSION['auth'];
            return;
        }
        
        $aPost = array ( 'accountType'   => 'GOOGLE', 
                         'Email'         => $this->_sUser,
                         'Passwd'        => $this->_sPass,
                         'service'       => 'analytics',
                         'source'        => 'Jaws');
                         
        $sResponse = $this->getUrl('https://www.google.com/accounts/ClientLogin', $aPost);
        $_SESSION['auth'] = '';
        if (strpos($sResponse, "\n") !== false){
            $aResponse = explode("\n", $sResponse);
            foreach ($aResponse as $sResponse){
                if (substr($sResponse, 0, 4) == 'Auth'){
                    $_SESSION['auth'] = trim(substr($sResponse, 5));
                }
            }
        }
        if ($_SESSION['auth'] == ''){
            unset($_SESSION['auth']);
            return 'Retrieving Auth hash failed!';
        }
        $this->_sAuth = $_SESSION['auth']; 
    }
    
    /**
    * Use caching (bool)
    * Whether or not to store GA data in a session for a given period
    * 
    * @param bool $bCaching (true/false)
    * @param int $iCacheAge seconds (default: 10 minutes)
    */
    function useCache($bCaching = true, $iCacheAge = 600){
        $this->_bUseCache = $bCaching;
        $this->_iCacheAge = $iCacheAge;
        if ($bCaching && !isset($_SESSION['cache'])){
            $_SESSION['cache'] = array();     
        }
    }
    
    
    /**
    * Get GA XML with auth key
    * 
    * @param string $sUrl
    * @return string XML
    */
    function getXml($sUrl){
        
        return $this->getUrl($sUrl, array(), array('Authorization: GoogleLogin auth=' . $this->_sAuth));
    }
    
    
    /**
    * Sets GA Profile ID  (Example: ga:12345)
    */        
    function setProfileById($sProfileId){
        
            $this->_sProfileId = $sProfileId; 
    }
    
    /**
    * Sets Profile ID by a given accountname
    * 
    */
    function setProfileByName($sAccountName){
        
        if (isset($_SESSION['profile'])){
            $this->_sProfileId = $_SESSION['profile'];
            return;
        }
        
        $this->_sProfileId = '';
        $sXml = $this->getXml('https://www.google.com/analytics/feeds/accounts/default');
        $aAccounts = $this->parseAccountList($sXml);
            
        foreach($aAccounts as $aAccount){
            if (isset($aAccount['accountName']) && $aAccount['accountName'] == $sAccountName){
                if (isset($aAccount['tableId'])){
                    $this->_sProfileId =  $aAccount['tableId'];
                }
            }    
        }
        if ($this->_sProfileId == ''){
            return 'No profile ID found!';
        }
        
        $_SESSION['profile'] = $this->_sProfileId;
    }
    
    /**
    * Returns an array with profileID => accountName
    * 
    */
    function getProfileList(){
        
        $sXml = $this->getXml('https://www.google.com/analytics/feeds/accounts/default');
        $aAccounts = $this->parseAccountList($sXml);
        $aReturn = array();
        foreach($aAccounts as $aAccount){ 
            $aReturn[$aAccount['tableId']] =  $aAccount['title'];
        }       
        return $aReturn;
    }
    
    /**
    * get resulsts from cache if set and not older then cacheAge
    * 
    * @param string $sKey
    * @return mixed cached data
    */
    function getCache($sKey){
        
        if ($this->_bUseCache === false){
            return false;
        }
        
        if (!isset($_SESSION['cache'][$this->_sProfileId])){
            $_SESSION['cache'][$this->_sProfileId] = array();
        }  
        if (isset($_SESSION['cache'][$this->_sProfileId][$sKey])){
            if (time() - $_SESSION['cache'][$this->_sProfileId][$sKey]['time'] < $this->_iCacheAge){
                return $_SESSION['cache'][$this->_sProfileId][$sKey]['data'];
            } 
        }
        return false;
    }
    
    /**
    * Cache data in session
    * 
    * @param string $sKey
    * @param mixed $mData Te cachen data
    */
    function setCache($sKey, $mData){
        
        if ($this->_bUseCache === false){
            return false;
        }
        
        if (!isset($_SESSION['cache'][$this->_sProfileId])){
            $_SESSION['cache'][$this->_sProfileId] = array();
        }  
        $_SESSION['cache'][$this->_sProfileId][$sKey] = array(  'time'  => time(),
                                                                'data'  => $mData);
    }
    
    /**
    * Parses GA XML to an array (dimension => metric)
    * Check http://code.google.com/intl/nl/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html 
    * for usage of dimensions and metrics
    * 
    * @param array  $aProperties  (GA properties: metrics & dimensions)
    * 
    * @return array result
    */
    function getData($aProperties = array()){
        $aParams = array();
        foreach($aProperties as $sKey => $sProperty){
            $aParams[] = $sKey . '=' . $sProperty;
        }
        
        $sUrl = 'https://www.google.com/analytics/feeds/data?ids=' . $this->_sProfileId . 
                                                        '&start-date=' . $this->_sStartDate . 
                                                        '&end-date=' . $this->_sEndDate . '&' . 
                                                        implode('&', $aParams);
        $aCache = $this->getCache($sUrl);
        if ($aCache !== false){
            return $aCache;
        }
        
        $sXml = $this->getXml($sUrl);
        
        $aResult = array();
        
		$oDoc = new DOMDocument();
        $oDoc->loadXML($sXml);
        $oEntries = $oDoc->getElementsByTagName('entry');
        foreach($oEntries as $oEntry){
            $oTitle = $oEntry->getElementsByTagName('title');
			foreach ($oTitle as $title) {
				$sTitle = $title->nodeValue;
				break;
			}
            $oMetric = $oEntry->getElementsByTagName('metric'); 
            
            // Fix the array key when multiple dimensions are given
			if (strpos($sTitle, ' | ') !== false && strpos($aProperties['dimensions'], ',') !== false){
                
                $aDimensions = explode(',', $aProperties['dimensions']);
                $aDimensions[] = '|';
                $aDimensions[] = '=';
                $sTitle = preg_replace('/\s\s+/', ' ', trim(str_replace($aDimensions, '', $sTitle)));  
                
            }
            $sTitle = str_replace($aProperties['dimensions'] . '=', '', $sTitle);
            
			foreach ($oMetric as $metric) {
				$aResult[$sTitle] = $metric->getAttribute('value');
				break;
			}
        }
        // cache the results (if caching is true)
        $this->setCache($sUrl, $aResult);
        
        return $aResult;
    }
    
    /**
    * Parse XML from account list
    * 
    * @param string $sXml
    */
    function parseAccountList($sXml){
        
        $oDoc = new DOMDocument();
        $oDoc->loadXML($sXml);
        $oEntries = $oDoc->getElementsByTagName('entry');
        $i = 0;
        $aProfiles = array();
        foreach($oEntries as $oEntry){
            
            $aProfiles[$i] = array();

            $oTitle = $oEntry->getElementsByTagName('title');
			foreach ($oTitle as $title) {
				$aProfiles[$i]["title"] = $title->nodeValue;
				break;
			}

            $oEntryId = $oEntry->getElementsByTagName('id');
			foreach ($oEntryId as $entry) {
				$aProfiles[$i]["entryid"] = $entry->nodeValue;
				break;
			}

            $oProperties = $oEntry->getElementsByTagName('property');
            foreach($oProperties as $oProperty){
                if (strcmp($oProperty->getAttribute('name'), 'ga:accountId') == 0){
                    $aProfiles[$i]["accountId"] = $oProperty->getAttribute('value');
                }    
                if (strcmp($oProperty->getAttribute('name'), 'ga:accountName') == 0){
                    $aProfiles[$i]["accountName"] = $oProperty->getAttribute('value');
                }
                if (strcmp($oProperty->getAttribute('name'), 'ga:profileId') == 0){
                    $aProfiles[$i]["profileId"] = $oProperty->getAttribute('value');
                }
                if (strcmp($oProperty->getAttribute('name'), 'ga:webPropertyId') == 0){
                    $aProfiles[$i]["webPropertyId"] = $oProperty->getAttribute('value');
                }
            }

            $oTableId = $oEntry->getElementsByTagName('tableId');
			foreach ($oTableId as $table) {
				$aProfiles[$i]["tableId"] = $table->nodeValue;
				break;
			}

            $i++;
        }
        return $aProfiles;
    }
    
    /**
    * Get data from given URL
    * Uses Curl if installed, falls back to file_get_contents if not
    * 
    * @param string $sUrl
    * @param array $aPost
    * @param array $aHeader
    * @return string Response
    */
    function getUrl($sUrl, $aPost = array(), $aHeader = array()){
        
        
        if (count($aPost) > 0){
            // build POST query
            $sMethod = 'POST'; 
            $sPost = http_build_query($aPost);    
            $aHeader[] = 'Content-type: application/x-www-form-urlencoded';
            $aHeader[] = 'Content-Length: ' . strlen($sPost);
            $sContent = $aPost;
        } else {
            $sMethod = 'GET';
            $sContent = null;
        }
        
        if (function_exists('curl_init')){

            // If Curl is installed, use it!
            $rRequest = curl_init();
            curl_setopt($rRequest, CURLOPT_URL, $sUrl);
            curl_setopt($rRequest, CURLOPT_RETURNTRANSFER, 1);
            
            if ($sMethod == 'POST'){
                curl_setopt($rRequest, CURLOPT_POST, 1); 
                curl_setopt($rRequest, CURLOPT_POSTFIELDS, $aPost); 
            } else {
                curl_setopt($rRequest, CURLOPT_HTTPHEADER, $aHeader);
            }
            
            $sOutput = curl_exec($rRequest);
            if ($sOutput === false){
                return 'Curl error (' . curl_error($rRequest) . ')';    
            }
            
            $aInfo = curl_getinfo($rRequest);
            
            if ($aInfo['http_code'] != 200){
                // not a valid response from GA
                if ($aInfo['http_code'] == 400){
                    return 'Bad request (' . $aInfo['http_code'] . ') url: ' . $sUrl;     
                }
                if ($aInfo['http_code'] == 403){
                    return 'Access denied (' . $aInfo['http_code'] . ') url: ' . $sUrl;     
                }
                return 'Not a valid response (' . $aInfo['http_code'] . ') url: ' . $sUrl;
            }
            
            curl_close($rRequest);
                        
        } else {
            // Curl is not installed, use file_get_contents
            
            // create headers and post
            $aContext = array('http' => array ( 'method' => $sMethod,
                                                'header'=> implode("\r\n", $aHeader) . "\r\n",
                                                'content' => $sContent));
            $rContext = stream_context_create($aContext);

            $sOutput = @file_get_contents($sUrl, 0, $rContext);
            if (strpos($http_response_header[0], '200') === false){
                // not a valid response from GA   
                return 'Not a valid response (' . $http_response_header[0] . ') url: ' . $sUrl;       
            }
        }
        return $sOutput;
    }   
    
    /**
    * Sets the date range for GA data
    * 
    * @param string $sStartDate (YYY-MM-DD)
    * @param string $sEndDate   (YYY-MM-DD)
    */
    function setDateRange($sStartDate, $sEndDate){
        
        $this->_sStartDate = $sStartDate; 
        $this->_sEndDate   = $sEndDate;
        
    }
    
    /**
    * Sets de data range to a given month
    * 
    * @param int $iMonth
    * @param int $iYear
    */
    function setMonth($iMonth, $iYear){  
        
        $this->_sStartDate = date('Y-m-d', strtotime($iYear . '-' . $iMonth . '-01')); 
        $this->_sEndDate   = date('Y-m-d', strtotime($iYear . '-' . $iMonth . '-' . date('t', strtotime($iYear . '-' . $iMonth . '-01'))));
    }
    
    /**
    * Get visitors for given period
    * 
    */
    function getVisitors(){
                                
        return $this->getData(array( 'dimensions' => 'ga:date',
                                     'metrics'    => 'ga:visits',
                                     'sort'       => 'ga:date'));
    }
    
    /**
    * Get pageviews for given period
    * 
    */    
    function getPageviews(){
                                
        return $this->getData(array( 'dimensions' => 'ga:date',
                                     'metrics'    => 'ga:pageviews',
                                     'sort'       => 'ga:date'));
    }
    
    /**
    * Get pageviews for given period
    * 
    */    
    function getPageviewsByPage(){
                                
        return $this->getData(array( 'dimensions' => 'ga:hostname,ga:pagePath',
                                     'metrics'    => 'ga:pageviews',
                                     'max-results' => 20,
                                     'sort'       => '-ga:pageviews'));
    }
    
    /**
    * Get visitors per hour for given period
    * 
    */    
    function getVisitsPerHour(){
        
        return $this->getData(array( 'dimensions' => 'ga:hour',
                                     'metrics'    => 'ga:visits',
                                     'sort'       => 'ga:hour'));
    }
    
    /**
    * Get Browsers for given period
    * 
    */    
    function getBrowsers(){
        
        $aData = $this->getData(array(  'dimensions' => 'ga:browser,ga:browserVersion',
                                        'metrics'    => 'ga:visits',
										'max-results' => 20,
                                        'sort'       => '-ga:visits'));             
        arsort($aData);
        return $aData;                                                                                                                                                                           
    }
    
    /**
    * Get Operating System for given period
    * 
    */    
    function getOperatingSystem(){
        
        $aData = $this->getData(array(   'dimensions' => 'ga:operatingSystem',
                                         'metrics'    => 'ga:visits',
										 'max-results' => 20,
                                         'sort'       => '-ga:visits'));
        // sort descending by number of visits
        arsort($aData);
        return $aData; 
    }

    /**
    * Get screen resolution for given period
    * 
    */    
    function getScreenResolution(){
        
        $aData = $this->getData(array(   'dimensions' => 'ga:screenResolution',
                                         'metrics'    => 'ga:visits',
										 'max-results' => 20,
                                         'sort'       => '-ga:visits'));
        
        // sort descending by number of visits 
        arsort($aData);
        return $aData; 
    }
    
    /**
    * Get referrers for given period
    * 
    */    
    function getReferrers(){
        
        $aData = $this->getData(array(   'dimensions' => 'ga:source',
                                         'metrics'    => 'ga:visits',
										 'max-results' => 20,
                                         'sort'       => '-ga:visits'));
    
        // sort descending by number of visits 
        arsort($aData);
        return $aData; 
    }
    
    /**
    * Get search words for given period
    * 
    */    
    function getSearchWords(){
        $aData = $this->getData(array(   'dimensions' => 'ga:keyword',
                                         'metrics'    => 'ga:visits',
										 'max-results' => 20,
                                         'sort'       => '-ga:visits'));
        // sort descending by number of visits                                                                                                                                                     
        arsort($aData);
        return $aData; 
    }
}