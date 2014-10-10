<?php
/**
 * @file 	parser.php
 * @brief	解析.proto类
 * @author	House.Lee(house.lee@soarnix.com)
 * @date	2013-04-16
 */

/**
 * CLASS Parser 可以分析.proto文件，并生成对应的php class
 */
class Parser {
	private		   $m_types = array();///< 消息的属性[required, repeated, optional]
	private 	   $c_types = array();///< 消息所属的类型
	private static $v_types = array('double', 'float', 'int32' => 'PBInt', 'int64' => 'PBInt',
                              'uint32', 'uint64', 'sint32' => 'PBSignedInt', 'sint64' => 'PBSignedInt',
                              'fixed32', 'fixed64', 'sfixed32', 'sfixed64',
                              'bool' => 'PBBool', 'string' => 'PBString', 'bytes' => 'PBString');///< 默认类型转换
	private		   $proto_str;
	
	public function Parse($filename) {
		if(!file_exists($filename)) {
			throw new Expection("File not exists");
		}
		$this->proto_str = trim(file_get_contents($filename));
		$this->filter_comment_();
		
		$gen_filename = explode("/", $filename);
		$gen_filename = $gen_filename[count($gen_filename) - 1];
	}
	
	private function filter_comment_() {
		$this->proto_str = preg_replace('/\/\/.+/', '', $this->proto_str);
		$this->proto_str = preg_replace('/\\r?\\n\s*/', "\n", $this->proto_str);
	}
	
	private function get_next() {
		$result = array();
		$match = preg_match('/([^\s^\{}]*)/', $this->proto_str, $result, PREG_OFFSET_CAPTURE);
		if (!$match)
			return -1;
		return trim($result[0][0]);
	}
	
	private function get_code_range($begin_delimeter , $end_delimter) {
		$offset_begin = strpos($this->proto_str , $begin_delimeter);
		if($offset_begin === false) {
			return array('begin' => -1 , 'end' => -1);
		}
		$offset_end = -1;
		
		$stack_size = 1;
		$len = strlen($this->proto_str);
		$pos = $offset_begin + 1;
		while($pos != $len) {
			if ($this->proto_str[$pos] == $begin_delimeter) {
				++$stack_size;
			} else if ($this->proto_str[$pos] == $end_delimter) {
				--$stack_size;
				if(!$stack_size) {
					$offset_end = $pos + 1;
					break;
				}
			}
		}
		if ($stack_size)
			throw new Exception("PB GRAMMAR ERROR");
		return array('begin' => $offset_begin , 'end' => $offset_end);
	}
	
	private function parse_message_type(&$string , $m_name , $path = "") {
		$myarray = array();
		
		while(strlen($string) > 0) {
			$next2parse = $this->get_next_();
			if(strtolower($next2parse) == "message") {
				$string = trim(substr($string , strlen($next2parse)));
				$name = $this->get_next();
				$offset = $this->get_code_range('{', '}');
				$content = trim(substr($string , $offset["begin"] + 1 , $offset["end"] - $offset["begin"] - 2));
				$this->parse_message_type($content , $name, trim($path . '.' . $name, '.'));
                $string = '' . trim(substr($string, $offset['end']));
			} else if(strtolower($next2parse)) {
				;
			}
		}
	}
	
}