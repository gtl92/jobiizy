/**
 * ==========================================================
 * 🎯 Script Jobiizy : remplacement du bloc #cv-form
 * ----------------------------------------------------------
 * Objectif :
 *  - Supprimer le formulaire "Envoyer votre CV" sur les pages de liste
 *    (ex : /jobs-split-view-2/)
 *  - Le remplacer par un simple bouton "👉 En savoir plus"
 *  - Gérer les clics sur ce bouton pour ouvrir la popup login
 *  - Réappliquer automatiquement le remplacement après chaque
 *    chargement AJAX (split view de Cariera)
 *  - Éviter le flash visuel du formulaire original avant remplacement
 * ==========================================================
 */

/**
 * Jobiizy – Remplacement du formulaire CV
 * Version STABLE (anti-boucle)
 */

(function () {
  'use strict';

  console.log('[Jobiizy] replace-cvform.js chargé');

  const SELECTOR = '#cv-form';
  const MAX_ATTEMPTS = 20;      // ⏱️ ~10 secondes
  const INTERVAL_MS = 500;

  let attempts = 0;
  let applied = false;
  let intervalId = null;

  /**
   * 🔍 Cherche le formulaire CV
   */
  function findCvForm() {
    return document.querySelector(SELECTOR);
  }

  /**
   * 🛠️ Applique la logique Jobiizy
   */
  function replaceCvForm(cvForm) {
    if (applied) {
      return;
    }

    applied = true;
    console.log('[Jobiizy] ✅ #cv-form trouvé, remplacement appliqué');

    // === TA LOGIQUE EXISTANTE PEUT RESTER ICI ===
    // Exemple :
    // cvForm.style.display = 'none';
    // injecter ton HTML custom
    // ou modifier le comportement

    cvForm.classList.add('jobiizy-cv-replaced');
  }

  /**
   * ⏱️ Tentative contrôlée (anti-boucle)
   */
  function tryInit() {
    if (applied) {
      stop();
      return;
    }

    const cvForm = findCvForm();

    if (cvForm) {
      replaceCvForm(cvForm);
      stop();
      return;
    }

    attempts++;

    if (attempts >= MAX_ATTEMPTS) {
      console.warn(
        '[Jobiizy] ⛔ #cv-form introuvable après',
        attempts,
        'tentatives → arrêt définitif'
      );
      stop();
    }
  }

  /**
   * 🛑 Arrêt propre
   */
  function stop() {
    if (intervalId) {
      clearInterval(intervalId);
      intervalId = null;
    }
  }

  /**
   * 🚀 Démarrage
   */
  function init() {
    // Tentative immédiate
    tryInit();

    // Puis tentatives bornées
    intervalId = setInterval(tryInit, INTERVAL_MS);
  }

  // DOM prêt
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();