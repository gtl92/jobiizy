(function() {
  "use strict";

  // FLAG pour éviter les boucles infinies
  let isApplying = false;
  
  const gradients = [
    'linear-gradient(to bottom, #e7d6ff, #c9c6ff, #a89eff)', // lilas
    'linear-gradient(to bottom, #d6e6ff, #a8c8ff, #6699ff)', // bleu clair
    'linear-gradient(to bottom, #ffe5cc, #ffd1a8, #ffb866)', // pêche
    'linear-gradient(to bottom, #d6fff0, #a8ffe0, #66e0c0)', // menthe
    'linear-gradient(to bottom, #fff2d6, #ffe4a8, #ffcc66)', // doré doux
    'linear-gradient(to bottom, #fcd6ff, #f7a8ff, #ea66ff)'  // rose clair
  ];

  const chosenGradient = gradients[Math.floor(Math.random() * gradients.length)];

  function applyGradient(el) {
    // Éviter d'appliquer si déjà en cours
    if (isApplying) return;
    
    // Vérifier si le gradient est déjà appliqué
    const currentBg = el.style.getPropertyValue('background-image');
    if (currentBg === chosenGradient) {
      console.log('[random-bg] Gradient déjà appliqué, skip');
      return;
    }
    
    isApplying = true;
    
    el.style.setProperty('background', chosenGradient, 'important');
    el.style.setProperty('background-image', chosenGradient, 'important');
    
    console.log('[random-bg] Applied to', el.tagName, chosenGradient);
    
    // Relâcher le flag après un court délai
    setTimeout(function() {
      isApplying = false;
    }, 100);
  }

  function init() {
    // Cibler uniquement les pages single job (PAS split-view)
    if (document.body.classList.contains('single-job_listing')) {
      const target = document.querySelector('.company-info .job-company');
      if (target) {
        applyGradient(target);
      }
    }
  }

  // Exécuter uniquement au chargement initial
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();