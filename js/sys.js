sys = {};
sys.init = function () {
	Ext.tip.QuickTipManager.init();
	Ext.create('Ext.container.Viewport', {
	    layout: 'border',
	    items: [{
	        region: 'north',
	        //bodyStyle: {background: '#dfe8f6'},
	        style: {overflow: 'visible', zIndex: 3}, bodyStyle: {overflow: 'visible', zIndex: 3}, 
	        height: 30,
	        border: false,
	        margins: '0 0 0 0',
	        listeners: {
	        	'render': function (s) {
	        		$('#'+s.body.dom.id).load('modules/start/header.inc.php');
	    		}
	    	}
	    }, /*{
	        region: 'west',
	        collapsible: true,
	        split: true,
	        title: 'Opciones',
	        width: 130,
	        listeners: {
	        	'render': function (s) {
	        		$('#'+s.body.dom.id).load('modules/start/sidemenu.php');
	    		}
	    	}
	        // could use a TreePanel or AccordionLayout for navigational items
	    },*/{
	        id: 'maintab', region: 'center', activeTab: 0, border: true,
	        xtype: 'tabpanel', // TabPanel itself has no title
	        items: [{
				title: 'Inicio', border: false, bodyStyle: {backgroundColor: 'white'}, autoScroll: true,
				listeners: {
		        	'render': function (s) {
		        		$('#'+s.body.dom.id).load('modules/start/init.php');
		    		}
		    	}
	        }]
	    }]
	});

	var loading = $('#loading');
	$(document).ajaxSend(function() {
		loading.show();
	}).ajaxComplete(function(s, a){
		loading.hide();
	}).ajaxError(function(){
		loading.hide();
	});
	
	Ext.EventManager.on(window, 'beforeunload', function (e, t) {
		e.stopEvent();
	});
	
	function showStartMessage () {
		sys.message('Bienvenido al Sistema', 5000);
	};
	
	Ext.defer(showStartMessage, 1000);
};
sys.message = function (text, _dismissDelay) {
	var dd = _dismissDelay||5000;
	var sm = new Ext.QuickTip({
		title: 'Mensaje',
		html: text,
		padding: 20,
		dismissDelay: dd
	});
	sm.showAt([Ext.getBody().getWidth() - 300,0]);	
};
sys.alert = function (text, title) {
	Ext.Msg.alert(title||'Mensaje', text);
};
sys.error = function (text) {
	var dd = 5000;
	var sm = new Ext.QuickTip({
		title: 'Error',
		html: '<span class="sys-error-message">'+text+'</span>',
		padding: 10,
		dismissDelay: dd
	});
	sm.showAt([Ext.getBody().getWidth() - 300,0]);	
};
sys.confirm = function (text, title, fn) {
   Ext.MessageBox.confirm(title||'Confirmar', text, fn); 
};
sys.confirmYNC = function (text, title, fn) {
   Ext.MessageBox.show({
       title:title||'Confirmar',
       msg: text,
       buttons: Ext.MessageBox.YESNOCANCEL,
       fn: fn,
       icon: Ext.MessageBox.QUESTION
   }); 
};
sys.getFloat = function (v) {
    var val = parseFloat(v.replace(/,/g,'')); // limpiamos los separadores de miles (comas) 
    return isNaN(val)?0:val;   
};
sys.addTab = function (id, title, url, params) {
	var t = null;
	var _id = id||Ext.id(null, 'sys_tab_');
	Ext.getCmp('maintab').items.each(function(item) {
		if (item.id == _id) {
			t = item;
			return false;
		}
	}); 
	if (t == null) {
		Ext.getCmp('maintab').add({
            id: _id, title: title, bodyCssClass: 'bg', bodyCls: 'bg', cls: 'bg', padding: 2, closable: true, autoScroll: true, html: 'cargando...',
            listeners: {
	        	'render': function (s) {
        			$.post(url, params, function (data) { $('#'+s.body.dom.id).html(data); });
	    		}
	    	}
        });
		Ext.getCmp('maintab').doLayout(true, true);
	}
	Ext.getCmp('maintab').setActiveTab(_id);
};
sys.addIFrameTab = function(title, url, focus, _id) {
	var t = null;
	var id = _id||Ext.id(null, 'sys_tab_');
	Ext.getCmp('maintab').items.each(function(item) {
		if (item.id == id) {
			t = item;
			return false;
		}
	}); 
	if (t == null) {
		Ext.getCmp('maintab').add({
			id: id, title: title, bodyCssClass: 'bg', padding: 2, closable: true, autoScroll: true, html: 'cargando...',
	        listeners: {
	        	'render': function (s) {
	    			$.post('iframe_resolver.php', 'url='+encodeURIComponent(url), function (data) { $('#'+s.body.dom.id).html(data); });
	    		}
	    	}
	    });
		Ext.getCmp('maintab').doLayout(true, true);
	}
	if (focus===false) return;
	Ext.getCmp('maintab').setActiveTab(id);
};
// utils
sys.iformat = function (input)
{
	var parts = input.value.split('.');
	var d = '';
	if (parts.length>1) {
		d = '.' + parts[1];
	}
	var e = parts[0];
	var num = e.replace(/,/g,'');
	if(!isNaN(num)){
		num = num.toString().split('').reverse().join('').replace(/(?=\d*,?)(\d{3})/g,'$1,');
		num = num.split('').reverse().join('').replace(/^[,]/,'');
		input.value = num + d;
	}
	else { 
		sys.error('Solo se permiten numeros');
		input.value = num.replace(/[^\d,]*/g,'') + d;
	}
};
sys.nformat = function (val)
{
	var parts = val.split('.');
	var d = '';
	if (parts.length>1) {
		d = '.' + parts[1];
	}
	var e = parts[0];
	var num = e.replace(/,/g,'');
	if(!isNaN(num)){
		num = num.toString().split('').reverse().join('').replace(/(?=\d*,?)(\d{3})/g,'$1,');
		num = num.split('').reverse().join('').replace(/^[,]/,'');
		return num+d;
	}
	else { 
		sys.error('Solo se permiten numeros');
		return num.replace(/[^\d,]*/g,'') + d;
	}
};
sys.getPathURL = function () {
	return location.href.replace('#','').replace('index.php','');
};
sys.print = function (urlfile) {
	var params = $.param({
	    'action': 'DoPrint',
	    'url': urlfile
	});
	$.post('printmanager.php', params, function (data) {
	    if ($.trim(data) == 'ok') {
	        sys.message('Impresion realizada satisfactoriamente');
	    } else {
	        if (data.search('<script>')>=0) {
	           $('#printmanager_container').html(data);    
	        } else {
	           sys.alert(data);   
	        }
	    }
	});
};
