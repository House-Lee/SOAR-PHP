<?php
$CONF = array();

$CONF['timezone'] = 'Asia/Shanghai';

$CONF['cookie_db'] = array(
		"host" => "10.15.13.2",
		"port" => "17016",
);
$CONF['cookie_sign'] = "zvOZo}`6+z";

$CONF['db'] = array(
                'db_host' => 'localhost',
                'db_database' => 'soar_sample',
                'db_user' => 'DEFAULT_USR',
                'db_pwd' => 'DEFAULT_PASSWORD',
                'charset' => 'utf8',
                'tablePrefix' => '',
);

$CONF['RC4'] = "FNA#l1C0-L^wvG.b";

$CONF['auto_cache'] = array(
                'host_list' => ['10.15.13.2:17016'],
                'expire' => 1800,
                'prefix' => 'testproj'
                );