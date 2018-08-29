<?php
    require_once("../../sys.php");
    Sys::DisableClassListen();
    require_once("core.php");
    $module = "registro";
    $prefix = "{$module}_cierrecaja";
    // default values
    $fechacierre = Sys::GetR('fechacierre', '');
    
    $e_desc = PgQuery::GetValue('public.establecimiento.establecimiento_desc', "establecimiento_id='".Sys::GetUserEstablecimientoId()."'");
    $pv_desc = PgQuery::GetValue('public.pventa.pventa_desc', "pventa_id='".Sys::GetUserPVentaId()."'");
    $cerrar = PgQuery::GetValue('public.pventa.pventa_cerrarcaja', "pventa_id='".Sys::GetUserPVentaId()."'");
    
    if ($fechacierre=='') {
        $q = new PgQuery("
        select _q.*
        from (
            select 
            r.registro_fecha,
            (
              select r2.tipope_id 
              from public.registro r2 
              where r2.pventa_id = '".Sys::GetUserPVentaId()."'
              and (r2.registro_fecha::date = r.registro_fecha OR r2.registro_fechacierre = r.registro_fecha)
              order by r2.registro_id DESC
              limit 1
            ) as tipope_id
            from (
            	select r0.registro_fecha::date 
                from public.registro r0
                join public.pventa pv ON pv.pventa_id = r0.pventa_id AND pv.pventa_cerrarcaja = '1' -- verificar si cierra caja
                where r0.pventa_id = '".Sys::GetUserPVentaId()."'
                and coalesce(r0.registro_fechacierre, r0.registro_fecha::date) = r0.registro_fecha::date
            	group by (r0.registro_fecha::date)
            ) as r
            order by r.registro_fecha DESC 
        ) _q
        where _q.tipope_id <> '99'
        order by _q.registro_fecha DESC
        ", NULL, true, true);
?>
<style>
#<?=$prefix?>_search_box {
    display: block;
    width: 40%;
    height: auto;
    margin: 15% 30%!important;
    border: 1px solid silver;
    position: absolute;
    background: white;
}
.<?=$prefix?>-item {
    padding: 5px 5px 5px 30px;
    display: block;
    color: gray; 
    background: url(img/arrow-m.png) no-repeat 5px 50%;
}     
.<?=$prefix?>-item:hover {
    color: white; /*#00659f;*/
    text-decoration: none; 
    background: #00668E url(img/arrow-m.png) no-repeat 5px 50%;
}
</style>
<div id="<?=$prefix?>_search_box">
    <div class="fs-12 c-gray bold c pd-5" style="border-bottom: 1px solid silver;">Cierres de Caja pendientes</div>
    <div class="pd-5 l" style="margin: 0px 5px; border-bottom: 1px dotted silver;">
        <div class="pd fs-10 c-gray bold"><?=$e_desc?></div>
        <div class="pd fs-10 c-gray bold"><?=$pv_desc?></div>
    </div>
    <div class="pd-5 c">
<?php   while ($c = $q->Read()):?>
        <div class=""><a class="<?=$prefix?>-item" href="javascript:void(0)"><?=$c['registro_fecha']?></a></div>
<?php   endwhile; 
?>
<?php   if ($cerrar=='0'):?>
        <span class="c-red fs-13">No esta configurado para CERRAR CAJA.</span>
<?php   endif;?>
    </div>
</div>
<script>
    $('.<?=$prefix?>-item').click(function (e) {
       var params = $.param({'fechacierre': $(this).html()});
       $.post('modules/<?=$module?>/form.cierrecaja.php', params, function (data) {
            $("#<?=$prefix?>_window-body").html(data);
       });
    });
</script>
<?php
        exit;
    } 
    $id = Registro::GetNextId();
    $r['registro_fecha'] = date('d/m/Y H:i:s');
	
    $qd = new PgQuery("
    SELECT 
    m.moneda_id, 
    m.moneda_desc, 
    m.moneda_simbolo,
    (0) as total
    FROM public.moneda m
    ORDER BY m.moneda_id
    ", NULL, true, true);
    
    $moneda_id_1 = $qd->row['moneda_id'];
?>
<style>
.<?=$prefix?>-text {
    border: 1px solid silver; background: white; display: block; 
    padding: 2px 3px;
    color: black;
}
</style>
<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
<table class="" width="100%">
<tr>
    <td class="cell" colspan="2">
        <button id="<?=$prefix?>_bt_update" type="button" onclick="<?=$prefix?>_update()">Cerrar Caja</button>
        <button type="button" onclick="<?=$prefix?>_cancel()">Cancelar</button>
    </td>
</tr>
<tr>
    <td class="cell" style="width: 570px;" align="left" valign="top">
        <input type="hidden" name="registro_id" value="<?=$id?>"/>
        <table class="" cellpadding="0" cellspacing="0">
        <tr align="left" valign="top">
            <td class="pd" colspan="2">
                <span class="<?=$prefix?>-text fs-11" style="width: auto;"><?=Sys::GetUserPVentaId()?>: <?=$e_desc?> / <?=$pv_desc?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Nro. Operacion: </td>
            <td class="pd">
                <span class="<?=$prefix?>-text fs-11" style="width: 140px;"><?=$id?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Fecha Operacion</td>
            <td class="pd">
                <span class="<?=$prefix?>-text" style="width: 140px;"><?=$r['registro_fecha']?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Fecha de Cierre</td>
            <td class="pd">
                <input type="hidden" name="registro_fechacierre" value="<?=$fechacierre?>"/>
                <span class="<?=$prefix?>-text bold c-black fs-12" style="width: 140px;"><?=$fechacierre?></span>
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
    <td class="cell-head bold r" width="110">Monto</td>
    <td class="cell-head bold c">Verificar (*)</td>
</tr>
<?php
while ($d = $qd->Read()): 
?>
<tr>
    <td class="cell-head"><?=$qd->recNo?></td>
    <td class="cell-head"><?=ucfirst(strtolower($d['moneda_desc']))?></td>
    <td class="cell-head r"><?=$d['moneda_simbolo']?></td>
    <td class="cell-head bold r"><input id="<?=$prefix?>_monto_<?=$d['moneda_id']?>" class="r fs-16" type="text" name="registro_det_importe_<?=$d['moneda_id']?>" value="<?=Sys::NFormat(abs($d['total']))?>" style="width: 200px;" maxlength="20"/></td>
    <td class="cell-head c"><input class="" type="checkbox" name="check_<?=$d['moneda_id']?>" value="1" checked/></td>
</tr>
<?php
	endwhile; 
?>
</table>
<div class="pd c-red">(*) si desmarca esta opcion, se registrara el FALTANTE en la operacion.</div>
</div>
</form>
<script>    
    // controls
    $('#<?=$prefix?>_registro_desc').keypress(function (e) {
        if (e.keyCode==13) {
            $('#<?=$prefix?>_monto_<?=$moneda_id_1?>').focus().select();
        }
    });
<?php
$list = $qd->GetColumnValues('moneda_id');
    foreach ($list as $key => $value):?>
    $('#<?=$prefix?>_monto_<?=$value?>').keypress(function (e) {
        if (e.keyCode==13) {
<?php   if ($key + 1 < count($list)):?>
            $('#<?=$prefix?>_monto_<?=$list[$key+1]?>').focus().select();
<?php   else: 
?>
            $('#<?=$prefix?>_bt_update').focus();
<?php   endif; 
?>
        }
    }).keyup(function(e) {
		sys.iformat($(this).get(0));
	});
<?php
endforeach; 
?>
    // functions
    // data functions
    function <?=$prefix?>_update() {
        if (confirm('Realmente desea guardar?')) {
            var params = $('#<?=$prefix?>_frm').serialize();
            $.post('modules/<?=$module?>/core.php', 'action=UpdateCierreCaja&'+params, function (data) {
                if ($.trim(data)=='ok') {
                    sys.message('Se ha guardado satisfactoriamente');
                    <?=$module?>_reload_list();
                    <?=$prefix?>_cancel();
                } else {
                    sys.alert(data);
					$('#<?=$prefix?>_monto_<?=$moneda_id_1?>').focus().select();
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