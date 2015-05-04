<?php
/**
* @package JPSpan
* @subpackage Server
* @version $Id: PostOffice.php,v 1.14 2005/04/28 09:58:58 harryf Exp $
*/
//--------------------------------------------------------------------------------
/**
* Define
*/
if ( !defined('JPSPAN') ) {
    define ('JPSPAN',dirname(__FILE__).'/../');
}
/**
* Include
*/
require_once JPSPAN . 'Server.php';
//--------------------------------------------------------------------------------

/**
* Class and method name passed in the URL with params passed
* as url-encoded POST data. Urls like
* http://localhost/server.php/Class/Method
* @package JPSpan
* @subpackage Server
* @public
*/
class JPSpan_Server_PostOffice extends JPSpan_Server {

    /**
    * Name of user defined handler that was called
    * @param string
    * @access private
    */
    var $calledClass = NULL;
    
    /**
    * Name of method in handler
    * @param string
    * @access private
    */
    var $calledMethod = NULL;
    
    /**
    * Request encoding to use (e.g. xml, php or json)
    * @var string
    * @access public
    */
    var $RequestEncoding = 'xml';

    /**
    * @access public
    */
    function JPSpan_Server_PostOffice() {
        parent::JPSpan_Server();
    }
    
    /**
    * Serve a request
    * @param boolean send headers
    * @return boolean FALSE if failed (invalid request - see errors)
    * @access public
    */
    function serve($sendHeaders = TRUE) {
        require_once JPSPAN . 'Monitor.php';
        $M = & JPSpan_Monitor::instance();
        $this->calledClass = NULL;
        $this->calledMethod = NULL;
        
        if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
            trigger_error('Invalid HTTP request method: '.$_SERVER['REQUEST_METHOD'],E_USER_ERROR);
            return FALSE;
        }
        if ( $this->resolveCall() ) {
            $M->setRequestInfo('class',$this->calledClass);
            $M->setRequestInfo('method',$this->calledMethod);
            
            if ( FALSE !== ($Handler = & $this->getHandler($this->calledClass) ) ) {
                $args = array();
                $M->setRequestInfo('args',$args);

                if ( $this->getArgs($args) ) {
                    $M->setRequestInfo('args',$args);
                    $response = call_user_func_array(
                        array(
                            & $Handler,
                            $this->calledMethod
                        ),
                        $args
                    );
                    
                } else {
                    $response = call_user_func(
                        array(
                            & $Handler,
                            $this->calledMethod
                        )
                    );
                    
                }

                require_once JPSPAN . 'Serializer.php';

                $M->setResponseInfo('payload',$response);
                $M->announceSuccess();

                $response = JPSpan_Serializer::serialize($response, $this->RequestEncoding);

				if ( $sendHeaders ) {
                    header('Content-Length: '.strlen($response));
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
                    header('Last-Modified: ' . gmdate( "D, d M Y H:i:s" ) . 'GMT'); 
                    header('Cache-Control: no-cache, must-revalidate'); 
                    header('Pragma: no-cache');
                }

                echo $response;
                return TRUE;
                
            } else {
            
                trigger_error('Invalid handle for: '.$this->calledClass,E_USER_ERROR);
                return FALSE;
                
            }
            
        }
        return FALSE;
    }

    /**
    * Resolve the call - identify the handler class and method and store
    * locally
    * @return boolean FALSE if failed (invalid request - see errors)
    * @access private
    */
    function resolveCall() {
        // Hack between server.php?class/method and server.php/class/method
        $uriPath = $_SERVER['QUERY_STRING'];

        if ( $uriPath ) {
            if ( preg_match('/\/$/',$uriPath) ) {
                $uriPath = substr($uriPath,0, strlen($uriPath)-1);
            }
        } else {
            $uriPath = JPSpan_Server::getUriPath();
        }

        $uriPath = explode('/',$uriPath);

        if ( !isset($_GET['object']) || !isset($_GET['method']) ) {
            trigger_error('Invalid call syntax',E_USER_ERROR);
            return FALSE;
        }

        if ( preg_match('/^[a-z]+[0-9a-z_]*$/', $_GET['object']) != 1 ) {
            trigger_error('Invalid handler name: '.$_GET['object'],E_USER_ERROR);
            return FALSE;
        }

        if ( preg_match('/^[a-z]+[0-9a-z_]*$/', $_GET['method']) != 1 ) {
            trigger_error('Invalid handler method: '.$_GET['method'],E_USER_ERROR);
            return FALSE;
        }

        if ( !array_key_exists($_GET['object'],$this->descriptions) ) {
            trigger_error('Unknown handler: '.$_GET['object'],E_USER_ERROR);
            return FALSE;
        }

        if ( !in_array($_GET['method'],$this->descriptions[$_GET['object']]->methods) ) {
            trigger_error('Unknown handler method: '.$_GET['method'],E_USER_ERROR);
            return FALSE;
        }

        $this->calledClass = $_GET['object'];
        $this->calledMethod = $_GET['method'];
        
        return TRUE;
        
    }

    /**
    * Populate the args array if there are any
    * @param array args (reference)
    * @return boolean TRUE if request had args
    * @access private
    */
    function getArgs(& $args) {
        require_once JPSPAN . 'RequestData.php';

        switch ($this->RequestEncoding) {
            case 'php':
                $args = JPSpan_RequestData_Post::fetch($this->RequestEncoding);
            break;
            case 'json':
                $args = JPSpan_RequestData_JSONPost::fetch($this->RequestEncoding);
            break;
            default:
                $args = JPSpan_RequestData_RawPost::fetch($this->RequestEncoding);
        }

        if ( is_array($args) ) {
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
    * Get the Javascript client generator
    * @return JPSpan_Generator
    * @access public
    */
    function & getGenerator() {
        require_once JPSPAN . 'Generator.php';
        $G = new JPSpan_Generator();
        $G->init(
            new JPSpan_PostOffice_Generator(),
            $this->descriptions,
            $this->serverUrl,
            $this->RequestEncoding
            );
        return $G;
    }
}

//--------------------------------------------------------------------------------
/**
* Generator for the JPSpan_Server_PostOffice
* @todo Much refactoring need to make code generation "pluggable"
* @see JPSpan_Server_PostOffice
* @package JPSpan
* @subpackage Server
* @access public
*/
class JPSpan_PostOffice_Generator {

    /**
    * @var array list of JPSpan_HandleDescription objects
    * @access public
    */
    var $descriptions;
    
    /**
    * @var string URL or server
    * @access public
    */
    var $serverUrl;
    
    /**
    * How requests should be encoded
    * @var string request encoding
    * @access public
    */
    var $RequestEncoding;
    
    /**
    * Invokes code generator
    * @param JPSpan_CodeWriter
    * @return void
    * @access public
    */
    function generate(& $Code) {

        $this->generateScriptHeader($Code);
        foreach ( array_keys($this->descriptions) as $key ) {
            $this->generateHandleClient($Code, $this->descriptions[$key]);
        }
    }
    
    /**
    * Generate the starting includes section of the script
    * @param JPSpan_CodeWriter
    * @return void
    * @access private
    */
    function generateScriptHeader(& $Code) {
        ob_start();
?>
/**@
* include 'remoteobject.js';
<?php
switch ($this->RequestEncoding) {
    case 'xml':
?>
* include 'request/rawpost.js';
* include 'encode/xml.js';
<?php
    break;
    case 'json':
?>
* include 'request/rawpost.js';
* include 'util/json.js';
* include 'encode/json.js';
<?php
    break;
    default:
?>
* include 'request/post.js';
* include 'encode/php.js';
<?php
}
?>
*/
<?php
        $Code->append(ob_get_contents());
        ob_end_clean();
    }
    
    /**
    * Generate code for a single description (a single PHP class)
    * @param JPSpan_CodeWriter
    * @param JPSpan_HandleDescription
    * @return void
    * @access private
    */
    function generateHandleClient(& $Code, & $Description) {
        $url = $this->serverUrl;
        $url .= strpos($url, '?') ? '&' : '?';
        $url .= 'object='.$Description->Class;

        ob_start();
?>

function <?php echo $Description->Class; ?>() {
    
    var oParent = new JPSpan_RemoteObject();
    
    if ( arguments[0] ) {
        oParent.Async(arguments[0]);
    }
    
    oParent.__serverurl = '<?php 
        echo $url; ?>';
    
    oParent.__remoteClass = '<?php echo $Description->Class; ?>';
    
<?php
switch ($this->RequestEncoding) {
    case 'xml':
?>
    oParent.__request = new JPSpan_Request_RawPost(new JPSpan_Encode_XML());
<?php
    break;
    case 'json':
?>
    oParent.__request = new JPSpan_Request_RawPost(new JPSpan_Encode_JSON());
<?php
    break;
    default:
?>
    oParent.__request = new JPSpan_Request_Post(new JPSpan_Encode_PHP());
<?php
}

foreach ( $Description->methods as $method ) {
?>
    
    // @access public
    oParent.<?php echo $method; ?> = function() {
        var url = this.__serverurl+'&method=<?php echo $method; ?>';
        return this.__call(url,arguments,'<?php echo $method; ?>');
    };
<?php
}
?>
    
    return oParent;
}

<?php
        $Code->append(ob_get_contents());
        ob_end_clean();
    }
}
