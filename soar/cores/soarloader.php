<?php
/**
 * @file	soarloader.php
 * @brief	实现所有基本类型的动态加载
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2014-11-03
 */

/**
 * class SoarLoader实现了对soar php中所有符合预置规格的类型的动态加载
 */

class SoarLoader {
    public static $loader;///< 用以保存SoarLoader实例的静态变量
     
    private static $include_path_;
    private static $namespace_pattern_;
    private static $register_queue_;

    /**
     * @brief		初始化SoarLoader实例
     * @detailed	初始化SoarLoader实例，确保针对每次请求，一个soar php的app全局只有一个该实例，会被soar php自动调用
     * @return		对象的全局实例
     */
    public static function init() {
        if(!self::$loader)
            self::$loader = new self();
        return self::$loader;
    }
    /**
     * 构造函数，注册所有的类加载函数
     */
    public function __construct() {
        self::$include_path_ = dirname( dirname( __FILE__ ) );
        self::$namespace_pattern_ = array(
                        'SoarObj' => 'CommonObjLoader',
                        'SoarLib' => 'CommonLibLoader',
                        'SoarClass' => 'CommonGeneralLoader',
        );
        self::$register_queue_ = array(
                        'ConfigureLoader',
                        'ViewLoader',
                        'CookieLoader',
                        'CommonObjLoader',
                        'ModelLoader',
                        'CommonLibLoader',
                        'CommonGeneralLoader'
        );
        spl_autoload_register(array($this , 'autoload'));
    }
     
    public function autoload( $className ) {
        $namespace_parts = explode('\\', $className);
        if (count($namespace_parts) > 1 && isset(self::$namespace_pattern_[$namespace_parts[0]])) {
            //is namespace mode
            $this->{self::$namespace_pattern_[$namespace_parts[0]]}(array_slice($namespace_parts, 1));
        } else {
            foreach (self::$register_queue_ as $func) {
                if ( $this->{$func}($className) ){
                    break;
                }
            }
        }
    }
     
    public function CommonObjLoader( $namespace_parts ) {
        $tmp = $this->filter_filepath($namespace_parts);
        $filepath = self::$include_path_.DIRECTORY_SEPARATOR."objects".DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, $tmp).".obj.php";
        if (file_exists($filepath)) {
            require_once 'object.php';
            require_once $filepath;
            return true;
        }
        return false;
    }
    public function CommonLibLoader( $namespace_parts ) {
        if (is_string($namespace_parts)) {
            $filepath = self::$include_path_.DIRECTORY_SEPARATOR."libs".DIRECTORY_SEPARATOR.strtolower($namespace_parts).".lib.php";
            if (file_exists($filepath)) {
                require_once $filepath;
                return true;
            }
        }
        $tmp = $this->filter_filepath($namespace_parts);
        $filepath = self::$include_path_.DIRECTORY_SEPARATOR."libs".DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, $tmp).".lib.php";
        if (file_exists($filepath)) {
            require_once $filepath;
            return true;
        }
        return false;
    }
    public function CommonGeneralLoader( $namespace_parts ) {
        if (is_string($namespace_parts)) {
            $filepath = self::$include_path_.DIRECTORY_SEPARATOR."general_classes".DIRECTORY_SEPARATOR.strtolower($namespace_parts).".class.php";
            if (file_exists($filepath)) {
                require_once $filepath;
                return true;
            }
        }
        $tmp = $this->filter_filepath($namespace_parts);
        $filepath = self::$include_path_.DIRECTORY_SEPARATOR."general_classes".DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, $tmp).".class.php";
        if (file_exists($filepath)) {
            require_once $filepath;
            return true;
        }
        return false;
    }
    
    public function ModelLoader( $modelName ) {
        if (substr($modelName, -3) != "Dao") {
            return false;
        }
        $tmp = $this->filter_filepath(substr($modelName , 0 , -3));
        $filepath = self::$include_path_.DIRECTORY_SEPARATOR."models".DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, $tmp).".model.php";
        if (file_exists($filepath)) {
            require_once 'model.php';
            require_once $filepath;
            return true;
        }
        return false;
    }
    public function ConfigureLoader( $className ) {
        if ($className == "SoarConfig") {
            require_once 'soarconfig.php';
            return true;
        }
        return false;
    }
    
    public function ViewLoader( $className ) {
        if ($className == "SoarView") {
            require_once 'soarview.php';
            return true;
        }
        return false;
    }
    
    public function CookieLoader( $className ) {
        if ($className == "SoarCookie") {
            require_once 'soarcookie.php';
        }
    }
    
    
    
    private function filter_filepath( $parts ) {
        if (!is_array($parts)) {
            $parts = array($parts);
        }
        $rtn = array();
        foreach($parts as $k => $v) {
            $rtn[$k] = strtolower(trim((join("_", preg_split("/(?=[A-Z])/", $v))), '_'));
        }
        return $rtn;

    }
}
SoarLoader::init();
