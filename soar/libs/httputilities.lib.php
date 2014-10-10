<?php
class HttpUtilities {
	public static function GetRawBody() {
		return file_get_contents("php://input");
	}
	
	public static function PostRawStr($URL , $str) {
		$ch = curl_init($URL);
		json_decode($str);
		if (json_last_error() == JSON_ERROR_NONE) {
			$header_arr = array("Content-Type: text/xml" , "Content-length: ".strlen($str));
		} else {
			$header_arr = array("Content-Type: application/json" , "Content-length: ".strlen($str));
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header_arr);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$res = curl_exec($ch);
		if(curl_errno($ch)) {
			$res = false;
		} 
		curl_close($ch);
		return $res;
	}
	public static function PostRawBinary($URL , $bi_stream , $length) {
		return false;
	}
	public static function is_ip($address) {
		$arr = explode('.',$address);
		if(count($arr) != 4)
			return false;
		for($i = 0; $i != 4; ++$i) {
			if(!is_numeric($arr[$i]) || ($arr[$i] > 255 || $arr[$i] < 0))
				return false;
		}
		return true;
	}
	public static function getClientIP() {
	    $client  = @$_SERVER['HTTP_CLIENT_IP'];
	    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	    $remote  = $_SERVER['REMOTE_ADDR'];

	    if(filter_var($client, FILTER_VALIDATE_IP))
	    {
	        $ip = $client;
	    }
	    elseif(filter_var($forward, FILTER_VALIDATE_IP))
	    {
	        $ip = $forward;
	    }
	    else
	    {
	        $ip = $remote;
	    }
	    return $ip;
	}
	public static function getClientAgent() {
	    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
	        return "";
	    }
	    return $_SERVER['HTTP_USER_AGENT'];
	}
}