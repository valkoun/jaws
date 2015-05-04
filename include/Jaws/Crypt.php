<?php
/**
 * Class that deals like a wrapper between Jaws and pear/Crypt
 *
 * @category   Crypt
 * @package    Core
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Crypt
{
    var $wrapper = '';   // if empty try to load the most suitable wrapper
    var $math    = null; // instance of math wrapper class
    var $pvt_key = '';   // private key
    var $pub_key = '';   // public key
    var $key_len = 128;  // key length
    var $rsa     = null; // instance of Crypt_RSA

    /**
     * @access constructor
     */
    function Jaws_Crypt()
    {
        require_once 'Crypt/RSA.php';
        if (empty($this->wrapper) || !extension_loaded(strtolower($this->wrapper))) {
            $this->wrapper = extension_loaded('bcmath')? 'BCMath' : '';
            //$this->wrapper = extension_loaded('gmp')? 'GMP' : (extension_loaded('bcmath')? 'BCMath' : '');
        }

        if (!empty($this->wrapper)) {
            $this->rsa = new Crypt_RSA(null, $this->wrapper);
            $this->math = Crypt_RSA_MathLoader::loadWrapper($this->wrapper);
        }
    }

    /**
     * @access public
     */
    function Init($force_enabled = false)
    {
        if (!isset($GLOBALS['app'])) {
            return new Jaws_Error('$GLOBALS[\'app\'] not available', 'CRYPT', JAWS_ERROR_ERROR);
        }
		if ($force_enabled === false && $GLOBALS['app']->Registry->Get('/crypt/enabled') != 'true') {
            return false;
        }

        $pvt_key        = $GLOBALS['app']->Registry->Get('/crypt/pvt_key');
        $pub_key        = $GLOBALS['app']->Registry->Get('/crypt/pub_key');
        $key_len        = $GLOBALS['app']->Registry->Get('/crypt/key_len');
        $key_age        = $GLOBALS['app']->Registry->Get('/crypt/key_age');
        $key_start_date = $GLOBALS['app']->Registry->Get('/crypt/key_start_date');
        if ($pvt_key == '1' || $pub_key == '1' || time() > ($key_start_date + $key_age)) {
            $result = $this->Generate_RSA_KeyPair($key_len);
            if (Jaws_Error::isError($result)) {
                $GLOBALS['app']->Registry->Set('/crypt/enabled', 'false');
                $GLOBALS['app']->Registry->Commit('core');
                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "Error in RSA key generation..");
                }
                return false;
            }

            $GLOBALS['app']->Registry->Set('/crypt/pvt_key', $this->pvt_key->toString());
            $GLOBALS['app']->Registry->Set('/crypt/pub_key', $this->pub_key->toString());
            $GLOBALS['app']->Registry->Set('/crypt/key_start_date', time());
            $GLOBALS['app']->Registry->Commit('core');
        } else {
            $this->pvt_key = Crypt_RSA_Key::fromString($pvt_key, $this->wrapper);
            $this->pub_key = Crypt_RSA_Key::fromString($pub_key, $this->wrapper);
        }

        return true;
    }

    function Generate_RSA_KeyPair($key_len = 128)
    {
        if (empty($this->wrapper)) {
            return new Jaws_Error("can't load any wrapper for existing math libraries", CRYPT_RSA_ERROR_NO_WRAPPERS);
        }

        if (empty($key_len)) {
            $key_len = $this->key_len;
        }

        $key_pair = new Crypt_RSA_KeyPair($key_len, $this->wrapper);
        if (PEAR::IsError($key_pair)) {
            return new Jaws_Error($key_pair->getMessage());
        }

        $this->pvt_key = $key_pair->getPrivateKey();
        $this->pub_key = $key_pair->getPublicKey();

        unset($key_pair);
    }

    function CreateSignature($doc, $pvt_key = null, $hash_func = null)
    {
        if (is_null($pvt_key)) {
            $pvt_key = $this->pvt_key;
        }
        $sign = $this->rsa->createSign($doc, $pvt_key, $hash_func);
        if (PEAR::IsError($sign)) {
            return new Jaws_Error($sign->getMessage());
        }
        return $sign;
    }

    function ValidateSignature($doc, $sign, $pub_key = null)
    {
        if (is_null($pub_key)) {
            $pub_key = $this->pub_key;
        }

        $result = $this->rsa->validateSign($doc, $sign, $pub_key);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage());
        }

        return $result;
    }

    function encrypt($plain_text, $pub_key = null)
    {
        if (is_null($pub_key)) {
            $pub_key = $this->pub_key;
        }
        $result = $this->rsa->encrypt($plain_text, $pub_key);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage());
        }

        return $result;
    }

    function decrypt($enc_text, $pvt_key = null)
    {
        if (is_null($pvt_key)) {
            $pvt_key = $this->pvt_key;
        }
        $this->rsa->setParams(array('dec_key' => $pvt_key));
        $result = $this->rsa->decrypt($enc_text);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage());
        }

        return $result;
    }

}