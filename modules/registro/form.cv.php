<?php
	require_once("../../sys.php");
    Sys::DisableClassListen();
    require_once("core.php");
	$module = "registro";
	$prefix = "{$module}_cv";
	// default values
	$id = Registro::GetNextId();
	$r['registro_fecha'] = date('d/m/Y H:i:s');
	$r['cliente_id'] = "00-0000000";
    $r['cliente_desc'] = PgQuery::GetValue('public.cliente.cliente_desc', "cliente_id = '{$r['cliente_id']}'")
?>
<style>
#<?=$prefix?>_cliente_add:focus {
	padding: 2px 5px;
	border: 1px solid silver;
	background: white;
	text-decoration: blink;
}
</style>
<table class="" width="100%">
<tr>
	<td class="pd" >
		<button id="<?=$prefix?>_bt_guardar" type="button" class="fs-12 bold" onclick="<?=$prefix?>_update()">Guardar</button>
		<button type="button" class="fs-12 bold" onclick="<?=$prefix?>_cancel()">Cancelar</button>
	</td>
	<td class="pd">
		<input id="<?=$prefix?>_print_flag" type="checkbox" checked/>
		<label for="<?=$prefix?>_print_flag" class="pd">Imprimir inmediatamente</label>
	</td>
</tr>
<tr>
	<td class="pd" style="" align="left" valign="top" colspan="2">
		<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
		<input type="hidden" name="registro_id" value="<?=$id?>"/>
		<input id="<?=$prefix?>_moneda_id_de" type="hidden" name="moneda_id_de" value="0"/>
		<table class="" cellpadding="0" cellspacing="0" style="width: 100%;">
		<tr align="left" valign="top">
            <td class="pd" width="120">Nro. Operacion: </td>
            <td class="pd">
                <span class="pd bold c-gray fs-11" style="border: 1px solid silver; background: white; display: block; width: 140px;"><?=$id?></span>
            </td>
        </tr>
		<tr align="left" valign="top">
			<td class="pd">Fecha</td>
			<td class="pd">
				<span class="pd c-black fs-9" style="border: 1px solid silver; background: white; display: block; width: 140px;"><?=$r['registro_fecha']?></span>
			</td>
		</tr>
		<tr align="left" valign="top">
            <td class="pd">Operacion</td>
            <td class="pd">
