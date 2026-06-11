/**
 * JOBIIZY – Gestion unifiée Favoris / Postuler / Entreprises
 * ----------------------------------------------------------
 * Version corrigée FINALE :
 *   ✔ Détection de l'action AVANT fermeture popup
 *   ✔ Gestion titre dynamique fiable à 100%
 *   ✔ Priorité correcte (POSTULER > Favoris Offre > Favoris Entreprise)
 */

/*
const JOBIIZY_DEBUG = true;

function jobiizyLog(...args) {
    if (JOBIIZY_DEBUG) console.log("[Jobiizy]", ...args);
}
*/
document.addEventListener("DOMContentLoaded", function () {
if (window.__JOBIIZY_BOOKMARK_INIT__) {
  console.warn('[Jobiizy] bookmark.js déjà initialisé → stop.');
  return;
}
window.__JOBIIZY_BOOKMARK_INIT__ = true;
    const popup = document.getElementById("jobiizy-login-popup-overlay");
    if (!popup) {
        console.log("❌ Popup introuvable, script arrêté");
        return;
    }

    const popupTitle = popup.querySelector("h2");
    const popupSubtitle = popup.querySelector("p");

    if (!popupTitle || !popupSubtitle) {
        console.log("❌ Éléments popup manquants");
        return;
    }

    console.log("✅ Script initialisé, popup détectée");

    // Fonction pour afficher la popup avec un message
    function showPopup(title, subtitle = "") {
        popupTitle.textContent = title;
        popupSubtitle.textContent = subtitle;
        popup.style.display = "flex";
        document.body.classList.add("jobiizy-popup-open");
        console.log("📢 Popup affichée:", title);
    }

    // Fonction pour fermer la popup
    function closePopup() {
        popup.style.display = "none";
        document.body.classList.remove("jobiizy-popup-open");
        console.log("🔄 Popup fermée");
    }

    // Gestionnaire de clic global
    document.addEventListener("click", function (e) {

        // ---------------------------------------------------------
        // 🔍 PHASE 1 : IDENTIFIER L'ACTION (sans toucher à la popup)
        // ---------------------------------------------------------
        
        let actionDetected = null;
        let actionMessage = "";

        // 🟥 1️⃣ Vérifier POSTULER (PRIORITÉ MAXIMUM)
        const applyBtn = e.target.closest("a.application_button");
        if (applyBtn) {
            const isLoggedIn = document.body.classList.contains("logged-in");
            
            if (isLoggedIn) {
                console.log("POSTULER ✔ utilisateur connecté → scroll vers formulaire");
                return; // Action native Cariera
            }
            
            actionDetected = "POSTULER";
            actionMessage = "Connectez-vous pour postuler à cette offre.";
            console.log("🎯 Action détectée: POSTULER (non connecté)");
        }

        // ❤️ 2️⃣ Vérifier COEUR OFFRE
        if (!actionDetected) {
            const heartBtn = e.target.closest("#jobiizy-fav-guest");
            if (heartBtn) {
                actionDetected = "FAVORIS_OFFRE";
                actionMessage = "Connectez-vous pour ajouter cette offre à vos favoris.";
                console.log("🎯 Action détectée: FAVORIS_OFFRE");
            }
        }

        // 🏢 3️⃣ Vérifier COEUR ENTREPRISE
        if (!actionDetected) {
            const companyHeart = e.target.closest("a.company-bookmark");
            if (companyHeart) {
                const isLoggedIn = document.body.classList.contains("logged-in");
                
                if (isLoggedIn) {
                    console.log("🏢 Cœur ENTREPRISE → utilisateur connecté (action native)");
                    return; // Action native Cariera
                }
                
                actionDetected = "FAVORIS_ENTREPRISE";
                actionMessage = "Connectez-vous pour ajouter cette entreprise à vos favoris.";
                console.log("🎯 Action détectée: FAVORIS_ENTREPRISE");
            }
        }

        // 🔒 4️⃣ Vérifier FERMETURE POPUP
        if (!actionDetected && popup.style.display === "flex") {
            if (e.target === popup || e.target.closest(".jobiizy-popup-close")) {
                closePopup();
                return;
            }
        }

        // ---------------------------------------------------------
        // 🎬 PHASE 2 : EXÉCUTER L'ACTION (maintenant qu'on sait quoi faire)
        // ---------------------------------------------------------
        
        if (actionDetected) {
            e.preventDefault();
            e.stopPropagation();
            
            // Fermer la popup si elle était déjà ouverte
            if (popup.style.display === "flex") {
                closePopup();
                // Petit délai pour éviter conflit visuel
                setTimeout(() => {
                    showPopup(actionMessage);
                }, 50);
            } else {
                showPopup(actionMessage);
            }
        }

    }, true); // ⚠️ IMPORTANT : useCapture = true pour capturer avant les autres handlers

    // Fermeture par touche Échap
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && popup.style.display === "flex") {
            closePopup();
        }
    });

});