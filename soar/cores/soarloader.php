<?php
/**
 * @file	soarloader.php
 * @brief	Implementation of the class autoloader of SoarPHP
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2014-11-03
 */

/**
 * Many developers writing object-oriented applications create one PHP source file per class definition. 
 * One of the biggest annoyances is having to write a long list of needed includes at the beginning of 
 * each script (one for each class).
 * class SoarLoader enable the feature that the developer can create a new object of any classes without 
 * manually listing the long expatiatory includes.
 */

class SoarLoader {
    public static $loader;///< the shared instance of SoarLoader
     
    private static $include_path_;
    private static $namespace_pattern_;
    private static $register_queue_;

    /**
     * @brief		Initialize the SoarLoader instance
     * @detailed	Initialize the SoarLoader instance. It guarantee the that singleton pattern, which means for each request session,
     *              there will be only one SoarLoader instance
     * @return		Global SoarLoader instance
     */
    public static function init() {
        if(!self::$loader)
            self::$loader = new self();
        return self::$loader;
    }
    /**
     * Constructor of SoarLoader
     * Register the three Global Root Namespace of SoarPHP which represent the 3 basic hierarchy types.
     * i.e.:
     *  - SoarObj
     *  - SoarLib
     *  - SoarClass
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
    /**
     * Autoload functions of SoarPHP
     * @param string $className
     */ 
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
