/**
 * JobiiZy Split View - JavaScript Complet
 * Version: 4.0.1 REFACTORISÉ
 * 
 * Architecture modulaire pour une meilleure maintenabilité
 * Remplace les 4 blocs <script> dispersés dans le fichier original
 * 
 * Modules:
 * - Utils: Fonctions utilitaires
 * - Sticky: Gestion de la barre sticky
 * - Search: Recherche AJAX
 * - Autocomplete: Autocomplétion
 * - Cards: Gestion des cards et détails
 * - Mobile: Modal et drawer mobile
 * - Filters: Popup de filtres
 * - InfiniteScroll: Chargement progressif
 * 
 * @author JobiiZy
 * @license GPL-2.0+
 * @requires jQuery
 */

(function($) {
    'use strict';
    
    // Vérifier que jobiizyData est disponible
    if (typeof jobiizyData === 'undefined') {
        console.error('❌ [JobiiZy] jobiizyData non défini. Vérifier wp_localize_script() dans functions.php');
        return;
    }
    
    console.log('🚀 [JobiiZy] Split View v4.0 - Initialisation');
    
jQuery(document).ready(function($) {
    
    // =========================================================
    // CONFIGURATION
    // =========================================================
    const CONFIG = {
        // Mode sticky: 'always', 'scroll', 'compact', 'threshold'
        stickyMode: 'scroll',
        
        // Seuil de scroll pour cacher/montrer
        scrollThreshold: 10,
        
        // Mode de chargement: 'button' ou 'scroll' (infinite scroll)
        loadMode: 'button',
        
        // Debug
        debug: false
    };
    
    function log(...args) {
        if (CONFIG.debug) console.log('[JobiiZy]', ...args);
    }
    
    log('🚀 Initialisation v3.0');
    
    // =========================================================
    // SMART STICKY BAR
    // =========================================================
    const $stickyBar = $('#jobiizy-sticky-bar');
    const $toggleBtn = $('#jobiizy-toggle-search');
	const sentinel = document.getElementById('jobiizy-search-sentinel');

    let lastScrollY = 0;
    let ticking = false;
    let isBarHidden = false;
    let initialBarTop = 0;
    let stickyTop = 0;
    let sentinelObserver = null;
    let lastY = window.scrollY || 0;

function updateCompactMode(){
  const y = window.scrollY || 0;
  const compact = y > 90; // ajuste ton seuil

  $stickyBar.toggleClass('is-compact', compact);
}


function updateCompactSmart(){
  const y = window.scrollY || 0;
  const goingDown = y > lastY;

  // Hauteur réelle de la barre (inclut padding)
  const barHeight = $stickyBar.outerHeight() || 0;

  // Seuils dynamiques
  const past = y > (barHeight + 200);   // ➜ déclenche plus tard
  const backTop = y < (barHeight / 1); // ➜ revient plus tôt

  // log('📏 updateCompactSmart:', { y, barHeight, past, backTop });

  if (past && goingDown) {
    $stickyBar.addClass('is-compact');
  }

  if (backTop) {
    $stickyBar.removeClass('is-compact');
  }

  lastY = y;
}
    function safePositionToggleBtn(){
  if (typeof positionToggleBtn === 'function') positionToggleBtn();
}

function clamp(n, min, max){ return Math.min(max, Math.max(min, n)); }
function lerp(a, b, t){ return a + (b - a) * t; }

function updateCompactProgressive(){
  const y = window.scrollY || 0;

  // Desktop / mobile (breakpoint à ajuster)
  const isMobile = window.matchMedia('(max-width: 768px)').matches;
  // const isMobile = false;
  // Zone de transition (scroll)
  // -> compact commence à start, termine à end
  const start = isMobile ? 90 : 160;
  const end   = isMobile ? 240 : 420;

  // t entre 0 et 1
  const t = clamp((y - start) / (end - start), 0, 1);

  // Valeurs normal -> compact (tu peux ajuster)
  const padY     = lerp(20, isMobile ? 8 : 10, t);     // 20px -> 10px (desktop) / 8px (mobile)
  const radius   = lerp(16, 14, t);
  const formH    = lerp(77, isMobile ? 44 : 46, t);
  const labelOp  = lerp(1, isMobile ? 0 : 0.15, t);    // mobile: labels disparaissent
  const actOp    = lerp(1, isMobile ? 0 : 0.6, t);     // mobile: actions disparaissent (option)
  const transY   = lerp(0, -2, t);                     // petit “lift”
  // shadow un poil plus “tight”
  const shadowA  = lerp(0.30, 0.22, t);

  document.documentElement.style.setProperty('--jb-padY', `${padY.toFixed(1)}px`);
  document.documentElement.style.setProperty('--jb-radius', `${radius.toFixed(1)}px`);
  document.documentElement.style.setProperty('--jb-formH', `${formH.toFixed(1)}px`);
  document.documentElement.style.setProperty('--jb-labelOpacity', `${labelOp.toFixed(2)}`);
  document.documentElement.style.setProperty('--jb-actionsOpacity', `${actOp.toFixed(2)}`);
  document.documentElement.style.setProperty('--jb-translateY', `${transY.toFixed(1)}px`);
  document.documentElement.style.setProperty('--jb-shadow', `0 4px 20px rgba(0,0,0,${shadowA.toFixed(2)})`);

  // Si tu veux un flag booléen en plus (pour cacher des éléments en CSS)
  $stickyBar.toggleClass('is-compact', t > 0.65);

  lastY = y;
}


function showBtn(){
  safePositionToggleBtn();
  $toggleBtn.addClass('visible');
}
function hideBtn(){
  $toggleBtn.removeClass('visible');
}
function initSentinelObserver(){
  if (!sentinel || !('IntersectionObserver' in window)) return;

  // IMPORTANT: on détruit l'ancien observer si on recalcule
  if (sentinelObserver) sentinelObserver.disconnect();

  // On décale le viewport virtuel de -stickyTop :
  // => le moment où le sentinel "sort" correspond à quand il passe sous le header/adminbar
  const topMargin = -(stickyTop || 0);

  sentinelObserver = new IntersectionObserver((entries) => {
    const e = entries[0];

    if (e.isIntersecting) {
      // On est en haut
      showBar();
      hideBtn();
    } else {
      // On a scrollé au-delà du point de départ
      hideBar();
      showBtn();
    }
  }, {
    root: null,
    threshold: 0,
    rootMargin: `${topMargin}px 0px 0px 0px`
  });

  sentinelObserver.observe(sentinel);
  log('👁️ Sentinel observer OK. rootMarginTop=', topMargin);
}

const $badge = $('#jobiizy-toggle-badge');
const $listCol = $('.jobiizy-listings-column').first();

function setToggleState(open){
  $toggleBtn
    .attr('aria-expanded', open ? 'true' : 'false')
    .toggleClass('is-open', open)
    .attr('title', open ? 'Fermer la recherche' : 'Afficher la recherche');

  const $icon = $toggleBtn.find('i');
  $icon.removeClass('la-sliders-h la-times').addClass(open ? 'la-times' : 'la-sliders-h');
}

function countActiveFilters(){
  const params = new URLSearchParams(window.location.search);
  let count = 0;
  if (params.get('search_keywords')) count++;
  if (params.get('search_location')) count++;
  if (params.get('search_categories')) count++;
  if (params.getAll('filter_job_type[]').length > 0) count++;
  return count;
}

function updateBadge(){
  const count = countActiveFilters();
  if (count > 0) $badge.text(count).show();
  else $badge.hide();
}

// Place le bouton à droite de la colonne listings (sans masquer)
function positionToggleBtn(){
  if (!$listCol.length) return;

  const rect = $listCol.get(0).getBoundingClientRect();
  const gap = 12;
  let left = Math.round(rect.right + gap);

  const btnW = 52;
  const maxLeft = window.innerWidth - btnW - 12;
  if (left > maxLeft) left = maxLeft;

  // $toggleBtn.css({ left: left + 'px', right: 'auto' });
  $toggleBtn.css({ right: 'auto' });
}

function calculateStickyTop() {
    let top = 0;
    
    // 1) Admin bar WordPress
    const $adminBar = $('#wpadminbar');
    if ($adminBar.length) {
        top += $adminBar.outerHeight();
        log('📏 Admin bar:', $adminBar.outerHeight() + 'px');
    }
    
    // 2) Header principal - utilise simplement 'header' (94px détecté dans vos tests)
    const $header = $('header').first();
    
    if ($header.length) {
        const headerHeight = $header.outerHeight();
        top += headerHeight;
        log('📏 Header principal:', headerHeight + 'px');
    } else {
        // Fallback si header non trouvé
        top += 94;
        log('📏 Header non trouvé, utilisation valeur par défaut: 94px');
    }

    stickyTop = Math.max(top, 0);
    log('✅ Top final calculé:', stickyTop + 'px');
}
    
    function applyStickyTop() {
        // 🆕 Vérification de sécurité - Skip si la barre n'existe pas (mobile)
        if (!$stickyBar || !$stickyBar.length || !$stickyBar[0]) {
            // log('⏭️ applyStickyTop skip (barre non trouvée - probablement en mobile)');
            return;
        }
        
        const stickyTopNew = stickyTop + 100;
            log('✅ Top final appliStickyTop calculé:', stickyTopNew + 'px');

        document.documentElement.style.setProperty('--jobiizy-sticky-top', stickyTopNew + 'px');
        $stickyBar.css('top', stickyTopNew + 'px');
        
        const rect = $stickyBar[0].getBoundingClientRect();
        initialBarTop = rect.top + window.scrollY;
    }
    
    function fixParentOverflow() {
        // 🆕 Vérification de sécurité - Skip si la barre n'existe pas (mobile)
        if (!$stickyBar || !$stickyBar.length || !$stickyBar[0]) {
            // log('⏭️ fixParentOverflow skip (barre non trouvée)');
            return;
        }
        
        let parent = $stickyBar[0].parentElement;
        let count = 0;
        
        while (parent && parent !== document.documentElement) {
            const style = window.getComputedStyle(parent);
            if (style.overflow !== 'visible' || style.overflowY !== 'visible') {
                parent.style.setProperty('overflow', 'visible', 'important');
                parent.style.setProperty('overflow-x', 'visible', 'important');
                parent.style.setProperty('overflow-y', 'visible', 'important');
                count++;
            }
            parent = parent.parentElement;
        }
        
        log('🔧 Overflow fixé sur', count, 'éléments');
    }
    
function showBar() {
  $stickyBar.removeClass('sticky-hidden').addClass('sticky-visible');
  isBarHidden = false;
  
  $stickyBar.removeClass('is-compact');

  $toggleBtn.removeClass('visible');
  setToggleState(true);
  updateBadge();
}

function hideBar() {
  $stickyBar.addClass('sticky-hidden').removeClass('sticky-visible');
  isBarHidden = true;

  positionToggleBtn();
  $toggleBtn.addClass('visible');
  setToggleState(false);
  updateBadge();
}
    
    function handleScroll() {
         const currentScrollY = window.scrollY;
        const scrollDelta = currentScrollY - lastScrollY;
        const isScrollingDown = scrollDelta > 0;
        const isScrollingUp = scrollDelta < 0;
        const isPastThreshold = Math.abs(scrollDelta) > CONFIG.scrollThreshold;
        const isStuck = currentScrollY > initialBarTop;
        
        $stickyBar.toggleClass('is-stuck', isStuck);
        
        switch (CONFIG.stickyMode) {
            case 'scroll':
                if (currentScrollY < 100) {
                    showBar();
                } else if (isScrollingDown && isPastThreshold && !isBarHidden) {
                    hideBar();
                } else if (isScrollingUp && isPastThreshold && isBarHidden) {
                    showBar();
                }
                break;
                
            case 'compact':
                $stickyBar.toggleClass('is-compact', isStuck);
                break;
                
            case 'always':
            default:
                break;
        }
        
        lastScrollY = currentScrollY;
    }
    
    // Toggle manuel
 /* 
   $toggleBtn.on('click', function() {
        if (isBarHidden) {
            showBar();
            // $('html, body').animate({ scrollTop: 0 }, 300);
        }
    });
 */
/* CLAUDE 260123  */
// Ajouter dans la section jQuery(document).ready()

// Modal recherche mobile
console.log('🚀 [JobiiZy] Init mobile search');
/* 
    function attachMobileSearchHandlers() {
        console.log('📱 Attachement des handlers mobile...');
        
        var $mobileBtn = $('#jobiizy-mobile-search-btn, .jobiizy-mobile-search-in-header');
        var $modal = $('#jobiizy-mobile-search-modal');
        var $closeBtn = $('#jobiizy-mobile-close-search');
        var $submitBtn = $('#jobiizy-mobile-search-submit');
        
        console.log('Boutons trouvés:', {
            mobileBtn: $mobileBtn.length,
            modal: $modal.length,
            closeBtn: $closeBtn.length,
            submitBtn: $submitBtn.length
        });
        
        // Retirer les anciens événements pour éviter les doublons
        $mobileBtn.off('click.mobileSearch');
        $closeBtn.off('click.mobileSearch');
        $submitBtn.off('click.mobileSearch');
        $modal.off('click.mobileSearch');
        
        // OUVRIR LA MODAL
        $mobileBtn.on('click.mobileSearch', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🔍 [JobiiZy] CLIC DÉTECTÉ sur bouton mobile !');
            
            // Forcer l'affichage
            $modal.css({
                'display': 'flex',
                'transform': 'translateY(0)'
            }).addClass('show');
            
            $('body').css('overflow', 'hidden');
            
            // Synchroniser les valeurs
            var desktopKeywords = $('#jobiizy-keywords').val() || '';
            var desktopLocation = $('#jobiizy-location').val() || '';
            
            $('#jobiizy-mobile-keywords').val(desktopKeywords);
            $('#jobiizy-mobile-location').val(desktopLocation);
            
            console.log('✅ Modal ouverte !');
        });
        
        // FERMER LA MODAL
        $closeBtn.on('click.mobileSearch', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('❌ [JobiiZy] Fermeture modal');
            
            $modal.css({
                'display': 'none',
                'transform': 'translateY(100%)'
            }).removeClass('show');
            
            $('body').css('overflow', '');
        });
        
        // FERMER AU CLIC SUR LE FOND
        $modal.on('click.mobileSearch', function(e) {
            if ($(e.target).is('#jobiizy-mobile-search-modal')) {
                console.log('❌ Clic sur overlay');
                $(this).css('display', 'none').removeClass('show');
                $('body').css('overflow', '');
            }
        });
        
        // SOUMETTRE LA RECHERCHE
        $submitBtn.on('click.mobileSearch', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🔎 [JobiiZy] Soumission recherche mobile');
            
            // Copier les valeurs
            var mobileKeywords = $('#jobiizy-mobile-keywords').val();
            var mobileLocation = $('#jobiizy-mobile-location').val();
            
            $('#jobiizy-keywords').val(mobileKeywords);
            $('#jobiizy-location').val(mobileLocation);
            
            // Fermer la modal
            $modal.css('display', 'none').removeClass('show');
            $('body').css('overflow', '');
            
            // Lancer la recherche
            if (typeof doLiveSearch === 'function') {
                doLiveSearch();
            } else {
                // Fallback : recharger avec paramètres
                var url = new URL(window.location.href);
                if (mobileKeywords) url.searchParams.set('search_keywords', mobileKeywords);
                else url.searchParams.delete('search_keywords');
                if (mobileLocation) url.searchParams.set('search_location', mobileLocation);
                else url.searchParams.delete('search_location');
                window.location.href = url.toString();
            }
        });
        
        // OUVRIR LES FILTRES AVANCÉS
        $('.jobiizy-mobile-open-filters').on('click.mobileSearch', function(e) {
            e.preventDefault();
            $modal.css('display', 'none').removeClass('show');
            $('#jobiizy-filters-popup').addClass('show');
            $('body').css('overflow', 'hidden');
        });
        
        console.log('✅ Handlers mobile attachés');
    }
 */

   // Attacher immédiatement
 //   attachMobileSearchHandlers();
    
    // Ré-attacher après un délai (au cas où le DOM change)
  //  setTimeout(attachMobileSearchHandlers, 1000);

   // ========================================
    // DEBUG (À RETIRER EN PRODUCTION)
    // ========================================
    console.log('=== DEBUG MOBILE SEARCH ===');
    console.log('Width:', $(window).width());
    console.log('Bouton exists:', $('#jobiizy-mobile-search-btn').length > 0);
    console.log('Modal exists:', $('#jobiizy-mobile-search-modal').length > 0);
    console.log('Modal display:', $('#jobiizy-mobile-search-modal').css('display'));
    console.log('==========================');
    
    // Test clic programmatique
    window.testMobileSearch = function() {
        console.log('🧪 TEST: Simulation de clic...');
        $('#jobiizy-mobile-search-btn').trigger('click');
    };
    console.log('💡 Tapez testMobileSearch() dans la console pour tester');

$('#jobiizy-mobile-search-btn').on('click', function() {
    $('#jobiizy-mobile-search-modal').addClass('show');
    $('body').css('overflow', 'hidden');
    
    // Synchroniser les valeurs
    $('#jobiizy-mobile-keywords').val($('#jobiizy-keywords').val());
    $('#jobiizy-mobile-location').val($('#jobiizy-location').val());
});

$('#jobiizy-mobile-close-search').on('click', function() {
    $('#jobiizy-mobile-search-modal').removeClass('show');
    $('body').css('overflow', '');
});

$('#jobiizy-mobile-search-submit').on('click', function() {
    // Copier les valeurs vers les champs principaux
    $('#jobiizy-keywords').val($('#jobiizy-mobile-keywords').val());
    $('#jobiizy-location').val($('#jobiizy-mobile-location').val());
    
    // Fermer la modal
    $('#jobiizy-mobile-search-modal').removeClass('show');
    $('body').css('overflow', '');
    
    // Lancer la recherche
    doLiveSearch();
});

$('.jobiizy-mobile-open-filters').on('click', function() {
    $('#jobiizy-mobile-search-modal').removeClass('show');
    $('#jobiizy-filters-popup').addClass('show');
});


/* CLAUDE 260123  */

 
 
 $toggleBtn.on('click', function(e) {
  e.preventDefault();
  if (isBarHidden) showBar();
  else hideBar();
});
    
    // Scroll listener
    $(window).on('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    });
    
    // Init sticky
    calculateStickyTop();
    applyStickyTop();
    fixParentOverflow();
updateBadge();
positionToggleBtn();
// ✅ seulement maintenant
initSentinelObserver();
// init + listeners (scroll léger)
// updateCompactMode();
updateCompactSmart();
// updateCompactProgressive()
window.addEventListener('scroll', updateCompactSmart, { passive: true });
window.addEventListener('resize', updateCompactSmart);

$(window).on('resize', function(){
  calculateStickyTop();
  applyStickyTop();
  positionToggleBtn();
});    
    // Recalculer après chargement complet
    $(window).on('load', function() {
        setTimeout(function() {
            calculateStickyTop();
            applyStickyTop();
            fixParentOverflow();
        }, 500);
    });
    
    // API globale
    window.JobiizySmartSticky = {
        setMode: function(mode) {
            CONFIG.stickyMode = mode;
            log('Mode changé:', mode);
            if (mode === 'always') showBar();
        },
        show: showBar,
        hide: hideBar,
        toggle: function() { isBarHidden ? showBar() : hideBar(); },
        recalculate: function() {
            calculateStickyTop();
            applyStickyTop();
        }
    };
    
    log('✅ Smart Sticky initialisé, mode:', CONFIG.stickyMode);
    
    
    // =========================================================
    // INFINITE SCROLL / LOAD MORE
    // =========================================================
    const $listingsColumn = $('#jobiizy-listings-scroll');
    const $loadMoreContainer = $('#jobiizy-load-more');
    const $loadMoreBtn = $('#jobiizy-load-more-btn');
    const $endMessage = $('#jobiizy-end-message');
    const $progressFill = $('#jobiizy-progress-fill');
    const $loadedCount = $('#jobiizy-loaded-count');
    const $progressPercent = $('#jobiizy-progress-percent');
    
    let currentPage = parseInt($listingsColumn.data('page')) || 1;
    const maxPages = parseInt($listingsColumn.data('max-pages')) || 1;
    const totalJobs = parseInt($listingsColumn.data('total')) || 0;
    const perPage = parseInt($listingsColumn.data('per-page')) || 10;
    let isLoading = false;
    let loadedJobs = Math.min(perPage, totalJobs);
    
    log('📊 Pagination:', { currentPage, maxPages, totalJobs, perPage });
    
    // Cacher le message de fin au départ
    $endMessage.hide();
    
    if (maxPages <= 1) {
        log('📄 Une seule page');
        $loadMoreContainer.hide();
        if (totalJobs > 0 && totalJobs <= perPage) {
            $endMessage.show();
        }
    }
    
    function updateProgress() {
        const percent = Math.round((loadedJobs / totalJobs) * 100);
        $progressFill.css('width', percent + '%');
        $loadedCount.text(loadedJobs);
        $progressPercent.text(percent + '%');
    }
    
    function loadMoreJobs() {
     //        log('🔍 loadMoreJobs appelé - isLoading:', isLoading, 'currentPage:', currentPage, 'maxPages:', maxPages);
    log('🔍 loadMoreJobs - isLoading:', isLoading, 'currentPage:', currentPage, 'maxPages:', maxPages);

    // Reset forcé pour déboguer
    isLoading = false;
    if (currentPage >= maxPages) {
        $('#jobiizy-load-more').hide();
        $('#jobiizy-end-message').show();
        return;
    }

        if (isLoading || currentPage >= maxPages) {
            if (currentPage >= maxPages) {
                $loadMoreContainer.hide();
                $endMessage.show();
            }
            return;
        }
        
        isLoading = true;
        $loadMoreBtn.addClass('loading').prop('disabled', true);
        
        const nextPage = currentPage + 1;
        log('📥 Chargement page', nextPage);
        
        const urlParams = new URLSearchParams(window.location.search);
        
        $.ajax({
            url: jobiizyData.ajaxurl,
            data: {
                action: 'jobiizy_load_more_jobs',
                page: nextPage,
                per_page: perPage,
                search_keywords: urlParams.get('search_keywords') || '',
                search_location: urlParams.get('search_location') || '',
                search_categories: urlParams.get('search_categories') || '',
                filter_job_type: urlParams.get('filter_job_type') || '',
                nonce: jobiizyData.nonce
            },
 success: function(response) {
    log('📦 AJAX success, response:', response);
    log('📦 response.data:', response.data);
    if (response.success && response.data.html) {
       log('📦 HTML reçu, longueur:', response.data.html.length);
        log('📦 $loadMoreContainer dans DOM ?', $.contains(document, $loadMoreContainer[0]));
                const $newCards = $(response.data.html);
        $newCards.addClass('newly-loaded');
        
        // 🔥 Sélection fraîche au lieu de la référence initiale
        $('#jobiizy-load-more').before($newCards);
        
        currentPage = nextPage;
        loadedJobs += response.data.count;
        $('#jobiizy-listings-scroll').data('page', currentPage);
        
        updateProgress();
        initCardClicks();
        
        if (currentPage >= maxPages) {
            $('#jobiizy-load-more').hide();
            $('#jobiizy-end-message').show();
        }
        
        log('✅ Chargé', response.data.count, 'offres');
    }
},
            error: function(xhr, status, error) {
        log('❌ AJAX error:', status, error, xhr.responseText);
            },
            complete: function(xhr, status) {
                log('🏁 AJAX complete, status:', status, 'HTTP:', xhr.status);
                isLoading = false;
                $loadMoreBtn.removeClass('loading').prop('disabled', false);
            }
        });
    }
     // 🆕 Exposer globalement
    // window.loadMoreJobs = loadMoreJobs;
    // window.initInfiniteScroll = initInfiniteScroll;   
    // Mode bouton
    if (CONFIG.loadMode === 'button') {
        $loadMoreBtn.on('click', loadMoreJobs);
    }
    

function initInfiniteScroll() {
    var $scroll = $('#jobiizy-listings-scroll');
    if (!$scroll.length) return;

    if (window.innerWidth <= 1024) {
        // Mobile : scroll sur window
        $(window).off('scroll.infinite').on('scroll.infinite', function() {
            if (isLoading) return;
            var scrollTop = $(window).scrollTop();
            var scrollHeight = document.documentElement.scrollHeight;
            var clientHeight = $(window).height();
            
            if (scrollTop + clientHeight >= scrollHeight - 200) {
                loadMoreJobs();
            }
        });
    } else {
        // Desktop : scroll sur le conteneur
        $scroll.off('scroll.infinite').on('scroll.infinite', function() {
            if (isLoading) return;
            var scrollTop = $(this).scrollTop();
            var scrollHeight = $(this)[0].scrollHeight;
            var clientHeight = $(this).innerHeight();
            
            if (scrollTop + clientHeight >= scrollHeight - 200) {
                loadMoreJobs();
            }
        });
    }
    log('✅ Infinite scroll bindé, mobile:', window.innerWidth <= 1024);
}
// test
if (CONFIG.loadMode === 'scroll') {
    $loadMoreBtn.hide();
    initInfiniteScroll();
}

// Exposer globalement pour doLiveSearch()
// window.initInfiniteScroll = initInfiniteScroll;

    
    // =========================================================
    // CARD CLICKS
    // =========================================================
    function initCardClicks() {
        $('.jobiizy-modern-card').off('click').on('click', function() {
            const $card = $(this);
            const postId = $card.data('post-id');
            
            $('.jobiizy-modern-card').removeClass('active');
            $card.addClass('active');
            
            log('📋 Card sélectionnée:', postId);
            
            // TODO: Charger les détails via AJAX
        });
    }
    
    initCardClicks();
    
    
    // =========================================================
    // POPUP FILTRES
    // =========================================================
    const $filtersPopup = $('#jobiizy-filters-popup');
    
    $('#jobiizy-open-filters').on('click', function() {
        $filtersPopup.addClass('show');
        $('body').css('overflow', 'hidden');
    });
    
    function closeFiltersPopup() {
        $filtersPopup.removeClass('show');
        $('body').css('overflow', '');
    }
    
    $('.jobiizy-close-filters').on('click', closeFiltersPopup);
    
    $filtersPopup.on('click', function(e) {
        if ($(e.target).is('#jobiizy-filters-popup')) {
            closeFiltersPopup();
        }
    });
    
    $(document).on('keyup', function(e) {
        if (e.key === 'Escape') closeFiltersPopup();
    });
    
    $('.jobiizy-btn-reset').on('click', function() {
        $('.jobiizy-filter-form')[0].reset();
    });
    
    $('.jobiizy-btn-apply').on('click', function() {
        window.location.search = $('.jobiizy-filter-form').serialize();
    });
   
    
    
    log('🎉 Initialisation complète');

});



jQuery(document).ready(function($) {
	console.log('🚀 [JobiiZy] Template avec recherche AJAX et autocomplétion');
	var ajaxurl = jobiizyData.ajaxurl;
	var searchTimeout = null;
	var currentRequest = null;
    // ========================================
    // UTILITAIRES MOBILE - À PLACER EN PREMIER
    // ========================================
    function isMobile() {
        return window.innerWidth <= 1024;
    }

    // ========================================
    // RECHERCHE AJAX (DÉFINIR AVANT LA MODAL)
    // ========================================
    function doLiveSearch() {
        var keywords = $('#jobiizy-keywords').val().trim();
        var location = $('#jobiizy-location').val().trim();
        
        console.log('🔎 [JobiiZy] Recherche AJAX:', { keywords, location });
        
        // Fermer dropdowns
        $('.jobiizy-autocomplete-dropdown').removeClass('show');
        
        // Ajouter loader
        $('#jobiizy-search-btn').addClass('loading');
        $('.jobiizy-listings-column').css('opacity', '0.5');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'jobiizy_live_search',
                search_keywords: keywords,
                search_location: location
            },
            success: function(response) {
                $('#jobiizy-search-btn').removeClass('loading');
                $('.jobiizy-listings-column').css('opacity', '1');
                
                if (response.success) {
                    console.log('✅ [JobiiZy] Résultats:', response.data.count);
                    
                    // Mettre à jour le compteur
                    $('.jobiizy-job-count').html('<i class="las la-briefcase"></i> Nous avons trouvé <strong>' + response.data.count + '</strong> offre' + (response.data.count > 1 ? 's' : '') + ' d\'emploi pour vous !');
                    
                    // Mettre à jour la liste
                    $('.jobiizy-listings-column').html(response.data.html);
                    
                    // Réinitialiser le panneau détail
                    $('.jobiizy-detail-content').hide();
                    
                    if (!isMobile()) {
                        $('.jobiizy-empty-state').fadeIn(200);
                    }
                    
                    // Mettre à jour l'URL sans recharger
                    var newUrl = new URL(window.location.href);
                    if (keywords) newUrl.searchParams.set('search_keywords', keywords);
                    else newUrl.searchParams.delete('search_keywords');
                    if (location) newUrl.searchParams.set('search_location', location);
                    else newUrl.searchParams.delete('search_location');
                    history.pushState({}, '', newUrl);
                    
                    // Rebind le clic sur les cards
                    bindCardClicks();
                    
                    // Rebind l'infinite scroll
                    if (isMobile()) {
                        initInfiniteScroll();
                    }
                    
                    // Auto-clic première offre uniquement en desktop
                    setTimeout(function() {
                        if (!isMobile()) {
                            $('.jobiizy-modern-card').first().trigger('click');
                        }
                    }, 300);
                    
                    // Mettre à jour les filtres actifs
                    countActiveFilters();
                    displayActiveFilters();
                } else {
                    console.error('❌ Erreur dans la réponse');
                }
            },
            error: function(xhr, status, error) {
                $('#jobiizy-search-btn').removeClass('loading');
                $('.jobiizy-listings-column').css('opacity', '1');
                console.error('❌ [JobiiZy] Erreur recherche:', error);
                alert('Erreur lors de la recherche. Veuillez réessayer.');
            }
        });
    }
    
    // Exposer la fonction globalement pour la modal
    window.doLiveSearch = doLiveSearch; 
    // ========================================
    // MODAL RECHERCHE MOBILE
    // ========================================
    function attachMobileSearchHandlers() {
        console.log('📱 Attachement des handlers mobile...');
        
        var $mobileBtn = $('#jobiizy-mobile-search-btn, .jobiizy-mobile-search-in-header');
        var $modal = $('#jobiizy-mobile-search-modal');
        var $closeBtn = $('#jobiizy-mobile-close-search');
        var $submitBtn = $('#jobiizy-mobile-search-submit');
        
        // Retirer les anciens événements
        $mobileBtn.off('click.mobileSearch');
        $closeBtn.off('click.mobileSearch');
        $submitBtn.off('click.mobileSearch');
        
        // OUVRIR LA MODAL
        $mobileBtn.on('click.mobileSearch', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🔍 [JobiiZy] Ouverture modal mobile');
            
            $modal.css('display', 'flex').addClass('show');
            $('body').css('overflow', 'hidden');
            
            // Synchroniser les valeurs DEPUIS les champs desktop (cachés)
            var desktopKeywords = $('#jobiizy-keywords').val() || '';
            var desktopLocation = $('#jobiizy-location').val() || '';
            
            $('#jobiizy-mobile-keywords').val(desktopKeywords);
            $('#jobiizy-mobile-location').val(desktopLocation);
            
            console.log('✅ Valeurs synchronisées:', { keywords: desktopKeywords, location: desktopLocation });
        });
        
        // FERMER LA MODAL
        $closeBtn.on('click.mobileSearch', function(e) {
            e.preventDefault();
            $modal.css('display', 'none').removeClass('show');
            $('body').css('overflow', '');
        });
        
        // SOUMETTRE LA RECHERCHE
        $submitBtn.on('click.mobileSearch', function(e) {
            e.preventDefault();
            
            console.log('🔎 [JobiiZy] Soumission recherche mobile');
            
            // Copier les valeurs VERS les champs desktop
            var mobileKeywords = $('#jobiizy-mobile-keywords').val() || '';
            var mobileLocation = $('#jobiizy-mobile-location').val() || '';
            
            console.log('📝 Copie des valeurs:', { keywords: mobileKeywords, location: mobileLocation });
            
            $('#jobiizy-keywords').val(mobileKeywords);
            $('#jobiizy-location').val(mobileLocation);
            
            // Fermer la modal
            $modal.css('display', 'none').removeClass('show');
            $('body').css('overflow', '');
            
            // Lancer la recherche
            console.log('🚀 Appel de doLiveSearch()');
            
            if (typeof window.doLiveSearch === 'function') {
                window.doLiveSearch();
            } else if (typeof doLiveSearch === 'function') {
                doLiveSearch();
            } else {
                console.error('❌ Fonction doLiveSearch non trouvée, rechargement...');
                // Fallback : recharger avec paramètres
                var url = new URL(window.location.href);
                if (mobileKeywords) url.searchParams.set('search_keywords', mobileKeywords);
                else url.searchParams.delete('search_keywords');
                if (mobileLocation) url.searchParams.set('search_location', mobileLocation);
                else url.searchParams.delete('search_location');
                window.location.href = url.toString();
            }
        });
        
        console.log('✅ Handlers mobile attachés');
    }
    
    // Attacher les handlers
    attachMobileSearchHandlers();
    setTimeout(attachMobileSearchHandlers, 1000);
    
    // ========================================
    // INFINITE SCROLL MOBILE
    // ========================================
    // Initialiser l'infinite scroll
 // Rebind l'infinite scroll
/* 
if (typeof window.initInfiniteScroll === 'function') {
    window.initInfiniteScroll();
}    
*/
    // ========================================
    // BOUTON RECHERCHE DESKTOP (NE PAS TOUCHER)
    // ========================================
    $('#jobiizy-search-btn').on('click', function() {
        console.log('🖥️ Bouton recherche desktop cliqué');
        doLiveSearch();
    });
    
    // Enter dans les champs desktop
    $('#jobiizy-keywords, #jobiizy-location').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            var $dropdown = $(this).siblings('.jobiizy-autocomplete-dropdown');
            if (!$dropdown.hasClass('show') || !$dropdown.find('.selected').length) {
                doLiveSearch();
            }
        }
    });    
    
    
       
    function openDrawer() {
        if (!isMobile()) return;
        
        $('.jobiizy-detail-column').addClass('open');
        $('#jobiizy-drawer-overlay').addClass('show');
        $('body').css('overflow', 'hidden');
    }
    
    function closeDrawer() {
        $('.jobiizy-detail-column').removeClass('open');
        $('#jobiizy-drawer-overlay').removeClass('show');
        $('body').css('overflow', '');
    }
    
    // Fermer le drawer
    $('#jobiizy-drawer-close, #jobiizy-drawer-overlay').on('click', closeDrawer);
    
    // Swipe down pour fermer (optionnel)
    let startY = 0;
    $('.jobiizy-drawer-handle').on('touchstart', function(e) {
        startY = e.touches[0].clientY;
    });
    
    $('.jobiizy-drawer-handle').on('touchmove', function(e) {
        let deltaY = e.touches[0].clientY - startY;
        if (deltaY > 100) {
            closeDrawer();
        }
    });
    
    // ========================================
    // SPLIT VIEW - CLIC SUR CARDS (MODIFIÉ)
    // ========================================
    function bindCardClicks() {
        $('.jobiizy-modern-card').off('click').on('click', function(e) {
            e.preventDefault();
            var $card = $(this);
            var postId = $card.data('post-id');
            var postUrl = $card.data('post-url');
            
            console.log('🖱️ [JobiiZy] Card cliquée:', postId);
            
            $('.jobiizy-modern-card').removeClass('active');
            $card.addClass('active');
            
            // En mobile, ouvrir le drawer
            if (isMobile()) {
                openDrawer();
            } else {
                $('.jobiizy-empty-state').fadeOut(200);
            }
            
            $('.jobiizy-detail-content').html('<div style="text-align:center;padding:60px;"><i class="las la-spinner la-spin" style="font-size:48px;color:#60a5fa;"></i><p style="color:#94a3b8;margin-top:16px;">Chargement...</p></div>').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'jobiizy_load_job_details', post_id: postId },
                success: function(response) {
                    if (response.success) {
                        console.log('✅ [JobiiZy] Détails chargés');
                        $('.jobiizy-detail-content').html(response.data);
                    } else {
                        loadInIframe(postUrl);
                    }
                },
                error: function() { 
                    loadInIframe(postUrl); 
                }
            });
        });
    }
    
    function loadInIframe(url) {
        $('.jobiizy-detail-content').html('<iframe src="' + url + '" style="width:100%;height:calc(100vh - 320px);border:none;border-radius:12px;"></iframe>');
    }	
	// ========================================
	// AUTOCOMPLÉTION MOTS-CLÉS
	// ========================================
	// Autocomplétion Keywords - Desktop ET Mobile
	$('#jobiizy-keywords, #jobiizy-mobile-keywords').on('input', function() {
		var query = $(this).val().trim();
		var $input = $(this);
		
		// Trouver le dropdown associé (desktop ou mobile)
		var $dropdown;
		if ($input.attr('id') === 'jobiizy-mobile-keywords') {
			$dropdown = $input.siblings('.jobiizy-autocomplete-dropdown');
			if ($dropdown.length === 0) {
				$dropdown = $input.closest('.jobiizy-mobile-search-field').find('.jobiizy-autocomplete-dropdown');
			}
		} else {
			$dropdown = $('.jobiizy-keywords-dropdown');
		}
		
		var $loader = $dropdown.find('.jobiizy-autocomplete-loader');
		var $results = $dropdown.find('.jobiizy-autocomplete-results');
		
		clearTimeout(searchTimeout);
		
		if (query.length < 2) {
			$dropdown.removeClass('show');
			return;
		}
		
		searchTimeout = setTimeout(function() {
			console.log('🔍 [JobiiZy] Recherche mots-clés:', query);
			
			$dropdown.addClass('show');
			$loader.addClass('show');
			$results.removeClass('show').empty();
			
			// Annuler requête précédente
			if (currentRequest) currentRequest.abort();
			
			currentRequest = $.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'jobiizy_autocomplete_keywords',
					query: query
				},
				success: function(response) {
					$loader.removeClass('show');
					
					if (response.success && response.data.length > 0) {
						var html = '';
						
						// Offres d'emploi
						var jobs = response.data.filter(item => item.type === 'job');
						if (jobs.length > 0) {
							html += '<div class="jobiizy-autocomplete-section">Offres d\'emploi</div>';
							jobs.forEach(function(item) {
								html += '<div class="jobiizy-autocomplete-item" data-value="' + escapeHtml(item.title) + '" data-url="' + item.url + '">';
								html += '<div class="item-icon">';
								if (item.logo) {
									html += '<img src="' + item.logo + '" alt="">';
								} else {
									html += '<i class="las la-briefcase"></i>';
								}
								html += '</div>';
								html += '<div class="item-content">';
								html += '<div class="item-title">' + highlightMatch(item.title, query) + '</div>';
								html += '<div class="item-subtitle">';
								if (item.company) html += '<span>' + item.company + '</span>';
								if (item.location) html += '<span><i class="las la-map-marker"></i>' + item.location + '</span>';
								html += '</div>';
								html += '</div>';
								if (item.type_name) html += '<span class="item-badge">' + item.type_name + '</span>';
								html += '</div>';
							});
						}
						
						// Entreprises
						var companies = response.data.filter(item => item.type === 'company');
						if (companies.length > 0) {
							html += '<div class="jobiizy-autocomplete-section">Entreprises</div>';
							companies.forEach(function(item) {
								html += '<div class="jobiizy-autocomplete-item" data-value="' + escapeHtml(item.title) + '">';
								html += '<div class="item-icon">';
								if (item.logo) {
									html += '<img src="' + item.logo + '" alt="">';
								} else {
									html += '<i class="las la-building"></i>';
								}
								html += '</div>';
								html += '<div class="item-content">';
								html += '<div class="item-title">' + highlightMatch(item.title, query) + '</div>';
								if (item.jobs_count) html += '<div class="item-subtitle">' + item.jobs_count + ' offre(s)</div>';
								html += '</div>';
								html += '</div>';
							});
						}
						
						$results.html(html).addClass('show');
					} else {
						$results.html('<div class="jobiizy-autocomplete-empty"><i class="las la-search"></i>Aucun résultat pour "' + escapeHtml(query) + '"</div>').addClass('show');
					}
				},
				error: function() {
					$loader.removeClass('show');
					$dropdown.removeClass('show');
				}
			});
		}, 300);
	});
	
	// ========================================
	// AUTOCOMPLÉTION LOCALISATION
	// ========================================
	// Autocomplétion Location - Desktop ET Mobile
	$('#jobiizy-location, #jobiizy-mobile-location').on('input', function() {
		var query = $(this).val().trim();
		var $input = $(this);
		
		// Trouver le dropdown associé (desktop ou mobile)
		var $dropdown;
		if ($input.attr('id') === 'jobiizy-mobile-location') {
			$dropdown = $input.siblings('.jobiizy-autocomplete-dropdown');
			if ($dropdown.length === 0) {
				$dropdown = $input.closest('.jobiizy-mobile-search-field').find('.jobiizy-autocomplete-dropdown');
			}
		} else {
			$dropdown = $('.jobiizy-location-dropdown');
		}
		
		var $loader = $dropdown.find('.jobiizy-autocomplete-loader');
		var $results = $dropdown.find('.jobiizy-autocomplete-results');
		
		clearTimeout(searchTimeout);
		
		if (query.length < 2) {
			$dropdown.removeClass('show');
			return;
		}
		
		searchTimeout = setTimeout(function() {
			console.log('📍 [JobiiZy] Recherche localisation:', query);
			
			$dropdown.addClass('show');
			$loader.addClass('show');
			$results.removeClass('show').empty();
			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'jobiizy_autocomplete_location',
					query: query
				},
				success: function(response) {
					$loader.removeClass('show');
					
					if (response.success && response.data.length > 0) {
						var html = '';
						response.data.forEach(function(item) {
							html += '<div class="jobiizy-autocomplete-item" data-value="' + escapeHtml(item.name) + '">';
							html += '<div class="item-icon"><i class="las la-map-marker"></i></div>';
							html += '<div class="item-content">';
							html += '<div class="item-title">' + highlightMatch(item.name, query) + '</div>';
							if (item.count) html += '<div class="item-subtitle">' + item.count + ' offre(s)</div>';
							html += '</div>';
							html += '</div>';
						});
						$results.html(html).addClass('show');
					} else {
						$results.html('<div class="jobiizy-autocomplete-empty"><i class="las la-map-marker"></i>Aucune localisation trouvée</div>').addClass('show');
					}
				},
				error: function() {
					$loader.removeClass('show');
					$dropdown.removeClass('show');
				}
			});
		}, 300);
	});
	
	// Clic sur un item d'autocomplétion
	$(document).on('click', '.jobiizy-autocomplete-item', function() {
		var value = $(this).data('value');
		var url = $(this).data('url');
		var $parent = $(this).closest('.jobiizy-search-field, .jobiizy-mobile-search-field');
		
		// Si c'est un lien direct vers une offre
		if (url) {
			// Charger l'offre dans le panneau détail
			var postId = url.match(/\/job\/([^\/]+)/);
			if (postId) {
				// Trouver la card correspondante et la cliquer
				var $card = $('.jobiizy-modern-card[data-post-url*="' + postId[1] + '"]');
				if ($card.length) {
					$card.trigger('click');
				}
			}
		}
		
		// Remplir le champ (desktop ou mobile)
		var $input = $parent.find('input');
		$input.val(value);
		
		// Synchroniser avec l'autre champ (mobile <-> desktop)
		if ($input.attr('id') === 'jobiizy-mobile-keywords') {
			$('#jobiizy-keywords').val(value);
		} else if ($input.attr('id') === 'jobiizy-mobile-location') {
			$('#jobiizy-location').val(value);
		} else if ($input.attr('id') === 'jobiizy-keywords') {
			$('#jobiizy-mobile-keywords').val(value);
		} else if ($input.attr('id') === 'jobiizy-location') {
			$('#jobiizy-mobile-location').val(value);
		}
		
		$parent.find('.jobiizy-autocomplete-dropdown').removeClass('show');
		
		// Lancer la recherche (sauf si on est dans la modal mobile)
		if (!$(this).closest('.jobiizy-mobile-search-modal').length) {
			doLiveSearch();
		}
	});
	
	// Fermer dropdown au clic extérieur
	$(document).on('click', function(e) {
		if (!$(e.target).closest('.jobiizy-search-field, .jobiizy-mobile-search-field').length) {
			$('.jobiizy-autocomplete-dropdown').removeClass('show');
		}
	});
	
	// Navigation clavier dans dropdown
	$('#jobiizy-keywords, #jobiizy-location').on('keydown', function(e) {
		var $dropdown = $(this).siblings('.jobiizy-autocomplete-dropdown');
		var $items = $dropdown.find('.jobiizy-autocomplete-item');
		var $selected = $items.filter('.selected');
		
		if (e.key === 'ArrowDown') {
			e.preventDefault();
			if ($selected.length === 0) {
				$items.first().addClass('selected');
			} else {
				$selected.removeClass('selected').nextAll('.jobiizy-autocomplete-item').first().addClass('selected');
			}
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			if ($selected.length) {
				$selected.removeClass('selected').prevAll('.jobiizy-autocomplete-item').first().addClass('selected');
			}
		} else if (e.key === 'Enter') {
			e.preventDefault();
			if ($selected.length) {
				$selected.trigger('click');
			} else {
				doLiveSearch();
			}
		} else if (e.key === 'Escape') {
			$dropdown.removeClass('show');
		}
	});
	
	// Dupliquer l'autocomplétion pour les champs mobiles
