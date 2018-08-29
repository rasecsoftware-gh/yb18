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
			if (trim($r['moneda_desc'])=='') {
				exit('Especifique el Nombre');
			}
			if (trim($r['moneda_simbolo'])=='') {
				exit('Especifique el Simbolo');
			}
            
            Sys::Check($r['moneda_min'], "float|>0", '', 'El valor de moneda minima debe ser un numero mayor que Zero.');
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('public.moneda');
				$dr->debug = false;
				$id = $r['moneda_id'];
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					$dr->Create(false);
				}
				$dr->Mixin(array(
				"moneda_id"=>$id, // SERIAL, 
				"moneda_desc"=>$r['moneda_desc'], 
				"moneda_simbolo"=>$r['moneda_simbolo'],
				"moneda_min"=>$r['moneda_min'],   
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
			$dr = new PgDataRow('public.moneda');
			$dr->Delete($id, true);
			echo "ok";
		}
		// detalle
	}
?>