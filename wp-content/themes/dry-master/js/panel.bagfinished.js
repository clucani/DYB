function panelBagFinished(container, bag) {
    
    var model = this;

    model.container = container;
    model.bag = bag;
    model.app = bag.app
    
    model.init = function() {

        model.bag.showPlot();

        model.container.fadeTo("slow", 0.8, function() {
        
        });


        return model;
    }

    return model.init();
}