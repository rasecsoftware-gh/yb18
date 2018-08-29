<?php
    define('sys_checksession', false);
    require_once '../../sys.php';
    $title = 'Reporte General de Saldo por Moneda';
    $fecha = Sys::GetR('fechasaldo', date('d/m/Y')); 
    $t = Sys::getTimeStamp($fecha);
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
                <a class="header-link" href="javascript: window.print();">imprimir</a><?="&nbsp;&nbsp;|&nbsp;&nbsp;"
                
?><a class="header-link" href="javascript: location.reload();">actualizar</a>
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
                    <td>
                    <input id="fechasaldo" type="text" name="fechasaldo" value="<?=$fecha?>" style="height_1: 19px;"/>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
        <script>
            $("#fechasaldo" ).datepicker({ 
                dateFormat: "dd/mm/yy",
                onSelect: function() {
                    $('#frm').submit();
                } 
            });
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
                <div class="report-fecha">Al <?=date('d', $t)?> de <?=Sys::GetMonthName(date('m', $t))?> del <?=date('Y', $t)?></div>
            </td>
        </tr>
        </table>
        </div>
<?php
    $qs2 = new PgQuery("
    SELECT pv.*, e.establecimiento_desc
    FROM public.pventa pv
    JOIN public.establecimiento e on e.establecimiento_id = pv.establecimiento_id
    WHERE ('$pv_id' = '' OR pv.pventa_id like '$pv_id%')  
    ORDER BY e.establecimiento_desc, pv.pventa_desc", NULL, true, true);
?>
        <table class="grid" width="100%">
        <tr>
            <td class="cell bold">P. Venta</td>
            <td class="cell bold">Moneda</td>
            <td class="cell bold c">Simbolo</td>
            <td class="cell bold r">Importe</td>
        </tr>
<?php
while ($p = $qs2->Read()):?>
        <tr>
            <td class="cell" colspan="4"><?=$p['pventa_id']?>: <?=$p['establecimiento_desc']?> / <?=$p['pventa_desc']?></td>
        </tr>
<?php
        // query
        $q = new PgQuery("
        SELECT m.moneda_id, m.moneda_simbolo, m.moneda_desc, _q.total
        FROM public.moneda m 
        LEFT JOIN (
            SELECT d.moneda_id, SUM(d.registro_det_importe) as total
            FROM public.registro r
            JOIN public.registro_det d On d.registro_id = r.registro_id AND d.registro_det_estado = '1' -- solo activos
            JOIN public.pventa pv ON pv.pventa_id = r.pventa_id
            WHERE r.registro_estado <> 'N' -- no anulado
            AND (
                coalesce(r.registro_fechacierre, r.registro_fecha::date) = '$fecha'
                OR pv.pventa_cerrarcaja = '0' -- listar si no cierra caja
            )
            AND (r.registro_estado = 'T' OR (
                r.registro_estado = 'P' AND r.registro_id_parent IS NULL -- solo pendientes que han sido enviados
            ))
            AND (
                public.f_get_ultimocierrefecha_id(r.pventa_id, r.registro_fecha::date) is null
                OR r.registro_id <> public.f_get_ultimocierrefecha_id(r.pventa_id, r.registro_fecha::date)
                OR d.clasemov_id = '18' -- solo restar faltante para obtener saldo real efectivo
            )
            AND r.pventa_id = '{$p['pventa_id']}'
            GROUP BY d.moneda_id
        ) _q ON _q.moneda_id = m.moneda_id
        ORDER BY m.moneda_id ASC", NULL, true, true);
?>  
        <?php
            while ($d = $q->Read()) {
            	$kardex_params = "establecimiento_id={$p['establecimiento_id']}&pventa_id={$p['pventa_id']}&moneda_id={$d['moneda_id']}&fechakardex=$fecha&showgoback=1";
        
?>
        <tr align="left" valign="top">
            <td class="cell l" ></td>
            <td class="cell l" ><?=$d['moneda_desc']?></td>
            <td class="cell c" ><?=$d['moneda_simbolo']?></td>
            <td class="cell r"><a class="kardex-link" href="javascript:void(0)" params="<?=$kardex_params?>"><?=Sys::NFormat(($d['total']),2,',')?></a></td>
        </tr> 
        <?php
            } 
        
?>
<?php
endwhile;?>
        </table>
        <script>
        	$('.kardex-link').click(function (e) {
        		window.open('kardexpormoneda.php?'+$(this).attr('params'), '_self');
        	});
        </script>
    </div>
</body>
</html>