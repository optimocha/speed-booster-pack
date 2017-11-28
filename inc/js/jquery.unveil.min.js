/**
 * jQuery Unveil
 * A very lightweight jQuery plugin to lazy load images
 * http://luis-almeida.github.com/unveil
 *
 * Modified by Sergej Müller
 * http://wpcoder.de
 *
 * Licensed under the MIT license.
 */

(function(c){c.fn.unveil=function(){function b(){e=a.filter(function(){var f=c(this),g=d.scrollTop(),b=g+d.height(),a=f.offset().top;return a+f.height()>=g&&a<=b});h=e.trigger("unveil");a=a.not(h)}var d=c(window),a=this,h,e;this.one("unveil",function(){var a=c(this),b=a.data("src")||a.attr("data-src");b&&a.css("opacity",0).attr("src",b).animate({opacity:1},200)});d.scroll(b);d.resize(b);b();return this}})(window.jQuery);jQuery(document).ready(function(){jQuery("img.crazy_lazy").css("display","").unveil()});