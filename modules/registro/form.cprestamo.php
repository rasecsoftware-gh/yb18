<?php
    require_once("../../sys.php");
    Sys::DisableClassListen();
    require_once("core.php");
    $module = "registro";
    $prefix = "{$module}_cprestamo";
	// get params
	$pid = Sys::GetR('id', '');
	if (PgQuery::GetValue('public.registro.registro_id', "registro_id = '$pid'")==NULL) {
?>
<style>
#<?=$prefix?>_search_box {
	display: block;
	width: 50%;
	height: 100px;
	margin: 25% 25%!important;
	border: 1px solid silver;
	position: absolute;
	background: white;
}	
</style>
<div id="<?=$prefix?>_search_box">
	<div class="fs-10 c-silver bold c pd-5">Buscar</div>
	<div class="pd-5 c">
		<input id="<?=$prefix?>_filter" class="c" style="padding: 5px!important; width: 80%;" type="text" name="filter" value=""/>
	</div>
</div>
<script>
	$('#<?=$prefix?>_filter').focus().autocomplete({
		minLength: 2,
		source: "modules/<?=$module?>/core.php?action=SearchPrestamo",
		focus: function( event, ui ) {
			$("#<?=$prefix?>_filter").val(ui.item.id);
			return false;
		},
		select: function( event, ui ) {
			$("#<?=$prefix?>_filter" ).val(ui.item.id);
			return false;
		}
	}).data("autocomplete")._renderItem = function( ul, item ) {
		var numdoc = '';
		return $("<li></li>")
			.data("item.autocomplete", item)
			.append("<a>" + item.descripcion + "</a>")
			.appendTo(ul);
	};
	$('#<?=$prefix?>_filter').keypress(function (e) {
	    if (e.keyCode == 13) {
	       var params = $.param({'id': $("#<?=$prefix?>_filter" ).val()});
	       $.post('modules/<?=$module?>/form.cprestamo.php', params, function (data) {
                $("#<?=$prefix?>_window-body").html(data);
           });
	    }
	});
