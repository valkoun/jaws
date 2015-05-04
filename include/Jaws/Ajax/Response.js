/**
 * Show the response
 */
function showResponse(message)
{
    if ($('msgbox-wrapper')) {
		$('msgbox-wrapper').innerHTML = '';
		for(var i = 0; i < message.length; i++) {
			var messageDiv = document.createElement('div');
			$('msgbox-wrapper').appendChild(messageDiv);
			messageDiv.innerHTML = message[i]['message'];
			messageDiv.className = message[i]['css'];
			messageDiv.id = 'msgbox_'+i;
			new Effect.Appear(messageDiv);
			hideResponseBox(messageDiv);
		}
	}
}

function getScrollXY() {
  var scrOfX = 0, scrOfY = 0;
  if( typeof( window.pageYOffset ) == 'number' ) {
    //Netscape compliant
    scrOfY = window.pageYOffset;
    scrOfX = window.pageXOffset;
  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
    //DOM compliant
    scrOfY = document.body.scrollTop;
    scrOfX = document.body.scrollLeft;
  } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
    //IE6 standards compliant mode
    scrOfY = document.documentElement.scrollTop;
    scrOfX = document.documentElement.scrollLeft;
  }
  return scrOfY;
}

/**
 * Hide response boxes - Fast Code
 */
function hideResponseBox(name, timehide)
{
    if (typeof(timehide) == 'undefined') {
        timehide = '3000';
    }

    setTimeout('hideResponseBoxCallback("' + name.id + '")', timehide);
}

/**
 * Hide response boxes - JS Action (callback)
 */
function hideResponseBoxCallback(name)
{
    new Effect.Fade(name);
}

/**
 * Show working notification.
 */
function showWorkingNotification(msg)
{
	if ($('working_notification')) {
		if (typeof(loading_message) == 'undefined' || !loading_message) {
			loading_message = 'Loading...';
		}
		
		if (typeof(msg) == 'object' || !msg) {
			msg = loading_message;
		}
		$('working_notification').innerHTML = msg;
		$('working_notification').style.visibility = 'visible';
		$('working_notification').style.zIndex = 2147483647;
    }
	//loading_message = default_loading_message;
}

/**
 * Hide working notification
 */
function hideWorkingNotification()
{
	if ($('working_notification')) {
		$('working_notification').style.visibility = 'hidden';
	}
}