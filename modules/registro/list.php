<?php
	require_once '../../sys.php';
	$module = 'registro';
	$prefix = "{$module}";
	// params
	$filter = Sys::GetP('filter', '', true, $prefix); 
	$listtype = Sys::GetP('listtype', 'now', true, $prefix);
	$is_admin = Sys::GetUserIsAdmin();
	// order 
	$sort_by = Sys::GetP('sort_by', 'registro_id', true, $prefix);
	$sort_type = Sys::GetP('sort_type', 'DESC', true, $prefix);
	$orders = array(
		"registro_id"=>array("order_by"=>"registro_id $sort_type", "sort_type"=>"", "next_sort_type"=>"asc"),
		"end"=>array("order_by"=>"end $sort_type", "sort_type"=>"", "next_sort_type"=>"asc")
	);
	$order_by = $orders[$sort_by]['order_by'];
	$orders[$sort_by]['sort_type'] = strtolower($sort_type);
	$orders[$sort_by]['next_sort_type'] = (strtolower($sort_type)=='asc'?'desc':'asc');
	 
	// paginacion
	$page =  Sys::GetP('page', 1, true, $prefix); 
	$size = 10;
	$hoy = date('d/m/Y');
	// query
	$q = new PgQuery("
	SELECT r.*,
	tio.tipope_desc,
	c.cliente_desc, c.cliente_numdoc,
	pv.pventa_cerrarcaja
	FROM public.registro r
	JOIN public.tipope tio ON tio.tipope_id = r.tipope_id
	JOIN public.pventa pv ON pv.pventa_id = r.pventa_id
	LEFT JOIN public.cliente c On c.cliente_id = r.cliente_id
	WHERE 
	(
		r.registro_id::text ilike '%$filter%'
		OR r.registro_desc ilike '%$filter%'
		OR c.cliente_desc ilike '%$filter%'
	)
	AND r.pventa_id = '".Sys::GetUserPVentaId()."'
	AND ('$listtype'<>'now' OR pv.pventa_cerrarcaja = '0' OR r.registro_fecha::date = now()::date)
	ORDER BY r.registro_id DESC
	", NULL, false, true);
	$totalCount = $q->GetQueryCount();
	
	$pageCount = ceil($totalCount / $size); 
	$page = ($page > $pageCount)?1:$page; // controla que se use una pagina que no existe (fuera de rango), 1 pote aveces $pageCount puede ser cero 0
	$offset = (($page - 1) * $size);
	
	$q->sql .= " LIMIT $size OFFSET $offset";
	$q->Execute();
?>
<style>
.<?=$prefix?>-state-n {
    background-color: red;
}
.<?=$prefix?>-state-t {
    background-color: green;
}
.<?=$prefix?>-state-p {
    background-color: purple;
}
tr .retorno {
    background-color: orange!important;
}
.<?=$prefix?>-td-select {
    background-color: #0096DB;
}
</style>
<div class="bg" style="padding: 3px; line-height: 16px; vertical-align: middle;">
<table>
<tr>
	<td><span class="bold c-black fs-12 pd">Registro de Operaciones</span></td>
	<td style="padding: 0 5px 0 5px;"><a href="#" class="btn-icon refresh" onclick="<?=$prefix?>_reload_list(); return false;" title="actualizar lista"></a></td>
	<td style="padding: 0 5px 0 5px;"><button type="button" class="bold" onclick="<?=$prefix?>_cv();"><table><tr valign="middle"><td><img src="img/cache 4.png" width="32" border="0" align="middle"/></td><td>&nbsp;&nbsp;COMPRA / VENTA</td></tr></table></button></td>
</tr>
</table>
</div>
<!-- SEARCH -->
<div class="pd " style="padding-bottom: 1px;">
	<form id="<?=$prefix?>_frm_search" name="<?=$prefix?>_frm_search" onsubmit="<?=$prefix?>_search(); return false;">
	<div class="frm-pd">
		<span>Buscar&nbsp;</span>
		<span><input id="<?=$prefix?>_filter" type="text" name="filter" value="<?=$filter?>"/></span>
	</div>
	<div class="frm-pd">
		<button type="submit">consultar</button>
		<!--<select id="<?=$prefix?>_showlistby" name="showlistby">
            <option value=''>- todos -</option>
            <option value='1'>transferencias</option>
            <option value='2'>transferencias enviadas</option>
            <option value='3'>transferencias enviadas con retorno</option>
    </select>-->
		<button type="button" onclick="<?=$prefix?>_clear_search();">limpiar</button>
	</div> 
	</form>
</div>
<script>
function <?=$prefix?>_search() {
	var params = $('#<?=$prefix?>_frm_search').serialize();
	$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_container').html(data); });
};
function <?=$prefix?>_clear_search() {
	document.<?=$prefix?>_frm_search.filter.value = '';
	<?=$prefix?>_search();
};
$('#<?=$prefix?>_filter').focus();
</script>
<!-- PAGINATION -->
<script>
function <?=$prefix?>_go_page(i) {
	var params = 'load_from_session=1&page='+i;
	$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_container').html(data); });
};
</script>
<div class="frm-pd l" style="padding-bottom: 1px; border: none;">
<?php
	$pages = array();
	for($i=1; $i<=($is_admin===1?$pageCount:1); $i++) {
		$selCls = ($i==$page)?"page-selected bold c":"page c";
		$pages[] = "<a href=\"#\" onclick=\"{$prefix}_go_page($i); return false;\" class=\"$selCls c-black\" title=\"pagina $i\">$i</a>"; 	
	} 
?>
	<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td valign="top"><?="mostrando del ".($offset+1)." al ".($offset+$q->recordCount)." de $totalCount&nbsp;|&nbsp;"?></td>
		<td valign="top"><?=implode("", $pages)?></td>
	</tr>
	</table>
</div>
<script>
	function <?=$prefix?>_sort(by, type) { 
		var params = 'load_from_session=1&sort_by='+by+'&sort_type='+type;
		$.post('modules/<?=$module?>/list.php', params, function (data) { $('#<?=$prefix?>_container').html(data); });
	};
</script>
<?php
	function renderSort($title, $name) { global $orders, $prefix; 
?>
	<a href="#" onclick="<?=$prefix?>_sort('<?=$name?>', '<?=$orders[$name]['next_sort_type']?>'); return false;"><?=$title?></a><span class="sort-<?=$orders[$name]['sort_type']?>"></span>
<?php
	} 
?>
<form id="<?=$prefix?>_frm_list">
<div class="grid-container">
<table class="grid" width="100%">
<tr>
	<td class="cell-head" style="width: 20px;">E</td>
	<td class="cell-head" style="width: 20px;">S</td>
	<td class="cell-head bold" width="100"># Operacion</td>
	<td class="cell-head bold" width="190">Tipo Ope.</td>
	<td class="cell-head bold" width="140">Fecha y hora</td>
	<td class="cell-head bold">Detalles / Descripcion</td>
	<!--<td class="cell-head bold">&nbsp;</td>-->
</tr>
<?php
     
    $select_id = '';
    if ($q->recordCount>0) $select_id = $q->row['registro_id']; 
    
	$list = array();
	while($r = $q->Read()) {
		$id = $r['registro_id'];
		$list[] = $id;
        $rid = $r['registro_id'];
        $ts = Sys::getTimeStamp($r['registro_fecha']); 
		//$vigente[$r['tipooperacion_id']] = array($r['moneda_id_de']=>array($r['moneda_id_a']=>$r['registro_fecha']));
		if ($r['registro_retornar']=='1') {
		    if (is_null($r['registro_id_parent'])){ // es la transferencia origen
                $qre = new PgQuery("
                SELECT r.registro_id, r.registro_fecha::date as registro_fecha, r.registro_estado, d.moneda_id, m.moneda_simbolo, d.registro_det_importe
                FROM public.registro r
                JOIN public.registro_det d ON d.registro_id = r.registro_id
                JOIN public.moneda m ON m.moneda_id = d.moneda_id
                WHERE r.registro_id_main = '{$r['registro_id']}'
                AND r.registro_estado <> 'N'
                LIMIT 1", null, true, true);
            } else {
                $qre = new PgQuery("
                SELECT r.registro_id, r.registro_fecha::date as registro_fecha, r.registro_estado, d.moneda_id, m.moneda_simbolo, d.registro_det_importe
                FROM public.registro r
                JOIN public.registro_det d ON d.registro_id = r.registro_id
                JOIN public.moneda m ON m.moneda_id = d.moneda_id
                JOIN public.registro rp On rp.registro_id = '{$r['registro_id_parent']}' -- get reg parent 
                    AND r.registro_id_main = rp.registro_id -- filter by reg main 
                WHERE true 
                AND r.registro_estado <> 'N'
                LIMIT 1", null, true, true);
            }
            $re = $qre->row;
            // si no tiene retorno todavia: naraja, si tiene no..!!
            $css_retorno = $qre->recordCount==0?'retorno':'';
        } else $css_retorno = '';
?>
<tr id="<?=$prefix."_".$rid?>" class="<?=$prefix?>-tr" rid="<?=$id?>">
	<td class="cell c <?=$prefix?>-state-<?=strtolower($r['registro_estado'])?> <?=$css_retorno?>"></td>
	<td id="<?=$prefix?>_td_<?=$rid?>" class="cell c" style="padding: 0 0!important;"><input id="<?=$prefix?>_item_<?=$rid?>" type="radio" name="item" value="<?=$id?>"/></td>
	<td class="cell c c-theme bold"><?=$id?></td>
	<td class="cell l fs-9 <?=$r['registro_estado']=='N'?'c-red':''?>"><?=$r['tipope_desc']?>
<?php   if ($r['tipope_id']=='05' && is_null($r['registro_id_parent'])):?>
        <img src="img/bullet_go.png" style="border: none;"/>
<?php   elseif ($r['tipope_id']=='05'):?>
        <img src="img/bullet_return.png" style="border: none;"/>
<?php   endif;// transfer go-back?>	    
<?php   if ($r['registro_retornar']=='1'):?>
    </br><?php
            if (is_null($r['registro_id_parent'])): // es la transferencia origen
                if ($qre->recordCount>0):
                    if ($re['registro_estado']=='P'):
    
?><span class="" style="color: purple;">con retorno pendiente de aceptacion</span>
    </br><span class="c-black pointer fs-8" title="numero de registro de la transferencia retornada"><?=$re['registro_id']?></span> <span class="c-gray fs-8"><?="(".$re['registro_fecha'].")"?></span>
<?php               elseif ($re['registro_estado']=='T'):
    
?><span class="" style="color: green;">con retorno aceptado</span>
</br><span class="c-black pointer fs-8" title="numero de registro de la transferencia retornada"><?=$re['registro_id']?></span> <span class="c-gray fs-8"><?="(".$re['registro_fecha'].")"?></span>
<?php               endif;?>
<?php           else:?><span class="c-orange bold">con retorno pendiente</span>
<?php           endif;?>
<?php       else: // es el registro de transferencia destino
                if ($qre->recordCount>0):
                    if ($re['registro_estado']=='P'):
    
?><span class="" style="color: purple;">retornado con pendiente de aceptacion</span>
    </br><span class="c-black pointer fs-8" title="numero de registro de la transferencia retornada"><?=$re['registro_id']?></span> <span class="c-gray fs-8"><?="(".$re['registro_fecha'].")"?></span>
<?php               elseif ($re['registro_estado']=='T'):
    
?><span class="" style="color: green;">retornado con</span>
</br><span class="c-black pointer fs-8" title="numero de registro de la transferencia retornada"><?=$re['registro_id']?></span> <span class="c-gray fs-8"><?="(".$re['registro_fecha'].")"?></span>
<?php               endif;?>
<?php           else:?><span class="c-orange bold">pendiente de retorno</span>
<?php           endif;?>
<?php       endif;?>
<?php   endif;?>
	</td>
	<td class="cell l"><?=date('d/m/Y', $ts)?>&nbsp;<span class="c-gray fs-10"><?=date('H:i:s', $ts)?></span></td>
	<td class="cell l fs-8"><?php
	if ($r['tipope_id']=='06' || $r['tipope_id']=='07'): // prestamo
	?> <div class="c-gray fs-8">Cliente: <?=$r['cliente_desc']?></div><?php
	endif;?><?=$r['registro_desc']?></td>
	<!--<td class="cell l"><?=$r['login']?></td>
	<td class="cell">
		<span style="float: right;">
		<a href="#" class="btn-icon print" onclick="<?=$prefix?>_print('<?=$id?>'); return false;" title="imprimir"></a>
		</span>
	</td>-->
</tr>
<?php 
	} 
	$_SESSION[$prefix."list"] = serialize($list);
?>
</table>
</div>
</form>
<script>
var <?=$prefix?>_cm = Ext.create('Ext.menu.Menu', {
    floating: true,  //
    renderTo: Ext.getBody(),  // usually rendered by it's containing component
    rid: '',
    items: [{
        text: 'Imprimir',
        listeners: {
            click: function () {
                //alert(<?=$prefix?>_cm.rid);
                <?=$prefix?>_print(<?=$prefix?>_cm.rid);
            }
        }
    },{
        text: '<span class="c-red fs-8">Anular</span>',
        listeners: {
            click: function () {
                <?=$prefix?>_anular(<?=$prefix?>_cm.rid);
            } 
        } 
    }]
});
$('.<?=$prefix?>-tr').hover(function (e) { $(this).addClass('grid-row-over'); }, function (e) { $(this).removeClass('grid-row-over'); });
var <?=$prefix?>_row_old = $('#<?=$prefix?>_td_<?=$select_id?>');
$('.<?=$prefix?>-tr').click(function (e) { 
    //$(this).addClass('grid-row-select');
    //if (e.which == 1) { // solo click izquierdo
    var rid = $(this).attr('rid');
    <?=$prefix?>_row_old.removeClass('<?=$prefix?>-td-select');
    $('#<?=$prefix?>_td_'+rid).addClass('<?=$prefix?>-td-select');
    <?=$prefix?>_row_old = $('#<?=$prefix?>_td_'+rid);
    
    $('#<?=$prefix?>_item_'+rid).get(0).checked = true;
    <?=$prefix?>_det_load_list(rid);
    <?=$prefix?>_info_load_list(rid);
    <?=$prefix?>_saldomoneda_reload_list();
}).bind('contextmenu', function (e) {
	if (<?=$prefix?>_cm.rid != $(this).attr('rid')) {
		$(this).click();
    	<?=$prefix?>_cm.rid = $(this).attr('rid');
   	}
    <?=$prefix?>_cm.showAt(e.pageX, e.pageY);
    return false;
});

function <?=$prefix?>_cv(id) {
	var w = new RegistroCVWindow({});
	w.show();
};

function <?=$prefix?>_print(id) {
	var url = location.href.replace('#','').replace('index.php','');
	url += "modules/registro/print.php?id="+id;
	//alert(url);
	sys.print(url);
	//window.open("modules/registro/print.php?id="+id, "_blank");
};
function <?=$prefix?>_anular(id) {
	if (!confirm('Realmente desea ANULAR?')) return;
	var motivo = prompt('Motivo de la ANULACION');
	$.post('modules/<?=$module?>/core.php', 'action=Anular&registro_id='+id+'&registro_desc='+motivo, function (data) {
		if ($.trim(data) == 'ok') {
			sys.message('Se ha ANULADO satisfactoriamente');
			<?=$prefix?>_reload_list();
		} else {
			sys.alert(data);
		}
	});
};
function <?=$prefix?>_reload_list() {
	$.post('modules/<?=$module?>/list.php', 'load_from_session=1', function (data) { $('#<?=$prefix?>_container').html(data); });
	<?=$module?>_tipocambio_reload_list();
};
function <?=$prefix?>_det_load_list(parent) {
    $.post('modules/<?=$module?>/detail.list.php', 'id_parent='+parent, function (data) { $('#<?=$prefix?>_det_container').html(data); });
};
function <?=$prefix?>_info_load_list(parent) {
    $.post('modules/<?=$module?>/info.php', 'id_parent='+parent, function (data) { $('#<?=$prefix?>_info_container').html(data); });
};
function <?=$prefix?>_saldomoneda_reload_list() {
    $.post('modules/<?=$module?>/saldomoneda.list.php', '', function (data) { $('#<?=$prefix?>_saldomoneda_container').html(data); });
};
// Forms
RegistroACajaWindow = Ext.extend(Ext.Window, {
	id:'<?=$prefix?>_acaja_window', title: 'Apertura de Caja', width: 800, height: 600, modal: true,
	initComponent: function() {
		this.on('show', function (s) {
			$.post('modules/<?=$module?>/form.acaja.php', '', 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		RegistroACajaWindow.superclass.initComponent.call(this);
	}
});
RegistroMCajaWindow = Ext.extend(Ext.Window, {
    id:'<?=$prefix?>_mcaja_window', title: 'Mantenimiento de Caja', width: 500, height: 300, modal: true,
    initComponent: function() {
        this.on('show', function (s) {
            $.post('modules/<?=$module?>/form.mcaja.php', 'task='+this.p_task+'&id='+this.p_id, 
            function (data) { 
                $('#'+s.body.dom.id).html(data); 
            }); 
        });
        RegistroMCajaWindow.superclass.initComponent.call(this);
    }
});
RegistroCompraWindow = Ext.extend(Ext.Window, {
    id:'<?=$prefix?>_compra_window', title: 'Registro de Compra de Divisas', width: 600, height: 450, modal: true,
    initComponent: function() {
        this.on('show', function (s) {
            $.post('modules/<?=$module?>/form.compra.php', 'task='+this.p_task+'&id='+this.p_id, 
            function (data) { 
                $('#'+s.body.dom.id).html(data); 
            }); 
        });
        RegistroCompraWindow.superclass.initComponent.call(this);
    }
});
RegistroVentaWindow = Ext.extend(Ext.Window, {
    id:'<?=$prefix?>_venta_window', title: 'Registro de Venta de Divisas', width: 600, height: 450, modal: true,
    initComponent: function() {
        this.on('show', function (s) {
            $.post('modules/<?=$module?>/form.venta.php', 'task='+this.p_task+'&id='+this.p_id, 
            function (data) { 
                $('#'+s.body.dom.id).html(data); 
            }); 
        });
        RegistroVentaWindow.superclass.initComponent.call(this);
    }
});
RegistroPrestamoWindow = Ext.extend(Ext.Window, {
    id:'<?=$prefix?>_prestamo_window', title: 'Registro de Prestamo', width: 600, height: 450, modal: true,
    initComponent: function() {
        this.on('show', function (s) {
            $.post('modules/<?=$module?>/form.prestamo.php', 'task='+this.p_task+'&id='+this.p_id, 
            function (data) { 
                $('#'+s.body.dom.id).html(data); 
            }); 
        });
        RegistroPrestamoWindow.superclass.initComponent.call(this);
    }
});
RegistroCPrestamoWindow = Ext.extend(Ext.Window, {
    id:'<?=$prefix?>_cprestamo_window', title: 'Cancelacion de Prestamo', width: 950, height: 600, modal: true,
    initComponent: function() {
        this.on('show', function (s) {
            $.post('modules/<?=$module?>/form.cprestamo.php', 'id='+this.p_id, 
            function (data) { 
                $('#'+s.body.dom.id).html(data); 
            }); 
        });
        RegistroCPrestamoWindow.superclass.initComponent.call(this);
    }
});
RegistroTransferenciaWindow = Ext.extend(Ext.Window, {
    id:'<?=$prefix?>_transferencia_window', title: 'Transferencia', width: 600, height: 400, modal: true,
    initComponent: function() {
        this.on('show', function (s) {
            $.post('modules/<?=$module?>/form.transferencia.php', 'id='+this.p_id, 
            function (data) { 
                $('#'+s.body.dom.id).html(data); 
            }); 
        });
        RegistroTransferenciaWindow.superclass.initComponent.call(this);
    }
});
RegistroCVWindow = Ext.extend(Ext.Window, {
    id:'<?=$prefix?>_cv_window', title: 'Compra y Venta de Divisas', width: 700, height: 600, modal: true,
    initComponent: function() {
        this.on('show', function (s) {
            $.post('modules/<?=$module?>/form.cv.php', 'task='+this.p_task+'&id='+this.p_id, 
            function (data) { 
                $('#'+s.body.dom.id).html(data); 
            }); 
        });
        RegistroCVWindow.superclass.initComponent.call(this);
    }
});
RegistroCierreCajaWindow = Ext.extend(Ext.Window, {
    id:'<?=$prefix?>_cierrecaja_window', title: 'Cierre de Caja', width: 600, height: 600, modal: true,
    initComponent: function() {
        this.on('show', function (s) {
            $.post('modules/<?=$module?>/form.cierrecaja.php', 'task='+this.p_task+'&id='+this.p_id, 
            function (data) { 
                $('#'+s.body.dom.id).html(data); 
            }); 
        });
        RegistroCierreCajaWindow.superclass.initComponent.call(this);
    }
});
// init
$('#<?=$prefix."_".$select_id?>').click();
</script>