/*
$('#jobiizy-mobile-keywords').on('input', function() {
    var value = $(this).val();
    $('#jobiizy-keywords').val(value).trigger('input');
});

$('#jobiizy-mobile-location').on('input', function() {
    var value = $(this).val();
    $('#jobiizy-location').val(value).trigger('input');
});
*/
// Copier les résultats d'autocomplétion vers la modal mobile
/*
$(document).on('DOMNodeInserted', '.jobiizy-autocomplete-results', function() {
    if (isMobile()) {
        var $this = $(this);
        var $mobileField = $('.jobiizy-mobile-search-modal:visible').find('input:focus').closest('.jobiizy-mobile-search-field');
        
        if ($mobileField.length) {
            var $mobileDropdown = $mobileField.find('.jobiizy-autocomplete-dropdown');
            if ($mobileDropdown.length === 0) {
                $mobileDropdown = $('<div class="jobiizy-autocomplete-dropdown show"><div class="jobiizy-autocomplete-results"></div></div>');
                $mobileField.append($mobileDropdown);
            }
            
            $mobileDropdown.find('.jobiizy-autocomplete-results').html($this.html());
            $mobileDropdown.addClass('show');
        }
    }
});
*/	
	
	// ========================================
	// RECHERCHE AJAX (sans rechargement)
	// ========================================
