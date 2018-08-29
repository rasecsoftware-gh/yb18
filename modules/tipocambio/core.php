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
			Sys::Check($r['tipocambio_factor'], 'float|>0', '', 'El Factor debe ser un numero mayor que 0.');
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('public.tipocambio');
				$dr->debug = false;
				$id = $r['tipocambio_id'];
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					$dr->Create(false);
				}
				$dr->Mixin(array(
				"tipocambio_id"=>$id, // SERIAL, 
				//"establecimiento_id"=>$r['establecimiento_id'], 
				"tipocambio_fecha"=>date('d/m/Y H:i:s'),
				"tipope_id"=>$r['tipope_id'], 
				"moneda_id_de"=>$r['moneda_id_de'], 
				"moneda_id_a"=>$r['moneda_id_a'], 
				"tipocambio_factor"=>$r['tipocambio_factor'],
				"tipocambio_operador"=>$r['tipocambio_operador'],     
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
			$dr = new PgDataRow('public.tipocambio');
			$dr->Delete($id, true);
			echo "ok";
		}
		// detalle
	}
?>