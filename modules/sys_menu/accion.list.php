<?php
	require_once '../../sys.php';
	$module = 'sys_menu';
	$prefix = "{$module}_accion";
	$task = Sys::GetR('task', $task);
	$id_parent = Sys::GetR('id_parent', $id);
	
?>
<form id="<?=$prefix?>_list_form" onsubmit="return false;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head bold" colspan="12">Acciones
		<span style="float: right;">
		<a href="#" class="btn-icon add" onclick="<?=$prefix?>_new();" title="Nuevo registro"></a>
		<a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_list();" title="Actualizar lista"></a>
		</span>
	</td>
</tr>
<tr>
	<td class="cell-head bold">#</td>
	<td class="cell-head bold">Descripcion</td>
	<td class="cell-head bold">Estado</td>
	<td class="cell-head bold">&nbsp;</td>
</tr>
<?php
	$q = new PgQuery("
	select * from sys.accion where id_menu = $id_parent order by descripcion
	", NULL, true, true);
	while ($d = $q->Read()) {
		$did = $d['id_accion'];
?>
<tr align="left">
	<td class="cell" title="<?=$d['keyname']?>"><?=$q->recNo?></td>
	<td class="cell"><?=$d['descripcion']?></td>
	<td class="cell">
		<input class="<?=$prefix?>_check" type="checkbox" name="<?=$prefix.'_'.$did?>" value="<?=$did?>" <?=$d['estado']==1?'checked':''?>/>
	</td>
	<td class="cell">
		<span style="float: right;">
		<a href="#" class="btn-icon edit" onclick="<?=$prefix?>_edit(<?=$did?>);" title="Modificar"></a>
		<a href="#" class="btn-icon delete" onclick="<?=$prefix?>_delete(<?=$did?>);" title="Eliminar"></a>
		</span>
	</td>
</tr>
<?php 
	} 
?>
</table>
</form>
<script>
function <?=$prefix?>_new() {
	var w = new AccionWindow({p_task: 'new'});
	w.show();
};
function <?=$prefix?>_edit(id) {
	var w = new AccionWindow({p_task: 'edit', p_id: id});
	w.show();
};
function <?=$prefix?>_delete(id) {
	if (!confirm('Realmente desea eliminar?')) return;
	var params = $.param({'action': 'DeleteAccion', 'id': id});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha eliminado satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
};
$('.<?=$prefix?>_check').click(function () {
	var id = $(this).get(0).value;
	var v = $(this).get(0).checked?1:0;
	var params = $.param({'action': 'AccionChangeEstado', 'id': id, 'value': v});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if (data.trim() == 'ok') {
			sys.message('Se ha cambiado satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
});
// Forms
AccionWindow = Ext.extend(Ext.Window, {
	id: '<?=$prefix?>_window', title: 'Accion', width: 440, height: 200, modal: true,
	initComponent: function() {
		this.on('show', function (s) {
			var params = $.param({'task': this.p_task, 'id': this.p_id, 'id_parent': '<?=$id_parent?>'});
			$.post('modules/<?=$module?>/accion.form.php', params, 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		AccionWindow.superclass.initComponent.call(this);
	}
});
</script>