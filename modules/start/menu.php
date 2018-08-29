<?php
	include '../../modules/sys_usuario/quickform.inc.php'; 
?>
<script>
	function loadmodule(moduleid, title, module, params) {
		var module = module||moduleid;
		var params = params||'';
		sys.addTab(moduleid+'_tab', title, 'modules/'+module+'/init.php', params);
	};
	function loadimodule(module, title, defaultfile, params) {
		var file = defaultfile||'init.php';
		var params = params||'';
		//title, url, focus
		sys.addIFrameTab(title, 'modules/'+module+'/'+defaultfile, params);
	};
/*
 * load report on blank window
 */
	function loadreport(filename, params) {
	    var params = params||'';
	    params = (params!=''?('?'+params):'');
		var w = window.open('modules/reportes/'+filename, '_blank');
	};
/*
 * load report from module/report dir on iframe tab
 */
	function loadireport(title, filename, params) {
        var params = params||'';
        sys.addIFrameTab(title, 'modules/report/'+filename, params);
    };
</script>
<style>
.sf-menu * {
	font-size: 9pt;
}
.sf-menu li.sfHover a.menu-item {
	color: white;
}
.sf-menu li li.sfHover {
	background: #C7E5EE;
}
</style>
<div style="position: absolute; margin: 0 0; height:30px; width: 70%; left: 120px; top: 0; z-index:50; background: #C7E5EE; border: none;">
		<ul id="sgp_menu" class="sf-menu">
<?php
	$id_usuario = Sys::GetUserId();
	$qum = new PgQuery("
	SELECT m.id_menu 
	FROM sys.menu m 
	WHERE
	m.id_menu IN (
		SELECT um.id_menu FROM sys.usuario_menu um WHERE um.id_usuario = $id_usuario AND um.estado = 1
	) 
	OR m.id_menu IN (
		SELECT um.id_menu FROM sys.usuario_perfil up
		JOIN sys.usuario_menu um ON um.id_usuario = up.id_perfil AND um.estado = 1
		WHERE up.id_usuario = $id_usuario AND up.estado = 1
	)
	GROUP BY m.id_menu
	ORDER BY m.id_menu
	", NULL, true, true);
	$umlist = array(); while ($qum->Read()) { $umlist[] = $qum->row['id_menu']; }
	
	$qm = new PgQuery("SELECT * FROM sys.menu WHERE id_parent IS NULL ORDER BY orden", NULL, true, true);
	// MENU
 	while ($r = $qm->Read()) {
 		$id = $r['id_menu'];
 		$qm2 = new PgQuery("SELECT * FROM sys.menu WHERE id_parent=$id ORDER BY orden", NULL, true, true);
		$smlist = array();
		// filter SUBMENU 
		while ($r2 = $qm2->Read()) {
			if (in_array($r2['id_menu'], $umlist) || Sys::GetUserIsAdmin()==1) {
				$smlist[] = $r2;
			}
		}
 		if ((in_array($id, $umlist) || count($smlist)>0 || Sys::GetUserIsAdmin()==1) && $r['estado']==1) {
 			$as_func = trim($r['actionscript'])!=''?"execAS{$r['id_menu']}":'';
?>
			<li>
				<a class="menu-item" href="#" onclick="<?=$as_func!=''?"$as_func(); return false;":''?>" style=""><?=$r['nombre']?></a>
<?php	if ($as_func!='') { 
?>
		<script>
		function <?=$as_func?>() {
<?php		$tmp = "as".microtime(true).".php"; file_put_contents($tmp, $r['actionscript']); include "$tmp"; unlink($tmp); 
?>
		}; 
		</script>
<?php	} 
?>
<?php		
			if (count($smlist)>0) {
?>
				<ul style="width: 22em;">
<?php			// SUBMENU rendering
				foreach($smlist as $i=>$r) {
					$id = $r['id_menu']; 
					if ($r['estado']==1) {
						$as_func = trim($r['actionscript'])!=''?"execAS{$r['id_menu']}":'';
					if ($r['begin_group']==1 && $i>0):
?>
					<li>
						<div style="border-bottom: 1px solid #e5f5f4; border-top: 1px solid #8fadb4; margin: 5px 5px 5px 5px;"></div>
					</li>
<?php				endif;?>
					<li>
						<a class="submenu-item" href="#" onclick="<?=$as_func!=''?"$as_func(); return false;":''?>"><?=$r['nombre']?></a>
<?php					if ($as_func!='') { 
?>
						<script>
						function <?=$as_func?>() {
<?php						$tmp = "as".time().".php"; file_put_contents($tmp, $r['actionscript']); include "$tmp"; unlink($tmp); 
?>
						}; 
						</script>
<?php					} 
?>
					</li>
<?php				}
				} 
?>
				</ul>
<?php		} 
?>
			</li>
<?php	} 
 	}
?>
		</ul>
</div>
<script>
	$('#sgp_menu').superfish({delay:200, autoArrows: true});
</script>