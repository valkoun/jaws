<?php
/**
 * MathCaptcha
 *
 * @category   Captcha
 * @package    Policy
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class MathCaptcha
{
    /**
     * Constructor
     *
     * @access  public
     */
    function MathCaptcha()
    {
        // If not installed try to install it
        $GLOBALS['app']->Registry->LoadFile('Policy');
        if ($GLOBALS['app']->Registry->Get('/gadgets/Policy/math_captcha') != 'installed') {
            $schema = JAWS_PATH . 'gadgets/Policy/captchas/MathCaptcha/schema.xml';
            if (!file_exists($schema)) {
                Jaws_Error::Fatal($schema . " doesn't exists", __FILE__, __LINE__);
            }
            $result = $GLOBALS['db']->installSchema($schema);
            if (Jaws_Error::IsError($result)) {
                Jaws_Error::Fatal("Can't install MathCaptcha schema", __FILE__, __LINE__);
            }
            $GLOBALS['app']->Registry->NewKey('/gadgets/Policy/math_captcha', 'installed');
            $GLOBALS['app']->Registry->Commit('Policy');
        }
    }

    /**
     * Returns an array with the captcha image field and a text entry so user can type
     *
     * @access  public
     * @return  array    Array indexed by captcha (the image entry) and entry (the input)
     */
    function Get()
    {
        $res = array();
        $key = $this->GetKey();
        $prefix = $this->GetPrefix();
        $img = $this->HexEncode($GLOBALS['app']->Map->GetURLFor('Policy', 'Captcha',
                                                                array('key' => $prefix . $key), false));

        $res['captcha'] =& Piwi::CreateWidget('Image', '', '');
        $res['captcha']->SetTitle(_t('GLOBAL_CAPTCHA_CODE'));
        $res['captcha']->SetID('captcha_img_'.rand());
        $res['captcha']->SetSrc($img);
        $res['entry'] =& Piwi::CreateWidget('Entry', $prefix . $key, '');
        $res['entry']->SetID('captcha_'.rand());
        $res['entry']->SetTitle(_t('GLOBAL_CAPTCHA_SENSITIVE'));
        return $res;
    }

    /**
     * Convert the string to an image so captcha can serve it
     *
     * @access  public
     * @param   string  $string Text to show
     * @return  string  String in HexCode
     */
    function HexEncode($string) 
    {
        $string = bin2hex($string);
        $res = '';
        for($i=0; $i<strlen($string); $i+=2) {
            $res .= '&#' . hexdec($string{$i} . $string{$i+1}) . ';';
        }       
        return $res;
    }

    /**
     * Check if a captcha key is valid
     *
     * @access  public
     * @param   boolean  Valid/Not Valid
     */
    function Check()
    {
        $request =& Jaws_Request::getInstance();
        $key   = '';
        $value = '';
        $prefix = $this->GetPrefix();
        foreach ($request->data['post'] as $k => $v) {
            if (substr($k, 0, strlen($prefix)) == $prefix) {
                $key = substr($k, 32);
                $value = $v; 
                break;
            }
        }

        $captcha_value = '$captcha_value = '. $this->GetValue($key).';';
        eval($captcha_value);
        $result = ($captcha_value !== false) && ($captcha_value === (int) $value);

        $this->RemoveKey($key);
        return $result;
    }

    /**
     * Remove a captcha key once it has been used (with success or failure)
     *
     * @access  public
     * @param   string  $key  Captcha key
     */
    function RemoveKey($key = null)
    {
        $params = array();
        // 10 minutes for cleantime
        $params['key'] = $key;
        $params['cleantime'] = date('Y-m-d H:i:s', time() - 600);
        $sql = "
            DELETE FROM [[captcha_math]]
            WHERE [createtime] <= {cleantime}";
        if (!is_null($key)) {
            $sql .= ' OR [key] = {key}';
        }

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            Jaws_Error::Fatal("Can't remove keys", __FILE__, __LINE__);
        }
    }

    /**
     * Returns the prefix (we use it to know where the captcha came from)
     *
     * @access  private
     * @return  string    Prefix to use
     */
    function GetPrefix()
    {
        return md5(implode(Jaws_Utils::GetRemoteAddress()) . $GLOBALS['app']->getSiteURL());
    }

    /**
     * Get the real value of a captcha by a given key
     *
     * @access  public
     * @param   string  $key    Captcha key
     * @return  string  Captcha value
     */
    function GetValue($key)
    {
        $params = array();
        $params['key'] = $key;
        $sql = "
            SELECT [value]
            FROM [[captcha_math]]
            WHERE [key] = {key}";
        $result = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($result) || empty($result)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_ERR, $result->getMessage());
            }
            $result = false;
        }

        return $result;
    }

    /**
     * Get the key of the current captcha (it creates the captcha and then returns its key)
     *
     * @access  public
     * @return  string  Captcha's key
     */
    function GetKey()
    {
        $key = uniqid(rand(0, 99999)) . time() . floor(microtime()*1000);

        $params = array();
        $params['key']   = $key;
        $params['value'] = $this->GenerateRandomValue();
        $params['createtime'] = $GLOBALS['db']->Date();

        $sql = "
            INSERT INTO [[captcha_math]]
                ([key], [value], [createtime])
            VALUES
                ({key}, {value}, {createtime})";

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $key = '';
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_ERR, $result->getMessage());
            }
        }

        return $key;
    }

    /**
     * Create the random string that user will see in the browser
     *
     * @access  private
     * @return  string    random string
     */
    function GenerateRandomValue()
    {
        return (string)rand(1, 9) . '+' . (string) rand(1, 9);
    }

    /**
     * Displays the captcha image
     *
     * @access  public
     */
    function Image($key = null)
    {
        if (is_null($key)) {
            $request =& Jaws_Request::getInstance();
            $key = $request->Get('key', 'get');
            $key = str_replace($this->GetPrefix(), '', $key);
        }

        $bg = dirname(__FILE__) . '/MathCaptcha/bg.png';
        $im = imagecreatefrompng($bg);
        $value = $this->GetValue($key);
        $value .= '=?';
        // Write it in a random position..
        $text_length = strlen($value);
        $darkgray = imagecolorallocate($im, 0x10, 0x70, 0x70);
        $x = 5; 
        $y = 20;
        for ($i = 0; $i < $text_length; $i++) {
            $fnt = rand(7,10);
            $y = rand(6, 10);
            imagestring($im, $fnt, $x, $y, $value{$i} , $darkgray);
            $x = $x + rand(15, 25);
        }
        header("Content-Type: image/png");
        imagepng($im);
        imagedestroy($im);
    }

}
