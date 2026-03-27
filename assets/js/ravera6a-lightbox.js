document.addEventListener('DOMContentLoaded', function () {
    const contentRoot = document.querySelector('.wp-block-post-content');

    if (!contentRoot) {
        return;
    }

    const lightbox = document.createElement('div');
    lightbox.className = 'ravera6a-lightbox';
    lightbox.setAttribute('aria-hidden', 'true');

    lightbox.innerHTML = `
        <div class="ravera6a-lightbox__backdrop"></div>
        <div class="ravera6a-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Prévisualisation de l’image">
            <button type="button" class="ravera6a-lightbox__close" aria-label="Fermer la prévisualisation">×</button>
            <img class="ravera6a-lightbox__image" src="" alt="">
        </div>
    `;

    document.body.appendChild(lightbox);

    const backdrop = lightbox.querySelector('.ravera6a-lightbox__backdrop');
    const closeButton = lightbox.querySelector('.ravera6a-lightbox__close');
    const previewImage = lightbox.querySelector('.ravera6a-lightbox__image');

    function getBestImageSrc(img) {
        const link = img.closest('a');

        if (link && link.href && /\.(jpg|jpeg|png|gif|webp|avif|svg)(\?.*)?$/i.test(link.href)) {
            return link.href;
        }

        if (img.dataset && img.dataset.fullUrl) {
            return img.dataset.fullUrl;
        }

        if (img.currentSrc) {
            return img.currentSrc;
        }

        return img.src;
    }

    function openLightbox(img) {
        const src = getBestImageSrc(img);

        if (!src) {
            return;
        }

        previewImage.src = src;
        previewImage.alt = img.alt || '';

        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ravera6a-lightbox-open');
    }

    function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ravera6a-lightbox-open');

        setTimeout(function () {
            previewImage.src = '';
            previewImage.alt = '';
        }, 200);
    }

    function markImagesAsClickable(root) {
        const images = root.querySelectorAll('img');

        images.forEach(function (img) {
            if (!img.src) {
                return;
            }

            if (img.closest('.ravera6a-lightbox')) {
                return;
            }

            img.classList.add('ravera6a-lightbox-enabled');
        });
    }

    markImagesAsClickable(contentRoot);

    contentRoot.addEventListener('click', function (event) {
        const img = event.target.closest('img');

        if (!img) {
            return;
        }

        if (!contentRoot.contains(img)) {
            return;
        }

        if (img.closest('.ravera6a-lightbox')) {
            return;
        }

        event.preventDefault();
        openLightbox(img);
    });

    closeButton.addEventListener('click', closeLightbox);
    backdrop.addEventListener('click', closeLightbox);

    lightbox.addEventListener('click', function (event) {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && lightbox.classList.contains('is-open')) {
            closeLightbox();
        }
    });

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.matches && node.matches('img')) {
                    node.classList.add('ravera6a-lightbox-enabled');
                } else {
                    markImagesAsClickable(node);
                }
            });
        });
    });

    observer.observe(contentRoot, {
        childList: true,
        subtree: true
    });
});