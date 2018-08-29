<?php
	require_once "../../sys.php";
	$module = "sys_menu";
	$prefix = "{$module}_accion";
	$task = Sys::GetR('task', 0);
	$id = Sys::GetR('id', 0);
	$id_parent = Sys::GetR('id_parent', 0);
	
	$list = unserialize(Sys::GetS($prefix.'list', 'a:0:{}'));
	$list_index = array_search($id, $list);
	
	$dr = new PgDataRow('sys.accion');
	$dr->debug = false;
	//echo "<pre>".print_r($dr->schema, true)."</pre>";
	$dr->decode = true;
	if ($task == 'new') {
		$dr->Create();
		$r = $dr->GetRow();
		$id = $r['id_accion'];
	} else {
		$dr->Read($id);
		$r = $dr->GetRow();
	}
	//var_dump($r);
?>
<script>
</script>
<table class="" width="100%">
<tr>
	<td class="cell" colspan="1">
		<span class="bold c-gray fs-12" style=""><?=$task=='new'?'Nueva':'Modificar'?></span>
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
<tr>
	<td class="cell" style="width: 550px;" align="left" valign="top">
		<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
			<input type="hidden" name="id_accion" value="<?=$id?>"/>
			<input type="hidden" name="id_menu" value="<?=$id_parent?>"/>
			<table class="">
			<tr align="left" valign="top">
				<td class="frm-pd">Identificador</td>
				<td class="frm-pd">
					<input id="<?=$prefix?>_keyname" type="text" name="keyname" value="<?=$r['keyname']?>" style="width: 300px;"/>
					<span class="italic c-gray"><br/>(identificador unico para la accion en todo el sistema. Se puede usar comodines como el '*'.)</span>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="2">Descripcion</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="2">
					<input type="text" name="descripcion" value="<?=$r['descripcion']?>" style="width: 400px;"/>
				</td>
			</tr>
			</table>
		</form>
	</td>
</tr>
</table>
<div>
<?=Sys::DisplayInfReg($r['inf_reg']); 
?>
</div>
<script>	
	// controls
	// functions
	// data functions
	function <?=$prefix?>_renew() {
		var params = $.param({'task': 'new'});
		$.post('modules/<?=$module?>/accion.form.php', params, function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
	function <?=$prefix?>_update() {
		if (confirm('Realmente desea guardar?')) {
			// quitamos el caracter ZERO WIDTH SPACE UTF-8: e2 80 8b, es xtranio pero con esto se quita eso.. jojo jijiji jujuju  
			var params = $('#<?=$prefix?>_frm').serialize().replace(/%E2%80%8B/gi,'');
			$.post('modules/<?=$module?>/core.php', 'action=AccionUpdate&'+params, function (data) {
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
		$.post('modules/<?=$module?>/accion.form.php', params, function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
// init
	$('#<?=$prefix?>_keyname').focus();
</script>