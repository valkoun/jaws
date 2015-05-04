<?php
/**
 * Mail sending support. A wrapper between Jaws and pear/Mail
 *
 * @category   Mail
 * @category   developer_feature
 * @package    Core
 * @author     David Coallier <davidc@agoraproduction.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Mail
{
    // {{{ Variables
    /**
     * The mailer type
     * @param string $mailer The mailer type
     */
    var $mailer = '';

    // {{{ Variables
    /**
     * Send email via this email
     * @param string $site_email The default site email address
     */
    var $site_email = '';

    // {{{ Variables
    /**
     * From name
     * @param string $email_name The default site email name
     */
    var $email_name = '';

    /**
     * SMTP email verification?
     * @param boolean $smtp_vrfy SMTP email verification?
     */
    var $smtp_vrfy = false;

    // {{{ Variables
    /**
     * The server infos (host,login,pass)
     * @param array $server The server infos
     */
    var $params = array();

    /**
     * The email recipients.
     * @param array $recipients The recipients.
     */
    var $recipient = array();

    /**
     * The email headers
     *
     * @param array string $headers The headers of the mail.
     */
    var $headers = array();

    /**
     * The crlf character(s)
     *
     * @param string $crlf
     */
    var $crlf = "\n";

    /**
     * A object of Mail_Mime
     *
     * @param object $mail_mime
     */
    var $mail_mime;

    /**
     * This creates the mail object that will
     * add recipient, send emails to destinated
     * email addresses calling functions.
     *
     * @access constructor
     */
    function Jaws_Mail($init = true)
    {
        require_once 'Mail.php';
        require_once 'Mail/mime.php';
        require_once JAWS_PATH . 'include/Jaws/UTF8.php';
        $this->mail_mime = new Mail_Mime($this->crlf);

        if ($init) {
            $this->Init();
        }
    }

    /**
     * This function loads the mail settings from
     * the registry.
     *
     * @access public
     */
    function Init()
    {
        if (!isset($GLOBALS['app'])) {
            return new Jaws_Error('$GLOBALS[\'app\'] not available', 'MAIL', JAWS_ERROR_ERROR);
        }

        // Get the Mail settings data from Registry
        $this->mailer     = $GLOBALS['app']->Registry->Get('/network/mailer');
        $this->site_email = $GLOBALS['app']->Registry->Get('/network/site_email');
        $this->email_name = $GLOBALS['app']->Registry->Get('/network/email_name');
        $this->smtp_vrfy  = $GLOBALS['app']->Registry->Get('/network/smtp_vrfy') == 'true';

        $params = array();
        $params['sendmail_path'] = $GLOBALS['app']->Registry->Get('/network/sendmail_path');
        $params['sendmail_args'] = $GLOBALS['app']->Registry->Get('/network/sendmail_args');
        $params['host']          = $GLOBALS['app']->Registry->Get('/network/smtp_host');
        $params['port']          = $GLOBALS['app']->Registry->Get('/network/smtp_port');
        $params['auth']          = $GLOBALS['app']->Registry->Get('/network/smtp_auth')  == 'true';
        $params['pipelining']    = $GLOBALS['app']->Registry->Get('/network/pipelining') == 'true';
        $params['username']      = $GLOBALS['app']->Registry->Get('/network/smtp_user');
        $params['password']      = $GLOBALS['app']->Registry->Get('/network/smtp_pass');

        $this->params = $params;
        return $this->params;
    }

    /**
     * This adds a recipient to the mail to send.
     *
     * @param string $recipient  The recipient to add.
     * @param string $valid      Do we validate the email ?
     * @param bool $checkdns   Do we check the MX record ?
     * @access public
     * @return string recipients
     */
    function AddRecipient($recipient, $valid = true, $checkdns = false)
    {
        if (trim($recipient) !== '') {
            if ($valid) {
                require_once 'Validate.php';
                if (!Validate::email($recipient, $checkdns)) {
                    return false;
                }
            }

            $this->recipient[] = $recipient;
            return true;
        }

        return false;
    }

    /**
     * This function sets the headers of the email to send.
     *
     * @param string $to       Send to.
     * @param string $from     Who the email is from.
     * @param string $subject  Subject of the email.
     * @access protected
     * @return array string headers
     */
    function SetHeaders($to = '', $from_name = '', $from_email = '', $subject = '')
    {
        if ($this->smtp_vrfy) {
            $subject    = $from_name . ' <' . $from_email . '> : ' . $subject;
            $from_name  = $this->email_name;
            $from_email = $this->site_email;
        } else {
            $from_name  = empty($from_name)? $this->email_name : $from_name;
            $from_email = empty($from_email)? $this->site_email : $from_email;
        }

        $params = array();
        $params['To'] = empty($to)? $this->site_email : $to;
        $params['Subject'] = $subject;
        if ($this->mailer == 'phpmail') {
            $params['From'] = $from_email;
        } else {
            $params['From'] = Jaws_UTF8::encode_mimeheader($from_name) . ' <'.$from_email.'>';
        }

        return $this->headers = $params;
    }

    /**
     * This function returns the set headers.
     *
     * @access public
     * @return $this->headers
     */
    function GetHeaders()
    {
        return $this->headers;
    }

    /**
     * This function sets the body, the structure
     * of the email, what's in it..
     *
     * @param string $body   The body of the email
     * @param string $format The format to use.
     * @access protected
     * @return string $body
     */
    function SetBody($body, $format = 'html')
    {
        if (!isset($body) && empty($body)) {
            return false;
        }

        switch ($format) {
            case 'file':
                $res = $this->mail_mime->addAttachment($body);
                break;
            case 'image':
                $res = $this->mail_mime->addHTMLImage($body);
                break;
            case 'html':
                $res = $this->mail_mime->setHTMLBody($body);
                break;
            case 'text':
                $res = $this->mail_mime->setTXTBody($body);
                break;
            default:
                $res = false;
        }

        return $res;
    }

    /**
     * This function sends the email
     *
     * @param array string recipients The recipients
     * @param array string headers    The email headers
     * @param       string from       The email sender
     * @param       string body       The email body
     * @access public
     */
    function send()
    {
        $mail = null;
        switch ($this->mailer) {
            case 'phpmail':
                $mail =& Mail::factory('mail');
                break;
            case 'sendmail':
                $mail =& Mail::factory('sendmail', $this->params);
                break;
            case 'smtp':
                $mail =& Mail::factory('smtp', $this->params);
                break;
            default:
                return false;
        }

        $realbody = $this->mail_mime->get(array('html_encoding' => '8bit',
                                     'text_encoding' => '8bit',
                                     'head_encoding' => 'base64',
                                     'html_charset'  => 'utf-8',
                                     'text_charset'  => 'utf-8',
                                     'head_charset'  => 'utf-8',
                                    ));

        $headers  = $this->mail_mime->headers($this->headers);
        if (empty($this->recipient)) {
            $this->recipient[] = $this->site_email;
        }

		if (!isset($GLOBALS['log'])) {
			require_once JAWS_PATH . 'include/Jaws/Log.php';
			$GLOBALS['log'] = new Jaws_Log();
		}
        
		$log_opts = array();
        $log_opts['file'] = JAWS_DATA ."logs/mail.log";
        $log_opts['maxlines'] = 20000;
        $log_opts['rotatelimit'] = 1;
        
		$e = $mail->send($this->recipient, $headers, $realbody);
        if (PEAR::isError($e)) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, '[mail_failure]: '. $e->getMessage() .': '. var_export(array('headers' => $headers, 'recipient' => $this->recipient, 'body' => $realbody), true), $log_opts);
            return new Jaws_Error($e->getMessage());
        } else {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, '[mail_sent]: '. var_export(array('headers' => $headers, 'recipient' => $this->recipient, 'body' => $realbody), true), $log_opts);
		}
        


        return true;
    }

    /**
     * Resets the values and updates
     *
     * @access  public
     */
    function ResetValues()
    {
        $this->headers   = array();
        $this->recipient = array();
        unset($this->mail_mime);
        $this->mail_mime = new Mail_Mime($this->crlf);
    }

}