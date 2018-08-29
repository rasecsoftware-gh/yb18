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
		public static function Update() {
			$r = $_POST;
			// validacion
			if (trim($r['pventa_desc'])=='') {
				exit('Especifique la descripcion');
			}
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('public.pventa');
				$dr->debug = false;
				$id = $r['pventa_id'];
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					$dr->Create(false);
				}
				$dr->Mixin(array(
				"pventa_id"=>$id, // SERIAL, 
				"establecimiento_id"=>$r['establecimiento_id'], 
				"pventa_desc"=>$r['pventa_desc'], 
				//"syslog"=>$r['syslog'], //
				"end"=>0 
				));
				$dr->UpdateLogInfo();
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function Delete() {
			$id = Sys::GetR('id');
			$dr = new PgDataRow('public.pventa');
			$dr->Delete($id, true);
			echo "ok";
		}
		// detalle
	}
?>