<?php
	require_once '../../sys.php';
	
	Registro::Listen();
	
	class Registro {  
		public static function Listen() {
			if (!defined("_DISABLED_CLASS_LISTEN_")) {
				Sys::CallClassMethodFromRequest(get_class());
			}
		} 
		public static function GetNextId($pventa=NULL) {
			$esta_id = Sys::GetUserEstablecimientoId();
            $pventa_id = is_null($pventa)?Sys::GetUserPVentaId():$pventa;
            
            $next = PgQuery::GetQueryVal("SELECT public.f_registro_nextid('$pventa_id')", '');
            return $next;
		}
		public static function Anular() {
			$r = $_POST;
            self::CheckCierreExists();
			// validacion
			Sys::Check($r['registro_desc'], '!empty', '', 'Es necesario especificar el motivo de la anulacion.');
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('public.registro');
				$dr->debug = false;
				$id = $r['registro_id'];
				$dr->Read($id);
                $destado = '0';
                if ($dr->Get('registro_estado')=='N') {
                    Sys::RollbackTransaction();
                    exit('El registro ya se encontraba Anulado.');
                }
                if ($dr->Get('tipope_id')=='05' && $dr->Get('registro_estado')=='T' && is_null($dr->Get('registro_id_parent'))) {
                    Sys::RollbackTransaction();
                    exit('No es posible ANULAR la transferencia desde esta ubicacion. Primero debe anular el que recibio la transferencia.');
                }
                // aceptar anular tranfer
                if ($dr->Get('tipope_id')=='05' && $dr->Get('registro_estado')=='P' && is_null($dr->Get('registro_id_parent'))) {
                    $q2 = new PgQuery("
                    SELECT r2.*, d2.registro_det_id 
                    FROM public.registro r
                    JOIN public.registro_det d ON d.registro_id = r.registro_id AND d.registro_det_estado = '1' 
                    JOIN public.registro r2 ON r2.registro_id_parent = r.registro_id AND r2.registro_estado = 'P'
                    JOIN public.registro_det d2 On d2.registro_id = r2.registro_id AND d2.registro_det_estado = '1'
                    WHERE r.registro_id = '$id' AND r.registro_estado='P'", NULL, true);
                    if ($q2->recordCount > 0) {
                        $r2 = $q2->row;
                        $dr2 = new PgDataRow('public.registro');
                        $dr2->Read($r2['registro_id']);
                        $dr2->Set('registro_estado', 'N');
                        $dr2->UpdateLogInfo();
                        $dr2->Update();
                        
                        while ($d2 = $q2->Read()) {
                            $dr2 = new PgDataRow('public.registro_det');
                            $dr2->Read($r2['registro_det_id']);
                            $dr2->Set('registro_det_estado', '0');
                            $dr2->UpdateLogInfo();
                            $dr2->Update();  
                        }
                    } else {
                        Sys::RollbackTransaction();
                        exit('No es posible ANULAR la transferencia desde esta ubicacion. Primero debe anular el que recibio la transferencia.');
                    }
                }
                if ($dr->Get('tipope_id')=='05' && $dr->Get('registro_estado')=='P' && !is_null($dr->Get('registro_id_parent'))) {
                    Sys::RollbackTransaction();
                    exit('No es posible ANULAR la transferencia desde esta ubicacion.');
                }
                if ($dr->Get('tipope_id')=='05' && $dr->Get('registro_estado')=='T' && !is_null($dr->Get('registro_id_parent'))) {
                    $dr->Set('registro_estado', 'P'); // volver a estado PENDIENTE-destino
                    // change other reg - se cambia solo el estado del registro a P
                    $dr2 = new PgDataRow('public.registro');
                    $dr2->Read($dr->Get('registro_id_parent'));
                    $dr2->Set('registro_estado', 'P');
                    $dr2->UpdateLogInfo();
                    $dr2->Update();
                    
                } else {
                    $dr->Set('registro_estado', 'N');
                    $dr->Set('registro_desc', $dr->Get('registro_desc').". ANULADO POR: ".$r['registro_desc']);
                }
				$dr->UpdateLogInfo();
				$dr->Update();
				
				$qd = new PgQuery("SELECT * FROM public.registro_det d WHERE d.registro_id = '$id'", NULL, true, true);
				while ($d = $qd->Read()) {
					$dr = new PgDataRow('public.registro_det');
	                $dr->debug = false;
	                $dr->Read($d['registro_det_id']);
	                $dr->Set('registro_det_estado', $destado);
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
		//CRUD operations
		public static function UpdateMCaja() {
			$r = $_POST;
            self::CheckCierreExists();
			// validacion
			$r['registro_det_importe'] = Sys::NUnformat($r['registro_det_importe']);
			Sys::Check($r['registro_det_importe'], 'float|>0', '', 'El Importe debe ser un numero mayor que 0.');
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('public.registro');
				$dr->debug = false;
				$id = $r['registro_id'];
				$dr->Create();
				$dr->Mixin(array(
				"registro_id"=>$id, 
				"establecimiento_id"=>Sys::GetUserEstablecimientoId(),
				"pventa_id"=>Sys::GetUserPVentaId(),
				"usuario"=>Sys::GetUserName(), 
				"registro_fecha"=>date('d/m/Y H:i:s'),
				"tipope_id"=>'02',
				"cliente_id"=>NULL, 
				"registro_desc"=>$r['registro_desc'], 
				"registro_estado"=>'T',
				"registro_interes"=>0,     
				"end"=>0 
				));
				$dr->UpdateLogInfo();
				$dr->Update();
				
                $tipomov_id = PgQuery::GetValue('public.clasemov.tipomov_id', "clasemov_id = '{$r['clasemov_id']}'");
                $monto = abs($r['registro_det_importe']);
                if ($tipomov_id=='E') {
                    self::CheckSaldo($r['moneda_id'], $monto);
                }
                
				$dr = new PgDataRow('public.registro_det');
                $dr->debug = false;
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "clasemov_id"=>$r['clasemov_id'],
                "moneda_id"=>$r['moneda_id'],
                "registro_det_importe"=>($tipomov_id=='I')?$monto:-abs($monto),
                "registro_det_desc"=>$r['registro_desc'],
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
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
        public static function UpdateCompra() {
            $r = $_POST;
            self::CheckCierreExists();
            // validacion
            $r['registro_det_importe_de'] = Sys::NUnformat($r['registro_det_importe_de']);
            $r['registro_det_importe_a'] = Sys::NUnformat($r['registro_det_importe_a']);
            $r['tipocambio_factor'] = Sys::NUnformat($r['tipocambio_factor']);
            Sys::Check($r['registro_det_importe_de'], 'float|>0', '', 'El Importe debe ser un numero mayor que 0.');
            Sys::Check($r['registro_det_importe_a'], 'float|>0', '', 'El Importe debe ser un numero mayor que 0.');
            $q = new PgQuery("SELECT * FROM public.tipocambio WHERE tipocambio_id = '{$r['tipocambio_id']}'", NULL, true, false);
            if ($q->IsEmpty()) {
                exit('Seleccione un Tipo de Cambio');
            }
            $tc = $q->row;
			Sys::Check($r['cliente_id'], '!empty|!null', '', 'Especifique el Cliente.');
            // process
            Sys::BeginTransaction();
            try {
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $id = $r['registro_id'];
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "usuario"=>Sys::GetUserName(), 
                "registro_fecha"=>date('d/m/Y H:i:s'),
                "tipope_id"=>'03',
                "cliente_id"=>$r['cliente_id'], 
                "registro_desc"=>is_null($r['registro_desc'])?'':$r['registro_desc'], 
                "registro_estado"=>'T',
                "registro_interes"=>0,
                "registro_tcfactor"=>$r['tipocambio_factor'],
                "registro_tcoperador"=>$tc['tipocambio_operador'],           
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                // ingreso
                $dr = new PgDataRow('public.registro_det');
                $dr->debug = false;
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$tc['moneda_id_de'],
                "clasemov_id"=>'05',
                "registro_det_importe"=>$r['registro_det_importe_de'],
                "registro_det_desc"=>'',
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                
                $monto = abs($r['registro_det_importe_a']);
                self::CheckSaldo($tc['moneda_id_a'], $monto);
                // egreso
                $dr = new PgDataRow('public.registro_det');
                $dr->debug = false;
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$tc['moneda_id_a'],
                "clasemov_id"=>'06',
                "registro_det_importe"=>-$monto,
                "registro_det_desc"=>'',
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
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
		public static function UpdateVenta() {
            $r = $_POST;
            self::CheckCierreExists();
            // validacion
            $r['registro_det_importe_de'] = Sys::NUnformat($r['registro_det_importe_de']);
            $r['registro_det_importe_a'] = Sys::NUnformat($r['registro_det_importe_a']);
            $r['tipocambio_factor'] = Sys::NUnformat($r['tipocambio_factor']);
            Sys::Check($r['registro_det_importe_de'], 'float|>0', '', 'El Importe debe ser un numero mayor que 0.');
            Sys::Check($r['registro_det_importe_a'], 'float|>0', '', 'El Importe debe ser un numero mayor que 0.');
            $q = new PgQuery("SELECT * FROM public.tipocambio WHERE tipocambio_id = '{$r['tipocambio_id']}'", NULL, true, false);
            if ($q->IsEmpty()) {
                exit('Seleccione un Tipo de Cambio');
            }
            $tc = $q->row;
            // process
            Sys::BeginTransaction();
            try {
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $id = $r['registro_id'];
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "establecimiento_id"=>Sys::GetUserEstablecimientoId(),
                "pventa_id"=>Sys::GetUserPVentaId(),
                "usuario"=>Sys::GetUserName(), 
                "registro_fecha"=>date('d/m/Y H:i:s'),
                "tipope_id"=>'04',
                "cliente_id"=>$r['cliente_id'], 
                "registro_desc"=>is_null($r['registro_desc'])?'':$r['registro_desc'], 
                "registro_estado"=>'T',
                "registro_interes"=>0,  
				"registro_tcfactor"=>$r['tipocambio_factor'],
				"registro_tcoperador"=>$tc['tipocambio_operador'],    
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                
                // ingreso
                $dr = new PgDataRow('public.registro_det');
                $dr->debug = false;
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$tc['moneda_id_de'],
                "clasemov_id"=>'08',
                "registro_det_importe"=>$r['registro_det_importe_a'],
                "registro_det_desc"=>'',
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                
                // egreso
                
                $monto = abs($r['registro_det_importe_de']);
                self::CheckSaldo($tc['moneda_id_a'], $monto);
                
                $dr = new PgDataRow('public.registro_det');
                $dr->debug = false;
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$tc['moneda_id_a'],
                "clasemov_id"=>'07',
                "registro_det_importe"=>-$monto,
                "registro_det_desc"=>'',
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
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
		public static function UpdatePrestamo() {
            $r = $_POST;
            self::CheckCierreExists();
            // validacion
            $r['registro_det_importe'] = Sys::NUnformat($r['registro_det_importe']);
            $r['registro_interes'] = Sys::NUnformat($r['registro_interes']);
            $r['registro_imora'] = Sys::NUnformat($r['registro_imora']);
            
            Sys::Check($r['cliente_id'], '!empty', '', 'Especifique el cliente.');
            Sys::Check($r['registro_det_importe'], 'float|>0', '', 'El Importe debe ser un numero mayor que 0.');
			Sys::Check($r['registro_interes'], 'float|>=0', '', 'Especifique la Tasa de Interes correctamente.');
			Sys::Check($r['registro_imora'], 'float|>=0', '', 'Especifique la Tasa de Interes Moratorio correctamente.');
            
            // process
            Sys::BeginTransaction();
            try {
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $id = $r['registro_id'];
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "establecimiento_id"=>Sys::GetUserEstablecimientoId(),
                "pventa_id"=>Sys::GetUserPVentaId(),
                "usuario"=>Sys::GetUserName(), 
                "registro_fecha"=>date('d/m/Y H:i:s'),
                "tipope_id"=>'06',
                "cliente_id"=>$r['cliente_id'], 
                "registro_desc"=>$r['registro_desc'], 
                "registro_estado"=>'P',
                "registro_interes"=>$r['registro_interes'],     
                "registro_imora"=>$r['registro_imora'],
                "registro_fechavenci"=>$r['registro_fechavenci'],
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                // egreso
                
                $monto = abs($r['registro_det_importe']);
                self::CheckSaldo($r['moneda_id'], $monto);
                
                $dr = new PgDataRow('public.registro_det');
                $dr->debug = false;
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$r['moneda_id'],
                "clasemov_id"=>'11', // prestamo
                "registro_det_importe"=>-$monto,
                "registro_det_desc"=>'',
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
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
        public static function CancelarPrestamo() {
            $r = $_POST;
            self::CheckCierreExists();
            // validacion
            $r['ti'] = Sys::NUnformat($r['ti']);
            $r['tim'] = Sys::NUnformat($r['tim']);
            $r['monto'] = Sys::NUnformat($r['monto']);
            
            Sys::Check($r['ti'], 'float|>=0', '', 'El valor del interes no es correcto.');
            Sys::Check($r['tim'], 'float|>=0', '', 'El valor del interes moratorio no es correcto.');
            Sys::Check($r['monto'], 'float|>0', '', 'El monto a pagar no es correcto.');
            $total_intereses = floatval($r['ti'])+floatval($r['tim']);
            if ($r['monto'] <= ($total_intereses)) {
                exit("El monto a pagar debe ser mayor a los intereses generados: $total_intereses");
            }
            
            // process
            Sys::BeginTransaction();
            try {
                // get registro info
                $ri = new PgDataRow('public.registro');
                $ri->Read($r['registro_id']);
                
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $nid = self::GetNextId();
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$nid, 
                "establecimiento_id"=>Sys::GetUserEstablecimientoId(),
                "pventa_id"=>Sys::GetUserPVentaId(),
                "usuario"=>Sys::GetUserName(), 
                "registro_fecha"=>date('d/m/Y H:i:s'),
                "tipope_id"=>'07',  // cancelacion de prestamo
                "cliente_id"=>$ri->Get('cliente_id'), 
                "registro_desc"=>$r['registro_desc']||'', 
                "registro_estado"=>'T', // terminado
                "registro_interes"=>$ri->Get('registro_interes'),     
                "registro_imora"=>$ri->Get('registro_imora'),
                "registro_fechavenci"=>NULL,
                "registro_id_parent"=>$r['registro_id'], 
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                // ingreso por cancelacion de prestamo
                $dr = new PgDataRow('public.registro_det');
                $dr->debug = false;
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$nid, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$r['moneda_id'],
                "clasemov_id"=>'12', // cancelacion de prestamo
                "registro_det_importe"=>abs($r['monto']-$total_intereses),
                "registro_det_desc"=>'',
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                // ingreso por interes de prestamo
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$nid, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$r['moneda_id'],
                "clasemov_id"=>'13', // interes
                "registro_det_importe"=>abs($r['ti']),
                "registro_det_desc"=>'',
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                // ingreso por interes moratorio
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$nid, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$r['moneda_id'],
                "clasemov_id"=>'14', // interes mor
                "registro_det_importe"=>abs($r['tim']),
                "registro_det_desc"=>'',
                "registro_det_estado"=>'1',
                "usuario"=>Sys::GetUserName(), 
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                
                $ri->Set('registro_estado', 'T');
                $ri->UpdateLogInfo();
                $ri->Update();
                
                Sys::CommitTransaction();
                echo "ok";
            } catch (Exception $ex) {
                Sys::RollbackTransaction();
                echo "error";
            }
        } // end cancelar prestamo
		public static function UpdateTransferencia() {
            $r = $_POST;
            self::CheckCierreExists();
            // validacion
            $r['registro_det_importe'] = Sys::NUnformat($r['registro_det_importe']);
            
            Sys::Check($r['pventa_id_destino'], '!empty', '', 'Especifique el destino de la transferencia.');
            Sys::Check($r['registro_det_importe'], 'float|>0', '', 'El Importe debe ser un numero mayor que 0.');
            Sys::Check($r['moneda_id'], '!empty', '', 'Especifique la moneda.');
            
            if (Sys::getTimeStamp($r['registro_fecharecep']) < Sys::getTimeStamp(date('d/m/Y'))) {
                exit('La fecha de recepcion no puede ser menor a la fecha de envio.');
            }
            
            // process
            Sys::BeginTransaction();
            try {
                $diferido = date('d/m/Y')!=$r['registro_fecharecep']?'1':'0';
                
                $pventa_desc_dest = PgQuery::GetValue('public.pventa.pventa_desc', "pventa_id = '{$r['pventa_id_destino']}'");
                $esta_id = PgQuery::GetValue('public.pventa.establecimiento_id', "pventa_id = '{$r['pventa_id_destino']}'");
                $esta_desc_dest = PgQuery::GetValue('public.establecimiento.establecimiento_desc', "establecimiento_id = '$esta_id'");
                // transferencia ORIGEN
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $id = $r['registro_id'];
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "usuario"=>Sys::GetUserName(), 
                "registro_fecha"=>date('d/m/Y H:i:s'),
                "tipope_id"=>'05', // transferencia
                "cliente_id"=>NULL, 
                "registro_desc"=>(trim($r['registro_desc'])!=''?$r['registro_desc']." / ":'')."Transferido a $esta_desc_dest - $pventa_desc_dest", 
                "registro_estado"=>'P',
                "registro_interes"=>0,     
                "registro_diferido"=>$diferido,
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                
                // egreso
                
                $monto = abs($r['registro_det_importe']);
                self::CheckSaldo($r['moneda_id'], $monto);
                
                $drd = new PgDataRow('public.registro_det');
                $drd->debug = false;
                $drd->Create();
                $drd->Mixin(array(
                "registro_id"=>$id, 
                "pventa_id"=>Sys::GetUserPVentaId(),
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$r['moneda_id'],
                "clasemov_id"=>'10', // egreso por transferencia
                "registro_det_importe"=>-$monto,
                "registro_det_desc"=>$r['registro_desc'],
                "registro_det_estado"=>'2', // pendiente
                "usuario"=>Sys::GetUserName(), 
                "end"=>0 
                ));
                $drd->UpdateLogInfo();
                $drd->Update();
                
                // transferencia DESTINO
                $pventa_desc_orig = PgQuery::GetValue('public.pventa.pventa_desc', "pventa_id = '".Sys::GetUserPVentaId()."'");
                $esta_id = PgQuery::GetValue('public.pventa.establecimiento_id', "pventa_id = '".Sys::GetUserPVentaId()."'");
                $esta_desc_orig = PgQuery::GetValue('public.establecimiento.establecimiento_desc', "establecimiento_id = '$esta_id'");
                
                $dr2 = new PgDataRow('public.registro');
                $dr2->debug = false;
                $id2 = self::GetNextId($r['pventa_id_destino']);
                $dr2->Create();
                $dr2->Mixin(array(
                "registro_id"=>$id2, 
                "pventa_id"=>$r['pventa_id_destino'],
                "usuario"=>Sys::GetUserName(), 
                "registro_fecha"=>$r['registro_fecharecep'],
                "tipope_id"=>'05', // transferencia
                "cliente_id"=>NULL, 
                "registro_desc"=>"Viene de $esta_desc_orig - $pventa_desc_orig", 
                "registro_estado"=>'P', // pendiente
                "registro_interes"=>0,  
                "registro_id_parent"=>$id,
                "registro_diferido"=>$diferido,   
                "end"=>0 
                ));
                $dr2->UpdateLogInfo();
                $dr2->Update();
                
                // ingreso
                $drd2 = new PgDataRow('public.registro_det');
                $drd2->debug = false;
                $drd2->Create();
                $drd2->Mixin(array(
                "registro_id"=>$id2, 
                "pventa_id"=>$r['pventa_id_destino'],
                "registro_det_fecha"=>date('d/m/Y H:i:s'),
                "moneda_id"=>$r['moneda_id'],
                "clasemov_id"=>'09', // ingreso por transferencia
                "registro_det_importe"=>$monto,
                "registro_det_desc"=>'',
                "registro_det_estado"=>'2', // pendiente
                "usuario"=>Sys::GetUserName(), 
                "end"=>0 
                ));
                $drd2->UpdateLogInfo();
                $drd2->Update();
                
                Sys::CommitTransaction();
                echo "ok";
            } catch (Exception $ex) {
                Sys::RollbackTransaction();
                echo "error";
            }
        } // end transferencia
        public static function AceptarTransferencia() {
            $r = $_POST;
            self::CheckCierreExists();
            // validacion
            Sys::Check($r['registro_id'], '!empty', '', 'Especifique el Nro. de Operacion.');
            
            // process
            Sys::BeginTransaction();
            try {
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $id = $r['registro_id'];
                //echo $id;
                $dr->Read($id);
                if ($dr->Get('registro_estado')=='N') {
                    Sys::RollbackTransaction();
                    exit('El registro se encuentra Anulado.');
                }
                if ($dr->Get('registro_estado')=='T') {
                    Sys::RollbackTransaction();
                    exit('No es posible Aceptar la Transferencia. Verifique el estado de la operacion.');
                }
                if ($dr->Get('registro_tcp')=='1') {
                    $dr->Set('registro_estado', 'P'); // si es en calidad de prestamo, sigue PENDIENTE
                } else {
                    $dr->Set('registro_estado', 'T');   
                }
                $dr->Set('registro_desc', $dr->Get('registro_desc').". TRANSFERENCIA ACEPTADA por: ".Sys::GetUserName().".");
                $dr->UpdateLogInfo();
                $dr->Update();
                
                $qd = new PgQuery("SELECT * FROM public.registro_det d WHERE d.registro_id = '$id'", NULL, true, true);
                while ($d = $qd->Read()) {
                    $drd = new PgDataRow('public.registro_det');
                    $drd->debug = false;
                    $drd->Read($d['registro_det_id']);
                    $drd->Set('registro_det_estado', '1');
                    $drd->UpdateLogInfo();
                    $drd->Update();  
                }
                
                $id2 = $dr->Get('registro_id_parent');
                //echo $id2;
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $dr->Read($id2);
                $dr->Set('registro_estado', 'T');
                $dr->Set('registro_desc', $dr->Get('registro_desc').". TRANSFERENCIA ACEPTADA por: ".Sys::GetUserName().".");
                $dr->UpdateLogInfo();
                $dr->Update();
                
                $qd = new PgQuery("SELECT * FROM public.registro_det d WHERE d.registro_id = '$id2'", NULL, true, true);
                while ($d = $qd->Read()) {
                    $drd = new PgDataRow('public.registro_det');
                    $drd->debug = false;
                    $drd->Read($d['registro_det_id']);
                    $drd->Set('registro_det_estado', '1');
                    $drd->UpdateLogInfo();
                    $drd->Update();  
                }
                Sys::CommitTransaction();
                echo "ok";
            } catch (Exception $ex) {
                Sys::RollbackTransaction();
                echo "error";
            }
        }
        public static function UpdateApertura() {
            $r = $_POST;
            //self::CheckCierreExists();
			// validacion
			$total = 0;
			$qm = new PgQuery("SELECT * FROM public.moneda ORDER BY moneda_id", NULL, true, true);
			while ($m = $qm->Read()) {
				if (array_key_exists("registro_det_importe_{$m['moneda_id']}", $r)) {
				    $r["registro_det_importe_{$m['moneda_id']}"] = Sys::NUnformat($r["registro_det_importe_{$m['moneda_id']}"]);
				    $importe = Sys::NUnformat($r["registro_det_importe_{$m['moneda_id']}"]);
					Sys::Check($importe, 'float|>=0', '', 'El Importe debe ser un numero mayor que 0.');
                    $r["registro_det_importe_{$m['moneda_id']}"] = $importe; // fix set
                    $total += $importe;
				}
			}
            
            if ($total <= 0) {
                exit('No es posible aperturar una caja vacia, debe especificar algun monto.');
            }
            
            // process
            Sys::BeginTransaction();
            try {
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $id = $r['registro_id'];
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "establecimiento_id"=>Sys::GetUserEstablecimientoId(),
                "pventa_id"=>Sys::GetUserPVentaId(),
                "usuario"=>Sys::GetUserName(), 
                "registro_fecha"=>date('d/m/Y H:i:s'),
                "tipope_id"=>'01', // apertura
                "cliente_id"=>NULL, 
                "registro_desc"=>"apertura de caja", 
                "registro_estado"=>'T',
                "registro_interes"=>0,     
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
				
				$qm->ResetReader();
                while ($m = $qm->Read()) {
                	$drd = new PgDataRow('public.registro_det');
	                $drd->debug = false;
	                $drd->Create();
	                $drd->Mixin(array(
	                "registro_id"=>$id, 
	                "pventa_id"=>Sys::GetUserPVentaId(),
	                "registro_det_fecha"=>date('d/m/Y H:i:s'),
	                "moneda_id"=>$m['moneda_id'],
	                "clasemov_id"=>'01', // apertura de caja
	                "registro_det_importe"=>abs($r["registro_det_importe_{$m['moneda_id']}"]),
	                "registro_det_desc"=>'',
	                "registro_det_estado"=>'1',
	                "usuario"=>Sys::GetUserName(), 
	                "end"=>0 
	                ));
	                $drd->UpdateLogInfo();
	                $drd->Update();	
                }
                Sys::CommitTransaction();
                echo "ok";
            } catch (Exception $ex) {
                Sys::RollbackTransaction();
                echo "error";
            }
        } // apertura
        public static function GetSaldoByMoneda($moneda_id, $fecha = NULL) {
            $fecha = is_null($fecha)?date('d/m/Y'):$fecha;
            $saldo = PgQuery::GetQueryVal("
            SELECT SUM(d.registro_det_importe) as total
            FROM public.registro_det d  
            JOIN public.registro r On r.registro_id = d.registro_id
            WHERE d.registro_det_estado = '1' -- activo
            AND r.registro_id <> 'N' -- no anulado
            AND (
                (r.registro_fecha::date = '$fecha' AND r.tipope_id <> '99') -- 99: cierre
                OR r.registro_fechacierre = '$fecha'
            )
            AND d.moneda_id = $moneda_id 
            AND r.pventa_id = '".Sys::GetUserPVentaId()."'
            ", 0);
            return $saldo;
        }
        public static function CheckSaldo($moneda_id, $monto, $fecha = NULL, $exit=true) {
            
            $saldo = self::GetSaldoByMoneda($moneda_id, $fecha);
            if ($saldo < 0) {
                exit('ERROR: El saldo es negativo. Verifique sus operaciones o contactese con el administrador del sistema.');
            }
            $dif = $saldo - abs($monto);
            if ($exit==true) {
                if ($dif < 0) {
                    $moneda_desc = PgQuery::GetValue('public.moneda.moneda_desc', "moneda_id = $moneda_id", $moneda_id);
                    //echo $moneda_desc.":$dif".";"; 
                    exit("No hay saldo disponible en $moneda_desc");    
                }
            } else {
                return ($dif >= 0)?true:false;    
            }
        }
        public static function CheckCierreExists($fecha=NULL, $exit=true) {
            $fecha = is_null($fecha)?date('d/m/Y'):$fecha;
            $esta_id = Sys::GetUserEstablecimientoId();
            $pv_id = Sys::GetUserPVentaId();
            $tipope = PgQuery::GetQueryVal("
            SELECT r.tipope_id
            FROM public.registro r 
            WHERE r.registro_estado = 'T' -- terminado
            AND r.tipope_id IN ('99', '01')
            AND r.pventa_id = '$pv_id'
            AND (r.registro_fecha::date = '$fecha' OR r.registro_fechacierre = '$fecha')
			ORDER BY r.registro_fecha DESC
            ", '');
            if ($tipope == '99' && $exit == true) {
                exit("La CAJA esta cerrada. No es posible realizar mas operaciones.");
            }
            return ($count>0?true:false);
        }
        public static function UpdateCierreCaja() {
            $r = $_POST;
            //self::CheckCierreExists();
            $cerrar = PgQuery::GetValue('public.pventa.pventa_cerrarcaja', "pventa_id='".Sys::GetUserPVentaId()."'");
            if ($cerrar=='0') {
                die("Este Punto de venta NO cierra caja.");
            }
            // validacion
            $qm = new PgQuery("SELECT * FROM public.moneda ORDER BY moneda_id", NULL, true, true);
            while ($m = $qm->Read()) {
                if (array_key_exists("registro_det_importe_{$m['moneda_id']}", $r)) {
                    $monto = Sys::NUnformat($r["registro_det_importe_{$m['moneda_id']}"]);
                    Sys::Check($monto, 'float|>=0', '', "El Importe en {$m['moneda_desc']} debe ser un numero mayor que 0.");
                    if (Sys::GetP("check_{$m['moneda_id']}", '0')=='1') {
                        if (self::GetSaldoByMoneda($m['moneda_id'], $r['registro_fechacierre']) > $monto) { // si saldo mayor que monton
                            $str = "El monto ingresado en {$m['moneda_desc']} es menor que el total en CAJA.";
                            exit($str);
                        }
                    }
                }
            }
            
            // process
            Sys::BeginTransaction();
            try {
                $dr = new PgDataRow('public.registro');
                $dr->debug = false;
                $id = $r['registro_id'];
                $dr->Create();
                $dr->Mixin(array(
                "registro_id"=>$id, 
                "establecimiento_id"=>Sys::GetUserEstablecimientoId(),
                "pventa_id"=>Sys::GetUserPVentaId(),
                "usuario"=>Sys::GetUserName(), 
                "registro_fecha"=>date('d/m/Y H:i:s'),
                "registro_fechacierre"=>$r['registro_fechacierre'],
                "tipope_id"=>'99', // cierre caja
                "cliente_id"=>NULL, 
                "registro_desc"=>"[cierre de caja del {$r['registro_fechacierre']}].{$r['registro_desc']}", 
                "registro_estado"=>'T',
                "registro_interes"=>0,     
                "end"=>0 
                ));
                $dr->UpdateLogInfo();
                $dr->Update();
                
                $qm->ResetReader();
                while ($m = $qm->Read()) {
                    $monto = Sys::NUnformat($r["registro_det_importe_{$m['moneda_id']}"]);
                    $saldo = self::GetSaldoByMoneda($m['moneda_id'], $r['registro_fechacierre']);
                    
                    $drd = new PgDataRow('public.registro_det');
                    $drd->debug = false;
                    $drd->Create();
                    $drd->Mixin(array(
                    "registro_id"=>$id, 
                    "pventa_id"=>Sys::GetUserPVentaId(),
                    "registro_det_fecha"=>date('d/m/Y H:i:s'),
                    "moneda_id"=>$m['moneda_id'],
                    "clasemov_id"=>'02', // cierre de caja
                    "registro_det_importe"=>-abs($monto),
                    "registro_det_desc"=>'',
                    "registro_det_estado"=>'1',
                    "usuario"=>Sys::GetUserName(), 
                    "end"=>0 
                    ));
                    $drd->UpdateLogInfo();
                    $drd->Update();
                    
                    $dif = ($saldo - $monto); // excedente o faltante
                    if ($dif>0) { //faltante
                        $drd->Create();
                        $drd->Mixin(array(
                        "registro_id"=>$id, 
                        "pventa_id"=>Sys::GetUserPVentaId(),
                        "registro_det_fecha"=>date('d/m/Y H:i:s'),
                        "moneda_id"=>$m['moneda_id'],
                        "clasemov_id"=>'18', // faltante
                        "registro_det_importe"=>-abs($dif), // negativo para hacer ZERO en el SALDO
                        "registro_det_desc"=>'',
                        "registro_det_estado"=>'1',
                        "usuario"=>Sys::GetUserName(), 
                        "end"=>0 
                        ));
                        $drd->UpdateLogInfo();
                        $drd->Update();
                    } elseif ($dif<0) { // excedente
                        $drd->Create();
                        $drd->Mixin(array(
                        "registro_id"=>$id, 
                        "pventa_id"=>Sys::GetUserPVentaId(),
                        "registro_det_fecha"=>date('d/m/Y H:i:s'),
                        "moneda_id"=>$m['moneda_id'],
                        "clasemov_id"=>'17', // excedente
                        "registro_det_importe"=>abs($dif), // + para SUMAR el monto - del cierre de caja EXCEDENTE
                        "registro_det_desc"=>'',
                        "registro_det_estado"=>'1',
                        "usuario"=>Sys::GetUserName(), 
                        "end"=>0 
                        ));
                        $drd->UpdateLogInfo();
                        $drd->Update();
                    }
                }
                Sys::CommitTransaction();
                echo "ok";
            } catch (Exception $ex) {
                Sys::RollbackTransaction();
                echo "error";
            }
        } // cierre caja
		public static function ClienteGetNextId() {
			$esta_id = Sys::GetUserEstablecimientoId();
            
            $q = new PgQuery("
            SELECT MAX(SUBSTR(cliente_id, 4, 7)::INTEGER) as max 
            FROM cliente 
            WHERE establecimiento_id = '$esta_id'
            ", NULL, true, false);
            if ($q->IsEmpty()) {
                // 0101-00000001
                return "$esta_id-0000001";
            } else {
                $next = $q->row['max'] + 1;
                return "$esta_id-".str_pad($next, 7, '0', STR_PAD_LEFT);
            }
		}
		public static function AddCliente() {
			$cliente_desc = Sys::GetP('cliente_desc');
			$cliente_numdoc = Sys::GetP('cliente_numdoc');
			
			$filter_numdoc = trim($cliente_numdoc);
			if (trim($cliente_numdoc)!='') {
				if (strlen(trim($cliente_numdoc)) < 8) {
					exit('Especifique correctamente el DNI o RUC.');
				}
				Sys::Check($cliente_numdoc, 'int|>0', '', 'Especifique correctamente el DNI o RUC.');
			} else {
				$filter_numdoc = '%%';
			}
			
			$q = new PgQuery("
			SELECT * 
			FROM public.cliente c 
			WHERE (".PgQuery::GetFilterSql($cliente_desc, "(c.cliente_desc ilike '%:filter%')").")
			AND c.cliente_numdoc like '$filter_numdoc'
			", NULL, true);
			
			if ($q->recordCount>0) {
				exit("El cliente posiblemente ya exista. Especifique mas datos.");
			}
			$dr = new PgDataRow('public.cliente');
			$id = self::ClienteGetNextId();
			$dr->Create();
			$dr->Set('cliente_id', $id);
			$dr->Set('cliente_desc', Sys::Upper($cliente_desc));
			$dr->Set('cliente_numdoc', $cliente_numdoc);
            $dr->Set('establecimiento_id', $id);
			$dr->UpdateLogInfo();
			$dr->Update();
			echo "$id";
		}
		public static function Delete() {
			$id = Sys::GetR('id');
			$dr = new PgDataRow('public.registro2');
			$dr->Delete($id, true);
			echo "ok";
		}
		public static function UpdateTipoCambio() {
			$r = $_POST;
			// validacion
			Sys::Check($r['tipocambio_factor'], 'float|>0', '', 'El Factor debe ser un numero mayor que 0.');
			
			// process
			Sys::BeginTransaction();
			try {
				$dr = new PgDataRow('public.tipocambio');
				$dr->debug = false;
				$id = $r['tipocambio_id'];
				$dr->Read($id);
				$dr->Set("tipocambio_factor", $r['tipocambio_factor']);
				$dr->UpdateLogInfo();
				$dr->Update();
				Sys::CommitTransaction();
				echo "ok";
			} catch (Exception $ex) {
				Sys::RollbackTransaction();
				echo "error";
			}
		}
		// search
		public static function SearchCliente() {
            $filter = Sys::GetR('term', '');
            $q = new PgQuery("
            SELECT cliente_id as id, cliente_desc as descripcion, cliente_numdoc
            FROM public.cliente
            WHERE (".PgQuery::GetFilterSql($filter, "(cliente_desc ILIKE '%:filter%' OR cliente_numdoc like '%:filter%')").")
            ORDER BY cliente_desc LIMIT 200", NULL, true, false);
            echo $q->ToJson();  
        }
        public static function SearchPrestamo() {
            $filter = Sys::GetR('term', '');
            $q = new PgQuery("
            SELECT r.registro_id as id, r.registro_id || ' | ' || c.cliente_desc as descripcion 
            FROM public.registro r
            JOIN public.cliente c ON c.cliente_id = r.cliente_id
            WHERE r.tipope_id = '06' AND NOT r.registro_estado IN ('T', 'N')
            AND (r.registro_id like '%$filter%' OR c.cliente_desc ilike '%$filter%' OR c.cliente_numdoc like '%$filter%')
            ORDER BY r.registro_id LIMIT 100", NULL, true, true);
            echo $q->ToJson();  
        }
	}
?>