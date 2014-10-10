#!/usr/bin/php
<?php
error_reporting(7);
define('ROOT_PATH',dirname(dirname(dirname(__FILE__))));
define('MODEL_PATH' , ROOT_PATH."/soar/models/");
$mysql_conf = array(
				'db_host' => 'localhost',
                'db_database' => 'soar_sample',
                'db_user' => 'root',
                'db_pwd' => 'LSLLxd',
                'charset' => 'utf8',
                'tablePrefix' => '',
				);
$type_map = array(
		'int' => 'int',
		'varchar' => 'string',
		'text' => 'string'
		);
//Connect to mysql
$mysql_link = mysql_connect($mysql_conf['db_host'] , $mysql_conf['db_user'] , $mysql_conf['db_pwd']);
if(!mysql_select_db($mysql_conf['db_database'] , $mysql_link)) {
	exit ("数据库连接失败\n");
}
mysql_set_charset($mysql_conf['charset']);

//start

$sql = "SHOW TABLES";
$query_res = mysql_query($sql , $mysql_link);
if (!$query_res) {
	exit("读取表信息失败，原因:".mysql_error($mysql_link)."\n");
}
$tables = array();
while ( ($res = mysql_fetch_array($query_res))) {
	$tables[] = $res[0];
}
foreach($tables as $table) {
	//load columns info of $table
	$columns = array();
	$sql = "desc `".$table."`";
	$query_res = mysql_query($sql , $mysql_link);
	if (!$query_res) {
		exit("读取表结构失败，原因:".mysql_error($mysql_link));
	}
	while (($res = mysql_fetch_array($query_res))) {
		$columns[] = $res;
	}
	$primary_key = $columns[0]['Field'];
	
	$tmp_names = explode("_",$table);
	$modelname = "";
	foreach($tmp_names as $word) {
		$modelname .= ucfirst($word);
	}
	$modelname .= "Dao";
	$filename = strtolower($modelname).".model.php";
	$filecontent = "<?php\n";
	$filecontent .= "class ".$modelname." extends Model {\n";
	$filecontent .= "\tpublic \$table = \"".$table."\";\n";
	$filecontent .= "\tpublic \$primary_key = \"".$primary_key."\";\n";
	//generate fields
	$filecontent .= "\tpublic \$fields = array(\n";
	$tabs = "\t\t\t\t\t\t";
	foreach($columns as $column) {
		$filecontent .= $tabs."\"".$column["Field"]."\" => \"";
		$type = explode('(', $column['Type']);
		if(!isset($type_map[$type[0]])) {
			$type = "string";
		} else {
			$type = $type_map[$type[0]];
		}
		$filecontent .= $type ."\",\n";
	}
	$filecontent .= $tabs.");\n";
	//generate functions
	$tabs = "\t";
	foreach($columns as $column) {
		$filecontent .= $tabs."public function set_".str_replace("-", "_", $column["Field"])."(\$value) {\n";
		$filecontent .= $tabs.$tabs.'$this->set(\''.$column["Field"].'\',$value);'."\n";
		$filecontent .= $tabs.$tabs.'$this->need_auto_update = true;'."\n";
		$filecontent .= $tabs."}\n";
		$filecontent .= $tabs."public function ".str_replace("-", "_", $column["Field"])."() {\n";
		$filecontent .= $tabs.$tabs."return \$this->get_key(\"".$column["Field"]."\");\n";
		$filecontent .= $tabs."}\n";
	}
	
	$filecontent .= "}";
	file_put_contents(MODEL_PATH.$filename, $filecontent);
}