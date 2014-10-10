<?php
require_once dirname( dirname( __FILE__ ) )."/libs/smarty/Smarty.class.php";

class SoarView {
	private static $driver_;
	private static $tpl_path_;
	private static $static_path_;
	private static $timezone;

	public static $instance;
	
	public static function init() {
		if(!self::$instance)
			self::$instance = new self();
		return self::$instance;
	}
	
	public function __construct() {
		self::$tpl_path_ = dirname( dirname( __FILE__ ) )."/tpl/";
		self::$static_path_ = dirname( dirname( __FILE__ ) )."/__static__/";
		self::$timezone = SoarConfig::get('main.timezone');
		if (self::$timezone === null)
			self::$timezone = 'Asia/Shanghai';

		$driver = new Smarty();
		$driver->setTemplateDir(self::$tpl_path_);
		$driver->setCompileDir(self::$static_path_."tpl_compiled/");
		$driver->setConfigDir(self::$static_path_."tpl_configs/");
		$driver->setCacheDir(self::$static_path_."tpl_cache/");
		if(defined('SOAR_DEBUG') && SOAR_DEBUG == 'DEBUG') {
			$driver->caching = false;
		} else {
			$driver->caching = true;
		}
		$driver->left_delimiter = '{#';
		$driver->right_delimiter = '#}';
		self::$driver_ = $driver;
	}
	
	public static function set($key , $value , $nocache = false) {
		if(!self::$driver_)
			self::init();
		self::$driver_->assign($key , $value , $nocache);
	}
	
	public static function show($tpl_file , $cache_id = null , $compile_id = null , $parent = null) {
		if(!self::$driver_)
			self::init();
		if (!trim($tpl_file))
			throw new Exception("no input file");
		if(strpos($tpl_file, ".html") === false)
			$tpl_file .= ".html";
		date_default_timezone_set(self::$timezone);
		self::$driver_->display($tpl_file , $cache_id , $compile_id , $parent);
	}
	
	public static function redirect($URL) {
		header('Location:'.$URL);
	}
}
SoarView::init();
