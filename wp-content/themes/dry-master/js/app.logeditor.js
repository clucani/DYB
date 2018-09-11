  /////////////////////////////////////////////////////////////////////////////////////////////
 // Main App
/////////////////////////////////////////////////////////////////////////////////////////////
function logEditor(container) {

    var app = this;

    app.container = container;
    app.debug = false;
    app.logEntries = [];
    app.entriesTable = jQuery('.log-entries', app.container);
    app.entryEditor = new entryEditor(app);
    app.sampleGroup = jQuery('.sample-group', app.container);
    app.groupBag = jQuery('.group-bag', app.container)

    app.init = function() {
        app.initEvents();
        return app;
    }   

    app.initEvents = function() {
        app.sampleGroup.change(function(){
            app.refreshGroupBags();
        })

        app.groupBag.change(app.refreshEntries);

        app.initEntryEvents();
    }

    app.initEntryEvents = function() {
        jQuery('tr', app.entriesTable).each(function(){
            app.logEntries.push(new logEntry(jQuery(this), app));
        })
    }   

    app.refreshGroupBags = function() {
        app.doPost('get-group-bags', { sampleGroupID: app.sampleGroup.val() }, app.loadBags );
    }

    app.loadBags = function(bags) {
        jQuery('option', app.groupBag).remove();
        bagCount = bags.length;

        for(var i = 0; i < bagCount; i++)
            jQuery('<option value="' + bags[i].id + '">' + bags[i].name + '</option>').appendTo(app.groupBag);

        app.refreshEntries();
    }

    app.refreshEntries = function() {
        app.doPost(
            'get-entry-table', 
            { bagID: app.groupBag.val() },
            function(response) {
                app.loadEntryTable(response.markup);
            }
        );
    }

    app.loadEntryTable = function(tableMarkup) {
        app.clearEntries();
        entryTable = jQuery(tableMarkup);
        app.entriesTable.replaceWith(entryTable);
        app.entriesTable = entryTable;
        app.initEntryEvents();
    }

    app.clearEntries = function() {
        var iCount = app.logEntries.length;
        for (var i = 0; i < iCount; i++) {
            app.logEntries[i] = null;
        }
    }

    app.doPost = function(method, params, onSuccess, onFail) {
        
        baseParams = { 'e-plugin': 'emuTheme', 'e-action': 'log-editor', 'method': method }

        for(var attrname in baseParams) { params[attrname] = baseParams[attrname]; }

        jQuery.post( document.URL, params,
            function(result){
                if (result === null || result === undefined || result == 'error') 
                    onFail()
                else {
                    if(result.responseCode == 'error') {
                        onFail();
                        return;
                    }
                  onSuccess(result.responseBody);  
                } 
            }
        );
    }

    app.log = function(message) {
        if(app.debug) console.log(message);
    }

    return app.init();
}

function logEntry(container, app) {

    var model = this;
    model.container = container;
    model.app = app;

    model.entryID = jQuery('.entry-id', model.container).val();
    model.entryEditor = null;
    model.bagID = model.app.groupBag.val();

    model.init = function() {
        model.initEvents();
        return model;
    }

    model.initEvents = function() {
        model.container.hover(function() {
            model.container.addClass("active");
        }, function() {
            model.container.removeClass("active");
        }).click(function(e) {
            app.entryEditor.editEntry(model);
        });
    }
    return model.init();
}

