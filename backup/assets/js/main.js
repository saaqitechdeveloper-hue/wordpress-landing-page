function choose_d(){
    (function () {
        const section = document.querySelector("#why-choose");
        if (!section) return;

        function getNaturalHeight(el) {
            const parent = el.parentElement;
            if (!parent) return 0;

            const clone = el.cloneNode(true);

            // parent ke andar hi inject karo so CSS inherit same rahe
            clone.style.position = "absolute";
            clone.style.visibility = "hidden";
            clone.style.pointerEvents = "none";
            clone.style.left = "0";
            clone.style.top = "0";

            // IMPORTANT: CSS zero ko bypass
            clone.style.height = "auto";
            clone.style.maxHeight = "none";
            clone.style.minHeight = "0";
            clone.style.overflow = "visible";

            // same width context (text wrap same rahe)
            clone.style.width = el.getBoundingClientRect().width + "px";

            parent.appendChild(clone);
            const h = Math.ceil(clone.scrollHeight);
            parent.removeChild(clone);

            return h;
        }

        function setPointsSize() {
            section.querySelectorAll(".points").forEach((point) => {
            const p = point.querySelector(".content p");
            if (!p) return;

            const naturalH = getNaturalHeight(p);
            point.style.setProperty("--size", naturalH + "px");
            });
        }

        document.addEventListener("DOMContentLoaded", setPointsSize);
        window.addEventListener("load", setPointsSize);

        let t;
        window.addEventListener("resize", () => {
            clearTimeout(t);
            t = setTimeout(setPointsSize, 100);
        });

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(setPointsSize).catch(() => {});
        }

        const ro = new ResizeObserver(() => setPointsSize());
        ro.observe(section);
        })();
}
choose_d();

function hero_slider(){
    const swiper = new Swiper('.portfolio-slider', {
        loop: true,
        centeredSlides: true,
        spaceBetween: 0,
        speed: 700,
        
        // Responsive breakpoints
        breakpoints: {
            // Mobile - 1 slide
            320: {
                slidesPerView: 1,
                centeredSlides: true,
            },
            // Tablet - 3 slides
            768: {
                slidesPerView: 3,
                centeredSlides: true,
            },
            // Desktop - 5 slides
            1024: {
                slidesPerView: 5,
                centeredSlides: true,
            }
        },

        // autoplay: {
        //     delay: 2200,
        //     disableOnInteraction: false,
        // },

        navigation: {
            nextEl: '.portfolio-slider .swiper-button-next',
            prevEl: '.portfolio-slider .swiper-button-prev',
        },

        on: {
            init() { 
                setScaleClasses(this); 
                updateScaleLogic(this);
            },
            slideChangeTransitionStart() { 
                setScaleClasses(this); 
                updateScaleLogic(this);
            },
            resize() { 
                setScaleClasses(this); 
                updateScaleLogic(this);
            },
            breakpoint() {
                // Update scale logic when breakpoint changes
                updateScaleLogic(this);
                setScaleClasses(this);
            }
        }
    });

    function updateScaleLogic(sw) {
        // Get current slidesPerView based on breakpoint
        const currentSlidesPerView = sw.params.slidesPerView;
        
        // Update scale logic based on screen size
        if (currentSlidesPerView === 1) {
            // Mobile: Only show center slide
            sw.wrapperEl.classList.add('mobile-view');
            sw.wrapperEl.classList.remove('tablet-view', 'desktop-view');
        } else if (currentSlidesPerView === 3) {
            // Tablet: Center + 1 on each side
            sw.wrapperEl.classList.add('tablet-view');
            sw.wrapperEl.classList.remove('mobile-view', 'desktop-view');
        } else {
            // Desktop: Center + 2 on each side
            sw.wrapperEl.classList.add('desktop-view');
            sw.wrapperEl.classList.remove('mobile-view', 'tablet-view');
        }
    }

    function setScaleClasses(sw) {
        // cleanup
        sw.slides.forEach(s => s.classList.remove('active', 'near', 'far'));
        
        // Get current slidesPerView
        const currentSlidesPerView = sw.params.slidesPerView;
        
        // active index in loop mode
        const a = sw.activeIndex;
        
        // Always mark active slide
        const activeSlide = sw.slides[a];
        if (activeSlide) activeSlide.classList.add('active');
        
        if (currentSlidesPerView === 1) {
            // Mobile: Only center slide is visible
            // No near/far slides
        } 
        else if (currentSlidesPerView === 3) {
            // Tablet: Center ±1 => near
            [a - 1, a + 1].forEach(i => {
                const slide = sw.slides[i];
                if (slide) slide.classList.add('near');
            });
        } 
        else {
            // Desktop (5 slides): Original logic
            // center ±1 => near
            [a - 1, a + 1].forEach(i => {
                const slide = sw.slides[i];
                if (slide) slide.classList.add('near');
            });

            // center ±2 => far
            [a - 2, a + 2].forEach(i => {
                const slide = sw.slides[i];
                if (slide) slide.classList.add('far');
            });
        }
    }
}
hero_slider();

