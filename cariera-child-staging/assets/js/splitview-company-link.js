(function() {
  "use strict";
  
  // FLAG pour éviter les boucles
  let ctaInjected = false;
  
  function addCompanyCTA() {
    // Ne pas réinjecter si déjà fait
    if (ctaInjected) {
      console.log('[Jobiizy] CTA déjà injecté, skip');
      return;
    }
    
    // Attendre que le DOM soit prêt
    const details = document.querySelector('.company-info .company-details');
    
    if (!details) {
      console.warn('[Jobiizy] company-details introuvable → CTA non injecté');
      return;
    }
    
    // Vérifier si le CTA existe déjà
    if (details.querySelector('.jobiizy-company-cta')) {
      console.log('[Jobiizy] CTA existe déjà dans le DOM');
      ctaInjected = true;
      return;
    }
    
    // Créer et injecter le CTA
    const ctaDiv = document.createElement('div');
    ctaDiv.className = 'jobiizy-company-cta';
    ctaDiv.innerHTML = '<a href="#" class="btn">Voir l\'entreprise</a>';
    
    details.appendChild(ctaDiv);
    ctaInjected = true;
    
    console.log('[Jobiizy] CTA injecté avec succès');
  }
  
  // Exécuter uniquement si ce n'est PAS une page split-view
  if (!document.body.classList.contains('listing-split-view')) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', addCompanyCTA);
    } else {
      addCompanyCTA();
    }
  }
  
})();