function entryEditor(app) {

    var model = this;
    
    model.ENTRY_TYPE_DRYING = 1;
    model.ENTRY_TYPE_EQUILIBRATING = 2;
    model.ENTRY_TYPE_MEASUREMENT = 3;

    model.logEntry = null;
    model.app = app;
    model.modalMarkup = jQuery('#entryEditorModal');

    model.logDate = jQuery('.log-date', model.modalMarkup);
    model.logTime = jQuery('.log-time', model.modalMarkup);
    model.entryType = jQuery('.entry-type', model.modalMarkup);
    model.duration = jQuery('.duration', model.modalMarkup);
    model.waterPotential = jQuery('.water-potential', model.modalMarkup);
    model.logTimeMoment = null;
    model.durationMoment = null;

    model.formGroupDuration = jQuery('.form-group-duration', model.modalMarkup);
    model.formGroupWP = jQuery('.form-group-wp', model.modalMarkup);

    model.btnSaveChanges = jQuery('.btn-save-changes', model.modalMarkup);
    model.btnDeleteEntry = jQuery('.btn-delete-entry', model.modalMarkup);

    model.init = function() {
        model.initEvents();
        return model;
    }

    model.openModal = function() {
        model.modalMarkup.modal();
    }

    model.editEntry = function(logEntry) {
        model.logEntry = logEntry;
        model.getEntry(function() {
            model.updateEntryLayout();
            model.openModal();
        });
    }

    model.initEvents = function() {
        model.btnSaveChanges.click(function(e) {
            model.saveEntry(function(){
                model.modalMarkup.modal('hide');
                model.app.refreshEntries();
            });
        })
        model.btnDeleteEntry.click(function(e) {
            if(confirm('Are you sure?')) {
                model.deleteEntry(function(){
                    model.modalMarkup.modal('hide');
                    model.app.refreshEntries();
                });
            }
        })
        model.entryType.change(function(){
            model.updateEntryLayout();
        });
    }

    model.updateEntryLayout = function() {
        if(parseInt(model.entryType.val()) == model.ENTRY_TYPE_MEASUREMENT) {
            model.formGroupDuration.hide();
            model.formGroupWP.show();
        }
        else {
            model.formGroupDuration.show();
            model.formGroupWP.hide();
        }
    }

    model.loadEntry = function(entry) {
        model.entryType.val(entry.entryType);
        model.durationMoment = moment({year:2014, month:7, day:27, hour:0, minute:0, second: 0}).add('seconds', parseInt(entry.duration));
        model.duration.val(model.durationMoment.format('HH:mm:ss'));
        model.waterPotential.val(entry.waterPotential);
        model.logTimeMoment = moment(entry.logTime);
        model.logDate.val(model.logTimeMoment.format('YYYY-MM-DD'));
        model.logTime.val(model.logTimeMoment.format('HH:mm:ss'));
    }

    model.getEntry = function(onSuccess) {
        model.app.doPost(
            'get-entry', 
            { entryID: model.logEntry.entryID, bagID: model.logEntry.bagID },
            function(entry) {
                model.loadEntry(entry);
                onSuccess();
            }
        );
    }

    model.deleteEntry = function(onSuccess) {
        model.app.doPost(
            'delete-entry', 
            { entryID: model.logEntry.entryID },
            function(entry) {
                onSuccess();
            }
        );
    }

    model.saveEntry = function(onSuccess) {

        durationStart = moment(model.duration.val(),"HH:mm:ss");
        durationEnd = moment({hour:0, minute:0, second: 0});

        // console.log('duration start : ' + durationStart.format());
        // console.log('duration end : ' + durationEnd.format());
        // console.log('diff : ' + );

        durationSeconds = durationStart.diff(durationEnd, 'seconds');

        model.app.doPost(
            'save-entry', 
            { 
                entryID: model.logEntry.entryID,
                entryType: model.entryType.val(),
                duration: durationSeconds,
                waterPotential: model.waterPotential.val(),
                logDate: model.logDate.val(),
                logTime: model.logTime.val(),
                bagID: model.logEntry.bagID
            },
            function(entry) {
                onSuccess();
            }
        );
    }



    return model.init();
}


  /////////////////////////////////////////////////////////////////////////////////////////////
 // Init
/////////////////////////////////////////////////////////////////////////////////////////////
jQuery(function() {
    if(!jQuery('#logEditor').length) return false;
    window.logEditorInstance = new logEditor(jQuery('#logEditor'));
});