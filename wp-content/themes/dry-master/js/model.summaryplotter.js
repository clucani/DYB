
function summaryPlotter(app) {
    
    var model = this;

    model.plotContainer = jQuery('#dryPlotter');
    model.modal = jQuery('#groupSummaryModal');
    model.dryPlotWrapper = jQuery('.dry-plot-wrapper', model.modal);
    model.tableWrapper = jQuery('.table-wrapper', model.modal);
    model.app = app;
    model.plot = '';
    model.plotData = '';
    model.refreshTimer = null;
    model.resizeTimerID = null;
    model.btnShowTableView = jQuery('.btn-show-table-view', model.modal);
    model.btnShowGraphView = jQuery('.btn-show-graph-view', model.modal); 

    model.init = function() {
        // model.initPlot();
        model.initEvents();
        return model;
    }

    model.show = function() {
        model.modal.modal();
        model.refresh();
    }

    model.initEvents = function() {
        model.btnShowTableView.click(function() {
            // hide the graph 
            model.buildTable(function() {
                model.dryPlotWrapper.addClass("hidden");
                model.tableWrapper.removeClass("hidden");
                model.btnShowTableView.addClass("hidden");
                model.btnShowGraphView.removeClass("hidden");
            })
        })

        model.btnShowGraphView.click(function() {

            model.tableWrapper.addClass("hidden");
            model.dryPlotWrapper.removeClass("hidden");
            model.buildPlot(function() {
                model.btnShowTableView.removeClass("hidden");
                model.btnShowGraphView.addClass("hidden");
            });
        })
        // jQuery(window).resize(function() {
        //     clearTimeout(model.resizeTimerID);
        //     model.resizeTimerID = setTimeout(model.refresh, 500);
        // });
    }

    model.buildTable = function(onComplete) {

        // Get the data for the bag
        model.app.doPost(
            'group-table', 
            { sampleGroupID: model.app.sampleGroupID },
            function(response) {
                model.tableWrapper.html(jQuery(response.table));
                onComplete();
            }
        );
        
    }

    model.buildPlot = function(onComplete) {
        
        // Get the data for the bag
        model.app.doPost(
            'plot-group', 
            { sampleGroupID: model.app.sampleGroupID },
            function(response) {
                model.plotData = google.visualization.arrayToDataTable(response.points);
                var options = {
                  legend: 'none',
                  isStacked: false,
                  chartArea: {width: '70%', height: '70%'},
                  hAxis: {
                    title: 'WP', 
                    minValue: 0
                    }
                };
                model.plot = new google.visualization.BarChart(model.plotContainer.get()[0]);
                model.plot.draw(model.plotData, options);
                onComplete();
            }
        );
    }

    model.refresh = function() {
        model.buildPlot(function(){});
    }

    model.initPlot = function() {
        if(model.app.visAPIloaded)
            model.buildPlot(function(){});
        else
            model.app.loadVisAPI(model.buildPlot(function(){}));
    }

    return model.init();
}
