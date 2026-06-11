/**
 * ==========================================================
 * 🎯 Script Jobiizy : Gestion de la popup de connexion / inscription
 * ----------------------------------------------------------
 * Objectif :
 *  - Afficher la popup quand un visiteur non connecté clique sur :
 *      • le bouton "Postuler"
 *      • le bouton "Apply"
 *      • tout bouton .application_button
 *  - Permettre la fermeture manuelle de la popup
 *  - Gérer les redirections vers :
 *      • Page de connexion / inscription
 *      • Page Devenir candidat
 *      • Page Devenir employeur
 *  - Compatible avec les rechargements AJAX du thème Cariera
 * ==========================================================
 */

(function () {
  console.log("🔥 [Jobiizy] splitview-apply chargé");
if (document.querySelector('.listing-details-container')) {
  console.log('[Jobiizy] splitview détecté → splitview-apply désactivé');
  return;
}

    // jobiizyLog("%c[Jobiizy] Script popup login chargé ✅", "color:lime;font-weight:bold;");
    // jobiizyLog("[random-bg] applied to", el.tagName, chosenGradient);

    /**
     * 🔍 Fonction pour trouver et initialiser la popup
     * (nécessaire car la popup peut ne pas être dans le DOM au chargement)
     */
    function initPopup() {
        const overlay = document.getElementById('jobiizy-login-popup-overlay');
        
        if (!overlay) {
            console.warn("[Jobiizy] ⚠️ Popup non trouvée dans le DOM (sera réessayé)");
            return null;
        }

        jobiizyLog("%c[Jobiizy] ✅ Popup trouvée et initialisée", "color:lime");

        // Sélection des éléments internes
        const closeBtn = overlay.querySelector('.jobiizy-popup-close');
        const loginConfirm = document.getElementById('jobiizy-login-confirm');
        const becomeCandidate = document.getElementById('jobiizy-become-candidate');
        const becomeEmployer = document.getElementById('jobiizy-become-employer');

        return {
            overlay,
            closeBtn,
            loginConfirm,
            becomeCandidate,
            becomeEmployer
        };
    }

    /**
     * 🎯 Fonction pour ouvrir la popup
     */
    function openPopup() {
        const popup = initPopup();
        if (!popup) {
            console.error("[Jobiizy] ❌ Impossible d'ouvrir la popup (élément non trouvé)");
            return false;
        }

        popup.overlay.style.display = 'flex';
        jobiizyLog("%c[Jobiizy] 🔓 Popup affichée", "color:gold;font-weight:bold;");
        return true;
    }

    /**
     * 🎯 Fonction pour fermer la popup
     */
    function closePopup() {
        const popup = initPopup();
        if (popup) {
            popup.overlay.style.display = 'none';
            jobiizyLog("[Jobiizy] Popup fermée");
        }
    }

    /**
     * 🔗 Gestion des clics sur les boutons "Postuler"
     */
    function handleApplicationClick(e) {
        const target = e.target.closest('a, button');
        if (!target) return;

        // 🔍 Vérifier si on est dans un formulaire CV valide (page single job)
        const isInsideForm = target.closest('form.job-manager-application-form');
        
        if (isInsideForm) {
            // ✅ Si c'est dans un formulaire, laisser la soumission normale se faire
            jobiizyLog("%c[Jobiizy] 📝 Soumission de formulaire CV (pas de popup)", "color:#09f");
            return; // Ne rien faire, laisser le formulaire se soumettre
        }

        // Liste des sélecteurs qui doivent déclencher la popup (hors formulaire)
        const shouldOpenPopup = 
            target.matches('.application_button') ||
            target.matches('a.btn.btn-main[href*="postuler"]') ||
            target.matches('a.btn.btn-main[href*="apply"]') ||
            target.matches('.job-apply-btn') ||
            target.matches('.apply-now-button');

        if (shouldOpenPopup) {
            e.preventDefault();
            e.stopPropagation();
            jobiizyLog("%c[Jobiizy] 🔓 Ouverture popup (hors formulaire)", "color:gold");
            openPopup();
        }
    }

    /**
     * 📋 Initialisation des event listeners
     */
    function setupEventListeners() {
        const popup = initPopup();
        if (!popup) {
            console.warn("[Jobiizy] ⚠️ Popup non disponible, listeners non installés");
            return;
        }

        // ==========================================================
        // 🔹 OUVERTURE DE LA POPUP
        // ==========================================================
        document.addEventListener('click', handleApplicationClick, true);

        // ==========================================================
        // 🔹 FERMETURE DE LA POPUP
        // ==========================================================
        
        // Bouton croix (×)
        if (popup.closeBtn) {
            popup.closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                closePopup();
                jobiizyLog("[Jobiizy] Popup fermée (croix)");
            });
        }

        // Clic en dehors de la boîte
        popup.overlay.addEventListener('click', (e) => {
            if (e.target === popup.overlay) {
                closePopup();
                jobiizyLog("[Jobiizy] Popup fermée (clic sur fond)");
            }
        });

        // Touche Échap
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && popup.overlay.style.display === 'flex') {
                closePopup();
                jobiizyLog("[Jobiizy] Popup fermée (touche Échap)");
            }
        });

        // ==========================================================
        // 🔹 REDIRECTIONS
        // ==========================================================
        
        // Vérifier que les routes sont disponibles
        if (typeof jobiizyPopupRoutes === 'undefined') {
            console.error("[Jobiizy] ❌ jobiizyPopupRoutes non défini ! Vérifiez wp_localize_script dans functions.php");
            return;
        }

        // Bouton "Se connecter / S'inscrire"
        if (popup.loginConfirm) {
            popup.loginConfirm.addEventListener('click', (e) => {
                e.preventDefault();
                jobiizyLog("[Jobiizy] Redirection vers:", jobiizyPopupRoutes.login);
                window.location.href = jobiizyPopupRoutes.login;
            });
        }

        // Bouton "Devenir candidat"
        if (popup.becomeCandidate) {
            popup.becomeCandidate.addEventListener('click', (e) => {
                e.preventDefault();
                jobiizyLog("[Jobiizy] Redirection vers:", jobiizyPopupRoutes.candidate);
                window.location.href = jobiizyPopupRoutes.candidate;
            });
        }

        // Bouton "Devenir employeur"
        if (popup.becomeEmployer) {
            popup.becomeEmployer.addEventListener('click', (e) => {
                e.preventDefault();
                jobiizyLog("[Jobiizy] Redirection vers:", jobiizyPopupRoutes.employer);
                window.location.href = jobiizyPopupRoutes.employer;
            });
        }

        jobiizyLog("%c[Jobiizy] ✅ Event listeners installés", "color:lime");
    }
