/**
 * ==========================================================
 * Jobiizy – SplitView Redirect + CTA Button
 * ----------------------------------------------------------
 * - Le clic sur une offre redirige vers la single view
 * - Quand la half-view charge du contenu à droite,
 *   on injecte un bouton "Voir l’offre / Postuler"
 * ==========================================================
 */
(function () {
  console.log("🔥 [Jobiizy] splitview-redirect chargé");

  // ---- Helpers
  const $ = (sel, root = document) => root.querySelector(sel);

  function isSplitView() {
    // On vérifie juste qu’on a la structure split-view
    return !!$('.listing-split-view') && !!$('.listing-details-container');
  }

  if (!isSplitView()) {
    console.log('[Jobiizy] Pas en split-view → arrêt');
    return;
  }

  // ---- 1) Interception clic sur les offres (liste gauche)
  // ---- 1) Interception clic sur les offres (liste gauche)
// ⚠️ Désactivé : on laisse Cariera gérer le split Ajax (refresh colonne droite)
document.addEventListener('click', function (e) {
  const link = e.target.closest('.job_listings .job_listing a[href], .job_listings .job-grid a[href]');
  if (!link) return;

  // IMPORTANT : ne surtout pas faire preventDefault/stopPropagation
  // sinon Cariera ne reçoit plus le clic et ne charge pas la colonne droite.
}, true);

  // ---- 2) Injection bouton dans la colonne droite quand elle a du contenu
  function injectCTA(singleUrl) {
    const right = $('.listing-details-container .listing');
    if (!right) return;

    // éviter doublon
    if ($('.jobiizy-split-cta', right)) return;

    const glow    = document.createElement(‘span’);
    glow.className = ‘chrome-btn-glow’;
    const bg      = document.createElement(‘span’);
    bg.className   = ‘chrome-btn-bg’;
    const content  = document.createElement(‘span’);
    content.className   = ‘chrome-btn-content’;
    content.textContent = "Voir l’offre / Postuler";

    const btn = document.createElement(‘a’);
    btn.href            = singleUrl;
    btn.dataset.jobUrl  = singleUrl;
    btn.className       = ‘button btn chrome-btn chrome-btn-filled’;
    btn.style.cssText   = ‘width:100%;text-align:center;’;
    btn.appendChild(glow);
    btn.appendChild(bg);
    btn.appendChild(content);

    const ctaWrap = document.createElement(‘div’);
    ctaWrap.className = ‘jobiizy-split-cta’;
    ctaWrap.appendChild(btn);
    right.appendChild(ctaWrap);

    console.log('[Jobiizy] CTA injecté');
  }

  // ---- 3) Observer : dès que Cariera injecte du HTML à droite, on ajoute le bouton
  const rightListing = $('.listing-details-container .listing');
  if (!rightListing) return;

  const observer = new MutationObserver(() => {
    // Essaye de récupérer l’URL de l’offre depuis le contenu injecté
    // Cas fréquent : on retrouve un lien canonical / permalink dans un <a>
    const anyLink = rightListing.querySelector('a[href*="/job/"], a[href*="/poste/"], a[href*="/emploi/"]');
    if (!anyLink) return;

    injectCTA(anyLink.href);
  });

  observer.observe(rightListing, { childList: true, subtree: true });

})();