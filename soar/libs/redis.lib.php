<?php
require_once dirname(__FILE__)."/predis/lib/Predis/Autoloader.php";
Predis\Autoloader::register();
class Redis {
	private static $server_conf;
	private $conn = null;
	public function __construct($host , $port , $db = 0) {
		if (isset(self::$server_conf[$host.":".$port])) {
			$this->conn = self::$server_conf[$host.":".$port];
		} else {
			$redis_conn = new Predis\Client(array(
									'scheme' => 'tcp',
									'host' => $host,
									'port' => $port,
									'connection_timeout' => 1.5,
									'throw_errors' => true
										));
			if ($db)
			    $redis_conn->select($db);
			self::$server_conf[$host.":".$port] = $redis_conn;
			$this->conn = $redis_conn;			
		}
	}
	public function FlushAll($db_index = null) {
		if (!is_null($db_index)) {
			$this->SelectDB($db_index);
		}
		$this->conn->flushdb();
	}
	public function SelectDB($db_index) {
		$this->conn->select($db_index);
	}
	public function Get($key) {
		return $this->conn->get($key);
	}
	public function Set($key , $value) {
		return $this->conn->set($key , $value);
	}
	public function Keys($cmp_str) {
		return $this->conn->keys($cmp_str);
	}
	public function Del($key) {
		return $this->conn->del($key);
	}
	public function Transaction($key , Closure $method) {
		//function $method($key) return $transaction_rtn_value;
		$rtn = null;
		$options = array(
				'cas' => true,
				'watch' => $key,
				'retry' => 3,
				);
		$rply = $this->conn->multiExec($options , function ($transaction) use (&$rtn , $key , $method){
			$rtn = $method($key);
		});
		return $rtn;
	}
	public function ZAdd($key , $score , $member) {
		$ret = $this->conn->zadd($key , $score , $member);
		return $ret;
	}
	public function ZRange($key , $start_idx , $end_idx , $with_score = false) {
		return $this->conn->zrange($key , $start_idx , $end_idx , array(
				'withscores' => $with_score
				));
	}
	public function ZRevRange($key , $start_idx , $end_idx , $with_score = false) {
		return $this->conn->zrevrange($key , $start_idx , $end_idx , array(
				'withscores' => $with_score
		));
	}
	public function ZRangeByScore($key , $lower , $upper , $with_score = false) {
		return $this->conn->zrangebyscore($key , $lower , $upper , array(
				'withscores' => $with_score
				));
	}
	private function push($list , $value , $direction = 'R') {
		if ($direction == 'R') {
			return $this->conn->rpush($list , $value);
		} else {
			return $this->conn->lpush($list , $value);
		}
	}
	private function pop($list , $direction = 'L') {
		if ($direction == 'L') {
			return $this->conn->lpop($list);
		} else {
			return $this->conn->rpop($list);
		}
	}
	public function RPush($list , $value) {
		return $this->push($list, $value);
	}
	public function LPush($list , $value) {
		return $this->push($list, $value , 'L');
	}
	public function RPop($list) {
		return $this->pop($list , 'R');
	}
	public function LPop($list) {
		return $this->pop($list);
	}
	public function ListRange($list , $start , $end) {
		if (!is_numeric($start) || !is_numeric($end)) {
			return null;
		}
		return $this->conn->lrange($list , $start , $end);
	}
	public function ListGetAll($list) {
		return $this->ListRange($list, 0, -1);
	}
	public function HSet($hash , $field , $value) {
		return $this->conn->hset($hash , $field , $value);
	}
	public function HGet($hash , $field) {
		return $this->conn->hget($hash , $field);
	}
	public function Expire($key , $expire) {
	    return $this->conn->expire($key , $expire);
	}
	//TODO:add other necessary features
}
