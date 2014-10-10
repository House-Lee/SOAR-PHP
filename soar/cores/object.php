<?php
/**
 * @file 	object.php
 * @brief	定义了SoarPHP中所有Object的基类
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2013-03-06
 */

/**
 * CLASS Object. Class Objec是所有Model的基类。暂未提供操作接口，留待以后扩充
 */
abstract class Object {
	//TODO:可考虑把Object的数据Cache起来
	
	
	public static function IsClosure($var) {
		return (is_object($var) && ($var instanceof Closure));
	}
	
	private $init = false;
	private $return_success = false;
	private $return_info = null;
	private $error_info = null;
	private function init_return() {
		if ($this->init != true) {
			$this->error_info = null;
			$this->return_success = false;
			$this->return_info = array();
			$this->init = true;
		}
	}
	public function SetErrorCode($code , $addition_msg = "") {
		$this->init_return();
		$basic_msg = SoarConfig::get('error.'.$code);
		if ($basic_msg === null) {
			$basic_msg = "";
		}
		$this->error_info = array('code' => $code , 'msg' => $basic_msg , 'addition' => $addition_msg);
		return true;
	}
	public function GetErrorCode() {
		$this->init_return();
		if ($this->error_info == null) {
			return $this->error_info;
		}
		return $this->error_info['code'];
	}
	public function GetErrorMsg() {
		$this->init_return();
		if ($this->error_info == null) {
			return $this->error_info;
		}
		return $this->error_info['msg'];
	}
	public function GetErrorAdditionMsg() {
		$this->init_return();
		if ($this->error_info == null) {
			return $this->error_info;
		}
		if ($this->error_info['addition'] == "")
			return null;
		return $this->error_info['addition'];
	}
	public function SetReturnSuccess() {
		$this->init_return();
		$this->return_success = true;
	}
	public function SetReturn($key , $value) {
		$this->init_return();
		$this->return_success = true;
		$this->return_info[$key] = $value;
	}
	public function IsSuccess() {
		$this->init_return();
		return $this->return_success;
	}
	public function GetReturn($key = null) {
		$this->init_return();
		if ($key == null) {
			if (!count($this->return_info)) {
				return null;
			}
			foreach ($this->return_info as $idx => $value) {
				$key = $idx;
				break;
			}
		}
		if (!$this->return_success || !isset($this->return_info[$key])) {
			return null;
		}
		return $this->return_info[$key];
	}
	
	
}