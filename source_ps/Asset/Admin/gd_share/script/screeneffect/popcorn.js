/*
 * jQuery.RandomBlinker
 * http://www.marcellobrivio.com/jquery.randomblinker
 *
 * Created by Marcello Brivio
 * http://www.marcellobrivio.com
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

function PopCorn(selector, options) {
        var size;
        var  x;
        var  y;
        var elemWidth;
        var elemHeight;
        var popcorn = null;

        var settings ={
                image : 'http://pngimg.com/uploads/snowflakes/snowflakes_PNG7581.png',
                maxSize : 30,
                minSize : 20,
                speed : 20,
                count : 3,
                position : 'absolute'
        };
        function init(selector) {
            extend(settings, options);
            elemWidth = String(selector.css('width')).split('px')[0];
            elemHeight = String(selector.css('height')).split('px')[0];
            settings.speed = settings.speed * 100;
        }
        function extend(obj, extObj){
            for(var i in extObj){
                if(obj.hasOwnProperty(i)){
                    obj[i] = extObj[i];
                }
            }

        }
        function draw(selector) {
            $('.popcorn_group').remove();
            for (var i = 0; i < settings.count; i++) {
                init(selector);
                reset();
                popcorn = new Image();
                popcorn.src = settings.image;
                setStyle(popcorn, {
                    'width': size,
                    'height': size,
                    'position' : settings.position,
                    'left' : x+'px',
                    'top' : y+'px',
                    'z-index' : 900
                });
                popcorn.className = "popcorn_group";
                selector.append(popcorn);
            }
        }
        function reset(){
            x = random(size, elemWidth-size);
            y = random(size, elemHeight-size);
            size = random(settings.minSize, settings.maxSize);
            size = (size ==0 ? settings.minSize : size);
        }
        function setStyle(element, props) {
            for (var property in props) {
                element.style[property] = props[property] + ((property == 'width' || property == 'height') ? 'px' : '');
            }
        }
        function random(min, max) {
            return Math.floor(Math.random() * (max- min) + min);
        }
         var popcorn_group;
        function activate (){
                draw(selector);
                popcorn_group = $('.popcorn_group');
                reset();
                popcorn_group.fadeIn(Math.round(settings.speed/3)).fadeOut(Math.round(settings.speed/3));
        }
        this.work = null;
        this.work = setInterval(activate, settings.speed*100);


}

