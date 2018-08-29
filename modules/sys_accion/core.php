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
			$id = $r['id_accion'];
			// validacion
			if (PgQuery::GetQueryVal("SELECT count(*) 
				FROM sys.accion
				WHERE key='{$r['key']}' 
				AND id_accion<>$id
				", 0) > 0) {
				exit('El Identificador de la Accion ya Existe');
			}
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('sys.accion');
				//echo "<pre>".print_r($dr->schema, true)."</pre>";
				$dr->debug = false;
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					$dr->Create(false);
				}
				$dr->Mixin(array(
				'id_accion'=>$id, // int IDENTITY(1, 1) NOT NULL,
				'key'=>trim($r['key']), //
				'descripcion'=>$r['descripcion'], // varchar(100) 
				//'inf_reg'=>$r['inf_reg'], // text
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
			Sys::CheckUserAction('accion.delete');
			$id = Sys::GetR('id');
			$dr = new PgDataRow('sys.accion');
			$dr->Delete($id, true);
			echo "ok";
		}
		// querys
	}
?>