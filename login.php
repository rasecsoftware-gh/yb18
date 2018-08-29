<?php
	define('sys_checksession', false);
	require_once 'sys.php';
	if (Sys::GetR('logout', 0) == 1) {
		$_SESSION['active'] = 0;
		header("Location: index.php"); exit;
	}
	$msg = ""; 
    $login = '';
	if (Sys::GetP('check', 0) == 1) {
		$login = Sys::GetP('login', '');
		$pw = Sys::GetP('password', '');
		$q = pg_query(Sys::GetConnection(), "
			SELECT id_usuario, is_admin, nombre 
			FROM sys.usuario 
			WHERE UPPER(login) like UPPER('$login') AND password = '$pw' AND estado = 1;");
		if ($q===false) {
			exit(pg_last_error());
		}
		if (pg_num_rows($q)>0) {
			$ru = pg_fetch_assoc($q);
			$_SESSION['active'] = 1;
			$_SESSION['sys_user_name'] = $login;
			$_SESSION['sys_user_id'] = $ru['id_usuario'];
			$_SESSION['sys_user_ip'] = Sys::GetClientIP();
			$_SESSION['sys_user_station'] = gethostbyaddr(Sys::GetClientIP());
			$_SESSION['sys_user_is_admin'] = $ru['is_admin']==1?1:0;
			$_SESSION['sys_user_establecimiento_id'] = '';
			$_SESSION['sys_user_pventa_id'] = '';
			// extra
			$_SESSION['sys_user_nickname'] = $ru['nombre'];
			//$_SESSION['sys_user_id_trabajador'] = intval($ru['id_trabajador']);  
			pg_free_result($q);
			header("Location: afterlogin.php"); exit;
		} else {
			$msg = "Usuario o clave incorrecto";
		}
		pg_free_result($q);
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title><?=Config::GetSystemName()?> - Inicio de sesion</title>
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
	padding: 30px;
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
<script>
	$(document).ready(function () {
		document.frm.login.focus();
	});
	//color #00668e
	//bg #C7E5EE
</script>
</head>
<body style="background: #C7E5EE;">
	<div id="main-title">
	<?=Config::GetOrganizationSiglas()?>
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
			<div id="login-container">
			<form name="frm" method="post">
			<input type="hidden" name="check" value="1"/>
			<table width="100%" border="0">
			<tr> 
				<td colspan="3" class="pd fs-14 bold c">Inicio de sesi&oacute;n</td>
			</tr>
			<tr>
				<td colspan="3" class="pd bold c c-red"><?=$msg?></td>
			</tr>
			<tr>
				<td rowspan="3" style="padding: 5px; text-align: center;">
					<img src="img/kdmconfig_72.png" border="0"/>
				</td>
				<td>usuario</td>
				<td><input style="padding: 5px!important;" type="text" name="login" value="<?=$login?>"/></td>
			</tr>
			<tr>
				<td>clave</td>
				<td><input style="padding: 5px!important;" type="password" name="password" value=""/></td>
			</tr>
			<tr>
				<td colspan="3" align="center"><button type="submit">Acceder</button></td>
			</tr>
			</table>
			</form>
			</div>
		</td>
	</tr>
	</table>
	<br/>
	<div class="pd c fs-7 c-gray">&copy; <?=date('Y')?> <?=Config::GetOrganizationSiglas()?> . Todos los derechos reservados.</div>
</body>
</html>