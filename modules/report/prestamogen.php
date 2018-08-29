<?php
    define('sys_checksession', false);
    require_once '../../sys.php';
    $title = 'Reporte de Prestamos';
	$es_id = Sys::GetR('establecimiento_id', Sys::GetUserEstablecimientoId());
    $pv_id = Sys::GetR('pventa_id', Sys::GetUserPVentaId());
    $cliente_id = Sys::GetR('cliente_id', '');
	$goback = Sys::GetR('showgoback', 0, true, 'prestamogen', true);
	$estado = Sys::GetR('registro_estado', 'P');
	$t = Sys::getTimeStamp(date('d/m/Y'));
	
	$qf = new PgQuery("
    SELECT 
    COALESCE(MIN(r.registro_fecha), now()) as fecha_min,
	COALESCE(MAX(r.registro_fecha), now()) as fecha_max
    FROM public.registro r
    WHERE r.tipope_id = '06' -- prestamo
	", NULL, true, true);
	
	$t1 = Sys::getTimeStamp($qf->row['fecha_min']);
	$t2 = Sys::getTimeStamp($qf->row['fecha_max']);
	
	$fechadesde = Sys::GetR('fechadesde', date('d/m/Y', $t1));
    $fechahasta = Sys::GetR('fechahasta', date('d/m/Y', $t2));
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
.header-link {
    color: gray;
    font-weight: bold;
    font-size: 10pt;
}
.header-link:hover {
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
.rowstate-N td.state {
	color: red;
}
.rowstate-T td.state {
	color: black;
}
.rowstate-P td.state {
	color: green;
}
.sub-grid {
    background-color: white;
    outline: none!important;
    border-collapse: collapse;
}
.cell {
    border: 1px solid #ddd!important;
}
.sub-grid .cell {
    color: gray!important;
}
</style>
<script>
</script>
</head>
<body style="padding: 0 0; margin: 0 0;">
<?php
    $es_desc = PgQuery::GetValue('public.establecimiento.establecimiento_desc', "establecimiento_id = '$es_id'", '');
    $pv_desc = PgQuery::GetValue('public.pventa.pventa_desc', "pventa_id = '$pv_id'", '');
?>
    <div align="left" class="hidable pd bg-theme" style="border-bottom: 1px solid black; margin-bottom: 5px;">
    <form id="frm" name="frm" method="get">
        <table style="width: 100%;" border="0">
        <tr valign="middle">
            <td class="pd" style="width: 200px;">
            	<a class="header-link" href="javascript: window.print();">imprimir</a><?="&nbsp;&nbsp;|&nbsp;&nbsp;"
                
?><a class="header-link" href="javascript: location.reload();">actualizar</a>
            <?php
	if ($goback==1):?>
            	&nbsp;|&nbsp;&nbsp;<a class="header-link" href="javascript: history.back();">atras</a>
            <?php
	endif; 
?>
            </td>
            <td class="pd"><span class="hidable c-gray italic hidden">Se recomienda imprimir el reporte en forma horizontal.</span></td>
            <td class="pd " valign="top">
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
            		<td>Cliente: 
<?php
    $qc = new PgQuery("
    SELECT 
    r.cliente_id,
    cl.cliente_desc
    FROM public.registro r
    JOIN public.cliente cl ON cl.cliente_id = r.cliente_id
    WHERE r.pventa_id like '$pv_id%'
    AND r.tipope_id = '06' -- prestamo
    GROUP BY 
    r.cliente_id,
    cl.cliente_desc
    ORDER BY cl.cliente_desc ASC
    ", NULL, true, true);
?>
            		<select id="cliente_id" name="cliente_id" style="margin-top: 0; height: 23px;">
                        <option value="" <?=''==$cliente_id?'selected':''?>>- todos -</option>
<?php
while ($c = $qc->Read()):?>
                        <option value="<?=$c['cliente_id']?>" <?=$c['cliente_id']==$cliente_id?'selected':''?>><?=$c['cliente_desc']?></option>
<?php
endwhile;?>
                    </select>
            		</td>
            		<td>
                    <span>Estado: </span>
                    <select id="registro_estado" name="registro_estado" style="margin-top: 0; height: 23px;">
                        <option value="P" <?='P'==$estado?'selected':''?>><?='PENDIENTES'?></option>
                        <option value="N" <?='N'==$estado?'selected':''?>><?='ANULADOS'?></option>
                        <option value="T" <?='T'==$estado?'selected':''?>><?='TERMINADO'?></option>
                        <option value="%" <?='%'==$estado?'selected':''?>><?='- TODOS -'?></option>
                    </select>&nbsp;
                    </td>
					<td>
                        del <input id="fechadesde" type="text" name="fechadesde" value="<?=$fechadesde?>" style="height: 19px;"/>
                    </td>
                    <td>
                        al <input id="fechahasta" type="text" name="fechahasta" value="<?=$fechahasta?>" style="height: 19px;"/>
                    </td>
            	</tr>
            	</table>
            	<script>
                    $('#pventa_id').change(function (e) {
                        $('#frm').submit();
                    });
                    $('#registro_estado').change(function (e) {
                        $('#frm').submit();
                    });
		            $('#cliente_id').change(function (e) {
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
            </td>
        </tr>
        </table>
        <script>
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
                    <td class="r fs-7">usuario: </td><td class="fs-7 l"><?=Sys::GetUserName()?></td>
                </tr>
                <tr>
                    <td class="r fs-7">impresion: </td><td class="fs-7 l"><?=date('d/m/Y H:i:s')?></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="left" colspan="3">
                <div class="report-title"><?=Sys::Upper($title)?></div>
                <div class="report-fecha">Al <?=date('d', $t)?> de <?=Sys::GetMonthName(date('m', $t))?> de <?=date('Y', $t)?></div>
            </td>
        </tr>
        <tr>
            <td colspan="3"><?=$pv_id?>: <?=$es_desc?> / <?=$pv_desc?></td>
        </tr>
        </table>
        </div>
<?php
    // query
    $q = new PgQuery("
    SELECT 
    r.registro_id,
    r.registro_fecha,
    r.registro_desc,
    r.usuario,
    r.registro_interes,
    r.registro_imora,
    r.registro_fechavenci,
    r.registro_estado,
	r.pventa_id,
    tio.tipope_desc,
    m.moneda_desc, 
    m.moneda_simbolo,
    cl.cliente_desc,
    c.clasemov_desc,
    SUM(d.registro_det_importe) as importe,
    SUM(public.f_get_soles(d.moneda_id, d.registro_det_importe)) as importe_s     
    FROM public.registro r
    JOIN public.registro_det d On d.registro_id = r.registro_id AND d.registro_det_estado = '1' -- solo activos
    JOIN public.moneda m ON m.moneda_id = d.moneda_id
    JOIN public.tipope tio ON tio.tipope_id = r.tipope_id
    JOIN public.clasemov c ON c.clasemov_id = d.clasemov_id
    JOIN public.cliente cl ON cl.cliente_id = r.cliente_id
    WHERE r.pventa_id like '$pv_id%'
    AND r.tipope_id = '06' -- prestamo
    AND r.registro_estado like '%$estado%'
    AND ('$cliente_id' = '' OR r.cliente_id = '$cliente_id' )
	AND (r.registro_fecha BETWEEN '$fechadesde 00:00:00' AND '$fechahasta 23:59:59')
    GROUP BY 
    r.registro_id,
    r.registro_fecha,
    r.registro_desc,
    r.usuario,
    r.registro_interes,
    r.registro_imora,
    r.registro_fechavenci,
    r.registro_estado,
	r.pventa_id,
    tio.tipope_desc,
    m.moneda_desc, 
    m.moneda_simbolo,
    cl.cliente_desc,
    c.clasemov_desc
    ORDER BY r.registro_id ASC
	", NULL, true, true);
	$total_s = $total_d = 0;
?>
        <table class="grid" width="100%">
        <tr>
            <td class="cell bold">#</td>
            <td class="cell bold c" style="width: 127px;">Nro.Ope.</td>
            <td class="cell bold l" style="width: 170px;">Operacion</td>
            <td class="cell bold c" style="width: 135px;">Fecha Prestamo / Pago</td>
            <td class="cell bold c">Vencimiento</td>
            <td class="cell bold l">Cliente</td>
			<td class="cell bold l">Descripcion</td>
            <td class="cell bold c" style="width: 70px;">Estado</td>
            <td class="cell bold l" style="width: 110px;">% Interes / Importe</td>
            <td class="cell bold l" style="width: 110px;">% Mora / Importe</td>
            <td class="cell bold r" style="width: 100px;">Importe Prestado</td>
            <td class="cell bold r" style="width: 106px;">Importe S/.</td>
        </tr>
        <?php
            while ($r = $q->Read()) {
            	$t = Sys::getTimeStamp($r['registro_fecha']);
            	$rstate = $r['registro_estado'];
                $total_s += abs($r['importe_s']);
                $total_d += abs($r['importe_d']);
				$pv_desc = PgQuery::GetValue('public.pventa.pventa_desc', "pventa_id = '{$r['pventa_id']}'", '');
				$es_id = PgQuery::GetValue('public.pventa.establecimiento_id', "pventa_id = '{$r['pventa_id']}'", '');
				$es_desc = PgQuery::GetValue('public.establecimiento.establecimiento_desc', "establecimiento_id = '$es_id'", '');
                $r_params = "registro_id={$r['registro_id']}&showgoback=1";
        
?>
        <tr class="rowstate-<?=$rstate?>" align="left" valign="top">
            <td class="cell l"><?=$q->recNo?></td>
            <td class="cell c bold" style="background: silver;"><a class="r-link c-black" href="javascript:void(0)" params="<?=$r_params?>"><?=$r['registro_id']?></a></td>
            <td class="cell l"><?=$r['tipope_desc']?></br><?="$es_desc - $pv_desc"?></td>
            <td class="cell c"><?=$r['registro_fecha']?></td>
            <td class="cell c"><?=$r['registro_fechavenci']?></td>
            <td class="cell l"><?=$r['cliente_desc']?></td>
			<td class="cell l"><?=$r['registro_desc']?></td>
            <td class="cell c state"><?=($rstate=='N'?'ANULADO':($rstate=='P'?'PENDIENTE':''))?></td>
            <td class="cell c"><?=Sys::NFormat($r['registro_interes'])?>%</td>
            <td class="cell c"><?=Sys::NFormat($r['registro_imora'])?>%</td>
            <td class="cell r bold"><?=$r['moneda_simbolo']?>&nbsp;<?=Sys::NFormat(abs($r['importe']))?></td>
            <td class="cell r bold">S/.&nbsp;<?=Sys::NFormat(abs($r['importe_s']))?></td>
        </tr> 
        <?php
                $qd = new PgQuery("
                SELECT 
                  r.registro_id,
                  r.pventa_id,
                  r.registro_fecha,
                  r.registro_estado,
                  d.moneda_id,
                  m.moneda_simbolo,
                  tp.tipope_desc,
                  (
                    SELECT SUM(d2.registro_det_importe)
                    FROM public.registro_det d2
                    WHERE d2.registro_id = r.registro_id AND d2.moneda_id = d.moneda_id AND d2.clasemov_id = '12'
                  ) as importe,
                  (
                    SELECT SUM(d2.registro_det_importe)
                    FROM public.registro_det d2
                    WHERE d2.registro_id = r.registro_id AND d2.moneda_id = d.moneda_id AND d2.clasemov_id = '13' -- interes
                  ) as importe_i,
                  (
                    SELECT SUM(d2.registro_det_importe)
                    FROM public.registro_det d2
                    WHERE d2.registro_id = r.registro_id AND d2.moneda_id = d.moneda_id AND d2.clasemov_id = '14' -- mora
                  ) as importe_im,
                  (
                    SELECT SUM(d2.registro_det_importe)
                    FROM public.registro_det d2
                    WHERE d2.registro_id = r.registro_id AND d2.moneda_id = d.moneda_id 
                  ) as importe_t,
                  public.f_get_soles(d.moneda_id, (
                    SELECT SUM(d2.registro_det_importe)
                    FROM public.registro_det d2
                    WHERE d2.registro_id = r.registro_id AND d2.moneda_id = d.moneda_id
                  )) as importe_s
                FROM public.registro_det d
                JOIN public.registro r ON r.registro_id = d.registro_id
                JOIN public.moneda m ON m.moneda_id = d.moneda_id
                JOIN public.tipope tp ON tp.tipope_id = r.tipope_id
                WHERE r.registro_id_parent = '{$r['registro_id']}'
                AND d.registro_det_importe <> 0
                GROUP BY 
                  r.registro_id,
                  r.pventa_id,
                  r.registro_fecha,
                  r.registro_estado,
                  d.moneda_id,
                  m.moneda_simbolo,
                  tp.tipope_desc
                ORDER BY r.registro_fecha ASC
                ", NULL, true, true);
                if ($qd->recordCount>0):
        
?>
        <tr class="" align="left" valign="top">
            <td class="cell l">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td class="cell l" colspan="11">
                <table class="grid sub-grid" width="100%">
        <?php       while ($d = $qd->Read()): $r_params = "registro_id={$d['registro_id']}&showgoback=1";?>
                <tr class="" align="left" valign="top">
                    <td class="cell l" style="width: 25px;"><?=$qd->recNo?></td>
                    <td class="cell c" style="width: 85px;"><a class="r-link" href="javascript:void(0)" params="<?=$r_params?>"><?=$d['registro_id']?></a></td>
                    <td class="cell l" style="width: 170px;"><?=$d['tipope_desc']?></td>
                    <td class="cell c" style="width: 135px;"><?=$d['registro_fecha']?></td>
                    <td class="cell c"><?=Sys::Upper($d['usuario'])?></td>
                    <td class="cell c" style="width: 70px;"><?=($d['registro_estado']=='N'?'ANULADO':($d['registro_estado']=='P'?'PENDIENTE':''))?></td>
                    <td class="cell r" style="width: 110px;"><?=$d['moneda_simbolo']?>&nbsp;<?=Sys::NFormat(abs($d['importe_i']))?></td>
                    <td class="cell r" style="width: 110px;"><?=$d['moneda_simbolo']?>&nbsp;<?=Sys::NFormat(abs($d['importe_im']))?></td>
                    <td class="cell r" style="width: 100px;"><?=$d['moneda_simbolo']?>&nbsp;<?=Sys::NFormat(abs($d['importe']))?></td>
                    <td class="cell r" style="width: 100px;">S/.&nbsp;<?=Sys::NFormat(abs($d['importe_s']))?></td>
                </tr> 
        <?php       endwhile; 
?>
                </table>
            </td>
        </tr>
        <?php   endif; 
?>
        <?php
            } 
        
?>
        <tr>
            <td class="cell bold r" colspan="11">TOTAL</td>
            <td class="cell bold r">S/.&nbsp;<?=Sys::NFormat($total_s)?></td>
        </tr>
        </table>
    </div>
    <script>
    $('.r-link').click(function (e) {
        window.open('registrorep.php?'+$(this).attr('params'), '_self');
    });
</script>
</body>
</html>