<?php
	require_once("../../sys.php");
	$module = "registro";
	$prefix = "$module";
	// default values
	$id_parent = Sys::GetR('id_parent','');
	
    $q = new PgQuery("
    SELECT r.*,
    tio.tipope_desc,
    c.cliente_desc, c.cliente_numdoc,
    e.establecimiento_desc,
    p.pventa_desc
    FROM public.registro r
    JOIN public.tipope tio ON tio.tipope_id = r.tipope_id
    LEFT JOIN public.cliente c On c.cliente_id = r.cliente_id
    JOIN public.pventa p ON p.pventa_id = r.pventa_id
    JOIN public.establecimiento e ON e.establecimiento_id = p.establecimiento_id
    WHERE r.registro_id::text like '$id_parent'
    ", NULL, true, false);
	$r = $q->row;
	
?>
<style>
.info-body {
	background: #b0d6e0;
}
.info-cell-header {
    background: #00668E;
    border: 1px solid #00446C;
    padding: 3px;
    color: white;
}
.info-cell-text {
    border: 1px solid #00446C;
    padding: 3px;
    color: black;
}
.info-cell-anulado {
    color: red;
    font-weight: bold;
}
.info-cell-pendiente {
    color: purple;
    font-weight: bold;
}
</style>
<div class="info-body" style="padding: 2px;">
<table class="" width="100%">
<?php
if ($r['tipope_id']=='05' && $r['registro_estado']=='P' && !is_null($r['registro_id_parent'])):?>
<tr>
    <td colspan="2">
        <button id="<?=$prefix?>_bt_transfer" type="button">Aceptar Transferencia</button>
    </td>
</tr>
<?php
endif; 
?>
<?php
$q2 = new PgQuery("
    SELECT * 
    FROM public.registro r
    JOIN public.registro_det d ON d.registro_id = r.registro_id AND d.registro_det_estado = '1'  
    WHERE r.registro_id_parent = '{$r['registro_id']}' AND r.registro_estado='P'", NULL, true);
    $r2 = $q2->row;
    if ($r['tipope_id']=='05' && $r['registro_estado']=='P' && $q2->recordCount>0):
?>
<tr>
    <td colspan="2">
        <button id="<?=$prefix?>_bt_anulartransfer" type="button">Aceptar Anulacion de Transferencia</button>
    </td>
</tr>
<?php
endif; 
?>
<tr align="left" valign="top">
    <td class="info-cell-header" width="100">Establecimiento: </td>
    <td class="info-cell-text"><?=$r['establecimiento_desc']?></td>
</tr>
<tr align="left" valign="top">
    <td class="info-cell-header">Punto de Venta: </td>
    <td class="info-cell-text"><?=$r['pventa_desc']?></td>
</tr>
<tr align="left" valign="top">
    <td class="info-cell-header">Nro. Operacion: </td>
    <td class="info-cell-text">
        <span class="c-black bold"><?=$r['registro_id']?></span>
    </td>
</tr>
<tr align="left" valign="top">
	<td class="info-cell-header">Fecha</td>
	<td class="info-cell-text"><?=$r['registro_fecha']?></td>
</tr>
<tr align="left" valign="top">
    <td class="info-cell-header">Tipo Operacion</td>
    <td class="info-cell-text"><?=$r['tipope_desc']?></td>
</tr>
<?php
if(!is_null($r['registro_fechacierre']) && $r['tipope_id'] == '99'):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Fecha Cierre</td>
    <td class="info-cell-text"><?=$r['registro_fechacierre']?></td>
</tr>
<?php
endif; 
?>
<?php
if(trim($r['cliente_id'])!=''):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Cliente</td>
    <td class="info-cell-text"><?=$r['cliente_desc']?></td>
</tr>
<?php
endif; 
?>
<tr align="left" valign="top">
    <td class="info-cell-header">Descripcion</td>
    <td class="info-cell-text"><?=$r['registro_desc']?></td>
</tr>
<?php
	if($r['registro_interes']>0):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Tasa de Interes</td>
    <td class="info-cell-text"><?=Sys::NFormat($r['registro_interes'])?> %
<?php   if($r['registro_imora']>0):?>
        <span class="c-red">(<?=$r['registro_imora']?> %)</span>
<?php   endif; 
?>
    </td>
</tr>
<?php
	endif; 
?>
<?php
	if($r['registro_tcfactor']>0):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Tipo Cambio</td>
    <td class="info-cell-text"><?=Sys::NFormat($r['registro_tcfactor'], 4)?></td>
</tr>
<?php
	endif; 
?>
<?php
	if($r['registro_estado']=='N'):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Estado</td>
    <td class="info-cell-text info-cell-anulado">ANULADO</td>
</tr>
<?php
	endif; 
?>
<?php
if($r['registro_estado']=='P'):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Estado</td>
    <td class="info-cell-text info-cell-pendiente">PENDIENTE</td>
</tr>
<?php
endif; 
?>
<tr align="left" valign="top">
    <td class="info-cell-header">Usuario</td>
    <td class="info-cell-text"><?=strtoupper($r['usuario'])?></td>
</tr>
</table>
<div>
<?//Sys::DisplayInfReg($r['syslog']); 
?>
</div>
</div>
<script>	
	// controls
	$('#<?=$prefix?>_bt_transfer').click(function(e) {
	    var params = $.param({
	        'action': 'AceptarTransferencia',
	        'registro_id': '<?=$r['registro_id']?>'
	    });
	    $.post('modules/<?=$module?>/core.php', params, function (data) {
	       if ($.trim(data) == 'ok') {
	           sys.alert('La transferencia ha sido ACEPTADO satisfactoriamente.');
	           <?=$module?>_reload_list();
	       } else {
	           sys.alert(data);
	       }
	    });
	});
	$('#<?=$prefix?>_bt_anulartransfer').click(function(e) {
	    if (confirm('Realmente desea ANULAR la TRANSFERENCIA?')===false) return;
        var params = $.param({
            'action': 'Anular',
            'registro_id': '<?=$r['registro_id']?>',
            'registro_desc': '.'
        });
        $.post('modules/<?=$module?>/core.php', params, function (data) {
           if ($.trim(data) == 'ok') {
               sys.alert('La transferencia ha sido ANULADO satisfactoriamente.');
               <?=$module?>_reload_list();
           } else {
               sys.alert(data);
           }
        });
    });
	// functions
	// data functions
</script>