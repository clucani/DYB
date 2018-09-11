function panelDryingSetup(container, bag) {
    
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

    model.wpEstimateContainer = jQuery('.wp-estimate', model.container);

    model.slope = jQuery('.slope', model.container).val();
    model.intercept = jQuery('.intercept', model.container).val();
    model.totalDryingTime = jQuery('.total-drying-time', model.container).val();

    model.regression = JSON.parse(jQuery('.regression', model.container).val());

    model.btnStartDrying = jQuery('.btn-start-drying', model.container);
    model.clock = jQuery('.clock', model.container);

    model.panelTime = moment({hour:0, minute:0, second: 0});
    model.midnight = moment({hour:0, minute:0, second: 0});

    model.init = function() {
        model.initEvents();
        // model.refreshClock();
        return model;
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
        model.btnStartDrying.click(function() {
            model.startDrying();
        });
    }

    model.addTime = function(period, value) {
        model.panelTime.add(period, value);
        model.refreshClock();
    }

    model.subTime = function(period, value) {
        model.panelTime.subtract(period, value);
        model.refreshClock();
    }

    model.refreshClock = function() {
        model.clock.html(model.panelTime.format('HH:mm:ss'));
        model.refreshWPEstimate();
    }

    model.refreshWPEstimate = function() {

        wpEstimate = '-';

        if(model.regression.best) {

            // How long is the current step going to run for?
            stepTime = model.panelTime.diff(model.midnight, 'seconds', true);

            // Add to the total amount of drying
            dryingGrandTotal = parseInt(model.totalDryingTime) + stepTime;

            // We now have x (dryingGrandTotal) and now we can calculate y using our regression model
            switch(model.regression.best.type)
            {
                case "linear":
                    
                    wpEstimate = model.regression.best.slope * dryingGrandTotal + model.regression.best.intercept;
                    break;

                default: // polys
                    
                    wpEstimate = model.regression.best.intercept;

                    for(var i = 0; i < model.regression.best.slope.length; i++) {
                        slope = model.regression.best.slope[i];
                        if(slope !== 0) {
                            wpEstimate = wpEstimate + slope * Math.pow(dryingGrandTotal, i + 1);
                        }
                    }
            }

            wpEstimate = Math.round(wpEstimate);

        }

        model.wpEstimateContainer.html('<small>Est. &Psi; ' + wpEstimate + '</small>');

    }

    model.startDrying = function() {
        
        intHours = parseInt(model.panelTime.format('H'));
        intMinutes = parseInt(model.panelTime.format('m'));
        durationSeconds = ((intHours * 60) + intMinutes) * 60;

        model.app.doPost(
            'start-drying', 
            { duration: durationSeconds, bagID: model.bag.bagID.val() },
            function(response) {
                model.bag.changeStep(response.step, response.panel);
            }
        );
    }

    return model.init();
}