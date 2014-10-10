<?php
$CONF = array();
$CONF['authcode'] = "N+D8tYjO?E9uZkPa";
$CONF['group'] = array(
		'admin'=>1,
		'member'=>2,
		'user_g1'=>4,
        'user_g2'=>8,
		);
$auth = $CONF['group'];
$auth['all'] = PHP_INT_MAX;
$CONF['default_right'] = $auth['user_g1'] | $auth['member'];

$CONF['site'] = array(
		'authdemo' => $auth['admin']|$auth['user_g1'],
		);
