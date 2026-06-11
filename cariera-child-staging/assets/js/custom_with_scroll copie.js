// ======================================================
// v2.0.3 JS unifié : Sticky widget + Validation formulaire + Scroll doux + Collapse
// Version corrigée (alignement parfait du widget sur .cv-form-layout)
// ======================================================

(function () {
    'use strict';

    const config = {
        debug: false,
        mobileBreakpoint: 1024,
    };

    window.StickyWidgetConfig = config;

    const errorMessages = {
        prenom: 'Il nous manque votre prénom.',
        nom: 'Oups, quel est votre nom ?',
        email: 'N\'oubliez pas votre email.',
        emailInvalid: 'Votre adresse email n\'est pas valide.',
        cv: 'Pas si vite, et votre CV ?',
        cgu: 'Vous devez accepter les conditions générales d\'utilisation.'
    };

    // === Initialisation principale ===
    function initializeAllScripts() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initScripts);
        } else {
            initScripts();
        }
    }

    function initScripts() {
        initSmoothScroll();
        initJobOverviewStickyClamp();
        initFormValidationFixed();
        initMessageCollapse();
        initRealTimeValidation();
        if (config.debug) console.debug('✅ Tous les scripts initialisés');
    }

    // Utilitaires
    function getPageTop(el) {
        return el.getBoundingClientRect().top + window.scrollY;
    }

    // ------------------------------------------------------------
    // SCROLL DOUX
    // ------------------------------------------------------------
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#cv-form"]').forEach(anchor => {
            anchor.addEventListener('click', e => {
                e.preventDefault();
                const target = document.querySelector('#cv-form');
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            });
        });
    }

    // ------------------------------------------------------------
    // STICKY WIDGET corrigé : s'arrête au top du .cv-form-layout
    // ------------------------------------------------------------
    function initJobOverviewStickyClamp() {
        const sidebar = document.querySelector('.job-sidebar');
        if (!sidebar) {
            if (config.debug) console.warn('❌ Sidebar .job-sidebar non trouvée');
            return;
        }

        const widget = sidebar.querySelector('.widget-job-overview');
        const cvFormLayout = document.querySelector('.cv-form-layout');

        if (!widget) return console.warn('❌ Widget .widget-job-overview non trouvé');
        if (!cvFormLayout) return console.warn('❌ CV Form Layout .cv-form-layout non trouvé');

        // Placeholder pour éviter les sauts
        let placeholder = widget.nextElementSibling;
        if (!placeholder || !placeholder.classList.contains('job-overview-placeholder')) {
            placeholder = document.createElement('div');
            placeholder.className = 'job-overview-placeholder';
            placeholder.style.display = 'none';
            widget.insertAdjacentElement('afterend', placeholder);
        }

        // S'assurer que la sidebar est positionnée
        if (getComputedStyle(sidebar).position === 'static') {
            sidebar.style.position = 'relative';
        }

        const TOP_OFFSET = 88; // marge top (header)
        let animationFrameId = null;
        let lastScrollY = window.scrollY;
// Pré-calculs initiaux
let cvFormLayoutTop = 0;

function computeLayoutPositions() {
  const el = document.querySelector('.cv-form-layout');
  if (el) cvFormLayoutTop = el.getBoundingClientRect().top + window.scrollY;
  if (config.debug) console.log('📍 Position initiale du cv-form-layout:', cvFormLayoutTop);
}

        function updatePosition() {
            animationFrameId = null;
            const currentScrollY = window.scrollY;
            const viewportWidth = window.innerWidth;
            const isMobile = viewportWidth <= config.mobileBreakpoint;

            if (isMobile) {
                setNormalMode();
                return;
            }

            // Recalcule des positions
            const sidebarRect = sidebar.getBoundingClientRect();
            const widgetHeight = widget.offsetHeight;

            const sidebarTop = getPageTop(sidebar);
            // const cvFormLayoutTop = getPageTop(cvFormLayout);

            // début sticky
            const startStickyY = sidebarTop - TOP_OFFSET;
            // arrêt sticky = haut du form
            const stopStickyY = cvFormLayoutTop - TOP_OFFSET;

            if (config.debug) {
                console.group('🔍 Sticky Debug');
                console.log({ scrollY: currentScrollY, startStickyY, stopStickyY });
                console.groupEnd();
            }

/* 
            if (currentScrollY < startStickyY) {
                setNormalMode();
            } else if (currentScrollY >= startStickyY && currentScrollY < stopStickyY) {
                setStickyMode(sidebarRect);
            } else {
                setStoppedMode(sidebar, cvFormLayout, sidebarTop);
            }
 */
// APRÈS
if (currentScrollY < startStickyY) {
    setNormalMode();
} else {
    setFixedClamped(sidebarRect, cvFormLayoutTop);
}
            lastScrollY = currentScrollY;
        }

        function onScroll() {
            if (!animationFrameId) {
                animationFrameId = requestAnimationFrame(updatePosition);
            }
        }

        function onResize() {
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            setTimeout(updatePosition, 50);
        }

        function setNormalMode() {
            widget.style.position = 'static';
            widget.style.top = '';
            widget.style.left = '';
            widget.style.width = '';
            widget.style.zIndex = '';
            widget.classList.remove('is-sticky', 'widget-background-transparent');
            placeholder.style.display = 'none';
            placeholder.style.height = '0px';
        }

function setFixedClamped(sidebarRect, cvFormLayoutTop) {
    // on garde la place occupée pour éviter le “saut”
    placeholder.style.display = 'block';
    placeholder.style.height  = widget.offsetHeight + 'px';

    // top “bridé” : ne dépasse pas TOP_OFFSET, et suit le haut du formulaire
    const topClamped = Math.min(TOP_OFFSET, cvFormLayoutTop - window.scrollY);

    widget.style.position = 'fixed';
    widget.style.top      = `${topClamped}px`;
    widget.style.left     = `${sidebarRect.left}px`;
    widget.style.width    = `${sidebarRect.width}px`;
    widget.style.zIndex   = '9999';

    widget.classList.add('is-sticky', 'widget-background-transparent');

    if (config.debug) {
        console.table({
            scrollY: window.scrollY,
            TOP_OFFSET,
            cvFormLayoutTop,
            topClamped
        });
    }
}
        function setStickyMode(sidebarRect) {
            placeholder.style.display = 'block';
            placeholder.style.height = widget.offsetHeight + 'px';

            widget.style.position = 'fixed';
            widget.style.top = `${TOP_OFFSET}px`;
            widget.style.left = `${sidebarRect.left}px`;
            widget.style.width = `${sidebarRect.width}px`;
            widget.style.zIndex = '9999';
            widget.classList.add('is-sticky', 'widget-background-transparent');
        }

        function setStoppedMode(sidebar, cvFormLayout, sidebarTop) {
            const sidebarStyles  = getComputedStyle(sidebar);
            const padTop         = parseFloat(sidebarStyles.paddingTop) || 0;
            const padLeft        = parseFloat(sidebarStyles.paddingLeft) || 0;
            const borderTop      = sidebar.clientTop || 0;
            const borderLeft     = sidebar.clientLeft || 0;

            const sidebarContentTop  = sidebarTop + borderTop + padTop;
            // const cvFormLayoutTop    = getPageTop(cvFormLayout);

            // ✅ On aligne le haut du widget avec le haut du cv-form-layout
            let absoluteTop = cvFormLayoutTop - sidebarContentTop;
            if (absoluteTop < 0) absoluteTop = 0;

            const contentWidth = sidebar.clientWidth;

            placeholder.style.display = 'block';
            placeholder.style.height  = widget.offsetHeight + 'px';

            widget.style.position = 'absolute';
            widget.style.top      = `${absoluteTop}px`;
            widget.style.left     = `${padLeft}px`;
            widget.style.width    = `${contentWidth}px`;
            widget.style.zIndex   = '9999';
            widget.classList.add('widget-background-transparent');
            widget.classList.remove('is-sticky');

            if (config.debug) {
                console.table({
                    sidebarTop,
                    cvFormLayoutTop,
                    padTop,
                    borderTop,
                    absoluteTop,
                    contentWidth
                });
            }
        }

        // Événements
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onResize);
        window.addEventListener('orientationchange', onResize);
