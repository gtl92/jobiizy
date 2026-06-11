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

(function() {

    console.log("%c[Jobiizy] Script popup login chargé ✅", "color:lime;font-weight:bold;");

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

        console.log("%c[Jobiizy] ✅ Popup trouvée et initialisée", "color:lime");

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
        console.log("%c[Jobiizy] 🔓 Popup affichée", "color:gold;font-weight:bold;");
        return true;
    }

    /**
     * 🎯 Fonction pour fermer la popup
     */
    function closePopup() {
        const popup = initPopup();
        if (popup) {
            popup.overlay.style.display = 'none';
            console.log("[Jobiizy] Popup fermée");
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
            console.log("%c[Jobiizy] 📝 Soumission de formulaire CV (pas de popup)", "color:#09f");
            return; // Ne rien faire, laisser le formulaire se soumettre
        }

        // Liste des sélecteurs qui doivent déclencher la popup (hors formulaire)
        const shouldOpenPopup = 
            target.matches('.application_button') ||
            target.matches('a.btn.btn-main[href*="postuler"]') ||
            target.matches('a.btn.btn-main[href*="apply"]') ||
            target.matches('.job-apply-btn') ||
            target.matches('.apply-now-button') ||
            target.matches('#jobiizy-fav-guest');  // ❤️ bouton favori

        if (shouldOpenPopup) {
            e.preventDefault();
            e.stopPropagation();
            console.log("%c[Jobiizy] 🔓 Ouverture popup (hors formulaire)", "color:gold");
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
                console.log("[Jobiizy] Popup fermée (croix)");
            });
        }

        // Clic en dehors de la boîte
        popup.overlay.addEventListener('click', (e) => {
            if (e.target === popup.overlay) {
                closePopup();
                console.log("[Jobiizy] Popup fermée (clic sur fond)");
            }
        });

        // Touche Échap
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && popup.overlay.style.display === 'flex') {
                closePopup();
                console.log("[Jobiizy] Popup fermée (touche Échap)");
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
                console.log("[Jobiizy] Redirection vers:", jobiizyPopupRoutes.login);
                window.location.href = jobiizyPopupRoutes.login;
            });
        }

        // Bouton "Devenir candidat"
        if (popup.becomeCandidate) {
            popup.becomeCandidate.addEventListener('click', (e) => {
                e.preventDefault();
                console.log("[Jobiizy] Redirection vers:", jobiizyPopupRoutes.candidate);
                window.location.href = jobiizyPopupRoutes.candidate;
            });
        }

        // Bouton "Devenir employeur"
        if (popup.becomeEmployer) {
            popup.becomeEmployer.addEventListener('click', (e) => {
                e.preventDefault();
                console.log("[Jobiizy] Redirection vers:", jobiizyPopupRoutes.employer);
                window.location.href = jobiizyPopupRoutes.employer;
            });
        }

        console.log("%c[Jobiizy] ✅ Event listeners installés", "color:lime");
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
        console.log("%c[Jobiizy] 🔄 Rechargement AJAX — réinitialisation popup", "color:orange;font-weight:bold;");
        
        // Réinitialiser les listeners après rechargement AJAX
        setTimeout(() => {
            setupEventListeners();
        }, 300);
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