/* 
	function doLiveSearch() {
		var keywords = $('#jobiizy-keywords').val().trim();
		var location = $('#jobiizy-location').val().trim();
		
		console.log('🔎 [JobiiZy] Recherche AJAX:', { keywords, location });
		
		// Fermer dropdowns
		$('.jobiizy-autocomplete-dropdown').removeClass('show');
		
		// Ajouter loader
		$('#jobiizy-search-btn').addClass('loading');
		$('.jobiizy-listings-column').css('opacity', '0.5');
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'jobiizy_live_search',
				search_keywords: keywords,
				search_location: location
			},
			success: function(response) {
				$('#jobiizy-search-btn').removeClass('loading');
				$('.jobiizy-listings-column').css('opacity', '1');
				
				if (response.success) {
					console.log('✅ [JobiiZy] Résultats:', response.data.count);
					
					// Mettre à jour le compteur
					$('.jobiizy-job-count').html('<i class="las la-briefcase"></i> Nous avons trouvé <strong>' + response.data.count + '</strong> offre' + (response.data.count > 1 ? 's' : '') + ' d\'emploi pour vous !');
					
					// Mettre à jour la liste
					$('.jobiizy-listings-column').html(response.data.html);
					
					// Réinitialiser le panneau détail
					$('.jobiizy-detail-content').hide();
					$('.jobiizy-empty-state').fadeIn(200);
					
					// Mettre à jour l'URL sans recharger
					var newUrl = new URL(window.location.href);
					if (keywords) newUrl.searchParams.set('search_keywords', keywords);
					else newUrl.searchParams.delete('search_keywords');
					if (location) newUrl.searchParams.set('search_location', location);
					else newUrl.searchParams.delete('search_location');
					history.pushState({}, '', newUrl);
					
					// Rebind le clic sur les cards
					bindCardClicks();
					
					// Auto-clic première offre
// Auto-clic UNIQUEMENT en desktop
                   setTimeout(function() {
                        if (!isMobile()) {
                            $('.jobiizy-modern-card').first().trigger('click');
                        }
                    }, 300);
					// Mettre à jour les filtres actifs
					countActiveFilters();
					displayActiveFilters();
				}
			},
			error: function() {
				$('#jobiizy-search-btn').removeClass('loading');
				$('.jobiizy-listings-column').css('opacity', '1');
				console.error('❌ [JobiiZy] Erreur recherche');
			}
		});
	}
	
 */
	// Bouton recherche
	$('#jobiizy-search-btn').on('click', function() {
		doLiveSearch();
	});
	
	// Enter dans les champs
	$('#jobiizy-keywords, #jobiizy-location').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			var $dropdown = $(this).siblings('.jobiizy-autocomplete-dropdown');
			if (!$dropdown.hasClass('show') || !$dropdown.find('.selected').length) {
				doLiveSearch();
			}
		}
	});
	
	// ========================================
	// GÉOLOCALISATION
	// ========================================
	$('.jobiizy-geolocate').on('click', function() {
		var $btn = $(this);
		var $input = $('#jobiizy-location');
		
		if (!navigator.geolocation) {
			alert('La géolocalisation n\'est pas supportée par votre navigateur');
			return;
		}
		
		$btn.addClass('loading');
		
		navigator.geolocation.getCurrentPosition(
			function(position) {
				// Reverse geocoding via API
				$.ajax({
					url: 'https://nominatim.openstreetmap.org/reverse',
					data: {
						lat: position.coords.latitude,
						lon: position.coords.longitude,
						format: 'json'
					},
					success: function(data) {
						$btn.removeClass('loading');
						if (data.address) {
							var city = data.address.city || data.address.town || data.address.village || data.address.municipality || '';
							$input.val(city);
							doLiveSearch();
						}
					},
					error: function() {
						$btn.removeClass('loading');
						alert('Impossible de déterminer votre position');
					}
				});
			},
			function(error) {
				$btn.removeClass('loading');
				alert('Erreur de géolocalisation: ' + error.message);
			}
		);
	});
	
	// ========================================
	// UTILITAIRES
	// ========================================
	function escapeHtml(text) {
		return $('<div>').text(text).html();
	}
	
	function highlightMatch(text, query) {
		var regex = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
		return text.replace(regex, '<strong style="color:#60a5fa">$1</strong>');
	}
	
	// ========================================
	// FILTRES ET TAGS
	// ========================================
	$('.jobiizy-btn-filters').on('click', function(e) {
		e.preventDefault();
		$('.jobiizy-toggle-filters').trigger('click');
	});	
	
	
	function countActiveFilters() {
		let count = 0;
		const params = new URLSearchParams(window.location.search);
		if (params.get('search_keywords')) count++;
		if (params.get('search_location')) count++;
		if (params.get('search_categories')) count++;
		if (params.getAll('filter_job_type[]').length > 0) count++;
		$('.jobiizy-btn-filters').attr('data-active-filters', count);
		$('.jobiizy-filter-badge').text(count);
		return count;
	}
	
	function displayActiveFilters() {
		const params = new URLSearchParams(window.location.search);
		const $tags = $('.jobiizy-filters-tags');
		$tags.empty();
		if (params.get('search_keywords')) addFilterTag('Recherche', params.get('search_keywords'), 'search_keywords');
		if (params.get('search_location')) addFilterTag('Lieu', params.get('search_location'), 'search_location');
		if (params.get('search_categories')) addFilterTag('Catégorie', params.get('search_categories'), 'search_categories');
		const jobTypes = params.getAll('filter_job_type[]');
		if (jobTypes.length > 0 && jobTypes.length < 6) {
			jobTypes.forEach(type => addFilterTag('Type', type, 'filter_job_type[]', type));
		}
		if ($tags.children().length > 0) {
			$('.jobiizy-active-filters').slideDown(200);
		} else {
			$('.jobiizy-active-filters').slideUp(200);
		}
	}
	
	function addFilterTag(label, value, param, specificValue) {
		const $tag = $('<div class="jobiizy-filter-tag">').append('<span>' + label + ': ' + value + '</span>').append($('<button type="button">').html('<i class="las la-times"></i>').on('click', function() { removeFilter(param, specificValue); }));
		$('.jobiizy-filters-tags').append($tag);
	}
	
	function removeFilter(param, specificValue) {
		const params = new URLSearchParams(window.location.search);
		if (specificValue) {
			const values = params.getAll(param).filter(v => v !== specificValue);
			params.delete(param);
			values.forEach(v => params.append(param, v));
		} else {
			params.delete(param);
		}
		// Recherche AJAX au lieu de recharger
		$('#jobiizy-keywords').val(params.get('search_keywords') || '');
		$('#jobiizy-location').val(params.get('search_location') || '');
		doLiveSearch();
	}
	
	$('.jobiizy-clear-all').on('click', function() { 
		$('#jobiizy-keywords').val('');
		$('#jobiizy-location').val('');
		doLiveSearch();
	});
	
	$('.jobiizy-btn-alert').on('click', function(e) { e.preventDefault(); alert('Fonctionnalité à implémenter !'); });
	
	countActiveFilters();
	displayActiveFilters();
	
	// ========================================
	// SPLIT VIEW - CLIC SUR CARDS
	// ========================================
