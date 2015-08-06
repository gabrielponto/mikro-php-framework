<?php
require_once dirname(__FILE__) . '/../../ewcfg8.php';
return array(
	'db'=>array(
		'connectionString'=>'mysql:dbname='.EW_CONN_DB.';host='.EW_CONN_HOST,
		'user'=>EW_CONN_USER,
		'password'=>EW_CONN_PASS
	)
);