<?php
/**
 *  Des 加密实现类
 * DES/3DES加密解密，如果是3des，将MCRYPT_DES修改为MCRYPT_3DES
 *
 */
class Helper_Descrypt
{
    private static $cipherText = '';
    private static $HcipherText = '';
    private static $decrypted_data = '';

    //加密
    public static function encrypt($str, $key)
    {
        self::$cipherText = self::$HcipherText = self::$decrypted_data = '';

        $cipher = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);

        if (mcrypt_generic_init($cipher, substr($key, 0, 8), $iv) != -1) {
            self::$cipherText = mcrypt_generic($cipher, self::pad($str));
            mcrypt_generic_deinit($cipher);
            // 以十六进制字符显示加密后的字符
            self::$HcipherText = bin2hex(self::$cipherText);
            //printf("<p>3DES encrypted:\n%s</p>",$this->cipherText);
            //printf("<p>3DES HexEncrypted:\n%s</p>",$this->HcipherText);
        }
        mcrypt_module_close($cipher);
        //return $this->cipherText;
        return strtoupper(self::$HcipherText);
    }

    //解密
    public static function decrypt($str, $key)
    {
        self::$cipherText = self::$HcipherText = self::$decrypted_data = '';

        $str = pack('H*', $str);
        $cipher = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);

        if (mcrypt_generic_init($cipher, substr($key, 0, 8), $iv) != -1) {
            self::$decrypted_data = mdecrypt_generic($cipher, $str);
            mcrypt_generic_deinit($cipher);
        }
        mcrypt_module_close($cipher);
        return trim(chop(self::$decrypted_data));
//        return self::$unpad($this->decrypted_data);
    }

    private static function pad($data)
    {
        $data = str_replace("\n", "", $data);
        $data = str_replace("\t", "", $data);
        $data = str_replace("\r", "", $data);
        return $data;
    }

    private static function unpad($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, -1 * $pad);

    }
}