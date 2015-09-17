<?php
use Predis\Connection\RedisCluster;

/**
 * @file 	model.php
 * @brief	Defines the Model class from which all the models inherit.
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2013-03-06
 */

require_once dirname(dirname(__FILE__))."/libs/redis.helper.php";

class AutoCacheConfig {
    public $enabled;
    public $host_list;
    public $expire;
    public $prefix;
    public $cache_retrieve_result;
    public $retr_res_expire;
    public $cache_mode;
    
    public function __construct($cfg) {
        $this->enabled = true;
        if (!$cfg || !isset($cfg['host_list'])) {
            //auto cache has been disabled
            $this->enabled = false;
            return;
        }
        $this->host_list = $cfg['host_list'];
        $this->cache_mode = ClusterRedis::Mode_Cluster;
        foreach ($this->host_list as $v) {
            if (is_array($v) && isset($v[1]) && $v[1] == "master") {
                $this->cache_mode = ClusterRedis::Mode_MasterSlave;
            }
        }
        $this->expire = 86400;
        if (isset($cfg['expire'])) {
            if ($cfg['expire'] == "never"){
                $this->expire = -1;
            } else if (is_numeric($cfg['expire']) && $cfg['expire'] > 0) {
                $this->expire = $cfg['expire'];
            }
        }
        $this->prefix = "";
        if (isset($cfg['prefix'])) {
            $this->prefix = $cfg['prefix'];
        }
        if (defined('SOAR_DEBUG') && SOAR_DEBUG == 'DEBUG') {
            $this->cache_retrieve_result = false;
            $this->retr_res_expire = 0;//0s
        } else {
            $this->cache_retrieve_result = true;
            $this->retr_res_expire = 30;//30s
        }
        
    }
}
/**
 * CLASS Model. Class Model是所有Model的基类。封装了所有数据库操作的接口
 */
abstract class Model {
	private $mysql;///< 每个Model的mysql单独实例，以使得每个Model拥有独立的数据缓冲区，不至于多个Model之间出现数据共享冲突
	private static $auto_cache_config_ = null;///< the config of auto cache
	                                         /*
	                                          * config example:
	                                          * [
	                                          * 'host_list' => ["10.1.0.1:17016" , ["10.1.0.2:17016", "alias"]],
	                                          * 'expire' => 86400 or "never",
	                                          * 'prefix' => 'proj_name'
	                                          * ]
	                                          */
	private static $auto_cache_conn = null;
	//TODO: implement auto cache
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
	protected $need_auto_update;
	
	public static function init() {
	    //initialize auto cache configuration if needed
	    if (self::$auto_cache_conn) {
	        //if successfully created the auto cache connection, quit the constructor directly.
	        return;
	    }
	    $config = SoarConfig::get('main.auto_cache');
	    self::$auto_cache_config_ = new AutoCacheConfig($config);
	    if (!self::$auto_cache_config_->enabled) {
	        return;
	    }
	    self::$auto_cache_conn = new ClusterRedis(self::$auto_cache_config_->host_list , self::$auto_cache_config_->cache_mode);
	}
	
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
	public static function DisableAutoCache() {
	    self::$auto_cache_config_->enabled = false;
	    self::$auto_cache_conn = null;
	}
	public static function DisableRetrieveResAutoCache() {
	    self::$auto_cache_config_->cache_retrieve_result = false;
	}
	
	public static function EnableRetrieveResAutoCache() {
	    self::$auto_cache_config_->cache_retrieve_result = true;
	}
	
