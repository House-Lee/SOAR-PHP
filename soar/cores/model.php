<?php
/**
 * @file 	model.php
 * @brief	定义了SoarPHP中所有Model的基类
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2013-03-06
 */

/**
 * CLASS Model. Class Model是所有Model的基类。封装了所有数据库操作的接口
 */
abstract class Model {
	private $mysql;///< 每个Model的mysql单独实例，以使得每个Model拥有独立的数据缓冲区，不至于多个Model之间出现数据共享冲突
	//TODO:增加insert和update时的类型检查
	private static $default_value = array(
										'int' => 0,
										'number'=> 0,
										'string'=> "null",
										'bool'=> 0,
										'time'=> "1970-01-01 00:00:00",
										);///< 定义了目前Model所支持的基本数据类型及其默认值。
	private static $default_operation = array(
										'=' => '=',
										'!=' => '<>',
										'<' => '<',
										'>' => '>',
										'>=' => '>=',
										'<=' => '<=',
										'match' => 'LIKE',///<全匹配
										'lmatch' => 'LIKE',///<左匹配
										'rmatch' => 'LIKE',///<右匹配
										'in' => 'IN',
										);///< 定义了目前Model所支持的基本条件比较操作及其对应的SQL操作符
	private static $allow_operation = array(
										'+='=>'+',
										'-='=>'-'
											);///< 定义了当前在update中允许的操作符
	private $data_buffer_;///<用来存放数据缓存，在update 或者 insert的时候系统自动优先在此读取数据,GetOne时数据会临时存放于此
	private $need_auto_update;
	
	/**
	 * 默认Model的构造函数
	 * 在每个Model实例化的时候自动为其创建一个mysql实例，数据库配置参数由config文件夹下的main.conf.php中的db元素所指定
	 */
	public function __construct() {
		$config = SoarConfig::get('main.db');
		if ($config === null) {
			throw new Exception("db not configure");
		}
		$config['charset'] = "utf8";//强制设定字符集为utf8，仅支持utf8字符集操作
		$this->mysql = new MySQL($config);
		$this->need_auto_update = false;
	}
	
	public function clear_buffer() {
		$this->data_buffer_ = array();
	}
	
	private function _filter_buffer() {
		foreach ($this->data_buffer_ as $key => &$value) {
			if (!isset($this->fields) || !is_array($this->fields) || !isset($this->fields[$key])) {
				throw new Exception('"'.$key.'" does not exists');
			}
			if (is_array($value)) {
				$real_value = &$value[1];
			} else {
				$real_value = &$value;
			}
			if (!@mb_check_encoding($real_value , "utf-8")) {
				throw new Exception("data charset must be encoded in utf8");
			}
			$real_value =  addslashes($real_value);
			$this->data_buffer_[$key] = $value;
			unset($real_value);
		}
	}
	private function _restore_single_buffer_(array &$buffer) {
		return true;
		//入库时已经转义，该处取值时不再转义，故abandon掉下面代码
		foreach($buffer as $key =>$value) {
			$buffer[$key] = stripslashes($value);
		}
		unset($buffer);
	}
	
	/**
	 * 保存一个key-value键值对到缓冲区以待接下来可能的insert或者update操作
	 * @param string $key	键
	 * @param string $value	值
	 * @throws Exception	当检测到子Model类未正确配置fields成员时，会抛出异常
	 * @return	void
	 */
	public function set($key , $value) {
		if (!isset($this->fields) || !is_array($this->fields) || !isset($this->fields[$key]))
			throw new Exception('"'.$key.'" does not exists');
		$this->data_buffer_[$key] = $value;
	}
	public function get_key($key) {
		if (!isset($this->fields) || !is_array($this->fields) || !isset($this->fields[$key]))
			throw new Exception('"'.$key.'" does not exists');
		return isset($this->data_buffer_[$key])?$this->data_buffer_[$key]:null;
	}
	
