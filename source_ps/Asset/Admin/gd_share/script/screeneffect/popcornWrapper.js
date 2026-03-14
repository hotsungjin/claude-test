

function PopCornWrapper(selectors) {

    this.selectors = selectors;
    this.effect = null;

    this.options = {
        image : '',
        maxSize : 30,
        minSize : 20,
        speed : 20,
        count : 3,
        position : 'absolute'
    };
    this.effect = new PopCorn(this.selectors, this.options);

    this.setImage = function(img){
        this.options.image = img;
    };
    this.setSpeed = function (speed) {
        this.options.speed = speed;
    };
    this.setMaxSize = function (size) {
        this.options.maxSize = size;
    };
    this.setMinSize = function (size) {
        this.options.minSize = size;
    };
    this.setFlakeCount = function (count) {
        this.options.count = count;
    };

    this.clearPopcorn = function () {
        clearInterval(this.effect.work);
    };

}
