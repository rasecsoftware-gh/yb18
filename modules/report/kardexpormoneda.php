<?php
    define('sys_checksession', false);
    require_once '../../sys.php';
    $title = 'Reporte de Kardex por Moneda';
	$es_id = Sys::GetR('establecimiento_id', Sys::GetUserEstablecimientoId());
    $pv_id = Sys::GetR('pventa_id', Sys::GetUserPVentaId());
	$moneda_id = Sys::GetR('moneda_id', 1);
    $fecha = Sys::GetR('fechakardex', date('d/m/Y')); 
    $fecha = $fecha==''?date('d/m/Y'):$fecha;
	$goback = Sys::GetR('showgoback', 0);
    $t = Sys::getTimeStamp($fecha);
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
.rowstate-0 td.state {
	color: red;
}
.rowstate-1 td.state {
	color: black;
}
.rowstate-2 td.state {
	color: silver;
}
.rowstate-99 td.state {
    color: silver;
}
</style>
<script>
</script>
</head>
<body style="padding: 0 0; margin: 0 0;">
<?php
    $es_desc = PgQuery::GetValue('public.establecimiento.establecimiento_desc', "establecimiento_id = '$es_id'", '');
    $pv_desc = PgQuery::GetValue('public.pventa.pventa_desc', "pventa_id = '$pv_id'", '');
    $moneda_desc = PgQuery::GetValue('public.moneda.moneda_desc', "moneda_id = $moneda_id", '');
?>
    <div align="left" class="hidable pd bg-theme" style="border-bottom: 1px solid black; margin-bottom: 5px;">
    <form id="frm" name="frm" method="get">
        <table style="width: 100%;" border="0">
        <tr valign="middle">
            <td class="pd">
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
            		<select id="moneda_id" name="moneda_id" style="margin-top: 0; height: 23px;">
<?php
	$qs = new PgQuery("SELECT * FROM public.moneda ORDER BY moneda_id", NULL, true, true);
	while ($m = $qs->Read()):?>
            		<option value="<?=$m['moneda_id']?>" <?=$m['moneda_id']==$moneda_id?'selected':''?>><?=$m['moneda_desc']?></option>
<?php
	endwhile;?>
            		</select>&nbsp;
            		</td>
            		<td>
            		<input id="fechakardex" type="text" name="fechakardex" value="<?=$fecha?>" style="height: 19px;"/>
            		</td>
            	</tr>
            	</table>
            </td>
        </tr>
        </table>
        <script>
            $("#fechakardex" ).datepicker({ 
                dateFormat: "dd/mm/yy",
                onSelect: function() {
                    $('#frm').submit();
                } 
            });
            $('#moneda_id').change(function (e) {
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
                <div class="report-title"><?=Sys::Upper($title)?> - <?=$moneda_desc?></div>
                <div class="report-fecha">Al <?=date('d', $t)?> de <?=Sys::GetMonthName(date('m', $t))?> del <?=date('Y', $t)?></div>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="fs-12 bold"><?=$pv_id?> <?=$es_desc?> / <?=$pv_desc?></td>
        </tr>
        </table>
        </div>
<?php
    // query
    $q = new PgQuery("
    SELECT r.*, d.*, 
    m.moneda_desc, m.moneda_simbolo,
    tio.tipope_desc,
    c.clasemov_desc, c.tipomov_id
    FROM public.registro r
    JOIN public.registro_det d On d.registro_id = r.registro_id --AND d.registro_det_estado = '1' -- solo activos
    JOIN public.moneda m ON m.moneda_id = d.moneda_id
    JOIN public.tipope tio ON tio.tipope_id = r.tipope_id
    JOIN public.clasemov c ON c.clasemov_id = d.clasemov_id
    JOIN public.pventa pv ON pv.pventa_id = r.pventa_id
    WHERE
    (
        pv.pventa_cerrarcaja = '0'   
        OR coalesce(r.registro_fechacierre, r.registro_fecha::date) = '$fecha'
    )
    AND d.registro_det_importe <> 0
    AND r.pventa_id = '$pv_id'
    AND d.moneda_id = $moneda_id
    ORDER BY r.registro_id ASC, d.registro_det_id ASC
	", NULL, true, true);
?>
        <table class="grid" width="100%">
        <tr>
            <td class="cell bold">#</td>
            <td class="cell bold c">Fecha</td>
            <td class="cell bold c">Hora</td>
            <td class="cell bold c">Nro.Ope.</td>
            <td class="cell bold l">Operacion</td>
            <td class="cell bold l">Movimiento</td>
            <td class="cell bold l">Detalle</td>
            <td class="cell bold l">Estado</td>
            <td class="cell bold l">Tipo</td>
            <td class="cell bold r">Ingreso</td>
            <td class="cell bold r">Egreso</td>
            <td class="cell bold r">Saldo</td>
        </tr>
        <?php
        	$saldo = 0;
            while ($d = $q->Read()) {
                $t = Sys::getTimeStamp($d['registro_fecha']);
            	/*if ($d['tipope_id']!='99') {            		
				} else {
					$t = Sys::getTimeStamp($d['registro_fechacierre']);
				}*/
				$rstate = $d['registro_det_estado'];  // anulado
				if ($rstate == '1' && $d['clasemov_id']!='02') {
					$saldo += floatval($d['registro_det_importe']);
				} 
				//<?=$rstate=='0'?'c-red':($rstate=='1'?'c-black':'c-silver')
				$r_params = "registro_id={$d['registro_id']}&showgoback=1";
        
?>
        <tr class="rowstate-<?=$d['clasemov_id']=='02'?99:$rstate?>" align="left" valign="top">
            <td class="cell l"><?=$q->recNo?></td>
            <td class="cell c"><?=date('d/m/Y', $t)?></td>
            <td class="cell c"><?=date('H:i:s', $t)?></td>
            <td class="cell c"><a class="r-link" href="javascript:void(0)" params="<?=$r_params?>"><?=$d['registro_id']?></a></td>
            <td class="cell l"><?=$d['tipope_desc']?></td>
            <td class="cell l"><?=$d['clasemov_desc']?></td>
            <td class="cell l"><?=(trim($d['registro_desc'])!=''?$d['registro_desc']:'')?></td>
            <td class="cell c state"><?=($rstate=='0'?'ANULADO':($rstate=='2'?'PENDIENTE':''))?></td>
            <td class="cell l"><?=$d['tipomov_id']?></td>
            <td class="cell r state"><?=$d['tipomov_id']=='I'?Sys::NFormat(($d['registro_det_importe'])):''?></td>
            <td class="cell r state"><?=$d['tipomov_id']=='E'?Sys::NFormat(($d['registro_det_importe'])):''?></td>
            <td class="cell r"><?=Sys::NFormat(($saldo))?></td>
        </tr> 
        <?php
            } 
        
?>
        </table>
    </div>
    <script>
    $('.r-link').click(function (e) {
        window.open('registrorep.php?'+$(this).attr('params'), '_self');
    });
</script>
</body>
</html>