<?php
	$qt = new PgQuery("
		SELECT t.*
		FROM public.tipope t
		WHERE t.tipope_flag_tc = '1'  
		ORDER BY t.tipope_id
		", NULL, true, false);
		$rt_selected = array();
		if ($qt->recordCount>0) {
			$rt_selected = $qt->row;
		}
?>
                <select class="fs-14 bold <?=$rt_selected['tipope_id']=='03'?'c-green':'c-blue'?>" id="<?=$prefix?>_tipope_id" name="tipope_id" style="width: 350px; height: 30px;"> 
<?php
	while ($rt = $qt->Read()):?>
                    <option class="<?=$rt['tipope_id']=='03'?'c-green':'c-blue'?>" value="<?=$rt['tipope_id']?>" rdata="<?=rawurlencode($qt->GetRowAsJson())?>"><?=substr($rt['tipope_desc'],0,6)?></option>
<?php
	endwhile; 
?>
                </select>
            </td>
        </tr>
		<tr align="left" valign="top">
            <td class="pd">Tipo de Cambio</td>
            <td class="pd">
            <div id="<?=$prefix?>_tc_container">
	            <script>
	            	function <?=$prefix?>_tc_load_list(tipope_id) {
	            		$('#<?=$prefix?>_tc_container').html('cargando...');
	            		$.post('modules/<?=$module?>/form.cv.tc.list.php', 'tipope_id='+tipope_id+'&focus=1', function (data) {
	            			$('#<?=$prefix?>_tc_container').html(data);
	            		});
	            	};
	            	$('#<?=$prefix?>_tc_container').load('modules/<?=$module?>/form.cv.tc.list.php', 'tipope_id=<?=$rt_selected['tipope_id']?>');
	            </script>
            </div>
            </td>
        </tr>
        <tr align="left" valign="middle">
            <td class="pd">Cantidad <span id="<?=$prefix?>_simbolo_de" class="bold fs-10"><?=$tc_selected['moneda_simbolo_de']?></span></td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_det_importe_de" class="fs-20 r" type="text" name="registro_det_importe_de" value="0" style="width: 250px;" size="15"/>
                &nbsp;&nbsp;(<span id="<?=$prefix?>_recibir" class="c-gray bold"><?=$rt_selected['tipope_id']=='03'?'&lArr; RECIBIR':'ENTREGAR &rArr;'?></span>)
            </td>
        </tr>
        <tr align="left" valign="middle">
            <td class="pd">Cotizacion</td>
            <td class="pd">
                <input id="<?=$prefix?>_factor" class="fs-20 r" type="text" name="tipocambio_factor" value="<?=$tc_selected['tipocambio_factor']?>" style="width: 250px;" size="15"/>
            </td>
        </tr>
        <tr align="left" valign="middle">
            <td class="pd">Importe <span id="<?=$prefix?>_simbolo_a" class="bold fs-10"><?=$tc_selected['moneda_simbolo_a']?></span></td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_det_importe_a" class="fs-20 r" type="text" name="registro_det_importe_a" value="0" style="width: 250px;" size="15"/>
                &nbsp;&nbsp;(<span id="<?=$prefix?>_entregar" class="c-gray bold"><?=$rt_selected['tipope_id']=='03'?'ENTREGAR &rArr;':'&lArr; RECIBIR'?></span>)
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd" colspan="2"><hr/></td>
        </tr>
        <tr align="left" valign="middle">
            <td class="pd">Efectivo</td>
            <td class="pd">
                <input id="<?=$prefix?>_efectivo" class="fs-20 r c-green" type="text" name="efectivo" value="0" style="width: 250px;" size="15"/>
            </td>
        </tr>
        <tr align="left" valign="middle">
            <td class="pd">Vuelto</td>
            <td class="pd">
                <input id="<?=$prefix?>_vuelto" class="fs-20 r c-red" type="text" name="vuelto" value="0" style="width: 250px;" size="15"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd" colspan="2"><hr/></td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Cliente</td>
            <td class="pd">
                <input id="<?=$prefix?>_cliente_id" type="hidden" name="cliente_id" value="<?=$r['cliente_id']?>"/>
                <input id="<?=$prefix?>_cliente_desc" class="" type="text" name="cliente_desc" value="<?=$r['cliente_desc']?>" style="width: 400px;"/>
                &nbsp;&nbsp;&nbsp;<a id="<?=$prefix?>_cliente_add" href="javascript:void(0)" style="display: none;">agregar</span>
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
	var count = 0;
	var tipope_id = '<?=$rt_selected['tipope_id']?>';
	//var tcdata = <?=json_encode($tc_selected)?>;
	// functions
	
	function <?=$prefix?>_set_tc_values(d) {
		if (tipope_id=='03') {
			$('#<?=$prefix?>_factor').val(d.tipocambio_factor);
			$('#<?=$prefix?>_simbolo_de').html(d.moneda_simbolo_de);
			$('#<?=$prefix?>_simbolo_a').html(d.moneda_simbolo_a);
		} else {
			$('#<?=$prefix?>_factor').val(d.tipocambio_factor);
			$('#<?=$prefix?>_simbolo_de').html(d.moneda_simbolo_a);
			$('#<?=$prefix?>_simbolo_a').html(d.moneda_simbolo_de);
		}
	};
	
	$('#<?=$prefix?>_tipope_id').change(function () {
		tipope_id = $(this).val();
		<?=$prefix?>_tc_load_list($(this).val());
		if (tipope_id=='03') {
			$(this).removeClass('c-blue').addClass('c-green');
			$('#<?=$prefix?>_recibir').html('&lArr; RECIBIR');
			$('#<?=$prefix?>_entregar').html('ENTREGAR &rArr;');
		} else {
			$(this).removeClass('c-green').addClass('c-blue');
			$('#<?=$prefix?>_recibir').html('ENTREGAR &rArr;');
			$('#<?=$prefix?>_entregar').html('&lArr; RECIBIR');
		}
	}).keypress(function (e) {
		if (e.keyCode == 13) {
			<?=$prefix?>_tc_load_list($(this).val());
			$('#<?=$prefix?>_tipocambio_id').focus();
		} else if (e.keyCode == 9) {
			//e.preventDefault();
			//<?=$prefix?>_tc_load_list($(this).val());
		}
	});
	
	function <?=$prefix?>_calc_a() {
		var val_de = $('#<?=$prefix?>_registro_det_importe_de').val().replace(/,/g,'');
		if (tipope_id == '03') {
		    var vde = parseFloat(val_de);
		    var f = parseFloat($('#<?=$prefix?>_factor').val().replace(/,/g,''));
		    var ope = tcdata.tipocambio_operador;
		    var evalstr = "var v = vde "+ope+" f";
			eval(evalstr);
			v = Math.round(v * 100) / 100; 
			$('#<?=$prefix?>_registro_det_importe_a').val(sys.nformat(v.toString()));
		} else { // venta
			var vde = parseFloat(val_de);
		    var f = parseFloat($('#<?=$prefix?>_factor').val().replace(/,/g,''));
		    var ope = tcdata.tipocambio_operador;
		    var evalstr = "var v = vde "+ope+" (1/f)";
			eval(evalstr);
			v = Math.round(v * 100) / 100; 
			$('#<?=$prefix?>_registro_det_importe_a').val(sys.nformat(v.toString()));
		}
	};
	
	function <?=$prefix?>_calc_de() {
		var val_a = $('#<?=$prefix?>_registro_det_importe_a').val().replace(/,/g,'');
		if (tipope_id == '03') {
		    var val = parseFloat(val_a);
		    var f = parseFloat($('#<?=$prefix?>_factor').val().replace(/,/g,''));
		    var ope = tcdata.tipocambio_operador;
		    var evalstr = "var v = val "+ope+" (1/f)";
			eval(evalstr);
			v = Math.round(v * 100) / 100; 
			$('#<?=$prefix?>_registro_det_importe_de').val(sys.nformat(v.toString()));
		} else { // venta
			var val = parseFloat(val_a);
		    var f = parseFloat($('#<?=$prefix?>_factor').val().replace(/,/g,''));
		    var ope = tcdata.tipocambio_operador;
		    var evalstr = "var v = val "+ope+" f";
			eval(evalstr);
			v = Math.round(v * 100) / 100; 
			$('#<?=$prefix?>_registro_det_importe_de').val(sys.nformat(v.toString()));
		}
	};
	var denext = false;
	$('#<?=$prefix?>_registro_det_importe_de').keydown(function(e) {
		denext = false;
		if (e.which == 13 ) {
			denext = true;
		}
	}).keyup(function (e) {
		<?=$prefix?>_calc_a();
		sys.iformat($(this).get(0));
		if (e.which == 13 ) {
			if (denext == true) {
				$('#<?=$prefix?>_factor').focus().select();
				denext = false;
			}
		}
	}).change(function () { 
		<?=$prefix?>_calc_a();
		if (tipope_id=='03') $('#<?=$prefix?>_efectivo').val($(this).val());
		sys.iformat($(this).get(0)); 
	}).keypress(function (e) {
		if (e.keyCode == 13) {
			if (tipope_id=='03') $('#<?=$prefix?>_efectivo').val($(this).val());
		} else if (e.keyCode == 9) {
			if (tipope_id=='03') $('#<?=$prefix?>_efectivo').val($(this).val());
		} else if (e.keyCode == 38) { // up
			$('#<?=$prefix?>_tipocambio_id').focus();
		} 
	});
	var fnext = false;
	$('#<?=$prefix?>_factor').keydown(function (e) {
		fnext = false;
		if (e.which == 13 ) {
			fnext = true;
		}
		<?=$prefix?>_calc_a();
	}).keyup(function (e) {
		if (e.which == 13 ) {
			if (fnext == true) {
				$('#<?=$prefix?>_registro_det_importe_a').focus().select();
				fnext = false;
			}
		}
		<?=$prefix?>_calc_a();
	}).keypress(function (e) {
		if (e.keyCode == 13) {
			
		} else if (e.keyCode == 9) {
			//e.preventDefault();
			//$('#<?=$prefix?>_bt_guardar').focus();
		} else if (e.keyCode == 38) { // up
			$('#<?=$prefix?>_registro_det_importe_de').focus().select();
		} 
	});
	
	$('#<?=$prefix?>_registro_det_importe_a').keyup(function (e) {
		if (
			(e.keyCode>=48 && e.keyCode<=57) || // num keys
			(e.keyCode>=96 && e.keyCode<=105) || // numpad
			(e.keyCode==190) || // point
			(e.keyCode==110) || // numpad point
			(e.keyCode==8) || // back erase
			(e.keyCode==46) // delete
		) {
			<?=$prefix?>_calc_de();
			sys.message(e.keyCode);
		}
		sys.iformat($(this).get(0));
	}).change(function () { 
		<?=$prefix?>_calc_de();
		if (tipope_id=='04') $('#<?=$prefix?>_efectivo').val($(this).val());
		sys.iformat($(this).get(0)); 
	}).keypress(function (e) {
		if (e.keyCode == 13) {
			if (tipope_id=='04') $('#<?=$prefix?>_efectivo').val($(this).val());
			$('#<?=$prefix?>_efectivo').focus().select();
		} else if (e.keyCode == 9) {
			if (tipope_id=='04') $('#<?=$prefix?>_efectivo').val($(this).val());
		} else if (e.keyCode == 38) { // up
			$('#<?=$prefix?>_factor').focus().select();
		} 
	});
	
	$('#<?=$prefix?>_efectivo').keyup(function (e) {
		if (tipope_id=='03')
			var v = $(this).val().replace(/,/g,'') - $('#<?=$prefix?>_registro_det_importe_de').val().replace(/,/g,'');
		else
			var v = $(this).val().replace(/,/g,'') - $('#<?=$prefix?>_registro_det_importe_a').val().replace(/,/g,'');
		v = Math.round(v * 100) / 100; 	
		$('#<?=$prefix?>_vuelto').val(sys.nformat(v.toString()));
		sys.iformat($(this).get(0));
	}).keypress(function (e) {
		if (e.keyCode == 13) {
			$('#<?=$prefix?>_cliente_desc').focus().select();
		} else if (e.keyCode == 9) {
		} else if (e.keyCode == 38) { // up
			$('#<?=$prefix?>_registro_det_a').focus().select();
		} 
	});
	
	$('#<?=$prefix?>_cliente_desc').autocomplete({
		minLength: 2,
		source: "modules/<?=$module?>/core.php?action=SearchCliente",
		focus: function( event, ui ) {
			$("#<?=$prefix?>_cliente_desc").val(ui.item.descripcion);
			return false;
		},
		select: function( event, ui ) {
			$("#<?=$prefix?>_cliente_desc").val(ui.item.descripcion);
			$("#<?=$prefix?>_cliente_id" ).val(ui.item.id);
			$("#<?=$prefix?>_cliente_add").hide();
			$("#<?=$prefix?>_bt_guardar" ).focus();
			return false;
		}
	})
	.data("autocomplete")._renderItem = function( ul, item ) {
		var numdoc = '';
		if ($.trim(item.cliente_numdoc) != '') {
			numdoc = "&nbsp;&nbsp;<span class=\"fs-10 c-gray italic\">("+item.cliente_numdoc+")</span>";
		}
		return $("<li></li>")
			.data("item.autocomplete", item)
			.append("<a>" + item.descripcion + numdoc + "</a>")
			.appendTo(ul);
	};
	
	$("#<?=$prefix?>_cliente_desc").keypress(function (e) {
		if (e.keyCode == 13) {
			if ($("#<?=$prefix?>_cliente_id").val().trim() == '') {
				$("#<?=$prefix?>_cliente_add").focus();
			} else {
				$('#<?=$prefix?>_bt_guardar').focus();
			}
		} else if (e.keyCode == 9) {
			e.preventDefault();
			$('#<?=$prefix?>_bt_guardar').focus();
		} else {
			if ($("#<?=$prefix?>_cliente_desc").val().length > 2 ) { //&& $("#<?=$prefix?>_cliente_id").val().trim() == ''
				$("#<?=$prefix?>_cliente_id").val('');
				$("#<?=$prefix?>_cliente_add").show();
			} else {
				$("#<?=$prefix?>_cliente_add").hide();
			}
		}
	});
	
	$("#<?=$prefix?>_cliente_add").click(function (e) {
		var numdoc = prompt('Ingrese el DNI o RUC segun sea el caso');
		if (numdoc == null) return;
		var params = $.param({
			'cliente_desc': $("#<?=$prefix?>_cliente_desc").val(),
			'cliente_numdoc': numdoc
		});
		$.post('modules/<?=$module?>/core.php', 'action=AddCliente&'+params, function (data) {
            if (isNaN($.trim(data).replace('-',''))==false) { // es client id valido
                sys.message('Se ha agregado el cliente satisfactoriamente');
                $("#<?=$prefix?>_cliente_id").val($.trim(data));
                $("#<?=$prefix?>_cliente_add").hide();
                $("#<?=$prefix?>_bt_guardar" ).focus();
            } else {
                sys.message(data);
            }
        });
	});
	// data functions
	function <?=$prefix?>_renew() {
		$.post('modules/<?=$module?>/form.cv.php', 'task=new', function (data) {
			$('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
		});
	};
	var <?=$prefix?>_updating = 0;
	function <?=$prefix?>_update() {
	    if (<?=$prefix?>_updating == 1) return;
	    <?=$prefix?>_updating = 1; // lock 
	    $('#<?=$prefix?>_bt_guardar').attr('disabled','');
		if (confirm('Realmente desea guardar?')) {
			var params = $('#<?=$prefix?>_frm').serialize();
			var action = 'action=UpdateCompra&';
			if (tipope_id == '04') {
				action = 'action=UpdateVenta&';
			}
			$.post('modules/<?=$module?>/core.php', action+params, function (data) {
				if ($.trim(data)=='ok') {
					sys.message('Se ha guardado satisfactoriamente');
					<?=$module?>_reload_list();
					<?=$module?>_print('<?=$id?>');
					if (confirm('Desea registrar nueva operacion?')) {
					   <?=$prefix?>_updating = 0; // unlock
					   <?=$prefix?>_renew();     
					} else {
					   <?=$prefix?>_cancel(); 
					}
				} else {
				    <?=$prefix?>_updating = 0; // unlock
				    $('#<?=$prefix?>_bt_guardar').removeAttr('disabled');
					sys.alert(data);
				}
			});
		} else {
		    <?=$prefix?>_updating = 0; // unlock
		    $('#<?=$prefix?>_bt_guardar').removeAttr('disabled');
		}
	};
	function <?=$prefix?>_cancel() {
		Ext.getCmp('<?=$prefix?>_window').close();
	};
	// init
	$('#<?=$prefix?>_tipope_id').focus();
</script>