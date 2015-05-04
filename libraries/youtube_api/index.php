<?php
    session_start();

    require 'api/google_oauth.php';

    $key = ''; //TODO: This should be the key given to you by google
    $secret = ''; //TODO: This should be the secret given to you by google
	$youtube_api_key = ''; //TODO: This should be the api key give to you by youtube

    $auth = new google_oauth($key, $secret);

    //TODO: Be sure to alter the callback url below to reflect your environment
    $data = $auth->get_request_token('http://jaws-project.com/index.php?action=IncludeLibrary&path=%2Flibraries%2Fyoutube_api%2Fauthorize.php');
    // Store the returned token_secret in memory some where so
    // that it can be recalled during the authorization step.
    $_SESSION['token_secret'] = $data['token_secret'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>YouTube API Test</title>
</head>
<body>
<?php
	$file = @file_get_contents(JAWS_DATA . '/logs/youtube.log');
	if (file_exists(JAWS_DATA . '/logs/youtube.log') && !empty($file)) {
		require 'api/youtube.php';
		$youtube = new youtube($youtube_api_key, $key, $secret, unserialize($file));
?>
    <h1>Already Authorized with YouTube</h1>
	<pre><?php var_dump(json_decode($youtube->getUserUploads('default', array('alt'=>'json'))));?></pre>
<?php
		$upload_token = $youtube->getFormUploadToken(array());
		// XML Parser
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
		$xml_parser = new XMLParser;
		$xml_result = $xml_parser->parse($upload_token, array("RESPONSE"));
		for ($i=0;$i<$xml_result[1]; $i++) {
			if (isset($xml_result[0][$i]['URL']) && isset($xml_result[0][$i]['TOKEN'])) {
?>
	<script type="text/javascript">
	  function checkForFile() {
		if (document.getElementById('file').value) {
		  return true;
		}
		document.getElementById('errMsg').style.display = '';
		return false;
	  }
	</script>

	<form action="<?php echo $xml_result[0][$i]['URL'] ?>?nexturl=http%3A%2F%2Fjaws-project.com%2Findex.php%3Faction%3DIncludeLibrary%26path%3D%252Flibraries%252Fyoutube_api%252Findex.php" method="post"
	  enctype="multipart/form-data" onsubmit="return checkForFile();">
	  <input id="file" type="file" name="file"/>
	  <div id="errMsg" style="display:none;color:red">
		You need to specify a file.
	  </div>
	  <input type="hidden" name="token" value="<?php echo $xml_result[0][$i]['TOKEN'] ?>"/>
	  <input type="submit" value="go" />
	</form>
<?php
				break;
			}
		}
	} else {
?>
    <h1>Authorize with YouTube</h1>
<?php
	}
?>
    <br/>
    <a href="<?php echo $data['redirect']; ?>">
        Click This Link to Authorize
    </a>
</body>
</html>
