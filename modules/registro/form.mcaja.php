<?php
	require_once("../../sys.php");
    Sys::DisableClassListen();
    require_once("core.php");
	$module = "registro";
	$prefix = "{$module}_mcaja";
	// default values
	$tipope_id = '02';
	$id = Registro::GetNextId();
	$r['registro_fecha'] = date('d/m/Y H:i:s');
	
	
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
			<td class="pd">Fecha</td>
			<td class="pd">
				<span class="pd c-black fs-9" style="border: 1px solid silver; background: white; display: block; width: 140px;"><?=$r['registro_fecha']?></span>
			</td>
		</tr>
		<tr align="left" valign="top">
            <td class="pd">Tipo</td>
            <td class="pd">
<?php
$qe = new PgQuery("
    select c.* 
    from public.clasemov c
    join public.tipope_clasemov tc ON tc.tipope_id = '$tipope_id' AND tc.clasemov_id = c.clasemov_id  
    order by c.clasemov_id", NULL, true, false);?>
                <select id="<?=$prefix?>_clasemov_id" name="clasemov_id">
<?php
while ($re = $qe->Read()):?>
                    <option value="<?=$re['clasemov_id']?>"><?=$re['clasemov_desc']?></option>
<?php
endwhile; 
?>
                </select>
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
                <input id="<?=$prefix?>_registro_det_importe" class="r" type="text" name="registro_det_importe" value="0.00" style="width: 150px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Descripcion</td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_desc" type="text" name="registro_desc" value="" style="width: 300px;"/>
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
	$('#<?=$prefix?>_clasemov_id').keypress(function (e) {
	    if (e.keyCode==13) $('#<?=$prefix?>_moneda_id').focus();
	});
	$('#<?=$prefix?>_moneda_id').keypress(function (e) {
        if (e.keyCode==13) $('#<?=$prefix?>_registro_det_importe').focus().select();
    });
    $('#<?=$prefix?>_registro_det_importe').keypress(function (e) {
        if (e.keyCode==13) $('#<?=$prefix?>_registro_desc').focus().select();
    }).keyup(function (e) {
        sys.iformat($(this).get(0));
    });
    $('#<?=$prefix?>_registro_desc').keypress(function (e) {
        if (e.keyCode==13) $('#<?=$prefix?>_bt_update').focus();
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
			$.post('modules/<?=$module?>/core.php', 'action=UpdateMCaja&'+params, function (data) {
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
	$('#<?=$prefix?>_clasemov_id').focus();
</script>