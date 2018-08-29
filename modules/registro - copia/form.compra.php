<?php
	require_once("../../sys.php");
    Sys::DisableClassListen();
    require_once("core.php");
	$module = "registro";
	$prefix = "{$module}_compra";
	// default values
	$id = Registro::GetNextId();
	$r['registro_fecha'] = date('d/m/Y H:i:s');
	$r['cliente_id'] = "00-0000000";
    $r['cliente_desc'] = PgQuery::GetValue('public.cliente.cliente_desc', "cliente_id = '{$r['cliente_id']}'")
?>
<script>
</script>
<table class="" width="100%">
<tr>
	<td class="cell" >
		<button id="<?=$prefix?>_bt_guardar" type="button" onclick="<?=$prefix?>_update()">Guardar</button>
		<button type="button" onclick="<?=$prefix?>_cancel()">Cancelar</button>
	</td>
	<td>
		<input id="<?=$prefix?>_print_flag" type="checkbox" checked/>
		<label for="<?=$prefix?>_print_flag" class="pd">Imprimir inmediatamente</label>
	</td>
</tr>
<tr>
	<td class="cell" style="" align="left" valign="top" colspan="2">
		<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
		<input type="hidden" name="registro_id" value="<?=$id?>"/>
		<input id="<?=$prefix?>_moneda_id_de" type="hidden" name="moneda_id_de" value="0"/>
		<table class="" cellpadding="0" cellspacing="0">
		<tr align="left" valign="top">
            <td class="pd" width="120">Nro. Operacion: </td>
            <td class="pd">
                <span class="pd bold c-gray fs-11" style="border: 1px solid silver; background: white; display: block; width: 140px;"><?=$id?></span>
            </td>
        </tr>
		<tr align="left" valign="top">
			<td class="pd">Fecha</td>
			<td class="pd">
				<span class="pd c-black fs-9" style="border: 1px solid silver; background: white; display: block; width: 140px;"><?=$r['registro_fecha']?></span>
			</td>
		</tr>
		<tr align="left" valign="top">
            <td class="pd">Tipo de Cambio</td>
            <td class="pd">