// GTL01 - Injection du bouton Postuler dans le split-view
function forceSplitViewRedirect(singleJobUrl) {
  const detailsContainer = document.querySelector('.listing-details-container');
  if (!detailsContainer) return;

  // attendre que Cariera ait injecté le contenu
  const observer = new MutationObserver(() => {
    const listing = detailsContainer.querySelector('.listing');
    if (!listing) return;

    // 🔥 Nettoyage : supprimer tout ce qui permet de postuler ici
    listing.querySelectorAll(
      'form, .job-manager-application-form, .application_button, .apply-now-button'
    ).forEach(el => el.remove());

    // éviter doublon
    if (listing.querySelector('.jobiizy-split-redirect')) return;

    const btn = document.createElement('a');
    btn.href = singleJobUrl;
    btn.className = 'jobiizy-split-redirect button btn btn-main';
    btn.textContent = 'Voir l’offre & postuler';
    btn.style.display = 'block';
    btn.style.marginTop = '24px';
    btn.style.textAlign = 'center';

    listing.appendChild(btn);

    observer.disconnect();
  });

  observer.observe(detailsContainer, {
    childList: true,
    subtree: true
  });
}
    /**
     * 🚀 Initialisation avec retry
     */
    let retryCount = 0;
    const maxRetries = 10;
    
    const initInterval = setInterval(() => {
        if (initPopup() || retryCount++ >= maxRetries) {
            clearInterval(initInterval);
            
            if (retryCount >= maxRetries) {
                console.error("[Jobiizy] ❌ Popup non trouvée après 5s - vérifiez que le HTML est présent dans le template");
            } else {
                setupEventListeners();
            }
        }
    }, 500);

    /**
     * 🔄 Compatibilité AJAX / Split-view
     */
    document.addEventListener('cariera_after_single_job_loaded', () => {
        jobiizyLog("%c[Jobiizy] 🔄 Rechargement AJAX — réinitialisation popup", "color:orange;font-weight:bold;");
        
        // Réinitialiser les listeners après rechargement AJAX
        setTimeout(() => {
            setupEventListeners();
        }, 300);
    });

document.addEventListener('click', function (e) {
  const link = e.target.closest('.job_listing a');
  if (!link) return;

  const container = document.querySelector('.listing-details-container');
  if (!container) return;

  // 👉 BLOQUE la navigation
  e.preventDefault();

  console.log('[Jobiizy] SplitView click intercepté', link.href);

  // 👉 active le loader
  container.classList.add('loading');
  const loader = container.querySelector('.loader');
  if (loader) loader.style.display = 'block';

  // 👉 récupère l’ID attendu par Cariera
  const li = link.closest('[data-id]');
  if (!li) {
    console.warn('[Jobiizy] data-id introuvable, fallback navigation');
    window.location.href = link.href;
    return;
  }

  const listingId = li.dataset.id;

  // 👉 appel AJAX EXACT Cariera
  if (typeof jQuery !== 'undefined') {
    jQuery.ajax({
      url: window.ajaxurl || '/wp-admin/admin-ajax.php',
      type: 'POST',
      data: {
        action: 'cariera_listing_half_loading',
        listing_id: listingId
      },
      success: function (response) {
        const html = jQuery(response).find('.listing').html();

        if (html) {
          container.querySelector('.listing').innerHTML = html;
          console.log('[Jobiizy] Injection bouton postuler', link.href);
          forceSplitViewRedirect(link.href);   //GTL01
          container.classList.remove('loading');
          if (loader) loader.style.display = 'none';
        } else {
          console.warn('[Jobiizy] Réponse AJAX vide → fallback');
          window.location.href = link.href;
        }
      },
      error: function () {
        console.error('[Jobiizy] AJAX erreur → fallback navigation');
        window.location.href = link.href;
      }
    });
  } else {
    // sécurité ultime
    window.location.href = link.href;
  }
});
    /**
     * 🌐 Exposer les fonctions globalement pour debug
     */
    window.jobiizyPopup = {
        open: openPopup,
        close: closePopup,
        init: initPopup
    };


})();