<?php
	
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
	padding: 5px 5px 5px 13px;
	display: block;
	color: gray; 
}
.sidemenu .item:hover {
	color: #00659f;
	background: #dfe9ef;
	text-decoration: none;
}
</style>
<div class="sidemenu">
	<div class="section pd">
	Generales
	</div>
	<ul>
		<li><a class="item" href="javascript:void(0)" mid="rubro" url="modules/rubro/init.php">Rubros</a></li>
		<li><a class="item" href="javascript:void(0)" mid="tipo_curso" url="modules/tipo_curso/init.php">Tipos de Curso</a></li>
		<li><a class="item" href="javascript:void(0)" mid="asociacion" url="modules/asociacion/init.php">Asociaciones</a></li>
	</ul>
	<div class="section pd">
	Registro
	</div>
	<ul>
		<li><a class="item" href="javascript:void(0)" mid="empresario" url="modules/empresario/init.php">Empresarios</a></li>
		<li><a class="item" href="javascript:void(0)" mid="capacitador" url="modules/capacitador/init.php">Capacitadores</a></li>
		<li><a class="item" href="javascript:void(0)" mid="orientador" url="modules/orientador/init.php">Orientadores</a></li>
		<li><a class="item" href="javascript:void(0)" mid="curso" url="modules/curso/init.php">Cursos</a></li>
		<li><a class="item" href="javascript:void(0)" mid="orientacion" url="modules/orientacion/init.php">Orientaciones</a></li>
	</ul>
	<div class="section pd">
	Registro de Asistencia
	</div>
	<ul>
		<li><a class="item" href="javascript:void(0)" mid="curso_asistente" url="modules/curso_asistente/init.php">Asistencia de Curso</a></li>
		<li><a class="item" href="javascript:void(0)" mid="orientacion_asistente" url="modules/orientacion_asistente/init.php">Asistencia de Orientacion</a></li>
	</ul>
</div>
<script>
$('.item').click(function () {
	var mid = $(this).attr('mid')||'';
	var url = $(this).attr('url')||'#';
	var iframe = $(this).attr('iframe')||'0';
	var title = $(this).html()||'';
	var param = $(this).attr('param')||'';
	if (iframe=='0') {
		sys.addTab(mid, title, url, param);
	} else {
		sys.addIFrameTab(title, url, true, mid);
	}
});
</script>