</script>
<?php
		exit;
	} 
    // default values
    $id = Sys::GetP('id');
    $rsi = Sys::GetP('rsi', 0);  // recalcular sin intereses
    $q = new PgQuery("
    SELECT r.*,
    c.cliente_desc, c.cliente_numdoc,
    pv.pventa_desc,
    t.tipope_desc,
    m.moneda_id, m.moneda_desc, m.moneda_simbolo,
    d.registro_det_importe,
    extract(epoch from (now()-r.registro_fecha)) as ttrans, -- tiempo transcurrido
    extract(epoch from ((r.registro_fechavenci::varchar||' 23:59:59')::timestamp-r.registro_fecha)) as tpres, -- tiempo hasta la fecha venc. del prestamo
    e.establecimiento_desc
    FROM public.registro r
    JOIN public.cliente c ON c.cliente_id = r.cliente_id
    JOIN public.pventa pv ON pv.pventa_id = r.pventa_id
    JOIN public.establecimiento e ON e.establecimiento_id = pv.establecimiento_id
    JOIN public.tipope t ON t.tipope_id = r.tipope_id
    JOIN public.registro_det d On d.registro_id = r.registro_id
    JOIN public.moneda m On m.moneda_id = d.moneda_id
    WHERE r.registro_id = '$id'
    LIMIT 1
    ", NULL, true, true);
    $r = $q->row;
?>
<style>
.<?=$prefix?>-text {
    border: 1px solid silver; background: white; display: block; 
    font-size: 9pt;
    padding: 2px 3px;
    color: black;
}
</style>
<form id="<?=$prefix?>_frm" name="<?=$prefix?>_frm" onsubmit="return false;">
<table class="" width="100%">
<tr>
    <td class="cell" colspan="1">
        <button type="button" onclick="<?=$prefix?>_back()">Atras</button>
        <button type="button" onclick="<?=$prefix?>_reload()">Recalcular</button>
        <button type="button" onclick="<?=$prefix?>_reload_sininteres()" class="c-red">Recalcular sin Intereses</button>
<?php
if ($rsi==='1'):?>
        &nbsp;&nbsp;&nbsp;&nbsp;<span class="c-red fs-13 bold">CALCULADO SIN INTERESES</span>
<?php
endif; 
?>        
    </td>
</tr>
<tr>
    <td class="cell" style="width: 570px;" align="left" valign="top">
        <input type="hidden" name="registro_id" value="<?=$id?>"/>
        <table class="" cellpadding="0" cellspacing="0">
        <tr align="left" valign="top">
            <td class="pd">Nro. Operacion: </td>
            <td class="pd">
                <span class="<?=$prefix?>-text fs-11" style="width: 140px;"><?=$id?></span>
<?php
if ($r['registro_estado']=='P'):?>
                <span class="fs-13 bold" style="color: purple;">PENDIENTE DE PAGO</span>
<?php
endif; 
?>                 
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Fecha Prestamo</td>
            <td class="pd">
                <span class="<?=$prefix?>-text" style="width: 140px;"><?=$r['registro_fecha']?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd fs-13 bold">Cliente</td>
            <td class="pd">
                <span class="<?=$prefix?>-text bold" style="width: 300px; font-size: 13pt;"><?=$r['cliente_desc']?></span>
            </td>
        </tr>
        <tr align="left" valign="top">
            <td class="pd">Fecha de Vencimiento</td>
            <td class="pd">
               <span class="<?=$prefix?>-text" style="width: 120px;"><?=$r['registro_fechavenci']?></span>
            </td>
        </tr>
<?php
if (trim($r['registro_desc'])!=''): 
?>
        <tr align="left" valign="top">
            <td class="pd">Descripcion</td>
            <td class="pd">
                <span class="<?=$prefix?>-text" style="width: 300px;"><?=$r['registro_desc']?></span>
            </td>
        </tr>
<?php
endif; 
?>
        <tr align="left" valign="top">
            <td class="pd">Prestamo realizado en</td>
            <td class="pd">
               <span class="<?=$prefix?>-text" style="width: 300px;"><?=$r['establecimiento_desc'].' - '.$r['pventa_desc'].': '.$r['usuario']?> </span>
            </td>
        </tr>
        </table>
    </td>
</tr>
</table>
<table class="grid" width="100%">
<tr>
    <td class="cell-head bold" width="20">#</td>
    <td class="cell-head bold">Tipo Operacion</td>
    <td class="cell-head bold">Fecha Op.</td>
    <td class="cell-head bold">Moneda</td>
    <td class="cell-head bold r">Capital</td>
    <td class="cell-head bold r">Interes (<?=$r['registro_interes']?>%)</td>
    <td class="cell-head bold r">I.Moratorio (<?=$r['registro_imora']?>%)</td>
    <td class="cell-head bold r" width="110">Total</td>
</tr>
<?php
    $i = $rsi==='1'?0:$r['registro_interes'];
    $im = $rsi==='1'?0:$r['registro_imora'];
    $ttrans = $r['ttrans'];
    $tpres = $r['tpres'];
    $saldo = abs($r['registro_det_importe']);
?>
<tr>
    <td class="cell-head"><?=0?></td>
    <td class="cell-head"><?=ucfirst(strtolower($r['tipope_desc']))?></td>
    <td class="cell-head"><?=$r['registro_fecha']?></td>
    <td class="cell-head"><?=$r['moneda_desc']?></td>
    <td class="cell-head r"><?=Sys::NFormat(abs($r['registro_det_importe']))?></td>
    <td class="cell-head r"></td>
    <td class="cell-head r"></td>
    <td class="cell-head bold r"><?=$r['moneda_simbolo']?>&nbsp;<?=Sys::NFormat(abs($r['registro_det_importe']))?></td>
</tr>
<?php
    $qp = new PgQuery("
    SELECT r.*,
    c.cliente_desc, c.cliente_numdoc,
    pv.pventa_desc,
    t.tipope_desc,
    m.moneda_id,
    m.moneda_desc, 
    m.moneda_simbolo,
    (
    SELECT SUM(registro_det_importe) 
    FROM public.registro_det
    WHERE public.registro_det.registro_id = r.registro_id AND clasemov_id = '12'
    ) as registro_det_importe,
    (
    SELECT SUM(registro_det_importe) 
    FROM public.registro_det
    WHERE public.registro_det.registro_id = r.registro_id AND clasemov_id = '13'
    ) as registro_det_interes,
    (
    SELECT SUM(registro_det_importe) 
    FROM public.registro_det
    WHERE public.registro_det.registro_id = r.registro_id AND clasemov_id = '14'
    ) as registro_det_mora,
    (
    SELECT SUM(registro_det_importe) 
    FROM public.registro_det
    WHERE public.registro_det.registro_id = r.registro_id
    ) as registro_det_total,
    extract(epoch from (now()-r.registro_fecha)) as ttrans,
    extract(epoch from ((r.registro_fechavenci::varchar||' 23:59:59')::timestamp-r.registro_fecha)) as tpres
    FROM public.registro r
    JOIN public.cliente c ON c.cliente_id = r.cliente_id
    JOIN public.pventa pv ON pv.pventa_id = r.pventa_id
    JOIN public.tipope t ON t.tipope_id = r.tipope_id
    LEFT JOIN public.moneda m ON m.moneda_id = (SELECT moneda_id FROM public.registro_det d WHERE d.registro_id = r.registro_id LIMIT 1)
    WHERE r.registro_id_parent = '$id'
    ORDER BY r.registro_fecha ASC
    ", NULL, true, true);
    while ($d = $qp->Read()) {
        $did = $d['registro_id'];
        $saldo = $saldo - $d['registro_det_importe'];
?>
<tr align="left" valign="top">
    <td class="cell"><?=$qp->recNo?></td>
    <td class="cell l"><?=$d['tipope_desc']?></td>
    <td class="cell l"><?=$d['registro_fecha']?></td>
    <td class="cell l"><?=$d['moneda_desc']?></td>
    <td class="cell-head r"><?=Sys::NFormat(abs($d['registro_det_importe']))?></td>
    <td class="cell-head r"><?=Sys::NFormat(abs($d['registro_det_interes']))?></td>
    <td class="cell-head r"><?=Sys::NFormat(abs($d['registro_det_mora']))?></td>
    <td class="cell r c-green"><?=$d['moneda_simbolo']?>&nbsp;<?=Sys::NFormat($d['registro_det_total'])?></td>
</tr> 
<?php
    }
    $ixs = (floatval($i)/100) / (60*60*24); // tasa de interes por segundo
    $imxs = (floatval($im)/100) / (60*60*24);
    $fi = ($ttrans * $ixs); // fator de interes
	if ($ttrans>$tpres) {
		$fim = (($ttrans - $tpres) * $imxs); // factor de interes moratorio
	} else {
		$fim = 0;
	}
    $ft = $fi + $fim;
    if ($ttrans <= $tpres) { // tiempo transcurrido < tiempo del prestamo (segun fecha de pago(vencimiento))
        $ti = round($fi * $saldo, 2); // total interes
        $tim = 0; // total interes moratorio
    } else {
        $ti = round($fi * $saldo, 2); // total interes del saldo
        $tim = round($fim * $saldo, 2);
    } 
    $total_deuda = $saldo + $ti + $tim;
?>
<tr>
    <td class="cell r" colspan="4" style="color: red;">Deuda Actual <?=$r['moneda_simbolo']?></td>
    <td class="cell-head r c-green"><?=Sys::NFormat($saldo)?></td>
    <td class="cell-head r c-red"><?=Sys::NFormat(($ti))?></td>
    <td class="cell-head r c-red"><?=Sys::NFormat(($tim))?></td>
    <td class="cell-head r bold" style="color: purple;"><?=$r['moneda_simbolo']?>&nbsp;<?=Sys::NFormat($total_deuda)?></td>
</tr>
<tr>
    <td class="cell r" colspan="4">Monto a Pagar <?=$r['moneda_simbolo']?></td>
    <td class="cell-head r c-green"><span id="<?=$prefix?>_capital" class="c-green bold"><?=Sys::NFormat($saldo)?></span></td>
    <td class="cell-head r c-green"><span id="<?=$prefix?>_interes" class="c-red bold"><?=Sys::NFormat($ti)?></span></td>
    <td class="cell-head r c-green"><span id="<?=$prefix?>_mora" class="c-red bold"><?=Sys::NFormat($tim)?></span></td>
    <td class="cell r">
        <input id="<?=$prefix?>_monto" class="r fs-13" type="text" value="<?=Sys::NFormat($total_deuda)?>" style="width: 100px;"/>
    </td>
</tr>
<tr>
    <td class="cell r" colspan="10">
        <button id="<?=$prefix?>_bt_cancelardeuda" type="button">Registrar Pago</button>
    </td>
</tr>
</table>
</form>
<script>    
    // controls
    $('#<?=$prefix?>_monto').keyup(function (e) {
       sys.iformat($(this).get(0));
       var t = sys.getFloat($(this).val());
       var c = t / (1+parseFloat('<?=$ft?>'));
       var i = c * sys.getFloat('<?=$fi?>');
       var im = c * sys.getFloat('<?=$fim?>');
	   sys.error('<?=$ft?>');
	   
       c = Math.round(c * 100) / 100;
       i = Math.round(i * 100) / 100;
       im = 0;//Math.round(im * 100) / 100;   
       $('#<?=$prefix?>_capital').html(c);
       $('#<?=$prefix?>_interes').html(i);
       $('#<?=$prefix?>_mora').html(im);
    }).keypress(function (e) {
        if (e.keyCode == 13) {
            $('#<?=$prefix?>_bt_cancelardeuda').focus();
        }
    });
    $('#<?=$prefix?>_bt_cancelardeuda').click(function (e) {
        sys.confirm('Realmente desea guardar?', 'Cancelacion de prestamo', function (bt) {
            if (bt=='yes') {
                var apagar = sys.getFloat($('#<?=$prefix?>_monto').val());
                var params = {
                 'action': 'CancelarPrestamo',
                 'registro_id': '<?=$id?>',
                 'moneda_id': '<?=$r['moneda_id']?>',
                 'ti': sys.getFloat($('#<?=$prefix?>_interes').html()),
                 'tim': sys.getFloat($('#<?=$prefix?>_mora').html()),
                 'monto': apagar,
                 'registro_estado': 'T' // prestamo terminado o finalizado o cancelado totalmente o.O
                };
                var post = function (p, reload) {
                    $.post('modules/<?=$module?>/core.php', p, function (data) {
                        if ($.trim(data)=='ok') {
                            sys.message('Se ha guardado satisfactoriamente');
                            <?=$module?>_reload_list();
                            if (reload===true) {
                                <?=$prefix?>_reload();
                            } else {
                                <?=$prefix?>_cancel();
                            }
                        } else {
                            sys.alert(data);
                        }
                    });  
                };
                if (apagar < (<?=$total_deuda?>)) { 
                    sys.confirmYNC('El importe a pagar es menor que la deuda total.</br>El prestamo seguira Pendiente de Pago, continuar asi?', 'Cancelacion de prestamo', function (bt) {
                        if (bt=='yes') {
                            params.registro_estado = 'P'; // dejar pendiente de pago
                            post(params, true);
                        } else if (bt=='no') {
                            sys.confirm('Entonces, desea registrar el pago incompleto y FINALIZAR el prestamo?', 'Cancelacion de prestamo', function (bt) {
                                if (bt=='yes') {
                                    post(params);
                                } else 
                                    return; 
                            });     
                        }
                    });
                } else {
                    post(params);
                }
            } 
        });
    });
    // functions
    // data functions
    function <?=$prefix?>_back() {
        var params = $.param({'id':''});
        $.post('modules/<?=$module?>/form.cprestamo.php', params, function (data) {
            $("#<?=$prefix?>_window-body").html(data);
        });
    };
    function <?=$prefix?>_reload() {
        var params = $.param({'id':'<?=$id?>'});
        $.post('modules/<?=$module?>/form.cprestamo.php', params, function (data) {
            $("#<?=$prefix?>_window-body").html(data);
        });
    };
    function <?=$prefix?>_reload_sininteres() {
        var params = $.param({'id':'<?=$id?>', 'rsi': 1});
        $.post('modules/<?=$module?>/form.cprestamo.php', params, function (data) {
            $("#<?=$prefix?>_window-body").html(data);
        });
    };
    function <?=$prefix?>_cancel() {
        Ext.getCmp('<?=$prefix?>_window').close();
    };
    // init
    $('#<?=$prefix?>_monto').focus().select();
</script>