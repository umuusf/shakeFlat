<?php
/**
 * core/util.php
 *
 */

namespace shakeFlat;
use \DateTime;

class Util
{
    public static function isMobile()
    {
        $useragent=$_SERVER['HTTP_USER_AGENT'];
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4)))
            return true;
        return false;
    }

    public static function remoteIP()
    {
        if(getenv('HTTP_CLIENT_IP') &&
            strcasecmp(getenv('HTTP_CLIENT_IP'),'unknown')) {
                $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') &&
            strcasecmp(getenv('HTTP_X_FORWARDED_FOR'),'unknown')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') &&
            strcasecmp(getenv('REMOTE_ADDR'),'unknown')) {
                $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) &&
            $_SERVER['REMOTE_ADDR']  &&
            strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
                $ip = $_SERVER['REMOTE_ADDR'];
        } else {
                $ip = 'unknown';
        }

        if (strpos($ip, ",") != false) {
            $x = explode(",", $ip);
            $ip = $x[0];
        }
        return $ip;
    }

    // Get the structure of the currently called URL. (protocol + domain)
    public static function calledURL()
    {
        $protocol = "http://";
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $protocol = "https://";
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
            $protocol = "https://";
        }

        $domain = $_SERVER["HTTP_HOST"] ?? (($_SERVER["SERVER_NAME"] ?? "") . ($_SERVER["SERVER_PORT"] ?? ""));

        return $protocol . $domain . "/";
    }

    // For the defined constants, it creates a list of things that correspond to the prefix.
    public static function defineList($prefix, $isKeepPrefix = false)
    {
        return self::_defineList(get_defined_constants(), $prefix, $isKeepPrefix);
    }

    // For the defined constants, among those corresponding to the prefix, the key of the one whose value is $value is found.
    public static function defineFindKey($prefix, $value)
    {
        $list = self::defineList($prefix);
        return array_search($value, $list);
    }

    // For the defined constants in class, it creates a list of things that correspond to the prefix.
    public static function classDefineList($className, $prefix, $isKeepPrefix = false)
    {
        $refl = new \ReflectionClass($className);
        return self::_defineList($refl->getConstants(), $prefix, $isKeepPrefix);
    }

    // For the defined constants, among those corresponding to the prefix, the key of the one whose value is $value is found.
    public static function classDefineFindKey($prefix, $value)
    {
        $list = self::classDefineList($prefix);
        return array_search($value, $list);
    }

    private static function _defineList($list, $prefix, $isKeepPrefix)
    {
        $reList = array();
        foreach($list as $constName => $value) {
            if (strlen($constName) <= strlen($prefix)) continue;
            if ($prefix != substr($constName, 0, strlen($prefix))) continue;

            if (!$isKeepPrefix) $constName = substr($constName, strlen($prefix));
            $reList[$constName] = $value;
        }

        return $reList;
    }

    // Shorten long numbers in Kilo, Mega, Giga, and Tera units.
    public static function unitFormatNumber($n, $isskip1000 = false)
    {
        if ($n >= 1000 && $n < 1000000 && !$isskip1000) {
            // 1k-999k
            $n_format = floor($n / 1000);
            $suffix = 'K';
        } else if ($n >= 1000000 && $n < 1000000000) {
            // 1m-999m
            $n_format = floor($n / 1000000);
            $suffix = 'M';
        } else if ($n >= 1000000000 && $n < 1000000000000) {
            // 1b-999g
            $n_format = floor($n / 1000000000);
            $suffix = 'G';
        } else if ($n >= 1000000000000) {
            // 1t+
            $n_format = floor($n / 1000000000000);
            $suffix = 'T';
        } else {
            // 1 - 999
            $n_format = floor($n);
            if ($isskip1000) $n_format = number_format($n_format);
            $suffix = '';
        }

        return !empty($n_format . $suffix) ? $n_format . $suffix : 0;
    }

    // Prepositional particles in Korean
    // ex) 영희이(가) 가방을(를) 찾았다
    public static function koreanJosa($ex)
    {
        $pps = ["은(는)","이(가)","을(를)","과(와)"];
        foreach( $pps as $pp ) {
            $ex = preg_replace_callback("/(.)".preg_quote($pp)."/u",
                function($matches) use($pp) {
                    $ch = $matches[1];
                    $has_jongsung = true;
                    if(preg_match('/[가-힣]/',$ch)) {
                        $code = (ord($ch[0])&0x0F)<<12 | (ord($ch[1])&0x3F)<<6 | (ord($ch[2])&0x3F);
                        $has_jongsung = ( ($code-16)%28 != 0 );
                    }
                    else if(preg_match('/[2459]/', $ch)) $has_jongsung = false;
                    return $ch.mb_substr($pp,($has_jongsung?0:2),1);
                }, $ex);
        }
        return $ex;
    }

    // Returns a value in the form of reading a number in Korean.
    public static function numberKorean($num) {
        if ( !is_numeric ( $num ) ) return "";
        $arr_number = strrev ( $num ) ;
        $ret = "";
        for( $i = strlen ( $arr_number ) - 1 ; $i >= 0 ; $i-- ) {
            $digit = substr ( $arr_number, $i, 1 ) ;
            switch ( $digit ) {
                case '-': $ret .= "(-) " ; break ;
                case '0': $ret .= "" ;      break ;
                case '1': $ret .= "일" ;   break ;
                case '2': $ret .= "이" ;   break ;
                case '3': $ret .= "삼" ;   break ;
                case '4': $ret .= "사" ;   break ;
                case '5': $ret .= "오" ;   break ;
                case '6': $ret .= "육" ;   break ;
                case '7': $ret .= "칠" ;   break ;
                case '8': $ret .= "팔" ;   break ;
                case '9': $ret .= "구" ;   break ;
            }
            if ( $digit == "-" ) continue ;
            if ( $digit != 0 ) {
                if ( $i % 4 == 1 ) $ret .= "십" ;
                else if ( $i % 4 == 2 ) $ret .= "백" ;
                else if ( $i % 4 == 3 ) $ret .= "천" ;
            }

            if ( $i % 4 == 0 ) {
                switch ( floor ( $i/4 ) ) {
                    case 0: $ret.= "" ;         break ;
                    case 1: $ret.= "만" ;      break ;
                    case 2: $ret.= "억" ;      break ;
                    case 3: $ret.= "조" ;      break ;
                    case 4: $ret.= "경" ;      break ;
                    case 5: $ret.= "해" ;      break ;
                    case 6: $ret.= "자" ;      break ;
                    case 7: $ret.= "양" ;      break ;
                    case 8: $ret.= "구" ;      break ;
                    case 9: $ret.= "간" ;      break ;
                    case 10: $ret.= "정" ;    break ;
                    case 11: $ret.= "재" ;    break ;
                    case 12: $ret.= "극" ;    break ;
                    case 13: $ret.= "항하사" ;    break ;
                    case 14: $ret.= "아승기" ;    break ;
                    case 15: $ret.= "나유타" ;    break ;
                    case 16: $ret.= "불가사의" ;    break ;
                    case 17: $ret.= "무량대수" ;    break ;
                }
            }
        }
        return $ret ;
    }

    // $t1, $t2 Displays the time difference in minutes/seconds.
    public static function timeDiffMinSec($t1, $t2, $isKorean = true)
    {
        $min = (int)(abs(strtotime($t1) - strtotime($t2)) / 60);
        $sec = (int)(abs(strtotime($t1) - strtotime($t2)) % 60);
        if ($isKorean) {
            $ret = $sec."초";
            if ($min > 0) $ret = $min."분 " . $ret;
        } else {
            $ret = $sec."second".(($sec > 1) ? "s" : "");
            if ($min > 0) $ret = $min."minute".(($min > 1) ? "s" : "")." " . $ret;
        }
        return $ret;
    }

    // Display the time difference in pretty style. (apple style)
    public static function timeDiffPretty($time, $postTime = null, $isKorean = true)
    {
        if ($postTime == null) $postTime = date("Y-m-d H:i:s");
        $gap = (int)(strtotime($time) - strtotime($postTime));
        if ($isKorean) {
            if ($gap < 0) $pp = "전"; else $pp = "후";
            $gap = abs($gap);
            if (intval($gap / (24*60*60)) > 0) $msg = intval($gap / (24*60*60)) . "일".$pp;
            elseif (intval($gap / (60*60)) > 0) $msg = intval($gap / (60*60)) . "시간".$pp;
            elseif (intval($gap / (60)) > 0) $msg = intval($gap / (60)) . "분".$pp;
            else $msg = $gap . "초".$pp;
        } else {
            if ($gap < 0) $pp = "ago"; else $pp = "later";
            $gap = abs($gap);
            $gd = intval($gap / (24*60*60)); if ($gd > 1) $ss = "s"; else $ss = "";
            if ($gd > 0) {
                $msg = $gd . "day{$ss} {$pp}";
            } else {
                $gd = intval($gap / (60*60)); if ($gd > 1) $ss = "s"; else $ss = "";
                if ($gd > 0) {
                    $msg = $gd . "hour{$ss} {$pp}";
                } else {
                    $gd = intval($gap / (60)); if ($gd > 1) $ss = "s"; else $ss = "";
                    if ($gd > 0) {
                        $msg = $gd . "minute{$ss} {$pp}";
                    } else {
                        if ($gap > 1) $ss = "s"; else $ss = "";
                        $msg = $gap . "second{$ss} {$pp}";
                    }
                }
            }
        }
        return $msg;
    }

    // return hash value (00~ff)
    public static function hash256($id)
    {
        return substr(md5($id), 0, 2);
    }

    // Check if the format of $format(YYYY-MM-DD HH:II:SS) is correct
    public static function validateDate($dateStr, $format = "Y-m-d H:i:s")
    {
        $dateStr = trim($dateStr);
        if (!$dateStr) return false;
        $dateTime = DateTime::createFromFormat($format, $dateStr);
        return $dateTime && ($dateTime->format($format) === $dateStr);
    }

    // Returns a non Y-m-d H:i:s format as a Y-m-d H:i:s format.
    public static function YmdHis($date, $format)
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime->format("Y-m-d H:i:s");
    }

    public static function number_formatX($p, $c = "")
    {
        if ($p == 0) return $c;
        return number_format($p);
    }

    public static function cutString($p, $l, $with3Dot = true)
    {
        $str = $p;
        if (mb_strlen($p) > $l) $str = mb_substr($p, 0, $l) . (($with3Dot) ? "..." : "");
        return $str;
    }

    // Generates information necessary for pagination.
    public static function paginationInfo($page, $pageSize, $total)
    {
        $startNo = (($page-1) * $pageSize) + 1;
        $endNo = $startNo + $pageSize - 1;
        if ($endNo > $total) $endNo = $total;

        $lastPage = intval(($total-1) / $pageSize) + 1;

        $startBtn = $page - 5;
        if ($startBtn < 1) $startBtn = 1;
        $endBtn = $startBtn + 9;
        if ($endBtn > $lastPage) {
            $endBtn = $lastPage;
            $startBtn = $endBtn - 9;
            if ($startBtn < 1) $startBtn = 1;
        }
        $prev = 0;
        $next = 0;
        if ($page > 1) $prev = $page - 1;
        if ($page < $lastPage) $next = $page + 1;

        return array (
            "startNo"   => $startNo,           // Showing {$startNo} to {$endNo} of {$total} entries
            "endNo"     => $endNo,
            "nowPage"   => $page,
            "startBtn"  => $startBtn,
            "endBtn"    => $endBtn,
            "prevPage"  => $prev,
            "nextPage"  => $next,
            "total"     => $total,
        );
    }

    public static function isJson($string) {
        if (!is_string($string)) return false;
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    // for debugging
    public static function webDump($p, $fontSize = 10)
    {
        if (self::isJson($p)) {
            $p = json_encode(json_decode($p), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        }
        echo "\n<br>\n<font style='font-size:{$fontSize}pt;'>" .
                str_replace("<span style=\"color: #0000BB\">&lt;?php<br /></span>", "", highlight_string("<?php\n".print_r($p,true), true)) .
                "</font><br>\n<br>\n";
    }

    // for debugging
    public static function webDumpExit($p, $fontSize = 10)
    {
        self::webDump($p, $fontSize);
        exit;
    }
}
