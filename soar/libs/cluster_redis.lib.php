<?php
/**
 * @file redis_cluster.lib.php
 * @brief Lib Redis Cluster
 * @author House.Lee (house.lee@soarnix.com)
 * @date 2014-12-08
 */
require_once 'redis.helper.php';
class ClusterRedis {
    private $conn = null;
    
    const Mode_MasterSlave = 0;
    const Mode_Cluster = 1;
    public function __construct(array $node_list , $mode = 1) {
        //node list example: [ ["10.1.18.2:17016" , "master"] , ["10.1.18.3:17016" , "node-alias"]]
        $hlist = [];
        $options = ['exceptions' => true];
        if ($mode == self::Mode_MasterSlave) {
            $options['replication'] = true;
            $master_found = false;
            $slave_cnt = 0;
            foreach($node_list as $v) {
                if (is_array($v)) {
                    if (!isset($v[0]) || !isset($v[1])){
                        continue;
                    }
                    $tmp = "tcp://".$v[0]."?alias=".$v[1];
                    if ($v[1] == "master") {
                        $master_found = true;
                    }
                } else {
                    ++$slave_cnt;
                    $tmp = "tcp://".$v."?alias=soar_slave-".$slave_cnt;
                }
                $hlist[] = $tmp;
            }
            if (!$master_found) {
                throw new Exception("[Redis Cluster]Master not found");
            }
        } else {
            $options["cluster"] = 'redis';
            foreach ($node_list as $v) {
                if (is_array($v)) {
                    if (!isset($v[0]) || !isset($v[1])) {
                        continue;
                    }
                    $tmp = "tcp://".$v[0]."?alias=".$v[1];
                } else {
                    $tmp = "tcp://".$v;
                }
                $hlist[] = $tmp;
            }
        }
        if (!count($hlist)) {
            throw new Exception("[Redis Cluster]Hosts Empty");
        }
        $this->conn = new Predis\Client($hlist , $options);
    }
    public function Get($key) {
        return $this->conn->get($key);
    }
    public function Set($key , $value) {
        return $this->conn->set($key , $value);
    }
    public function SetEx($key , $value , $expire) {
        return $this->conn->setex($key , $expire , $value);
    }
    public function Del($key) {
        return $this->conn->del($key);
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
}