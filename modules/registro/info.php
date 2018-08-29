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
        <button id="<?=$prefix?>_bt_transfer" type="button" class="c-red bold fs-10">Aceptar Transferencia</button>
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
    <td class="info-cell-header" width="100">Punto Venta: </td>
    <td class="info-cell-text"><?=$r['establecimiento_desc']?> / <?=$r['pventa_desc']?></td>
</tr>
<tr align="left" valign="top">
    <td class="info-cell-header">Operacion: </td>
    <td class="info-cell-text">
        <span class="c-black bold"><?=$r['registro_id']?></span>: <span class="bold" style="color: black;"><?=$r['tipope_desc']?></span>
    </td>
</tr>
<tr align="left" valign="top">
	<td class="info-cell-header">Fecha</td>
	<td class="info-cell-text"><?=$r['registro_fecha']?></td>
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
<?php
if($r['registro_diferido']=='1'):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Diferido al:</td>
    <td class="info-cell-text">
<span class="c-gray bold fs-12">
<?php   if (is_null($r['registro_id_parent'])): // es transferencia origen: mostrar fecha destino?>
    <?=PgQuery::GetValue('public.registro.registro_fecha', "registro_id_parent = '{$r['registro_id']}'")?>
<?php   else:?>
    <?=$r['registro_fecha']?>
<?php
endif;?></span>    
    </td>
</tr>
<?php
endif; 
?>
<?php
if(trim($r['registro_id_parent'])!=''):?>
<tr align="left" valign="top">
    <td class="info-cell-header"><?php

    switch ($r['tipope_id']) {
    case '05': echo 'Viene de:'; break; // transferencia
    case '07': echo 'Prestamo:'; break; // cancelacion de prestamo
    default: echo 'Referencia:';
    } 
?></td>
    <td class="info-cell-text">
<?php
    $qtr = new PgQuery("
    SELECT 
        r.registro_id, r.registro_fecha::date as registro_fecha,
        pv.pventa_desc,
        e.establecimiento_desc,
        (
            SELECT m.moneda_simbolo 
            FROM public.registro_det d
            JOIN public.moneda m ON m.moneda_id = d.moneda_id
            WHERE d.registro_id = r.registro_id
            LIMIT 1 
        ) as moneda_simbolo,
        (
            SELECT abs(SUM(d.registro_det_importe)) FROM public.registro_det d WHERE d.registro_id = r.registro_id
        ) as total
    FROM public.registro r 
    JOIN public.pventa pv ON pv.pventa_id = r.pventa_id
    JOIN public.establecimiento e On e.establecimiento_id = pv.establecimiento_id
    WHERE r.registro_id = '{$r['registro_id_parent']}'", NULL, true, true);
    if ($qtr->recordCount>0):
        $tr = $qtr->row;
?>
    <span class="c-black bold">
<?=$tr['establecimiento_desc'].' - '.$tr['pventa_desc'].'. '?></br><?=$tr['registro_id'].': '.$tr['registro_fecha'].'. '.$tr['moneda_simbolo'].' '.Sys::NFormat($tr['total'])?>         
    </span>
<?php
endif;?>    
    </td>
</tr>
<?php
endif; 
?>
<?php
if(trim($r['registro_id_main'])!=''):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Con Retorno a:</td>
    <td class="info-cell-text">
<?php
    $qtr = new PgQuery("
    SELECT 
        r.registro_id, r.registro_fecha::date as registro_fecha,
        pv.pventa_desc,
        e.establecimiento_desc,
        (
            SELECT m.moneda_simbolo 
            FROM public.registro_det d
            JOIN public.moneda m ON m.moneda_id = d.moneda_id
            WHERE d.registro_id = r.registro_id
            LIMIT 1 
        ) as moneda_simbolo,
        (
            SELECT abs(SUM(d.registro_det_importe)) FROM public.registro_det d WHERE d.registro_id = r.registro_id
        ) as total
    FROM public.registro r 
    JOIN public.pventa pv ON pv.pventa_id = r.pventa_id
    JOIN public.establecimiento e On e.establecimiento_id = pv.establecimiento_id
    WHERE r.registro_id = '{$r['registro_id_main']}'", NULL, true, true);
    if ($qtr->recordCount>0):
        $tr = $qtr->row;
?>
    <span class="c-black bold">
<?=$tr['establecimiento_desc'].' - '.$tr['pventa_desc'].'. '?></br><?=$tr['registro_id'].': '.$tr['registro_fecha'].'. '.$tr['moneda_simbolo'].' '.Sys::NFormat($tr['total'])?>         
    </span>
<?php
endif;?>    
    </td>
</tr>
<?php
endif; 
?>
<?php
if(trim($r['registro_id_parent'])=='' && $r['tipope_id']=='05'):?>
<tr align="left" valign="top">
    <td class="info-cell-header">Transferido a:</td>
    <td class="info-cell-text">
<?php
    $qtr = new PgQuery("
    SELECT 
        r.registro_id, r.registro_fecha::date as registro_fecha,
        pv.pventa_desc,
        e.establecimiento_desc,
        (
            SELECT m.moneda_simbolo 
            FROM public.registro_det d
            JOIN public.moneda m ON m.moneda_id = d.moneda_id
            WHERE d.registro_id = r.registro_id
            LIMIT 1 
        ) as moneda_simbolo,
        (
            SELECT abs(SUM(d.registro_det_importe)) FROM public.registro_det d WHERE d.registro_id = r.registro_id
        ) as total
    FROM public.registro r 
    JOIN public.pventa pv ON pv.pventa_id = r.pventa_id
    JOIN public.establecimiento e On e.establecimiento_id = pv.establecimiento_id
    WHERE r.registro_id_parent = '{$r['registro_id']}'", NULL, true, true);
    if ($qtr->recordCount>0):
        $tr = $qtr->row;
?>
    <span class="c-black bold">
<?=$tr['establecimiento_desc'].' - '.$tr['pventa_desc'].'. '?></br><?=$tr['registro_id'].': '.$tr['registro_fecha'].'. '.$tr['moneda_simbolo'].' '.Sys::NFormat($tr['total'])?>         
    </span>
<?php
endif;?>    
    </td>
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
		sys.confirm('Realmente desea aceptar la transferencia?', 'Transferencia', function (bt) {
			if (bt=='yes') {
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