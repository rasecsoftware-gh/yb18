<?php
	require_once("../../sys.php");
	$module = "moneda";
	$prefix = "$module";
	$task = Sys::GetR('task', 0);
	$id = Sys::GetR('id', 0);
	
	$list = unserialize(Sys::GetS($prefix.'list', 'a:0:{}'));
	$list_index = array_search($id, $list);
	
	$dr = new PgDataRow('public.moneda');
	$dr->decode = true;
	if ($task == 'new') {
		$dr->Create();
		$r = $dr->GetRow();
		$id = $r['moneda_id'];
	} else {
		$dr->Read($id);
		$r = $dr->GetRow();
	}
?>
<style>
</style>
<table class="" width="100%">
<tr>
	<td class="cell" colspan="2">
		<span class="bold c-gray fs-12" style=""><?=$task=='new'?'Nueva':'Modificar'?> Moneda</span>
		<span class="c-gray fs-10">(<?=$id?>)&nbsp;</span>
		<button type="button" onclick="<?=$prefix?>_update()">Guardar</button>
		<button type="button" onclick="<?=$prefix?>_cancel()">Salir</button>
<?php
	if ($task=='edit') { 
?>
		|<button type="button" onclick="<?=$prefix?>_renew()">Nuevo</button>
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
	<td class="cell" style="width: 570px;" align="left" valign="top">
		<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
		<input type="hidden" name="moneda_id" value="<?=$id?>"/>
		<table class="">
		<tr align="left" valign="top">
			<td class="frm-pd" colspan="1">Nombre</td>
			<td class="frm-pd" colspan="3">
				<input id="<?=$prefix?>_moneda_desc" type="text" name="moneda_desc" value="<?=$r['moneda_desc']?>" style="width: 250px;"/>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="frm-pd" colspan="1">Simbolo</td>
			<td class="frm-pd" colspan="3">
				<input id="<?=$prefix?>_moneda_simbolo" type="text" name="moneda_simbolo" value="<?=$r['moneda_simbolo']?>" style="width: 150px;"/>
			</td>
		</tr>
		<tr align="left" valign="top">
            <td class="frm-pd" colspan="1">Moneda Minima</td>
            <td class="frm-pd" colspan="3">
                <input id="<?=$prefix?>_moneda_min" type="text" name="moneda_min" value="<?=Sys::NFormat($r['moneda_min'])?>" style="width: 150px;"/>
            </td>
        </tr>
		</table>
		</form>
	</td>
</tr>
</table>
<div>
<?=Sys::DisplayInfReg($r['syslog']); 
?>
</div>
<script>	
	// controls
	// functions
	// data functions
	function <?=$prefix?>_renew() {
		$.post('modules/<?=$module?>/form.php', 'task=new', function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
	function <?=$prefix?>_update() {
		if (confirm('Realmente desea guardar?')) {
			var params = $('#<?=$prefix?>_frm').serialize();
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
		var params = Ext.urlEncode({'task': 'edit', 'id': _id});
		$.post('modules/<?=$module?>/form.php', params, function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
	// init
	$('#<?=$prefix?>_moneda_desc').focus();
</script>