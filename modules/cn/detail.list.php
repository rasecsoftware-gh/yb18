<?php
	require_once '../../sys.php';
	Sys::DisableClassListen();
	require_once 'core.php';
	$module = 'cn';
	$prefix = "{$module}_detail";
	$id_parent = Sys::GetR('id_parent', '');
	// vars conditions
	$anipre = Sys::GetPeriodo();
	$is_admin = Sys::GetUserIsAdmin();
	$secfun = Sys::GetS("{$module}secfun", '');
	$depen = Sys::GetS("{$module}depen", '');
	$tipo = Sys::GetS("{$module}tipo", '');
	// get aditional info
	$cn_estado = SqlQuery::GetValue('dbo.Cuadro_Mensual.F_CuaMes_Esta', "C_AniPre='$anipre' AND C_CuaMes=$id_parent", 0);
	
	// query
	$params = array(
		$id_parent,
		$anipre
	);
	if ($tipo == 'I') {
		$sql = "{call dbo.Proc_CuaInvMesDetallGet(?, ?)}";
	} elseif ($tipo == 'C') {
		$sql = "{call dbo.Proc_CuaNecMesDetallGet(?, ?)}";
	} else {
		exit('Tipo de centro de costo no valido!');
	}
	$q = sqlsrv_query(SqlProvider::GetConnection(),
	$sql, $params);
	if($q === false)
	{
		if (Config::$debug == 0) exit;
		echo "Error SQL.\n";
		die( print_r( sqlsrv_errors(), true));
	}
	sqlsrv_next_result($q);
	sqlsrv_next_result($q);
?>
<form id="<?=$prefix?>_list_form" onsubmit="return false;">
<table class="grid" width="100%">
<tr>
	<td class="cell-head c-gray" colspan="10">
	Necesidades del Mes
	<?php
	if ($id_estado_doc==1): 
?>
	<span style="float: right; margin-left: 5px;">
	<a href="#" class="btn-icon add" onclick="<?=$prefix_a?>_new();" title="Nuevo" style=""></a>
	<a href="#" class="btn-icon add-dlg" onclick="<?=$prefix_a?>_importar_requisitos();" title="Importar Requisitos del Procedimiento" style=""></a>
	</span>
<?php
	endif; 
?>
	</td>
</tr>
<tr>
	<td class="cell-head c-gray">#</td>
	<td class="cell-head ">Descripcion</td>
	<td class="cell-head ">Unidad</td>
	<td class="cell-head ">Precio</td>
	<td class="cell-head ">Cantidad</td>
	<td class="cell-head ">Total</td>
	<td class="cell-head ">RB-R</td>
	<td class="cell-head ">Asignacion</td>
	<td class="cell-head ">E</td>
	<td class="cell-head ">&nbsp;</td>
