<?php
/**
 * core/token.php
 *
 * Create a token for login processing in the application for API service.
 * Similar to jwt or the well-known token solution for authentication.
 * The token created here cannot be decrypted by the client.
 * If you use the Cookie class(shakeFlat\core\Cookie) to store authentication information in cookies,
 * you do not need to use this class. Cookie class itself uses encryption packing.
 *
 */

namespace shakeFlat;
use shakeFlat\AES256;

class Token
{
    private $token = "";

    public function __construct($token = null)
    {
        if ($token !== null) $this->token = $token;
    }

    // Create a token
    // Delivers the contents in the form of an array to $payload. $expire is the token expiration time, in seconds.
    // To use application-specific HTTP header values pass the header name to $extraHTTPHeader. (It will be useful when the mobile app is the client)
    // Set $useBasicHTTPHeader to true to enhance security by using several http header values delivered from the client.
    // If $expire is 0, the token does not expire.
    public function create($payload, $expire = 3600, $extraHTTPHeader = "", $useBasicHTTPHeader = false)
    {
        if (!is_array($payload)) return false;

        $evi_extra = "";
        $evi_header = array();

        $httpHeader = getallheaders();
        if ($extraHTTPHeader != "") $evi_extra = $httpHeader[$extraHTTPHeader] ?? "";
        if ($useBasicHTTPHeader) {
            $evi_header = array (
                "Accept-Language" => $httpHeader["Accept-Language"] ?? "",
                "Accept-Encoding" => $httpHeader["Accept-Encoding"] ?? "",
                "Accept"          => $httpHeader["Accept"] ?? "",
                "User-Agent"      => $httpHeader["User-Agent"] ?? "",
            );
        }

        if ($expire != 0) $expire = time() + $expire;
        $data = array (
            "payload" => $payload,
            "evi" => array (
                "expire"  => $expire,
                "extra"   => array ( $extraHTTPHeader, $evi_extra ),
                "header"  => $evi_header,
                "random"  => uniqid() . rand(1000,9999),
            ),
        );

        $this->token = AES256::packObject($data);
        return $this->token;
    }

    // Read the payload data by interpreting the token.
    // Returns false if the token has expired or has been tampered with.
    public function payload()
    {
        $data = self::check();
        return $data["payload"] ?? false;
    }

    // with evi
    public function data()
    {
        return self::check();
    }

    public function expireTime()
    {
        $data = self::check();
        $expire = $data["evi"]["expire"] ?? 0;
        return $expire;
    }

    // update the token. Reset the expire time.
    // If $expire is 0, the token does not expire.
    public function refresh($expire = 3600)
    {
        $data = self::check();
        if ($data === false) return false;
        return self::refreshData($data, $expire);
    }

    // update the token(data:unpacked). Reset the expire time.
    // If $expire is 0, the token does not expire.
    public function refreshData($tokenData, $expire = 3600)
    {
        if ($expire != 0) $expire = time() + $expire;
        $tokenData["evi"]["expire"] = $expire;
        $tokenData["evi"]["random"] = uniqid() . rand(1000,9999);
        $this->token = AES256::packObject($tokenData);
        return $this->token;
    }

    // Check if the token is correct.
    private function check()
    {
        $data = AES256::unpackObject($this->token);
        if (!isset($data["payload"]) || !isset($data["evi"]["expire"]) || !isset($data["evi"]["extra"]) || !isset($data["evi"]["header"])) return false;

        if ($data["evi"]["expire"] != 0 && $data["evi"]["expire"] < time()) return false;

        $httpHeader = getallheaders();
        if (($data["evi"]["extra"][0] ?? "") != "") {
            if (!isset($httpHeader[$data["evi"]["extra"][0]]) || $httpHeader[$data["evi"]["extra"][0]] !== ($data["evi"]["extra"][1] ?? null)) return false;
        }

        if ($data["evi"]["header"]) {
            $evi_header = array (
                "Accept-Language" => $httpHeader["Accept-Language"] ?? "",
                "Accept-Encoding" => $httpHeader["Accept-Encoding"] ?? "",
                "Accept"          => $httpHeader["Accept"] ?? "",
                "User-Agent"      => $httpHeader["User-Agent"] ?? "",
            );
            if ($data["evi"]["header"] != $evi_header) return false;
        }

        return $data;
    }
}