<?php
	require_once("../../sys.php");
    Sys::DisableClassListen();
    require_once("core.php");
	$module = "registro";
	$prefix = "{$module}_transferencia";
	// default values
	$tipope_id = '05';
	$id = Registro::GetNextId();
	$r['registro_fecha'] = date('d/m/Y H:i:s');
    $r['registro_fechaentrega'] = date('d/m/Y');
	
	
?>
<script>
</script>
<table class="" width="100%">
<tr>
	<td class="cell" colspan="2">
		<button id="<?=$prefix?>_bt_update" type="button" onclick="<?=$prefix?>_update()">Guardar</button>
		<button type="button" onclick="<?=$prefix?>_cancel()">Cancelar</button>
	</td>
</tr>
<tr>
	<td class="cell" style="width: 570px;" align="left" valign="top">
		<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
		<input type="hidden" name="registro_id" value="<?=$id?>"/>
		<table class="" cellpadding="0" cellspacing="0">
		<tr align="left" valign="top">
            <td class="pd">Nro. Operacion: </td>
            <td class="pd">
                <span class="pd bold c-gray fs-11" style="border: 1px solid silver; background: white; display: block; width: 140px;"><?=$id?></span>
            </td>
        </tr>
		<tr align="left" valign="top">
			<td class="pd">Fecha Envio</td>
			<td class="pd">
				<span class="pd c-black fs-9" style="border: 1px solid silver; background: white; display: block; width: 140px;"><?=$r['registro_fecha']?></span>
			</td>
		</tr>
        <tr align="left" valign="top">
            <td class="pd">Transferir a</td>
            <td class="pd">
<?php
$qe = new PgQuery("
    select p.*, e.establecimiento_desc 
    from public.pventa p
    join public.establecimiento e on e.establecimiento_id = p.establecimiento_id
    where p.pventa_id <> '".Sys::GetUserPVentaId()."'  
    order by p.pventa_id", NULL, true, false);?>
                <select id="<?=$prefix?>_pventa_id_destino" name="pventa_id_destino">
<?php
while ($re = $qe->Read()):?>
                    <option value="<?=$re['pventa_id']?>"><?=$re['establecimiento_desc']?> - <?=$re['pventa_desc']?></option>
<?php
endwhile; 
?>
                </select>
                &nbsp;
                <input id="<?=$prefix?>_registro_retornar" type="checkbox" name="registro_retornar" value="1"/>
                <label for="<?=$prefix?>_registro_retornar" class="pd">con retorno</label>
            </td>
        </tr>
		<tr align="left" valign="top">
            <td class="pd">Moneda</td>
            <td class="pd">
<?php
$qe = new PgQuery("select * from public.moneda order by moneda_id", NULL, true, false);?>
                <select id="<?=$prefix?>_moneda_id" name="moneda_id">
<?php
while ($re = $qe->Read()):?>
                    <option value="<?=$re['moneda_id']?>"><?=$re['moneda_desc']?></option>
<?php
endwhile; 
?>
                </select>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Importe</td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_det_importe" class="r fs-14" type="text" name="registro_det_importe" value="0" style="width: 150px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Fecha Entrega</td>
            <td class="pd">
                <span id="<?=$prefix?>_registro_fechaentrega_container" ></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Descripcion</td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_desc" type="text" name="registro_desc" value="" style="width: 300px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Devolver a</td>
            <td class="pd" id="<?=$prefix?>_regitro_id_retornar_container">
                <select id="<?=$prefix?>_regitro_id_retornar" name="registro_id_retornar">
                    <option value="">- nadie -</option>
                </select>
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
	$('#<?=$prefix?>_pventa_id_destino').keypress(function (e) {
	   if (e.keyCode==13)  $('#<?=$prefix?>_moneda_id').focus();
	}).change(function (e) {
	    var params = $.param({'pventa_id': $(this).val()});
	    $.post('modules/<?=$module?>/form.transferencia.devolver.list.php', params, function (data) {
            $('#<?=$prefix?>_regitro_id_retornar_container').html(data);
        });
	});
	$('#<?=$prefix?>_moneda_id').keypress(function (e) {
       if (e.keyCode==13)  $('#<?=$prefix?>_registro_det_importe').focus().select();
    });
    $('#<?=$prefix?>_registro_det_importe').keypress(function (e) {
       if (e.keyCode==13)  $('#<?=$prefix?>_registro_desc').focus().select();
    }).keyup(function (e){
       sys.iformat($(this).get(0)); 
    });
    new Ext.form.DateField({
        id: '<?=$prefix?>_registro_fechaentrega', renderTo: '<?=$prefix?>_registro_fechaentrega_container', enableKeyEvents: true,
        name: 'registro_fechaentrega', value: '<?=$r['registro_fechaentrega']?>',
        width: 100
    });
    $('#<?=$prefix?>_registro_desc').keypress(function (e) {
       if (e.keyCode==13)  $('#<?=$prefix?>_bt_update').focus();
    });
	// functions
	// data functions
	function <?=$prefix?>_renew() {
		$.post('modules/<?=$module?>/form.php', 'task=new', function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
	function <?=$prefix?>_update() {
		if (confirm('Realmente desea guardar?')) {
			var params = $('#<?=$prefix?>_frm').serialize();
			$.post('modules/<?=$module?>/core.php', 'action=UpdateTransferencia&'+params, function (data) {
				if ($.trim(data)=='ok') {
					sys.message('Se ha guardado satisfactoriamente');
					<?=$module?>_reload_list();
					<?=$prefix?>_cancel();
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
	$('#<?=$prefix?>_pventa_id_destino').focus();
</script>