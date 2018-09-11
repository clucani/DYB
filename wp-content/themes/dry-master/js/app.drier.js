  /////////////////////////////////////////////////////////////////////////////////////////////
 // Main App
/////////////////////////////////////////////////////////////////////////////////////////////
function drier(container) {

    var app = this;

    app.container = container;
    app.debug = false;
    app.bags = [];
    // app.ding = new Audio("/wp-content/themes/dry-master/audio/ding.mp3");
    app.ding = new Audio("/wp-content/themes/dry-master/audio/birds008.wav");

    app.STEP_DRYING_SETUP = 1;
    app.STEP_DRYING = 2;
    app.STEP_DRYING_COMPLETE = 3;
    app.STEP_EQUILIBRATING = 4;
    app.STEP_EQUILIBRATED = 5;
    app.STEP_NEW_BAG = 10;
    app.STEP_BAG_FINISHED = 11;

    app.visAPIloaded = false;

    app.newBagTemplate = jQuery('.new-bag-template');
    app.sampleGroupID = jQuery('#sampleGroup').val();
    app.btnShowGroupSummary = jQuery('.btn-show-group-summary');
    app.bagList = jQuery('.bag-list');
    app.summaryPlotter = null;

    app.init = function() {
        app.initEvents();
        app.summaryPlotter = new summaryPlotter(app);
        return app;
    }   

    app.initEvents = function() {
        jQuery('#btnNewBag').click(function() {
            app.newBag();
        });

        app.btnShowGroupSummary.click(function() { 
            app.summaryPlotter.show();
        })

        app.setupExistingBags();
    }

    app.refreshPlots = function() {
        var iCount = app.bags.length;
        for (var i = 0; i < iCount; i++) {
            app.bags[i].refreshPlot();
        }
        app.dryPlotter.refresh();
    }

    app.loadVisAPI = function(callback) {
      // // Load the Visualization API and the piechart package.
      // google.load('visualization', '1.0', {'packages':['corechart']});

      // // Set a callback to run when the Google Visualization API is loaded.
      // google.setOnLoadCallback(function() {
        app.visAPIloaded = true;
        callback();
      // });
    }

    app.setupExistingBags = function() {
        // Need to setup any existing bags
        jQuery('.bag').each(function() {
            app.bags.push(new bag(jQuery(this), app));
        });
    }

    app.doPost = function(method, params, onSuccess, onFail) {
        
        baseParams = { 'e-plugin': 'emuTheme', 'e-action': 'drier', 'method': method }

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

    app.syncHeights = function() {

        tallestColumnHeight = 0;

        jQuery('.bag').each(function(){

            var columnHeight = jQuery(this).height();

            if(columnHeight > tallestColumnHeight)
                tallestColumnHeight = columnHeight;

        }).css("min-height", tallestColumnHeight);
    }

    // Create a new bag
    app.newBag = function() {

        var newBagPanel = app.newBagTemplate.clone();
        app.newBagTemplate.before(newBagPanel);
        newBagPanel.removeClass('hidden new-bag-template').addClass('bag');
        app.bags.push(new bag(newBagPanel, app));
    }

    app.log = function(message) {
        if(app.debug) console.log(message);
    }

    return app.init();
}


  /////////////////////////////////////////////////////////////////////////////////////////////
 // Init
/////////////////////////////////////////////////////////////////////////////////////////////
jQuery(function() {
    if(!jQuery('#drier').length) return false;
    window.drierApp = new drier(jQuery('#adEditor'));
});

jQuery(function() {
    jQuery('.btn-delete-group').click(function(e) {
        return confirm('Are you sure?');
    })
});