	/**
	 * 获取数据类型的默认值
	 * @param string $type
	 * @return multitype:number string |NULL 如果该类型存在则返回默认值，否则返回null
	 */
	public static function GetDefaultValue($type) {
		if (isset(self::$default_value[$type]))
			return self::$default_value[$type];
		else
			return null;
	}
	
	public static function IsResEmpty(array $res) {
		return (!count($res))? true : false;
	}
	
	/**
	 * 封装了SQL数据库的插入操作
	 * @param array $src	如果提供了此数组，会用此数组中的值更新data_buffer中的key-value，此数组不包含但data_buffer中包含的key-value不会失效
	 * @throws Exception	如果子类配置的参数，如fields,table,primary_key出错时，或者希望插入的key在数据库中不存在时，会抛出异常
	 * @return boolean		成功返回true，失败返回false
	 */
	public function Insert($src = array()) {
		if (is_array($src) && count($src) > 0) {
			foreach ($src as $key=>$value) {
				$this->set($key, $value);
			}
		}
		$this->_filter_buffer();
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) ) {
			throw new Exception('error parameter `table` or `fields`');
		}
		$this->need_auto_update = false;
		$query_str = "INSERT INTO `".$this->table."` SET ";
		foreach($this->fields as $key => $type) {
			if (isset($this->data_buffer_[$key])) {
				$query_str .= "`".$key."`='".$this->data_buffer_[$key]."',";
			} else {
				if (!isset(self::$default_value[$type]))
					throw new Exception("unrecognized type :".$type);
				$query_str .= "`".$key."`='".self::$default_value[$type]."',";
			}
		}
// 		$this->data_buffer_ = array();
// 		$log = new Log();
// 		$log->setLog($query_str , "insert_log");
		if ( $this->mysql->query(rtrim($query_str , ',')) ) {
			$this->set($this->primary_key, $this->GetLastInsertID());
			return true;
		} else {
			return false;
		}
	}
	
	public function GetLastInsertID() {
		return $this->mysql->get_last_insert_id();
	}
	
	/**
	 * 封装了SQL数据库的更新操作
	 * @param array $src			更新的数据源，如果提供了此数组，会用此数组中的值更新data_buffer中的key-value，此数组不包含但data_buffer中包含的key-value不会失效
	 * @param mix $conditions		更新的查询条件:
	 * 									i.	如果其类型是非数组类型，则将用主键（子类中用primary_key指定）来与其匹配进行搜寻;
	 * 									ii.	如果其为数组类型，则其中每个元素应当是一个三元组,组元素依次为：要匹配的列名，匹配操作符，匹配值。各三元组之间是与关系。例如：
	 * 										$conditions = array( array('id','!=','xyz'),array('key2','=','abc'));表示匹配列id不为xyz，且列key2等于abc的数据项
	 * @throws Exception			如果子类配置的参数，如fields,table,primary_key出错时，或者希望更新的key在数据库中不存在时，会抛出异常
	 * @return boolean				成功返回true，失败返回false
	 */
	public function Update($src = array() , $conditions = null) {
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) ) {
			throw new Exception('error parameter `table` or `fields`');
		}
		//procedure values tobe updated
		if (is_array($src) && count($src) > 0) {
			foreach ($src as $key=>$value) {
				$this->set($key, $value);
			}
		}
		if (!count($this->data_buffer_)) {
			throw new Exception("update condition not set");
		}
		$this->need_auto_update = false;
		$this->_filter_buffer();
		$query_str = "UPDATE `".$this->table."` SET ";
		foreach ($this->data_buffer_ as $key=>$value) {
			if (!is_array($value)) {
				$query_str .= "`".$key."`='".$value."',";
			} else {
				if (!isset(self::$allow_operation[$value[0]]))
					throw new Exception("operation:[".$value[0]."] not supported");
				if ($value[0] == "+=" && !$this->is_typeof_field_number($key)) {
					$query_str .= "`".$key."`=CONCAT(`".$key."`,'".$value[1]."'),";
				} else {
					$query_str .= "`".$key."`=`".$key."`".self::$allow_operation[$value[0]]."'".$value[1]."',";
				}
			}
		}
		$query_str = rtrim($query_str , ',');
		//procedure query conditions
		$cond_str = $this->_generate_condition_string($conditions);
		if ($cond_str !== false) {
			$query_str .= $cond_str;
		}
