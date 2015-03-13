<?php
/**
 * 框架工具类
 * @author guangzhao1@leju.com
 * @author guangzhao1
 */

class Leb_Util 
{
    /**
     * 兼容的json编码
     * @return string
     */
    public static function jsonEncode($value)
    {
        $json = false;
        if(version_compare(PHP_VERSION, '5.4.0') >= 0)
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        elseif(version_compare(PHP_VERSION, '5.2.0') >= 0)
            $json = json_encode($value);
        return $json;
    }

    /**
     * 兼容的json解码函数
     * @return object
     */
    public static function jsonDecode($json)
    {
        $value = false;
        if(!is_string($json))
            throw new Exception("Can't decode json");

        if(version_compare(PHP_VERSION, '5.4.0') >= 0)
            $value = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
        elseif(version_compare(PHP_VERSION, '5.2.0') >= 0)
            $value = json_decode($json, true);
        return $value;
    }

    /**
     * json_last_error
     * @return int
     */
    public static function jsonLastError()
    {
        return json_last_error();
    }
     
    /**
     * 返回json最后一次错误描述信息
     * @return string
     */
    public static function jsonLastStrerror()
    {
        $eno = json_last_error();
        $err = 'Unknown error.';
        switch($eno) {
        case JSON_ERROR_NONE:
            $err = 'No error has occurred';
            break;
        case JSON_ERROR_DEPTH:
            $err = 'The maximum stack depth has been exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $err = 'Invalid or malformed JSON';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $err = 'Control character error, possibly incorrectly encoded';
            break;
        case JSON_ERROR_SYNTAX:
            $err = 'Syntax error';
            break;
        default:
            if (version_compare(PHP_VERSION, '5.3.3') >= 0 && $eno == JSON_ERROR_UTF8)
                $err = 'Malformed UTF-8 characters, possibly incorrectly encoded';
        }

        return $err;
    }

    /**
     * 是否当前的HTTP请求为AJAX请求
     */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? true : false;
    }

    /**
     * 判断给定的字符串是否是email地址
     */
    public static function isEmail($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex+1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } else if (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } else if (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                                   str_replace("\\\\","",$local))) {
                // character not valid in local part unless 
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
                    $isValid = false;
                }
            }
            if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
                // domain not found in DNS
                $isValid = false;
            }
        }
        return $isValid;
        return false;
    }

    /**
     * 在$ips给出的格式不正确时
     */
    public static function inIpList($nip, $ips)
    {
        $vipp = null;
        $functor_body = 
                'if (is_numeric($cipp)) {
                    if ($cipp >= 0 && $cipp <= 255) {
                        return true;
                    }
                }
                return false;';
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            eval('$vipp = function($cipp) { ' . $functor_body . '};');
        } else {
            $vipp = create_function('$cipp', $functor_body);
        }
        
        if (empty($ips) || $ips == '*') {
            return true;
        }
        if (is_array($ips)) {
            $regraw = "/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/";
            $regrange = ""; // ".0/24, .0/16, .0/8 ..
            $aipnum = ip2long($nip);
            foreach ($ips as $idx => $cip) {
                $cip = trim($cip);

                if (empty($cip) || $cip == '*' || $cip == $nip) {
                    return true;
                }
                
                $ipp = explode('.', $cip);
                if (count($ipp) != 4) {
                    continue;
                }

                $bip_ps = array();
                $eip_ps = array();
                $valid_ip = false;
                foreach ($ipp as $k => $pn) {
                    if ($k == 3) {
                        if (strchr($pn, '/')) {
                            $suff = substr($pn, strpos($pn, '/')+1, 3);

                            if ($suff == 8) {
                                $bip_ps[1] = $bip_ps[2] = $bip_ps[3] = 0;
                                $eip_ps[2] = $eip_ps[3] = 255;
                                $eip_ps[1] = 255;
                            } else if ($suff == 16) {
                                $bip_ps[2] = $bip_ps[3] = 0;
                                $eip_ps[2] = $eip_ps[3] = 255;
                            } else {
                                $bip_ps[3] = 0;
                                $eip_ps[3] = 255;
                            }
                            $valid_ip = true;
                        } else {
                            if ($vipp($pn)) {
                                $bip_ps[] = $pn;
                                $eip_ps[] = $pn;
                                $valid_ip = true;
                            } else {
                                break;
                            }
                        }
                    } else {
                        if ($vipp($pn)) {
                            $bip_ps[] = $pn;
                            $eip_ps[] = $pn;
                        } else {
                            break;
                        }
                    }
                }

                if ($valid_ip) {
                    $bipnum = ip2long(implode('.', $bip_ps));
                    $eipnum = ip2long(implode('.', $eip_ps));
                 
                    if ($aipnum >= $bipnum && $aipnum <= $eipnum) {
                        return true;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }
        } else {
            return $ips == $nip;
        }
        return false;
    }    

    /**
     * 获取客户端ip
     * @return string
     */
    public static function getRealIp()
    {
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"]))
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if($ip) { array_unshift($ips, $ip); $ip = FALSE; }
            for($i = 0; $i < count($ips); $i++)
            {
                // if (!eregi ("^(10|172\.16|192\.168)\.", $ips[$i])) {
                if(!preg_match('/(10|172\.16|192\.168)/i', $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }

        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    public static function getIp() 
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    public static function getMyIp()
    {
        return self::getIp();
    }

    public static function refcount($var, &$refs = null)
    {
        ob_start();
        debug_zval_dump($var);
        $refs = ob_get_contents();
        ob_end_clean();
    
        $c = PHP_INT_MAX;
        if(preg_match("/ refcount\((\d+)\)/ms", $refs, $mats))
            $c = $mats[1] - 3;

        return $c;
    }

    /**
     * 计算图片指纹
     */
    public static function imageHash($file)
    {
        if(!file_exists($file))
            return false;
        $height = $width = 8;
        $img = imagecreatetruecolor($width, $height);
        list($w, $h) = getimagesize($file);
        $source = imagecreatefromjpeg($file);
        imagecopyresampled($img, $source, 0, 0, 0, 0, $width, $height, $w, $h);
        imagedestroy($source);
        $value = self::getHashValue($img);
        imagedestroy($img);
        return $value;
    }

    public static function getHashValue($img)
    {
        $width = imagesx($img);
        $height= imagesy($img);
        $total = 0;
        $array = array();
        for($y =0; $y < $height; $y++)
        {
            for($x=0; $x < $width; $x++)
            {
                $gray = (imagecolorat($img, $x, $y)>>8)&0xff;
                if(!isset($array[$y]))
                    $array[$y] = array();
                $array[$y][$x] = $gray;
                $total += $gray;
            }
        }

        $average = intval($total / (64));
        $result = '';
        for($y=0; $y < $height; $y++)
        {
            for($x=0; $x < $width; $x++)
            {
                if($array[$y][$x]>=$average)
                    $result .= '1';
                else
                    $result .= '0';
            }
        }

        return $result;
    }
}

//Compatible with older versions
class Util extends Leb_Util
{
}
