<?php
	require_once '../../sys.php';
	$module = "sys_usuario";
	$prefix = "{$module}_pventa";
	
	$id_parent = Sys::GetR('id_parent', $id);
	
?>
<form id="<?=$prefix?>_list_form" onsubmit="return false;">
<table class="grid" width="100%">
<tr>
    <td class="cell bold" colspan="7">Puntos de Venta
        <span style="float: right; margin-left: 5px;">
        <a href="#" class="btn-icon save" onclick="<?=$prefix?>_multi_update();" title="Guardar Cambios" style=""></a>
        <a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_list();" title="Actualizar lista" style=""></a>
        </span>
    </td>
</tr>
<tr>
    <td class="cell-head bold" width="40">#</td>
    <td class="cell-head bold" width="40"><input type="checkbox" onchange="<?=$prefix?>_select_all(this.checked);"/></td>
    <td class="cell-head bold">Codigo</td>
    <td class="cell-head bold">Descripcion</td>
    <td class="cell-head bold">&nbsp;</td>
</tr>
<?php
    $qp = new PgQuery("
    SELECT p.*,
    e.establecimiento_desc, 
    up.usuario_pventa_estado as up_estado
    FROM public.pventa p
    JOIN public.establecimiento e On e.establecimiento_id = p.establecimiento_id
    LEFT JOIN sys.usuario_pventa up ON up.pventa_id = p.pventa_id AND up.usuario_id=$id_parent AND up.usuario_pventa_estado = '1'
    WHERE p.pventa_estado = '1'
    ORDER BY p.pventa_id", NULL, true, true);
    while ($d = $qp->Read()) {
        $did = $d['pventa_id'];
?>
<tr align="left" valign="top">
    <td class="cell"><?=$qp->recNo?></td>
    <td class="cell">
        <input class="<?=$prefix?>_check" type="checkbox" name="list[]" value="<?=$did?>" <?=$d['up_estado']==1?'checked':''?>/>
    </td>
    <td class="cell l">
        <?=$d['pventa_id']?> | <?=$d['establecimiento_desc']?> | <?=$d['pventa_desc']?>
    </td>
    <td class="cell">
        <span style="float: right;">
        </span>
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
function <?=$prefix?>_multi_update(id) {
    if (!confirm('Realmente desea guardar?')) return false;
    var params = $('#<?=$prefix?>_list_form').serialize();
    $.post('modules/<?=$module?>/core.php', 'action=UsuarioPVentaMultiUpdate&usuario_id=<?=$id_parent?>&'+params, function (data) {
        if (data.trim() == 'ok') {
            sys.message('Se han guardado satisfactoriamente');
            <?=$prefix?>_reload_list();
        } else {
            alert(data);
        }
    });
};
function <?=$prefix?>_select_all(checked) {
    if (checked) {
        $('.<?=$prefix?>_check').attr('checked','checked');
    } else {
        $('.<?=$prefix?>_check').removeAttr('checked');
    }
}
function <?=$prefix?>_reload_list() {
    $.post('modules/<?=$module?>/usuario_pventa.list.php', 'id_parent=<?=$id_parent?>', function (data) { $('#<?=$prefix?>_detail_container').html(data); });
};
// Forms
</script>