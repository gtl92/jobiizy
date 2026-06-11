/**
 * JobiiZy - Mobile Search Bar Upgrade v6.4.1 FIXED
 * 
 * CORRECTION v6.4.1 :
 *   - ✅ Détection mmenu corrigée (mm-menu_opened sur nav)
 *   - ✅ Support mmenu offcanvas
 * 
 * @version 6.4.1-fixed
 * @requires jQuery
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        if (window.innerWidth > 1024) {
            console.log('[MobileUpgrade] Desktop, ignoré');
            return;
        }

        console.log('[MobileUpgrade] 📱 Init v6.4.1 - mmenu FIXED');

        // =========================================================
        // CONFIG
        // =========================================================
        var CFG = {
            scrollDelta: 6,
            compactThreshold: 100,
            headerReturnDelay: 150,
            debug: true  // 🆕 Activé par défaut pour debug
        };

        function log() {
            if (CFG.debug) console.log.apply(console, ['[MobileUpgrade]'].concat(Array.from(arguments)));
        }

        // =========================================================
        // ÉLÉMENTS
        // =========================================================
        var $header = $('header.cariera-main-header, header.main-header').first();
        var headerH = $header.length ? $header.outerHeight() : 60;
        var adminBarH = $('#wpadminbar').length ? $('#wpadminbar').outerHeight() : 0;

        log('📏 Header:', headerH + 'px, AdminBar:', adminBarH + 'px');

        if ($header.length) {
            $header.attr('style', '').addClass('jb-header-visible');
        }

        // =========================================================
        // CRÉER LA BARRE
        // =========================================================
        var barHTML = 
            '<div class="jb-mobile-bar jb-normal" id="jb-mobile-bar">' +
                '<div class="jb-mobile-bar-inner">' +
                    '<div class="jb-mobile-search-trigger" id="jb-mobile-search-trigger">' +
                        '<i class="las la-search jb-search-icon"></i>' +
                        '<span class="jb-search-text" id="jb-search-text">Rechercher un emploi...</span>' +
                    '</div>' +
                    '<button type="button" class="jb-mobile-filter-btn" id="jb-mobile-filter-btn">' +
                        '<i class="las la-sliders-h"></i>' +
                        '<span class="jb-filter-count" id="jb-filter-count">0</span>' +
                    '</button>' +
                '</div>' +
                '<div class="jb-mobile-active-tags" id="jb-mobile-active-tags"></div>' +
            '</div>';

        $('body').prepend(barHTML);

        var $bar = $('#jb-mobile-bar');
        var $trigger = $('#jb-mobile-search-trigger');
        var $searchText = $('#jb-search-text');
        var $filterBtn = $('#jb-mobile-filter-btn');
        var $filterCount = $('#jb-filter-count');
        var $activeTags = $('#jb-mobile-active-tags');

        // Spacer
        var $spacer = $('<div class="jb-mobile-spacer" id="jb-mobile-spacer"></div>');
        var $mainContent = $('.jobiizy-split-view-outer, .main-content, main').first();
        if ($mainContent.length) {
            $mainContent.prepend($spacer);
        }

        // Modal
        var $modal = $('#jobiizy-mobile-search-modal');
        var $closeModal = $('#jobiizy-mobile-close-search');
        var $submitModal = $('#jobiizy-mobile-search-submit');

        // =========================================================
        // ÉTAT
        // =========================================================
        var state = 'init';
        var lastScrollY = window.scrollY;
        var ticking = false;
        var menuIsOpen = false;
        var detailIsOpen = false;
        var headerReturnTimer = null;

        // =========================================================
        // DIMENSIONS
        // =========================================================
        function measureHeader() {
            headerH = $header.length ? $header.outerHeight() : 60;
            adminBarH = $('#wpadminbar').length ? $('#wpadminbar').outerHeight() : 0;
        }

        // =========================================================
        // VÉRIFIER SI DOIT ÊTRE CACHÉ
        // =========================================================
        function shouldBeHidden() {
            return menuIsOpen || detailIsOpen;
        }

        // =========================================================
        // MODE NORMAL
        // =========================================================
        function setNormal(immediate) {
            if (state === 'normal' && !shouldBeHidden()) return;

            if (headerReturnTimer) {
                clearTimeout(headerReturnTimer);
                headerReturnTimer = null;
            }

            if (!immediate && state === 'compact') {
                showHeader();
                headerReturnTimer = setTimeout(function() {
                    applyNormalMode();
                }, CFG.headerReturnDelay);
            } else {
                applyNormalMode();
            }
        }

        function applyNormalMode() {
            if (shouldBeHidden()) {
                log('⏸️ Normal annulé (menu ou offre ouvert)');
                return;
            }

            var topPos = headerH + adminBarH;
            
            log('⬆️ Normal — top:', topPos + 'px');
            
            $bar.removeClass('jb-compact jb-hidden-for-menu jb-hidden-for-detail').addClass('jb-normal');
            $bar.css('top', topPos + 'px');

            var barH = $bar.outerHeight() || 70;
            $spacer.css('height', (topPos + barH - 20) + 'px');

            showHeader();
            state = 'normal';
        }

        // =========================================================
        // MODE COMPACT
        // =========================================================
        function setCompact() {
            if (state === 'compact') return;

            if (shouldBeHidden()) {
                log('⏸️ Compact annulé (menu ou offre ouvert)');
                return;
            }

            if (headerReturnTimer) {
                clearTimeout(headerReturnTimer);
                headerReturnTimer = null;
            }

            var topPos = adminBarH;

            log('⬇️ Compact — top:', topPos + 'px');

            $bar.removeClass('jb-normal jb-hidden-for-menu jb-hidden-for-detail').addClass('jb-compact');
            $bar.css('top', topPos + 'px');

            hideHeader();
            state = 'compact';
        }

        // =========================================================
        // HEADER SHOW/HIDE
        // =========================================================
        function showHeader() {
            $header.removeClass('jb-header-hidden').addClass('jb-header-visible');
        }

        function hideHeader() {
            $header.removeClass('jb-header-visible').addClass('jb-header-hidden');
        }

        // =========================================================
        // SCROLL HANDLER
        // =========================================================
        function onScroll() {
            if (Math.abs(window.scrollY - lastScrollY) > CFG.scrollDelta) {
                hasUserScrolled = true;
            }
            
            if (shouldBeHidden()) {
                lastScrollY = window.scrollY;
                return;
            }

            var currentY = window.scrollY;
            var delta = currentY - lastScrollY;
            var isDown = delta > 0;
            var isUp = delta < 0;
            var isPastThreshold = Math.abs(delta) > CFG.scrollDelta;

            if (currentY < CFG.compactThreshold) {
                if (state !== 'normal') {
                    setNormal(true);
                }
                lastScrollY = currentY;
                return;
            }

            if (isDown && isPastThreshold && state === 'normal' && hasUserScrolled) {
                setCompact();
            }
            
            if (isUp && isPastThreshold && state === 'compact') {
                setNormal(false);
            }

            lastScrollY = currentY;
        }

        var hasUserScrolled = false;

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    onScroll();
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });

        // =========================================================
        // 🔥 WATCH MENU MOBILE - MMENU FIXED
        // =========================================================
        function watchMenu() {
            log('👀 Installation watchdog menu mmenu...');
            
            // 🔥 MMENU : Classes sur <nav> lui-même
            var $mmenuNav = $('.mm-menu, nav.mmenu-init, .main-mobile-nav');
            
            // Autres menus standards
            var $menuBtn = $('.menu-toggle, .mobile-menu-toggle, .hamburger, [class*="menu-trigger"]');
            // var $mobileMenu = $('.mobile-menu, .off-canvas-menu, #mobile-menu, .slicknav_nav');
                        
             var $mobileMenu = $('.mobile-menu, .off-canvas-menu, #mobile-menu, .slicknav_nav, .cariera_mobile_menu, .mm-menu, .mm-menu_opened');

            
            
            
            log('🍔 Menu elements:', {
                mmenuNav: $mmenuNav.length,
                menuBtn: $menuBtn.length,
                mobileMenu: $mobileMenu.length
            });

            // Click sur bouton menu
            if ($menuBtn.length) {
                $menuBtn.on('click.jbmobile', function() {
                    setTimeout(checkMenuState, 50);
                });
            }

            // Observer body
            var bodyObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'class') {
                        checkMenuState();
                    }
                });
            });

            bodyObserver.observe(document.body, { 
                attributes: true, 
                attributeFilter: ['class'] 
            });

            // 🔥 Observer mmenu NAV (critical pour mmenu)
            if ($mmenuNav.length) {
                log('✅ mmenu détecté, installation observer...');
                
                var mmenuObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class') {
                            log('🔄 mmenu class changed');
                            checkMenuState();
                        }
                    });
                });

                $mmenuNav.each(function() {
                    mmenuObserver.observe(this, { 
                        attributes: true, 
                        attributeFilter: ['class'] 
                    });
                });
            }

            // Observer autres menus
            if ($mobileMenu.length) {
                var menuObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === 'class' || mutation.attributeName === 'style') {
                            checkMenuState();
                        }
                    });
                });

                $mobileMenu.each(function() {
                    menuObserver.observe(this, { 
                        attributes: true, 
                        attributeFilter: ['class', 'style'] 
                    });
                });
            }

            function checkMenuState() {
                var wasOpen = menuIsOpen;
                
                // 🔥 MMENU : vérifier mm-menu_opened sur nav
			var mmenuOpen =
    $mmenuNav.hasClass('mm-menu_opened') ||
    $mmenuNav.hasClass('mm-menu--opened');
                
                // Classes body
var $html = $('html');

var bodyMenuOpen =
    $('body').hasClass('mobile-menu-active') ||
    $('body').hasClass('menu-open') ||
    $('body').hasClass('mm-menu_opened') ||
    $('body').hasClass('mm-wrapper_opened') ||
    $('body').hasClass('mm-wrapper--opened') ||
    $('body').hasClass('offcanvas-open') ||

    // ✅ mmenu: souvent sur <html>
    $html.hasClass('mm-menu_opened') ||
    $html.hasClass('mm-wrapper_opened') ||
    $html.hasClass('mm-wrapper--opened') ||
    $html.hasClass('offcanvas-open');
    
 var $blocker = $('.mm-wrapper__blocker, .mobile-menu-overlay, .off-canvas-overlay');   
var blockerOpen = $blocker.length > 0 && ($blocker.is(':visible') || $blocker.css('opacity') !== '0');
                
                // Autres menus visibles
                var otherMenuOpen = 
                    $mobileMenu.is(':visible') ||
                    $mobileMenu.hasClass('active') ||
                    $mobileMenu.hasClass('is-active') ||
                    $mobileMenu.hasClass('opened');
                
                // 🔥 Combinaison de toutes les détections
				menuIsOpen = mmenuOpen || bodyMenuOpen || otherMenuOpen || blockerOpen;                
                if (wasOpen !== menuIsOpen) {
                    log('🍔 Menu état changé:', menuIsOpen ? 'OUVERT ✅' : 'FERMÉ ❌');
                    log('   - mmenuOpen:', mmenuOpen);
                    log('   - bodyMenuOpen:', bodyMenuOpen);
                    log('   - otherMenuOpen:', otherMenuOpen);
                    
                    if (menuIsOpen) {
                        $bar.addClass('jb-hidden-for-menu');
                        log('👻 Barre cachée (menu ouvert)');
                    } else {
                        $bar.removeClass('jb-hidden-for-menu');
                        
                        if (window.scrollY < CFG.compactThreshold) {
                            setNormal(true);
                        } else if (state === 'compact') {
                            setCompact();
                        }
                        
                        log('👁️ Barre visible (menu fermé)');
                    }
                }
            }

            // Vérification initiale
            checkMenuState();
        }

        // =========================================================
        // WATCH OFFRE OUVERTE
        // =========================================================
        function watchDetailColumn() {
            log('👀 Installation watchdog offre...');
            
            var $detailColumn = $('.jobiizy-detail-column');
            
            if (!$detailColumn.length) {
                log('⚠️ .jobiizy-detail-column introuvable');
                return;
            }

            log('✅ Detail column trouvée');

function checkDetailState() {
    var wasOpen = detailIsOpen;

    // ✅ toujours re-check sur le DOM (et sur TOUS les éléments)
    detailIsOpen = $('.jobiizy-detail-column.open').length > 0;

    if (wasOpen !== detailIsOpen) {
        log('📄 Offre état changé:', detailIsOpen ? 'OUVERTE' : 'FERMÉE');

        $bar.toggleClass('jb-hidden-for-detail', detailIsOpen);

        if (!detailIsOpen) {
            if (window.scrollY < CFG.compactThreshold) setNormal(true);
            else if (state === 'compact') setCompact();
        }
    }
}
 var detailObserver = new MutationObserver(function(mutations) {
    for (var i=0; i<mutations.length; i++) {
        if (mutations[i].type === 'attributes' && mutations[i].attributeName === 'class') {
            checkDetailState();
            return;
        }
    }
});

// ✅ observe tout le DOM pour capter un remplacement / réinjection
detailObserver.observe(document.body, {
    attributes: true,
    attributeFilter: ['class'],
    subtree: true
});
// ✅ Observer HTML (mmenu met souvent les classes ici)
var htmlObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'class') {
            checkMenuState();
        }
    });
});

htmlObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
});
            checkDetailState();
        }

        // =========================================================
        // MODAL
        // =========================================================
        $trigger.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openSearchModal();
        });

        function openSearchModal() {
            if (!$modal.length) return;
            
            var kw = $('#jobiizy-keywords').val() || '';
            var loc = $('#jobiizy-location').val() || '';
            $('#jobiizy-mobile-keywords').val(kw);
            $('#jobiizy-mobile-location').val(loc);
            
            $modal.css('display', 'flex');
            $modal[0].offsetHeight;
            $modal.addClass('show');
            $('body').css('overflow', 'hidden');
            
            setTimeout(function() { 
                $('#jobiizy-mobile-keywords').trigger('focus'); 
            }, 400);
        }

        $closeModal.on('click.v6 touchend.v6', function(e) {
            e.preventDefault(); 
            e.stopPropagation();
            closeSearchModal();
        });

        $modal.on('click.v6', function(e) {
            var $t = $(e.target);
            if ($t.hasClass('jobiizy-mobile-close-search') || $t.closest('.jobiizy-mobile-close-search').length) {
                e.preventDefault(); 
                e.stopPropagation();
                closeSearchModal();
            }
        });

        function closeSearchModal() {
            $modal.removeClass('show').css('transform', 'translateY(100%)');
            setTimeout(function() { 
                $modal.css('display', 'none'); 
            }, 350);
            $('body').css('overflow', '');
        }

        $(document).on('keyup.v6', function(e) {
            if (e.key === 'Escape' && $modal.hasClass('show')) closeSearchModal();
        });

        setTimeout(function() {
            var btn = document.getElementById('jobiizy-mobile-close-search');
            if (btn) {
                var s = getComputedStyle(btn);
                if (s.display === 'none' || s.visibility === 'hidden' || s.opacity === '0' || s.width === '0px') {
                    btn.style.cssText = 'display:flex!important;align-items:center!important;justify-content:center!important;width:44px!important;height:44px!important;min-width:44px!important;background:rgba(248,113,113,0.2)!important;border:1px solid rgba(248,113,113,0.4)!important;border-radius:12px!important;color:#fca5a5!important;font-size:22px!important;cursor:pointer!important;opacity:1!important;visibility:visible!important;';
                }
            }
        }, 500);

        // =========================================================
        // SUBMIT
        // =========================================================
        $submitModal.on('click.v6', function(e) {
            e.preventDefault(); 
            e.stopPropagation();
            
            var kw = $('#jobiizy-mobile-keywords').val() || '';
            var loc = $('#jobiizy-mobile-location').val() || '';
            $('#jobiizy-keywords').val(kw);
            $('#jobiizy-location').val(loc);
            
            closeSearchModal();
            updateSearchText();
            updateActiveTags();
            
            if (typeof window.doLiveSearch === 'function') {
                window.doLiveSearch();
            } else {
                var url = new URL(window.location.href);
                if (kw) url.searchParams.set('search_keywords', kw); 
                else url.searchParams.delete('search_keywords');
                if (loc) url.searchParams.set('search_location', loc); 
                else url.searchParams.delete('search_location');
                window.location.href = url.toString();
            }
        });

        // =========================================================
        // FILTRES
        // =========================================================
        $filterBtn.on('click', function(e) {
            e.preventDefault();
            
            var $fp = $('#jobiizy-filters-popup');
            if ($fp.length) { 
                $fp.addClass('show'); 
                $('body').css('overflow', 'hidden'); 
            } else { 
                openSearchModal(); 
            }
        });

        // =========================================================
        // TEXTE + TAGS
        // =========================================================
        function updateSearchText() {
            var kw = $('#jobiizy-keywords').val() || '';
            var loc = $('#jobiizy-location').val() || '';
            var parts = [];
            if (kw.trim()) parts.push(kw.trim());
            if (loc.trim()) parts.push(loc.trim());
            $searchText.text(parts.length > 0 ? parts.join(' · ') : 'Rechercher un emploi...');
            $searchText.toggleClass('has-value', parts.length > 0);
        }

        function updateActiveTags() {
            var filters = [];
            var kw = $('#jobiizy-keywords').val();
            if (kw && kw.trim()) filters.push({ label: '🔍 ' + truncate(kw.trim(), 20), param: 'search_keywords' });
            var loc = $('#jobiizy-location').val();
            if (loc && loc.trim()) filters.push({ label: '📍 ' + truncate(loc.trim(), 20), param: 'search_location' });
            
            var params = new URLSearchParams(window.location.search);
            if (params.get('search_keywords') && (!kw || !kw.trim()))
                filters.push({ label: '🔍 ' + truncate(params.get('search_keywords'), 20), param: 'search_keywords' });
            if (params.get('search_location') && (!loc || !loc.trim()))
                filters.push({ label: '📍 ' + truncate(params.get('search_location'), 20), param: 'search_location' });

            $activeTags.empty();
            filters.forEach(function(f) {
                $activeTags.append('<div class="jb-mobile-tag"><span>' + escapeHtml(f.label) + '</span><button type="button" class="jb-tag-remove" data-param="' + f.param + '"><i class="las la-times"></i></button></div>');
            });
            $activeTags.toggleClass('visible', filters.length > 0);
            $filterCount.text(filters.length).toggleClass('visible', filters.length > 0);
        }

        $activeTags.on('click', '.jb-tag-remove', function(e) {
            e.stopPropagation();
            var p = $(this).data('param');
            if (p === 'search_keywords') $('#jobiizy-keywords, #jobiizy-mobile-keywords').val('');
            if (p === 'search_location') $('#jobiizy-location, #jobiizy-mobile-location').val('');
            updateSearchText(); 
            updateActiveTags();
            if (typeof window.doLiveSearch === 'function') window.doLiveSearch();
        });

        function truncate(t, m) { return t && t.length > m ? t.substring(0, m) + '…' : (t || ''); }
        function escapeHtml(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

        // =========================================================
        // RESIZE
        // =========================================================
        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 1024) {
                    $bar.remove(); 
                    $spacer.remove();
                    $header.removeClass('jb-header-hidden jb-header-visible').attr('style', '');
                    return;
                }
                measureHeader();
                if (state === 'normal') setNormal(true);
            }, 200);
        });

        // =========================================================
        // INIT
        // =========================================================
        measureHeader();
        setNormal(true);
        updateSearchText();
        updateActiveTags();
        watchMenu();
        watchDetailColumn();

        var origSearch = window.doLiveSearch;
        if (typeof origSearch === 'function') {
            window.doLiveSearch = function() {
                origSearch.apply(this, arguments);
                setTimeout(function() { 
                    updateSearchText(); 
                    updateActiveTags(); 
                }, 500);
            };
        }

        console.log('[MobileUpgrade] ✅ v6.4.1-fixed initialisé (mmenu support)');

        window.JbMobileUpgrade = {
            openModal: openSearchModal,
            closeModal: closeSearchModal,
            updateText: updateSearchText,
            updateTags: updateActiveTags,
            getState: function() { return state; },
            setDebug: function(on) { CFG.debug = on; },
            forceNormal: setNormal,
            forceCompact: setCompact,
            isMenuOpen: function() { return menuIsOpen; },
            isDetailOpen: function() { return detailIsOpen; }
        };
    });

})(jQuery);