<?php
require_once "soar/soar_app.php";

$App = new SoarApp("site/index" , "debug");
try {
	$App->Run();
} catch (Exception $exp) {
	exit( $exp->getMessage() );
}