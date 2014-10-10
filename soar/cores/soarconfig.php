<?php
/**
 * @file 	soarconfig.php
 * @brief	定义了SoarPHP中所有配置获取的方法类 SoarConfig
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2013-03-06
 */

/**
 * CLASS SoarConfig. Class SoarConfig提供了所有配置信息动态获取和加载的方法
 */
class SoarConfig {
	private static $base_path_; ///< 存储了配置文件的根路径，初始为soar下的config目录
	private static $confReg;///< 每次加载一个配置后，放入缓存中，从而避免多次读取同一文件以加快效率
	
	private static $instance;///< 全局SoarConfig实例，以实现“随叫随到”
	
	/**
	 * 初始化SoarConfig
	 * @return SoarConfig
	 */
	public static function init() {
		if(!self::$instance)
			self::$instance = new self();
		return self::$instance;
	}
	
	/**
	 * 构造函数，初始化配置根目录和缓存
	 */
	public function __construct() {
		self::$base_path_ = dirname( dirname( __FILE__ ) )."/config/";
		self::$confReg = array();
	}
	
	public static function set_path($path) {
		self::$base_path_ = dirname( dirname( __FILE__ ) )."/config/".$path."/";
	}
	
	public static function get_path() {
		return self::$base_path_;
	}
	public static function append_path($path) {
		self::$base_path_ .= $path."/";
	}
	/**
	 * 获取配置
	 * @param string $config 希望获取的配置，子配置用.分割，比如"zhcn.main.db"将会加载"zhcn/main.conf.php"中的db数组
	 * @return boolean|Ambigous <>	如果成功则返回对应的配置，否则返回false
	 */
	public static function get( $config ) {
		$path_arr = explode('.', $config);
		$real_path = self::$base_path_;
		$upper = count($path_arr) - 2;
		for ($i = 0; $i != $upper; ++$i) {
			$real_path .= $path_arr[$i]."/";
		}
		$real_path .= $path_arr[$upper].".conf.php";
		if (isset(self::$confReg[$real_path])) {
			if (isset(self::$confReg[$real_path][$path_arr[$upper + 1]]))
				return self::$confReg[$real_path][$path_arr[$upper + 1]];
			else
				return null;
		}
		if (!file_exists($real_path)) {
			return null;
		} else {
			$CONF = array();
			require_once $real_path;
			self::$confReg[$real_path] = $CONF;
			if (isset($CONF[$path_arr[$upper + 1]]))
				return $CONF[$path_arr[$upper + 1]];
			else
				return null;
		}
	}
}
SoarConfig::init();
