function bag(container, app) {

    var model = this;
    model.container = container;
    model.app = app;

    model.bagID = jQuery('.bag-id', model.container);
    model.bagDisplayID = jQuery('.display-id', model.container);
    model.currentPanel = jQuery('.panel', model.container);
    model.currentStep = jQuery('.step', model.container);
    model.btnClose = jQuery('button.close', model.container);
    model.btnShowPlot = jQuery('.btn-show-plot', model.container);
    model.plotContainer = jQuery('.bag-plot', model.container);
    model.plotPanel = jQuery('.plot-panel', model.container);
    model.panel = null;
    model.btnFinish = jQuery('.btn-finish', model.container);
    model.plot = null;
    model.plotData = null;


    model.historyPanel = jQuery('.history-panel', model.container);
    model.linkDryingHistory = jQuery('.show-dry-history', model.container);

    model.resizeTimerID = null;

    model.init = function() {
        model.initEvents();
        model.initPanel();
        return model;
    }

    model.initEvents = function() {
    }

    model.buildPlot = function() {
        
        // Get the data for the bag
        model.app.doPost(
            'plot-bag', 
            { bagID: model.bagID.val() },
            function(response) {
               
                var rowData
                var data = new google.visualization.DataTable();
                
                data.addColumn('number', 'Drying Time');
                

                jQuery.each(response.phases, function(i, phase) {
                    data.addColumn('number', 'WP');
                });

                jQuery.each(response.phases, function(phaseI, phase) {
                    
                    // Add the phase points
                    jQuery.each(response.phases[phaseI].points, function(pointI, point) {
                        
                        rowData = [];

                        rowData.push(point[0]); // Drying Time
                        
                        for(var i=0; i < phaseI; i++)
                            rowData.push(null); // Previous columns
                        
                        rowData.push(point[1]);
                        
                        for(var i=phaseI; i < response.phases.length - 1; i++)
                            rowData.push(null); // Other columns
                    
                        data.addRow(rowData);
                    
                    });

                });

                model.plotData = data;
                var options = {
                    chartArea: {width: '90%', height: '90%'},
                    legend: "none",
                    series: {},
                    trendlines: {},
                    axisTitlesPosition: 'in',
                    hAxis: {textPosition: 'in', minValue: 0, viewWindowMode: 'pretty'}, 
                    vAxis: {textPosition: 'in', minValue: 0, viewWindowMode: 'pretty'}
                };

                jQuery.each(response.phases, function(i, phase) {
                    options.series[i] = { color: phase.color };

                    if(phase.regression) {
                        switch(phase.regression.type) {
                            
                            case "linear":
                                options.trendlines[i] = { type: 'linear', visibleInLegend: false }
                                break;
                            
                            case "poly_2":
                                options.trendlines[i] = { type: 'polynomial', degree: 2, visibleInLegend: false }
                                break;
                                
                            case "poly_3":
                                options.trendlines[i] = { type: 'polynomial', degree: 3, visibleInLegend: false }
                                break;
                                
                            case "poly_4":
                                options.trendlines[i] = { type: 'polynomial', degree: 4, visibleInLegend: false }
                                break;
                                
                            case "poly_5":
                                options.trendlines[i] = { type: 'polynomial', degree: 5, visibleInLegend: false }
                                break;
                                
                            break;
                        }
                    }
                     
                });

                model.plot = new google.visualization.ScatterChart(model.plotContainer.get()[0]);
                model.plot.draw(model.plotData, options);
            }
        );

    }

    model.refreshPlot = function() {
        if(!model.plotPanel.hasClass("hidden"))
            model.buildPlot();
    }

    model.showPlot = function() {
        model.plotPanel.removeClass("hidden");

        if(model.app.visAPIloaded)
            model.buildPlot();
        else
            model.app.loadVisAPI(model.buildPlot);
    }

    model.hidePlot = function() {
        model.plotContainer.addClass("hidden");

    }

    model.initPanelCommonEvents = function(panel) {
        model.historyPanel = jQuery('.history-panel', panel.container);
        model.linkDryingHistory = jQuery('.show-dry-history', panel.container);
        model.btnClose = jQuery('button.close', panel.container);
        model.btnFinish = jQuery('.btn-finish', panel.container);
        model.btnShowPlot = jQuery('.btn-show-plot', panel.container);
        model.plotContainer = jQuery('.bag-plot', panel.container);
        model.plotPanel = jQuery('.plot-panel', panel.container);
        model.bagDisplayID = jQuery('.display-id', panel.container);
        model.updateIDContainer = jQuery('.update-id', panel.container);
        
        // model.showPlot();

        jQuery(window).resize(function() {
            clearTimeout(model.resizeTimerID);
            model.resizeTimerID = setTimeout(model.refreshPlot, 500);
        });

        model.bagDisplayID.click(function(e){

            if(jQuery(this).data('showing-form')) {
                model.updateIDContainer.html("").addClass("hidden");
                jQuery(this).data('showing-form', false);
            }
            else {

                var currentID = jQuery(this).html();
                var newDisplayID = jQuery('<input type="text" class="form-control new-display-id" value="' + currentID + '" />');
                var btnUpdateID = jQuery('<input type="button" class="btn-update-id btn btn-default" value="Update" />');
                
                model.updateIDContainer.append([newDisplayID, btnUpdateID]).removeClass("hidden");

                jQuery(this).data('showing-form', true);

                btnUpdateID.click(function(e){
                    model.bagDisplayID.html(newDisplayID.val());
                    model.save(function() {
                        model.updateIDContainer.html("").addClass("hidden");
                        jQuery(this).data('showing-form', false);
                    })
                }); 
            }
        })

        model.btnFinish.click(function(e) {
            e.preventDefault();
            
            if(!confirm('Are you sure?')) return;

            model.app.doPost(
                'finish-bag', 
                { bagID: model.bagID.val() },
                function(response) {
                    model.changeStep(response.step, response.panel);
                }
            );

        });
        model.btnClose.click(function() {
            if(confirm('Are you sure?'))
                model.removeBag();
        });

        model.btnShowPlot.click(function(e) {

            e.preventDefault();
            if(model.plotPanel.hasClass('hidden')) {
                jQuery(this).addClass('active');
                model.showPlot();
            } 
            else {
                jQuery(this).removeClass('active');
                model.plotPanel.addClass('hidden');
            }
        });

        model.linkDryingHistory.click(function(e) {
            e.preventDefault();
            if(model.historyPanel.hasClass('hidden')) {
                jQuery(this).addClass('active');
                model.historyPanel.removeClass('hidden');
            } 
            else {
                jQuery(this).removeClass('active');
                jQuery('.text', model.linkDryingHistory).html('Show Drying History');
                model.historyPanel.addClass('hidden');
            }
        })
    }

    model.initPanel = function() {

        model.panel = null; // clean-up

        switch( parseInt(model.currentStep.val()) ) {
            case model.app.STEP_DRYING_SETUP:
                model.panel = new panelDryingSetup(model.currentPanel, model);
            break;
            case model.app.STEP_DRYING:
                model.panel = new panelDrying(model.currentPanel, model);
            break;
            case model.app.STEP_DRYING_COMPLETE:
                model.app.ding.play();
                model.panel = new panelDryingComplete(model.currentPanel, model);
            break;
            case model.app.STEP_EQUILIBRATING:
                model.panel = new panelEquilibrating(model.currentPanel, model);
            break;
            case model.app.STEP_EQUILIBRATED:
                model.app.ding.play();
                model.panel = new panelEquilibrated(model.currentPanel, model);
            break;
            case model.app.STEP_NEW_BAG:
                model.panel = new panelNewBag(model.currentPanel, model);
            break;
            case model.app.STEP_BAG_FINISHED:
                model.panel = new panelBagFinished(model.currentPanel, model);
            break;
        }
        model.initPanelCommonEvents(model.panel);
        model.app.syncHeights();
    }

    model.removeBag = function() {
        model.app.doPost(
            'delete-bag', 
            { bagID: model.bagID.val() },
            function(response) {
                model.container.fadeOut('fast', function() {
                    jQuery(this).remove();
                    model = null;
                })
            }
        );
    }

    model.save = function(callback) {
        model.app.doPost(
            'save-bag', 
            { 
                bagID: model.bagID.val(), 
                displayBagID: model.bagDisplayID.html() 
            },
            function(response) {
                callback();
            }
        );
    }

    model.changeStep = function(newStep, panelMarkup) {
        
        panelMarkup = jQuery(panelMarkup)
        
        // Replace the current panel
        model.currentPanel.replaceWith(panelMarkup);
        model.currentPanel = panelMarkup;
        
        // Set the new step
        model.currentStep.val(newStep);
        
        // Initiliase the panel
        model.initPanel();
    }

    return model.init();
}