<?php
/**
 * rc4.php
 * Aug 6, 2012-10:17:54 AM
 * House_Lee
 */

class RC4 {
	private $sBox = array ();
	private $sBoxLen = 256;
	private $key;
	
	private $asciimap = array (
	' '=>32,'!'=>33,'\"'=>34,'#'=>35,'$'=>36,'%'=>37,'&'=>38,'\''=>39,'('=>40,
	')'=>41,'*'=>42,'+'=>43,','=>44,'-'=>45,'.'=>46,'/'=>47,'0'=>48,'1'=>49,
	'2'=>50,'3'=>51,'4'=>52,'5'=>53,'6'=>54,'7'=>55,'8'=>56,'9'=>57,':'=>58,
	';'=>59,'<'=>60,'='=>61,'>'=>62,'?'=>63,'@'=>64,'A'=>65,'B'=>66,'C'=>67,
	'D'=>68,'E'=>69,'F'=>70,'G'=>71,'H'=>72,'I'=>73,'J'=>74,'K'=>75,'L'=>76,
	'M'=>77,'N'=>78,'O'=>79,'P'=>80,'Q'=>81,'R'=>82,'S'=>83,'T'=>84,'U'=>85,
	'V'=>86,'W'=>87,'X'=>88,'Y'=>89,'Z'=>90,'['=>91,'\\'=>92,']'=>93,'^'=>94,
	'_'=>95,'`'=>96,'a'=>97,'b'=>98,'c'=>99,'d'=>100,'e'=>101,'f'=>102,'g'=>103,
	'h'=>104,'i'=>105,'j'=>106,'k'=>107,'l'=>108,'m'=>109,'n'=>110,'o'=>111,
	'p'=>112,'q'=>113,'r'=>114,'s'=>115,'t'=>116,'u'=>117,'v'=>118,'w'=>119,
	'x'=>120,'y'=>121,'z'=>122,'{'=>123,'|'=>124,'}'=>125);
	
	
	public function __construct($key) {
		for($i = 0; $i != $this->sBoxLen; ++ $i) {
			$this->sBox [$i] = 0;
		}
		$this->setKey ( $key );
	}
	
	
	public function setKey($key) {
		$this->key = array ();
		$len = strlen ( $key );
		for($i = 0; $i != $len; ++ $i) {
			$this->key [$i] = $this->asciimap [$key [$i]];
		}
		$k = array ();
		
		for($i = 0; $i != $this->sBoxLen; ++ $i) {
			$this->sBox [$i] = $i;
			$k [$i] = $this->key [$i % $len];
		}
		$tmp = 0;
		$j = 0;
		for($i = 0; $i != $this->sBoxLen; ++ $i) {
			$j = ($j + $this->sBox [$i] + $k [$i]) % $this->sBoxLen;
			$tmp = $this->sBox [$i];
			$this->sBox [$i] = $this->sBox [$j];
			$this->sBox [$j] = $tmp;
		}
	}
	
	public function crypt($indata , $binary = false) {
		$x = 0;
		$y = 0;
		$t = 0;
		$tmp = 0;
		$len = strlen ( $indata );
		$inArr = array ();
		for($i = 0; $i != $len; ++ $i) {
			$inArr [$i] = $this->asciimap [$indata [$i]];
		}
		$resArr = array ();
		for($i = 0; $i != $len; ++ $i) {
			$x = ($x + 1) % $this->sBoxLen;
			$y = ($y + $this->sBox [$x]) % $this->sBoxLen;
			$tmp = $this->sBox [$x];
			$this->sBox [$x] = $this->sBox [$y];
			$this->sBox [$y] = $tmp;
			$t = ($this->sBox [$x] + $this->sBox [$y]) % $this->sBoxLen;
			$resArr [$i] = $inArr [$i] ^ $this->sBox [$t];
		}
		$rtnString = "";
		for($i = 0; $i != $len; ++ $i) {
			if ($binary === false) {
				$rtnString .= $resArr [$i] . ",";
			} else {
				$rtnString .= pack("i",(int)$resArr[$i] );
			}
		}
		$rtnString = rtrim ( $rtnString, ',' );
		return $rtnString;
	}
	
	public function decrypt($inData , $binary = false) {
		$plaintext = "";
		$cryptData = array();
		if ($binary) {
			$origin = unpack("i*" , $inData);
			$cryptData = array();
			$upper = count($origin);
			for($i = 1; $i <= $upper; ++$i) {
				$cryptData[] = $origin[$i];
			}
		} else {
			if (!is_array($inData)) {
				$cryptData = explode(',', $inData);
			} else {
				$cryptData = $inData;
			}
		}
		$upper = count($cryptData);
		$x = 0;
		$y = 0;
		$t = 0;
		for ($i = 0; $i != $upper; ++$i) {
			$x = ($x + 1) % $this->sBoxLen;
			$y = ($y + $this->sBox [$x]) % $this->sBoxLen;
			$tmp = $this->sBox [$x];
			$this->sBox [$x] = $this->sBox [$y];
			$this->sBox [$y] = $tmp;
			$t = ($this->sBox [$x] + $this->sBox [$y]) % $this->sBoxLen;
			$resArr [$i] = $cryptData [$i] ^ $this->sBox [$t];
		}
		for ($i = 0; $i != $upper; ++$i) {
			foreach ( $this->asciimap as $key => $value) {
				if ($value == $resArr[$i]) {
					$plaintext .= $key;
					break;
				}
			}
		}
		return $plaintext;
	}
		
}





