/* // ======================================================
   // Capitalise la 1re lettre des titres :
   // .title, .job-main-title, .single-job-listing-company-name
   // Gère aussi le contenu injecté dynamiquement (AJAX)
   // ======================================================
*/
window.TITLE_SELECTOR = window.TITLE_SELECTOR || '.title, .job-main-title, .single-job-listing-company-name';

/**
 * Capitalise le premier caractère du premier nœud texte
 * @param {Element} el - L'élément à traiter
 */
function capitalizeFirstTextNode(el) {
  // Éviter double-traitement
  if (el.dataset.capitalized === '1') return;

  // 🔧 FORCER text-transform: none pour éviter les conflits CSS
  el.style.setProperty('text-transform', 'none', 'important');

  // Trouver le 1er nœud texte non vide (descend dans l'arbre: <a>, <span>, etc.)
  const walker = document.createTreeWalker(
    el,
    NodeFilter.SHOW_TEXT,
    {
      acceptNode: (node) =>
        node.nodeValue && node.nodeValue.trim().length
          ? NodeFilter.FILTER_ACCEPT
          : NodeFilter.FILTER_SKIP
    }
  );

  const textNode = walker.nextNode();
  if (!textNode) {
    // console.warn('[title-capitalize] Aucun texte trouvé dans:', el);
    return;
  }

  // Préserve espaces initiaux, majuscule sur 1er caractère
  const original = textNode.nodeValue;
  const match = original.match(/^(\s*)(.)([\s\S]*)$/);
  
  if (!match) {
    // console.warn('[title-capitalize] Pas de match pour:', original);
    return;
  }

  const [, leadingSpaces, firstChar, rest] = match;
  const newValue = leadingSpaces + firstChar.toUpperCase() + rest.toLowerCase();
  
  // Appliquer uniquement si différent
  if (original !== newValue) {
    textNode.nodeValue = newValue;
   // console.log('[title-capitalize] ✅', original.trim(), '→', newValue.trim());
  }

  // Marquer comme traité
  el.dataset.capitalized = '1';
}

/**
 * Capitalise tous les titres dans un conteneur
 * @param {Document|Element} root - Conteneur à scanner
 */
function capitalizeAllTitles(root = document) {
  const els = root.querySelectorAll(TITLE_SELECTOR);
  // console.log('[title-capitalize] Trouvé', els.length, 'titre(s) à traiter');
  els.forEach(capitalizeFirstTextNode);
}

// ======================================================
// INITIALISATION
// ======================================================

document.addEventListener('DOMContentLoaded', () => {
  // console.log('[title-capitalize] 🚀 Initialisation...');
  
  // Exécution initiale avec délai pour laisser le DOM se stabiliser
  setTimeout(() => {
    capitalizeAllTitles();
  }, 100);

  // Surveille les ajouts dynamiques (pagination, AJAX, filtres…)
  const observer = new MutationObserver((mutations) => {
    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        // Ignorer les nœuds non-éléments
        if (node.nodeType !== Node.ELEMENT_NODE) return;

        // Si le node lui-même est un titre concerné
        if (node.matches && node.matches(TITLE_SELECTOR)) {
          capitalizeFirstTextNode(node);
        }
        
        // Scanner ses descendants
        if (node.querySelectorAll) {
          const titles = node.querySelectorAll(TITLE_SELECTOR);
          if (titles.length > 0) {
            // console.log('[title-capitalize] 🔄 Nouveau contenu détecté:', titles.length, 'titre(s)');
            titles.forEach(capitalizeFirstTextNode);
          }
        }
      });
    });
  });

  observer.observe(document.body, { 
    childList: true, 
    subtree: true 
  });

  // console.log('[title-capitalize] ✅ Observer actif');
});

// ======================================================
// OUTIL DE DEBUG (à retirer en production)
// ======================================================

// Décommenter pour tester manuellement dans la console
// window.testCapitalize = () => {
//   capitalizeAllTitles();
// };