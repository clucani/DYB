function panelEquilibrated(container, bag) {
    
    var model = this;

    model.container = container;
    model.bag = bag;
    model.app = bag.app
    model.btnClose = jQuery('button.close', model.container);
    model.btnSaveWP = jQuery('button.btn-save-wp', model.container);
    model.extraTimeSpan = jQuery('.extra-time', model.container);
    model.wp = jQuery('.wp', model.container);
    model.timer = null;
    model.stepStartTime = jQuery('.step-start-time', model.container);
    model.stepEndTime = null;
    model.stepDuration = jQuery('.step-duration', model.container);
    
    model.init = function() {
        model.initClock();
        model.initEvents();
        return model;
    }

    model.initClock = function() {
        //model.stepEndTime = moment(model.stepStartTime.val()).add('second', model.stepDuration.val());
        model.refreshClock();
        model.timer = setInterval(function() {
            model.refreshClock();
        }, 1000);
    }

    model.initEvents = function() {
        model.btnSaveWP.click(function() {
            model.saveWP();
        });
    }

    model.saveWP = function() {
        
        // Clear timers
        clearInterval(model.timer);

        model.app.doPost(
            'save-wp', 
            { wp: model.wp.val(), bagID: model.bag.bagID.val() },
            function(response) {
                model.bag.changeStep(response.step, response.panel);
            }
        );
    }

    model.extraEqTime = function() {
        return moment().diff(moment(model.stepStartTime.val()), 'seconds');
    }

    model.refreshClock = function() {
        model.panelTime = moment({hour:0, minute:0, second: 0});
        model.panelTime.add('seconds', model.extraEqTime());
        model.extraTimeSpan.html(model.panelTime.format('HH:mm:ss'));
    }   

    return model.init();
}
