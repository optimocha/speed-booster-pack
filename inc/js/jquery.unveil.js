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

;(function($) {
    $.fn.unveil = function() {

    var $w = $(window),
        images = this,
        loaded,
        inview,
        source;

        this.one(
            'unveil',
            function() {
                var $$ = $(this),
                    source = $$.data('src') || $$.attr('data-src');

                if ( source) {
                    $$
                    .css('opacity', 0)
                    .attr('src', source)
                    .animate(
                        {
                            'opacity': 1
                        },
                        200
                    );
                }
            }
        );

        function unveil() {
            inview = images.filter(
                function() {
                    var $e = $(this),
                    wt = $w.scrollTop(),
                    wb = wt + $w.height(),
                    et = $e.offset().top,
                    eb = et + $e.height();

                    return eb >= wt && et <= wb;
                }
            );

            loaded = inview.trigger('unveil');
            images = images.not(loaded);
        }

        $w.scroll(unveil);
        $w.resize(unveil);

        unveil();

        return this;
    };
})(window.jQuery);


jQuery(document).ready(
    function(){
        jQuery("img.crazy_lazy").css('display', '').unveil();
    }
);