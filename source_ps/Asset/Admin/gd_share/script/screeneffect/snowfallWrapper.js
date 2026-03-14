

function SnowFall(selector)
{
    this.selector = selector;

    this.options = {
        flakeCount : 35,
        flakeColor : '#ffffff',
        flakeIndex: 900,
        flakePosition: 'absolute',
        minSize : 10,
        maxSize : 30,
        minSpeed : 3,
        maxSpeed : 3,
        round : false,
        shadow : false,
        collection : false,
        image : false,
        collectionHeight : 40,
        straight : false,
        twinkle : false,
        maxOpacity : 1
    };

    this.snow = function(){
        snowFall.snow(this.selector, 'clear');
        snowFall.snow(this.selector, this.options);
    };

    this.setMinSize = function (size) {
        this.options.minSize = size;
        this.snow();
    };
    this.setMaxSize = function (size) {
        this.options.maxSize = size;
        this.snow();
    };
    this.setMinSpeed = function (speed) {
        this.options.minSpeed = speed;
        this.snow();
    };
    this.setMaxSpeed = function (speed) {
        this.options.maxSpeed = speed;
        this.snow();
    };
    this.setImage = function (image) {
        this.options.image = image;
        this.snow();
    };
    this.setStraight = function () {
        this.options.straight = true;
        this.snow();
    };
    this.setMoving = function () {
        this.options.straight = false;
        this.snow();
    };
    this.setTwinkle = function (bool) {
        if (bool === undefined) {
            this.options.twinkle = !this.options.twinkle;
        } else {
            this.options.twinkle = bool;
        }
        this.snow();
    };
    this.setOpacity = function (opacity) {
        this.options.maxOpacity = opacity;
        this.snow();
    }
    this.setFlakeCount = function (count) {
        this.options.flakeCount = count;
        this.snow();
    };
    this.setDefaults = function (options) {
        this.options = options;
        this.snow();
    };
    this.clear = function () {
        snowFall.snow(this.selector, 'clear');
    };
}
