function panelDrying(container, bag) {
    
    var model = this;

    model.container = container;
    model.bag = bag;
    model.app = bag.app

    model.btnPlusOneHour = jQuery('.btn-plus-one-hour', model.container);
    model.btnPlusThirtyMin = jQuery('.btn-plus-thirty-min', model.container);
    model.btnPlusTenMin = jQuery('.btn-plus-ten-min', model.container);

    model.btnMinusOneHour = jQuery('.btn-minus-one-hour', model.container);
    model.btnMinusThirtyMin = jQuery('.btn-minus-thirty-min', model.container);
    model.btnMinusTenMin = jQuery('.btn-minus-ten-min', model.container);
    
    model.stepStartTime = jQuery('.step-start-time', model.container).val();
    model.stepDuration = jQuery('.step-duration', model.container).val();
    model.totalDryingTimeContainer = jQuery('.total-drying-time-container', model.container);
    model.stepEndTime = null;

    model.btnStopDrying = jQuery('.btn-stop-drying', model.container);
    model.clock = jQuery('.clock', model.container);

    model.panelTime = moment({hour:0, minute:0, second: 0});
    model.midnight = moment({hour:0, minute:0, second: 0});

    model.wpEstimateContainer = jQuery('.wp-estimate', model.container);

    model.slope = jQuery('.slope', model.container).val();
    model.intercept = jQuery('.intercept', model.container).val();
    model.totalDryingTime = jQuery('.total-drying-time', model.container).val();

    model.regression = JSON.parse(jQuery('.regression', model.container).val());

    model.timer = null;
    model.refreshTimer = null;

    model.init = function() {
        model.initClock();
        model.initEvents();
        return model;
    }

    model.initClock = function() {
        
        // Get the start and end times
        startTime = model.stepStartTime;

        model.stepStartTime = moment(startTime);
        model.stepEndTime = moment(startTime).add('seconds', parseInt(model.stepDuration));

        model.refreshClock();
        
        model.timer = setInterval(function() {
            model.refreshClock();
        }, 1000);
    }

    model.initEvents = function() {
        model.btnPlusOneHour.click(function() {
            model.addTime('hour', '1');
        });
        model.btnPlusThirtyMin.click(function() {
            model.addTime('minute', '30');
        });
        model.btnPlusTenMin.click(function() {
            model.addTime('minute', '10');
        });
        model.btnMinusOneHour.click(function() {
            model.subTime('hour', '1');
        });
        model.btnMinusThirtyMin.click(function() {
            model.subTime('minute', '30');
        });
        model.btnMinusTenMin.click(function() {
            model.subTime('minute', '10');
        });
        model.btnStopDrying.click(function() {
            model.stopDrying();
        });
        // model.refreshTimer = setInterval(function() {
        //     model.bag.refreshPlot();
        // }, 60000);
    }

    model.addTime = function(period, value) {
        model.stepEndTime.add(period, value);
        model.updateTime();
        model.refreshClock();
    }

    model.subTime = function(period, value) {
        model.stepEndTime.subtract(period, value);
        model.updateTime();
        model.refreshClock();
    }

    model.updateTime = function() {
        model.app.doPost(
            'update-step-time', 
            { bagID: model.bag.bagID.val(), duration: model.stepEndTime.diff(model.stepStartTime, 'seconds')},
            function(response) {
            }
        );
    }

    model.refreshClock = function() {
        if(moment().isAfter(model.stepEndTime)) {
            model.stopDrying();
            return;        
        }   
        model.panelTime = moment({hour:0, minute:0, second: 0});
        model.panelTime.add('seconds', model.dryingTime());
        model.clock.html(model.panelTime.format('HH:mm:ss'));
        model.totalDryingTimeContainer.html(
            moment({hour:0, minute:0, second: 0}).add('seconds', model.stepTotalDryingTime()).format('HH:mm:ss')
        );
        model.refreshWPEstimate();
    }   

    model.refreshWPEstimate = function() {

        wpEstimate = '-';

        if(model.regression.best) {

            // How long is the current step going to run for?
            stepTime = model.stepEndTime.diff(model.stepStartTime, 'seconds', true);

            // Final drying time is the total drying time plus the total duration the step is going to run for
            finalTotalDryingTime = parseInt(model.totalDryingTime) + stepTime;
            
            // Current drying time is the total drying time (previous to the current step) plus 
            // how long the current step has been running for
            currentTotalDryingTime = parseInt(model.totalDryingTime) + parseInt(model.stepTotalDryingTime());

            // We now have x (dryingGrandTotal) and now we can calculate y using our regression model
            switch(model.regression.best.type)
            {
                case "linear":
                    
                    wpFinalEstimate = model.regression.best.slope * finalTotalDryingTime + model.regression.best.intercept;
                    wpCurrentEstimate =  model.regression.best.slope * currentTotalDryingTime + model.regression.best.intercept;
                    break;

                default: // polys
                    
                    wpFinalEstimate = model.regression.best.intercept;

                    for(var i = 0; i < model.regression.best.slope.length; i++) {
                        slope = model.regression.best.slope[i];
                        if(slope !== 0) {
                            wpFinalEstimate = wpFinalEstimate + slope * Math.pow(finalTotalDryingTime, i + 1);
                        }
                    }
                    
                    wpCurrentEstimate = model.regression.best.intercept;

                    for(var i = 0; i < model.regression.best.slope.length; i++) {
                        slope = model.regression.best.slope[i];
                        if(slope !== 0) {
                            wpCurrentEstimate = wpCurrentEstimate + slope * Math.pow(currentTotalDryingTime, i + 1);
                        }
                    }
            }

            wpFinalEstimate = Math.round(wpFinalEstimate);
            wpCurrentEstimate = Math.round(wpCurrentEstimate);

        }

        if(typeof wpCurrentEstimate !== 'undefined' )
            model.wpEstimateContainer.html('<small>C&Psi; ' + wpCurrentEstimate + ', F&Psi; ' + wpFinalEstimate + '</small>');
    }



    model.dryingTime = function() {
        return model.stepEndTime.diff(moment(), 'seconds');
    }

    model.stepTotalDryingTime = function() {
        return moment().diff(model.stepStartTime, 'seconds');
    }

    model.stopDrying = function() {
        
        // Clear timers
        clearInterval(model.timer);
        clearInterval(model.refreshTimer);

        model.app.doPost(
            'stop-drying', 
            { bagID: model.bag.bagID.val() },
            function(response) {
                model.bag.changeStep(response.step, response.panel);
            }
        );
    }

    return model.init();
}