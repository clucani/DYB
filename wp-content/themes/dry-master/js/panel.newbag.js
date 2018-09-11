function panelNewBag(container, bag) {

    var model = this;

    model.container = container;
    model.bag = bag;
    model.app = bag.app;

    model.btnSetupDrying = jQuery('.btn-setup-drying', model.container);
    model.btnStartEq = jQuery('.btn-start-eq', model.container);
    model.eqTime = jQuery('.eq-time', model.container);
    model.initialWP = jQuery('.initial-wp', model.container);
    model.displayBagID = jQuery('.display-bag-id', model.container);

    model.init = function() {
        model.initEvents();
        return model;
    }

    model.initEvents = function() {
        model.btnSetupDrying.click(function(){
            model.create(false);        
        })
        model.btnStartEq.click(function() {
            model.create(true);        
        })
    }

    model.create = function(eq) {
        model.app.doPost(
            'create-bag', 
            { displayBagID: model.displayBagID.val(), startEq: eq, eqDuration: model.eqTime.val(), sampleGroupID: model.app.sampleGroupID, initialWP: model.initialWP.val() },
            function(response) {
                model.bag.bagID.val(response.bagID);
                model.app.bagList.append(jQuery('<option value="' + response.bagID + '">' + model.displayBagID.val() + '</option>'));
                model.bag.changeStep(response.step, response.panel);
            }
        );
    }
    
    return model.init();
}