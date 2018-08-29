<?php
	require_once '../../sys.php';
	$module = 'registro';
	$prefix = "{$module}";
?>
<style>
.sidemenu {
	padding: 0;
	margin: 0;
}
.sidemenu .section {
	padding: 5px 10px 5px 5px;
	display: block;
	color: black;
	border-bottom: 1px solid silver;  
	margin: 0 0 5px 0; 
}
.sidemenu .item {
	padding: 5px 5px 5px 30px;
	display: block;
	color: black; 
	font-weight: bold;
	margin-left: 5px;
	background: url(img/arrow-m.png) no-repeat 5px 50%;
}
.sidemenu .item:hover {
	color: white; /*#00659f;*/
	font-weight: bold;
	text-decoration: none; 
	background: #00668E url(img/arrow-m.png) no-repeat 5px 50%; /*#dfe9ef url(img/member-hover.gif) no-repeat 7px 50%;*/
}
</style>
<div class="sidemenu">
	<ul>
<?php
	$q = new PgQuery("SELECT * FROM public.tipope WHERE NOT tipope_id IN ('03','04') ORDER BY tipope_id", NULL, true, false);
	while ($r = $q->Read()):
?>
		<li><a class="item" href="javascript:void(0)" rid="<?=$r['tipope_id']?>"><?=ucfirst(strtolower($r['tipope_desc']))?></a></li>
<?php
	endwhile; 
?>
	</ul>
</div>
<script>
$('.item').click(function () {
	var rid = $(this).attr('rid');
	if (rid == '01') {
	    var F = new RegistroACajaWindow({});
	    F.show();
	} else if (rid == '02') {
        var F = new RegistroMCajaWindow({});
        F.show();
    } else if (rid == '03') { 
        var F = new RegistroCompraWindow({});
        F.show();
    } else if (rid == '04') { 
        var F = new RegistroVentaWindow({});
        F.show();
    } else if (rid == '05') { 
        var F = new RegistroTransferenciaWindow({});
        F.show();
    } else if (rid == '06') { 
        var F = new RegistroPrestamoWindow({});
        F.show();
    } else if (rid == '07') { 
        var F = new RegistroCPrestamoWindow({});
        F.show();
    } else if (rid == '99') { 
        var F = new RegistroCierreCajaWindow({});
        F.show();
    } else {
        alert('Operacion no implementada');
    }
});
</script>