<?php
	define('sys_checksession', true);
	require_once 'sys.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="C ontent-Type" content="text/html; charset=ISO-8859-1">
<title><?=Config::GetSystemName()?></title>
<link rel="shortcut icon" href="img/logo.ico"/>
<link href="css/base.css" rel="stylesheet" type="text/css"/>
<link href="css/theme.css" rel="stylesheet" type="text/css"/>
<!--<link href="css/forms.css" rel="stylesheet" type="text/css"/>-->
<link href="css/fix.css" rel="stylesheet" type="text/css"/>
<!-- JQuery -->
<link rel="stylesheet" type="text/css" href="js/jquery.ui/css/redmond/jquery-ui-1.8.18.custom.css" />
<script src="js/jquery.ui/js/jquery-1.7.1.min.js" type="text/javascript"></script>
<script src="js/jquery.ui/js/jquery-ui-1.8.18.custom.min.js" type="text/javascript"></script>
<script src="js/jquery.ui/js/jquery.ui.datepicker-es.js" type="text/javascript"></script>
<!-- Ext Links -->
<link rel="stylesheet" type="text/css" href="js/ext-4/resources/css/ext-all.css" />
<script type="text/javascript" src="js/ext-4/ext-all.js"></script>
<!-- Gds Links -->
<!-- 
<link rel="stylesheet" type="text/css" href="js/gds/ext.css" />
<script type="text/javascript" src="js/gds/ext.js"></script>
<script type="text/javascript" src="js/gds/SearchWindow.js"></script>
 -->
<!-- Others Libs -->
<link href="js/superfish.css" media="screen" rel="stylesheet" type="text/css" >	
<script src="js/superfish.js" type="text/javascript"></script>
<!-- System Links -->
<script src="js/sys.js" type="text/javascript"></script>
<style>
#loading {
	display: none; 
	padding: 10px; 
	background-color: #e9f2ff; 
	position: absolute; 
	left: 48%; top: 30%; 
	border: 1px solid #8eaace;
}
#sys_message {
	background-image: url(img/bg_header_gradient.png); 
	background-repeat: repeat-x; 
	padding: 5px 5px;
}
</style>
<script>
	Ext.Loader.setConfig({enabled: true});
	Ext.require([
		'Ext.grid.*',
		'Ext.data.*',
		'Ext.util.*',
		'Ext.Action',
		'Ext.tab.*',
		'Ext.button.*',
		//'Ext.form.*',
		//'Ext.layout.container.Card',
		'Ext.layout.container.Border',
		'Ext.container.Viewport' 
	]);
	Ext.onReady(function() {
		sys.init();
	});
</script>
</head>
<body>
	<script type="text/javascript" src="js/ext-4/locale/ext-lang-es.js"></script>
	<div id="loading" class="hidable" style="z-index: 99999;"><img src="img/loading.gif" border="0" align="left">&nbsp;cargando</div>
	<div id="printmanager_container" class="hidable hidden"></div>
</body>
</html>