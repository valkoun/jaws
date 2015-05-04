<?php
/**
 * HKCaptcha
 *
 * @category   Captcha
 * @package    Policy
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 * @based on   http://www.lagom.nl/linux/hkcaptcha/
 */
class HKCaptcha
{
    /**
     * Constructor
     *
     * @access  public
     */
    function HKCaptcha()
    {
        // If not installed try to install it
        $GLOBALS['app']->Registry->LoadFile('Policy');
        if ($GLOBALS['app']->Registry->Get('/gadgets/Policy/hkcaptcha') != 'installed') {
            $schema = JAWS_PATH . 'gadgets/Policy/captchas/HKCaptcha/schema.xml';
            if (!file_exists($schema)) {
                Jaws_Error::Fatal($schema . " doesn't exists", __FILE__, __LINE__);
            }
            $result = $GLOBALS['db']->installSchema($schema);
            if (Jaws_Error::IsError($result)) {
                Jaws_Error::Fatal("Can't install HKCaptcha schema", __FILE__, __LINE__);
            }
            $GLOBALS['app']->Registry->NewKey('/gadgets/Policy/hkcaptcha', 'installed');
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

        $captcha_value = $this->GetValue($key);
        $result = ($captcha_value !== false) && (strtolower($captcha_value) === strtolower($value));

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
            DELETE FROM [[captcha_hk]]
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
            FROM [[captcha_hk]]
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
            INSERT INTO [[captcha_hk]]
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
    function GenerateRandomValue($lenght = 5, $use_lower_case = true, $use_upper_case = true, $use_numbers = false)
    {
        $string = "";
        $lower_case = "abcdefghijklmnopqrstuvwxyz";
        $upper_case = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
        $numbers = "01234567890";
        $possible = "";
        if ($use_lower_case) {
            $possible .= $lower_case;
        }
        if ($use_upper_case) {
            $possible .= $upper_case;
        }
        if ($use_numbers) {
            $possible .= $numbers;
        }

        for ($i = 1; $i <= $lenght; $i++) {
            $string .= substr($possible, rand(0, strlen($possible)-1), 1);
        }
        return $string;
    }

    /**
     * Get a distorted image
     *
     * @access  private
     * @return  void
     */
    function warped_image($tmpimg, $img)
    {
        $numpoles = 3;
        $height = imagesy($img);
        $width  = imagesx($img);

        // make an array of poles AKA attractor points
        for ($i = 0; $i < $numpoles; ++$i) {
            do {
                $px[$i] = rand(0, $width);
            } while ($px[$i] >= $width*0.3 && $px[$i] <= $width*0.7);

            do {
                $py[$i] = rand(0, $height);
            } while ($py[$i] >= $height*0.3 && $py[$i] <= $height*0.7);

            $rad[$i] = rand($width*0.4, $width*0.8);
            $amp[$i] = -0.0001 * rand(0,9999) * 0.15 - 0.15;
        }

        // get img properties bgcolor
        $bgcol = imagecolorat($tmpimg, 1, 1);
        $iscale  = imagesy($tmpimg) / imagesy($img);

        // loop over $img pixels, take pixels from $tmpimg with distortion field
        for ($ix = 0; $ix < $width; ++$ix) {
            for ($iy = 0; $iy < $height; ++$iy) {
                $x = $ix;
                $y = $iy;
                for ($i = 0; $i < $numpoles; ++$i) {
                    $dx = $ix - $px[$i];
                    $dy = $iy - $py[$i];
                    if ($dx == 0 && $dy == 0) {
                        continue;
                    }

                    $r = sqrt($dx*$dx + $dy*$dy);
                    if ($r > $rad[$i]) {
                      continue;
                    }

                    $rscale = $amp[$i] * sin(3.14*$r/$rad[$i]);
                    $x += $dx*$rscale;
                    $y += $dy*$rscale;
                }

                $c = $bgcol;
                $x *= $iscale;
                $y *= $iscale;
                if ($x >= 0 && $x < imagesx($tmpimg) && $y >= 0 && $y < imagesy($tmpimg)) {
                    $c = imagecolorat($tmpimg, $x, $y);
                }

                imagesetpixel($img, $ix, $iy, $c);
            }
        }
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
        $value = $this->GetValue($key);

        $font = "gadgets/Policy/captchas/HKCaptcha/comicbd.ttf";
        $width  = 15 * imagefontwidth (5);
        $height = 4 * imagefontheight (5);

        $tmpimg  = imagecreate($width*2, $height*2);
        $bgColor = imagecolorallocatealpha ($tmpimg, 255, 255, 255, 127);
        $col = imagecolorallocate($tmpimg, 0, 0, 0);

        // init final image
        $img = imagecreate($width, $height);
        imagepalettecopy($img, $tmpimg);    
        imagecopy($img, $tmpimg, 0,0 ,0,0, $width, $height);

        // put text into $tmpimg
        $fsize = $height*0.6;
        $bb = imageftbbox($fsize, 0, $font, $value);
        $tx = $bb[4]-$bb[0];
        $ty = $bb[5]-$bb[1];
        $x = floor($width - $tx/2 - $bb[0]);
        $y = round($height - $ty/2 - $bb[1]);
        imagettftext($tmpimg, $fsize, 0, $x, $y, -$col, $font, $value);

        // warp text
        $this->warped_image($tmpimg, $img);

        header("Content-Type: image/png");
        imagepng($img);
        imagedestroy($img);
        imagedestroy($tmpimg);
    }

}