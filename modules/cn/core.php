<?php
	require_once '../../sys.php';
	
	Core::Listen();
	
	class Core {  
		public static $tableName = 'dbo.table';  
		public static function Listen() {
			if (!defined("_DISABLED_CLASS_LISTEN_")) {
				Sys::CallClassMethodFromRequest(get_class());
			}
		} 
		public static function GetEstadoInfo($code) {
			$einfo = array(
			'00'=>array('Generado','black', 'white'), // generado
			'01'=>array('Cotizandose','green', 'black'), // cotizandose
			'02'=>array('Adjudicado','yellow', 'black'), // adjudicado
			'03'=>array('Comprometido','cyan', 'white'), // comprometido
			'99'=>array('Anulado','red', 'white') // anulado
			);
			return $einfo[$code];
		}
		public static function GetDetailEstadoInfo($code) {
			$einfo = array(
			'01'=>array('Generado','black'), // generado
			'04'=>array('Con Requerimiento','fuchsia'), 
			'05'=>array('Cotizandose','green'), 
			'06'=>array('Adjudicado','blue'), 
			'07'=>array('Con Orden','indigo'), 
			'99'=>array('Anulado','red') // anulado
			);
			return $einfo[$code];
		}
		public static function CheckSD($secfun, $depen, $exit=1) {
			if ($secfun ==0 || $depen == 0) {
				exit('Los parametros especificados son incorrectos');
			}
			$usuario = Sys::GetUserId();
			$anipre = Sys::GetPeriodo();
			$isAdmin = Sys::GetUserIsAdmin();
			$q = new SqlQuery("
			SELECT * FROM dbo.usuario_secfun_depen 
			WHERE c_anipre = '$anipre' AND UPPER(c_usuario) = UPPER('$usuario')
			AND c_secfun = '$secfun' AND c_depend = '$depen' 
			", NULL, true);
			if ($q->recordCount > 0 || $isAdmin == 1) {
				return true;
			} else {
				if ($exit==1) {
					exit('Acceso denegado a la Secuencia Funcional.');
				} 
				return false;
			}
		}
		//CRUD operations
		public static function Create() {
			$r = $_POST;
			$anipre = Sys::GetPeriodo();
			$depen = Sys::GetS('cndepen', '');
			$secfun = Sys::GetS('cnsecfun', '');
			// validation
			Sys::Check($anipre, "int|>0", "Año");
			Sys::Check($depen, "int|>0", '', "No se ha especificado la Dependencia");
			Sys::Check($secfun, "int|>0", '', "No se ha especificado la Secuencia");
			Sys::Check($r['descripcion'], "!empty", "Descripcion");
			// process
			Sys::BeginTransaction();
			try {
				$dr = new SqlDataRow('dbo.Cuadro_Mensual');
				$dr->debug = false;
				$dr->Create();
				$dr->Set('c_anipre', $anipre);
				$dr->Set('c_depen', $depen);
				$dr->Set('c_secfun', $secfun);
				$dr->Set('c_mespre', date('m'));
				$dr->Set('n_cuames_desc', $r['descripcion']);
				$dr->Set('d_cuames_fecha', date('d/m/Y H:i:s'));
				$dr->Set('f_cuames_esta', '00');
				$dr->Set('d_cuames_soli', NULL);
				$dr->Set('d_cuames_coti', NULL);
				$dr->Set('d_cuames_adju', NULL);
				$dr->Set('d_cuames_comp', NULL);
				$dr->Set('usuario', Sys::GetUserName());
				$dr->Set('fechamodificacion', date('d/m/Y H:i:s'));
				
				$last = $dr->GetMax('c_cuames', "c_anipre='$anipre'", 0);
				$next = str_pad($last+1, 6, '0', STR_PAD_LEFT);
				$dr->Set('c_cuames', $next);
				
				$dr->Update();
				Sys::CommitTransaction();
				echo "$next";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
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
			self::CheckSD($secfun, $depen);
			$_SESSION['cnsecfun'] = trim($secfun);
			$_SESSION['cndepen'] = trim($depen);
			$_SESSION['cntipo'] = trim($tipo);
			exit('ok');
		}
		public static function Delete() {
			$id = Sys::GetR('id');
			//$dr = new SqlDataRow(self::$tableName);
			//$dr->Read()
			//$dr->Delete($id, true);
			echo "Accion no implementada";
		}
		public static function Anular() {
			$anipre = Sys::GetPeriodo();
			$id = Sys::GetP('id');
			$tipo = Sys::GetS('cntipo', '');
			$dr = new SqlDataRow('dbo.Cuadro_Mensual');
			$dr->Read(array($id, $anipre));
			self::CheckSD($dr->Get('c_secfun'), $dr->Get('c_depen'));
			// validacion
			if ($tipo=='') exit('El Tipo de Cuadro no es Valido');
			if ($dr->Get('f_cuames_esta')!='00') {
				exit("Ya no se puede Anular el cuadro mensual.<br/>Solo es posible Anular cuadros en estado 'Generado'.");
			}
			// process
			Sys::BeginTransaction();
			try {
				$dr->Set('f_cuames_esta', '99');
				$dr->Set('usuario', Sys::GetUserName());
				$dr->Set('fechamodificacion', date('d/m/Y H:i:s'));
				// los detalles tb
				$table = $tipo=='I'?'dbo.cuadro_inversion':'dbo.cuadro_necesi';
				$field = $tipo=='I'?'f_cuainv_esta':'f_cuanec_esta';
				$q = new SqlQuery("UPDATE $table SET $field = '99' WHERE c_anipre = '$anipre' AND c_cuames = '$id'; 
				SELECT 1;", NULL, true);			
				$dr->Update();
				Sys::CommitTransaction();
				echo 'ok';
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function CancelAnular() {
			$anipre = Sys::GetPeriodo();
			$id = Sys::GetP('id');
			$tipo = Sys::GetS('cntipo', '');
			$dr = new SqlDataRow('dbo.Cuadro_Mensual');
			$dr->Read(array($id, $anipre));
			self::CheckSD($dr->Get('c_secfun'), $dr->Get('c_depen'));
			// validacion
			if ($tipo=='') exit('El Tipo de Cuadro no es Valido');
			if ($dr->Get('f_cuames_esta')!='99') {
				exit('El Cuadro no se encuentra Anulado.');
			}
			// process
			Sys::BeginTransaction();
			try {
				$dr->Set('f_cuames_esta', '00');
				$dr->Set('usuario', Sys::GetUserName());
				$dr->Set('fechamodificacion', date('d/m/Y H:i:s'));
				// los detalles tb
				$table = $tipo=='I'?'dbo.cuadro_inversion':'dbo.cuadro_necesi';
				$field = $tipo=='I'?'f_cuainv_esta':'f_cuanec_esta';
				$q = new SqlQuery("UPDATE $table SET $field = '01' WHERE c_anipre = '$anipre' AND c_cuames = '$id'; 
				SELECT 1;", NULL, true);			
				$dr->Update();
				Sys::CommitTransaction();
				echo 'ok';
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function UpdateDescripcion() {
			$r = $_POST;
			$id = $r['id'];
			$anipre = Sys::GetPeriodo();
			// validacion
			Sys::Check($r['value'], "!empty", '', "Especifique alguna descripcion");
			Sys::Check($r['id'], "int|>0", "El numero de cuadro no es valido");
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new SqlDataRow('dbo.cuadro_mensual');
				$dr->debug = false;
				$dr->Read(array($id, $anipre));
				self::CheckSD($dr->Get('c_secfun'), $dr->Get('c_depen'));
				$dr->Set('n_cuames_desc', $r['value']);
				$dr->Set('usuario', Sys::GetUserName());
				$dr->Set('fechamodificacion', date('d/m/Y H:i:s'));
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function DetailDelete() {
			$id = Sys::GetR('id');
			$anipre = Sys::GetPeriodo();
			$tipo = Sys::GetS("cntipo", '');
			Sys::BeginTransaction();
			try {
				$table = $tipo=='I'?'dbo.cuadro_inversion':'dbo.cuadro_necesi';
				$dr = new SqlDataRow($table);
				$dr->Read(array($id, $anipre));
				// check sd
				list($secfun, $depen) = SqlQuery::GetRowValues('dbo.cuadro_mensual', 'c_secfun, c_depen', array($dr->Get('c_cuames'), $anipre), '');
				self::CheckSD($secfun, $depen);
				
				$statecolumn = $tipo=='I'?'f_cuainv_esta':'f_cuanec_esta';
				if ($dr->Get($statecolumn)!='01') { // no es generado
					exit('No se puede eliminar.');
				}
				$table2 = $tipo=='I'?'dbo.cuainv_detall':'dbo.cuanec_detall';
				$cncolumn = $tipo=='I'?'c_cuainv':'c_cuanec';
				SqlQuery::ExecuteNonQuery("DELETE FROM $table2 WHERE c_anipre = '$anipre' AND $cncolumn = '$id'");
				SqlQuery::ExecuteNonQuery("DELETE FROM dbo.caract_cuadro WHERE c_anipre = '$anipre' AND $cncolumn = '$id'");
				$dr->Delete();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function DetailUpdateCantidad() {
			$r = $_POST;
			$id = $r['id']; // ide del detalle: cuanec o cuainv :P
			$mes = $r['mes']; 
			$anipre = Sys::GetPeriodo();
			$tipo = Sys::GetS("cntipo", '');
			// validacion
			Sys::Check($r['value'], "float|>=0", '', "Especifique valor correctamente");
			Sys::Check($r['id'], "int|>0", "El numero de cuadro no es valido");
			Sys::Check($tipo, "!empty", "El Tipo de Cuadro no es valido!.");
			$table = $tipo=='I'?'dbo.CuaInv_Detall':'dbo.CuaNec_Detall';
			// process
			Sys::BeginTransaction();
			try {
				$dr = new SqlDataRow($table);
				$dr->debug = false;
				$dr->Read(array($id, $anipre, $mes));
				$dr->Set($tipo=='I'?'q_cuainv_cant':'q_cuanec_cant', $r['value']);
				$dr->Set('usuario', Sys::GetUserName());
				$dr->Set('fechamodificacion', date('d/m/Y H:i:s'));
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function DetailUpdatePrecio() {
			$r = $_POST;
			$id = $r['id']; // ide del detalle: cuanec o cuainv :P
			$anipre = Sys::GetPeriodo();
			$tipo = Sys::GetS("cntipo", '');
			// validacion
			Sys::Check($r['value'], "float|>=0", '', "Especifique el valor correctamente");
			Sys::Check($r['id'], "int|>0", "El numero de cuadro no es valido");
			Sys::Check($tipo, "!empty", "El Tipo de Cuadro no es valido!.");
			$table = $tipo=='I'?'dbo.Cuadro_Inversion':'dbo.Cuadro_Necesi';
			// process
			Sys::BeginTransaction();
			try {
				$dr = new SqlDataRow($table);
				$dr->debug = false;
				$dr->Read(array($id, $anipre, $mes));
				$dr->Set($tipo=='I'?'q_cuainv_prec':'q_cuanec_prec', $r['value']);
				$dr->Set('usuario', Sys::GetUserName());
				$dr->Set('fechamodificacion', date('d/m/Y H:i:s'));
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function DetailUpdateCarac() {
			$r = $_POST;
			$id = $r['id']; // id del detalle: cuanec o cuainv :P
			$anipre = Sys::GetPeriodo();
			$tipo = Sys::GetS("cntipo", '');
			// validacion
			//Sys::Check($r['value'], "", '', "Especifique las caracteristicas");
			Sys::Check($r['id'], "int|>0", "El numero de cuadro no es valido");
			Sys::Check($tipo, "!empty", "El Tipo de Cuadro no es valido!.");
			if ($tipo == 'I') {
				$estado = SqlQuery::GetValue('dbo.cuadro_inversion.f_cuainv_esta', "c_anipre='$anipre' AND c_cuainv='$id'", '');
				$bstipo = SqlQuery::GetValue('dbo.cuadro_inversion.c_bieser_tipo', "c_anipre='$anipre' AND c_cuainv='$id'", '');
			} else {
				$estado = SqlQuery::GetValue('dbo.cuadro_necesi.f_cuanec_esta', "c_anipre='$anipre' AND c_cuanec='$id'", '');
				$bstipo = SqlQuery::GetValue('dbo.cuadro_necesi.c_bieser_tipo', "c_anipre='$anipre' AND c_cuanec='$id'", '');
			}
			if ($estado=='') {
				exit('No se ha podido determinar el estado del detalle del Cuadro');
			} elseif ($estado!='01'){
				$einfo = self::GetDetailEstadoInfo($estado);
				exit("No se puede modificar si el estado es '".$einfo[0]."'.");
			}
			// process
			Sys::BeginTransaction();
			try {
				$dr = new SqlDataRow('dbo.caract_cuadro');
				$dr->debug = false;
				$pkcolumn = $tipo=='I'?'c_cuainv':'c_cuanec';
				$dr->SetPkey(array($pkcolumn, 'c_anipre'));
				if ($dr->Exists(array($id, $anipre))) {
					$dr->Read(array($id, $anipre));	
				} else {
					$dr->Create();
					$nextnum = str_pad(($dr->GetMax('c_carcua', "c_anipre = '$anipre'", 0)+1), 6, '0', STR_PAD_LEFT);
					$dr->Set('c_carcua', $nextnum);
					$dr->Set('c_anipre', $anipre);
					$dr->Set('c_bieser_tipo', $bstipo);
					$dr->Set($pkcolumn, $id);
				}
				$dr->Set('n_carcua_desc', $r['value']);
				$dr->Set('usuario', Sys::GetUserName());
				$dr->Set('fechamodificacion', date('d/m/Y H:i:s'));
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		public static function AddDetails() {
			$r = $_POST;
			$list = Sys::GetP('list', array()); // list to add
			$id = Sys::GetP('cuames', '');
			$srow = json_decode(rawurldecode(Sys::GetP('srow', '{}')), true);
			$prow = json_decode(rawurldecode(Sys::GetP('prow', '{}')), true);
			
			$secfun = Sys::GetS("cnsecfun", '');
			$depen = Sys::GetS("cndepen", '');
			$tipo = Sys::GetS("cntipo", '');
			// var conditions
			$anipre = Sys::GetPeriodo();
			$mespre = SqlQuery::GetValue('dbo.cuadro_mensual.c_mespre', "c_anipre='$anipre' AND c_cuames = '$id'", '');
			// validacion
			Sys::Check($id, "int|>0", '', 'Especifique el Numero de cuadro correctamente');
				
			// process
			Sys::BeginTransaction();
			try {
				foreach($list as $idbs) {
					$qbs = new SqlQuery("
					SELECT * 
					FROM dbo.anios_bieser 
					WHERE c_anipre = '$anipre' AND c_bieser='$idbs'
					", NULL, true, true);
					$rbs = $qbs->row;
					//var_dump($rbs);
					$table = $tipo=='I'?'dbo.cuadro_inversion':'dbo.cuadro_necesi';
					$dr = new SqlDataRow($table);
					$dr->debug = false;
					$dr->Create();
					$dr->Mixin(array(
					"c_anipre"=>$anipre,
					"c_cuames"=>$id,
					"c_bieser"=>$idbs, // interno
					"c_bieser_clas"=>$rbs['c_bieser_clas'],
					"c_bieser_tipo"=>$rbs['c_bieser_tipo'],  
					"c_bieser_grup"=>$rbs['c_bieser_grup'],
					"c_bieser_cate"=>$rbs['c_bieser_cate'],
					"c_fuefin"=>$srow['c_fuefin'],
					"c_recurs"=>$srow['c_recurs'],
					"c_clapre"=>$srow['c_clapre'].'.00',
					"c_proadq"=>NULL, // eto ta ligao
					"usuario"=>Sys::GetUserName(),
					"fechamodificacion"=>date('d/m/Y H:i:s'),
					"end"=>0 
					));
					if ($tipo=='I') {
						$nextnum = str_pad(($dr->GetMax('c_cuainv', "c_anipre = '$anipre'", 0)+1), 6, '0', STR_PAD_LEFT);
						$dr->Set('c_cuainv', $nextnum);
						$dr->Set('c_secfun', $secfun);
						$dr->Set('c_depend', $depen);
						if ($rbs['c_bieser_tipo']=='02') { // servicio
							// en caso de servicio se considera el precio como cantidad y es siempre 1
							$dr->Set('q_cuainv_prec', 1); 
						} else {
							// en el caso de bienes se jala el costo del bien desde la db
							$dr->Set('q_cuainv_prec', $rbs['q_bieser_cost']);
						}
						$dr->Set('f_cuainv_esta', '01');
					} else { // C: corriente
						$nextnum = str_pad(($dr->GetMax('c_cuanec', "c_anipre = '$anipre'", 0)+1), 6, '0', STR_PAD_LEFT);
						$dr->Set('c_cuanec', $nextnum);
						$dr->Set('c_depen', $depen);
						$dr->Set('c_objeti', $prow['c_objeti']);
						$dr->Set('c_metpoi', $prow['c_metpoi']);
						$dr->Set('c_actpoi', $prow['c_actpoi']);
						if ($rbs['c_bieser_tipo']=='02') { // servicio
							// en caso de servicio se considera el precio como cantidad y es siempre 1
							$dr->Set('q_cuanec_prec', 1); 
						} else {
							// en el caso de bienes se jala el costo del bien desde la db
							$dr->Set('q_cuanec_prec', $rbs['q_bieser_cost']);
						}
						$dr->Set('f_cuanec_esta', '01');
					}
					$dr->Update();
					// sub detall
					$table = $tipo=='I'?'dbo.cuainv_detall':'dbo.cuanec_detall';
					$dr = new SqlDataRow($table);
					$dr->debug = false;
					$dr->Create();
					$dr->Set('c_anipre', $anipre);
					$dr->Set('c_mespre', $mespre);
					$dr->Set('usuario', Sys::GetUserName());
					$dr->Set('fechamodificacion', date('d/m/Y H:i:s'));
					if ($tipo == 'I') {
						$dr->Set('c_cuainv', $nextnum);
						$dr->Set('q_cuainv_cant', 0);
						$dr->Set('q_cuainv_sal', NULL);
						$dr->Set('f_cuainv_esta', NULL);
					} else {
						$dr->Set('c_cuanec', $nextnum);
						$dr->Set('q_cuanec_cant', 0);
						$dr->Set('q_cuanec_sal', NULL);
						$dr->Set('f_cuanec_esta', NULL);
					}
					$dr->Update();
				}
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				if (Config::$Debug)	{
					throw $ex;
				}
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		// query list
	}
?>