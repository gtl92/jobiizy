/*!
 * JobiiZy – Global JS (stable)
 * - Init popup login Jobiizy (si présente)
 * - Helpers debug (jobiizyGetCaller...)
 * - Anti-boucle : retries bornés + exit sur pages non concernées
 */
 
 const JOBIIZY_DEBUG = true;

function jobiizyLog(...args) {
    if (JOBIIZY_DEBUG) console.log("[Jobiizy]", ...args);
}
//  GEMINI LIVE
document.addEventListener('DOMContentLoaded', function() {
    console.log("GEMINI LIVE");
    // 1. Ajouter une classe "scrolled" au header pour effet de transparence
    window.addEventListener('scroll', () => {
        const header = document.querySelector('.main-header');
        if (window.scrollY > 50) {
            header.classList.add('is-sticky');
        } else {
            header.classList.remove('is-sticky');
        }
    });

    // 2. Transformer les filtres en "Chips" sur Mobile
    if (window.innerWidth < 768) {
        const filters = document.querySelectorAll('.job-type-filter');
        filters.forEach(f => {
            f.classList.add('mobile-chip-style');
        });
    }
});
//  GEMINI LIVE


(function () {
  'use strict';

  // ------------------------------------------------------------
  // Debug helpers
  // ------------------------------------------------------------
  const DEBUG =
    window.jobIizyDebug === true ||
    window.jobiizyDebug === true ||
    (typeof window.JobiizyDebug !== 'undefined' && window.JobiizyDebug === true);

  const log = (...args) => DEBUG && console.log('%c[Jobiizy]', 'color:#0aa', ...args);
  const warn = (...args) => console.warn('%c[Jobiizy]', 'color:#f90', ...args);
  const err = (...args) => console.error('%c[Jobiizy]', 'color:#f33', ...args);

  // Ex: jobiizyGetCaller(2) -> donne une ligne stack utile pour tracer “qui appelle”
  function jobiizyGetCaller(depth = 2) {
    try {
      throw new Error('trace');
    } catch (e) {
      const stack = String(e.stack || '').split('\n').map(s => s.trim());
      return stack[depth] || stack[stack.length - 1] || '(caller unknown)';
    }
  }

  function jobiizyGetCallerSafe(depth = 2) {
    try {
      return jobiizyGetCaller(depth + 1);
    } catch (_) {
      return '(caller unknown)';
    }
  }

  // expose (si tu veux les appeler depuis la console)
  window.jobiizyGetCaller = jobiizyGetCaller;
  window.jobiizyGetCallerSafe = jobiizyGetCallerSafe;

  // ------------------------------------------------------------
  // Page guards (hyper important pour éviter les boucles)
  // ------------------------------------------------------------
  function isSingleJob() {
    return document.body.classList.contains('single-job_listing') || !!document.querySelector('main[data-jobiizy="job"]');
  }

  function hasSplitView() {
    return !!document.querySelector('.job-listings-split-view, .listing-split-view, [data-split-view]');
  }

  function hasJobiizyPopupNode() {
    return !!document.getElementById('jobiizy-login-popup');
  }

  // Sur ton clone, /emplois/ est une page Elementor “custom” :
  // -> pas archive WP, pas single job
  // -> on NE DOIT PAS lancer des loops si la popup n’existe pas
  function isSupportedForGlobal() {
    // On autorise si:
    // - fiche offre
    // - split-view présent
    // - ou la popup existe (injectée par PHP)
    return isSingleJob() || hasSplitView() || hasJobiizyPopupNode();
  }

  if (!isSupportedForGlobal()) {
    log('Page non concernée → jobiizy-global.js stop.', {
      url: location.href,
      body: document.body.className
    });
    return;
  }

  // ------------------------------------------------------------
  // Small utility: retry borné (anti setInterval infini)
  // ------------------------------------------------------------
  function retryUntil(fn, opts) {
    const {
      label = 'init',
      intervalMs = 250,
      maxAttempts = 12
    } = (opts || {});

    let attempts = 0;
    const timer = setInterval(() => {
      attempts++;

      let ok = false;
      try {
        ok = !!fn();
      } catch (e) {
        clearInterval(timer);
        err(`${label} → erreur:`, e, 'caller:', jobiizyGetCallerSafe(4));
        return;
      }

      if (ok) {
        clearInterval(timer);
        log(`${label} → OK (attempt ${attempts}/${maxAttempts})`);
        return;
      }

      if (attempts >= maxAttempts) {
        clearInterval(timer);
        warn(`${label} → introuvable après ${attempts} tentatives (stop).`);
      }
    }, intervalMs);

    return () => clearInterval(timer);
  }

  // ------------------------------------------------------------
  // Jobiizy Popup: init
  // ------------------------------------------------------------
  function initJobiizyPopup() {
    const popup = document.getElementById('jobiizy-login-popup');
    if (!popup) return false;

    // déjà init ?
    if (popup.dataset.jobiizyInit === '1') return true;
    popup.dataset.jobiizyInit = '1';

    // trouve overlay / close
    const overlay =
      popup.querySelector('[data-jobiizy-overlay]') ||
      popup.querySelector('.jobiizy-modal-overlay') ||
      popup.querySelector('.jobiizy-modal__overlay');

    const closeButtons = popup.querySelectorAll(
      '[data-close], .jobiizy-modal-close, .js-dialog-close, .js-close'
    );

    const close = () => {
      popup.classList.remove('is-open');
      popup.setAttribute('aria-hidden', 'true');
    };

    const open = () => {
      popup.classList.add('is-open');
      popup.removeAttribute('aria-hidden');
    };

    closeButtons.forEach(btn => btn.addEventListener('click', close));

    if (overlay) {
      overlay.addEventListener('click', (e) => {
        // click overlay uniquement (pas les enfants)
        if (e.target === overlay) close();
      });
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && popup.classList.contains('is-open')) {
        close();
      }
    });

    // API globale (utilisable par d'autres scripts)
    window.Jobiizy = window.Jobiizy || {};
    window.Jobiizy.openLoginPopup = open;
    window.Jobiizy.closeLoginPopup = close;

    log('Popup Jobiizy initialisée');
    return true;
  }

  // ------------------------------------------------------------
  // Boot
  // ------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', () => {
    // Important: retries bornés → plus de boucle infinie
    retryUntil(initJobiizyPopup, {
      label: 'initJobiizyPopup',
      intervalMs: 250,
      maxAttempts: 8
    });
  });


})();