<?php
    session_start();

    require 'api/google_oauth.php';
    require 'api/youtube.php';

    $key = ''; //TODO: This should be the key given to you by google
    $secret = ''; //TODO: This should be the secret given to you by google

    $auth = new google_oauth($key, $secret);
    $oauth_token = $auth->get_access_token($_GET['oauth_token'], $_SESSION['token_secret'], $_GET['oauth_verifier']);
	echo '<pre>';
	//Make sure you urlencode the values of $oauth_token before passing it to the youtube api
	foreach($oauth_token as $k => $v) {
        $oauth_token[$k] = urlencode($v);
	}
	
	if (Jaws_Utils::is_writable(JAWS_DATA) && Jaws_Utils::is_writable(JAWS_DATA . '/logs')) {
		$create_file = file_put_contents(JAWS_DATA . '/logs/youtube.log', serialize($oauth_token), FILE_APPEND);
	}
	var_dump($create_file);
    $youtube_api_key = ''; //TODO: This should be the api key give to you by youtube

    $youtube = new youtube($youtube_api_key, $key, $secret, $oauth_token);

    var_dump(json_decode($youtube->getUserProfile('default', array('alt'=>'json'))));    
	echo '</pre>';
