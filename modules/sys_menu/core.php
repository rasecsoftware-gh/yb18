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
			Sys::CheckUserAction('menu.update');
			$r = $_POST;
			// validacion
			if (trim($r['nombre'])=='') {
				exit('Especifique el Nombre correctamente');
			}
			$id = $r['id_menu'];
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('sys.menu');
				//echo "<pre>".print_r($dr->schema, true)."</pre>";
				$dr->debug = false;
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					$dr->Create(false);
					$dr->Set('orden', $dr->GetMax('orden', NULL, 0)+1);
				}
				$dr->Mixin(array(
				'id_menu'=>$id, // int 
				'nombre'=>$r['nombre'], //
				'estado'=>$r['estado'], //
				'id_parent'=>($r['id_parent']==0)?NULL:$r['id_parent'], //
				'actionscript'=>$r['actionscript'], //
				'begin_group'=>$r['begin_group']==1?1:0, //
				//'inf_reg'=>$r['inf_reg'], //
				//'orden'=>$r['orden'], //
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
			Sys::CheckUserAction('menu.delete');
			$id = Sys::GetP('id');
			if (PgQuery::GetQueryVal("SELECT COUNT(*) FROM sys.menu WHERE id_parent=$id", 0)>0) {
				//exit("No se puede eliminar. Otros elementos dependen de este Menu");	
			}
			$dr = new PgDataRow('sys.menu');
			$dr->AddChildRelation(array(
				'name'=>'submenu',
				'table'=>'sys.menu',
				'columns'=>array('id_menu'=>'id_parent')
			));
			$dr->Delete($id, true);
			echo "ok";
		}
		public static function AccionUpdate() {
			Sys::CheckUserAction('accion.update');
			$r = $_POST;
			// validacion
			if (trim($r['descripcion'])=='') {
				exit('Especifique la descripcion');
			}
			$id = $r['id_accion'];
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
					$dr->Set('id_menu', $r['id_menu']);
					$dr->Set('estado', 1);
				}
				$dr->Mixin(array(
				'id_accion'=>$id, // int 
				'keyname'=>$r['keyname'], //
				'descripcion'=>$r['descripcion'], //
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
		public static function AccionChangeEstado() {
			$r = $_POST;
			$id = $r['id'];
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('sys.accion');
				$dr->debug = false;
				$dr->Read($id);
				$dr->Set('estado', $r['value']);
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