document.addEventListener("DOMContentLoaded", () => {

  function createMarquee({
    row,
    direction = -1,
    speed = 60
  }) {
    if (!row) return;

    const originalHTML = row.innerHTML;
    let tween;

    function getSpeed() {
      return window.innerWidth <= 768 ? speed * 0.6 : speed;
    }

    function init() {
      if (tween) tween.kill();

      row.innerHTML = originalHTML;

      // width AFTER reset
      const rowWidth = row.scrollWidth;
      const viewportWidth = window.innerWidth;

      const repeatCount = Math.ceil((viewportWidth * 2) / rowWidth);
      row.innerHTML = originalHTML.repeat(repeatCount);

      const loopWidth = row.scrollWidth / repeatCount;

      gsap.set(row, { x: 0 });

      tween = gsap.to(row, {
        x: direction * loopWidth,
        duration: loopWidth / getSpeed(),
        ease: "none",
        repeat: -1,
        modifiers: {
          x: x => `${parseFloat(x) % loopWidth}px`
        }
      });
    }

    init();

    let resizeTimer;
    window.addEventListener("resize", () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(init, 300);
    });

    // ✅ hover ONLY this row
    row.addEventListener("mouseenter", () => tween.pause());
    row.addEventListener("mouseleave", () => tween.resume());
  }

  /* ================= TEXT MARQUEE ================= */
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

  /* ============== TESTIMONIAL MARQUEE ============== */
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

function gallery_slider(){
    const gallery_slider = new Swiper('.gallery_slider-wrapper', {
        // Configure slides to show center slide at 80% and side slides at 20%
        slidesPerView: 'auto',
        centeredSlides: true,
        spaceBetween: 20,
        
        // Calculate slide widths for 80-20 layout
        breakpoints: {
            320: {
                slidesPerView: 1.2,
                centeredSlides: true,
                spaceBetween: 10
            },
            768: {
                slidesPerView: 1.6,
                centeredSlides: true,
                spaceBetween: 20
            },
            1024: {
                // This creates the 80-20 effect
                slidesPerView: 1.8,
                centeredSlides: true,
                spaceBetween: 30
            }
        },
        
        // Navigation arrows
        navigation: {
            nextEl: '.gallery_slider-next',
            prevEl: '.gallery_slider-prev',
        },
        
        // Pagination bullets
        pagination: {
            el: '.gallery_slider-bullets',
            clickable: true,
        },
        
        // Effect
        effect: 'slide',
        speed: 500,
        loop: true,
        grabCursor: true,
        
        // Keyboard control
        keyboard: {
            enabled: true,
        },
    });

    // Zoom functionality
    const zoomButtons = document.querySelectorAll('.gallery_slider-zoom');
    const modal = document.querySelector('.gallery_slider-modal');
    const modalImg = document.querySelector('.gallery_slider-modal-img');
    const modalCaption = document.querySelector('.gallery_slider-modal-caption');
    const modalClose = document.querySelector('.gallery_slider-modal-close');

    // Add click event to each zoom button
    zoomButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            const slide = button.closest('.gallery_slider-slide');
            const img = slide.querySelector('img');
            const altText = img.getAttribute('alt');
            
            modalImg.src = img.src;
            modalCaption.textContent = altText;
            modal.classList.add('active');
            
            // Pause slider when modal is open
            gallery_slider.autoplay.stop();
        });
    });

    // Close modal when clicking close button
    modalClose.addEventListener('click', () => {
        modal.classList.remove('active');
        // Resume slider when modal is closed
        gallery_slider.autoplay.start();
    });

    // Close modal when clicking outside the image
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
            // Resume slider when modal is closed
            gallery_slider.autoplay.start();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            modal.classList.remove('active');
            // Resume slider when modal is closed
            gallery_slider.autoplay.start();
        }
    });

    // Add slide number indicator to each slide
    const slides = document.querySelectorAll('.gallery_slider-slide');
    slides.forEach((slide, index) => {
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
    });
}
gallery_slider();