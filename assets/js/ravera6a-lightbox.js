document.addEventListener('DOMContentLoaded', function () {
    const config = window.ravera6aLightbox || {};
    const enableAllPostContentImages = !!config.enableAllPostContentImages;

    const galleryRoots = Array.from(document.querySelectorAll('[data-ravera-gallery="1"]'));
    const postContentRoot = document.querySelector('.wp-block-post-content');

    if (!enableAllPostContentImages && !galleryRoots.length) {
        return;
    }

    const lightbox = document.createElement('div');
    lightbox.className = 'ravera6a-lightbox';
    lightbox.setAttribute('aria-hidden', 'true');

    lightbox.innerHTML = `
        <div class="ravera6a-lightbox__backdrop"></div>
        <div class="ravera6a-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Prévisualisation de l’image">
            <button type="button" class="ravera6a-lightbox__close" aria-label="Fermer la prévisualisation">×</button>
            <button type="button" class="ravera6a-lightbox__nav ravera6a-lightbox__nav--prev" aria-label="Image précédente">‹</button>
            <img class="ravera6a-lightbox__image" src="" alt="">
            <button type="button" class="ravera6a-lightbox__nav ravera6a-lightbox__nav--next" aria-label="Image suivante">›</button>
        </div>
    `;

    document.body.appendChild(lightbox);

    const backdrop = lightbox.querySelector('.ravera6a-lightbox__backdrop');
    const closeButton = lightbox.querySelector('.ravera6a-lightbox__close');
    const prevButton = lightbox.querySelector('.ravera6a-lightbox__nav--prev');
    const nextButton = lightbox.querySelector('.ravera6a-lightbox__nav--next');
    const previewImage = lightbox.querySelector('.ravera6a-lightbox__image');

    let currentImages = [];
    let currentIndex = -1;

    function getBestImageSrc(img) {
        const link = img.closest('a');

        if (link && link.href && /\.(jpg|jpeg|png|gif|webp|avif|svg)(\?.*)?$/i.test(link.href)) {
            return link.href;
        }

        if (img.srcset) {
            const sources = img.srcset.split(',').map(function (source) {
                return source.trim();
            });

            const largest = sources
                .map(function (source) {
                    const parts = source.split(/\s+/);
                    return {
                        url: parts[0] || '',
                        width: parseInt(parts[1], 10)
                    };
                })
                .filter(function (item) {
                    return item.url && !isNaN(item.width);
                })
                .sort(function (a, b) {
                    return b.width - a.width;
                })[0];

            if (largest && largest.url) {
                return largest.url;
            }
        }

        if (img.dataset && img.dataset.fullUrl) {
            return img.dataset.fullUrl;
        }

        if (img.currentSrc) {
            return img.currentSrc;
        }

        return img.src;
    }

    function isEligibleImage(img) {
        if (!img || !img.src) {
            return false;
        }

        if (img.closest('.ravera6a-lightbox')) {
            return false;
        }

        if (img.closest('[data-ravera-gallery="1"]')) {
            return true;
        }

        if (enableAllPostContentImages && postContentRoot && postContentRoot.contains(img)) {
            return true;
        }

        return false;
    }

    function getEligibleImages() {
        const seen = new Set();
        const images = [];

        if (enableAllPostContentImages && postContentRoot) {
            postContentRoot.querySelectorAll('img').forEach(function (img) {
                if (!isEligibleImage(img)) {
                    return;
                }

                const key = img;
                if (seen.has(key)) {
                    return;
                }

                seen.add(key);
                images.push(img);
            });
        }

        galleryRoots.forEach(function (galleryRoot) {
            galleryRoot.querySelectorAll('img').forEach(function (img) {
                if (!isEligibleImage(img)) {
                    return;
                }

                const key = img;
                if (seen.has(key)) {
                    return;
                }

                seen.add(key);
                images.push(img);
            });
        });

        return images;
    }

    function updateNavVisibility() {
        const hasNavigation = currentImages.length > 1;

        prevButton.hidden = !hasNavigation;
        nextButton.hidden = !hasNavigation;
    }

    function showImageAtIndex(index) {
        if (!currentImages.length) {
            return;
        }

        if (index < 0) {
            index = currentImages.length - 1;
        }

        if (index >= currentImages.length) {
            index = 0;
        }

        currentIndex = index;

        const img = currentImages[currentIndex];
        const src = getBestImageSrc(img);

        if (!src) {
            return;
        }

        previewImage.src = src;
        previewImage.alt = img.alt || '';
        updateNavVisibility();
    }

    function openLightbox(img) {
        currentImages = getEligibleImages();
        currentIndex = currentImages.indexOf(img);

        if (currentIndex === -1) {
            currentImages = [img];
            currentIndex = 0;
        }

        showImageAtIndex(currentIndex);

        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ravera6a-lightbox-open');
    }

    function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ravera6a-lightbox-open');

        currentImages = [];
        currentIndex = -1;

        setTimeout(function () {
            previewImage.src = '';
            previewImage.alt = '';
        }, 200);
    }

    function showPreviousImage() {
        if (!lightbox.classList.contains('is-open') || currentImages.length < 2) {
            return;
        }

        showImageAtIndex(currentIndex - 1);
    }

    function showNextImage() {
        if (!lightbox.classList.contains('is-open') || currentImages.length < 2) {
            return;
        }

        showImageAtIndex(currentIndex + 1);
    }

    function markImagesAsClickable(root) {
        const images = root.querySelectorAll('img');

        images.forEach(function (img) {
            if (isEligibleImage(img)) {
                img.classList.add('ravera6a-lightbox-enabled');
            }
        });
    }

    if (enableAllPostContentImages && postContentRoot) {
        markImagesAsClickable(postContentRoot);
    }

    galleryRoots.forEach(function (galleryRoot) {
        markImagesAsClickable(galleryRoot);
    });

    document.addEventListener('click', function (event) {
        const img = event.target.closest('img');

        if (!img) {
            return;
        }

        if (!isEligibleImage(img)) {
            return;
        }

        event.preventDefault();
        openLightbox(img);
    });

    closeButton.addEventListener('click', closeLightbox);
    backdrop.addEventListener('click', closeLightbox);
    prevButton.addEventListener('click', function (event) {
        event.stopPropagation();
        showPreviousImage();
    });
    nextButton.addEventListener('click', function (event) {
        event.stopPropagation();
        showNextImage();
    });

    lightbox.addEventListener('click', function (event) {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (!lightbox.classList.contains('is-open')) {
            return;
        }

        if (event.key === 'Escape') {
            closeLightbox();
            return;
        }

        if (event.key === 'ArrowLeft') {
            showPreviousImage();
            return;
        }

        if (event.key === 'ArrowRight') {
            showNextImage();
        }
    });

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.matches && node.matches('img')) {
                    if (isEligibleImage(node)) {
                        node.classList.add('ravera6a-lightbox-enabled');
                    }
                } else {
                    markImagesAsClickable(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});