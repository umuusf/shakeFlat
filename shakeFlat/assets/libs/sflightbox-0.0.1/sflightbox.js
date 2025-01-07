/*
usage : $("img").sfLightBox(options);
*/

(function ($) {
    $.fn.sfLightBox = function (options) {
        let settings = $.extend({
            // These are the defaults.
            overlayColor: 'rgba(0, 0, 0, 0.8)',
            captionColor: '#fff',
            captionFontSize: '14px',
            transitionDuration: '0.3s'
        }, options);

        let $image = null;
        let $caption = null;
        let $overlay = null;
        let currentIndex = 0;
        window.sflightbox_images = window.sflightbox_images || [];

        if ($('#sfLightBoxOverlay').length) {
            $overlay = $('#sfLightBoxOverlay');
            $image = $('#sfLightBoxImage');
            $caption = $('#sfLightBoxCaption');
        } else {
            $overlay = $('<div id="sfLightBoxOverlay"></div>').css({
                'background-color': settings.overlayColor,
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'width': '100%',
                'height': '100%',
                'display': 'none',
                'justify-content': 'center',
                'align-items': 'center',
                'z-index': '9999'
            });

            let $imageContainer = $('<div id="sfLightBoxImageContainer"></div>').css({
                'display': 'flex',
                'flex-direction': 'column',
                'justify-content': 'center',
                'align-items': 'center',
                'width': '100vw',
                'height': '100vh'
            });

            $image = $('<img id="sfLightBoxImage">').css({
                'max-width': '100%',
                'max-height': '100%',
                'transition': settings.transitionDuration
            });

            $caption = $('<div id="sfLightBoxCaption"></div>').css({
                'color': settings.captionColor,
                'font-size': settings.captionFontSize,
                'margin-top': '10px',
                'text-align': 'center'
            });

            let $closeButton = $('<div id="sfLightBoxClose">&times;</div>').css({
                'position': 'absolute',
                'top': '20px',
                'right': '20px',
                'color': '#fff',
                'font-size': '30px',
                'cursor': 'pointer'
            });

            let $prevButton = $('<div id="sfLightBoxPrev">&lt;</div>').css({
                'position': 'absolute',
                'left': '0',
                'top': '50%',
                'width': '80px',
                'height': '100px',
                'color': '#fff',
                'font-size': '30px',
                'cursor': 'pointer',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center',
                'transform': 'translateY(-50%)'
            });

            let $nextButton = $('<div id="sfLightBoxNext">&gt;</div>').css({
                'position': 'absolute',
                'right': '0',
                'top': '50%',
                'width': '80px',
                'height': '100px',
                'color': '#fff',
                'font-size': '30px',
                'cursor': 'pointer',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center',
                'transform': 'translateY(-50%)'
            });

            $imageContainer.append($image).append($caption);
            $overlay.append($imageContainer).append($closeButton).append($prevButton).append($nextButton);
            $('body').append($overlay);

            $closeButton.on('click', function () {
                $overlay.fadeOut();
            });

            $prevButton.on('click', function () {
                var group = $image.data('group');
                currentIndex = (currentIndex > 0) ? currentIndex - 1 : window.sflightbox_images[group].length - 1;
                showImage(group, currentIndex);
            });

            $nextButton.on('click', function () {
                var group = $image.data('group');
                currentIndex = (currentIndex < window.sflightbox_images[group].length - 1) ? currentIndex + 1 : 0;
                showImage(group, currentIndex);
            });

            $overlay.on('click', function (e) {
                if (e.target.id === 'sfLightBoxOverlay' || e.target.id === 'sfLightBoxImageContainer') {
                    $overlay.fadeOut();
                }
            });
        }

        this.each(function () {
            let $this = $(this);
            let group = $this.data('sflightbox');
            let caption = $this.data('caption') || '';
            let bigImage = $this.data('big') || $this.attr('src');

            if (group === undefined) return;
            if (window.sflightbox_images[group] === undefined) window.sflightbox_images[group] = [];
            if (window.sflightbox_images[group].find(img => img.bigImage === bigImage)) return;
            window.sflightbox_images[group].push({
                caption: caption,
                bigImage: bigImage
            });

            $this.on('click', function (e) {
                e.preventDefault();
                currentIndex = window.sflightbox_images[group].findIndex(img => img.bigImage === bigImage);
                showImage(group, currentIndex);
                $overlay.fadeIn();
            });

            $this.on('remove', function () {
                var index = window.sflightbox_images[group].findIndex(img => img.bigImage === bigImage);
                if (index !== -1) {
                    window.sflightbox_images[group].splice(index, 1);
                    if (window.sflightbox_images[group].length === 0) {
                        delete window.sflightbox_images[group];
                    }
                }
            });
        });

        function showImage(group, index) {
            let image = window.sflightbox_images[group][index];
            $image.attr('src', image.bigImage).data('group', group);
            $caption.text(image.caption);
        }

        return this;
    };
}(jQuery));