/*	function bindCardClicks() {
    $('.jobiizy-modern-card').off('click').on('click', function(e) {
        e.preventDefault();
        var $card = $(this);
        var postId = $card.data('post-id');
        var postUrl = $card.data('post-url');
        
        console.log('🖱️ [JobiiZy] Card cliquée:', postId);
        
        $('.jobiizy-modern-card').removeClass('active');
        $card.addClass('active');
        
        // En mobile, ouvrir le drawer
        if (isMobile()) {
            openDrawer();
        } else {
            $('.jobiizy-empty-state').fadeOut(200);
        }
        
        $('.jobiizy-detail-content').html('<div style="text-align:center;padding:60px;"><i class="las la-spinner la-spin" style="font-size:48px;color:#60a5fa;"></i></div>').show();
        			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: { action: 'jobiizy_load_job_details', post_id: postId },
				success: function(response) {
					if (response.success) {
						console.log('✅ [JobiiZy] Détails chargés');
						$('.jobiizy-detail-content').html(response.data);
					} else {
						loadInIframe(postUrl);
					}
				},
				error: function() { loadInIframe(postUrl); }
			});
		});
	}
	*/

	function loadInIframe(url) {
		$('.jobiizy-detail-content').html('<iframe src="' + url + '" style="width:100%;height:calc(100vh - 320px);border:none;border-radius:12px;"></iframe>');
	}
	
	// Bind initial
	bindCardClicks();
	
	// Auto-clic première offre