	public static function SetResultListCacheTime($time) {
	    if (is_numeric($time) && $time > 0) {
	        self::$auto_cache_config_->retr_res_expire = $time;
	    }
	}
	private function _gen_cache_id_($table_name , $main_id) {
	    if (is_numeric($main_id)) {
	        return "soarphp_autocache:".self::$auto_cache_config_->prefix.":".$table_name.":".$main_id;
	    } else {
	        $main_id = md5(str_replace(" ", "^", $main_id));
	        return "soarphp_autocache:".self::$auto_cache_config_->prefix.":".$table_name.":sql:".$main_id;
	    }
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
	    //Test the code here
// 		return true;
// 		//入库时已经转义，该处取值时不再转义，故abandon掉下面代码
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
				$this->data_buffer_[$key] = self::$default_value[$type];
				$query_str .= "`".$key."`='".self::$default_value[$type]."',";
			}
		}
// 		$this->data_buffer_ = array();
// 		$log = new Log();
// 		$log->setLog($query_str , "insert_log");
		if ( $this->mysql->query(rtrim($query_str , ',')) ) {
			$this->set($this->primary_key, $this->GetLastInsertID());
			//Auto Cache if needed
			if (self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
			    $key = $this->_gen_cache_id_($this->table, $this->get_key($this->primary_key));
			    if (self::$auto_cache_config_->expire != -1) {
			        self::$auto_cache_conn->SetEx($key , json_encode($this->data_buffer_) , self::$auto_cache_config_->expire);
			    } else {
			        self::$auto_cache_conn->Set($key , json_encode($this->data_buffer_));
			    }
			}
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
				if ($value[0] == "+=" && $this->is_typeof_field_number($key)) {
					$query_str .= "`".$key."`=`".$key."`".self::$allow_operation[$value[0]]."'".$value[1]."',";
				} else {
				    $query_str .= "`".$key."`=CONCAT(`".$key."`,'".$value[1]."'),";
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
		    //Auto Cache if needed
		    if (self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
		        //first fetch all the affected ids
		        $sql = "select `".$this->primary_key."` from `".$this->table."`";
		        if ($cond_str !== false) {
		            $sql .= $cond_str;
		        }
		        if ($this->mysql->query($sql)){
		            $ids = $this->mysql->get_all();
		            if (is_array($ids)) {
		                foreach ($ids as $t) {
		                    $cache_key = $this->_gen_cache_id_($this->table, $t[$this->primary_key]);
		                    if (($buf = self::$auto_cache_conn->Get($cache_key)) != null) {
		                        $val = json_decode($buf , true);
		                        foreach ($this->data_buffer_ as $key=>$value) {
		                            if (!is_array($value)) {
		                                $val[$key] = $value;
		                            } else {
		                                if ($value[0] == "+=" && $this->is_typeof_field_number($key)) {
		                                    isset($val[$key]) && ($val[$key] += $value);
		                                } else {
		                                   isset($val[$key]) && ($val[$key] .= $value);
		                                }
		                            }
		                        }
		                        if (self::$auto_cache_config_->expire != -1) {
		                            self::$auto_cache_conn->SetEx($cache_key, json_encode($val) , self::$auto_cache_config_->expire);
		                        } else {
		                            self::$auto_cache_conn->Set($cache_key, json_encode($val));
		                        }
		                    }
		                }//end foreach ($ids as $t)
		            }//end if(is_array(ids))
		        }//end if ($this->mysql->query($sql))
		        $this->mysql->free();
		    }
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
		if ( $this->mysql->query( $query_str )) {
		    //Auto Cache if needed
		    if (self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
		        //first fetch all the affected ids
		        $sql = "select `".$this->primary_key."` from `".$this->table."`";
		        if ($cond_str !== false) {
		            $sql .= $cond_str;
		        }
		        if ($this->mysql->query($sql)){
		            $ids = $this->mysql->get_all();
		            if (is_array($ids)) {
		                foreach ($ids as $t) {
		                    $cache_key = $this->_gen_cache_id_($this->table, $t[$this->primary_key]);
		                    if (($buf = self::$auto_cache_conn->Get($cache_key)) != null) {
		                        $val = json_decode($buf , true);
		                        foreach ($this->data_buffer_ as $key=>$value) {
		                            if ($method == "+") {
		                                isset($val[$key]) && ($val[$key] += $value);
		                            } else {
		                                isset($val[$key]) && ($val[$key] -= $value);
		                            }
		                        }
		                        if (self::$auto_cache_config_->expire != -1) {
		                            self::$auto_cache_conn->SetEx($cache_key, json_encode($val) , self::$auto_cache_config_->expire);
		                        } else {
		                            self::$auto_cache_conn->Set($cache_key, json_encode($val));
		                        }
		                    }
		                }//end foreach ($ids as $t)
		            }//end if(is_array(ids))
		        }//end if ($this->mysql->query($sql))
		        $this->mysql->free();
		    }
			$res = true;
		} else {
			$res = false;
		}
		$this->data_buffer_ = array();
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
		    $this->clear_buffer();
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
		    $this->clear_buffer();
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
	public function GetOne ( $primary_id  , array $columns = array()) {
		if (!isset($this->table) || !isset($this->fields) || !is_array($this->fields) || !isset($this->primary_key)) {
			throw new Exception('error parameter `table` or `fields` or `primary_key`');
		}
		if (!mb_check_encoding($primary_id , "utf-8")) {
			throw new Exception("data not utf8");
		}
		$primary_id = addslashes($primary_id);
		$query_str = "SELECT ";
		$cache_key = $this->_gen_cache_id_($this->table, $primary_id);
		if (self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
		    //Try to retrieve from cache first
		    $res = self::$auto_cache_conn->Get($cache_key);
		    if ($res) {
		        if (self::$auto_cache_config_->expire != -1) {
		            self::$auto_cache_conn->Expire($cache_key, self::$auto_cache_config_->expire);
		        }
		        $rtn = $this->filter_row(json_decode($res , true) , $columns);
		        if ($rtn == -1) {
		            goto recache;
		        }
		        $this->_restore_single_buffer_($rtn);
		        $this->data_buffer_ = $rtn;
		        echo "Single Get From Cache\n";
		        return $rtn;
		    }
		    recache:
		    $query_str .= "*";
		} else {
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
		}
		$query_str .= " FROM `".$this->table."` WHERE `".$this->primary_key."`='".$primary_id."'";
		$rtn = array();
		$res = $this->mysql->query($query_str);
		if( ($res) ) {
			$res_arr = $this->mysql->fetch_array();
			if (!is_array($res_arr)) {
				$this->mysql->free();
				return $rtn;
			}
			foreach($res_arr as $key => $value) {
				if (isset($this->fields[$key]))
					$rtn[$key] = $value;
			}
			if (self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
			    if (self::$auto_cache_config_->expire != -1) {
			        self::$auto_cache_conn->SetEx($cache_key , json_encode($rtn) , self::$auto_cache_config_->expire);
			    } else {
			        self::$auto_cache_conn->Set($cache_key , json_encode($rtn));
			    }
			    $rtn = $this->filter_row($rtn, $columns);
			}
			$this->_restore_single_buffer_($rtn);
			$this->data_buffer_ = $rtn;
			$this->set($this->primary_key, $primary_id);
			$this->mysql->free();
			//echo "Single Get From DB\n";
			return $rtn;
		}
		return null;
	}
	
	private function filter_row($src , array $cols) {
	    if (!count($cols))
	        return $src;
	    $rtn = [];
	    foreach($cols as $c) {
	        if (!isset($src[$c])) {
	            return -1;
	        }
	        $rtn[$c] = $src[$c];
	    }
	    return $rtn;
	}
	
	/*******__cmp functions*******/
	private function __cmp_eq($a , $b) {
	    return $a == $b;
	}
	private function __cmp_neq($a , $b) {
	    return $a != $b;
	}
	private function __cmp_lt($a , $b) {
	    return $a < $b;
	}
	private function __cmp_gt($a , $b) {
	    return $a > $b;
	}
	private function __cmp_ge($a , $b) {
	    return $a >= $b;
	}
	private function __cmp_le($a , $b) {
	    return $a <= $b;
	}
	private function __cmp_match($a , $b) {
	    return !(strpos($b, $a) === false);
	}
	private function __cmp_lmatch($a , $b) {
	    return $a == "" || strpos($b, $a)===0;
	}
	private function __cmp_rmatch($a , $b) {
	    $length = strlen($a);
	    if ($length == 0) {
	        return true;
	    }
	    return (substr($b, -$length) === $a);
	}
	private function __cmp_inarray($a , array $b) {
	    return !(array_search($a, $b) === false);
	}
	/*******__cmp functions end*******/
	private function test_condition($cached_v , $operator , $cond_v) {
	    /*
	     cached: 5
	     cond v <= 6
	     
	     cached: 5
	     cond v in [5 , 4 , 3]
	     
	     cached: abc
	     cond v rmatch aoeabc
	     */
	    $op_map = [
	    '=' => '__cmp_eq',
	    '!=' => '__cmp_neq',
	    '<' => '__cmp_lt',
	    '>' => '__cmp_gt',
	    '>=' => '__cmp_ge',
	    '<=' => '__cmp_le',
	    'match' => '__cmp_match',///<全匹配
	    'lmatch' => '__cmp_lmatch',///<左匹配
	    'rmatch' => '__cmp_rmatch',///<右匹配
	    'in' => '__cmp_inarray',
	    ];
	    if (!isset($op_map[$operator])) {
	        throw new Exception("operator not supported");
	    }
	    return $this->{$op_map[$operator]}($cached_v , $cond_v);
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
		$cond_str = $this->_generate_condition_string($conditions);
		$query_str = "SELECT ";
		if (self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
		    //Try to retrieve from cache first.
		    if ($cond_str !== false && self::$auto_cache_config_->cache_retrieve_result) {
		        $cache_key = $this->_gen_cache_id_($this->table, $cond_str);
		        $search_idx = self::$auto_cache_conn->Get($cache_key);
		        if (!$search_idx) {
		            goto recache;
		        }
		        $search_idx = json_decode($search_idx , true);
		        $id_list = explode(',', $search_idx["ids"]);
		        $rtn = [];
		        foreach($id_list as $id) {
		            $item_key = $this->_gen_cache_id_($this->table, $id);
		            $tmp = self::$auto_cache_conn->Get($item_key);
		            if (!$tmp) {
		                //cache no longer available
		                goto recache;
		            }
		            $tmp = json_decode($tmp , true);
		            foreach ($search_idx["conds"] as $cond) {
		                if (count($cond) != 3 || !isset($tmp[$cond[0]])) {
		                    goto recache;
		                }
		                if (!$this->test_condition($tmp[$cond[0]] , $cond[1] , $cond[2])) {
// 		                    if (!is_array($cond[2]))
// 		                        echo "condition isn't satisfied:".$tmp[$cond[0]].$cond[1].$cond[2]."\n";
// 		                    else
// 		                        echo "condition isn't satisfied:".$tmp[$cond[0]].$cond[1].json_encode($cond[2])."\n";
		                    goto recache;
		                }
		            }
		            $tmp = $this->filter_row($tmp , $columns);
		            if ($tmp == -1) {
		                //cache invalid
		                goto recache;
		            }
		            
		            $this->_restore_single_buffer_($tmp);
		            $rtn[] = $tmp;
		        }
		        echo "Multi Get From Cache\n";
		        return $rtn;
		    }
		    recache:
		    $query_str .= "*";
		} else {
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
		}
		$query_str .= " FROM `".$this->table."`";
		
		if ($cond_str !== false) {
			$query_str .= $cond_str;
		}
		if (!is_null($rtn_duration)) {
			if (!is_numeric($start_offset)) {
				throw new Exception("start offset not int");
			}
			$query_str .= " LIMIT ".$start_offset.",".$rtn_duration;
			($cond_str !== false) && ($cond_str .= " LIMIT ".$start_offset.",".$rtn_duration);//shall be removed when more powerful mechanism has been designed
		}
		$rtn = array();
		if( ($res = $this->mysql->query($query_str)) ) {
			$res_arr = $this->mysql->get_all(); 
			if (!is_array($res_arr)) {
				$this->mysql->free();
				return $rtn;
			}
			$upper = count($res_arr);
			$id_list = [];
			for ($i = 0; $i != $upper; ++$i) {
				foreach($res_arr[$i] as $key => $value) {
					if (isset($this->fields[$key]))
						$rtn[$i][$key] = $value;
				}
				if (self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
				    $id_list[] = $res_arr[$i][$this->primary_key];
				    $ckey = $this->_gen_cache_id_($this->table, $res_arr[$i][$this->primary_key]);
				    if (self::$auto_cache_config_->expire != -1) {
				        self::$auto_cache_conn->SetEx($ckey, json_encode($rtn[$i]) , self::$auto_cache_config_->expire);
				    } else {
				        self::$auto_cache_conn->Set($ckey, json_encode($rtn[$i]));
				    }
				}
				$rtn[$i] = $this->filter_row($rtn[$i], $columns);
				$this->_restore_single_buffer_($rtn[$i]);
			}
			if (self::$auto_cache_config_->enabled && self::$auto_cache_conn && self::$auto_cache_config_->cache_retrieve_result && $cond_str !== false) {
			    $ckey = $this->_gen_cache_id_($this->table, $cond_str);
			    self::$auto_cache_conn->SetEx($ckey, json_encode(['ids'=>join(',', $id_list),'conds'=> $conditions]) ,  self::$auto_cache_config_->retr_res_expire);
			}
		}
		$this->mysql->free();
		$this->clear_buffer();
		echo "Multi Get From DB\n";
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
	//TODO:实现Delete One & Delete的AutoCache
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
		if ( $this->mysql->query( $query_str )) {
		    if(self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
		        //Delete Cache
		        self::$auto_cache_conn->Del($this->_gen_cache_id_($this->table, $primary_id));
		    }
			return true;
		} else {
			return false;
		}
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
		if(self::$auto_cache_config_->enabled && self::$auto_cache_conn) {
		    $sql = "select `".$this->primary_key."` from `".$this->table."`".$cond_str;
		    if ($this->mysql->query($sql)){
		        $ids = $this->mysql->get_all();
		        if (is_array($ids)) {
		            foreach ($ids as $t) {
		                self::$auto_cache_conn->Del($this->_gen_cache_id_($this->table, $t[$this->primary_key]));
		            }//end foreach ($ids as $t)
		        }//end if(is_array(ids))
		    }//end if ($this->mysql->query($sql))
		    $this->mysql->free();
		    if (self::$auto_cache_config_->cache_retrieve_result)
		        self::$auto_cache_conn->Del($this->_gen_cache_id_($this->table,$cond_str));
		}
		if ($this->mysql->query($query_str)) {
			return true;
		} else {
			return false;
		}
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
			foreach($conditions as $key => $value) {
			    if (!is_array($value) || count($value) != 3) {
			        throw new Exception("condition format incorrect:".$key."=>".json_encode($value));
// 			        unset($conditions[$key]);
			    }
			}
			uasort($conditions, function ($a , $b) {
			    $l = strtolower($a[0]);
			    $r = strtolower($b[0]);
			    if ($l == $r ) {
			        return 0;
			    }
			    return (strcmp($l, $r) < 0)?-1:1;
			});
			foreach($conditions as $value) {
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
	        if ($this->get_key($this->primary_key)) {
	            $this->Update();
	        } else {
	            $this->Insert();
	        }
	    }
	}
}
Model::init();

