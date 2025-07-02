<?php
/**
 * aes256 encrypt/decrypt
 *
 */

namespace shakeFlat;
use Exception;

class AES256
{
    public static function pack($string, $key = null, $iv = null, $is_zip = true)
    {
        if ($key === null) $key = SHAKEFLAT_ENV["aes256"]["key_only_server"];
        if ($iv === null) $iv = SHAKEFLAT_ENV["aes256"]["iv_only_server"] ?? str_repeat("0", 16);

        if ($is_zip) {
            return base64_encode(self::encrypt(gzencode($string, 9), $key, $iv));
        } else {
            return base64_encode(self::encrypt($string, $key, $iv));
        }
    }

    public static function unpack($pack, $key = null, $iv = null, $is_zip = true)
    {
        if ($key === null) $key = SHAKEFLAT_ENV["aes256"]["key_only_server"];
        if ($iv === null) $iv = SHAKEFLAT_ENV["aes256"]["iv_only_server"] ?? str_repeat("0", 16);

        try {
            if ($is_zip) {
                $d = self::decrypt(base64_decode($pack), $key, $iv);
                if (!$d) return null;
                $string = gzdecode($d);
                if (!$string) return null;
                return $string;
            } else {
                $string = self::decrypt(base64_decode($pack), $key, $iv);
                if ($string) return $string;
                return null;
            }
        } catch(Exception $e) {
            return null;
        }
    }

    // for resources that can handle json_encode
    public static function packObject($object, $key = null, $iv = null, $is_zip = true)
    {
        return self::pack(json_encode($object, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), $key, $iv, $is_zip);
    }

    public static function unpackObject($packObject, $key = null, $iv = null, $is_zip = true)
    {
        $object = self::unpack($packObject, $key, $iv, $is_zip);
        if ($object === null) return null;
        return json_decode($object, true);
    }

    public static function encrypt($text, $key, $iv = null)
    {
        if ($iv === null) $iv = SHAKEFLAT_ENV["aes256"]["iv_only_server"] ?? str_repeat("0", 16);
        $ret = openssl_encrypt($text, "aes-256-cbc", $key, true, $iv);
        if ($ret) return $ret;
        return null;
    }

    public static function decrypt($text, $key, $iv = null)
    {
        if ($iv === null) $iv = SHAKEFLAT_ENV["aes256"]["iv_only_server"] ?? str_repeat("0", 16);
        $ret = openssl_decrypt($text, "aes-256-cbc", $key, true, $iv);
        if ($ret) return $ret;
        return null;
    }
}