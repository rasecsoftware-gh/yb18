<?php
    define('sys_checksession', false);
    require_once '../../sys.php';
    $title = 'Reporte General de Caja';
    $t = Sys::getTimeStamp(date('d/m/Y')); 
    $pv_id = Sys::GetR('pventa_id', '');
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
                    <td>
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
    while ($m = $qs->Read()):?>
                    <option value="<?=$m['pventa_id']?>" <?=$m['pventa_id']===$pv_id?'selected':''?>><?=$m['establecimiento_desc']?> <?=is_null($m['pventa_desc'])?'':"/ {$m['pventa_desc']}"?></option>
<?php
endwhile;?>
                    </select>&nbsp;
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
// monedas y totales por columna
    $qm = new PgQuery("
    select 
        d.moneda_id, m.moneda_desc, m.moneda_simbolo, 
        sum(d.registro_det_importe) as importe,
        public.f_get_soles(d.moneda_id, sum(d.registro_det_importe)) as importe_s
    from public.registro_det d
    join public.registro r on r.registro_id = d.registro_id
    join public.moneda m on m.moneda_id = d.moneda_id 
    join public.pventa pv ON pv.pventa_id = r.pventa_id
    where d.registro_det_estado = '1'
    AND (
        public.f_get_ultimocierre_id(r.pventa_id) is null
        OR r.registro_id <> public.f_get_ultimocierre_id(r.pventa_id)
        OR d.clasemov_id IN ('18','17') -- solo restar faltante para obtener saldo real efectivo y sumar el excedente
    )
    and (
        pv.pventa_cerrarcaja = '0' -- no cierra tons mostrar todo
        OR coalesce(r.registro_fechacierre, r.registro_fecha::date) = public.f_get_ultimoregistro_fecha(r.pventa_id)
    )
    and d.registro_det_importe <> 0 -- no monedas sin monto
    and (
        r.registro_estado = 'T'
        OR (r.registro_estado = 'P' and r.tipope_id = '06') -- prestamo pendiente si resta
    )
    group by d.moneda_id, m.moneda_desc, m.moneda_simbolo
    order by d.moneda_id ASC
    ", null, true, true);
    $m_list = $qm->ToArray();
?>
        <table class="grid" width="100%">
        <tr>
            <td class="cell bold c">Codigo</td>
            <td class="cell bold c">Establecimiento</td>
            <td class="cell bold c br-2">Punto de Venta</td>
<?php   foreach ($m_list as $m):?>
<?php       if ($m['moneda_id']<>1) {
                $tc_factor = Sys::NFormat(PgQuery::GetValue('public.tipocambio.tipocambio_factor', "moneda_id_de={$m['moneda_id']} and moneda_id_a = 1", 0), 3);
                $tc_factor = "&nbsp;&nbsp;&nbsp;<span class='c-gray'>(t.c.: {$tc_factor})<span>";
            } 
?>
            <td class="cell bold c br-1" colspan="<?=$m['moneda_id']<>1?'2':'1'?>"><?=$m['moneda_desc']?><?=$m['moneda_id']<>1?' a SOLES':''?><?=$tc_factor?></td>
<?php   endforeach; 
?>                  
            <td class="cell bold r bl-2">Sub Total S/.</td>
            <td class="cell bold r bl-2">Sub Total US$</td>
        </tr>
<?php
    // query
    $q = new PgQuery("
    select 
    	pv.pventa_id, 
    	pv.pventa_desc, 
    	e.establecimiento_id,
    	e.establecimiento_desc, 
    	sum(public.f_get_soles(tm.moneda_id, tm.importe)) as importe,
    	public.f_get_dolares(1, sum(public.f_get_soles(tm.moneda_id, tm.importe))) as importe_d
	from public.pventa pv
	join public.establecimiento e on e.establecimiento_id = pv.establecimiento_id
	left join (
	  select 
    	  r.pventa_id, d.moneda_id, 
    	  sum(d.registro_det_importe) as importe
	  from public.registro_det d
	  join public.registro r on r.registro_id = d.registro_id
	  join public.pventa pv ON pv.pventa_id = r.pventa_id 
	  where r.pventa_id like '$pv_id%'
	  AND d.registro_det_estado = '1' -- solo mov. terminados
	  AND (
        public.f_get_ultimocierre_id(r.pventa_id) is null
        OR r.registro_id <> public.f_get_ultimocierre_id(r.pventa_id)
        OR d.clasemov_id IN ('18','17') -- solo restar faltante para obtener saldo real efectivo y sumar excendente:17
      )
	  and (
	      pv.pventa_cerrarcaja = '0' -- no cierra tons mostrar todo
          OR coalesce(r.registro_fechacierre, r.registro_fecha::date) = public.f_get_ultimoregistro_fecha(r.pventa_id)
	  )
      and (
        r.registro_estado = 'T'
        OR (r.registro_estado = 'P' and r.tipope_id = '06') -- prestamo pendiente si resta
      )
	  group by r.pventa_id, d.moneda_id
	) as tm on tm.pventa_id = pv.pventa_id
	where pv.pventa_id like '$pv_id%'
	group by pv.pventa_id, pv.pventa_desc, e.establecimiento_id, e.establecimiento_desc
    ORDER BY pv.pventa_id ASC", NULL, true, true);
?>  
<?php
	$total = $total_d = 0;
	while ($d = $q->Read()) {
		//$params = "establecimiento_id={$p['establecimiento_id']}&pventa_id={$p['pventa_id']}&moneda_id={$d['moneda_id']}&fechakardex=$fecha&showgoback=1";
		$total += $d['importe'];
		$total_d += $d['importe_d'];
?>
        <tr align="left" valign="top">
            <td class="cell c"><?=$d['pventa_id']?></td>
            <td class="cell c"><?=$d['establecimiento_desc']?></td>
            <td class="cell c br-2"><?=$d['pventa_desc']?></td>
<?php   foreach ($m_list as $m):?>
<?php       $qi = new PgQuery("
            select 
                public.f_get_ultimoregistro_fecha(r.pventa_id) as fecha,
                sum(d.registro_det_importe) as importe,
                public.f_get_soles({$m['moneda_id']}, sum(d.registro_det_importe)) as importe_s
            from public.registro_det d
            join public.registro r on r.registro_id = d.registro_id
            join public.pventa pv ON pv.pventa_id = r.pventa_id 
            where d.registro_det_estado in ('1') -- solo mov. terminados y pendientes si son prestamos px 
            AND (
                public.f_get_ultimocierre_id(r.pventa_id) is null
                OR r.registro_id <> public.f_get_ultimocierre_id(r.pventa_id)
                OR d.clasemov_id IN ('18','17') -- solo restar faltante para obtener saldo real efectivo y sumar excedente:17
            )
            and (
                pv.pventa_cerrarcaja = '0' -- no cierra tons mostrar todo
                OR coalesce(r.registro_fechacierre, r.registro_fecha::date) = public.f_get_ultimoregistro_fecha(r.pventa_id)
            )
            and (
                r.registro_estado = 'T'
                OR (r.registro_estado = 'P' and r.tipope_id = '06') -- prestamo pendiente si resta
            )
            and r.pventa_id = '{$d['pventa_id']}'
            and d.moneda_id = {$m['moneda_id']}
            group by r.pventa_id
            ", null, true, true);
            $im = $qi->row;
            $k_params = "establecimiento_id={$d['establecimiento_id']}&pventa_id={$d['pventa_id']}&moneda_id={$m['moneda_id']}&fechakardex={$im['fecha']}&showgoback=1";
?>
            <td class="cell r br-1" ><a class="kardex-link" href="javascript:void(0)" params="<?=$k_params?>"><?=Sys::NFormat($im['importe'])?></a></td>
<?php       if ($m['moneda_id']<>1):?>
            <td class="cell r br-1" ><?=Sys::NFormat($im['importe_s'])?></td>
<?php       endif;?>            
<?php   endforeach; 
?>          
            <td class="cell r bl-2 bold"><?=Sys::NFormat(($d['importe']),2,',')?></td>
            <td class="cell r bl-2 bold"><?=Sys::NFormat(($d['importe_d']),2,',')?></td>
        </tr> 
<?php
	} 
?>
		<tr align="left" valign="top">
            <td class="cell r bold br-2" colspan="3">Total</td>
<?php   foreach ($m_list as $m):?>
            <td class="cell r bold br-1" ><?=$m['moneda_simbolo']?> <?=Sys::NFormat($m['importe'])?></td>
<?php       if ($m['moneda_id']<>1):?>
            <td class="cell r bold br-1" >S/. <?=Sys::NFormat($m['importe_s'])?></td>
<?php       endif;?>            
<?php   endforeach; 
?>              
            <td class="cell r bold bl-2">S/. <?=Sys::NFormat($total,2,',')?></td>
            <td class="cell r bold bl-2">US$ <?=Sys::NFormat($total_d,2,',')?></td>
        </tr> 
        <tr align="left" valign="top">
            <td class="cell r bold br-2" colspan="<?=3+(count($m_list)*2)-1?>">Prestamos por Cobrar</td>
<?php
$cxc = new PgQuery("
    SELECT ABS(SUM(_q.importe)) as importe, public.f_get_dolares(1, ABS(SUM(_q.importe))) as importe_d
    FROM ( 
        SELECT 
            public.f_get_soles(d.moneda_id, d.registro_det_importe) as importe
        FROM public.registro r
        JOIN public.registro_det d On d.registro_id = r.registro_id AND d.registro_det_estado = '1' -- solo activos
        WHERE r.pventa_id like '$pv_id%' AND r.tipope_id = '06' AND r.registro_estado = 'P'
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
    ", NULL, true, true);
    if ($cxc->recordCount>0) {
        $cc = $cxc->row;
    } else {
        $cc['importe'] = $cc['importe_d'] = 0;
    }
    $cxc_params = "registro_estado=P&showgoback=1&pventa_id=";
?>
            <td class="cell r bold bl-2">S/. <a class="cxc-link" href="javascript:void(0)" params="<?=$cxc_params?>"><?=Sys::NFormat($cc['importe'],2,',')?></a></td>
            <td class="cell r bold bl-2">US$ <?=Sys::NFormat($cc['importe_d'],2,',')?></td>
        </tr>
<?php
	$tts = $total + $cc['importe'];
	$ttd = $total_d + $cc['importe_d'];
?>	
		<tr align="left" valign="top">
            <td class="cell r bold br-2" colspan="<?=3+(count($m_list)*2)-1?>">Total General</td>
			<td class="cell r bold bl-2">S/. <?=Sys::NFormat($tts,2,',')?></td>
            <td class="cell r bold bl-2">US$ <?=Sys::NFormat($ttd,2,',')?></td>
        </tr>
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