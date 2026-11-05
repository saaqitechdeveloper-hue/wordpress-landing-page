/**
 * SAAQi Tech - Winter Sale 2026 Interactive Script
 * - Synchronized Multi-instance Countdown Timer (Dec 31, 2026)
 * - Custom Dropdown UI (ul / li / span) with rotating SVG arrow
 * - Dynamic Package Selection & Auto-populate Form
 * - Smooth Scroll & Visual Form Highlight
 */

document.addEventListener('DOMContentLoaded', function() {
    initWinterCountdown();
    initCustomSelects();
    initLenisSmoothScroll();
});

/**
 * Live Countdown Timer to December 31, 2026 23:59:59
 */
function initWinterCountdown() {
    const targetDate = new Date('2026-12-31T23:59:59').getTime();

    function update() {
        const now = new Date().getTime();
        let diff = targetDate - now;

        if (diff <= 0) {
            diff = (14 * 24 * 60 * 60 * 1000) - (Math.abs(diff) % (14 * 24 * 60 * 60 * 1000));
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        const pad = (n) => (n < 10 ? '0' + n : n);

        const daysElements = document.querySelectorAll('.winter-time-days');
        const hoursElements = document.querySelectorAll('.winter-time-hours');
        const minsElements = document.querySelectorAll('.winter-time-mins');
        const secsElements = document.querySelectorAll('.winter-time-secs');

        daysElements.forEach(el => el.textContent = pad(days));
        hoursElements.forEach(el => el.textContent = pad(hours));
        minsElements.forEach(el => el.textContent = pad(minutes));
        secsElements.forEach(el => el.textContent = pad(seconds));
    }

    update();
    setInterval(update, 1000);
}

/**
 * Modern Custom Dropdown UI (ul / li / span)
 * Preserves native select value for form submission, accessibility, and validation.
 * Rotates SVG chevron smoothly and never disappears.
 */
function initCustomSelects() {
    const customSelects = document.querySelectorAll('.winter-custom-select');

    customSelects.forEach(wrapper => {
        const trigger = wrapper.querySelector('.winter-select-trigger');
        const triggerText = wrapper.querySelector('.winter-select-text');
        const options = wrapper.querySelectorAll('.winter-select-option');
        const nativeSelect = wrapper.querySelector('.winter-hidden-select');

        if (!trigger || !nativeSelect) return;

        // Sync custom UI to native select value
        function syncFromNative() {
            const currentVal = nativeSelect.value;
            let matchedText = '';
            options.forEach(opt => {
                if (opt.getAttribute('data-value') === currentVal) {
                    opt.classList.add('selected');
                    matchedText = opt.textContent.trim();
                } else {
                    opt.classList.remove('selected');
                }
            });

            if (matchedText) {
                triggerText.textContent = matchedText;
                triggerText.classList.remove('placeholder');
            }
        }

        // Initialize state
        syncFromNative();

        // Listen to programmatic or native changes
        nativeSelect.addEventListener('change', syncFromNative);

        // Toggle open / close
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = wrapper.classList.contains('active');

            // Close all other open custom selects
            document.querySelectorAll('.winter-custom-select.active').forEach(other => {
                if (other !== wrapper) {
                    other.classList.remove('active');
                    other.querySelector('.winter-select-trigger')?.setAttribute('aria-expanded', 'false');
                }
            });

            if (isOpen) {
                wrapper.classList.remove('active');
                trigger.setAttribute('aria-expanded', 'false');
            } else {
                wrapper.classList.add('active');
                trigger.setAttribute('aria-expanded', 'true');
            }
        });

        // Option selection
        options.forEach(opt => {
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                const val = opt.getAttribute('data-value');
                const text = opt.textContent.trim();

                nativeSelect.value = val;
                triggerText.textContent = text;
                triggerText.classList.remove('placeholder');

                options.forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');

                wrapper.classList.remove('active');
                trigger.setAttribute('aria-expanded', 'false');

                // Trigger change event
                nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        // Keyboard navigation
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                trigger.click();
            } else if (e.key === 'Escape') {
                wrapper.classList.remove('active');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.winter-custom-select')) {
            document.querySelectorAll('.winter-custom-select.active').forEach(activeWrapper => {
                activeWrapper.classList.remove('active');
                activeWrapper.querySelector('.winter-select-trigger')?.setAttribute('aria-expanded', 'false');
            });
        }
    });
}

/**
 * Handle "Get This Deal" package click:
 * Auto-selects dropdowns, scrolls to form, highlights form with pulsing glow
 */
function handleSelectPackage(packageType, budget) {
    const websiteTypeSelect = document.getElementById('website_type');
    const messageInput = document.getElementById('message');
    const budgetSelect = document.getElementById('budget');
    const formCard = document.querySelector('.winter-form-card') || document.querySelector('.form');
    const formLink = document.getElementById('form-link');

    // Cleanly map the 3 packages
    let mappedType = packageType;
    if (packageType === 'WordPress Website') {
        mappedType = 'WordPress';
    } else if (packageType === 'WordPress') {
        mappedType = 'WordPress';
    } else if (packageType === 'Custom WordPress') {
        mappedType = 'Custom WordPress';
    } else if (packageType === 'WordPress + AI') {
        mappedType = 'WordPress + AI';
    }

    if (websiteTypeSelect && mappedType) {
        websiteTypeSelect.value = mappedType;
        websiteTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (budgetSelect && budget) {
        budgetSelect.value = budget;
        budgetSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (messageInput && budget) {
        messageInput.value = 'Selected Deal: ' + mappedType + ' (' + budget + ')';
    }

    // Smooth scroll to form (Lenis integrated)
    if (formLink) {
        if (window.lenis) {
            window.lenis.scrollTo(formLink, { offset: -30, duration: 1.2 });
        } else {
            formLink.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Visual pulse effect
    if (formCard) {
        formCard.classList.add('highlight-focus');
        setTimeout(() => {
            formCard.classList.remove('highlight-focus');
        }, 1800);
    }
}

window.selectWinterPackage = handleSelectPackage;
window.selectPackageAndScroll = handleSelectPackage;


/**
 * Lenis Smooth Scroll Initialization & Anchor Interception
 */
function initLenisSmoothScroll() {
    if (typeof Lenis === 'undefined') return;

    try {
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            orientation: 'vertical',
            gestureOrientation: 'vertical',
            smoothWheel: true,
            wheelMultiplier: 1.0,
            touchMultiplier: 1.5,
        });

        window.lenis = lenis;

        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);

        // Smooth scroll for all internal anchors
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId && targetId !== '#' && targetId.startsWith('#')) {
                    const targetEl = document.querySelector(targetId);
                    if (targetEl) {
                        e.preventDefault();
                        lenis.scrollTo(targetEl, { offset: -30, duration: 1.2 });
                    }
                }
            });
        });
    } catch (err) {
        console.warn('Lenis initialization failed:', err);
    }
}
