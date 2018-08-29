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
			$init = microtime();
			//Sys::CheckUserAction('usuario.update');
			$r = $_POST;
			$id = $r['id_usuario'];
			// validacion
			Sys::Check($r['login'], '!empty', 'Login');
			Sys::Check($r['nombre'], '!empty', 'Nombre');
			//if ($r['id_trabajador']==0) 
				//$r['id_trabajador'] = NULL;
			
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('sys.usuario');
				//echo "<pre>".print_r($dr->schema, true)."</pre>";
				$dr->debug = false;
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					$dr->Create(false);
				}
				$dr->Mixin(array(
				'id_usuario'=>$id, // int IDENTITY(1, 1) NOT NULL,
				'login'=>$r['login'], //
				'password'=>$r['password'], //
				//'inf_reg'=>$r['inf_reg'], //
				'activo'=>($r['estado']), //
				'is_admin'=>($r['is_admin']), //
				'is_profile'=>($r['is_profile']), //
				'externo'=>($r['externo']), //
				'all_cc'=>($r['all_cc']), //
				//'id_trabajador'=>$r['id_trabajador'],
				'nombre'=>$r['nombre'],
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
		public static function UsuarioMenuUpdateMultiple() {
			$id_usuario = Sys::GetP('id_usuario', 0);
			$list = Sys::GetP('list', array());
			Sys::BeginTransaction();
			try {
				PgQuery::ExecuteNonQuery("DELETE FROM sys.usuario_menu WHERE id_usuario = $id_usuario");
				$dr = new PgDataRow('sys.usuario_menu');
				foreach($list as $id) {
					$dr->Create();
					$dr->Set('id_usuario', $id_usuario);
					$dr->Set('id_menu', $id);
					$dr->Set('estado', 1);
					$dr->UpdateLogInfo();
					$dr->Update();
				}
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function UsuarioProfileUpdateMultiple() {
			$id_usuario = Sys::GetP('id_usuario', 0);
			$list = Sys::GetP('list', array());
			Sys::BeginTransaction();
			try {
				PgQuery::ExecuteNonQuery("DELETE FROM sys.usuario_perfil WHERE id_usuario = $id_usuario");
				$dr = new PgDataRow('sys.usuario_perfil');
				foreach($list as $id) {
					$dr->Create();
					$dr->Set('id_usuario', $id_usuario);
					$dr->Set('id_perfil', $id);
					$dr->Set('estado', 1);
					$dr->UpdateLogInfo();
					$dr->Update();
				}
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function UpdateMultiAccion() {
			exit('obsoleto!');
			$id_usuario = Sys::GetP('id_usuario', 0);
			$list = Sys::GetP('list', array());
			Sys::BeginTransaction();
			try {
				PgQuery::ExecuteNonQuery("DELETE FROM sys.usuario_accion WHERE id_usuario = $id_usuario");
				$dr = new PgDataRow('sys.usuario_accion');
				foreach($list as $id) {
					$dr->Create();
					$dr->Set('id_usuario', $id_usuario);
					$dr->Set('id_accion', $id);
					$dr->Set('estado', 1);
					$dr->UpdateLogInfo();
					$dr->Update();
				}
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function UsuarioAccionChangeEstado() {
			$r = $_POST;
			// validation
			Sys::Check($r['id_u_m'], 'int|>0', '', 'EL usuario no tiene el menu asignado.');
			$id = PgQuery::GetValue('sys.usuario_accion.id_u_a', "id_u_m={$r['id_u_m']} AND id_accion={$r['id_accion']}", 0);
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('sys.usuario_accion');
				$dr->debug = false;
				if ($dr->Exists($id)) {
					$dr->Read($id);	
				} else {
					$dr->Create();
					$dr->Set('id_u_m', $r['id_u_m']);
					$dr->Set('id_accion', $r['id_accion']);
				}
				$dr->Set('estado', $r['value']);
				$dr->UpdateLogInfo();
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function UsuarioCCostoAdd() {
			Sys::CheckUserAction('usuario_ccosto.add');
			$r = $_POST;
			$id = $r['id_usuario'];
			$toadd = $r['id_ccosto'];
			// validation
			$q = new PgQuery("SELECT * FROM log.usuario_ccosto WHERE id_usuario = $id AND id_ccosto = $toadd", NULL, true);
			if ($q->recordCount > 0) {
				exit("<Sys:Warning>El usuario ya tiene asignado el centro de costo.");
			}
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('log.usuario_ccosto');
				$dr->debug = false;
				$dr->Create();
				$dr->Set('id_usuario', $id);
				$dr->Set('id_ccosto', $toadd);
				$dr->UpdateLogInfo();
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function UsuarioCCostoDelete() {
			Sys::CheckUserAction('usuario_ccosto.delete');
			$id = Sys::GetP('id');
			$dr = new PgDataRow('log.usuario_ccosto');
			$dr->Delete($id, true);
			echo "ok";
		}
		public static function UpdateMultiContenidos() {
			exit('deprecated!');
			$id_usuario = Sys::GetP('id_usuario', 0);
			$list = Sys::GetP('list', array());
			Sys::BeginTransaction();
			try {
				PgQuery::ExecuteNonQuery("DELETE FROM sys.usuario_contenido WHERE id_usuario = $id_usuario");
				$dr = new PgDataRow('sys.usuario_contenido');
				foreach($list as $id) {
					$dr->Create();
					$dr->Set('id_usuario', $id_usuario);
					$dr->Set('id_contenido', $id=='*'?NULL:$id);
					$dr->Set('estado', true);
					$dr->UpdateLogInfo();
					$dr->Update();
				}
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
        public static function UsuarioPVentaMultiUpdate() {
            $id_usuario = Sys::GetP('usuario_id', 0);
            $list = Sys::GetP('list', array());
            Sys::BeginTransaction();
            try {
                PgQuery::ExecuteNonQuery("DELETE FROM sys.usuario_pventa WHERE usuario_id = $id_usuario");
                $dr = new PgDataRow('sys.usuario_pventa');
                foreach($list as $id) {
                    $dr->Create();
                    $dr->Set('usuario_id', $id_usuario);
                    $dr->Set('pventa_id', $id);
                    $dr->Set('usuario_pventa_estado', 1);
                    $dr->UpdateLogInfo();
                    $dr->Update();
                }
                Sys::CommitTransaction();
                echo "ok";
            } catch (Exception $ex) {
                Sys::RollbackTransaction();
                echo "error";
            }
        }
		public static function Delete() {
			//Sys::CheckUserAction('usuario.delete');
			$id = Sys::GetP('id');
			$dr = new PgDataRow('sys.usuario');
			$dr->Delete($id, true);
			echo "ok";
		}
		public static function ChangePass() {
			$r = $_POST;
			// validacion
			if (trim($r['pwd1'])=='' || trim($r['pwd2'])=='') {
				exit('La contrasena no puede ser vacio');
			}
			if (trim($r['pwd1'])!=trim($r['pwd2'])) {
				exit('Las contrasenas no coinciden');
			}
			$id = $r['id_usuario'];
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('sys.usuario');
				$dr->debug = false;
				if ($dr->Exists($id)) {
					$dr->Read($id);
				} else {
					exit('El usuario no existe');
				}
				if ($dr->Get('password')!=$r['pwd']) {
					exit('La contrasena actual es incorrecta');
				}
				$dr->Set('password', $r['pwd1']);
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		// search
		public static function SearchTrabajador() {
			$filter = Sys::GetR('term', '');
			$q = new PgQuery("
			SELECT id_tra as id, nom_tra||' '||coalesce(trim(ape_tra), '') as label 
			FROM rh.trabajador 
			WHERE estado = 1 AND (nom_tra ILIKE '%$filter%' OR ape_tra ILIKE '%$filter%')
			ORDER BY nom_tra, ape_tra LIMIT 50", NULL, true, false);
			echo $q->ToJson();	
		}
	}
?>