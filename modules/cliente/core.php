<?php
	require_once '../../sys.php';
	
	Core::Listen();
	
	class Core {  
		public static function Listen() {
			if (!defined("_DISABLED_CLASS_LISTEN_")) {
				Sys::CallClassMethodFromRequest(get_class());
			}
		} 
        public static function GetNextId() {
            $esta_id = Sys::GetUserEstablecimientoId();
            
            $next = PgQuery::GetQueryVal("SELECT public.f_cliente_nextid('$esta_id')", '');
            return $next;
        }
		//CRUD operations
		public static function Update() {
			$r = $_POST;
            $cliente_numdoc = Sys::GetP('cliente_numdoc');
			// validacion
			Sys::Check($r['cliente_desc'], '!empty', '', 'Especifique el nombre');
			
            $filter_numdoc = trim($cliente_numdoc);
            if (trim($cliente_numdoc)!='') {
                if (strlen(trim($cliente_numdoc)) < 8) {
                    exit('Especifique correctamente el DNI o RUC.');
                }
                Sys::Check($cliente_numdoc, 'int|>0', '', 'Especifique correctamente el DNI o RUC.');
            } 
            
            $q = new PgQuery("
            SELECT * 
            FROM public.cliente c 
            WHERE ( 
                (".PgQuery::GetFilterSql($r['cliente_desc'], "(c.cliente_desc ilike '%:filter%')").")
                OR (c.cliente_numdoc like '$filter_numdoc' AND trim(c.cliente_numdoc) <> '')
            )
            AND c.cliente_id <> '{$r['cliente_id']}'
            ", NULL, true);
            //echo $q->sql;
            
            if ($q->recordCount>0) {
                exit("El nombre o num. documento ya existe . Especifique mas datos.");
            }
            
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('public.cliente');
				$dr->debug = false;
				$id = $r['cliente_id'];
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					$dr->Create(false);
					$dr->Set('establecimiento_id', Sys::GetUserEstablecimientoId());
				}
				$dr->Mixin(array(
				"cliente_id"=>$id, // SERIAL, 
				"cliente_desc"=>Sys::Upper($r['cliente_desc']), 
				"cliente_numdoc"=>$r['cliente_numdoc'],
				"cliente_infoadic"=>$r['cliente_infoadic'],
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
            $q = new PgQuery("SELECT * FROM public.registro WHERE cliente_id = '$id'", NULL, true, true);
            if ($q->recordCount>0) {
                die("No es posible eliminar el cliente.");
            }
			$dr = new PgDataRow('public.cliente');
			$dr->Delete($id, true);
			echo "ok";
		}
		// detalle
	}
?>