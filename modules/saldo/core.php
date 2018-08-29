<?php
	require_once '../../sys.php';
	
	Core::Listen();
	
	class Core {  
		public static function Listen() {
			if (!defined("_DISABLED_CLASS_LISTEN_")) {
				Sys::CallClassMethodFromRequest(get_class());
			}
		} 
		public static function SetSD() {
			// params
			$secfun = Sys::GetP('secfun', 0);
			$depen = Sys::GetP('depen', 0);
			$tipo = Sys::GetP('tipo', 0);
			if ($secfun ==0 || $depen == 0) {
				exit('Los parametros especificados son incorrectos');
			}
			$_SESSION['saldosecfun'] = trim($secfun);
			$_SESSION['saldodepen'] = trim($depen);
			$_SESSION['saldotipo'] = trim($tipo);
			exit('ok');
		}
		// query list
	}
?>