<?php
$qe = new PgQuery("
SELECT tc.tipocambio_id, tc.moneda_id_de, tc.moneda_id_a, tc.tipocambio_factor, tc.tipocambio_operador,
mde.moneda_desc as moneda_desc_de,
ma.moneda_desc as moneda_desc_a,
mde.moneda_simbolo as moneda_simbolo_de,
ma.moneda_simbolo as moneda_simbolo_a
FROM public.tipocambio tc
JOIN public.moneda mde on mde.moneda_id = tc.moneda_id_de
JOIN public.moneda ma on ma.moneda_id = tc.moneda_id_a
WHERE tc.tipope_id = '03' and tc.establecimiento_id = '".Sys::GetUserEstablecimientoId()."'  
ORDER BY tc.tipocambio_id
", NULL, true, false);
	$tc_selected = array();
	if ($qe->recordCount>0) {
		$tc_selected = $qe->row;
	}
?>
                <select class="fs-10" id="<?=$prefix?>_tipocambio_id" name="tipocambio_id" style="width: 350px; height: 25px;"> 
<?php
while ($re = $qe->Read()):?>
                    <option value="<?=$re['tipocambio_id']?>" rdata="<?=rawurlencode($qe->GetRowAsJson())?>"><?=$re['moneda_desc_de']." &rArr; ".$re['moneda_desc_a']?></option>
<?php
endwhile; 
?>
                </select>
                &nbsp;&nbsp;&nbsp;
                <!---->
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Cantidad <span id="<?=$prefix?>_simbolo_de" class="bold fs-10"><?=$tc_selected['moneda_simbolo_de']?></span></td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_det_importe_de" class="fs-14 r" type="text" name="registro_det_importe_de" value="0" style="width: 150px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Cotizacion</td>
            <td class="pd">
                <input id="<?=$prefix?>_factor" class="fs-14 r" type="text" name="tipocambio_factor" value="<?=$tc_selected['tipocambio_factor']?>" style="width: 150px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Importe <span id="<?=$prefix?>_simbolo_a" class="bold fs-10"><?=$tc_selected['moneda_simbolo_a']?></span></td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_det_importe_a" class="fs-14 r" type="text" name="registro_det_importe_a" value="0" style="width: 150px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd" colspan="2"><hr/></td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Efectivo</td>
            <td class="pd">
                <input id="<?=$prefix?>_efectivo" class="fs-14 r c-green" type="text" name="efectivo" value="0" style="width: 150px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Vuelto</td>
            <td class="pd">
                <input id="<?=$prefix?>_vuelto" class="fs-14 r c-red" type="text" name="vuelto" value="0" style="width: 150px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd" colspan="2"><hr/></td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Cliente</td>
            <td class="pd">
                <input id="<?=$prefix?>_cliente_id" type="hidden" name="cliente_id" value="<?=$r['cliente_id']?>"/>
                <input id="<?=$prefix?>_cliente_desc" class="" type="text" name="cliente_desc" value="<?=$r['cliente_desc']?>" style="width: 250px;"/>
            </td>
        </tr>
		</table>
		</form>
	</td>
</tr>
</table>
<div>
<?=Sys::DisplayInfReg($r['syslog']); 
?>
</div>
<script>	
	// controls
	var count = 0;
	var tcdata = <?=json_encode($tc_selected)?>;
	// functions
	
	function <?=$prefix?>_set_tc_values(d) {
		$('#<?=$prefix?>_factor').val(d.tipocambio_factor);
		$('#<?=$prefix?>_simbolo_de').html(d.moneda_simbolo_de);
		$('#<?=$prefix?>_simbolo_a').html(d.moneda_simbolo_a);
	};
	
	$('#<?=$prefix?>_tipocambio_id').change(function () {
		eval("tcdata = "+unescape( $($(this).children()[$(this).get(0).selectedIndex]).attr('rdata')));
		console.info(tcdata);
		<?=$prefix?>_set_tc_values(tcdata);
		$('#<?=$prefix?>_registro_det_importe_de').focus().select();
	}).keypress(function (e) {
		//console.info(e);
		if (e.keyCode == 13) {
			$('#<?=$prefix?>_registro_det_importe_de').focus().select();
		} else if (e.keyCode == 9) {
			//$('#<?=$prefix?>_registro_det_importe_de').focus().select();
		}
	});
	
	$('#<?=$prefix?>_registro_det_importe_de').keyup(function (e) {
	    var vde = parseFloat($(this).val());
	    var f = parseFloat($('#<?=$prefix?>_factor').val());
	    var ope = tcdata.tipocambio_operador;
	    var evalstr = "var v = vde "+ope+" f";
	    console.info(evalstr);
		eval(evalstr);
		v = Math.round(v * 100) / 100; 
		$('#<?=$prefix?>_registro_det_importe_a').val(v);
	}).change(function () { 
		$('#<?=$prefix?>_efectivo').val($(this).val()); 
	}).keypress(function (e) {
		if (e.keyCode == 13) {
			$('#<?=$prefix?>_efectivo').val($(this).val()).focus().select();
		} else if (e.keyCode == 9) {
			$('#<?=$prefix?>_efectivo').val($(this).val());
		} 
	});
	
	$('#<?=$prefix?>_efectivo').keyup(function (e) {
		var v = $(this).val() - $('#<?=$prefix?>_registro_det_importe_de').val();
		$('#<?=$prefix?>_vuelto').val(v);
	}).keypress(function (e) {
		if (e.keyCode == 13) {
			$('#<?=$prefix?>_bt_guardar').focus();
		} else if (e.keyCode == 9) {
			e.preventDefault();
			$('#<?=$prefix?>_bt_guardar').focus();
		} 
	});
	
	// data functions
	function <?=$prefix?>_renew() {
		$.post('modules/<?=$module?>/form.php', 'task=new', function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
	function <?=$prefix?>_update() {
		if (confirm('Realmente desea guardar?')) {
			var params = $('#<?=$prefix?>_frm').serialize();
			$.post('modules/<?=$module?>/core.php', 'action=UpdateCompra&'+params, function (data) {
				if ($.trim(data)=='ok') {
					sys.message('Se ha guardado satisfactoriamente');
					<?=$module?>_reload_list();
					<?=$prefix?>_cancel();
					<?=$module?>_print('<?=$id?>');
				} else {
					sys.alert(data);
				}
			});
		}
	};
	function <?=$prefix?>_cancel() {
		Ext.getCmp('<?=$prefix?>_window').close();
	};
	// init
	$('#<?=$prefix?>_tipocambio_id').focus();
</script>