<?php
/**
 * @file       soarcookie.php
 * @brief      定义了SoarPHP中设置和获取可跨Session访问变量的方法
 * @author     House.Lee(house.lee@soarnix.com)
 * @date       2014-10-14
 */
//TODO: when use SecureSet, try to retrieve current cookies first in order to prevent over write
//      the previous buffers.

define('VALUE_IDX' , 0);
define('EXPIRE_IDX' , 1);
class SoarCookie {
    private static $fs_path_;
    private static $cookie_db_;
    private static $db_conn_;
    private static $cookie_prefix_;
    private static $cookie_sign_;
//     private static $max_cookie_length_; //因为支持了富类型cookie，所以在没想好如何衡量cookie容量之前，暂时取消max cookie限制
//     private static $current_cookie_length_;
    private static $max_cookie_expiration_;

    private static $cookie_buffer_;
    
    private static $instance;///< 全局SoarCookie实例，以实现“随叫随到”
    
    /**
     * 初始化SoarCookie
     * @return SoarCookie
     */
    public static function init() {
        if(!self::$instance)
            self::$instance = new self();
        return self::$instance;
    }
    
    public function __construct() {
        self::$fs_path_ = dirname( dirname( __FILE__ ) )."/__static__/cookie_cache/";
        self::$cookie_db_ = null;
        self::$db_conn_ = null;
        self::$cookie_prefix_ = "soarphp_cookie:";
//         self::$max_cookie_length_ = 4096;
        self::$max_cookie_expiration_ = 2592000; // 86400 * 30, 30days
//         self::$current_cookie_length_ = 0;
        self::$cookie_buffer_ = array();
        
        $cookie_sign = SoarConfig::get('main.cookie_sign');
        if ($cookie_sign == null) {
            $cookie_sign = "l8gdXxW6Or5MNoLGazZvUFKcBk1y3Ju4Rmn0SpswPhVYIbqCj7Q9TDtiHeAEf2";
        }
        self::$cookie_sign_ = $cookie_sign;
        
        $cookie_db = SoarConfig::get('main.cookie_db');
        if (is_array($cookie_db) && isset($cookie_db['host']) && isset($cookie_db['port'])) {
            self::$cookie_db_ = $cookie_db;
            try {
                self::$db_conn_ = new Redis($cookie_db['host'], $cookie_db['port'] , 2);
            } catch (Exception $e) {
                self::$cookie_db_ = null;
                self::$db_conn_ = null;
		    }
        }
    }
    
