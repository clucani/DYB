function panelEquilibrating(container, bag) {
    
    var model = this;

    model.container = container;
    model.bag = bag;
    model.app = bag.app
    model.btnStartEq = jQuery('button.btn-start-eq', model.container);
    model.btnStopEq = jQuery('.btn-stop-eq', model.container);
    model.eqTime = jQuery('.eq-time', model.container);
    model.timer = null;
    model.stepStartTime = jQuery('.step-start-time', model.container);
    model.stepEndTime = null;
    model.stepDuration = jQuery('.step-duration', model.container);
    model.panelTime = null;
    model.totalEqTimeSpan = jQuery('.total-eq-time', model.container);
    model.clock = jQuery('.clock', model.container);
    
    model.init = function() {
        model.initClock();
        model.initEvents();
        return model;
    }

    model.initClock = function() {
        model.stepEndTime = moment(model.stepStartTime.val()).add('second', model.stepDuration.val());
        model.refreshClock();
        model.timer = setInterval(function() {
            model.refreshClock();
        }, 1000);
    }


    model.initEvents = function() {
        model.btnStartEq.click(function() {
            model.startEq();
        });
        model.btnStopEq.click(function() {
            model.stopEq();
        });
    }

    model.stopEq = function() {

        // Clear timers
        clearInterval(model.timer);

        model.app.doPost(
            'stop-eq', 
            { bagID: model.bag.bagID.val() },
            function(response) {
                model.bag.changeStep(response.step, response.panel);
            }
        );
    }

    model.eqTime = function() {
        return model.stepEndTime.diff(moment(), 'seconds');
    }

    model.totalEqTime = function() {
        return moment().diff(moment(model.stepStartTime.val()), 'seconds');
    }

    model.refreshClock = function() {
        if(moment().isAfter(model.stepEndTime)) {
            model.stopEq();
            return;        
        }   
        model.panelTime = moment({hour:0, minute:0, second: 0});
        model.panelTime.add('seconds', model.eqTime());
        model.clock.html(model.panelTime.format('HH:mm:ss'));
        model.totalEqTimeSpan.html(
            moment({hour:0, minute:0, second: 0}).add('seconds', model.totalEqTime()).format('HH:mm:ss')
        );
    }   

    return model.init();
}