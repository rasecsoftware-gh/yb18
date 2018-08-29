<script>
//Form
QuickUsuarioWindow = Ext.extend(Ext.Window, {
	id:'quick_usuario_window', title: 'Usuario - Cambiar Contraseña', width: 400, height: 200, modal: true,
	initComponent: function() {
		this.on('show', function (s) {
			$.post('modules/sys_usuario/quickform.php', '', 
			function (data) { 
				$('#'+s.body.dom.id).html(data); 
			});	
		});
		QuickUsuarioWindow.superclass.initComponent.call(this);
	}
});
function show_quickform_usuario() {
	var w =  new QuickUsuarioWindow();
	w.show();
};
</script>