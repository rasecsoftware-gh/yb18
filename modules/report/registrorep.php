<?php
    define('sys_checksession', false);
    require_once '../../sys.php';
    $title = 'Reporte de Informacion de Registro';
    $goback = Sys::GetR('showgoback', 0);
    $t = Sys::getTimeStamp(date('d/m/Y')); 
    // default values
    $id = Sys::GetR('registro_id','');
    
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
    WHERE r.registro_id::text like '$id'
    ", NULL, true, false);
    $r = $q->row;
    
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
.info-cell-anulado {
    color: red;
    font-weight: bold;
}
.info-cell-pendiente {
    color: purple;
    font-weight: bold;
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
                <?php
if ($goback==1):?>
                &nbsp;|&nbsp;&nbsp;<a class="header-link" href="javascript: history.back();">atras</a>
            <?php
endif; 
?>
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
<div class="" style="padding: 2px;">
<table style="width: 100%">
<tr>
    <td valign="top">
        <table class="grid" width="100%">
        <tr align="left" valign="top">
            <td class="cell" width="100">Punto Venta: </td>
            <td class="cell"><?=$r['establecimiento_desc']?> / <?=$r['pventa_desc']?></td>
        </tr>
        <tr align="left" valign="top">
            <td class="cell">Operacion: </td>
            <td class="cell">
                <span class="c-black bold"><?=$r['registro_id']?></span>: <span class="bold" style="color: black;"><?=$r['tipope_desc']?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="cell">Fecha</td>
            <td class="cell"><?=$r['registro_fecha']?></td>
        </tr>
        <?php
if(!is_null($r['registro_fechacierre']) && $r['tipope_id'] == '99'):?>
        <tr align="left" valign="top">
            <td class="cell">Fecha Cierre</td>
            <td class="cell"><?=$r['registro_fechacierre']?></td>
        </tr>
        <?php
endif; 
?>
        <?php
if(trim($r['cliente_id'])!=''):?>
        <tr align="left" valign="top">
            <td class="cell">Cliente</td>
            <td class="cell"><?=$r['cliente_desc']?></td>
        </tr>
        <?php
endif; 
?>
        <tr align="left" valign="top">
            <td class="cell">Descripcion</td>
            <td class="cell"><?=$r['registro_desc']?></td>
        </tr>
        <?php
if($r['registro_interes']>0):?>
        <tr align="left" valign="top">
            <td class="cell">Tasa de Interes</td>
            <td class="cell"><?=Sys::NFormat($r['registro_interes'])?> %
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
            <td class="cell">Tipo Cambio</td>
            <td class="cell"><?=Sys::NFormat($r['registro_tcfactor'], 4)?></td>
        </tr>
        <?php
endif; 
?>
        <?php
if($r['registro_estado']=='N'):?>
        <tr align="left" valign="top">
            <td class="cell">Estado</td>
            <td class="cell info-cell-anulado">ANULADO</td>
        </tr>
        <?php
endif; 
?>
        <?php
if($r['registro_estado']=='P'):?>
        <tr align="left" valign="top">
            <td class="cell">Estado</td>
            <td class="cell info-cell-pendiente">PENDIENTE</td>
        </tr>
        <?php
endif; 
?>
        <?php
if($r['registro_diferido']=='1'):?>
        <tr align="left" valign="top">
            <td class="cell">Diferido al:</td>
            <td class="cell">
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
            <td class="cell"><?php

            switch ($r['tipope_id']) {
            case '05': echo 'Viene de:'; break; // transferencia
            case '07': echo 'Prestamo:'; break; // cancelacion de prestamo
            default: echo 'Referencia:';
            } 
        
?></td>
            <td class="cell">
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
            <td class="cell">Con Retorno a:</td>
            <td class="cell">
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
            <td class="cell">Transferido a:</td>
            <td class="cell">
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
            <td class="cell">Usuario</td>
            <td class="cell"><?=strtoupper($r['usuario'])?></td>
        </tr>
        </table>
    </td>
    <td valign="top">
        <style>
        .state-0 {
            background-color: red;
        }
        </style>
        <table class="grid" width="100%">
        <tr>
            <td class="cell bold" width="40">#</td>
            <td class="cell bold">Tipo</td>
            <td class="cell bold">Moneda</td>
            <td class="cell bold r">Importe</td>
        </tr>
        <?php
            $qp = new PgQuery("
            SELECT d.*, 
            m.moneda_desc,
            c.clasemov_desc,
            c.tipomov_id
            FROM public.registro_det d
            JOIN public.moneda m On m.moneda_id = d.moneda_id 
            JOIN public.clasemov c On c.clasemov_id = d.clasemov_id
            WHERE d.registro_id = '$id'
            AND NOT d.clasemov_id IN ('17') -- no mostrar excedentes
            ORDER BY d.registro_det_id ASC", NULL, true, true);
            while ($d = $qp->Read()) {
                $did = $d['registro_det_id'];
        
?>
        <tr align="left" valign="top">
            <td class="cell <?=$prefix?>-state-<?=$d['registro_det_estado']?>"><?=$qp->recNo?></td>
            <td class="cell l"><?=$d['clasemov_desc']?></td>
            <td class="cell l"><?=$d['moneda_desc']?></td>
            <td class="cell r bold <?=$d['tipomov_id']=='E'?'c-red':''?>">
        <?php   if ($d['clasemov_id']=='18'): // faltante 
?>
                <span class="c-red fs-12"><?='- FALTANTE -'?></span>
        <?php   else:?>
                <?=Sys::NFormat(($d['registro_det_importe']))?>
        <?php   endif;?>
            </td>
        </tr> 
        <?php
            } 
        
?>
        </table>
    </td>
</tr>
</table>

    
</div>

</body>
</html>