/**
 * SAAQi Tech - Modern High-Performance Landing Page Scripts
 * Optimized for 60fps smoothness, zero layout thrashing, and low memory usage.
 */

/* ==========================================================================
   1. WHY CHOOSE US ACCORDION (Non-blocking, zero DOM cloning)
   ========================================================================== */
function choose_d() {
    const section = document.querySelector("#why-choose");
    if (!section) return;

    function setPointsSize() {
        requestAnimationFrame(() => {
            section.querySelectorAll(".points").forEach((point) => {
                const p = point.querySelector(".content p");
                if (!p) return;
                // Measure natural height directly without creating/appending clone nodes
                const prevH = p.style.height;
                const prevMax = p.style.maxHeight;
                p.style.height = "auto";
                p.style.maxHeight = "none";
                const naturalH = Math.ceil(p.scrollHeight);
                p.style.height = prevH;
                p.style.maxHeight = prevMax;
                point.style.setProperty("--size", naturalH + "px");
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setPointsSize);
    } else {
        setPointsSize();
    }
    window.addEventListener("load", setPointsSize, { passive: true });

    let resizeTimer;
    window.addEventListener("resize", () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(setPointsSize, 150);
    }, { passive: true });

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(setPointsSize).catch(() => {});
    }
}
choose_d();


/* ==========================================================================
   2. PORTFOLIO SWIPER SLIDER (Hardware accelerated)
   ========================================================================== */
function hero_slider() {
    const sliderEl = document.querySelector('.portfolio-slider');
    if (!sliderEl) return;

    function updateScaleLogic(sw) {
        if (!sw || !sw.params) return;
        const currentSlidesPerView = sw.params.slidesPerView;
        if (!sw.wrapperEl) return;

        if (currentSlidesPerView === 1) {
            sw.wrapperEl.classList.add('mobile-view');
            sw.wrapperEl.classList.remove('tablet-view', 'desktop-view');
        } else if (currentSlidesPerView === 3) {
            sw.wrapperEl.classList.add('tablet-view');
            sw.wrapperEl.classList.remove('mobile-view', 'desktop-view');
        } else {
            sw.wrapperEl.classList.add('desktop-view');
            sw.wrapperEl.classList.remove('mobile-view', 'tablet-view');
        }
    }

    function setScaleClasses(sw) {
        if (!sw || !sw.slides || !sw.slides.length) return;

        // cleanup all slides
        sw.slides.forEach(s => s.classList.remove('active', 'near', 'far'));

        const currentSlidesPerView = sw.params.slidesPerView;
        const a = sw.activeIndex;
        const activeSlide = sw.slides[a];
        if (activeSlide) activeSlide.classList.add('active');

        if (currentSlidesPerView === 1) {
            // Mobile: Only center slide is visible
        } else if (currentSlidesPerView === 3) {
            // Tablet: Center ±1 => near
            [a - 1, a + 1].forEach(i => {
                const slide = sw.slides[i];
                if (slide) slide.classList.add('near');
            });
        } else {
            // Desktop (5 slides): center ±1 => near, center ±2 => far
            [a - 1, a + 1].forEach(i => {
                const slide = sw.slides[i];
                if (slide) slide.classList.add('near');
            });
            [a - 2, a + 2].forEach(i => {
                const slide = sw.slides[i];
                if (slide) slide.classList.add('far');
            });
        }
    }

    new Swiper('.portfolio-slider', {
        loop: true,
        centeredSlides: true,
        spaceBetween: 0,
        speed: 700,
        breakpoints: {
            320: { slidesPerView: 1, centeredSlides: true },
            768: { slidesPerView: 3, centeredSlides: true },
            1024: { slidesPerView: 5, centeredSlides: true }
        },
        navigation: {
            nextEl: '.portfolio-slider .swiper-button-next',
            prevEl: '.portfolio-slider .swiper-button-prev',
        },
        on: {
            init() { 
                setScaleClasses(this); 
                updateScaleLogic(this); 
            },
            slideChange() {
                setScaleClasses(this);
                updateScaleLogic(this);
            },
            slideChangeTransitionStart() { 
                setScaleClasses(this); 
                updateScaleLogic(this); 
            },
            slideChangeTransitionEnd() { 
                setScaleClasses(this); 
                updateScaleLogic(this); 
            },
            loopFix() {
                setScaleClasses(this);
                updateScaleLogic(this);
            },
            resize() { 
                setScaleClasses(this); 
                updateScaleLogic(this); 
            },
            breakpoint() { 
                updateScaleLogic(this); 
                setScaleClasses(this); 
            }
        }
    });
}
hero_slider();


/* ==========================================================================
   3. ULTRA-SMOOTH MARQUEES WITH INTERSECTION OBSERVER (0% CPU off-screen)
   ========================================================================== */
document.addEventListener("DOMContentLoaded", () => {
    function createMarquee({ row, direction = -1, speed = 60 }) {
        if (!row) return;

        const originalHTML = row.innerHTML;
        let tween;

        function getSpeed() {
            return window.innerWidth <= 768 ? speed * 0.6 : speed;
        }

        function init() {
            if (tween) tween.kill();

            row.innerHTML = originalHTML;
            const rowWidth = row.scrollWidth || 600;
            const viewportWidth = window.innerWidth || 1200;

            // Safe repeat count: at least 2, at most 5 to avoid DOM explosion
            const rawRepeat = Math.ceil((viewportWidth * 2) / rowWidth);
            const repeatCount = Math.min(Math.max(rawRepeat, 2), 5);

            row.innerHTML = originalHTML.repeat(repeatCount);
            const loopWidth = row.scrollWidth / repeatCount;

            gsap.set(row, { x: 0 });

            // Hardware-accelerated fromTo loop without per-frame JS modifier overhead
            const targetX = direction * loopWidth;
            tween = gsap.fromTo(row, 
                { x: 0 },
                {
                    x: targetX,
                    duration: loopWidth / getSpeed(),
                    ease: "none",
                    repeat: -1
                }
            );
        }

        init();

        let resizeTimer;
        window.addEventListener("resize", () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(init, 250);
        }, { passive: true });

        // Pause on hover
        row.addEventListener("mouseenter", () => tween && tween.pause(), { passive: true });
        row.addEventListener("mouseleave", () => tween && tween.resume(), { passive: true });

        // PAUSE WHEN OFF-SCREEN: Saves 100% CPU when scrolled away!
        if ("IntersectionObserver" in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (tween && tween.paused()) tween.resume();
                    } else {
                        if (tween && !tween.paused()) tween.pause();
                    }
                });
            }, { rootMargin: "120px 0px" });
            observer.observe(row);
        }
    }

    /* Text Marquee */
    createMarquee({
        row: document.querySelector(".marque-01"),
        direction: -1,
        speed: 70
    });

    createMarquee({
        row: document.querySelector(".marque-02"),
        direction: 1,
        speed: 70
    });

    /* Testimonial Marquee */
    createMarquee({
        row: document.querySelector(".testimonial-marquee-01"),
        direction: -1,
        speed: 40
    });

    createMarquee({
        row: document.querySelector(".testimonial-marquee-02"),
        direction: 1,
        speed: 40
    });
});