</tr>
<?php
	$i = 0;
	while ($d = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC)) {
		$d = Sys::UTF8Decode($d);
		$d = SqlProvider::LowColNames($d);
		$did = $d['c_cuanec'].$d['c_cuainv'];
		$einfo = Core::GetDetailEstadoInfo($d['f_cuanec_esta'].$d['f_cuainv_esta']);
		$carac = rawurlencode($d['n_carcua_desc']);
?>
<tr class="<?=$prefix?>-tr" align="left" rid="<?=$did?>" carac="<?=$carac?>">
	<td class="cell c-gray"><?=++$i?></td>
	<td class="cell l c-gray fs-7"><?=$d['n_bieser_desc']?></td>
	<td class="cell c c-gray fs-7"><?=$d['n_unibis_desc']?></td>
	<?php
	$precio = $d[$tipo=='I'?'q_cuainv_prec':'q_cuanec_prec']; 
?>
	<?php
	$cantidad = $d[$tipo=='I'?'q_cuainv_cant':'q_cuanec_cant']; 
?>
	<td class="cell r c-gray fs-7">
	<?php
	if ($d['f_cuanec_esta']=='01'): // generado
			if ($d['c_bieser_tipo']=='01'): // BIEN: precio es precio?>
				<?=Sys::NFormat($precio)?>
	<?php
		else: // SERVICIO: precio es cantidad o.O 
?>
		<a href="javascript: void(0)" class="c-theme" onclick="<?=$prefix?>_set_cantidad('<?=$did?>', '<?=$d['c_mespre']?>', <?=$d['q_cuanec_cant']?>)" title="cambiar precio"><?=Sys::NFormat($cantidad)?></a>
	<?php
		endif;?>
	<?php
	else: // no generado 
?>
	<?php
		if ($d['c_bieser_tipo']=='01'): // bienes 
?>
			<?=Sys::NFormat($precio)?>
		<?php
	else:?>
			<?=Sys::NFormat($cantidad)?>
		<?php
	endif;?>
	<?php
	endif;?>
	</td>
	<td class="cell r c-gray fs-7">
	<?php
	if ($d['f_cuanec_esta']=='01'): // generado
			if ($d['c_bieser_tipo']=='01'): // BIEN: cant es cantidad?>
		<a href="javascript: void(0)" class="c-theme" onclick="<?=$prefix?>_set_cantidad('<?=$did?>', '<?=$d['c_mespre']?>', <?=$d['q_cuanec_cant']?>)" title="cambiar cantidad"><?=Sys::NFormat($cantidad)?></a>
	<?php
		else: // SERVICIO: precio es cantidad o.O 
?>
		<?=Sys::NFormat($precio)?>
	<?php
		endif;?>
	<?php
	else: // no generado 
?>
	<?php
		if ($d['c_bieser_tipo']=='01'): // bienes 
?>
			<?=Sys::NFormat($cantidad)?>
		<?php
	else: // servicios 
?>
			<?=Sys::NFormat($precio)?>
		<?php
	endif;?>
	<?php
	endif;?>
	<!-- <a href="javascript: void(0)" class="c-theme" onclick="<?=$prefix?>_set_precio('<?=$did?>', '<?=$d['q_cuanec_prec']?>')" title="cambiar precio"><?=Sys::NFormat($d['q_cuanec_prec'])?></a> -->
	</td>
	<td class="cell c c-gray fs-7"><?=Sys::NFormat($d['total'])?></td>
	<td class="cell c c-gray fs-7"><?=$d['c_fuefin']."-".$d['c_recurs']?></td>
	<td class="cell c c-gray fs-7"><?=$d['c_clapre']?></td>
	<td class="cell c c-gray fs-7">
		<span class="pd-1 fs-7" style="display: block; color: white; background: <?=$einfo[1]?>;" title="<?=$einfo[0]?>"><?=$d['f_cuanec_esta'].$d['f_cuainv_esta']?></span>
	</td>
	<td class="cell">
	<?php
	if (trim($carac)!=''):?>
		<a href="javascript: void(0)" class="c-theme" onclick="<?=$prefix?>_set_caracteristicas('<?=$did?>', '<?=$carac?>')" title="cambiar caracteristicas">...</a>
	<?php
	endif;?>
	</td>
</tr>
<?php 
	}
	if ($i==0 && $cn_estado=='00'): // generado
?>
<tr>
	<td class="cell c-gray c" colspan="10">
		<a class="c-theme" href="javascript: void(0)" onclick="<?=$prefix?>_new();">+ agregar bien o servicio</a>
	</td>
</tr>
<?php
	endif;?>
</table>
</form>
<script>
var <?=$prefix?>_cm = Ext.create('Ext.menu.Menu', {
    floating: true,  // 
    renderTo: Ext.getBody(),  // usually rendered by it's containing component
    items: [{
        text: 'Agregar Bien/Servicio',
        listeners: {
        	click: function () {
    			<?=$prefix?>_new();
    		} 
    	}
    },{
    	text: 'Quitar Bien/Servicio',
        listeners: {
        	click: function () {
    			<?=$prefix?>_delete($(<?=$prefix?>_selected_row).attr('rid'));
    		} 
    	}
    },{
        text: 'Especificar Caracteristicas',
        listeners: {
        	click: function () {
    			var r = $(<?=$prefix?>_selected_row);
    			<?=$prefix?>_set_caracteristicas(r.attr('rid'), r.attr('carac'));
    		} 
    	}
    }]
});

var <?=$prefix?>_selected_row = '';
$('.<?=$prefix?>-tr')
.hover(
	function (e) { $(this).addClass('grid-row-over').addClass('grid-row-over-bg').prev().addClass('grid-row-over-before'); }, 
	function (e) { $(this).removeClass('grid-row-over').removeClass('grid-row-over-bg').prev().removeClass('grid-row-over-before'); }
).click(function () {
	$(<?=$prefix?>_selected_row).removeClass('grid-row-select');
	$(this).addClass('grid-row-select');
	<?=$prefix?>_selected_row = this;	
	//alert($(this).attr('rid'));
}).bind('contextmenu', function (e) {
	$(<?=$prefix?>_selected_row).removeClass('grid-row-select');
	$(this).addClass('grid-row-select');
	<?=$prefix?>_selected_row = this;	
	<?=$prefix?>_cm.showAt(e.pageX, e.pageY);
	return false;
});

