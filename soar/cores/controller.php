<?php
/**
 * @file 	controller.php
 * @brief	定义了SoarPHP中所有Controller的基类
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2013-03-06
 */

/** 
 * CLASS Controller. Class Controller是所有Controller的基类。为每个Controller提供了DoIt函数执行实际的动作，以及默认如果未找到Action则执行404
 */
define('AUTH_CACHE_PATH' , dirname(dirname(__FILE__))."/__static__/auth_cache/" );
class Controller {
	private static $_requests_array_ = null;
	private function is_action_exist( $action ) {
		$methods = get_class_methods($this);
		foreach ($methods as $value) {
			if (strtolower($value) == strtolower($action))
				return true;
		}
		return false;
	}
	private function cli_print_404_msg() {
		global $argv;
		$output_str =  "[ERROR] Could not find an controller to handle the request";
		if (isset($argv[1])) {
			$output_str .= ":***".$argv[1]."***";
		}
		echo $output_str."\n";
	}
	/**
	 * @brief		提供了当路由解析出错时的默认动作。
	 * @detailed	此函数提供了当路由解析出错，比如当给出了一个不存在的controller或者不存在的action时的默认执行动作。即加载404页面返回给前端提示；
	 * @return		void
	 */
	public function action404() {
		if (defined("CALL_FROM") && CALL_FROM == "command_line") {
			$this->cli_print_404_msg();
		} else {
			SoarView::show('404.html');
		}
	}
	public function action_unauthorized() {
		exit("Unauthorized");
	}
	/**
	 * @brief		加载父类的成员函数，执行最终动作
	 * @detailed	加载父类成员函数，以执行最终动作，如果动作不存在，则执行默认动作
	 * @param 		string $action
	 * @return		void
	 */
	public function DoIt( $action ) {
		if ($this->is_action_exist($action)) {
			if ($this->is_authorized(get_class($this), $action)) {
				$this->$action();
			} else {
				$this->action_unauthorized();
			}
		} else {
			$this->action404();
		}
	}
	/**
	 * @brief		获取Http Get请求中的变量，等价于$_GET
	 * @detailed	获取Http Get请求中的变量，等价于$_GET，如果变量不存在，返回null
	 * @param 		string $param
	 * @return		string 
	 */
	public static function HttpGet($param) {
		return isset($_GET[$param])?$_GET[$param]:null;
	}
	/**
	 * @brief		获取Http Post请求中的变量，等价于$_POST
	 * @detailed	获取Http Post请求中的变量，等价于$_POST，如果变量不存在，返回null
	 * @param 		string $param
	 * @return		string
	 */
	public static function HttpPost($param) {
		return isset($_POST[$param])?$_POST[$param]:null;
	}
	/**
	 * @brief		获取请求中的变量，
	 * @detailed	获取请求中的变量，同时支持命令行和HTTP模式，如果变量不存在，返回null
	 * @param 		string $param
	 * @return		string
	 */
	public static function GetRequest($param) {
		if (!is_null(self::$_requests_array_)) {
			return isset(self::$_requests_array_[$param])?self::$_requests_array_[$param]:null;
		}
		if (defined("CALL_FROM") && CALL_FROM == "command_line") {
			global $argv;
			$upper = count($argv);
			self::$_requests_array_ = array();
			for($i = 2; $i < $upper; ++$i) {
				$tmp = explode('=',$argv[$i]);
				if (count($tmp) == 2) {
					self::$_requests_array_[$tmp[0]] = $tmp[1];
				} else {
					self::$_requests_array_[$i - 2] = $argv[$i];
				}
			}
		} else {
			self::$_requests_array_ = $_REQUEST;
		}
		return isset(self::$_requests_array_[$param])?self::$_requests_array_[$param]:null;
	}
	private static $default_crypt_key = "N+D8tYjO?E9uZkPa";
	
	
	/**
	 * @brief		获取请求中的变量，
	 * @detailed	获取请求中的变量，同时支持命令行和HTTP模式，如果变量不存在，返回null
	 * @param 		string $param
	 * @return		string
	 */
	public static function authorize(array $rights, $expire = 86400 , $restful_return = true) {
	    //rights example: array('admin','ulevel1',...,'uleveln');
		if (defined("CALL_FROM") && CALL_FROM == "command_line") {
			return true;
		}
		$expire += time();
		$auth_group = SoarConfig::get('auth.group');
		if ($auth_group === null) {
			throw new Exception("authorize group not set");
		}
		foreach($rights as $idx => $right) {
			if (!isset($auth_group[$right])) {
				unset($rights[$idx]);
			}
		}
		$plain_text = join(',', $rights);
		$crypt_key = SoarConfig::get('auth.authcode');
		if ($crypt_key === null) {
			$crypt_key = self::$default_crypt_key;
		}
		$rc4 = new RC4($crypt_key);
		$crypt_text_binary = $rc4->crypt($plain_text , true);
		$crypt_text_ascii = base64_encode($crypt_text_binary);
		
		//save auth on server
		$client_id = HttpUtilities::getClientIP()."-".HttpUtilities::getClientAgent();
		
		$auth_key = hash("sha256" , $client_id.$crypt_text_ascii);
		
		$cookie_db = SoarConfig::get('main.cookie_db');
		$content = json_encode(array(
		                'client_id' => $client_id,
		                'auth_rights' => $crypt_text_ascii,
		                'expire' => $expire
		            ));
		if (is_array($cookie_db) && isset($cookie_db['host']) && isset($cookie_db['port'])) {
		    //if we got a redis instance to save cookies, use that instead
		    try {
		        $redis = new Redis($cookie_db['host'], $cookie_db['port'] , 1);
		        $redis->Set("soarphp_authorization:".$auth_key, $content);
		        $redis->Expire("soarphp_authorization:".$auth_key, $expire - time());
		    } catch (Exception $e) {
				self::set_error("AUTH_COOKIE_DB_ERROR" , $e->getMessage());
				self::quit();
			}
		} else {
		    if (!($fp = @fopen(AUTH_CACHE_PATH.$auth_key , "w"))) {
		        throw new Exception("authorized failed: could not save auth files");
		        return false;
		    }

		    @fwrite($fp , $content);
		    fclose($fp);
		}
		$rtn = true;
		if ($restful_return) {
		    self::set_return('soarphp_authorization', $auth_key);
		} else {
		    $rtn = array(true , $auth_key);
		}
		setcookie('soarphp_authorization' , $auth_key , $expire);
		return $rtn;
	}
	public static function deauthorize() {
	    $auth_key = self::GetRequest('soarphp_authorization');
	    if ($auth_key == null) {
	        if (isset($_COOKIE['soarphp_authorization'])) {
	            $auth_key = $_COOKIE['soarphp_authorization'];
	        } else {
	            return true;
	        }
	    }
	    @unlink(AUTH_CACHE_PATH.$auth_key);
	    setcookie('soarphp_authorization' , null , time() - 1800);
	    $cookie_db = SoarConfig::get('main.cookie_db');
	    if (is_array($cookie_db) && isset($cookie_db['host']) && isset($cookie_db['port'])) {
	        try {
	            $redis = new Redis($cookie_db['host'], $cookie_db['port'] , 1);
	            $redis->Del("soarphp_authorization:".$auth_key);
	        } catch (Exception $e) {
	            self::set_error("AUTH_COOKIE_DB_ERROR" , $e->getMessage());
	            self::quit();
	        }
	    }
	}
	private function retrieve_authorize() {
		if (defined("CALL_FROM") && CALL_FROM == "command_line") {
			return PHP_INT_MAX;
		}
		$auth_group = SoarConfig::get('auth.group');
		if ($auth_group === null) {
			throw new Exception("authorize group not set");
		}
		$auth_key = self::GetRequest('soarphp_authorization');
		if ($auth_key == null) {
		    if (isset($_COOKIE['soarphp_authorization'])) {
		        $auth_key = $_COOKIE['soarphp_authorization'];
		    } else {
		        return 0;
		    }
		}
		$auth_content = null;
		$cookie_db = SoarConfig::get('main.cookie_db');
		if (is_array($cookie_db) && isset($cookie_db['host']) && isset($cookie_db['port'])) {
		    try {
		        $redis = new Redis($cookie_db['host'], $cookie_db['port'] , 1);
		        $auth_content = $redis->Get("soarphp_authorization:".$auth_key);
		    } catch (Exception $e) {
		        self::set_error("AUTH_COOKIE_DB_ERROR" , $e->getMessage());
		        self::quit();
		    }
		}
		if ($auth_content == null) {
		    if (!file_exists(AUTH_CACHE_PATH.$auth_key)) {
		        return 0;
		    }
		    $auth_content = file_get_contents(AUTH_CACHE_PATH.$auth_key);
		}
		$auth_content = json_decode($auth_content , true);
		$client_id = HttpUtilities::getClientIP()."-".HttpUtilities::getClientAgent();
		if (!is_array($auth_content) || 
		    !isset($auth_content['client_id']) || 
		    !isset($auth_content['auth_rights']) ||
		    !is_numeric($auth_content['expire'])) {
		    return 0;
		}
		if ($client_id != $auth_content['client_id']) {
		    return 0;
		}
		if (time() > $auth_content['expire']) {
		    return 0;
		}
		
		$crypt_text_ascii = $auth_content['auth_rights'];
		$crypt_text_binary = base64_decode($crypt_text_ascii);
		$crypt_key = SoarConfig::get('auth.authcode');
		if ($crypt_key === null) {
			$crypt_key = self::$default_crypt_key;
		}
		$rc4 = new RC4($crypt_key);
		$plain_text = $rc4->decrypt($crypt_text_binary , true);
		$rights_tmp = explode(',' , $plain_text);
		$rights = 0;
		foreach($rights_tmp as $right) {
			if (isset($auth_group[$right]))
				$rights |= $auth_group[$right];
		}
		return $rights;
	}
	public function is_authorized($controller , $module) {
		if (defined("CALL_FROM") && CALL_FROM == "command_line") {
			return true;
		}
		$controller_path = defined("CTRL_PATH")?CTRL_PATH:"";
		$controller = strtolower($controller);
		$module = strtolower($module);
		$ctl_auth_info = SoarConfig::get("auth.".$controller_path.$controller);
		if ($ctl_auth_info === null || !isset($ctl_auth_info[$module])) {
			return true;
		}
		$rights = $this->retrieve_authorize();
		return (intval($rights) & intval($ctl_auth_info[$module])) ? true:false;
	}
	private static $rtn = array('success'=>false , 'returns' => array(), 'errors' => array());
	public static function set_rtn_success() {
		self::$rtn['success'] = true;
	}
	public static function set_return($key , $value) {
		self::$rtn['success'] = true;
		self::$rtn['returns'][$key] = $value;
	}
	public static function set_error($code , $addition_msg = null) {
		self::$rtn['success'] = false;
		$msg = SoarConfig::get('error.'.$code);
		if ($msg == null) {
			$msg = "Undefined error";
		}
		self::$rtn['errors'] = array(
				'code' => $code,
				'msg' => $msg,
				);
		if ($addition_msg != null) {
			self::$rtn['errors']['addition_msg'] = $addition_msg;
		}
	}
	private static $quit_called = false;
	public static function quit() {
	    self::$quit_called = true;
		exit(json_encode(self::$rtn));
	}
	public static function set_err_and_quit($code , $addition_msg = null) {
		self::set_error($code , $addition_msg);
		self::quit();
	}
	public static function set_success_and_quit() {
		self::set_rtn_success();
		self::quit();
	}
	public static function set_rtn_and_quit($key , $value) {
		self::set_return($key, $value);
		self::quit();
	}
	public function __destruct() {
	    if (self::$quit_called) 
	        return;
	    if (is_array(self::$rtn['returns']) && count(self::$rtn['returns']))
	        self::quit();
	    if (is_array(self::$rtn['errors']) && count(self::$rtn['errors']))
	        self::quit();
	}
}