// Auto-clic UNIQUEMENT en desktop
setTimeout(function() { 
    if (!isMobile()) {
        $('.jobiizy-modern-card').first().trigger('click'); 
    } else {
        console.log('📱 Mode mobile : pas d\'auto-clic');
    }
}, 500);
});
jQuery(document).ready(function($) {
	// Ouvrir popup
	$(document).on('click', '.jobiizy-toggle-filters', function(e) {
		e.preventDefault();
		console.log('🔍 [JobiiZy] Ouverture popup filtres');
		$('#jobiizy-filters-popup').addClass('show');
		$('body').css('overflow', 'hidden');
	});
	
	// Fermer popup
	function closePopup() {
		$('#jobiizy-filters-popup').removeClass('show');
		$('body').css('overflow', '');
	}
	
	$('.jobiizy-close-filters').on('click', closePopup);
	
	$('#jobiizy-filters-popup').on('click', function(e) {
		if ($(e.target).is('#jobiizy-filters-popup')) {
			closePopup();
		}
	});
	
	$(document).on('keyup', function(e) {
		if (e.key === 'Escape') closePopup();
	});
	
	// Réinitialiser
	$('.jobiizy-btn-reset').on('click', function() {
		$('.jobiizy-filter-form')[0].reset();
		$('.jobiizy-filter-form input[type="checkbox"]').prop('checked', true);
	});
	
	// Appliquer
	$('.jobiizy-btn-apply').on('click', function() {
		console.log('✅ [JobiiZy] Application des filtres');
		window.location.search = $('.jobiizy-filter-form').serialize();
	});
});

    // Fin du module
    console.log('✅ [JobiiZy] Initialisation terminée');
    