function <?=$prefix?>_new() {
	//alert('<?=$tipo?>');
	if ('<?=$tipo?>'=='C' && $(cn_poi_selected_row).length == 0) {
		alert('Seleccione una Actividad'); return;
	}
	if ($(cn_saldo_selected_row).length == 0) {
		alert('Seleccione un Clasificador de gasto del Saldo del Calendario'); return;
	}
	eval("var srow = "+unescape($(cn_saldo_selected_row).attr('rdata')));
	eval("var prow = "+unescape($(cn_poi_selected_row).attr('rdata')));
	var w = new BieSerWindow({
		'p_cuames': '<?=$id_parent?>', 
		'p_srow': $(cn_saldo_selected_row).attr('rdata'), 
		'p_prow': $(cn_poi_selected_row).attr('rdata')
	});
	w.show();
};
function <?=$prefix?>_set_caracteristicas(id, value) {
	Ext.MessageBox.prompt('Caracteristicas del Bien o Servicio', 'Especifique las caracteristicas', 
	function (btn, nvalue) {
		if (btn == 'ok') {
			var params= $.param({action: 'DetailUpdateCarac', 'id': id, 'value': nvalue});
			$.post('modules/<?=$module?>/core.php', params, function (data) {
				if ($.trim(data) == 'ok') {
					sys.message('Se ha modificado satisfactoriamente');
					<?=$prefix?>_reload_list();
				} else {
					alert(data);
				}
			});	
		} 
	}, null, true, unescape(value)); 
};
function <?=$prefix?>_set_precio(id, value) {
	Ext.MessageBox.prompt('Bien o Servicio', 'Especifique el Precio', 
	function (btn, nvalue) {
		if (btn == 'ok') {
			var params= $.param({action: 'DetailUpdatePrecio', 'id': id, 'value': nvalue});
			$.post('modules/<?=$module?>/core.php', params, function (data) {
				if ($.trim(data) == 'ok') {
					sys.message('Se ha modificado satisfactoriamente');
					<?=$prefix?>_reload_list();
				} else {
					alert(data);
				}
			});	
		} 
	}, null, false, value); 
};
function <?=$prefix?>_set_cantidad(id, mes, value) {
	Ext.MessageBox.prompt('Bien o Servicio', 'Especifique la Cantidad', 
	function (btn, nvalue) {
		if (btn == 'ok') {
			var params= $.param({action: 'DetailUpdateCantidad', 'id': id, 'mes': mes, 'value': nvalue});
			$.post('modules/<?=$module?>/core.php', params, function (data) {
				if ($.trim(data) == 'ok') {
					sys.message('Se ha modificado satisfactoriamente');
					<?=$prefix?>_reload_list();
				} else {
					alert(data);
				}
			});	
		} 
	}, null, false, value); 
};
function <?=$prefix?>_delete(id) {
	if (!confirm('Realmente desea eliminar?')) return;
	var params= $.param({action: 'DeleteDetail', id: id});
	$.post('modules/<?=$module?>/core.php', 'action=DetailDelete&id='+id, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha eliminado satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_importar_requisitos() {
	var params = $.param({action: 'ImportarRequisitos', id_documento: '<?=$id_parent?>'});
	$.post('modules/<?=$module?>/core.php', params, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha importado los requisitos satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_reload_list() {
	var params = $.param({'id_parent': '<?=$id_parent?>'});
	$('#<?=$prefix?>_container').load('modules/<?=$module?>/detail.list.php', params);
};
function <?=$prefix?>_delete_multi_details() {
	var params = $('#<?=$prefix?>_list_form').serialize();
	if (!confirm('Realmente desea eliminar los items seleccionados?')) return false;
	$.post('modules/<?=$module?>/core.php', 'action=DeleteMultiDetails&'+params, function (data) {
		if (data.trim() == 'ok') {
			sys.message('Se han eliminado satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			alert(data);
		}
	});
};
function <?=$prefix?>_edit_cantidad(id, value) {
	var nvalue = prompt('Especifique la Cantidad', value);
	if (nvalue != null && parseFloat(nvalue, 10) != value) {
		// process
		var params = $.param({action: 'DetailUpdateCantidad', 'id': id, 'value': nvalue});
		$.post('modules/<?=$module?>/core.php', params, function (data) {
			if (data.trim() == 'ok') {
				sys.message('Se ha modificado satisfactoriamente');
				<?=$prefix?>_reload_list();
			} else {
				alert(data);
			}
		});
	}
}
function <?=$prefix?>_select_all(checked) {
	if (checked) {
		$('.<?=$prefix?>_check').attr('checked','checked');
	} else {
		$('.<?=$prefix?>_check').removeAttr('checked');
	}
}
// Forms
BieSerWindow = Ext.extend(Ext.Window, {
	id:'<?=$prefix?>_bs_window', title: 'Bienes y Servicios', width: 750, height: 500, modal: true, autoScroll: true,
	initComponent: function() {
		this.on('show', function (s) {
			var params = $.param({
				'cuames': this.p_cuames, 
				'srow': this.p_srow,
				'prow': this.p_prow
			});
			$.post('modules/<?=$module?>/bs.list.php', params, 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		BieSerWindow.superclass.initComponent.call(this);
	}
});
</script>