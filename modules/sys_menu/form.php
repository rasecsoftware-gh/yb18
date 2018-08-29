<?php
	require_once "../../sys.php";
	$module = "sys_menu";
	$prefix = $module;
	$task = Sys::GetR('task', 0);
	$id = Sys::GetR('id', 0);
	
	$list = unserialize(Sys::GetS($prefix.'list', 'a:0:{}'));
	$list_index = array_search($id, $list);
	
	$dr = new PgDataRow('sys.menu');
	//echo "<pre>".print_r($dr->schema, true)."</pre>";
	$dr->decode = true;
	if ($task == 'new') {
		$dr->Create(true);
		$r = $dr->GetRow();
		$r['actionscript'] = "loadmodule('idmodulo', 'Titulo', 'path', 'params');";
		$id = $r['id_menu'];
	} else {
		$dr->Read($id);
		$r = $dr->GetRow();
	}
	$inf_reg = $r['inf_reg'];
?>
<script>
</script>
<table class="" width="100%">
<tr>
	<td class="cell" colspan="2">
		<span class="bold c-gray fs-12" style=""><?=$task=='new'?'Nuevo':'Modificar'?> Menu</span>
		<span class="c-gray fs-10">(id: <?=$id?>)&nbsp;</span>
		<button type="button" onclick="<?=$prefix?>_update()">Guardar</button>
		<button type="button" onclick="<?=$prefix?>_cancel()">Cancelar</button>
<?php
	if ($task=='edit') { 
?>
		<button type="button" onclick="<?=$prefix?>_reload()">Recargar</button>
		&nbsp;|&nbsp;<button type="button" onclick="<?=$prefix?>_renew()">Nuevo</button>
<?php
	} 
?>
		<span style="float: right;">
<?php
	if ($list_index-1 >= 0) { 
?>
			<a href="#" class="btn-icon prev" onclick="<?=$prefix?>_reload(<?=$list[$list_index-1]?>);" title="Anterior"></a>
<?php
	} else { 
?>
			<a href="#" class="btn-icon prev-disabled" onclick="sys.message('No hay mas registros'); return false;" title="Anterior"></a>
<?php
	} 
?>	
<?php
	if ($list_index+1 < count($list)) { 
?>
			<a href="#" class="btn-icon next" onclick="<?=$prefix?>_reload(<?=$list[$list_index+1]?>);" title="Siguiente"></a>
<?php
	} else { 
?>
			<a href="#" class="btn-icon next-disabled" onclick="sys.message('No hay mas registros'); return false;" title="Siguiente"></a>
<?php
	} 
?>		
		</span>
	</td>
</tr>
<tr align="left" valign="top">
	<td class="cell" style="" align="left" valign="top">
		<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
			<input type="hidden" name="id_menu" value="<?=$id?>"/>
			<table class="">
			<tr align="left" valign="top">
				<td class="frm-pd">Nombre</td>
				<td class="frm-pd" colspan="3">
					<input id="<?=$prefix?>_nombre" type="text" name="nombre" value="<?=$r['nombre']?>" style="width: 450px;"/>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd">Activo</td>
				<td class="frm-pd" colspan="3">
					<select name="estado">
						<option value="1" <?=$r['estado']==1?'selected':''?>>Si</option>
						<option value="0" <?=$r['estado']==0?'selected':''?>>No</option>
					</select>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd">Menu Principal</td>
				<td class="frm-pd" colspan="3">
					<select name="id_parent">
						<option value="0">- nadie -</option>
					<?php
	$qm = new PgQuery("SELECT * FROM sys.menu WHERE id_parent IS NULL ORDER BY nombre", NULL, true, true);
						while ($rm = $qm->Read()) { 
?>
						<option value="<?=$rm['id_menu']?>" <?=$rm['id_menu']==$r['id_parent']?'selected':''?>><?=$rm['nombre']?></option>
					<?php
	} 
?>
					</select>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd">Es comienzo de grupo:</td>
				<td class="frm-pd" colspan="3">
					<select name="begin_group">
						<option value="1" <?=$r['begin_group']==1?'selected':''?>>Si</option>
						<option value="0" <?=$r['begin_group']==0?'selected':''?>>No</option>
					</select>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="4">Action Script (command)</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="4">
					<textarea name="actionscript" class="fs-10" style="font-family: 'courier new'!important; width: 590px; height: 100px;"><?=$r['actionscript']?></textarea>
				</td>
			</tr>
			</table>
		</form>
	</td>
	<td class="cell" width="250">
		<div id="<?=$prefix?>_accion_container" class="pd">
		</div>
	</td>
</tr>
</table>
<div>
<?=Sys::DisplayInfReg($inf_reg); 
?>
</div>
<script>	
	// controls
	// functions
	// data functions
	function <?=$prefix?>_renew() {
		var params = $.param({'task': 'new'});
		$.post('modules/<?=$module?>/form.php', params, function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
	function <?=$prefix?>_update() {
		if (confirm('Realmente desea guardar?')) {
			// quitamos el caracter ZERO WIDTH SPACE UTF-8: e2 80 8b, es xtranio pero con esto se quita eso.. jojo jijiji jujuju  
			var params = $('#<?=$prefix?>_frm').serialize().replace(/%E2%80%8B/gi,'');
			$.post('modules/<?=$module?>/core.php', 'action=Update&'+params, function (data) {
				if ($.trim(data)=='ok') {
					sys.message('Se ha guardado satisfactoriamente');
					<?=$prefix?>_reload();
					<?=$prefix?>_reload_list();
				} else {
					alert(data);
				}
			});
		}
	};
	function <?=$prefix?>_cancel() {
		Ext.getCmp('<?=$prefix?>_window').close();
	};
	function <?=$prefix?>_reload(id) {
		var _id = id;
		if (typeof(_id)=='undefined') {
			_id = <?=$id?>;
		}
		var params = $.param({'task': 'edit', 'id': _id});
		$.post('modules/<?=$module?>/form.php', params, function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
	function <?=$prefix?>_accion_reload_list() {
		var params = $.param({
			'id_parent': <?=$id?>
		});
		$('#<?=$prefix?>_accion_container').html('cargando...');
		$.post('modules/<?=$module?>/accion.list.php', params, function (data) { 
			$('#<?=$prefix?>_accion_container').html(data);
		});
	};
// init
	$('#<?=$prefix?>_nombre').focus();
	<?=$prefix?>_accion_reload_list();
</script>