<?php
//spl_autoload_extensions(".php");
//spl_autoload_register();

function g_autoload($className) {
	$path = dirname(__FILE__) . '/../models/'.$className.'.php';
	if (file_exists($path)) {
		require_once $path;
	} else {
		$path = dirname(__FILE__) . '/' . $className.'.php';
		if (file_exists($path)) require_once $path;
	}
}
spl_autoload_register("g_autoload");
class GManager {
	public $config;
	
	protected static $_instance = null;
	public static function app() {
		if (self::$_instance !== null) return self::$_instance;
		self::$_instance = new GManager();
		return self::$_instance;
	}
}
GConfig::init();