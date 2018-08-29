<?php
	require_once '../../sys.php';
	$module = "sys_usuario";
	$prefix = "{$module}_menu";
	
	$id_parent = Sys::GetR('id_parent', 0);
?>
<form id="<?=$prefix?>_list_form" onsubmit="return false;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold" colspan="4">Menu
		<span style="float: right; margin-left: 5px;">
		<a href="#" class="btn-icon save" onclick="<?=$prefix?>_update_multiple();" title="Guardar Cambios" style=""></a>
		<a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_load_list();" title="Actualizar lista" style=""></a>
		</span>
	</td>
</tr>
<tr>
	<td class="cell-head bold" width="40">#</td>
	<td class="cell-head bold" width="40"><input type="checkbox" onchange="<?=$prefix?>_select_all(this.checked);"/></td>
	<td class="cell-head bold">Descripcion</td>
	<td class="cell-head bold">&nbsp;</td>
</tr>
<?php
	// menus
	$q = new PgQuery("
	SELECT m.*, um.estado as um_estado
	FROM sys.menu m
	LEFT JOIN sys.usuario_menu um ON um.id_menu=m.id_menu AND um.id_usuario=$id_parent AND um.estado = 1
	WHERE m.estado = 1 AND m.id_parent IS NULL 
	ORDER BY orden", NULL, true, true);
	while ($d = $q->Read()) {
		$did = $d['id_menu'];
		// sub menus query
		$qd = new PgQuery("
		SELECT m.*, 
		COALESCE(um.id_u_m, 0) as id_u_m, 
		COALESCE(um.estado, 0) as um_estado
		FROM sys.menu m
		LEFT JOIN sys.usuario_menu um ON um.id_menu=m.id_menu AND um.id_usuario=$id_parent AND um.estado = 1
		WHERE m.estado = 1 AND m.id_parent={$d['id_menu']} 
		ORDER BY orden", NULL, true, true);
?>
<tr align="left" valign="top">
	<td class="cell"><?=$q->recNo?></td>
	<td class="cell">
<?php
		if ($qd->IsEmpty()) { 
?>
		<input class="<?=$prefix?>_check" type="checkbox" name="list[]" value="<?=$did?>" <?=$d['um_estado']==1?'checked':''?>/>
<?php
		} 
?>
	</td>
	<td class="cell l">
		<div class="l c-silver fs-7 r" style="display: block; margin: -3px 0 -5px 0px;"><?=$did?></div>
		<div class="l"><?=$d['nombre']?></div>
	</td>
	<td class="cell">
		<span style="float: right;">
		</span>
	</td>
</tr>
<?php
		// sub menus reader
		while ($d2 = $qd->Read()) {
			$did = $d2['id_menu'];
			// acciones query
			$qa = new PgQuery("
			SELECT a.*, 
			COALESCE(ua.id_u_a, 0) as id_u_a, 
			ua.estado as ua_estado
			FROM sys.accion a
			LEFT JOIN sys.usuario_accion ua ON ua.id_accion = a.id_accion AND ua.id_u_m = {$d2['id_u_m']}
			WHERE a.id_menu = $did AND {$d2['um_estado']} = 1
			ORDER BY a.id_accion
			", NULL, true, true);
?>
<tr align="left" valign="top">
	<td class="cell c-gray r"><?=$q->recNo?>.<?=$qd->recNo?></td>
	<td class="cell"><input class="<?=$prefix?>_check" type="checkbox" name="list[]" value="<?=$did?>" <?=$d2['um_estado']==1?'checked':''?>/></td>
	<td class="cell l">
		<div class="l c-silver fs-7 r" style="display: block; margin: -3px 0 -5px 0px;"><?=$did?></div>
		<div class="l" style="padding-left: 20px;"><?=$d2['nombre']?></div>
<?php
			if ($qa->recordCount > 0):?>
		<div class="pd" style="margin: 1px 3px 1px 20px; border: 1px solid #F0F0F0;">
<?php
		
				// acciones reader
				while ($a = $qa->Read()) {
?>
			<span class="" style="">
				<input class="<?=$prefix?>_accion_check" type="checkbox" value="<?=$a['id_accion']?>" parentid="<?=$d2['id_u_m']?>" rid="<?=$a['id_u_a']?>" <?=$a['ua_estado']==1?'checked':''?>/>&nbsp;
				<span class="c-gray" style=""><?=$a['descripcion']?></span>
			</span>&nbsp;
<?php
				}?>
		</div>
<?php
			endif;?>
	</td>
	<td class="cell">
		<span style="float: right;">
		</span>
	</td>
</tr> 
<?php
		}
	} 
?>
</table>
</form>
<script>
$('.<?=$prefix?>_accion_check').click(function () {
	var params = $.param({
		'action': 'UsuarioAccionChangeEstado',
		//'id_u_a': $(this).attr('rid'),  
		'id_u_m': $(this).attr('parentid'), 
		'id_accion': $(this).get(0).value, 
		'value': ($(this).get(0).checked?1:0)
	});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if (data.trim() == 'ok') {
			sys.message('Se ha cambiado satisfactoriamente');
		} else {
			alert(data);
			<?=$prefix?>_load_list();
		}
	});
});
// functions
// data functions
function <?=$prefix?>_update_multiple(id) {
	if (!confirm('Realmente desea guardar?')) return false;
	var params = $('#<?=$prefix?>_list_form').serialize();
	$.post('modules/<?=$module?>/core.php', 'action=UsuarioMenuUpdateMultiple&id_usuario=<?=$id_parent?>&'+params, function (data) {
		if (data.trim() == 'ok') {
			sys.message('Se han guardado satisfactoriamente');
			<?=$prefix?>_load_list();
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
};
// Forms
</script>