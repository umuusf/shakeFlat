(function ($) {
    $.fn.sfRangeSlide = function (options) {
        const settings = $.extend({
            divClass: '',           // Class name for the created div
            theme: 'auto',          // Can choose from 'auto', 'light', 'dark'            
            min: 0,                 // Minimum value for the Range Slider
            max: 9999999,           // Maximum value for the Range Slider
            step: null,             // Step value for the Range Slider
            thousands: ',',         // Thousands separator
            decimal: '.',           // Decimal separator
            precision: 0,           // Number of decimal places
            prefix: '',             // String to prefix the value
            suffix: '',             // String to suffix the value
        }, options);

        // Function to determine the current theme
        function getTheme() {
            if (settings.theme === 'light') return 'light';
            if (settings.theme === 'dark') return 'dark';
            return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        }

        // Bind events to each input element
        return this.each(function () {
            const $input = $(this);
            
            $input.on('focus click', function () {
                if ($input.data('sfRangeSlide')) {          // If the div already exists, show it
                    $input.data('sfRangeSlide').show();
                    return; 
                }

                const isDark = (getTheme() === 'dark');
                var w = $input.outerWidth();
                if (w < 350) w = 350;

                // create div for Range Slider
                const $div = $('<div>')
                    .addClass("sf-range-slide")
                    .addClass(getTheme())
                    .css({
                        position: 'absolute',
                        top: $input.offset().top + $input.outerHeight(),
                        left: $input.offset().left,
                        width: w,
                        border: '1px solid',
                        padding: '1.5rem 2rem 2rem 2rem',
                        zIndex: 9999,
                        background: isDark ? '#565656' : '#ededed',
                        borderColor: isDark ? '#898989' : '#cecece',
                    });
                if (settings.divClass) $div.addClass(settings.divClass);

                // create Range Slider
                const $slider = $('<div>');

                // jQuery UI Range Slider initialization                
                $slider.slider({
                    range: true,
                    min: settings.min,
                    max: settings.max,
                    step: settings.step ? settings.step : (settings.max - settings.min) / 100,
                    values: [settings.min, settings.max], 
                    width: 'calc(' + w + 'px - 4rem)',
                    slide: function (event, ui) {
                        //console.log(`Range: ${ui.values[0]} - ${ui.values[1]}`);
                        // thousands, decimal, precision, prefix, suffix options
                        const value1 = $.sfNumber(ui.values[0], settings.precision, settings.decimal, settings.thousands);
                        const value2 = $.sfNumber(ui.values[1], settings.precision, settings.decimal, settings.thousands);
                        $input.val(`${settings.prefix}${value1}${settings.suffix} - ${settings.prefix}${value2}${settings.suffix}`);
                        $input.trigger('apply.sfRangeSlide');
                    },
                });

                // Divide the slider into 5 parts and draw a ruler. Display vertical lines on the slider bar.
                const $scale1 = $('<div>').css({ position: 'absolute', top: '0', left: '0%', width: '1px', height: '100%', background: isDark ? '#000' : '#000', zIndex: 1 });
                const $scale2 = $('<div>').css({ position: 'absolute', top: '0', left: '25%', width: '1px', height: '100%', background: isDark ? '#000' : '#000', zIndex: 1 });
                const $scale3 = $('<div>').css({ position: 'absolute', top: '0', left: '50%', width: '1px', height: '100%', background: isDark ? '#000' : '#000', zIndex: 1 });
                const $scale4 = $('<div>').css({ position: 'absolute', top: '0', left: '75%', width: '1px', height: '100%', background: isDark ? '#000' : '#000', zIndex: 1 });
                const $scale5 = $('<div>').css({ position: 'absolute', top: '0', left: '100%', width: '1px', height: '100%', background: isDark ? '#000' : '#000', zIndex: 1 });

                const num1 = $.sfNumberShort(settings.min);
                const num2 = $.sfNumberShort(Math.floor((settings.max - settings.min) / 4));         
                const num3 = $.sfNumberShort(Math.floor((settings.max - settings.min) / 2));
                const num4 = $.sfNumberShort(Math.floor((settings.max - settings.min) * 3 / 4));
                const num5 = $.sfNumberShort(settings.max);

                const $label1 = $('<div>').css({ position: 'absolute', top: '130%', left: '-10%', width: '20%', textAlign: 'center', fontSize: '0.8rem', color: isDark ? '#fff' : '#000', zIndex: 1 }).text(num1);
                const $label2 = $('<div>').css({ position: 'absolute', top: '130%', left: '15%', width: '20%', textAlign: 'center', fontSize: '0.8rem', color: isDark ? '#fff' : '#000', zIndex: 1 }).text(num2);
                const $label3 = $('<div>').css({ position: 'absolute', top: '130%', left: '40%', width: '20%', textAlign: 'center', fontSize: '0.8rem', color: isDark ? '#fff' : '#000', zIndex: 1 }).text(num3);
                const $label4 = $('<div>').css({ position: 'absolute', top: '130%', left: '65%', width: '20%', textAlign: 'center', fontSize: '0.8rem', color: isDark ? '#fff' : '#000', zIndex: 1 }).text(num4);
                const $label5 = $('<div>').css({ position: 'absolute', top: '130%', left: '90%', width: '20%', textAlign: 'center', fontSize: '0.8rem', color: isDark ? '#fff' : '#000', zIndex: 1 }).text(num5);

                $slider.append($scale1).append($label1);
                $slider.append($scale2).append($label2);
                $slider.append($scale3).append($label3);
                $slider.append($scale4).append($label4);
                $slider.append($scale5).append($label5);
                


                // Add slider to div and insert into DOM
                $div.append($slider);


                $('body').append($div);
                $input.data('sfRangeSlide', $div);
               
                // Event function to close the slider
                $input.on('close.sfRangeSlide', function () { $div.hide(); });
                $input.on('blur', function () { if (!$div.is(':hover')) $div.hide(); });
                $(document).on('click', function (e) { if (!$input.is(':focus') && !$div.is(e.target) && $div.has(e.target).length === 0) $div.hide(); });

                // Function to apply the Range Slider
                $input.on('search', function () { $div.hide(); $input.trigger('apply.sfRangeSlide'); });
                $input.on('change keyup', function (e) { 
                    const values = $input.val().split(' - ');
                    if (values.length === 2) {
                        const value1 = parseInt(values[0].replace(/\D/g, ''));
                        const value2 = parseInt(values[1].replace(/\D/g, ''));
                        if (value1 >= settings.min && value1 <= settings.max && value2 >= settings.min && value2 <= settings.max) {
                            $slider.slider('values', [value1, value2]);
                        }
                    } else if (values.length === 1) {
                        const value = parseInt(values[0].replace(/\D/g, ''));
                        if (value >= settings.min && value <= settings.max) {
                            $slider.slider('values', [value, settings.max]);
                        }
                    }

                    $input.trigger('apply.sfRangeSlide'); 

                    e.stopPropagation();
                    return false;
                });

                // window resize event
                $(window).on('resize', function(e) {
                    $div.css({
                        top: $input.offset().top + $input.outerHeight(),
                        left: $input.offset().left,
                    });
                });
            });

        });
    };
}(jQuery));

(function ($) {
    $.sfNumber = function (number, decimals, dec_point, thousands_sep) {
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        const n = !isFinite(+number) ? 0 : +number;
        const prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
        const sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep;
        const dec = (typeof dec_point === 'undefined') ? '.' : dec_point;
        let s = '';

        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? (n).toFixed(prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    };
}(jQuery));

// Convert number to k, m, b, t
(function ($) {
    $.sfNumberShort = function (number) {
        if (number >= 1e12) return (number / 1e12).toFixed((number % 1e12 === 0) ? 0 : 1) + 'T';
        if (number >= 1e9) return (number / 1e9).toFixed((number % 1e9 === 0) ? 0 : 1) + 'G';
        if (number >= 1e6) return (number / 1e6).toFixed((number % 1e6 === 0) ? 0 : 1) + 'M';
        if (number >= 1e3) return (number / 1e3).toFixed((number % 1e3 === 0) ? 0 : 1) + 'K';
        return number;
    };
}(jQuery));
