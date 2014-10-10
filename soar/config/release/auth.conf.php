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

$CONF['memberctl'] = array(
		"logininfo"=>$auth['all'],
		"logout"=>$auth['all'],
		"createdepartment"=>$auth["admin"],
		"deletedepartment"=>$auth["admin"],
		"renamedepartment"=>$auth['admin'],
		);
$CONF['site'] = array(
        'index' => $auth['admin'],
		'admin' => $auth['admin'],
		'project' => $auth['admin'] | $auth['member'],
		'machine' => $auth['admin'] | $auth['member'],
		'mission' => $auth['admin'] | $auth['member'],
		);
$CONF['projectctl'] = array(
		'addcontact' => $auth['member']|$auth['admin'],
		'deletecontact' => $auth['member']|$auth['admin'],
		'addresponder' => $auth['member']|$auth['admin'],
		'deleteresponder' => $auth['member']|$auth['admin'],
		'createproject' => $auth['member']|$auth['admin'],
		'modifyproject' => $auth['member']|$auth['admin'],
		);
$CONF['testchecker'] = array(
		'tauth' => $auth['member']|$auth['admin']
		);