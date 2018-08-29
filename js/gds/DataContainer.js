// JavaScript Document
// - setDataValue (colname, value)
// - se aniadio checkSuccess, verifica el resultado json.success si es true o false y fallo, message
// - se aniadio el parametro para excluir columnas en la actualizacion del 
//	 registro de una relacion: updateExcludeColumns: ['eta_columna_no_se_actualiza', ...], 
//   para no enviarlo al servidor por gusto pues.. XD
if (typeof gds == 'undefined') gds = {};
if (typeof gds.ext == 'undefined') gds.ext = {};
var gds_ext_DataContainer;
gds.ext.DataContainer = {
	schema: {}, //{ name:'c', relations:[{name:['detalle']}] },
	sendOriginalData: false,
	confirmDelete: true,
	showUpdatedMsg: true,	
	requestUrl: null,
	debug: false,
	// CRUD actions
	requestCreateAction: null, //'Create', //optional
	requestReadAction: null,
	requestUpdateAction: null,
	requestDeleteAction: null, //'Delete', //optional
	// client actions
	oldAction: 'none',
	tryAction: null,
	currentAction: 'none',
	actionParams: null,
	initialize: function() {
		// las propiedades de tipo objeto se inicializan dinamicamente por que si no se comportan como variables globales a nivel 
		// de todas las instancias de las clase
		this.data = {};
		this.bkOriginal = {};
		this.bkData = {};
		this.actionParams = {};
		this.actionButtons = {};
		this.updatableControlsFromState =  new Array();
		this.addEvents(
			'beforeaction', // function (sender:object, args:{action:string, next:function, [data:object]}), llamar a next() para continuar
			// se dispara cuando termina la peticion del xhr update action, se tiene que llamar a la funcion next() para continuar
			// function (sender:object, args:{result:object, next:function})
			'updaterequestloaded',
			// se dispara cuando termina la peticion del xhr delete action, se tiene que llamar a la funcion next() para continuar
			// function (sender:object, args:{result:object, next:function})
			'deleterequestloaded',
			'afteraction', // function (sender:object, args:{action:string})
			'afteraction.new', // function (sender:object, args:{action:string})
			'afteraction.edit', // function (sender:object, args:{action:string})
			'afteraction.update', // function (sender:object, args:{action:string})
			'afteraction.cancel', // function (sender:object, args:{action:string})
			'afteraction.delete', // function (sender:object, args:{action:string})
			'beforesetdata', // function (Object sender, Object args:{Object data})
			'aftersetdata', // function (Object sender, Object args)
			'ready' // function (sender:object)
		);
		this.on(this.checkReadyOnEvent, function () {
			this.createLoadMask();
			//console.info('DataContainer '+this.checkReadyOnEvent+'!');
			// verifica y dispara el evento 'ready' si se ha completado de cargar las peticiones registradas con 'beginLoad()'
			// y terminadas con 'endLoad()'
			this.checkReady.defer(this.checkReadyTimeInterval, this);
		}, this);
	},
	resetControls: function () {
		this.data = {};
		this.oldAction = 'none';
		this.currentAction = 'none';
		this.actionParams = null;
		this.controls = this.getAllControls();
		this.blankControls();
		this.disableControls();
		this.updateActionButtons();
		this.updateControlsFromState();
	},
	beforeAction: function() {
		var action = this.tryAction;
		switch(action) {
			case 'new':
				if (this.hasListener('beforeaction')) {
					this.fireEvent('beforeaction', this, {'action':action, next:this.successBeforeAction.createDelegate(this)});
				} else {
					this.successBeforeAction();
				}
			break;
			case 'edit':
				if (this.hasListener('beforeaction')) {
					this.fireEvent('beforeaction', this, {'action':action, next:this.successBeforeAction.createDelegate(this)});
				} else {
					this.successBeforeAction();
				}
			break;
			case 'cancel':
				if (this.hasListener('beforeaction')) {
					this.fireEvent('beforeaction', this, {'action':action, next:this.successBeforeAction.createDelegate(this)});
				} else {
					this.successBeforeAction();
				}
			break;
			case 'update':
				this.readData();
				if (this.hasListener('beforeaction')) {
					this.fireEvent('beforeaction', this, {'action':action, next:this.successBeforeAction.createDelegate(this), 'data':this.data});
				} else {
					this.successBeforeAction();
				}
			break;
			case 'view':
				if (this.hasListener('beforeaction')) {
					this.fireEvent('beforeaction', this, {'action':action, next:this.successBeforeAction.createDelegate(this)});
				} else {
					this.successBeforeAction();
				}
			break;
			case 'delete':
				this.readData();
				if (this.hasListener('beforeaction')) {
					this.fireEvent('beforeaction', this, {'action':action, next:this.successBeforeAction.createDelegate(this), 'data':this.data});
				} else {
					this.successBeforeAction();
				}
			break;
		}
	},
	successBeforeAction: function() {
		var action = this.tryAction;
		switch(action) {
			case 'new':
				this.backupAndReadData();
				this.blankControls();
				this.loadData();
			break;
			case 'edit':
				this.backupData(); 
				if (typeof this.actionParams == 'object') {
					this.blankControls();
					this.loadData();
				} else {
					//this.backupData(); // se movio al inicio... :S ...keP keP!
					this.enableControls();
					this.afterAction();
				}
			break;
			case 'cancel':
				this.restoreData();
				this.currentAction = this.oldAction;
				this.oldAction = this.oldAction2;
				this.disableControls();
				this.afterAction();
			break;
			case 'update':
				this.updateData();
			break;
			case 'view':
				// x no habilitar los controles primero no se mostraban adecuadamente!
				this.enableControls();
				this.blankControls();
				this.loadData();
			break;
			case 'delete':
				if (this.confirmDelete == true) {
					Ext.MessageBox.confirm('Confirmar', 'Realmente desea Eliminar?', function (btn) {
						if (btn == 'yes') {
							this.deleteData();
						} else {
							//*****************??
							this.hideMask();
						}
					}, this);
				} else {
					this.deleteData();
				}
			break;
		}
	},
	loadData: function() {
		// local data
		if (this.loadDataMode == 'local') {
			this.original = this.actionParams;
			// tmr! x asignar directamente no funkaba bien el seteo de los record!
			this.data = Ext.apply({}, this.actionParams);
			this.setData(this.schema, this, this.data);
			this.successLoadData();
			return;
		}
		// 'actionParams' is null if new or setted if edit :) :S
		var loaded = function (data) {
			this.hideMask();
			this.original = data;
			this.data = data;
			this.setData(this.schema, this, data);
			this.successLoadData();
		};
		var onError = function (xhr) {
			this.hideMask();
			Ext.MessageBox.alert('LoadData Error', xhr.responseText);
		};
		var rAction = this.getRequestAction();
		if (rAction != null) {
			$.ajax({
				url: this.getRequestUrl(), type: 'post',
				processData: true, // not postdata
				data: Ext.apply({'action': rAction}, this.actionParams),
				success: loaded.createDelegate(this),
				error: 	onError.createDelegate(this),
				dataType: 'json'
			});
			this.showMask();
		} else {
			this.successLoadData();
		}
	},
	successLoadData: function () {
		var action = this.tryAction;
		switch(action) {
			case 'new':
				this.enableControls();
				this.afterAction();
			break;
			case 'edit':
				this.backupData();
				this.enableControls();
				this.afterAction();
			break;
			case 'view':
				this.backupData();
				this.disableControls();
				this.afterAction();
			break;
		}
	},
	updateData: function () {
		var loaded = function (result) {
			this.hideMask();
			if (this.hasListener('updaterequestloaded')) {
				this.fireEvent('updaterequestloaded', this, {'result':result, next:this.afterUpdateData.createDelegate(this, [result])});
			} else if (this.checkSuccess == true) {
				if (result.success == true) {
					this.afterUpdateData(result);
				} else {
					this.hideMask();
					Ext.MessageBox.alert('Mensaje', result.message);
				}
			} else {
				this.afterUpdateData(result);
			}
		};
		// for Jquery, xhr: XHRObject
		var onError = function(xhr) {
			this.hideMask();
			Ext.MessageBox.alert('UpdateData Error', xhr.responseText);
		};
		var rAction = this.getRequestAction();
		if (rAction != null) {
			var postdata = {'action':rAction, 'data':this.data, 'original':(this.sendOriginalData==true)?this.original:null};
			if (gds.ext.DataContainer.debug == true || this.debug == true) {
				//console.debug(postdata);
			}
			$.ajax({
				url: this.getRequestUrl(), type: 'post',
				processData: false, // for postdata
				data: Ext.encode(postdata),
				success: loaded.createDelegate(this),
				error: onError.createDelegate(this),
				dataType: 'json'
			});
			this.showMask();
		} else {
			this.afterUpdateData();
		}
	},
	afterUpdateData: function(rr) { //request result
		if (gds.ext.DataContainer.debug == true || this.debug == true) {
			//console.debug(rr);
		}
		if (this.loadDataFromUpdate === true) {
			// se modifico OJO!*****
			// this.data = rr.data; x
			this.data = Ext.apply(this.data, rr.data);  
			this.setData(this.schema, this, this.data); // aki se modifico rr.data x this.data OJO!****
		}
		this.disableControls();
		if (Ext.isString(rr.message)) {
			Ext.MessageBox.alert('Mensaje', rr.message);
		} else if (this.showUpdatedMsg === true) {
			this.showMsg('Mensaje', 'Se ha guardado satisfactoriamente');
		}
		this.afterAction();
	},
	deleteData: function () {
		var loaded = function (result) {
			this.hideMask();
			if (this.hasListener('deleterequestloaded')) {
				this.fireEvent('deleterequestloaded', this, {'result':result, next:this.afterDeleteData.createDelegate(this)});
			} else if (this.checkSuccess == true) {
				if (result.success == true) {
					this.afterDeleteData();
				} else {
					this.hideMask();
					Ext.MessageBox.alert('Mensaje', result.message);
				}
			} else {
				this.afterDeleteData();
			}
		};
		// xhr: XHRObject
		var onError = function(msg, xhr) {
			this.hideMask();
			Ext.MessageBox.alert('Error', xhr.responseText); // ********************
		};
		var rAction = this.getRequestAction();
		if (rAction != null) {
			$.ajax({
				url: this.getRequestUrl(), type: 'post',
				processData: false, // for postdata
				data: Ext.encode({'action':rAction, 'data':this.data, 'original':(this.sendOriginalData==true)?this.original:null}),
				success: loaded.createDelegate(this),
				error: onError.createDelegate(this),
				dataType: 'json'
			});
			this.showMask();
		} else {
			// *************
			//this.hideMask();
		}
	},
	afterDeleteData: function () {
		this.blankControls();
		this.disableControls();
		this.afterAction();
	},
	afterAction: function () {
		if (this.tryAction != 'cancel') {
			this.oldAction2 = this.oldAction;
			this.oldAction = this.currentAction;
			this.currentAction = this.tryAction;
		}
		this.updateActionButtons();
		this.updateControlsFromState();
		this.hideMask();
		
		var tryAct = this.tryAction;
		this.tryAction = null;
		if (this.hasListener('afteraction.'+tryAct)) {
			this.fireEvent('afteraction.'+tryAct, this, {'action':tryAct});	
		}
		this.fireEvent('afteraction', this, {'action':tryAct});
	},
	doAction: function (action, params, mode) { // mode: 'local' or 'remote' (default 'remote')
		this.actionParams = params;
		this.loadDataMode = mode||'remote';
		this.tryAction = action;
		this.controls = this.getAllControls();
		this.beforeAction();
	},
	// *** utils *** //
	getAllControls: function () {
		if (Ext.isArray(this.controls)) return this.controls;
		//{ name:'c', relations:[{name:['detalle']}] },
		if (typeof this.schema != 'object') return [];
		if (typeof this.schema.name == 'undefined') return [];
		return gds.ext.getControls(this.schema, this);
	},
	backupAndReadData: function () {
		this.bkOriginal = Ext.apply({}, this.original);
		this.bkData = Ext.apply({}, this.data);
		this.readData();
	},
	backupData: function () {
		this.bkOriginal = Ext.apply({}, this.original);
		this.bkData = Ext.apply({}, this.data);
	},
	restoreData: function () {
		this.data = Ext.apply({}, this.bkData);
		this.setData(this.schema, this, this.data);
	},
	blankControls: function () {
		var blankControl = function (c) {
			switch (c.getXType()) {
				case 'grid': if(c.store) {c.store.removeAll();}	break;
				case 'label': 
					if (c.setText) {
						c.setText(''); 
					} else {
						if (c.el) c.el.dom.innerHTML = '';
					}
				break;
				default: c.setValue(null); break;
			}
		}
		for(var i=0;i<this.controls.length;i++) {
			blankControl(this.controls[i]);
		}
	},
	disableControlsOnRead: true,
	disableControls: function () {
		var disableControl = function (c) {
			switch (c.getXType()) {
				case 'grid': break;
				case 'combo': 
				// no se muestra correctamente, es necesario hacerle un repaint
					c.disable(); 
					if (c.rendered == true) {
						//c.el.repaint();
					}
				break;
				default: c.disable(); break;
			}
		}
		if (this.disableControlsOnRead == true) {
			for(var i=0;i<this.controls.length;i++) {
				disableControl(this.controls[i]);
			}
		}
	},
	enableControls: function () {
		var enableControl = function (c) {
			c.enable();
		}
		for(var i=0;i<this.controls.length;i++) {
			enableControl(this.controls[i]);
		}
	},
	readData: function () {
		var rd = gds.ext.getControlsValues(this.schema, this, true);
		this.data = Ext.apply(this.data, rd);
	},
	readOriginal: function() {
		var dame = Ext.apply({}, this.data);
		this.readData();
		this.original = this.data;
		this.data = dame;
	},
	setData: function (s, c, d) { //schema, container, data
		// action: seria el tryAction ya que esto se ejecuta antes de afterAction!
		if (this.hasListener('beforesetdata')) {
			this.fireEvent('beforesetdata', this, {action:this.tryAction, data:d});
		}
		gds.ext.setControlsValues(s, c, d);
		this.setLogInfo();
		if (this.hasListener('aftersetdata')) {
			this.fireEvent('aftersetdata', this, {action:this.tryAction});
		}
	},
	getRequestAction: function () {
		switch (this.tryAction)	{
			case 'new': return this.requestCreateAction;
			case 'edit': return this.requestReadAction;
			case 'update': return this.requestUpdateAction;
			case 'delete': return this.requestDeleteAction;
			case 'view': return this.requestReadAction;
		}
	},
	getRequestUrl: function () {
		if (this.requestUrl == null) {
			//console.error('DataContainer: requestUrl is null'); 
		} else {
			return this.requestUrl;
		}
	},
	getByCId: function (cid) {
		var cs = this.find('cid', cid);
		return (cs.length > 0)?cs[0]:null;
	},
	getByName: function (name) {
		var cs = this.find('name', name);
		return (cs.length > 0)?cs[0]:null;
	},
	getById: function (id) {
		var cs = this.find('id', id);
		return (cs.length > 0)?cs[0]:null;
	},
	$ci: function (cid) {
		return this.getByCId(cid);
	},
	$n: function (name) {
		return this.getByName(name);
	},
	$: function (id) {
		return this.getById(id);
	},
	showMsg: function (title, msg) {
		var createBox = function (t, s) {
			return ['<div class="msg">',
			'<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>',
			'<div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc"><h3>', t, '</h3>', s, '</div></div></div>',
			'<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>',
			'</div>'].join('');
		};
		if (typeof gds.ext.msgCt == 'undefined') {
			gds.ext.msgCt = Ext.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
		}
		gds.ext.msgCt.alignTo(document, 't-t');
		var s = String.format.apply(String, Array.prototype.slice.call(arguments, 1));
		var m = Ext.DomHelper.append(gds.ext.msgCt, {html:createBox(title, s)}, true);
		m.slideIn('t').pause(3).ghost("t", {remove:true});
	},
	canModify: function () {
		if (this.tryAction != null) {
			return (this.tryAction == 'new' || this.tryAction == 'edit');	
		}
		return (this.currentAction == 'new' || this.currentAction == 'edit');
	},
	loadingCount: 0,
	beginLoad: function () {
		this.loadingCount++;
	},
	endLoad: function () {
		this.loadingCount--;
	},
	// register a store adding beforeload and load events for check loadingCount
	// the store could not to have storeId in configParams
	registerLoadingStore: function (store, storeId) {
		if (typeof storeId == 'string') {
			if (Ext.StoreMgr.containsKey(storeId)) {
				return;
			}
		} 
		store.storeId = storeId;
		Ext.StoreMgr.register(store);
		store.on('beforeload', function() { this.beginLoad(); }, this);
		store.on('load', function() { this.endLoad(); }, this);
		store.load();
	},
	registerLoadingJsonStore: function (storeConfig) {
		if (typeof storeConfig.storeId == 'string') {
			if (Ext.StoreMgr.containsKey(storeConfig.storeId)) {
				var store = Ext.StoreMgr.lookup(storeConfig.storeId);
				if (store.loaded == false) {
					Ext.StoreMgr.unregister(storeConfig.storeId);
				} else {
					return;
				}
			} 
		} else {
			//return;
		}
		var store = new Ext.data.JsonStore(storeConfig);
		store.loadingStoreAttemptCount = 0;
		store.loading = false;
		store.loaded = false;
		store.registeredInErrorList = false;
		store.on('beforeload', function() {
			store.loading = true; 
			this.beginLoad();
		}, this);
		store.on('load', function(s) {
			store.loading = false; 
			store.loaded = true;
			this.endLoad(); 
		}, this);
		store.on('loadexception', function () {
			store.loading = false;
			if (store.registeredInErrorList == false) {
				this.loadingStoreErrorList.push(store);
				store.registeredInErrorList = true;
			}
			this.endLoad();
			//alert('Error en la carga del Store: '+ store.storeId);
		}, this);
		store.load();
		return store;
	},
	checkReadyOnEvent: 'render',
	checkReadyTimeInterval: 100,
	ready: false,
	loadingStoreAttempts: 2, // numero de intentos si falla la carga del store
	loadingStoreErrorList: [],
	checkReadyRunning: false,
	loadingErrorCount: 0,
	checkReady: function () {
		if (this.checkReadyRunning == true) return;
		this.checkReadyRunning = true;
		if (this.currentLoadingCount != this.loadingCount) {
			var errorCount = 0;
			var errorText = '';
			if (this.loadingStoreErrorList.length > 0) {
				var toRemove = [];
				for (var i in this.loadingStoreErrorList) {
					var store = this.loadingStoreErrorList[i];
					if (store.loading == false) { 
						if (store.loadingStoreAttemptCount < this.loadingStoreAttempts) {
							store.loadingStoreAttemptCount++;
							//console.info('checkReady!.reloading('+store.loadingStoreAttemptCount+'): ' + store.storeId);
							store.reload();
						} else {
							this.loadingErrorCount++;
							toRemove.push(store);
						}
					}
				}
				for (var i in toRemove) {
					this.loadingStoreErrorList.remove(toRemove[i]);
				}
			}
			if (this.loadingErrorCount > 0) {
				errorText = ', errors: ' + this.loadingErrorCount;
			}
			//console.info('checkReady! '+this.loadingCount+errorText);
			this.currentLoadingCount = this.loadingCount;
		}
		this.checkReadyRunning = false;
		if (this.loadingCount <= 0 && this.loadingStoreErrorList.length == 0) {
			this.hideMask();
			this.ready = true;
			this.fireEvent('ready', this);
		} else {
			this.showMask();
			this.checkReady.defer(this.checkReadyTimeInterval, this);
		}
	},
	// registra controles que se van actualizar la propiedad disabled segun el stado de canModfy, disabled = !canModify()
	regUpdatableControlFromState: function (c) {
		this.updatableControlsFromState.push(c);
		if (c.setDisabled) {
			c.setDisabled(!this.canModify()||(this.readOnly===true));
		}
	},
	updateControlsFromState: function () {
		for(var i=0;i<this.updatableControlsFromState.length;i++) {
			var c = this.updatableControlsFromState[i];
			if (c.setDisabled) {
				c.setDisabled(!this.canModify()||(this.readOnly===true));
			}
		}
	},
	actionButtons: null,
	//designed for multiples butons by action!!! :p
	attachActionButton: function(action, btn) {
		if (!Ext.isArray(this.actionButtons["_"+action])) {
			this.actionButtons["_"+action] = new Array();
		} 
		this.actionButtons["_"+action].push(btn);
	},
	// method simplified :)
	regActionBtn: function(action, btn) {
		if (Ext.isArray(action)) {
			for (var i=0;i<action.length;i++) {
				this.attachActionButton(action[i], btn);	
			}
		} else {
			this.attachActionButton(action, btn);
		}
	},
	readOnly: false,
	updateActionButtons: function() {
		var ro = this.readOnly;
		var setEnabled = function(ba, v) {
			if (Ext.isArray(ba)) {
				for(var i=0;i<ba.length;i++) {
					var b = ba[i];
					if (v === true && !ro) { b.enable(); } else { b.disable(); }
				}
			}
		};
		//alert(this.tryAction);
		// antes taba currentAction, pero no funkaba si era 'cancel', ps salia 'none'
		switch (this.tryAction) {
			case 'new': case 'edit'://0 nuevo, 1 modificar, 2 guardar, 3 cancelar, 4 imprimir
				setEnabled(this.actionButtons._new, false);
				setEnabled(this.actionButtons._edit, false);
				setEnabled(this.actionButtons._update, true);
				setEnabled(this.actionButtons._cancel, true);
				setEnabled(this.actionButtons._delete, false);
				setEnabled(this.actionButtons._print, false);
			break;
			case 'update': case 'view': case 'cancel':
				setEnabled(this.actionButtons._new, true);
				setEnabled(this.actionButtons._edit, true);
				setEnabled(this.actionButtons._update, false);
				setEnabled(this.actionButtons._cancel, false);
				setEnabled(this.actionButtons._delete, true);
				setEnabled(this.actionButtons._print, true);
			break;
			default:
				setEnabled(this.actionButtons._new, true);
				setEnabled(this.actionButtons._edit, false);
				setEnabled(this.actionButtons._update, false);
				setEnabled(this.actionButtons._cancel, false);
				setEnabled(this.actionButtons._delete, false);
				setEnabled(this.actionButtons._print, false);
			break;
		}
	},
	createLoadMask: function() {
		this.loadMask = new Ext.LoadMask(this.body, {msg:"Cargando..."});
	},
	showMask: function () {
		if (this.loadMask) {
			this.loadMask.show();
		}
	},
	hideMask: function () {
		if (this.loadMask) {
			this.loadMask.hide();
		}
	},
	getData: function (relation) {
		if (relation) {
			
		} else {
			return Ext.apply({}, this.data);
		}
	},
	getDataValue: function (colname) {
		if (typeof this.data == 'object') {
			return this.data[colname];
		}
		return null;
	},
	setDataValue: function (colname, value) {
		if (typeof this.data == 'object') {
			this.data[colname] = value;
		}
	},
	logInfoLabel: null,
	setLogInfo: function() {
		if (this.logInfoLabel != null) {
			var d = this.getData();
			var data = {
				created: d.created||'00/00/0000 00:00:00',
				createdby: d.createdby||'¿?',
				modified: d.modified||'00/00/0000 00:00:00',
				modifiedby: d.modifiedby||'¿?'
			};
			
			var tpl = new Ext.XTemplate(
				'Creado por <span style="color:#416AA3;">{createdby}</span> el <span style="color:#416AA3;">{created}</span> ',
				'y modificado por <span style="color:#416AA3;">{modifiedby}</span> el <span style="color:#416AA3;">{modified}</span>',
				'&nbsp;&nbsp;'
			);
			tpl.overwrite(this.logInfoLabel.getEl(), data);
		}
	}
};

