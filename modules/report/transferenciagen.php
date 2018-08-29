<?php
    define('sys_checksession', false);
    require_once '../../sys.php';
    $title = 'Reporte General de Transferencias';
	$es_id = Sys::GetR('establecimiento_id', Sys::GetUserEstablecimientoId());
    $pv_id = Sys::GetR('pventa_id', Sys::GetUserPVentaId());
    $pv_id_destino = Sys::GetR('pventa_id', '');
	$goback = Sys::GetR('showgoback', 0, true, 'prestamogen', true);
	$t = Sys::getTimeStamp(date('d/m/Y'));
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
            	    <td>Origen: 
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
            		<td>Destino: 
            		    <select id="pventa_id" name="pventa_id_destino" style="margin-top: 0; height: 23px;">
                        <option value="" <?=''==$pv_id_destino?'selected':''?>>- todo -</option>
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
                        <option value="<?=$m['pventa_id']?>" <?=$m['pventa_id']==$pv_id?'selected':''?>><?=$m['establecimiento_desc']?> <?=is_null($m['pventa_desc'])?'':"/ {$m['pventa_desc']}"?></option>
<?php
endwhile;?>
                        </select>&nbsp;
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
            	</tr>
            	</table>
            	<script>
                    $('#pventa_id').change(function (e) {
                        $('#frm').submit();
                    });
                    $('#pventa_id_destino').change(function (e) {
                        $('#frm').submit();
                    });
                    $('#registro_estado').change(function (e) {
                        $('#frm').submit();
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
        </table>
        </div>
<?php
    // query x pventa
    $qpv = new PgQuery("
    SELECT pv.pventa_id, pv.pventa_desc, e.establecimiento_desc
    FROM public.pventa pv
    JOIN public.establecimiento e ON e.establecimiento_id = pv.establecimiento_id
    WHERE
    pv.pventa_id like '$pv_id%'
    ORDER BY pv.pventa_id ASC
    ", NULL, true, true);
?>
<?php
while ($pv = $qpv->Read()):?>
        <div class="pd fs-10 bold"><?=$pv['pventa_id'].' '.$pv['establecimiento_desc'].' - '.$pv['pventa_desc']?></div>
<?php
        // query detalles
        $q = new PgQuery("
        SELECT DISTINCT 
        r.registro_id,
        r.pventa_id,
        r.registro_fecha,
        r.registro_desc,
        r.registro_estado,
        r.registro_id_main,
        r.registro_id_parent,
        r.registro_diferido,
        r.registro_retornar,
        r.registro_tcfactor,
        r.usuario, 
        d.moneda_id, 
        abs(d.registro_det_importe) as registro_det_importe,
        m.moneda_desc, 
        m.moneda_simbolo,
        tio.tipope_desc,
        rr.registro_id as registro_id_r,
        rr.registro_fecha as registro_fecha_r,
        rr.registro_desc as registro_desc_r,
        rr.usuario as usuario_r,
        dr.moneda_id as moneda_id_r,
        mr.moneda_desc as moneda_desc_r,
        mr.moneda_simbolo as moneda_simbolo_r,
        abs(dr.registro_det_importe) as registro_det_importe_r
        FROM public.registro r
        JOIN public.registro_det d On d.registro_id = r.registro_id 
        JOIN public.moneda m ON m.moneda_id = d.moneda_id
        JOIN public.tipope tio ON tio.tipope_id = r.tipope_id
        LEFT JOIN public.registro rr ON rr.registro_id_main = r.registro_id
        LEFT JOIN public.registro_det dr On dr.registro_id = rr.registro_id
        LEFT JOIN public.moneda mr On mr.moneda_id = dr.moneda_id
        WHERE r.tipope_id = '05'
        AND r.pventa_id like '{$pv['pventa_id']}'
        ORDER BY r.registro_id ASC
        ", NULL, true, true);
?>        
<?php   if ($q->recordCount==0): 
?>
        <div class="pd-5 c-gray">no tiene transferencias registradas</div>   
<?php   else:?>
        <table class="grid" width="100%">
        <tr>
            <td class="cell bold">#</td>
            <td class="cell bold">P.Venta</td>
            <td class="cell bold l">Operacion</td>
            <td class="cell bold c">Fecha</td>
            <td class="cell bold c">Hora</td>
            <td class="cell bold l">Detalle / Descripcion</td>
            <td class="cell bold l">Estado</td>
            <td class="cell bold l">Moneda</td>
            <td class="cell bold r">Importe</td>
            <td class="cell bold c">Retornar</td>
            <td class="cell bold l">Transferencia de Retorno</td>
        </tr>
        <?php
            $saldo = 0;
            while ($d = $q->Read()) {
                $t = Sys::getTimeStamp($d['registro_fecha']);
                $rstate = $d['registro_estado'];  // anulado
                //<?=$rstate=='0'?'c-red':($rstate=='1'?'c-black':'c-silver')
        
?>
        <tr class="rowstate-<?=$rstate?>" align="left" valign="top">
            <td class="cell l"><?=$q->recNo?></td>
            <td class="cell c"><?=$d['pventa_id']?></td>
            <td class="cell c"><?=$d['registro_id']?> <?=$d['tipope_desc']?></td>
            <td class="cell c"><?=date('d/m/Y', $t)?></td>
            <td class="cell c"><?=date('H:i:s', $t)?></td>
            <td class="cell l"><?=$d['registro_desc']?></td>
            <td class="cell c state"><?=($rstate=='N'?'ANULADO':($rstate=='P'?'PENDIENTE':''))?></td>
            <td class="cell l"><?=$d['moneda_desc']?></td>
            <td class="cell r state"><?=$d['moneda_simbolo']?> <?=Sys::NFormat($d['registro_det_importe'])?></td>
            <td class="cell c"><?=$d['registro_retornar']=='1'?'SI':''?></td>
            <td class="cell l"><?php
            if (trim($d['registro_id_r'])!=''):
            echo $d['registro_id_r'].': '.$d['registro_fecha_r'].'. '.$d['moneda_desc_r'].'. Importe: '.$d['moneda_simbolo_r'].' '.$d['registro_det_importe_r'];
            endif;
            
?></td>
        </tr> 
        <?php
            } 
        
?>
        </table>
<?php   endif; // en rows?>        
<?php
endwhile; // pventa list?>        
    </div>
</body>
</html>