/* ==========================================================================
   4. GALLERY SLIDER (Safe autoplay & modal zoom)
   ========================================================================== */
function gallery_slider() {
    const wrapper = document.querySelector('.gallery_slider-wrapper');
    if (!wrapper) return;

    const gallery_slider_instance = new Swiper('.gallery_slider-wrapper', {
        slidesPerView: 'auto',
        centeredSlides: true,
        spaceBetween: 20,
        breakpoints: {
            320: { slidesPerView: 1.2, centeredSlides: true, spaceBetween: 10 },
            768: { slidesPerView: 1.6, centeredSlides: true, spaceBetween: 20 },
            1024: { slidesPerView: 1.8, centeredSlides: true, spaceBetween: 30 }
        },
        navigation: {
            nextEl: '.gallery_slider-next',
            prevEl: '.gallery_slider-prev',
        },
        pagination: {
            el: '.gallery_slider-bullets',
            clickable: true,
        },
        speed: 500,
        loop: true,
        grabCursor: true,
        keyboard: { enabled: true }
    });

    const zoomButtons = document.querySelectorAll('.gallery_slider-zoom');
    const modal = document.querySelector('.gallery_slider-modal');
    const modalImg = document.querySelector('.gallery_slider-modal-img');
    const modalCaption = document.querySelector('.gallery_slider-modal-caption');
    const modalClose = document.querySelector('.gallery_slider-modal-close');

    if (modal && modalImg) {
        zoomButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const slide = button.closest('.gallery_slider-slide');
                const img = slide ? slide.querySelector('img') : null;
                if (!img) return;
                const altText = img.getAttribute('alt') || '';
                modalImg.src = img.src;
                if (modalCaption) modalCaption.textContent = altText;
                modal.classList.add('active');
            }, { passive: true });
        });

        const closeModal = () => modal.classList.remove('active');
        if (modalClose) modalClose.addEventListener('click', closeModal, { passive: true });
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        }, { passive: true });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
        }, { passive: true });
    }

    const slides = document.querySelectorAll('.gallery_slider-slide');
    slides.forEach((slide, index) => {
        if (!slide.querySelector('.gallery_slider-slide-number')) {
            const slideNumber = document.createElement('div');
            slideNumber.className = 'gallery_slider-slide-number';
            slideNumber.textContent = index + 1;
            slideNumber.style.cssText = `
                position: absolute;
                top: 15px;
                left: 15px;
                background: rgba(0, 0, 0, 0.7);
                color: white;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 14px;
                z-index: 5;
            `;
            slide.appendChild(slideNumber);
        }
    });
}
gallery_slider();
