<?php
	require_once '../../sys.php';
	Sys::DisableClassListen();
	require_once 'core.php';
	$module = 'cn';
	$prefix = "{$module}";
	// params
	$p_task = Sys::GetR('task', 'edit');
	$id = Sys::GetR('id', '');
	$secfun = Sys::GetS("{$prefix}secfun", '');
	$depen = Sys::GetS("{$prefix}depen", '');
	$tipo = Sys::GetS("{$prefix}tipo", '');
	// vars conditions
	$anipre = Sys::GetPeriodo();
	$is_admin = Sys::GetUserIsAdmin();
	// navigation vars
	$list = unserialize(Sys::GetS($prefix.'list', 'a:0:{}'));
	$list_index = array_search($id, $list);
	
	$dr = new SqlDataRow("dbo.Cuadro_Mensual");
	$dr->decode = true;
	$dr->Read(array($id, $anipre));
	$r = $dr->GetRow();
	$r['des_depen'] = SqlQuery::GetQueryVal("SELECT dbo.fn_ObtieneDescripcionDependencia('$anipre', '$depen')", '', NULL, true);
	$r['des_secfun'] = SqlQuery::GetQueryVal("SELECT dbo.fn_ObtenerDescripcionSecuencia('$anipre', '$secfun')", '', NULL, true);
?>
<div style="padding: 1px 2px;">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td class="cell" colspan="1">
		<span class="bold c-gray fs-12" style="">Cuadro Mensual</span>
		&nbsp;&nbsp;		
<?php
	if ($task=='edit' && $r['f_cuames_esta']=='00'): // generado?>
		<button type="button" onclick="<?=$prefix?>_anular()">Anular</button>
<?php
	endif;?>
<?php
	if ($task=='edit' && $r['f_cuames_esta']=='99'): // 
?>
		<button type="button" onclick="<?=$prefix?>_cancel_anular()" class="c-red" title="Cancelar Anulacion">Cancelar Anulacion</button>
<?php
	endif;?>
<?php
	if ($task=='edit' && $r['f_cuames_esta']=='00'):?>
		<button class="" type="button" onclick="<?=$prefix?>_print('<?=$id?>')">Imprimir</button>
<?php
	endif;?>
		<button type="button" onclick="<?=$prefix?>_cancel()">Salir</button>
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
			<a href="#" class="btn-icon prev" onclick="<?=$prefix?>_reload('<?=$list[$list_index-1]?>');" title="Anterior"></a>
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
			<a href="#" class="btn-icon next" onclick="<?=$prefix?>_reload('<?=$list[$list_index+1]?>');" title="Siguiente"></a>
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
</table>
<script>

</script>
<div style="border: 1px solid #D5D5D5; margin-top: 2px;">
	<form id="<?=$prefix?>_form" method="post">
	<input type="hidden" name="C_CuaMes" value="<?=$id?>"/>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr align="left" valign="top">
		<td class="frm-pd bold c-gray" valign="middle">Numero:</td>
		<td class="frm-pd">
			<span class="bold upper fs-14 c-silver">00</span><span class="bold upper fs-14 c-black"><?=intval($id)?></span>
			&nbsp;
			<?php
				$estado = $r['f_cuames_esta'];
				$einfo = Core::GetEstadoInfo($estado);
			?>
			<span class="bold upper" style="background: <?=$einfo[1]?>!important; color: <?=$einfo[2]?>!important; border: 1px solid black; padding: 1px 5px; float: right;"><?=$einfo[0]?></span>
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd c-gray" width="90">Secuencia Func.:</td>
		<td class="frm-pd" colspan="1">
			<?=$r['des_secfun']?>	
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd c-gray">Dependencia:</td>
		<td class="frm-pd" colspan="1">
			<?=$r['des_depen']?>	
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd c-gray">Mes:</td>
		<td class="frm-pd" colspan="1">
			<?=strtoupper(Sys::GetMonthName(intval($r['c_mespre'],10)))?>	
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd c-gray">Descripcion:</td>
		<td class="frm-pd" colspan="1">
		<?php
	if ($estado=='00'):?>
			<a href="javascript: void(0)" class="c-theme" onclick="<?=$prefix?>_update_descripcion()" title="cambiar descripcion"><?=$r['n_cuames_desc']?></a>
		<?php
	else:?>
			<?=$r['n_cuames_desc']?>
		<?php
	endif;?>	
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd c-gray">Fecha:</td>
		<td class="frm-pd" colspan="1">
			<?=$r['d_cuames_fecha']?>	
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd c-gray">Usuario:</td>
		<td class="frm-pd" colspan="1">
			<?=$r['usuario']?>	
		</td>
	</tr>
	</table>
	</form>
</div>
<div id="<?=$prefix?>_detail_container" style="margin: 2px 0 0 0;">
</div>
</div>
<script>
// controls
// actions
function <?=$prefix?>_update_descripcion() {
	Ext.MessageBox.prompt('Cuadro Mensual', 'Especifique la Descripcion', 
	function (btn, nvalue) {
		if (btn == 'ok') {
			var params= $.param({action: 'UpdateDescripcion', 'id': '<?=$id?>', 'value': nvalue});
			$.post('modules/<?=$module?>/core.php', params, function (data) {
				if ($.trim(data) == 'ok') {
					sys.message('Se ha modificado satisfactoriamente');
					<?=$prefix?>_reload();
				} else {
					alert(data);
				}
			});	
		} 
	}, null, false, '<?=$r['n_cuames_desc']?>'); 
};
function <?=$prefix?>_anular() {
	if (!confirm('Realmente desea ANULAR el Cuadro Mensual?')) return;
	var params = 'id=<?=$id?>';
	$.post('modules/<?=$module?>/core.php', 'action=Anular&'+params, function (data) {
		if (data.trim() == 'ok') {
			sys.message('Se ha Anulado satisfactoriamente');
			<?=$prefix?>_reload();
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_cancel_anular() {
	if (!confirm('Realmente desea Cancelar la Anulacion?')) return;
	var params = $.param({action: 'CancelAnular', 'id': '<?=$id?>'});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if (data.trim() == 'ok') {
			sys.message('Se ha Cancelado la Anulacion satisfactoriamente');
			<?=$prefix?>_reload();
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_cancel() {
	<?=$prefix?>_reload_list();
};
function <?=$prefix?>_reload(id) {
	var _id = id;
	if (typeof(_id)=='undefined') {
		_id = '<?=$id?>';
	}
	var params = $.param({'task': 'edit', 'id': _id});
	$('#<?=$prefix?>_cpanel').load('modules/<?=$module?>/form.php', params);
};
function <?=$prefix?>_renew() {
	<?=$prefix?>_new();
};
function <?=$prefix?>_load_details() {
	var params = $.param({'id_parent': '<?=$id?>'});
	$('#<?=$prefix?>_detail_container').load('modules/cn/detail.list.php?'+params);
};
// Forms
DRequisitoListWindow = Ext.extend(Ext.Window, {
	id:'<?=$prefix?>_requisito_list_window', title: 'Requisitos', width: 500, height: 400, modal: true, autoScroll: true,
	initComponent: function() {
		this.on('show', function (s) {
			$.post('modules/<?=$module?>/requisito.list.php', 'id_procedimiento='+this.p_id_procedimiento, 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		DRequisitoListWindow.superclass.initComponent.call(this);
	}
});
// init
//$('#<?=$prefix?>_id_t_t').focus();
<?=$prefix?>_load_details();
</script>