<?php
    require_once("../../sys.php");
    Sys::DisableClassListen();
    require_once("core.php");
    $module = "registro";
    $prefix = "{$module}_prestamo";
    // default values
    $id = Registro::GetNextId();
    $r['registro_fecha'] = date('d/m/Y H:i:s');
    $r['registro_fechavenci'] = date('d/m/Y');
    
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
    <td class="cell" colspan="2">
        <button type="button" onclick="<?=$prefix?>_update()">Guardar</button>
        <button type="button" onclick="<?=$prefix?>_cancel()">Cancelar</button>
    </td>
</tr>
<tr>
    <td class="cell" style="width: 570px;" align="left" valign="top">
        <form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
        <input type="hidden" name="registro_id" value="<?=$id?>"/>
        <table class="" cellpadding="0" cellspacing="0">
        <tr align="left" valign="top">
            <td class="pd">Nro. Operacion: </td>
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
            <td class="pd">Cliente</td>
            <td class="pd">
                <input id="<?=$prefix?>_cliente_id" type="hidden" name="cliente_id" value=""/>
                <input id="<?=$prefix?>_cliente_desc" class="" type="text" name="cliente_desc" value="" style="width: 300px;"/>
                &nbsp;&nbsp;&nbsp;<a id="<?=$prefix?>_cliente_add" href="javascript:void(0)" style="display: none;">agregar</span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Moneda</td>
            <td class="pd">
<?php
$qe = new PgQuery("select * from public.moneda order by moneda_id", NULL, true, false);?>
                <select id="<?=$prefix?>_moneda_id" name="moneda_id">
<?php
while ($re = $qe->Read()):?>
                    <option value="<?=$re['moneda_id']?>"><?=$re['moneda_desc']?></option>
<?php
endwhile; 
?>
                </select>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Importe</td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_det_importe" class="r fs-14" type="text" name="registro_det_importe" value="0.00" style="width: 150px;"/>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Interes Diario</td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_interes" class="r fs-14" type="text" name="registro_interes" value="5" style="width: 150px;"/> %
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Interes Moratorio</td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_imora" class="r fs-14" type="text" name="registro_imora" value="0" style="width: 150px;"/> %
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Fecha de Pago</td>
            <td class="pd">
                <span id="<?=$prefix?>_registro_fechavenci_container" ></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Descripcion</td>
            <td class="pd">
                <input id="<?=$prefix?>_registro_desc" type="text" name="registro_desc" value="" style="width: 300px;"/>
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
	$("#<?=$prefix?>_cliente_desc").autocomplete({
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
			$("#<?=$prefix?>_moneda_id" ).focus();
			return false;
		}
	})
	.data("autocomplete")._renderItem = function( ul, item ) {
		var numdoc = '';
		if ($.trim(item.cliente_numdoc) != '') {
			numdoc = "&nbsp;&nbsp;<span class=\"fs-7 c-gray italic\">("+item.cliente_numdoc+")</span>";
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
				$("#<?=$prefix?>_moneda_id" ).focus();	
			}
		} else {
			if ($("#<?=$prefix?>_cliente_desc").val().length >= 2 ) { //&& $("#<?=$prefix?>_cliente_id").val().trim() == ''
				$("#<?=$prefix?>_cliente_id").val('');
				$("#<?=$prefix?>_cliente_add").show();
			} else {
				$("#<?=$prefix?>_cliente_add").hide();
			}
		}
	});
	
	$("#<?=$prefix?>_cliente_add").click(function (e) {
		var numdoc = prompt('Ingrese el DNI o RUC segun sea el caso (si desconoce dejelo en BLANO)');
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
                $("#<?=$prefix?>_moneda_id" ).focus();
            } else {
                sys.message(data);
            }
        });
	});
	
	new Ext.form.DateField({
		id: '<?=$prefix?>_registro_fechavenci', renderTo: '<?=$prefix?>_registro_fechavenci_container', enableKeyEvents: true,
	    name: 'registro_fechavenci', value: '<?=$r['registro_fechavenci']?>',
	    width: 100
	});
    // functions
    $('#<?=$prefix?>_registro_det_importe').keyup(function (e) {
        sys.iformat($(this).get(0));
    }).change(function () { 
        sys.iformat($(this).get(0)); 
    }).change();
    
    $('#<?=$prefix?>_registro_interes').keyup(function (e) {
        sys.iformat($(this).get(0));
    }).change(function () { 
        sys.iformat($(this).get(0)); 
    }).change();
    
    $('#<?=$prefix?>_registro_imora').keyup(function (e) {
        sys.iformat($(this).get(0));
    }).change(function () { 
        sys.iformat($(this).get(0)); 
    }).change();
    
    // data functions
    function <?=$prefix?>_renew() {
        $.post('modules/<?=$module?>/form.php', 'task=new', function (data) {
            $('#'+Ext.getCmp('<?=$prefix?>_window').body.dom.id).html(data);
        });
    };
    function <?=$prefix?>_update() {
        if (confirm('Realmente desea guardar?')) {
            var params = $('#<?=$prefix?>_frm').serialize();
            $.post('modules/<?=$module?>/core.php', 'action=UpdatePrestamo&'+params, function (data) {
                if ($.trim(data)=='ok') {
                    sys.message('Se ha guardado satisfactoriamente');
                    <?=$module?>_reload_list();
                    <?=$prefix?>_cancel();
                } else {
                    sys.alert(data);
                }
            });
        }
    };
    function <?=$prefix?>_cancel() {
        Ext.getCmp('<?=$prefix?>_window').close();
    };
    // init
    $('#<?=$prefix?>_cliente_desc').focus();
</script>