// ============================================================================
// CODE JAVASCRIPT MOBILE - À AJOUTER À LA FIN DE jobiizy-split-view.js
// AVANT LA LIGNE })(jQuery);
// ============================================================================

// ========================================================================
// BARRE STICKY MOBILE INTELLIGENTE
// ========================================================================

(function initMobileStickyBar() {
	'use strict';
	
	// Vérifier qu'on est bien en mode mobile
	function isMobile() {
		return window.innerWidth <= 1024;
	}
	
	// Ne rien faire si on est en desktop
	if (!isMobile()) {
		console.log('📱 [Mobile Sticky] Desktop détecté - module non chargé');
		return;
	}
	
	console.log('📱 [Mobile Sticky] Initialisation...');
	
	// ========================================================================
	// VARIABLES
	// ========================================================================
	
	var $mobileBar = $('#jobiizy-mobile-sticky');
	var $searchTrigger = $('#mobile-search-trigger');
	var $filtersBadge = $('#mobile-filters-badge');
	var $resetBtn = $('#mobile-reset-btn');
	var $activeFilters = $('#mobile-active-filters');
	var $filtersTags = $('.mobile-filters-tags');
	
	var lastScrollTop = 0;
	var scrollThreshold = 5;
	var isBarVisible = true;
	var scrollTimeout = null;
	var autoShowTimeout = null;
	var autoShowDelay = 1000;
	
	// ========================================================================
	// SMART HIDE/SHOW AU SCROLL
	// ========================================================================
	
	$(window).on('scroll.mobileStickyBar', function() {
		clearTimeout(scrollTimeout);
		clearTimeout(autoShowTimeout);
		scrollTimeout = setTimeout(handleScroll, 10);
		
		autoShowTimeout = setTimeout(function() {
			console.log('🔄 Auto-show après inactivité');
			showBar();
		}, autoShowDelay);
	});
	
	function handleScroll() {
		var scrollTop = $(window).scrollTop();
		var scrollDelta = scrollTop - lastScrollTop;
		console.log('📱 [Mobile Sticky] handleScroll scrollTop :', scrollTop );

		// Si en haut de page (< 100px), toujours afficher
		if (scrollTop < 50) {
			showBar();
			lastScrollTop = scrollTop;
			return;
		}
		
		// Scroll DOWN (delta positif) → Cacher
		if (scrollDelta > scrollThreshold && isBarVisible) {
			hideBar();
		}
		// Scroll UP (delta négatif) → Afficher
		else if (scrollDelta < -scrollThreshold && !isBarVisible) {
			showBar();
		}
		
		lastScrollTop = scrollTop;
	}
	
	function showBar() {
		if (!isBarVisible) {
			$mobileBar.removeClass('hidden').addClass('visible');
			isBarVisible = true;
			console.log('✅ Barre mobile affichée');
		}
	}
	
	function hideBar() {
		if (isBarVisible) {
			$mobileBar.removeClass('visible').addClass('hidden');
			isBarVisible = false;
			console.log('❌ Barre mobile cachée');
		}
	}
	
	// ========================================================================
	// ÉVÉNEMENTS DES BOUTONS
	// ========================================================================
	
	// Bouton recherche → Ouvrir la modal de recherche mobile
	$searchTrigger.on('click', function(e) {
		e.preventDefault();
		console.log('📱 [Mobile Sticky] Clic sur bouton recherche');
		
		// Ouvrir la modal
		var $modal = $('#jobiizy-mobile-search-modal');
		
		if ($modal.length === 0) {
			console.error('❌ Modal #jobiizy-mobile-search-modal introuvable !');
			return;
		}
		
		console.log('✅ Modal trouvée, ouverture...');
		$modal.addClass('show').css('display', 'flex');
		$('body').css('overflow', 'hidden');
		
		// Synchroniser les valeurs
		var keywordValue = $('#jobiizy-keywords').val() || '';
		var locationValue = $('#jobiizy-location').val() || '';
		
		$('#jobiizy-mobile-keywords').val(keywordValue);
		$('#jobiizy-mobile-location').val(locationValue);
		
		console.log('✅ Modal ouverte, valeurs synchronisées');
	});
	
	// Badge filtres → Ouvrir la popup filtres
	$filtersBadge.on('click', function(e) {
		e.preventDefault();
		console.log('📱 [Mobile Sticky] Ouverture popup filtres');
		$('#jobiizy-open-filters').trigger('click');
	});
	
	// Bouton reset → Réinitialiser tous les filtres
	$resetBtn.on('click', function(e) {
		e.preventDefault();
		console.log('📱 [Mobile Sticky] Réinitialisation des filtres');
		resetAllFilters();
	});
	
	// ========================================================================
	// GESTION DES FILTRES
	// ========================================================================
	
	// Compter les filtres actifs
	function getActiveFiltersCount() {
		var count = 0;
		
		// Mots-clés
		if ($('#jobiizy-keywords').val() && $('#jobiizy-keywords').val().trim()) count++;
		
		// Localisation
		if ($('#jobiizy-location').val() && $('#jobiizy-location').val().trim()) count++;
		
		// Catégorie
		var category = $('select[name="search_categories"]').val();
		if (category && category !== '') count++;
		
		// Types de contrat
		count += $('input[name="filter_job_type[]"]:checked').length;
		
		return count;
	}
	
	// Mettre à jour l'affichage du badge et des tags
	function updateFiltersBadge() {
		var activeCount = getActiveFiltersCount();
		
		console.log('📱 [Mobile Sticky] Filtres actifs:', activeCount);
		
		if (activeCount > 0) {
			// Afficher badge et bouton reset
			$filtersBadge.show().attr('data-count', activeCount);
			$filtersBadge.find('.badge-count').text(activeCount);
			$resetBtn.show();
			
			// Afficher les tags des filtres actifs
			updateActiveFiltersTags();
			$activeFilters.show();
		} else {
			// Masquer tout si aucun filtre
			$filtersBadge.hide();
			$resetBtn.hide();
			$activeFilters.hide();
		}
	}
	
	// Créer les tags de filtres actifs
	function updateActiveFiltersTags() {
		$filtersTags.empty();
		
		// Mots-clés
		var keywords = $('#jobiizy-keywords').val();
		if (keywords && keywords.trim()) {
			$filtersTags.append(createFilterTag('🔍 ' + truncateText(keywords.trim(), 15), function() {
				$('#jobiizy-keywords, #jobiizy-mobile-keywords').val('');
				triggerSearch();
			}));
		}
		
		// Localisation
		var location = $('#jobiizy-location').val();
		if (location && location.trim()) {
			$filtersTags.append(createFilterTag('📍 ' + truncateText(location.trim(), 15), function() {
				$('#jobiizy-location, #jobiizy-mobile-location').val('');
				triggerSearch();
			}));
		}
		
		// Catégorie
		var $categorySelect = $('select[name="search_categories"]');
		var categoryValue = $categorySelect.val();
		if (categoryValue) {
			var categoryText = $categorySelect.find('option:selected').text();
			$filtersTags.append(createFilterTag('📁 ' + truncateText(categoryText, 15), function() {
				$categorySelect.val('');
				triggerSearch();
			}));
		}
		
		// Types de contrat
		$('input[name="filter_job_type[]"]:checked').each(function() {
			var label = $(this).closest('label').find('span').text() || $(this).val();
			var $checkbox = $(this);
			$filtersTags.append(createFilterTag(truncateText(label, 15), function() {
				$checkbox.prop('checked', false);
				triggerSearch();
			}));
		});
	}
	
	// Créer un tag de filtre avec bouton de suppression
	function createFilterTag(text, onRemove) {
		var $tag = $('<div class="mobile-filter-tag">' + 
			'<span>' + escapeHtml(text) + '</span>' +
			'<button type="button"><i class="las la-times"></i></button>' +
		'</div>');
		
		$tag.find('button').on('click', function(e) {
			e.stopPropagation();
			console.log('📱 [Mobile Sticky] Suppression filtre:', text);
			onRemove();
			updateFiltersBadge();
		});
		
		return $tag;
	}
	
	// Réinitialiser tous les filtres
	function resetAllFilters() {
		$('#jobiizy-keywords, #jobiizy-mobile-keywords').val('');
		$('#jobiizy-location, #jobiizy-mobile-location').val('');
		$('select[name="search_categories"]').val('');
		$('input[name="filter_job_type[]"]').prop('checked', false);
		
		triggerSearch();
		updateFiltersBadge();
	}
	
	// Déclencher la recherche
	function triggerSearch() {
		if (typeof window.doLiveSearch === 'function') {
			window.doLiveSearch();
		} else if (typeof doLiveSearch === 'function') {
			doLiveSearch();
		} else {
			console.warn('⚠️ [Mobile Sticky] doLiveSearch non trouvée');
			// Fallback : recharger la page
			window.location.reload();
		}
	}
	
	// ========================================================================
	// UTILITAIRES
	// ========================================================================
	
	function truncateText(text, maxLength) {
		if (!text) return '';
		text = String(text);
		if (text.length <= maxLength) return text;
		return text.substring(0, maxLength) + '...';
	}
	
	function escapeHtml(text) {
		if (!text) return '';
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
	}
	
	// ========================================================================
	// INITIALISATION
	// ========================================================================
	
	// Vérifier que les éléments existent
	if ($mobileBar.length === 0) {
		console.error('❌ [Mobile Sticky] Barre mobile introuvable (#jobiizy-mobile-sticky)');
		return;
	}
	
	if ($searchTrigger.length === 0) {
		console.error('❌ [Mobile Sticky] Bouton recherche introuvable (#mobile-search-trigger)');
		return;
	}
	
	console.log('✅ [Mobile Sticky] Éléments trouvés');
	console.log('  - Barre:', $mobileBar.length);
	console.log('  - Bouton recherche:', $searchTrigger.length);
	console.log('  - Badge filtres:', $filtersBadge.length);
	
	// Mettre à jour le badge au chargement
	updateFiltersBadge();
	
	// Initialiser la barre comme visible
	$mobileBar.addClass('visible');
	
	// Écouter les changements de filtres
	$(document).on('filtersApplied searchCompleted', function() {
		console.log('📱 [Mobile Sticky] Événement filtres détecté');
		setTimeout(updateFiltersBadge, 100);
	});
	
	// Vérifier périodiquement (fallback)
	setInterval(function() {
		var newCount = getActiveFiltersCount();
		var currentCount = parseInt($filtersBadge.attr('data-count') || 0);
		
		if (newCount !== currentCount) {
			console.log('📱 [Mobile Sticky] Changement détecté:', currentCount, '→', newCount);
			updateFiltersBadge();
		}
	}, 2000);
	
	console.log('✅ [Mobile Sticky] Initialisation terminée');
	
	// Désactiver si changement vers desktop
	var resizeTimeout;
	$(window).on('resize', function() {
		clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(function() {
			if (!isMobile() && $mobileBar.is(':visible')) {
				console.log('📱 [Mobile Sticky] Passage en desktop - désactivation');
				$(window).off('scroll.mobileStickyBar');
			}
		}, 250);
	});
	
})();

// ============================================================================
// FIN CODE MOBILE
// ============================================================================

})(jQuery);