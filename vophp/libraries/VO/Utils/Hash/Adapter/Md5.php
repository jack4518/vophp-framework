<?php
/**
 * Uses MD5 to hash a value into a 32bit binary string data address space.
 * @author Paul Annesley
 * @package Flexihash
 * @licence http://www.opensource.org/licenses/mit-license.php
 */
class VO_Utils_Hash_Adapter_Md5 implements VO_Utils_Hash_Adapter_Interface{
    public function hash($string)
    {
        return substr(md5($string), 0, 8);
    }
}
