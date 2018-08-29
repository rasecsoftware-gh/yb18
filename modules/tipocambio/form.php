<?php
	require_once("../../sys.php");
	$module = "tipocambio";
	$prefix = "$module";
	$task = Sys::GetR('task', 0);
	$id = Sys::GetR('id', 0);
	
	$list = unserialize(Sys::GetS($prefix.'list', 'a:0:{}'));
	$list_index = array_search($id, $list);
	
	$dr = new PgDataRow('public.tipocambio');
	$dr->decode = true;
	if ($task == 'new') {
		$dr->Create();
		$r = $dr->GetRow();
		$id = $r['tipocambio_id'];
		$r['tipocambio_fecha'] = date('d/m/Y H:i:s');
	} else {
		$dr->Read($id);
		$r = $dr->GetRow();
	}
?>
<script>
</script>
<table class="" width="100%">
<tr>
	<td class="cell" colspan="2">
		<span class="bold c-gray fs-12" style=""><?=$task=='new'?'Nuevo':'Modificar'?> Tipo Cambio</span>
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
		<input type="hidden" name="tipocambio_id" value="<?=$id?>"/>
		<table class="">
		<tr align="left" valign="top">
			<td class="frm-pd">Fecha</td>
			<td class="frm-pd" colspan="3">
				<span class="bold c-gray fs-12"><?=$r['tipocambio_fecha']?></span>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="frm-pd" colspan="1">Establecimiento</td>
			<td class="frm-pd" colspan="3">
<?php
	$qe = new PgQuery("select * from public.establecimiento order by establecimiento_id", NULL, true, false);?>
				<select id="<?=$prefix?>_establecimiento_id" name="establecimiento_id">
<?php
	while ($re = $qe->Read()):?>
					<option value="<?=$re['establecimiento_id']?>" <?=$re['establecimiento_id']==$r['establecimiento_id']?'selected':''?>><?=$re['establecimiento_desc']?></option>
<?php
	endwhile; 
?>
				</select>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="frm-pd" colspan="1">Operacion</td>
			<td class="frm-pd" colspan="3">
<?php
	$qe = new PgQuery("select * from public.tipope where tipope_flag_tc = '1' order by tipope_id", NULL, true, false);?>
				<select id="<?=$prefix?>_tipope_id" name="tipope_id">
<?php
	while ($re = $qe->Read()):?>
					<option value="<?=$re['tipope_id']?>" <?=$re['tipope_id']==$r['tipope_id']?'selected':''?>><?=$re['tipope_desc']?></option>
<?php
	endwhile; 
?>
				</select>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="frm-pd" colspan="1">Recibe</td>
			<td class="frm-pd" colspan="3">
<?php
	$qe = new PgQuery("select * from public.moneda order by moneda_id", NULL, true, false);?>
				<select id="<?=$prefix?>_moneda_id_de" name="moneda_id_de">
<?php
	while ($re = $qe->Read()):?>
					<option value="<?=$re['moneda_id']?>" <?=$re['moneda_id']==$r['moneda_id_de']?'selected':''?>><?=$re['moneda_desc']?></option>
<?php
	endwhile; 
?>
				</select>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="frm-pd" colspan="1">Entrega</td>
			<td class="frm-pd" colspan="3">
<?php
	$qe = new PgQuery("select * from public.moneda order by moneda_id", NULL, true, false);?>
				<select id="<?=$prefix?>_moneda_id_a" name="moneda_id_a">
<?php
	while ($re = $qe->Read()):?>
					<option value="<?=$re['moneda_id']?>" <?=$re['moneda_id']==$r['moneda_id_a']?'selected':''?>><?=$re['moneda_desc']?></option>
<?php
	endwhile; 
?>
				</select>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="frm-pd" colspan="1">Factor</td>
			<td class="frm-pd" colspan="3">
				<input id="<?=$prefix?>_tipocambio_factor" type="text" name="tipocambio_factor" value="<?=$r['tipocambio_factor']?>" style="width: 150px;"/>
			</td>
		</tr>
		<tr align="left" valign="top">
			<td class="frm-pd" colspan="1">Operador</td>
			<td class="frm-pd" colspan="3">
				<select id="<?=$prefix?>_tipocambio_operador" name="tipocambio_operador">
					<option value="*" <?='*'==$r['tipocambio_operador']?'selected':''?>><?='*'?></option>
					<option value="/" <?='/'==$r['tipocambio_operador']?'selected':''?>><?='/'?></option>
				</select>
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
					if (typeof(registro_tipocambio_reload_list) == 'function') {
						registro_tipocambio_reload_list();
					}
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
	$('#<?=$prefix?>_establecimiento_id').focus();
</script>