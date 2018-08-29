<?php
	require_once '../../sys.php';
	$id = Sys::GetUserId();
	
	$dr = new PgDataRow("sys.usuario");
	$dr->decode = true;
	$dr->Read($id);
	$r = $dr->GetRow();
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td class="bold" style="padding: 5px;">
	(ID: <?=$id?>)
	</td>
</tr>
</table>
<form id="usuario_quickform" method="post">
	<input type="hidden" name="id_usuario" value="<?=$id?>"/>
	<table width="*" border="0" cellpadding="0" cellspacing="0">
	<tr align="left" valign="top">
		<td class="frm-pd" width="120">Nombre</td>
		<td class="frm-pd">
			<input type="text" name="nombre" value="<?=$r['login']?>" size="30" maxlength="30" readonly="readonly"/>
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd">Contrase&ntilde;a Actual</td>
		<td class="frm-pd">
			<input type="password" name="pwd" value="" size="30" maxlength="200"/>
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd">Nueva Contrase&ntilde;a</td>
		<td class="frm-pd">
			<input type="password" name="pwd1" value="" size="30" maxlength="200"/>
		</td>
	</tr>
	<tr align="left" valign="top">
		<td class="frm-pd">Confirmar Contrase&ntilde;a</td>
		<td class="frm-pd">
			<input type="password" name="pwd2" value="" size="30" maxlength="200"/>
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr>
		<td colspan="2" align="right">
			<button type="submit" onclick="quickform_usuario_save(); return false;">Guardar</button>
			<button type="button" onclick="Ext.getCmp('quick_usuario_window').close();">Cancelar</button>
		</td>
	</tr>
	</table>
</form>
<script>
// controls
// actions
function quickform_usuario_save() {
	var params = $('#usuario_quickform').serialize();
	$.post('modules/sys_usuario/core.php', 'action=ChangePass&'+params, function (data) {
		if (data.trim() == 'ok') {
			Ext.getCmp('quick_usuario_window').close();
		} else {
			alert(data);
		}
	});
};
</script>