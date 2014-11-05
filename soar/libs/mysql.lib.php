<?php
/**
 * @file mysql.lib.php
 * mysql lib
 */

class MySQL {
    private $db_host;
    private $db_user;
    private $db_pwd;
    private $db_database;
    private $charset; //GBK,UTF8,gb2312
    private $exclusive_conn;
    private static $shared_conn = null; ///< 静态全局连接以节约SQL连接资源
    private static $show_error = false;
    private $last_query;
    private $last_insert_id;
    private $result;
    
    public function __construct($config = null , $shared_mode = true) {
        $this->exclusive_conn = null;
        if (!($shared_mode && self::$shared_conn)){
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
			$conn = $this->_connect();
			if ($shared_mode) {
			    self::$shared_conn = $conn;
			} else {
			    $this->exclusive_conn = $conn;
			}
        }
    }
    
    public function get_last_query() {
		return $this->last_query;
	}
	
	public function start_transaction() {
	    if ($this->exclusive_conn) {
	        $conn = &$this->exclusive_conn;
	    } else {
	        $conn = &self::$shared_conn;
	    }
	    @mysqli_autocommit($conn, false);
	    unset($conn);
	}
	
	public function commit() {
	    if ($this->exclusive_conn) {
	        $conn = &$this->exclusive_conn;
	    } else {
	        $conn = &self::$shared_conn;
	    }
        @mysqli_commit($conn);
	    unset($conn);
	}
	
	public function rollback() {
	    if ($this->exclusive_conn) {
	        $conn = &$this->exclusive_conn;
	    } else {
	        $conn = &self::$shared_conn;
	    }
	    @mysqli_rollback($conn);
	    unset($conn);
	}
	
	public function query( $str ) {
	    if ($this->exclusive_conn) {
	        $conn = &$this->exclusive_conn;
	    } else {
	        $conn = &self::$shared_conn;
	    }
	    $this->last_query = $str;
	    if(!($this->result = @mysqli_query($conn, $str))) {
	        $err_msg = "Query Error.";
	        if (self::$show_error) {
	            $err_msg .= "REASON:".@mysqli_error($conn);
	            echo $err_msg;
	        }
	        unset($conn);
	        return false;
	        // 			throw new Exception($err_msg);
	    } else {
	        $this->last_insert_id = mysqli_insert_id($conn);
	    }
	    unset($conn);
	    return true;
	}
	public function get_last_insert_id() {
	    return $this->last_insert_id;
	}
    
	
	public function fetch_array() {
	    return mysqli_fetch_array($this->result);
	}
	
	public function get_all() {
	    $full_result = array();
	    $i = 0;
	    while($full_result[$i++] = $this->fetch_array());
	    array_pop($full_result);
	    return $full_result;
	}
	
	public function free() {
	    if ($this->result) {
	        @mysqli_free_result($this->result);
	        $this->result = null;
	    }
	}
	
	public function __destruct() {
	    if ($this->exclusive_conn) {
	        mysqli_close($this->exclusive_conn);
	    }
	}
	
    private function _connect() {
        $db = explode(":", $this->db_host);
        $host = $db[0];
        $port = isset($db[1]) ? (int)$db[1] : 3306;
        $conn = null;
        if (!($conn = @mysqli_connect($host, $this->db_user, $this->db_pwd, $this->db_database, $port))) {
            $err_msg = "Could Not Connect.";
            if (self::$show_error) {
                $err_msg .= "REASON:".mysqli_connect_error();
                echo $err_msg;
            }
            throw new Exception($err_msg);
        }
        @mysqli_set_charset($conn, $this->charset);
        return $conn;
    }
}