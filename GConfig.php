<?php 
class GConfig {
	protected static $_config_name = 'main.php';
	protected static $_path;
	public static function init() {
		GManager::app()->config = json_decode(json_encode(require_once self::_path()));
	}
	
	protected static function _path() {
		$possibilities = array('', 'config/', '../', '../config/', '../../', '../../config/');
		foreach ($possibilities as $pos) {
			$path = dirname(__FILE__) . '/' . $pos . self::$_config_name;
			if (file_exists($path)) {
				self::$_path = $path;
				return self::$_path;
			}
		}
		throw new Exception('Não há arquivo de configuração. Crie um arquivo de configuração na pasta "config", com o nome de main.php');
	}
}