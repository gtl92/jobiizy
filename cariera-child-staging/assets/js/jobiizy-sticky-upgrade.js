/**
 * JobiiZy - Desktop Sticky Search Bar Upgrade v5.5 SMOOTH
 * 
 * AMÉLIORATIONS v5.5 :
 *   - Transitions plus fluides (easing amélioré)
 *   - Durées d'animation optimisées
 *   - Glow progressif (fade-in au lieu d'apparition brutale)
 * 
 * @version 5.5-smooth
 * @requires jQuery
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        if (window.innerWidth <= 1024) {
            console.log('[StickyUpgrade] Mobile, module ignoré');
            return;
        }

        console.log('[StickyUpgrade] 🚀 Init v5.5 - Transitions Smooth');

        var CFG = {
            compactThreshold: 200,
            scrollDelta: 8,
            hideAnimDuration: 350,      // 🆕 300 → 350ms
            returnAnimDuration: 500,    // 🆕 400 → 500ms
            debug: false
        };

        function log() {
            if (CFG.debug) console.log.apply(console, ['[StickyUpgrade]'].concat(Array.from(arguments)));
        }

        var $bar = $('#jobiizy-desktop-bar');
        if (!$bar.length) {
            $bar = $('.jobiizy-search-bar-sticky').filter(':visible').first();
        }
        
        if (!$bar.length) {
            console.warn('[StickyUpgrade] ❌ Barre desktop introuvable');
            return;
        }

        var barEl = $bar[0];
        log('✅ Barre ciblée:', barEl.id || barEl.className);

        var $spacer = $('<div class="jobiizy-sticky-spacer"></div>');
        $bar.after($spacer);

        var state = 'normal';
        var lastScrollY = window.scrollY;
        var ticking = false;
        var barNormalHeight = $bar.outerHeight();
        var adminBarH = 0;

        function measureDimensions() {
            var $adminBar = $('#wpadminbar');
            adminBarH = $adminBar.length ? $adminBar.outerHeight() : 0;
            barNormalHeight = $bar.outerHeight();
        }

        function disableOldSystem() {
            if (window.JobiizySmartSticky) {
                window.JobiizySmartSticky.setMode('always');
            }
            $bar.removeClass('sticky-hidden sticky-visible is-stuck is-compact');
            $('#jobiizy-toggle-search').hide();
        }

        // =========================================================
        // GO OVERLAY - 🆕 TRANSITIONS AMÉLIORÉES
        // =========================================================
        function goOverlay() {
            if (state === 'overlay') return;
            log('⬇️ → OVERLAY COMPACT');
            state = 'overlay';

            barNormalHeight = $bar.outerHeight();
            $spacer.css('height', barNormalHeight + 'px').addClass('active');
            $bar.removeClass('is-returning-normal is-overlay-hiding');

            barEl.style.setProperty('position', 'fixed', 'important');
            barEl.style.setProperty('top', adminBarH + 'px', 'important');
            barEl.style.setProperty('left', '0', 'important');
            barEl.style.setProperty('right', '0', 'important');
            barEl.style.setProperty('z-index', '100001', 'important');
            barEl.style.setProperty('padding', '10px 30px', 'important');
            barEl.style.setProperty('background', 'rgba(15, 23, 42, 0.98)', 'important');
            
            // 🆕 Backdrop enrichi
            barEl.style.setProperty('backdrop-filter', 'blur(24px) saturate(180%)', 'important');
            barEl.style.setProperty('-webkit-backdrop-filter', 'blur(24px) saturate(180%)', 'important');
            
            barEl.style.setProperty('border-radius', '0', 'important');
            
            // 🆕 GLOW GEMINI - Plus smooth avec opacity progressive
            barEl.style.setProperty('box-shadow', 
                '0 0 60px rgba(59, 130, 246, 0.6), ' +
                '0 0 30px rgba(99, 102, 241, 0.5), ' +
                '0 8px 40px rgba(0, 0, 0, 0.6), ' +
                'inset 0 0 30px rgba(59, 130, 246, 0.15), ' +
                'inset 0 -2px 0 rgba(99, 102, 241, 0.4), ' +
                'inset 0 2px 0 rgba(139, 92, 246, 0.3)', 'important');
            
            barEl.style.setProperty('border-bottom', '2px solid rgba(99, 102, 241, 0.6)', 'important');
            barEl.style.setProperty('border-top', '1px solid rgba(139, 92, 246, 0.4)', 'important');
            
            // 🆕 Animation slide + fade plus douce
            barEl.style.setProperty('animation', 'jb-slideDownOverlay 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards', 'important');
            
            // 🆕 Ajouter le pulse après un court délai (effet progressif)
            setTimeout(function() {
                if (state === 'overlay') {
                    barEl.style.setProperty('animation', 'jb-slideDownOverlay 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards, jb-desktopGlowPulse 3s ease-in-out 0.5s infinite', 'important');
                }
            }, 400);

            $bar.addClass('is-overlay-compact');
        }

        // =========================================================
        // GO NORMAL - 🆕 RETOUR PLUS SMOOTH
        // =========================================================
        function goNormal() {
            if (state === 'normal') return;
            log('⬆️ → NORMAL');
            state = 'transitioning';

            // 🆕 Animation de sortie plus douce
            barEl.style.setProperty('animation', 'jb-slideUpOverlay 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards', 'important');

            setTimeout(function() {
                barEl.style.cssText = '';
                $bar.removeClass('is-overlay-compact is-overlay-hiding is-compact');
                $spacer.removeClass('active').css('height', '0');
                $bar.addClass('is-returning-normal');
                
                setTimeout(function() {
                    $bar.removeClass('is-returning-normal');
                    state = 'normal';
                    log('✅ Retour normal');
                }, CFG.returnAnimDuration);
            }, CFG.hideAnimDuration);
        }

        function onScroll() {
            var currentY = window.scrollY;
            var delta = currentY - lastScrollY;
            var isDown = delta > 0;
            var isUp = delta < 0;
            var isPastThreshold = Math.abs(delta) > CFG.scrollDelta;

            if (currentY < CFG.compactThreshold) {
                if (state === 'overlay') goNormal();
                lastScrollY = currentY;
                return;
            }

            if (state === 'transitioning') {
                lastScrollY = currentY;
                return;
            }

            if (isDown && isPastThreshold && currentY > CFG.compactThreshold && state === 'normal') {
                goOverlay();
            }
            
            if (isUp && isPastThreshold && state === 'overlay') {
                goNormal();
            }

            lastScrollY = currentY;
        }

        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    onScroll();
                    ticking = false;
                });
                ticking = true;
            }
        }, { passive: true });

        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth <= 1024) {
                    barEl.style.cssText = '';
                    $bar.removeClass('is-overlay-compact is-overlay-hiding is-returning-normal');
                    $spacer.removeClass('active').css('height', '0');
                    state = 'normal';
                    return;
                }
                measureDimensions();
            }, 200);
        });

        measureDimensions();
        disableOldSystem();

        if (window.scrollY > CFG.compactThreshold) {
            goOverlay();
        }

        console.log('[StickyUpgrade] ✅ v5.5-smooth initialisé');

        window.JobiizyStickyUpgrade = {
            goOverlay: goOverlay,
            goNormal: goNormal,
            getState: function() { return state; },
            setThreshold: function(px) { CFG.compactThreshold = px; },
            setDebug: function(on) { CFG.debug = on; },
            getBar: function() { return barEl; }
        };
    });

})(jQuery);