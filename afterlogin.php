<?php
	define('sys_checksession', true);
	require_once 'sys.php';
	if (Sys::GetUserIsAdmin() == 1) {
		header("Location: index.php"); exit;
	}
	$msg = ""; 
	if (Sys::GetP('select', 0) == 1) {
	    $user_id = Sys::GetUserId();
        $pventa_id = Sys::GetP('pventa_id','');
		$q = new PgQuery("
		SELECT up.*, p.establecimiento_id
		FROM sys.usuario_pventa up
		JOIN public.pventa p On p.pventa_id = up.pventa_id 
		WHERE up.usuario_id = $user_id AND up.pventa_id = '$pventa_id'
		", NULL, true, false);
		if ($q->recordCount > 0) {
			$r = $q->row;
			$_SESSION['sys_user_establecimiento_id'] = $r['establecimiento_id'];
			$_SESSION['sys_user_pventa_id'] = $r['pventa_id'];  
			$_SESSION['sys_user_psa'] = $r['usuario_pventa_psa'];
			header("Location: index.php"); exit;
		} else {
			$msg = "Puesto de Venta no asociado al Usuario";
		}
		pg_free_result($q);
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title><?=Config::GetSystemName()?> - Seleccionar Establecimiento</title>
<link rel="shortcut icon" href="img/logo.ico"/>
<link href="css/base.css" rel="stylesheet" type="text/css"/>
<link href="css/theme.css" rel="stylesheet" type="text/css"/>
<script src="js/jquery.ui/js/jquery-1.7.1.min.js" type="text/javascript"></script>
<style>
#login-container {
	box-shadow: 0px 3px 5px gray;
	-webkit-box-shadow: silver 0px 3px 5px;
	-moz-box-shadow: gray 0px 3px 5px;
	border: 1px solid silver; 
	background: white url(img/table_header_gradient_1.png) repeat-x left top; 
	padding: 5px 5px 5px 5px;
}
#main-title {
	position: absolute;
	left: 20px;
	top: 17px;
	font-size: 20pt;
	font-weight: bold;
	color: #b2d5df;
}
</style>
<style>
.listmenu {
	padding: 0;
	margin: 0;
}
.listmenu .section {
	padding: 5px 10px 5px 5px;
	display: block;
	color: black;
	border-bottom: 1px solid silver;  
	margin: 0 0 5px 0; 
}
.listmenu .item {
	padding: 5px 5px 5px 13px;
	display: block;
	color: gray; 
}
.listmenu .item:hover {
	color: #00659f;
	background: #dfe9ef;
	text-decoration: none;
}
</style>
<script>
	$(document).ready(function () {
		//document.frm.login.focus();
	});
	//color #00668e
	//bg #C7E5EE
</script>
</head>
<body style="background: #C7E5EE;">
	<div id="main-title">
	<?=Config::GetSystemDescription()?>
	</div>
	<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
	<table width="400" border="0" align="center">
	<tr>
		<td colspan="1" class="pd fs-9 c bold" style="color: #00668e;">
		<?=Config::GetSystemDescription()?>
		</td>
	</tr>
	<tr>
		<td align="left" valign="top">
			<div id="login-container" style="">
			<form name="frm" method="post">
			<input type="hidden" name="select" value="1"/>
			<input id="pventa_id" type="hidden" name="pventa_id" value=""/>
			<div>Acceder a:</div>
<?php
	$user_id = Sys::GetUserId();
	$is_admin = Sys::GetUserIsAdmin();
	$q = new PgQuery("
	SELECT * 
	FROM sys.usuario_pventa up
	JOIN public.pventa p On p.pventa_id = up.pventa_id
	JOIN public.establecimiento e On e.establecimiento_id = p.establecimiento_id
	WHERE up.usuario_id = $user_id
	ORDER BY p.establecimiento_id, p.pventa_id
	", NULL, true, false);
?>
			<div class="listmenu">
				<ul>
<?php
	while ($r = $q->Read()): 
?>
					<li><a class="item" href="#" rid="<?=$r['pventa_id']?>"><?=$r['establecimiento_desc']." / ".$r['pventa_desc']?></a></li>
<?php	
	endwhile; 
?>
				</ul>
			</div>
			<div class="pd">
				<!--<button type="submit">Acceder</button>-->
			</div>
			</form>
			</div>
		</td>
	</tr>
	</table>
	<script>
	$('.item').click(function () {
		var rid = $(this).attr('rid');
		$('#pventa_id').val(rid);
		document.frm.submit();
	});
	</script>
	<br/>
	<div class="pd c fs-7 c-gray">&copy; <?=date('Y')?> <?=Config::GetOrganizationSiglas()?> . Todos los derechos reservados.</div>
</body>
</html>