// 		$this->data_buffer_ = array();
		if ( $this->mysql->query( $query_str )) {
			return true;
		} else {
			return false;
		}
	}
	private function is_typeof_field_number($key) {
		return ($this->fields[$key] == "int" || $this->fields[$key] == "number");
	}
	private function _maths_($method , $conditions) {
		$this->LockItem($conditions);
		$query_str = "UPDATE `".$this->table."` SET ";
		
		foreach($this->data_buffer_ as $key=>$value) {
			if (!$this->is_typeof_field_number($key)) {
				throw new Exception("`".$key."` is not a number");
			}
			if (!is_numeric($value)) {
				throw new Exception("value type not numeric");
			}
			$query_str .= "`".$key."`=`".$key."`".$method."'".$value."',";
		}
		$query_str = rtrim($query_str , ',');
		//procedure query conditions
		$cond_str = $this->_generate_condition_string($conditions);
		if ($cond_str !== false) {
			$query_str .= $cond_str;
		}
//debug start
// 		$log = new Log();
// 		$log->setLog($query_str);
//debug end
		$this->data_buffer_ = array();
		if ( $this->mysql->query( $query_str )) {
			$res = true;
		} else {
			$res = false;
		}
		$this->UnlockItem($conditions);
		return $res;
	}
	/**
	 * 封装了SQL数据库的数学增量运算操作，只可用于数字类型(int , number)
	 * @param array $column_value_array			更新的数据源，如果提供了此数组，会用此数组中的值更新data_buffer中的key-value，此数组不包含但data_buffer中包含的key-value不会失效
	 * @param mix $conditions		更新的查询条件:
	 * 									i.	如果其类型是非数组类型，则将用主键（子类中用primary_key指定）来与其匹配进行搜寻;
	 * 									ii.	如果其为数组类型，则其中每个元素应当是一个三元组,组元素依次为：要匹配的列名，匹配操作符，匹配值。各三元组之间是与关系。例如：
	 * 										$conditions = array( array('id','!=','xyz'),array('key2','=','abc'));表示匹配列id不为xyz，且列key2等于abc的数据项
	 * @throws Exception			如果子类配置的参数，如fields,table,primary_key出错时，或者希望更新的key在数据库中不存在时，会抛出异常
	 * @return boolean				成功返回true，失败返回false
	 */
	public function INCR($condition = array() , $column_value_array = array()) {
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) ) {
			throw new Exception('error parameter `table` or `fields`');
		}
		if (is_array($column_value_array) && count($column_value_array) > 0) {
			foreach ($column_value_array as $key=>$value) {
				$this->set($key, $value);
			}
		}
		return $this->_maths_('+' ,$condition);
	}
	/**
	 * 封装了SQL数据库的数学减量运算操作，只可用于数字类型(int , number)
	 * @param array $column_value_array			更新的数据源，如果提供了此数组，会用此数组中的值更新data_buffer中的key-value，此数组不包含但data_buffer中包含的key-value不会失效
	 * @param mix $conditions		更新的查询条件:
	 * 									i.	如果其类型是非数组类型，则将用主键（子类中用primary_key指定）来与其匹配进行搜寻;
	 * 									ii.	如果其为数组类型，则其中每个元素应当是一个三元组,组元素依次为：要匹配的列名，匹配操作符，匹配值。各三元组之间是与关系。例如：
	 * 										$conditions = array( array('id','!=','xyz'),array('key2','=','abc'));表示匹配列id不为xyz，且列key2等于abc的数据项
	 * @throws Exception			如果子类配置的参数，如fields,table,primary_key出错时，或者希望更新的key在数据库中不存在时，会抛出异常
	 * @return boolean				成功返回true，失败返回false
	 */
	public function DECR($condition = array() , $column_value_array = array()) {
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) ) {
			throw new Exception('error parameter `table` or `fields`');
		}
		if (is_array($column_value_array) && count($column_value_array) > 0) {
			foreach ($column_value_array as $key=>$value) {
				$this->set($key, $value);
			}
		}
		return $this->_maths_( '-' ,$condition);
	}
	
	/**
	 * 获取一条记录
	 * @param string $primary_id	希望获取的记录的主码
	 * @param array $columns		希望获取的列，如果不指定，则获取全部列。举例：$columns = array('col_1','col_2')表明获取列col_1,col_2的值；
	 * @throws Exception			如果子类配置的参数，如fields,table,primary_key出错时，或者希望获取的key在数据库中不存在时，会抛出异常
	 * @return multitype:array		如果找到，则返回组装好的该行数据。否则返回空数组。
	 */
	public function GetOne ( $primary_id  , $columns = array()) {
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `fields` or `primary_key`');
		}
		if (!mb_check_encoding($primary_id , "utf-8")) {
			throw new Exception("data not utf8");
		}
		$primary_id = addslashes($primary_id);
		$query_str = "SELECT ";
		if (is_array($columns) && count($columns) > 0) {
			foreach ($columns as $col) {
				if (!isset($this->fields[$col]))
					throw new Exception('key not found:'.$col);
				$query_str .= "`".$col."`,";
			}
			$query_str = rtrim($query_str , ',');
		} else {
			$query_str .= "*";
		}
		$query_str .= " FROM `".$this->table."` WHERE `".$this->primary_key."`='".$primary_id."'";
		$rtn = array();
		if( ($res = $this->mysql->query($query_str)) ) {
			$res_arr = $this->mysql->fetch_array();
			if (!is_array($res_arr)) {
				$this->mysql->free();
				return $rtn;
			}
			foreach($res_arr as $key => $value) {
				if (isset($this->fields[$key]))
					$rtn[$key] = $value;
			}
			$this->_restore_single_buffer_($rtn);
		}
		$this->mysql->free();
		$this->data_buffer_ = $rtn;
		$this->set($this->primary_key, $primary_id);
		return $rtn;
	}
	
	/**
	 * 获取一条或多条记录
	 * @param mix $conditions	搜寻的查询条件，具体解释参考Update中的conditions参数
	 * @param array $columns	希望获取的列。参考GetOne中的columns参数
	 * @throws Exception		如果子类配置的参数，如fields,table,primary_key出错时，或者希望获取的key在数据库中不存在时，会抛出异常
	 * @return Ambigous <multitype:array, multitype:unknown >|multitype:	如果成功则返回包含一行或多行数据的二维数组，否则返回空数组
	 */
	public function Get($conditions = array(),$columns = array() , $start_offset = 0 , $rtn_duration = null) {
		if (!is_array($conditions)) {
			return $this->GetOne($conditions , $columns);
		}
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `fields` or `primary_key`');
		}
		$query_str = "SELECT ";
		if (is_array($columns) && count($columns) > 0) {
			foreach ($columns as $col) {
				if (!isset($this->fields[$col]))
					throw new Exception('key not found:'.$col);
				$query_str .= "`".$col."`,";
			}
			$query_str = rtrim($query_str , ',');
		} else {
			$query_str .= "*";
		}
		$query_str .= " FROM `".$this->table."`";
		$cond_str = $this->_generate_condition_string($conditions);
		if ($cond_str !== false) {
			$query_str .= $cond_str;
		}
		if (!is_null($rtn_duration)) {
			if (!is_numeric($start_offset)) {
				throw new Exception("start offset not int");
			}
			$query_str .= " LIMIT ".$start_offset.",".$rtn_duration;
		}
		$rtn = array();
		if( ($res = $this->mysql->query($query_str)) ) {
			$res_arr = $this->mysql->get_all(); 
			if (!is_array($res_arr)) {
				$this->mysql->free();
				return $rtn;
			}
			$upper = count($res_arr);
			for ($i = 0; $i != $upper; ++$i) {
				foreach($res_arr[$i] as $key => $value) {
					if (isset($this->fields[$key]))
						$rtn[$i][$key] = $value;
				}
				$this->_restore_single_buffer_($rtn[$i]);
			}
		}
		$this->mysql->free();
		$this->clear_buffer();
		return $rtn;
	}
	
	public function GetCount(array $conditions = array()) {
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `fields` or `primary_key`');
		}
		$query_str = "SELECT count(*) FROM `".$this->table."`";
		$cond_str = $this->_generate_condition_string($conditions);
		if ($cond_str !== false) {
			$query_str .= $cond_str;
		}
		$rtn = 0;
		if( ($res = $this->mysql->query($query_str)) ) {
			$res_arr = $this->mysql->get_all();
			if (!is_array($res_arr)) {
				$this->mysql->free();
				return $rtn;
			}
			$rtn = $res_arr[0][0];
		}
		$this->mysql->free();
		return $rtn;
	}
	public function GetMaxId(array $conditions = array()) {
		//这个函数有一些主码类型Specific，可能限定在特定的数据库类型，比如MySQL或者MongoDB,考虑是否应当融合在Model里，也许有更合适的地方放置此函数
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `fields` or `primary_key`');
		}
		$query_str = "SELECT MAX(`".$this->primary_key."`) FROM `".$this->table."`";
		$cond_str = $this->_generate_condition_string($conditions);
		if ($cond_str !== false)
			$query_str .= $cond_str;
		$rtn = null;
		if( ($res = $this->mysql->query($query_str)) ) {
			$res_arr = $this->mysql->get_all();
			if (!is_array($res_arr)) {
				$this->mysql->free();
				return $rtn;
			}
			$rtn = $res_arr[0][0];
		}
		$this->mysql->free();
		return $rtn;
	}
	/**
	 * 删除一条数据
	 * @param string $primary_id	欲删除的主码
	 * @throws Exception			如果子类配置的参数，如fields,table,primary_key出错时，或者希望获取的key在数据库中不存在时，会抛出异常
	 * @return boolean				成功返回true，失败返回false
	 */
	public function DeleteOne( $primary_id ) {
		if (!isset($this->table) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `primary_key`');
		}
		if (!mb_check_encoding($primary_id , "utf-8")) {
			throw new Exception("data not utf8");
		}
		$this->clear_buffer();
		$primary_id = addslashes($primary_id);
		$query_str = "DELETE FROM `".$this->table."` WHERE `".$this->primary_key."`='".$primary_id."'";
		if ( $this->mysql->query( $query_str ))
			return true;
		else
			return false;
	}
	
	/**
	 * 删除一条或多条数据
	 * @param mix $conditions	搜寻的查询条件，具体解释参考Update中的conditions参数
	 * @throws Exception		如果子类配置的参数，如fields,table,primary_key出错时，或者希望获取的key在数据库中不存在时，会抛出异常
	 * @return boolean			成功返回true，失败返回false
	 */
	public function Delete($conditions = null) {
		/*
		 * $conditions = array( array('id','!=','xyz'),array('key2','=','abc'));
		 */
		if (!isset($this->table) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `primary_key`');
		}
		$this->clear_buffer();
		$query_str = "DELETE FROM `".$this->table."` ";
		$cond_str = $this->_generate_condition_string($conditions);
		if ($cond_str === false) {
			//不允许无条件truncate表
			return false;
		}
		$query_str .= $cond_str;
		if ($this->mysql->query($query_str))
			return true;
		else
			return false;
	}
	//Note:不要在同一个会话中嵌套使用多把锁，否则下一次加锁会隐式释放上一次的锁，导致数据冲突，或有可能导致无法多表查询。
	//将来在优化框架设计时可以重构这块以提供更强大的异步的数据安全支持
	public function LockTableR() {
		if (!isset($this->table) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `primary_key`');
		}
		if($this->mysql->query("LOCK TABLES ".$this->table." READ"))
			return true;
		return false;
	}
	public function LockTableW() {
		if (!isset($this->table) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `primary_key`');
		}
		if($this->mysql->query("LOCK TABLES ".$this->table." WRITE"))
			return true;
		return false;
	}
	public function UnlockTable() {
		if($this->mysql->query("UNLOCK TABLES"))
			return true;
		return false;
	}
	public function LockItem($condition) {
		if (!isset($this->table) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `primary_key`');
		}
		$cond_str = $this->_generate_condition_string($condition);
		if ($cond_str === false) {
			throw new Exception('lock item failed. could not specify the items to be locked');
		}
		$sql = "SELECT `".$this->primary_key."` FROM `".$this->table."` ".$cond_str." FOR UPDATE";
		$this->mysql->start_transaction();
		$this->mysql->query( $sql );
	}
	public function UnlockItem($condition , $rollback = false) {
		if ($rollback) {
			$this->mysql->rollback();
		} else {
			$this->mysql->commit();
		}
	}
	private function _generate_condition_string($conditions) {
		$have_cond = false;
		if (!is_array($conditions)) {
			if (!isset($this->primary_key))
				throw new Exception("error parameter `primary_key`");
			if (is_null($conditions)) {
				$conditions = $this->get_key($this->primary_key);
				if (is_null($conditions)) {
					throw new Exception("no item specific");
				}
			}
			if (!mb_check_encoding($conditions , "utf-8")) {
				throw new Exception("data not in utf-8");
			}
			$cond_str = " WHERE `".$this->primary_key."`='".addslashes($conditions)."'";
			$have_cond = true;
		} elseif (count($conditions) > 0) {
			$cond_str = " WHERE ";
			foreach($conditions as $value) {
				if (!is_array($value) || count($value) != 3)
					continue;
				if ( !isset($this->fields[$value[0]]) )
					throw new Exception("error: no key call `".$value[0]."` in table `".$this->table."`");
				if ( !isset( self::$default_operation[ $value[1] ] ) )
					throw new Exception("error: not support operation:".$value[1]);
// 				$cond_str .= "`".$value[0]."`".self::$default_operation[ $value[1] ]."'".$value[2]."' AND";
				if ($value[1] != "in") {
					if (!mb_check_encoding($value[2] , "utf-8")) {
						throw new Exception("data not in utf-8");
					}
					$value[2] = addslashes($value[2]);
					if($value[1] == 'match') {
						$cond_str .= "`".$value[0]."`".self::$default_operation[ $value[1] ]." '%".$value[2]."%' AND";
					} elseif ($value[1] == 'lmatch') {
						$cond_str .= "`".$value[0]."`".self::$default_operation[ $value[1] ]." '".$value[2]."%' AND";
					} elseif ($value[1] == 'rmatch') {
						$cond_str .= "`".$value[0]."`".self::$default_operation[ $value[1] ]." '%".$value[2]."' AND";
					} else {
						$cond_str .= "`".$value[0]."`".self::$default_operation[ $value[1] ]."'".$value[2]."' AND";
					}
				} else {
					if (!is_array($value[2]))
						throw new Exception("error: \"in\" operator must be followed by array");
					if (!count($value[2])) {
						throw new Exception("error: \"in\" set must contains at least one element");
					}
					$in_cond = "";
					foreach($value[2] as $item) {
						if (!mb_check_encoding($item , "utf-8")) {
							throw new Exception("data not in utf-8");
						}
						$item = addslashes($item);
						$in_cond .= "'".$item."',";
					}
					$in_cond = rtrim($in_cond , ",");
					$cond_str .= "`".$value[0]."`".self::$default_operation[ $value[1] ]." (".$in_cond.") AND";
				}
				$have_cond = true;
			}
			$cond_str = substr($cond_str , 0 , -4);
		}
		if ($have_cond)
			return $cond_str;
		return false;
	}
	
	public function __destruct() {
	    //check if auto update is needed
	    if ($this->need_auto_update) {
	        if (isset($this->primary_key) &&
	            is_array($this->fields) && 
	            isset($this->fields[$this->primary_key])) {
	            
	            $this->Update();
	        } else {
	            $this->Insert();
	        }
	    }
	}
}

