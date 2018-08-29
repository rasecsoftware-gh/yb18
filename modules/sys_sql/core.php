<?php
	require_once '../../sys.php';
	
	Core::Listen();
	
	class Core {  
		public static function Listen() {
			if (!defined("_DISABLED_CLASS_LISTEN_")) {
				Sys::CallClassMethodFromRequest(get_class());
			}
		} 
		//CRUD operations
		public static function Execute() {
			$name = Sys::GetP('name');
			if (get_magic_quotes_gpc()) {
				$name = stripslashes($name);
			}
			$url = Config::GetReportUrl()."setprinter?n=".urlencode($name);
			//echo $url;
			$result = @file_get_contents($url);
			if ($result!==false) {
				if (trim($result)=='ok') {
					echo 'ok'; exit;	
				} 
			} 
			echo 'error';
		}
	}
?>