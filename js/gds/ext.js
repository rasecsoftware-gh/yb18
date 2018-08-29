// Ext extensions!
// Ext.form.Label extension!
Ext.form.Label.prototype.setText = function (s) {
	this.el.dom.innerHTML = s;
};
// Ext.data.Record extension!
Ext.data.Record.prototype.setValues = function (v) {
	this.beginEdit();
	for(var i in v) {
		if (this.store.fields.containsKey(i)) {
			//console.info('Ext.data.Record.setValues: '+i+'('+v[i]+')');
			this.set(i, v[i]);
		}
	}
	this.endEdit();
};
// Ext.grid.GridPanel extension!
Ext.grid.GridPanel.prototype.getSelections = function () {
	return this.getSelectionModel().getSelections();
};

// validation
Ext.form.DateField.prototype.initComponent = function(){
    Ext.form.DateField.superclass.initComponent.call(this);
    this.enableKeyEvents = true;
    this.addEvents(
        /**
         * @event select
         * Fires when a date is selected via the date picker.
         * @param {Ext.form.DateField} this
         * @param {Date} date The date that was selected
         */
        'select'
    );

    if(Ext.isString(this.minValue)){
        this.minValue = this.parseDate(this.minValue);
    }
    if(Ext.isString(this.maxValue)){
        this.maxValue = this.parseDate(this.maxValue);
    }
    this.disabledDatesRE = null;
    this.initDisabledDays();
    this.on('keyup', function (s, e) {
    	this._dateFormat();
    });
    this._timerId = null;
    this._oldLength = 0;
    this._dateFormat = function () {
    	var d = this.getRawValue();
    	clearInterval(this._timerId);
    	d = d.substr(0,10);
    	function D1(s) {
    		if (s == '/') return 'next';
    		else if (isNaN(s) == true || (s>3)) return 'exit';
    		else return s;
    	};
    	function D2(s, l, cl, ol, d1) {
    		if (l < ol && cl == l) return '-1';
			else if (isNaN(s) == true || (d1>2 && s>1) || (d1<1 && s<1)) return 'exit';
	    	else if (cl < l) return s;
	    	else return s + '/';
    	};
    	function Slap(s, l, cl, ol) {
    		if (s == '/') return s;
    		else if (isNaN(s) == true) return 'exit';
    		else return '/' + s;
    	};
    	function M1(s) {
    		if (s == '/') return 'next';
    		else if (isNaN(s) == true || (s>1)) return 'exit';
    		else return s;
    	};
    	function M2(s, l, cl, ol, m1) {
    		if (l < ol && cl == l) return '-1';
			else if (isNaN(s) == true || (m1>0 && s>2) || (m1<1 && s<1)) return 'exit';
			else if (cl < l) return s;
	    	else return s + '/';
    	};
    	function Y(s) {
    		if (s == '/') return 'next';
    		else if (isNaN(s) == true) return 'exit';
    		else return s;
    	};
    	var tpl = new Array(D1,D2,Slap,M1,M2,Slap,Y,Y,Y,Y);
    	var s, r = [], j = -1; ra=0;
    	for (var i=0; i< d.length; i++) {
    		do {
    			j++;
    			s = tpl[i](d[j], d.length, i+1, this._oldLength, ra);
    		} while (s == 'next');
    		if (s == '-1') r[i] = '';
    		else if (s != 'exit') r.push(s);
    		ra = r[i];
    	}
    	d = r.join('');
		this._oldLength = d.length;
		this.setRawValue(d);
/*    	if (l>=10){
    		d=d.substr(0,10);
    		dia=d.substr(0,2);
    		mes=d.substr(3,2);
    		ano=d.substr(6,4);
    		// Año no viciesto y es febrero y el dia es mayor a 28
    		if( (ano%4 != 0) && (mes ==02) && (dia > 28) ){ d=d.substr(0,2)+"/"; }
    	}
    	return (d);*/
    };
};
//Add the additional 'advanced' VTypes
Ext.apply(Ext.form.VTypes, {
	fillDate: function (val, field) {
	// aqui se tiene que  controlar el ingreso de la fecha
	// rellenando los '/' por cada elemento de la fecha...
	// nada mas.. jeje
	// luego tambien hay que controlar el ingreso de la hora
	// colocando cada caracter de separacionde hora i.e.: 14:25:35 ok
	// todo esto en el control datefield.. jojo
	},
    daterange : function(val, field) {
        var date = field.parseDate(val);

        if(!date){
            return;
        }
        if (field.startDateField && (!this.dateRangeMax || (date.getTime() != this.dateRangeMax.getTime()))) {
            var start = Ext.getCmp(field.startDateField);
            start.setMaxValue(date);
            start.validate();
            this.dateRangeMax = date;
        } 
        else if (field.endDateField && (!this.dateRangeMin || (date.getTime() != this.dateRangeMin.getTime()))) {
            var end = Ext.getCmp(field.endDateField);
            end.setMinValue(date);
            end.validate();
            this.dateRangeMin = date;
        }
        /*
         * Always return true since we're only using this vtype to set the
         * min/max allowed values (these are tested for after the vtype test)
         */
        return true;
    },

    password : function(val, field) {
        if (field.initialPassField) {
            var pwd = Ext.getCmp(field.initialPassField);
            return (val == pwd.getValue());
        }
        return true;
    },

    passwordText : 'Passwords do not match'
});
// Ext.Panel transparent
Ext.Panel.prototype.bodyStyle = {background: 'none'}; 

// overrides
Ext.form.ComboBox.prototype.typeAhead = true;
Ext.form.ComboBox.prototype.triggerAction = 'all';
Ext.form.ComboBox.prototype.editable = false;
Ext.form.ComboBox.prototype.emptyText = '- seleccione -';
Ext.form.ComboBox.prototype.forceSelection = true;
Ext.form.ComboBox.prototype.allowBlank = false;
Ext.form.ComboBox.prototype.mode = 'local';

Ext.form.DateField.prototype.enableKeyEvents = true;

Ext.form.TimeField.prototype.enableKeyEvents = true;
Ext.form.TimeField.prototype.format = 'H:i:s'; // 24 horas format :)

Ext.form.NumberField.prototype.style = {textAlign: 'right' };

// templates
/*gds.ext.ComboBoxTemplate = {
	typeAhead: true, triggerAction: 'all', editable: false, emptyText: '- seleccione -', 
	forceSelection: true, allowBlank: false, mode: 'local'
};
gds.ext.TriggerFieldSearchTemplate = {
	triggerClass: 'x-form-search-trigger', readOnly: true
};*/