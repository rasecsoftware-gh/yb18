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
		public static function SetPrinter() {
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
		public static function SetPVentaPSA() {
			$id = Sys::GetP('id', 0);
			$value = Sys::GetP('value','0');
			if ($id <= 0) exit("Seleccione un registro valido");
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('sys.usuario_pventa');
				$dr->debug = false;
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					exit("Seleccione un registro valido");
				}
				$dr->Set('usuario_pventa_psa', $value);
				if ($dr->Get('pventa_id')==Sys::GetUserPVentaId()) {
					$_SESSION['sys_user_psa'] = $value;
				}
				$dr->UpdateLogInfo();
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
	}
?>