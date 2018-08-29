<?php
    require_once '../../sys.php';
    $module = "registro";
    $prefix = "{$module}_saldomoneda";
    
    $now = date('d/m/Y H:i:s');
    
?>
<style>
.saldomoneda-title {
    padding: 5px 10px 5px 5px;
    display: block;
    color: black;
    border-bottom: 1px solid silver;  
    margin: 0 0 5px 0; 
}
</style>
<div class="saldomoneda-title"> Al <?=$now?></div>
<form id="<?=$prefix?>_frm_list" onsubmit="return false;">
<table class="grid" width="100%">
<tr>
    <td class="cell-head bold">Moneda</td>
    <td class="cell-head bold r">Importe</td>
</tr>
<?php
    $qp = new PgQuery("
    SELECT m.moneda_id, m.moneda_simbolo, m.moneda_desc, SUM(d.registro_det_importe) as total
    FROM public.moneda m 
    LEFT JOIN public.registro_det d On d.moneda_id = m.moneda_id and d.registro_det_estado = '1' 
    LEFT JOIN public.registro r On r.registro_id = d.registro_id and r.registro_id <> 'A'
        AND r.registro_fecha::date = '$now'
    WHERE true
    GROUP BY m.moneda_id, m.moneda_simbolo, m.moneda_desc
    ORDER BY m.moneda_id ASC", NULL, true, true);
    while ($d = $qp->Read()) {
        $did = $d['moneda_id'];
?>
<tr align="left" valign="top">
    <td class="cell l" title="<?=$d['moneda_desc']?>"><?=$d['moneda_simbolo']?></td>
    <td class="cell r"><?=Sys::NFormat(($d['total']))?></td>
</tr> 
<?php
    } 
?>
</table>
</form>
<script>
// functions
// data functions
// Forms
</script>