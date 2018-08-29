<?php
    define('sys_checksession', false);
    require_once '../../sys.php';
    $title = 'Reporte General de Flujo de Caja';
    $t = Sys::getTimeStamp(date('d/m/Y')); 
    $pv_id = Sys::GetR('pventa_id', '');
    $fechadesde = Sys::GetR('fechadesde', date('d/m/Y'));
    $fechahasta = Sys::GetR('fechahasta', date('d/m/Y'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title><?=ucfirst(Sys::Lower($title))?></title>
<link href="../../css/base.css" rel="stylesheet" type="text/css"/>
<link href="../../css/theme.css" rel="stylesheet" type="text/css"/>
<link rel="stylesheet" type="text/css" href="../../js/jquery.ui/css/redmond/jquery-ui-1.8.18.custom.css" />
<script src="../../js/jquery.ui/js/jquery-1.7.1.min.js" type="text/javascript"></script>
<script src="../../js/jquery.ui/js/jquery-ui-1.8.18.custom.min.js" type="text/javascript"></script>
<script src="../../js/jquery.ui/js/jquery.ui.datepicker-es.js" type="text/javascript"></script>
<style>
* {
    font-size: 8pt;
}
a.quick-menu {
    color: gray;
    text-decoration: none;
}
a.quick-menu:hover {
    color: black;
    text-decoration: underline;
}
.print-bt {
    color: gray;
    font-weight: bold;
}
.print-bt:hover {
    color: black;
    font-weight: bold;
    text-decoration: none;
}
.report-title {
    font-weight: bold;
    font-size: 14pt;
    color: black;
    padding: 5px 0 15px 0;
}
.report-fecha {
    font-weight: bold;
    font-size: 10pt;
    color: gray;
    padding: 5px 0 15px 0;
    margin-top: -10px;
}
.bl-2 {
    border-left: 2px solid gray!important; 
}
.br-2 {
    border-right: 2px solid gray!important; 
}
.br-1 {
    border-right: 1px solid gray!important; 
}
.cell {
    border: 1px solid gray;
}
</style>
<script>
</script>
</head>
<body style="padding: 0 0; margin: 0 0;">
<?php
?>
    <div align="left" class="hidable pd bg-theme" style="border-bottom: 1px solid black; margin-bottom: 5px;">
    <form id="frm" name="frm" method="get">
        <table style="width: 100%;" border="0">
        <tr>
            <td class="pd" style="width: 200px;">
            	<a class="print-bt" href="javascript: window.print();">imprimir</a><?="&nbsp;&nbsp;|&nbsp;&nbsp;"
            	?><a class="print-bt" href="javascript: location.reload();">actualizar</a>
            </td>
            <td class="pd"><span class="hidable c-gray italic hidden">Se recomienda imprimir el reporte en forma horizontal.</span></td>
            <td class="pd ">
                <table>
                <tr>
                    <td><?=$pv_id?>
                    <select id="pventa_id" name="pventa_id" style="margin-top: 0; height: 23px;">
                    <option value="" <?=''==$pv_id?'selected':''?>>- todo -</option>
<?php
$qs = new PgQuery("
    SELECT _q.* FROM (
        SELECT e.establecimiento_id, pv.pventa_id, pv.pventa_desc, e.establecimiento_desc
        FROM public.pventa pv
        JOIN public.establecimiento e on e.establecimiento_id = pv.establecimiento_id
        UNION ALL (
            SELECT e.establecimiento_id, e.establecimiento_id as pventa_id, null as pventa_desc, e.establecimiento_desc FROM public.establecimiento e  
        )
    ) as _q
    ORDER BY _q.pventa_id, _q.pventa_desc", NULL, true, true);
    while ($m = $qs->Read()):
        if ($m['pventa_id']===$pv_id) $pvs = $m; // pventa seleccionado
?>
                    <option value="<?=$m['pventa_id']?>" <?=$m['pventa_id']===$pv_id?'selected':''?>><?=$m['establecimiento_desc']?> <?=is_null($m['pventa_desc'])?'':"/ {$m['pventa_desc']}"?></option>
<?php
endwhile;?>
                    </select>&nbsp;
                    </td>
                    <td>
                        <input id="fechadesde" type="text" name="fechadesde" value="<?=$fechadesde?>" style="height: 19px;"/>
                    </td>
                    <td>
                        <input id="fechahasta" type="text" name="fechahasta" value="<?=$fechahasta?>" style="height: 19px;"/>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
        <script>
            $('#pventa_id').change(function (e) {
                $('#frm').submit();
            });
            $("#fechadesde" ).datepicker({ 
                dateFormat: "dd/mm/yy",
                onSelect: function() {
                    $('#frm').submit();
                } 
            });
            $("#fechahasta" ).datepicker({ 
                dateFormat: "dd/mm/yy",
                onSelect: function() {
                    $('#frm').submit();
                } 
            });
        </script>
    </form>
    </div>
    <div style="padding: 5px 10px;">
        <div>
        <table width="100%">
        <tr>
            <td colspan="2" class="">
            <?=Config::GetOrganizationName()?>
            </td>
            <td align="right">
                <table>
                <tr>
                    <td class="l fs-7">Impreso por <?=Sys::GetUserName()?> el <?=date('d/m/Y H:i:s')?></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="left" colspan="3">
                <div class="report-title"><?=Sys::Upper($title)?></div>
                <div class="report-fecha">Al <?=date('d', $t)?> de <?=Sys::GetMonthName(date('m', $t))?> del <?=date('Y', $t)?></div>
            </td>
        </tr>
        </table>
        </div>
<?php
// one query
    $q = new PgQuery("
    SELECT 
    f.fecha,
    (
        select sum(_q.importe_s)
        from (
            select 
                public.f_get_soles(d.moneda_id, sum(d.registro_det_importe)) as importe_s
            from public.registro_det d
            join public.registro r on r.registro_id = d.registro_id
            join public.pventa pv On pv.pventa_id = r.pventa_id
            left join public.f_ultimafechaderegistro_byfecha_list('$fechadesde', '$fechahasta') as fr ON fr.pventa_id = pv.pventa_id and fr.fecha = f.fecha
            left join public.v_pventa_ultimocierrefecha_id puc ON puc.pventa_id = pv.pventa_id and puc.fecha = fr.registro_fecha
            where d.registro_det_estado = '1'
            --AND (NOT d.clasemov_id IN ('17','18')) -- no faltante ni exedentes
            AND (
              puc.registro_id is null -- no comparar directamente un left join  
              OR r.registro_id <> puc.registro_id -- dif de ultimo cierre
              OR d.clasemov_id IN ('17','18')
            )
            and (
                pv.pventa_cerrarcaja = '1' -- si no cierra caja, movimientos ant fecha
                OR coalesce(r.registro_fechacierre, r.registro_fecha::date) <= fr.registro_fecha
            )
            and (
                pv.pventa_cerrarcaja = '0' -- si cierra caja, solo movimientos de la fecha
                OR coalesce(r.registro_fechacierre, r.registro_fecha::date) = fr.registro_fecha
            )
            and d.registro_det_importe <> 0 -- no monedas sin monto
            and r.pventa_id like '$pv_id%'
            group by d.moneda_id
        ) as _q
    ) as importe_s,
    (
        SELECT ABS(SUM(_q.importe)) 
        FROM ( 
            SELECT 
                public.f_get_soles(d.moneda_id, d.registro_det_importe) as importe
            FROM public.registro r
            JOIN public.registro_det d On d.registro_id = r.registro_id AND d.registro_det_estado = '1' -- solo activos
            WHERE r.pventa_id like '$pv_id%' AND r.tipope_id = '06' AND r.registro_estado = 'P'
            AND r.registro_fecha::date <= f.fecha
            UNION ALL (
                SELECT 
                public.f_get_soles(d.moneda_id, d.registro_det_importe) as importe
                FROM public.registro r
                JOIN public.registro_det d On d.registro_id = r.registro_id AND d.registro_det_estado = '1' -- solo activos
                WHERE r.tipope_id = '07' AND r.registro_estado = 'T'
                AND d.clasemov_id = '12' -- solo cancelacion de prestamo capital
                AND r.registro_id_parent IN ( 
                  SELECT rp.registro_id 
                  FROM public.registro as rp
                  WHERE rp.pventa_id like '$pv_id%' AND rp.tipope_id = '06' AND rp.registro_estado = 'P'
                )
            )
        ) as _q
    ) as importe_pxc,
    (
        select sum(_q.importe)
        from (
            select 
                public.f_get_soles(d.moneda_id, sum(d.registro_det_importe)) as importe
            from public.registro_det d
            join public.registro r on r.registro_id = d.registro_id
            join public.pventa pv On pv.pventa_id = r.pventa_id
            where d.registro_det_estado = '1'
            AND (d.clasemov_id IN ('17')) -- solo excedente
            and ( -- no cuenta si cierra o no caja, pote solo cajas ke cierrar tienes ex o fal
                coalesce(r.registro_fechacierre, r.registro_fecha::date) = f.fecha
            )
            and r.pventa_id like '$pv_id%'
            group by d.moneda_id
        ) as _q
    ) as importe_e,
    (
        select sum(_q.importe)
        from (
            select 
                public.f_get_soles(d.moneda_id, sum(d.registro_det_importe)) as importe
            from public.registro_det d
            join public.registro r on r.registro_id = d.registro_id
            join public.pventa pv On pv.pventa_id = r.pventa_id
            where d.registro_det_estado = '1'
            AND (d.clasemov_id IN ('18')) -- solo faltante
            and ( -- no cuenta si cierra o no caja, pote solo cajas ke cierrar tienes ex o fal
                coalesce(r.registro_fechacierre, r.registro_fecha::date) = f.fecha
            )
            and r.pventa_id like '$pv_id%'
            group by d.moneda_id
        ) as _q
    ) as importe_f,
    (
        SELECT COUNT(*)
        FROM public.registro r 
        WHERE coalesce(r.registro_fechacierre, r.registro_fecha::date) = f.fecha
    ) as operaciones
    FROM public.f_fecha_list('$fechadesde', '$fechahasta') as f
    ORDER BY f.fecha
    ", null, true, true);
?>
<?php
if ($pv_id==''):?>
    <div class="bold"></div>
<?php
else:?>
    <div class="bold fs-12 pd"><?=$pvs['pventa_id']?> <?=$pvs['establecimiento_desc']?> <?=is_null($pvs['pventa_desc'])?'':'- '.$pvs['pventa_desc']?></div>
<?php
endif;?>
        <table class="grid" width="100%">
        <tr>
            <td class="cell bold c">Fecha</td>
            <td class="cell bold c">Nro. Operaciones</td>
            <td class="cell bold c">Sub Total S/.</td>
            <td class="cell bold c">Por Cobrar S/.</td>
            <td class="cell bold c">Total S/.</td>
            <td class="cell bold c">Excedente S/.</td>
            <td class="cell bold c">Faltante S/.</td>
        </tr>
<?php
	while ($d = $q->Read()) {
		//$params = "establecimiento_id={$p['establecimiento_id']}&pventa_id={$p['pventa_id']}&moneda_id={$d['moneda_id']}&fechakardex=$fecha&showgoback=1";
		$total = $d['importe_s'];
		$total += $d['importe_pxc'];
        $dow = Sys::GetDayName(date('w', Sys::getTimeStamp($d['fecha']))+1);
        if ($d['operaciones']==0) {
            $d['importe_s'] = $d['importe_pxc'] = $total = 0;
        }
?>
        <tr align="left" valign="top">
            <td class="cell c"><?=substr($dow, 0, 3)?>, <?=$d['fecha']?></td>
            <td class="cell c"><?=$d['operaciones']?></td>
            <td class="cell r "><?=Sys::NFormat(($d['importe_s']),2,',')?></td>
            <td class="cell r "><?=Sys::NFormat(($d['importe_pxc']),2,',')?></td>
            <td class="cell r bold"><?=Sys::NFormat($total,2,',')?></td>
            <td class="cell r "><?=Sys::NFormat(($d['importe_e']),2,',')?></td>
            <td class="cell r "><?=Sys::NFormat(($d['importe_f']),2,',')?></td>
        </tr> 
<?php
	} 
?>
        </table>
        <script>
        	$('.kardex-link').click(function (e) {
        		window.open('kardexpormonedagen.php?'+$(this).attr('params'), '_self');
        	});
        	$('.cxc-link').click(function (e) {
                window.open('prestamogen.php?'+$(this).attr('params'), '_self');
            });
        </script>
    </div>
</body>
</html>