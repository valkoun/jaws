<?php
/**
 * Custom hooks
 *
 * @category   GadgetHook
 * @package    Core
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2015 Alan Valkoun
 */
class CustomHook
{	
	/*
	 * Invisible Hand API call example
	*/
	function InvisibleHandAPI()
	{
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
		require_once 'HTTP/Request.php';
		$request =& Jaws_Request::getInstance();
		$get_search_type = $request->get('t', 'get');
		$search_type = $get_search_type;
		if (is_null($search_type) || empty($search_type)) {
			$search_type = 'compare';
		}
		
		$get_sort = $request->get('s', 'get');
		$sort = $get_sort;
		if (is_null($sort) || empty($sort)) {
			$sort = 'popularity';
		}
		
		$get_query = $request->get('q', 'get');
		$query = $get_query;
		
		$get_limit = $request->get('l', 'get');
		$max_limit = $get_limit;
		
		if (is_null($max_limit) || empty($max_limit)) {
			$max_limit = '40';
		}
		$max_limit = (int)$max_limit;
		
		$ih_api_id = 'INVISIBLEHAND_API_ID';
		$ih_api_key = 'INVISIBLEHAND_API_KEY';
						
		$ih_urls = array();
		$ih_urls[] = "http://us.api.invisiblehand.co.uk/v1/products?query=%22comforter+set%22&sort=best_price&order=asc&retailer=Overstock.com&app_id=".$ih_api_id."&app_key=".$ih_api_key;
		$ih_urls[] = "http://us.api.invisiblehand.co.uk/v1/products?query=%22quilt+set%22&sort=best_price&order=asc&retailer=Overstock.com&app_id=".$ih_api_id."&app_key=".$ih_api_key;
		$ih_urls[] = "http://us.api.invisiblehand.co.uk/v1/products?query=%22mattress+topper%22+%22memory+foam%22&sort=best_price&order=asc&retailer=Overstock.com&app_id=".$ih_api_id."&app_key=".$ih_api_key;
		$ih_urls[] = "http://us.api.invisiblehand.co.uk/v1/products?query=%22bed+in+a+bag%22&sort=best_price&order=asc&retailer=Overstock.com&app_id=".$ih_api_id."&app_key=".$ih_api_key;
		
		foreach ($ih_urls as $url) {
			$httpRequest = new HTTP_Request($url);
			$httpRequest->setMethod(HTTP_REQUEST_METHOD_GET);
			$resRequest = $httpRequest->sendRequest();
			if (PEAR::isError($resRequest) || (int) $httpRequest->getResponseCode() <> 200) {
				return new Jaws_Error('ERROR REQUESTING URL: '.$url.' ('.$httpRequest->getResponseCode().')', _t('USERS_NAME'));
			}
			$data = $httpRequest->getResponseBody();
			$result_array = $GLOBALS['app']->UTF8->json_decode($data);
			var_dump($result_array);
		}
		
		return true;
	}
}
