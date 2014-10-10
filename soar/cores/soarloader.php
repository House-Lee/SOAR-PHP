<?php
/**
 * @file	soarloader.php
 * @brief	实现所有基本类型的动态加载
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2013-03-05
 */

/**
 * class SoarLoader实现了对soar php中所有符合预置规格的类型的动态加载
 */
class SoarLoader {
	public static $loader;///< 用以保存SoarLoader实例的静态变量
	public static $include_path;
	
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
		self::$include_path = dirname( dirname( __FILE__ ) );
		
		spl_autoload_register(array($this , 'library'));//lib的自动加载
		spl_autoload_register(array($this , 'model'));//model的自动加载
		spl_autoload_register(array($this , 'controller'));//controller的自动加载
		spl_autoload_register(array($this , 'config'));//设定config的自动加载
		spl_autoload_register(array($this , 'view'));//设定view的自动加载
		spl_autoload_register(array($this , 'object'));//object的自动加载
		spl_autoload_register(array($this , 'general'));//通用类型的自动加载
	}
	
	/**
	 * 加载libs类，文件以".lib.php"结尾，存放于soar/libs/目录下
	 * @param string $class 类名
	 */
	public function library( $class ) {
		if( $class ) {
			set_include_path( self::$include_path."/libs/" );
			spl_autoload_extensions( ".lib.php" );
			spl_autoload( strtolower($class) );
		}
	}
	
	/**
	 * 加载models类，文件以".model.php"结尾，存放于soar/model/目录下
	 * @param string $class 类名
	 */
	public function model( $class ) {
		if( $class ) {
			require_once 'model.php';
			set_include_path( self::$include_path."/models/" );
			spl_autoload_extensions( ".model.php" );
			spl_autoload( strtolower($class) );
		}
	}
	
	/**
	 * 加载controllers类，文件以".controller.php"结尾，存放于soar/controller/目录下
	 * @param string $class
	 */
	public function controller( $class ) {
		if( $class ) {
// 			$filename_div = preg_split("/(?=[A-Z])/",$class);
// 			$filename = "";
// 			foreach($filename_div as $word) {
// 				$filename .= "_".$word;
// 			}
			$filename = trim((join("_", preg_split("/(?=[A-Z])/", $class))), '_');
			require_once 'controller.php';
			set_include_path( self::$include_path."/controllers/");
			spl_autoload_extensions( ".controller.php" );
			spl_autoload( strtolower($filename) );
		}
	}
	/**
	 * 加载全局配置类SoarConfig
	 * @param string $class
	 */
	public function config( $class ) {
		require_once 'soarconfig.php';
	}
	/**
	 * 加载全局视图类SoarView
	 * @param string $class
	 */
	public function view( $class ) {
		require_once 'soarview.php';
	}
	/**
	 * 加载objects类，文件以".obj.php"结尾，存放于soar/objects/目录下
	 * @param string $class
	 */
	public function object( $class ) {
		if( $class ) {
			require_once 'object.php';
			set_include_path( self::$include_path."/objects/");
			spl_autoload_extensions( ".obj.php" );
			spl_autoload( strtolower($class) );
		}
	}
	/**
	 * 加载general类，文件以".class.php"结尾，存放于soar/general_classes/目录下
	 * @param string $class
	 */
	public function general( $class ) {
		if( $class ) {
// 			require_once 'general.php';
			set_include_path( self::$include_path."/general_classes/");
			spl_autoload_extensions( ".class.php" );
			spl_autoload( strtolower($class) );
		}
	}	
}
SoarLoader::init();