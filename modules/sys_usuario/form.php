<?php
	require_once "../../sys.php";
	$module = "sys_usuario";
	$prefix = $module;
	$task = Sys::GetR('task', 0);
	$id = Sys::GetR('id', 0); 
	
	$list = unserialize(Sys::GetS($prefix.'list', 'a:0:{}'));
	$list_index = array_search($id, $list);
	
	$dr = new PgDataRow('sys.usuario');
	$dr->decode = true;
	if ($task == 'new') {
		$dr->Create();
		$r = $dr->GetRow();
		$id = $r['id_usuario'];
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
		<span class="bold c-gray fs-12" style=""><?=$task=='new'?'Nuevo':'Modificar'?> Usuario</span>
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
	<td class="cell" style="width: 300px;" align="left" valign="top">
		<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
			<input type="hidden" name="id_usuario" value="<?=$id?>"/>
			<table class="">
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="4">Login</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="4">
					<input id="<?=$prefix?>_login" type="text" name="login" value="<?=$r['login']?>"  style="width: 250px;"/>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="4">Contrase&ntilde;a</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="4">
					<input id="<?=$prefix?>_password" type="password" name="password" value="<?=$r['password']?>"  style="width: 250px;"/>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="4">Descripcion</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" colspan="4">
					<input id="<?=$prefix?>_nombre" type="text" name="nombre" value="<?=$r['nombre']?>" maxlength="100" style="width: 250px;"/>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd" width="100">Activo</td>
				<td class="frm-pd" colspan="3">
					<select id="<?=$prefix?>_activo"  name="activo">
						<option value="1" <?=$r['estado']==1?'selected':''?>>Si</option>
						<option value="0" <?=$r['estado']==0?'selected':''?>>No</option>
					</select>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd">Es Administrador</td>
				<td class="frm-pd" colspan="3">
					<select id="<?=$prefix?>_is_admin"  name="is_admin">
						<option value="1" <?=$r['is_admin']==1?'selected':''?>>Si</option>
						<option value="0" <?=$r['is_admin']==0?'selected':''?>>No</option>
					</select>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd">Es Perfil</td>
				<td class="frm-pd" colspan="3">
					<select id="<?=$prefix?>_is_profile"  name="is_profile">
						<option value="1" <?=$r['is_profile']==1?'selected':''?>>Si</option>
						<option value="0" <?=$r['is_profile']==0?'selected':''?>>No</option>
					</select>
				</td>
			</tr>
			<tr align="left" valign="top">
				<td class="frm-pd">Acceso Externo</td>
				<td class="frm-pd" colspan="3">
					<select id="<?=$prefix?>_externo"  name="externo">
						<option value="1" <?=$r['externo']==1?'selected':''?>>Si</option>
						<option value="0" <?=$r['externo']==0?'selected':''?>>No</option>
					</select>
				</td>
			</tr>
			
			</table>
		</form>
		<div>
		<?=Sys::DisplayInfReg($inf_reg); 
?>
		</div>
	</td>
	<td class="cell">
<?php
	if ($task=='edit' && $r['is_admin']==0) { 
?>
		<div>
			<button type="button" onclick="<?=$prefix?>_menu_load_list();">Configurar Menu</button>
<?php
		if ($r['is_profile']==0 && $r['is_admin']==0) { 
?>
			<button type="button" onclick="<?=$prefix?>_profile_load_list();">Configurar Perfil</button>
<?php
		} 
?>
			<button type="button" onclick="<?=$prefix?>_pventa_load_list();">Configurar Punto de Venta</button>
		</div>
<?php
	} 
?>		
		<div id="<?=$prefix?>_detail_container" class="grid-container" style="height: 490px; overflow: auto;">
<?php
		if ($task=='edit' && $r['is_admin']==1):?>
			<div class="fs-9 c-gray" style="padding: 20px 20px;">
			El usuario esta tipado como <b class="fs-9">Administrador</b>, asi que tiene acceso total al sistema y no requiere
			mas configuracion.
			</div>
<?php
		elseif ($task=='new'):?>
			<div class="fs-9 c-gray" style="padding: 20px 20px;">
			Complete la informacion basica del usuario, luego haga click en 'Guardar', para posteriormente
			configurar las opciones adicionales.
			</div>
<?php
		endif;?>
		</div>
	</td>
</tr>
</table>
<script>	
//vars
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
					<?=$prefix?>_load_list();
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
	function <?=$prefix?>_menu_load_list() {
		$.post('modules/<?=$module?>/usuario_menu.list.php', 'id_parent=<?=$id?>', function (data) { $('#<?=$prefix?>_detail_container').html(data); });
	};
	function <?=$prefix?>_profile_load_list() {
		$.post('modules/<?=$module?>/usuario_profile.list.php', 'id_parent=<?=$id?>', function (data) { $('#<?=$prefix?>_detail_container').html(data); });
	};
	function <?=$prefix?>_pventa_load_list() {
		$.post('modules/<?=$module?>/usuario_pventa.list.php', 'id_parent=<?=$id?>', function (data) { $('#<?=$prefix?>_detail_container').html(data); });
	};
// init
	$('#<?=$prefix?>_login').focus();
<?php
	if ($task=='edit' && $r['is_admin']==0):?>
	<?=$prefix?>_menu_load_list();
<?php
	endif;?>
</script>