    public static function NormalSet($name , $value , $expire = 86400) {
        setcookie($name , $value , time() + $expire , null , null , null , true);
    }
    public static function NormalGet($name) {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } else {
            return null;
        }
    }
    public static function NormalDel($name) {
        setcookie($name , null , 0);
    }
    public static function Set($name , $value , $expire = 86400 , $restful_return = false) {
        $name = trim($name);
        if ($name == "" || $expire <= 0) {
            return false;
        }
        $expire += time();
        $cookie_key = self::_get_cookie_key_();
        if ($cookie_key == null) {
            $cookie_key = self::_generate_cookie_key_();
//             self::$current_cookie_length_ = 0;
            self::$cookie_buffer_ = array();
        } elseif (!count(self::$cookie_buffer_)) {
            self::_retrieve_cookie_();
        }
//         $kv_len = strlen($name) + strlen($value);
        self::$cookie_buffer_[$name] = array($value , $expire);
        $rtn = true;
        if($restful_return) {
            Controller::set_return('soarphp_cookie_access', $cookie_key);
        } else {
            $rtn = $cookie_key;
        }
        setcookie("soarphp_cookie_access" , $cookie_key , time() + self::$max_cookie_expiration_ , null , null , null , true);
        self::_save_cookie_($cookie_key);
        return $rtn;
    }
    public static function Get($name) {
        $name = trim($name);
        if ($name == "") {
            return null;
        }
        if (!count(self::$cookie_buffer_)) {
            self::_retrieve_cookie_();
        }
        if (!isset(self::$cookie_buffer_[$name])) {
            return null;
        }
        $nowtime = time();
        if ($nowtime > self::$cookie_buffer_[$name][EXPIRE_IDX]) {
            foreach(self::$cookie_buffer_ as $k => $v) {
                if ($nowtime > $v[EXPIRE_IDX]) {
//                     self::$current_cookie_length_ -= strlen($k) + strlen($v[VALUE_IDX]);
                    unset(self::$cookie_buffer_[$k]);
                }
            }
            self::_save_cookie_(self::_get_cookie_key_());
            return null;
        }
        return self::$cookie_buffer_[$name][VALUE_IDX];
    }
    public static function Delete($name) {
        $name = trim($name);
        if ($name == "") {
            return false;
        }
        $cookie_key = self::_get_cookie_key_();
        if ($cookie_key == null) {
            return false;
        }
        if (!count(self::$cookie_buffer_)) {
            self::_retrieve_cookie_();
        }
        if (!isset(self::$cookie_buffer_[$name])) {
            return false;
        }
        unset(self::$cookie_buffer_[$name]);
        self::_save_cookie_($cookie_key);
        return true;
    }
    
    
    /********private functions********/
    
    private static function _get_cookie_key_() {
        $cookie_key = Controller::GetRequest('soarphp_cookie_access');
        if ($cookie_key == null && isset($_COOKIE['soarphp_cookie_access'])) {
                $cookie_key = $_COOKIE['soarphp_cookie_access'];
        }
        return $cookie_key;
    }
    private static function _generate_cookie_key_() {
        $client_id = HttpUtilities::getClientIP()."-".HttpUtilities::getClientAgent();
        $random_id = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 
                        0,
                        10);
        return $random_id."_".hash("sha256" , self::$cookie_sign_.$client_id.$random_id);
    }
    
    private static function _retrieve_cookie_() {
//         self::$current_cookie_length_ = 0;
        self::$cookie_buffer_ = array();
        $cookie_key = self::_get_cookie_key_();
        if ($cookie_key == null)
            return false;
        $cookie_content = null;
        if (self::$db_conn_) {
            try {
                $cookie_content = self::$db_conn_->Get("soarphp_cookie:".$cookie_key);
            } catch (Exception $e) {
                throw $e;
            }
        }
        if ($cookie_content == null) {
            if (!file_exists(self::$fs_path_.$cookie_key)) {
                return false;
            }
            $cookie_content = file_get_contents(self::$fs_path_.$cookie_key);
        }
        $cookie_content = json_decode($cookie_content , true);
        $need_resave = false;
        $nowtime = time();
        foreach($cookie_content as $k => $v) {
            if ($nowtime >= $v[EXPIRE_IDX]) {
                $need_resave = true;
            } else {
                self::$cookie_buffer_[$k] = $v;
//                 self::$current_cookie_length_ += strlen($k) + strlen($v[VALUE_IDX]);
            }
        }
        if ($need_resave) {
            self::_save_cookie_($cookie_key);
        }
        return true;
    }
    
    private static function _save_cookie_($cookie_key) {
        if ($cookie_key == null || $cookie_key == "") {
            throw new Exception("cookie key empty");
        }
        if (self::$db_conn_) {
            try {
                self::$db_conn_->Set("soarphp_cookie:".$cookie_key, json_encode(self::$cookie_buffer_));
                self::$db_conn_->Expire("soarphp_cookie:".$cookie_key, self::$max_cookie_expiration_);
            } catch (Exception $e) {
                throw $e;
            }
        } else {
            if (!($fp = @fopen(self::$fs_path_.$cookie_key , "w"))) {
                throw new Exception("save cookie failed: could not save auth files");
                return false;
            }
            @fwrite($fp , json_encode(self::$cookie_buffer_));
            fclose($fp);
        }
    }
}
SoarCookie::init();
