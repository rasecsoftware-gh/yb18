<?php
	require_once '../../sys.php';
	$module = "sys_usuario";
	$prefix = "{$module}";
	
	$id_parent = Sys::GetR('id_parent', $id);
?>
<form id="<?=$prefix?>_profile_list_form" onsubmit="return false;">
<table class="grid" width="100%">
<tr>
	<td class="cell bold" colspan="7">Perfiles
		<span style="float: right; margin-left: 5px;">
		<a href="#" class="btn-icon save" onclick="<?=$prefix?>_update_multi_profile();" title="Guardar Cambios" style=""></a>
		<a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_profile();" title="Actualizar lista" style=""></a>
		</span>
	</td>
</tr>
<tr>
	<td class="cell-head bold" width="40">#</td>
	<td class="cell-head bold" width="40"><input type="checkbox" onchange="<?=$prefix?>_profile_select_all(this.checked);"/></td>
	<td class="cell-head bold">Descripcion</td>
	<td class="cell-head bold">&nbsp;</td>
</tr>
<?php
	$qp = new PgQuery("
	SELECT p.*, up.estado as up_estado
	FROM sys.usuario p
	LEFT JOIN sys.usuario_perfil up ON up.id_perfil=p.id_usuario AND up.id_usuario=$id_parent AND up.estado = 1
	WHERE p.estado = 1 AND p.is_profile = 1
	ORDER BY login", NULL, true, true);
	while ($d = $qp->Read()) {
		$did = $d['id_usuario'];
?>
<tr align="left" valign="top">
	<td class="cell"><?=$qp->recNo?></td>
	<td class="cell">
		<input class="<?=$prefix?>_profile_check" type="checkbox" name="list[]" value="<?=$did?>" <?=$d['up_estado']==1?'checked':''?>/>
	</td>
	<td class="cell l">
		<?=$d['login']?>
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
function <?=$prefix?>_update_multi_profile(id) {
	if (!confirm('Realmente desea guardar?')) return false;
	var params = $('#<?=$prefix?>_profile_list_form').serialize();
	$.post('modules/<?=$module?>/core.php', 'action=UpdateMultiProfile&id_usuario=<?=$id_parent?>&'+params, function (data) {
		if (data.trim() == 'ok') {
			sys.message('Se han guardado satisfactoriamente');
			<?=$prefix?>_reload_profile();
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_profile_select_all(checked) {
	if (checked) {
		$('.<?=$prefix?>_profile_check').attr('checked','checked');
	} else {
		$('.<?=$prefix?>_profile_check').removeAttr('checked');
	}
}
function <?=$prefix?>_reload_profile() {
	$.post('modules/<?=$module?>/profile.list.php', 'id_parent=<?=$id_parent?>', function (data) { $('#<?=$prefix?>_detail_container').html(data); });
};
// Forms
</script>