<?php
	require_once '../../sys.php';
	$module = "sys_printer";
	$prefix = "{$module}";
	// params
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, Config::GetReportUrl()."state");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$contenido = curl_exec($ch);
	curl_close($ch);
	$serveractive = (trim($contenido)=='ok'); 
	// get server data
	$printers = array();
	if ($serveractive==true) {
		$printer = @file_get_contents(Config::GetReportUrl()."printer");
		if ($printer === false) {
			$printer = '';
		}
		
		$printers = @file_get_contents(Config::GetReportUrl()."printers");
		if ($printers !== false) {
			$printers = explode('|', $printers);
		} else {
			$printers = array();
		}
	}
	//echo $printer;
?>
<div style="padding: 10px; height: 30px;">
	<span class="bold c-gray fs-12" style="display: block; float: left; margin-right: 20px; line-height: 16px;">Configuracion de Impresora</span>
	<span style="float: left;">
		<a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_list(); return false;" title="actualizar lista"></a>
	</span>
</div>
<hr/>
<div class="pd-5">
	<div class="pd">
		<span>Estado del Servidor de Impresion:</span> 
<?php
	if ($serveractive == true):?>
		<span class="bold c-green">ACTIVO</span>
<?php
	else: 
?>
		<span class="bold c-red">INACTIVO</span>
<?php
endif;?>
	</div>
<?php
	$q = new PgQuery("
	SELECT up.*, p.pventa_desc 
	FROM sys.usuario_pventa up
	JOIN public.pventa p ON p.pventa_id = up.pventa_id 
	WHERE usuario_id = ".Sys::GetUserId()." 
	ORDER BY pventa_id
	", NULL, true, true);?>
	<br/>
	<div class="grid-container">
		<table class="grid" width="500">
		<tr>
			<td class="cell-head bold">Punto de Venta</td>
			<td class="cell-head bold">Usar Servidor de Impresion</td>
		</tr>
<?php
	if ($q->recordCount==0):?>
		<tr>
			<td class="cell c-gray" colspan="2">No tienes puntos de venta registrados</td>
		</tr>
<?php
	else: 
?>
<?php
		while ($p = $q->Read()):?>
		<tr class="<?=$prefix?>-tr">
			<td class="cell l"><?=$p['pventa_id']." - ".$p['pventa_desc']?></td>
			<td class="cell c">
				<input class="<?=$prefix?>-pventa-item pointer" type="checkbox" value="<?=$p['usuario_pventa_id']?>" <?=$p['usuario_pventa_psa']=='1'?'checked':''?>/>
			</td>
		</tr>
<?php
		endwhile; 
?>
<?php
	endif; 
?>
		</table>
	</div>
	<br/>
	<div class="pd">Establecer la impresora:</div>
	<form id="<?=$prefix?>_frm_list">
	<div class="grid-container">
		<table class="grid" width="500">
		<tr>
			<td class="cell-head bold">#</td>
			<td class="cell-head bold">Descripcion</td>
			<td class="cell-head bold">&nbsp;</td>
		</tr>
	<?php
	foreach ($printers as $key => $value):?>
		<tr class="<?=$prefix?>-tr">
			<td class="cell l"><?=$key+1?></td>
			<td class="cell l"><?=$value?></td>
			<td class="cell">
				<input class="<?=$prefix?>-printerlist-item pointer" type="radio" name="printerlist" value="<?=$value?>" <?=$value==$printer?'checked':''?>/>
			</td>
		</tr>
	<?php
	endforeach; 
?>
	</table>
	</div>
	</form>
</div>
<script>
$('.<?=$prefix?>-tr').hover(function (e) { $(this).addClass('grid-row-over'); }, function (e) { $(this).removeClass('grid-row-over'); });

$('.<?=$prefix?>-printerlist-item').click(function (e) {
	var printername = $(this).attr('value');
	var params = $.param({
		'action': 'SetPrinter',
		'name': printername
	});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if ($.trim(data) == 'ok') {
			sys.alert("Se ha establecido la impresora '"+printername+"' satisfactoriamente.");
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
});

$('.<?=$prefix?>-pventa-item').click(function (e) {
	var upv_id = $(this).attr('value');
	var value = ($(this).get(0).checked?1:0);
	var params = $.param({
		'action': 'SetPVentaPSA',
		'id': upv_id, 
		'value': value
	});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if ($.trim(data) == 'ok') {
			if (value == 1) {
				sys.alert("Se ha ACTIVADO el Uso del Servidor de Impresion satisfactoriamente.");	
			} else {
				sys.alert("Se ha DESACTIVADO el Uso del Servidor de Impresion satisfactoriamente.");
			}
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
});

function <?=$prefix?>_delete(id) {
	if (!confirm('Realmente desea eliminar?')) return;
	$.post('modules/<?=$module?>/core.php', 'action=Delete&id='+id, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha eliminado satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_reload_list() {
	$.post('modules/<?=$module?>/list.php', 'load_from_session=1', function (data) { $('#<?=$prefix?>_container').html(data); });
};
// Forms
</script>