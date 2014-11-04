<?php
/**
 * @file 	soar_app.php
 * @brief	定义了SoarPHP的接口类SoarApp，每一个Project实际上就是SoarApp的一个实例化对象
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2013-03-06
 */
require_once 'cores/soarloader.php';

/**
 * Class SoarApp
 * @author House_Lee
 */
class SoarApp {
	private static $request_ctl_file_; ///< 每次请求的controller文件地址，如果路由无法解析，则该处为null
	private static $request_controller_;///< 每次请求的controller类名 ， 如果路由无法解析，则该处为null
	private static $request_action_;///<	每次请求的action名，如果路由无法解析，则该处为null
	private $default_router_;/* = site/index *///< 默认路由
	private function __clone() {}
	
	/**
	 * SoarApp构造函数
	 * @param string $default_router	提供默认的路由，即如果未指定路由时，默认加载的路由
	 * @param string $edition			开发版本，如果是调试则该参数应当显式传入debug，debug会有错误报告，默认为release，没有错误的详细描述
	 */
	public function __construct($default_router , $edition = 'release') {
		if($edition == 'debug') {
			define('SOAR_DEBUG' , 'DEBUG');
			if( !ini_get('display_errors') ) {
				ini_set('display_errors', 'On');
			}
			error_reporting(E_ALL);
			SoarConfig::set_path('dev');
		} else {
			define('SOAR_DEBUG', 'RELEASE');
			SoarConfig::set_path('release');
		}
		$this->default_router_ = $default_router;
		define('ROOT_PATH' , dirname(__FILE__));
	}
	
	/**
	 * 分析路由，由URL中名为r的参数提供
	 * @return boolean 成功返回true，否则返回false
	 */
	private function AnalyseRouter() {
		/*
		 * controller class的命名走驼峰，但是在调用的时候按照下划线区分每个单词
		 * 比如实际的controller为class UserCenter，
		 * 那么在调用的时候应当为user_center,且文件应当为user_center.controller.php
		 */
	    require_once 'cores/controller.php';
		$tobeana = "";
		define("CALL_FROM" , (php_sapi_name() == "cli")?"command_line":"web_server");
		if (CALL_FROM == "web_server") {
			//如果是来自web server的调用请求，则通过GET来获取路由信息
			if (isset($_GET['r'])) {
				//如果显示指定了r（router）参数，则分析r所指定的路由，否则分析default_router_
				$tobeana = $_GET['r'];
			} else {
				$tobeana = $this->default_router_;
			}
		} elseif (CALL_FROM == "command_line") {
			//否则如果是command line调用，则通过argv[1]来获取路由信息
			global $argv;
			if (isset($argv[1])) {
				$tobeana = $argv[1];
			} else {
				$tobeana = $this->default_router_;
			}
		}		
		$router = explode('/', $tobeana);
		if (count($router) != 2) {
			self::$request_ctl_file_ = self::$request_controller_ = self::$request_action_ = null;
			return false;
		}
		$ctl_route_arr = explode('.', $router[0]);
		$ctl_route = "";
		$upper = count($ctl_route_arr) - 1;
		$ctl_file_name = $ctl_route_arr[$upper];
		for($i = 0; $i != $upper; ++$i) {
			$ctl_route .= $ctl_route_arr[$i]."/";
		}
		self::$request_ctl_file_ = dirname(__FILE__)."/controllers/".$ctl_route.strtolower($ctl_file_name).".controller.php";
		if (!file_exists(self::$request_ctl_file_)) {
			self::$request_ctl_file_ = dirname(__FILE__)."/fastproto/".$ctl_route.strtolower($ctl_file_name).".prototype.php";
		}
		if (!file_exists(self::$request_ctl_file_)) {
			self::$request_ctl_file_ = self::$request_controller_ = self::$request_action_ = null;
			return false;
		}
		require_once self::$request_ctl_file_;
		define('CTRL_PATH', $ctl_route);
		$class_name = "";
		$tmp_arr = explode('_' , $ctl_file_name);
		foreach ( $tmp_arr as $word ) {
			$class_name .= ucfirst( strtolower($word) );
		}
		self::$request_controller_ = $class_name;
		self::$request_action_ = $router[1];
		return true;
	}
	
	/**
	 * 获取路由，返回一个三元组，包含路由的controller文件地址，controller名，以及action名
	 * @return array
	 */
	public static function GetRouter() {
		return array(
					 'ctl_file'=>self::$request_ctl_file_ ,
					 'controller'=>self::$request_controller_ ,
					 'action'=>self::$request_action_ ,
				);
	}
	/**
	 * 启动SoarApp
	 */
	public function Run() {
		if (!$this->AnalyseRouter()) {
// 			throw new Exception("Router Error");
			$controller = new Controller();
			$controller->action404();
		} else {
			$timezone = SoarConfig::get('main.timezone');
			if ($timezone === null)
				$timezone = 'Asia/Shanghai';
			date_default_timezone_set($timezone);
			$controller = new self::$request_controller_();
			$controller->DoIt( self::$request_action_ );
		}
	}
}