// DataPanel
gds.ext.DataPanel = function(config) {
	gds.ext.DataPanel.superclass.constructor.call(this, config);
}
Ext.extend(gds.ext.DataPanel, Ext.Panel, Ext.apply(gds.ext.DataContainer, {
	layout: 'border', region: 'center', border: false, bodyStyle: {background: 'none'},
	initComponent: function() {
		this.initialize();
		// call superclass initComponent
		gds.ext.DataPanel.superclass.initComponent.call(this);
	}
}));
Ext.reg('gdsdatapanel', gds.ext.DataPanel);
//DataWindow
gds.ext.DataWindow = function (config) {
	gds.ext.DataWindow.superclass.constructor.call(this, config);
};
Ext.extend(gds.ext.DataWindow, Ext.Window, Ext.apply(gds.ext.DataContainer, {
	modal: true, bodyStyle: {background: 'none'},
	checkReadyOnEvent: 'show',
	initComponent: function() {
		this.initialize();
		// call superclass initComponent
		gds.ext.DataWindow.superclass.initComponent.call(this);
	}
}));
Ext.reg('gdsdatawindow', gds.ext.DataWindow);
//DataForm
gds.ext.DataForm = function (config) {
	gds.ext.DataForm.superclass.constructor.call(this, config);
}
Ext.extend(gds.ext.DataForm, Ext.form.FormPanel, Ext.apply(gds.ext.DataContainer, {
	layout: 'border', region: 'center', border: false, bodyStyle: {background: 'none'},
	checkReadyOnEvent: 'render',
	initComponent: function() {
		this.initialize();
		// call superclass initComponent
		gds.ext.DataForm.superclass.initComponent.call(this);
	}
}));
Ext.reg('gdsdataform', gds.ext.DataForm);
// UTILS functions
//function (Object schema, Ext.Component container, Object data)
gds.ext.setControlsValues = function (s, c, d) { 
	var setControlValue = function (ct, vv) {
		switch (ct.getXType()) {
			case 'label': 
				if (ct.setText) {
					ct.setText(vv); 
				} else {
					ct.el.dom.innerHTML = vv;
				}
			break;
			default: ct.setValue(vv); break;
		}
	}
	if (typeof d == 'undefined' || d == null) return;
	if (Ext.isArray(s.name)) {
		if (!Ext.isArray(d)) return;
		var cs = c.find('relation', s.name[0]);
		if (cs.length > 0) {
			// is required only a control with store :)
			var g = cs[0];
			var data = {};
			data[g.store.root] = d;
			g.store.loadData(data);
			// set relations values
			if (Ext.isArray(s.relations)) {
				for (var i=0;i<s.relations.length;i++) {
					var rel = s.relations[i];
					var name = Ext.isArray(rel.name)?rel.name[0]:rel.name;
					for (var j=0;j<d.length;j++) {
						gds.ext.setControlsValues(rel, c, d[j]);
					}
				}
			}
		}
	} else { // is object
		var cs = c.find('relation', s.name);
		var d2 = d;
		if (Ext.isArray(d)) {
			if (d.length == 0) return;
			d2 = d[0]; //en el caso que data de relaciones es un array!
		}
		for (var i=0;i<cs.length;i++) {
			if (typeof d2[cs[i].name] != 'undefined') {
				setControlValue(cs[i], d2[cs[i].name]);
			}
		}
		// set relations values
		if (Ext.isArray(s.relations)) {
			for (var i=0;i<s.relations.length;i++) {
				var rel = s.relations[i];
				var name = Ext.isArray(rel.name)?rel.name[0]:rel.name;
				gds.ext.setControlsValues(rel, c, d2[name]);
			}
		}
	}
};
// function (Object schema, Ext.Component container, Boolean first)
gds.ext.getControlsValues = function (schema, container, first) {
	/*schema = {
		name: 'fcu', 
		relations: [{
			name: 'fcui', 
			relations: [{
				name: ['det_const'],
				updateExcludeColumns: ['total_calcudado']
			},{
				name: ['det_obra_comp']
			}]
		},{
			name: ['det_ubi']
		}]
	};*/
	// value, relations, container
	var readRelations = function (v, r, c) {
		if (Ext.isArray(r)) {
			for(var i=0;i<r.length;i++) {
				// si el nombre es array o string
				var name = Ext.isArray(r[i].name)?r[i].name[0]:r[i].name;
				v[name] = read(r[i], c, false);
			}
		}
		return v;
	};
	// schema(relation), container, isfirst
	var read = function (s, c, f) {
		if (f===true) {
			var cs = c.find('relation', s.name);
			var v = {};
			for (var i=0;i<cs.length;i++) {
				// los controles label no tiene un metodo o propiedad para obtener el valor/text ...y ke P ke P!
				if (cs[i].getXType() != 'label') {
					v[cs[i].name] = getControlValue(cs[i]);
					if (typeof cs[i].rawname == 'string') { //for display names p.e. combo.getRawValue()
						v[cs[i].rawname] = (typeof cs[i].getRawValue() == 'undefined')?null:cs[i].getRawValue();
					}
				}
			}
			v = readRelations(v, s.relations, c);
			return v;
		} else {
			if (Ext.isArray(s.name)) {
				var v = [];
				var cs = c.find('relation', s.name[0]);
				if (cs.length > 0) { // need control with store
					var fields = cs[0].store.fields;
					var rows = cs[0].store.data;
					for(var i=0;i<rows.length;i++) {
						var r = {};
						for(var j=0;j<fields.length;j++) {
							var field = fields.get(j).name;
							// updateExcludeColumns!
							// colums que se van a excluir, es decir, no se van a enviar al server.
							if (Ext.isArray(s.updateExcludeColumns)) {
								if (s.updateExcludeColumns.indexOf(field) >= 0) continue;
							}
							var cellval = rows.get(i).get(field);
							r[field] = (typeof cellval == 'undefined')?null:cellval;
						}
						r = readRelations(r, s.relations, c);
						v.push(r);
					}
				}
				return v;
			} else {
				var v = {};
				var cs = c.find('relation', s.name);
				for(var i=0;i<cs.length;i++) {
					// los controles label no tiene un metodo o propiedad para obtener el valor/text ...y ke P ke P!
					if (cs[i].getXType() != 'label') {
						v[cs[i].name] = getControlValue(cs[i]);
						if (typeof cs[i].rawname == 'string') { //for display names p.e. combo.getRawValue() != combo.getValue()
							v[cs[i].rawname] = (typeof cs[i].getRawValue() == 'undefined')?null:cs[i].getRawValue();
						}
					}
				}
				v = readRelations(v, s.relations, c);
				return new Array(v); // si no es first, es un data relation = []
			}
		}
	};
	var getControlValue = function (c) {
		//console.info(c.getXType());
		switch (c.getXType()) {
			case 'combo':
				if (c.readRawValue === true) {
					return c.getRawValue();
				} 
				return (c.value == '' || typeof c.value == 'undefined')?null:c.value;
			break;
			// eto no funka bien cuando limpias manualmente el valor que muestra el control, ya que la propiedad 'value' no cambia
			// tons no es posible obtener el valor null de esta manera... averiguar :(
			case 'datefield': return (typeof c.value == 'undefined')?null:c.value; 
			case 'hidden': return (typeof c.value == 'undefined')?null:c.value;
			default: return (typeof c.getValue() == 'undefined')?null:c.getValue();
		}
	};
	// read(Schema, Container, isFirst)
	return read(schema, container, first);
};
// function (Object relation/schema, Ext.Component container)
gds.ext.getControls = function (r, c) {
	var name = Ext.isArray(r.name)?r.name[0]:r.name;
	var cs = c.find('relation', name);
	if (Ext.isArray(r.relations)) {
		for(var i=0;i<r.relations.length;i++) {
			cs = cs.concat(gds.ext.getControls(r.relations[i], c));
		}
	}
	return cs;
}
// Record r, Object v
gds.ext.setRecordValues = function (r, v) {	
	r.beginEdit();
	for(var i in v) {
		if (r.store.fields.containsKey(i)) {
			//console.info('set: '+i+'('+v[i]+')');
			r.set(i, v[i]);
		}
	}
	r.endEdit();
}
