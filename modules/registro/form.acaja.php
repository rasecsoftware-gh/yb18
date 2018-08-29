<?php
    require_once("../../sys.php");
    Sys::DisableClassListen();
    require_once("core.php");
    $module = "registro";
    $prefix = "{$module}_acaja";
    // default values
    $id = Registro::GetNextId();
    $r['registro_fecha'] = date('d/m/Y H:i:s');
	
	$e_desc = PgQuery::GetValue('public.establecimiento.establecimiento_desc', "establecimiento_id='".Sys::GetUserEstablecimientoId()."'");
	$pv_desc = PgQuery::GetValue('public.pventa.pventa_desc', "pventa_id='".Sys::GetUserPVentaId()."'");
	// ultimo cierre
	$quc = new PgQuery("
	SELECT public.f_get_ultimocierre_id('".Sys::GetUserPVentaId()."') as registro_id
	", NULL, true, true);
	
	if ($quc->recordCount>0) {
		$uc = $quc->row;
		$uc_id = $quc->row['registro_id'];
	} else {
		$uc = array();
		$uc_id = '';
	}
	
    $qd = new PgQuery("
    SELECT 
    m.moneda_id, 
    m.moneda_desc, 
    m.moneda_simbolo,
    SUM(abs(rd.registro_det_importe)) as total,
    SUM(abs(rd2.registro_det_importe)) as total_f
    FROM public.moneda m
    LEFT JOIN public.registro r ON r.registro_id = '$uc_id'
    LEFT JOIN public.registro_det rd ON rd.registro_id = r.registro_id AND rd.moneda_id = m.moneda_id AND rd.registro_det_estado = '1' AND rd.clasemov_id in ('02') -- solo cierre de caja
    LEFT JOIN public.registro_det rd2 ON rd2.registro_id = r.registro_id AND rd2.moneda_id = m.moneda_id AND rd2.registro_det_estado = '1' AND rd2.clasemov_id in ('18') -- solo faltante de cierre de caja
    GROUP BY m.moneda_id, m.moneda_desc, m.moneda_simbolo
    ORDER BY m.moneda_id
    ", NULL, true, true);
    $moneda_id_1 = $qd->row['moneda_id']; 
?>
<style>
</style>
<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
<table class="" width="100%">
<tr>
    <td class="cell" colspan="2">
        <button id="<?=$prefix?>_bt_update" type="button" onclick="<?=$prefix?>_update()">Aperturar</button>
        <button type="button" onclick="<?=$prefix?>_cancel()">Cancelar</button>
    </td>
</tr>
<tr>
    <td class="cell" style="width: 570px;" align="left" valign="top">
        <input type="hidden" name="registro_id" value="<?=$id?>"/>
        <table class="" cellpadding="0" cellspacing="0">
        <tr align="left" valign="top">
            <td class="pd" colspan="2">
                <span class="input-text fs-11" style="width: auto;"><?=Sys::GetUserPVentaId()?>: <?=$e_desc?> / <?=$pv_desc?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Nro. Operacion: </td>
            <td class="pd">
                <span class="input-text fs-11" style="width: 140px;"><?=$id?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Fecha de Apertura</td>
            <td class="pd">
                <span class="input-text" style="width: 140px;"><?=$r['registro_fecha']?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Ultimo Cierre Caja</td>
            <td class="pd">
                <span class="input-text" style=""><?=$uc_id!=''?($uc['registro_id']." - ".$uc['registro_fecha']):"No hay cierre de caja"?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Descripcion</td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_desc" type="text" name="registro_desc" value="" style="width: 300px;"/>
            </td>
        </tr>
        </table>
    </td>
</tr>
</table>
<div class="grid-container">
<table class="grid" width="100%" style="align: center;">
<tr>
    <td class="cell-head bold" width="20">#</td>
    <td class="cell-head bold">Moneda</td>
    <td class="cell-head bold r">Simbolo</td>
    <td class="cell-head bold r" width="110">Aperturar</td>
</tr>
<?php
while ($d = $qd->Read()): 
?>
<tr>
    <td class="cell-head"><?=$qd->recNo?></td>
    <td class="cell-head"><?=ucfirst(strtolower($d['moneda_desc']))?></td>
    <td class="cell-head r"><?=$d['moneda_simbolo']?></td>
    <td class="cell-head bold r"><input id="<?=$prefix?>_monto_<?=$d['moneda_id']?>" class="<?=$prefix?>-monto r fs-14" type="text" name="registro_det_importe_<?=$d['moneda_id']?>" value="<?=Sys::NFormat(abs($d['total']))?>" style="width: 150px;" maxlength="20" <?=abs($d['total'])!=0?'readonly':''?>/></td>
</tr>
<?php
	endwhile; 
?>
</table>
<script>
<?php
$qd->ResetReader();
    $moneda_id_list = $qd->GetColumnValues('moneda_id');
    foreach ($moneda_id_list as $key=>$value): 
?>
    $('#<?=$prefix?>_monto_<?=$value?>').keypress(function (e) {
       if (e.keyCode == 13) {
<?php   if ($key < (count($moneda_id_list)-1)): 
?>
            $('#<?=$prefix?>_monto_<?=$moneda_id_list[$key+1]?>').focus().select();
<?php   else: 
?>
            $('#<?=$prefix?>_bt_update').focus();
<?php   endif; 
?>
       }
    });
<?php
endforeach; 
?>
</script>
</div>
</form>
<script>    
    // controls
    $('#<?=$prefix?>_registro_desc').keypress(function (e) {
       if (e.keyCode == 13) $('#<?=$prefix?>_monto_<?=$moneda_id_1?>').focus().select();
    });
    $('.<?=$prefix?>-monto').keyup(function (e) {
		sys.iformat($(this).get(0));
	}).change(function () { 
		sys.iformat($(this).get(0)); 
	}).change();
    // functions
    // data functions
    function <?=$prefix?>_update() {
        if (confirm('Realmente desea guardar?')) {
            var params = $('#<?=$prefix?>_frm').serialize();
            $.post('modules/<?=$module?>/core.php', 'action=UpdateApertura&'+params, function (data) {
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
    $('#<?=$prefix?>_registro_desc').focus().select();
</script>