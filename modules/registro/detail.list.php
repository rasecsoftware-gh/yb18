<?php
	require_once '../../sys.php';
	$module = "registro";
	$prefix = "{$module}_det";
	
	$id_parent = Sys::GetR('id_parent', '');
	
?>
<style>
.<?=$prefix?>-state-0 {
    background-color: red;
}
</style>
<form id="<?=$prefix?>_frm_list" onsubmit="return false;">
<table class="grid" width="100%">
<tr>
    <td class="cell-head bold" width="40">#</td>
    <td class="cell-head bold">Tipo</td>
    <td class="cell-head bold">Moneda</td>
    <td class="cell-head bold r">Importe</td>
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
    WHERE d.registro_id = '$id_parent'
    AND NOT d.clasemov_id IN ('17') -- no mostrar excedentes
    ORDER BY d.registro_det_id ASC", NULL, true, true);
    while ($d = $qp->Read()) {
        $did = $d['registro_det_id'];
?>
<tr align="left" valign="top">
    <td class="cell <?=$prefix?>-state-<?=$d['registro_det_estado']?>"><?=$qp->recNo?></td>
    <td class="cell l"><?=$d['clasemov_desc']?></td>
    <td class="cell l"><?=$d['moneda_desc']?></td>
    <td class="cell r fs-14 bold <?=$d['tipomov_id']=='E'?'c-red':''?>">
<?php   if ($d['clasemov_id']=='18'): // faltante 
?>
        <span class="c-red fs-12"><?='- FALTANTE -'?></span>
<?php   else:?>
        <?=Sys::NFormat(abs($d['registro_det_importe']))?>
<?php   endif;?>
    </td>
</tr> 
<?php
    } 
?>
</table>
</form>
<script>
// functions
// data functions
function <?=$prefix?>_reload_list() {
    $.post('modules/<?=$module?>/detail.list.php', 'id_parent=<?=$id_parent?>', function (data) { $('#<?=$prefix?>_det_container').html(data); });
};
// Forms
</script>