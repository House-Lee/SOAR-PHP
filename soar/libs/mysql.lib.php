<?php
class MySQL {
	private $db_host;
	private $db_user;
	private $db_pwd;
	private $db_database;
	private $charset; //GBK,UTF8,gb2312
	private static $conn; ///< 静态全局连接以节约SQL连接资源
	private static $locking = false;///<标记当前是否有人持有行锁，防止共享conn中出现commit冲突释放不该释放的锁
	private static $max_try_lock = 5;///<最大尝试加锁次数
	private static $try_lock_interval = 10;///<ms
	private static $show_error = false;
	private $last_query_;
	private $last_insert_id;
	private $result_;
	
	public function get_last_query() {
		return $this->last_query_;
	}
	
	public function __construct($config = null) {
		if(!self::$conn ) {
			if (!is_array($config) || !isset($config['db_host']) || !isset($config['db_user']) || !isset($config['db_pwd']) || !isset($config['db_database'])) {
				throw new Exception("MySQL initialized failed.");
			}
			$this->db_host = $config['db_host'];
			$this->db_user = $config['db_user'];
			$this->db_pwd = $config['db_pwd'];
			$this->db_database = $config['db_database'];
			$this->charset = isset($config['charset'])?$config['charset']:'utf8';
			if(defined('SOAR_DEBUG') && SOAR_DEBUG == 'DEBUG') {
				self::$show_error = true;
			} else {
				self::$show_error = false;
			}
			$this->connect();
		}
	}
	
	public function connect() {
		if (count(explode(':' , $this->db_host)) != 2)
				$this->db_host .= ":3306";
		if (!(self::$conn = mysql_connect($this->db_host , $this->db_user , $this->db_pwd))) {
			$err_msg = "Could Not Connect.";
			if (self::$show_error) {
				$err_msg .= "REASON:".mysql_error();
				echo $err_msg;
			}
			throw new Exception($err_msg);
		}
		if (!mysql_select_db($this->db_database , self::$conn)) {
			$err_msg = "Could Not Open DataBase.";
			if (self::$show_error) {
				$err_msg .= "REASON:".mysql_error();
				echo $err_msg;
			}
			throw new Exception($err_msg);
		}
		mysql_query('SET NAMES '.$this->charset , self::$conn);
	}
	public function start_transaction() {
		$i = 0;
		while ($i < self::$max_try_lock) {
			if (!self::$locking)
				break;
			++$i;
			usleep(self::$try_lock_interval);
		}
		if ($i == self::$max_try_lock) {
			throw new Exception("transaction failed");
		}
		self::$locking = true;
		mysql_query("START TRANSACTION" , self::$conn);
	}
	public function commit() {
		self::$locking = false;
		mysql_query("COMMIT" , self::$conn);
	}
	public function rollback() {
		self::$locking = false;
		mysql_query("ROLLBACK" , self::$conn);
	}
	
	public function query( $str ) {
		$this->last_query_ = $str;
		if(!($this->result_ = mysql_query($str , self::$conn))) {
			$err_msg = "Query Error.";
			if (self::$show_error) {
				$err_msg .= "REASON:".mysql_error(self::$conn);
				echo $err_msg;
			}
			return false;
// 			throw new Exception($err_msg);
		} else {
			$this->last_insert_id = mysql_insert_id(self::$conn);
		}
		return true;
	}
	
	public function get_last_insert_id() {
		return $this->last_insert_id;
	}
	
	public function fetch_array() {
		return mysql_fetch_array($this->result_);
	}
	public function get_all()
	{
		$full_result = array();
		$i = 0;
		while($full_result[$i++] = $this->fetch_array());
// 		unset($full_result[$i - 1]);
		array_pop($full_result);
		return $full_result;
	}
	
	public function free() {
		if ($this->result_)
			@mysql_free_result($this->result_);
	}
	
// 	function __destruct() {
// 		if($this->result_)
// 			$this->free();	
// 	}
 }