window.addEventListener('load', computeLayoutPositions);
window.addEventListener('resize', computeLayoutPositions);
        setTimeout(updatePosition, 100);

        // Debug toggler
        window.toggleStickyDebug = function() {
            config.debug = !config.debug;
            console.log('🔧 Sticky Debug:', config.debug ? 'ON' : 'OFF');
            if (!config.debug) console.clear();
        };
    }

    // ------------------------------------------------------------
    // VALIDATION DU FORMULAIRE
    // ------------------------------------------------------------
    function initFormValidationFixed() {
        document.querySelectorAll('form').forEach(f => f.setAttribute('novalidate', 'true'));
        document.addEventListener('click', e => {
            if (e.target.matches('button[type="submit"], input[type="submit"]')) {
                const form = e.target.closest('form');
                if (form && isJobApplicationForm(form)) {
                    e.preventDefault();
                    validateAndSubmitForm(form);
                }
            }
        }, true);
    }

    function isJobApplicationForm(form) {
        return (
            form.querySelector('input[name="prenom"]') &&
            form.querySelector('input[type="file"]')
        );
    }

    function validateAndSubmitForm(form) {
        let isValid = true;
        let firstInvalid = null;
        clearAllErrorsFixed(form);
        const fields = findFormFieldsFixed(form);

        for (const [key, field] of Object.entries(fields)) {
            if (!field) continue;
            const result = validateFieldFixed(field, key);
            if (!result.isValid) {
                isValid = false;
                showFieldErrorFixed(field, result.message);
                if (!firstInvalid) firstInvalid = field;
            }
        }

        if (!isValid && firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        form.submit();
    }

    function findFormFieldsFixed(form) {
        return {
            prenom: form.querySelector('#jobiizy-prenom'),
            nom: form.querySelector('#jobiizy-nom'),
            email: form.querySelector('#jobiizy-email'),
            cv: form.querySelector('#jobiizy-cv'),
            cgu: form.querySelector('input[name="agreement-checkbox"]')
        };
    }

    function validateFieldFixed(field, name) {
        const value = (field.value || '').trim();
        switch (name) {
            case 'prenom':
            case 'nom':
                if (!value) return { isValid: false, message: errorMessages[name] };
                break;
            case 'email':
                if (!value) return { isValid: false, message: errorMessages.email };
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
                    return { isValid: false, message: errorMessages.emailInvalid };
                break;
            case 'cv':
                if (!field.files || !field.files.length)
                    return { isValid: false, message: errorMessages.cv };
                break;
            case 'cgu':
                if (!field.checked)
                    return { isValid: false, message: errorMessages.cgu };
                break;
        }
        return { isValid: true };
    }

    function clearAllErrorsFixed(form) {
        form.querySelectorAll('.cv-form-error-message').forEach(e => e.remove());
        form.querySelectorAll('input, textarea').forEach(el => {
            el.style.borderColor = '';
            el.style.backgroundColor = '';
        });
    }

    function showFieldErrorFixed(field, message) {
        const div = document.createElement('div');
        div.className = 'cv-form-error-message';
        div.innerHTML = `
      <img src="/wp-content/themes/cariera-child/assets/media/icons8-error.svg"
           alt="Erreur" width="16" height="16" style="margin-right:6px;">
      <span style="font-weight:600;color:#E52054">${message}</span>`;
        field.insertAdjacentElement('afterend', div);
    }

    // ------------------------------------------------------------
    // VALIDATION EN TEMPS RÉEL
    // ------------------------------------------------------------
    function initRealTimeValidation() {
        document.addEventListener('input', e => {
            if (e.target.matches('input, textarea')) clearFieldErrorFixed(e.target);
        });
    }

    function clearFieldErrorFixed(field) {
        const err = field.nextElementSibling;
        if (err && err.classList.contains('cv-form-error-message')) err.remove();
    }

    // ------------------------------------------------------------
    // COLLAPSE MESSAGE
    // ------------------------------------------------------------
    function initMessageCollapse() {
        document.addEventListener('click', e => {
            if (e.target.matches('.cv-form-collapse-toggle')) {
                e.preventDefault();
                const target = document.querySelector(e.target.dataset.target);
                if (!target) return;
                target.classList.toggle('show');
                const expanded = target.classList.contains('show');
                e.target.setAttribute('aria-expanded', expanded);
                const up = e.target.querySelector('.arrow-up');
                const down = e.target.querySelector('.arrow-down');
                if (up && down) {
                    up.style.display = expanded ? 'inline-block' : 'none';
                    down.style.display = expanded ? 'none' : 'inline-block';
                }
            }
        });
    }

    initializeAllScripts();
})();