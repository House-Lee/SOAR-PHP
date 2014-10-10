<?php
class Log {
	private $msg_;
	private $debug_info_;
	private $debug_depth_ = 3;
	private $debug_show_ = true;
	private $save_path_;
	private $tmp_path_;
	private $save_file_;
	
	private function get_DebugInfo() {
		if (!is_array($this->debug_info_))
			return "NULL";
		$res = json_encode(array('user-ip'=>$_SERVER['REMOTE_ADDR'] , 
								 'user-agent'=>$_SERVER['HTTP_USER_AGENT'] , 
								 'user-host'=>$_SERVER['REMOTE_HOST'],
								 'user-port'=>$_SERVER['REMOTE_PORT'],
								)
						   )."\n";
		$upper = ( count($this->debug_info_) > $this->debug_depth_ ) ? $this->debug_depth_ : count($this->debug_info_);
		for ($i = 0; $i != $upper; ++$i) {
			$res .= "#".$i.json_encode($this->debug_info_[$i])."\n";
		}
		return $res;
	}
	private function get_timestamp() {
		list ( $usec, $sec ) = explode ( " ", microtime () );
		return (( float ) $usec + ( float ) $sec);
	}
	private function mkdir($path) {
		if (!is_dir($path))
			mkdir($path , 0777);
	}
	private function write_file($file , $content , $append = false) {
		if ($append) {
			$type = "ab";
		} else {
			$type = "wb";
		}
		if (!($fp = @fopen($file , $type))) {
			return false;
		}
		$res = @fwrite($fp , $content);
		fclose($fp);
		return $res;
	}
	private function save_log() {
		$content = "Logged At: ".date("Y-m-d,H:i:s")."\t( Timestamp:".$this->get_timestamp().")\n";
		$content .= "Messages: \n\t".$this->msg_."\n";
// 		if ($this->debug_show_) {
// 			$content .= "DEBUG_INFO:\n".$this->get_DebugInfo();
// 		}
		$content .= "\n\n\n\n";
		$this->write_file(rtrim ( $this->save_path_.$this->tmp_path_, '/' ) . '/' . $this->save_file_, $content , true);
	}
	
	
	
	public function __construct($msg = "" , $debugInfo = null , $showDebug = true) {
		date_default_timezone_set("Asia/Shanghai");
		$this->msg_ = $msg;
		$this->debug_show_ = $showDebug;
		if (!is_array($debugInfo)) {
			$this->debug_info_ = debug_backtrace();
		} else {
			$this->debug_info_ = $debugInfo;
		}
		if (defined('LOG_PATH'))
			$this->save_path_ = rtrim(LOG_PATH , '/').'/';
		else
			$this->save_path_ = dirname( dirname(__FILE__) )."/logs/";
		$this->save_file_ = date("Ymd").".log";
	}
	
	public function setLog($msg , $specific_path = null) {
		$this->msg_ = $msg;
		if ($specific_path) {
			$this->tmp_path_ = ltrim($specific_path , '/');
			$this->mkdir($this->save_path_.$this->tmp_path_);
		} else {
			$this->tmp_path_ = "";
		}
		$this->save_log();
	}
}

