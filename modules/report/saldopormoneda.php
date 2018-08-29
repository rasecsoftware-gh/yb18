<?php
    define('sys_checksession', false);
    require_once '../../sys.php';
    $title = 'Reporte de Saldo por Moneda';
    $fecha = Sys::GetR('fechasaldo', date('d/m/Y')); 
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
</style>
<script>
</script>
</head>
<body style="padding: 0 0; margin: 0 0;">
<?php
    $es_id = Sys::GetUserEstablecimientoId();
    $pv_id = Sys::GetUserPVentaId();
    $es_desc = PgQuery::GetValue('public.establecimiento.establecimiento_desc', "establecimiento_id = '$es_id'", '');
    $pv_desc = PgQuery::GetValue('public.pventa.pventa_desc', "pventa_id = '$pv_id'", '');
?>
    <div align="left" class="hidable pd bg-theme" style="border-bottom: 1px solid black; margin-bottom: 5px;">
    <form id="frm" name="frm" method="get">
        <table style="width: 100%;" border="0">
        <tr>
            <td class="pd"><a class="print-bt" href="javascript: window.print();">imprimir</a></td>
            <td class="pd"><span class="hidable c-gray italic hidden">Se recomienda imprimir el reporte en forma horizontal.</span></td>
            <td class="pd "><input id="fechasaldo" type="text" name="fechasaldo" value="<?=$fecha?>"/></td>
        </tr>
        </table>
        <script>
            $("#fechasaldo" ).datepicker({ 
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
        <tr>
            <td colspan="3"><?=$pv_id?>: <?=$es_desc?> / <?=$pv_desc?></td>
        </tr>
        </table>
        </div>
<?php
    // query
    $q = new PgQuery("
    SELECT m.moneda_id, m.moneda_simbolo, m.moneda_desc, _q.total
    FROM public.moneda m 
    LEFT JOIN (
        SELECT d.moneda_id, SUM(d.registro_det_importe) as total
        FROM public.registro r
        JOIN public.registro_det d On d.registro_id = r.registro_id AND d.registro_det_estado = '1' -- solo activos
        WHERE r.registro_estado <> 'N' -- no anulado
        AND (
            (r.registro_fecha::date = '$fecha' AND r.tipope_id <> '99') -- no cierres
            OR (r.registro_fechacierre = '$fecha') -- cierres normales y destiempo
        )
        AND r.pventa_id = '$pv_id'
        GROUP BY d.moneda_id
    ) _q ON _q.moneda_id = m.moneda_id
    ORDER BY m.moneda_id ASC", NULL, true, true);
?>
        <table class="grid" width="100%">
        <tr>
            <td class="cell bold">Moneda</td>
            <td class="cell bold c">Simbolo</td>
            <td class="cell bold r">Importe</td>
        </tr>
        <?php
            while ($d = $q->Read()) {
            	$kardex_params = "establecimiento_id=$es_id&pventa_id=$pv_id&moneda_id={$d['moneda_id']}&fechakardex=$fecha&showgoback=1";
        
?>
        <tr align="left" valign="top">
            <td class="cell l" ><?=$d['moneda_desc']?></td>
            <td class="cell c" ><?=$d['moneda_simbolo']?></td>
            <td class="cell r"><a class="kardex-link" href="javascript:void(0)" params="<?=$kardex_params?>"><?=Sys::NFormat(($d['total']),2,',')?></a></td>
        </tr> 
        <?php
            } 
        
?>
        </table>
        <script>
        	$('.kardex-link').click(function (e) {
        		window.open('kardexpormoneda.php?'+$(this).attr('params'), '_self');
        	});
        </script>
    </div>
</body>
</html>