<?php
if(!defined("_CONFIG_")) {
	define("_CONFIG_", 1);
	function makedir($path, $mode = 0777) {
		return is_dir($path) or ( makedir(dirname($path), $mode) and mkdir($path, $mode) );
	}
	$session_path = dirname(__FILE__).'/tmp';
	@mkdir($session_path);
	ini_set('session.save_path', $session_path);
	ini_set('session.cookie_lifetime', 0);  //El tiempo viene dado en segundos
	ini_set('session.gc_probability', 1);
	ini_set('session.gc_divisor', 1000);
	ini_set('session.gc_maxlifetime', 120*60);
	
	// set php display error level
	error_reporting(E_ALL ^ E_NOTICE);
	// tiempo maximo de ejecucion del script (segundos)
	set_time_limit(300);
	ini_set('date.timezone', 'America/Lima');
	
	session_name('yago');
	session_start();
	//session_regenerate_id(true);
	@header('Content-Type: text/html; charset=ISO-8859-1');
	class Config {
		public static $UseDB = true;
		public static $Debug = true;
		// DB parameters
		public static $DbServer = "localhost";
		public static $DbPort = "5494";
		public static $DbName = "yago";
		public static $DbUser = "postgres";
		public static $DbPassword = "123456";
		// json solo admit utf8! // para las peticione GEt convertir a utf8 la consulta (utf8_encode)
		public static $DbEncode = "utf-8";
		public static $DbPersistent = true; 
		
		public static function GetReportUrl() {
			return "http://".Sys::GetUserIP().":88/";
		}
		public static function GetOrganizationName() {
			return "Yagobank";
		}
		public static function GetOrganizationSiglas() {
			return "Yagobank";
		}
		public static function GetSystemName() {
			return "Yagobank";
		}
		public static function GetSystemVersion() {
			return "1.0";
		}
		public static function GetSystemDescription() {
			return "Yagobank";
		}
	}
}
?>