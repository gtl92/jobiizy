/**
 * jobiizy-offre-globale.js
 * Autocomplétion pour page-offre-globale.php
 *
 * - Réutilise les classes .jobiizy-autocomplete-item / .jobiizy-autocomplete-section
 *   du split-view (CSS déjà chargé via jobiizy-split-view.css)
 * - Au clic sur un item : remplit le champ SANS soumettre le formulaire
 * - Enter sur le champ ou clic "Rechercher" → soumission normale du <form>
 * - Dépend de : jobiizyData (ajaxurl) localisé via wp_localize_script
 */
(function ($) {
    'use strict';

    if (typeof jobiizyData === 'undefined') {
        console.error('[JZOG] jobiizyData non défini — vérifier enqueue.php');
        return;
    }

    var ajaxurl      = jobiizyData.ajaxurl;
    var searchTimer  = null;
    var currentXhr   = null;
    var DEBOUNCE_MS  = 280;

    // ── Helpers ──────────────────────────────────────────────────────────────
    function escHtml(text) {
        return $('<div>').text(text).html();
    }

    function highlight(text, query) {
        if (!query) return escHtml(text);
        var re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escHtml(text).replace(re, '<strong style="color:#60a5fa">$1</strong>');
    }

    function showDrop($drop) { $drop.addClass('show'); $drop.find('.jobiizy-autocomplete-results').addClass('show'); }
    function hideDrop($drop) { $drop.removeClass('show'); $drop.find('.jobiizy-autocomplete-results').removeClass('show'); }

    function fillAutocompleteItem($item) {
        var value  = $item.attr('data-value');
        var $drop  = $item.closest('.jobiizy-autocomplete-dropdown');
        var $field = $drop.closest('.jzog-field');
        var $input = $field.find('input');

        $input.val(value).trigger('change').focus();
        hideDrop($drop);
    }

    function handleAutocompletePick(e) {
        var item = e.target.closest ? e.target.closest('.jzog-ac-fill') : null;
        if (!item) return;

        e.preventDefault();
        e.stopPropagation();
        fillAutocompleteItem($(item));
    }

    document.addEventListener('pointerdown', handleAutocompletePick, true);
    document.addEventListener('touchstart', handleAutocompletePick, { capture: true, passive: false });

    // ── Autocomplétion keywords ───────────────────────────────────────────────
    function setupKeywords() {
        var $input = $('#jzog-keywords');
        var $drop  = $input.closest('.jzog-field').find('.jobiizy-autocomplete-dropdown');
        if (!$input.length || !$drop.length) return;

        $input.on('input', function () {
            var q = $(this).val().trim();
            clearTimeout(searchTimer);
            if (q.length < 2) { hideDrop($drop); return; }

            searchTimer = setTimeout(function () {
                $drop.addClass('show');
                $drop.find('.jobiizy-autocomplete-loader').addClass('show');
                $drop.find('.jobiizy-autocomplete-results').removeClass('show').empty();

                if (currentXhr) currentXhr.abort();
                currentXhr = $.post(ajaxurl, { action: 'jobiizy_autocomplete_keywords', query: q }, function (res) {
                    $drop.find('.jobiizy-autocomplete-loader').removeClass('show');
                    if (!res.success || !res.data.length) {
                        $drop.find('.jobiizy-autocomplete-results')
                            .html('<div class="jobiizy-autocomplete-empty"><i class="las la-search"></i>Aucun résultat pour "' + escHtml(q) + '"</div>')
                            .addClass('show');
                        return;
                    }

                    var html = '';
                    var jobs     = res.data.filter(function (i) { return i.type === 'job'; });
                    var companies = res.data.filter(function (i) { return i.type === 'company'; });

                    if (jobs.length) {
                        html += '<div class="jobiizy-autocomplete-section">Offres d\'emploi</div>';
                        jobs.forEach(function (item) {
                            html += '<div class="jobiizy-autocomplete-item jzog-ac-fill" data-value="' + escHtml(item.title) + '">';
                            html += '<div class="item-icon">';
                            html += item.logo ? '<img src="' + escHtml(item.logo) + '" alt="">' : '<i class="las la-briefcase"></i>';
                            html += '</div>';
                            html += '<div class="item-content">';
                            html += '<div class="item-title">' + highlight(item.title, q) + '</div>';
                            html += '<div class="item-subtitle">';
                            if (item.company) html += '<span>' + escHtml(item.company) + '</span>';
                            if (item.location) html += '<span><i class="las la-map-marker"></i>' + escHtml(item.location) + '</span>';
                            html += '</div></div>';
                            if (item.type_name) html += '<span class="item-badge">' + escHtml(item.type_name) + '</span>';
                            html += '</div>';
                        });
                    }

                    if (companies.length) {
                        html += '<div class="jobiizy-autocomplete-section">Entreprises</div>';
                        companies.forEach(function (item) {
                            html += '<div class="jobiizy-autocomplete-item jzog-ac-fill" data-value="' + escHtml(item.title) + '">';
                            html += '<div class="item-icon">';
                            html += item.logo ? '<img src="' + escHtml(item.logo) + '" alt="">' : '<i class="las la-building"></i>';
                            html += '</div>';
                            html += '<div class="item-content">';
                            html += '<div class="item-title">' + highlight(item.title, q) + '</div>';
                            if (item.jobs_count) html += '<div class="item-subtitle">' + item.jobs_count + ' offre(s)</div>';
                            html += '</div></div>';
                        });
                    }

                    $drop.find('.jobiizy-autocomplete-results').html(html).addClass('show');
                }).fail(function () {
                    $drop.find('.jobiizy-autocomplete-loader').removeClass('show');
                    hideDrop($drop);
                });
            }, DEBOUNCE_MS);
        });

        // Navigation clavier
        $input.on('keydown', function (e) {
            var $items    = $drop.find('.jobiizy-autocomplete-item');
            var $selected = $items.filter('.selected');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!$selected.length) $items.first().addClass('selected');
                else $selected.removeClass('selected').nextAll('.jobiizy-autocomplete-item').first().addClass('selected');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if ($selected.length) $selected.removeClass('selected').prevAll('.jobiizy-autocomplete-item').first().addClass('selected');
            } else if (e.key === 'Enter' && $selected.length) {
                e.preventDefault();
                $input.val($selected.data('value'));
                hideDrop($drop);
                // Laisse le formulaire se soumettre normalement
            } else if (e.key === 'Escape') {
                hideDrop($drop);
            }
        });

        $input.on('blur', function () { setTimeout(function () { hideDrop($drop); }, 200); });
    }

    // ── Autocomplétion localisation ───────────────────────────────────────────
    function setupLocation() {
        var $input = $('#jzog-location');
        var $drop  = $input.closest('.jzog-field').find('.jobiizy-autocomplete-dropdown');
        if (!$input.length || !$drop.length) return;

        $input.on('input', function () {
            var q = $(this).val().trim();
            clearTimeout(searchTimer);
            if (q.length < 2) { hideDrop($drop); return; }

            searchTimer = setTimeout(function () {
                $drop.addClass('show');
                $drop.find('.jobiizy-autocomplete-loader').addClass('show');
                $drop.find('.jobiizy-autocomplete-results').removeClass('show').empty();

                $.post(ajaxurl, { action: 'jobiizy_autocomplete_location', query: q }, function (res) {
                    $drop.find('.jobiizy-autocomplete-loader').removeClass('show');
                    if (!res.success || !res.data.length) {
                        $drop.find('.jobiizy-autocomplete-results')
                            .html('<div class="jobiizy-autocomplete-empty"><i class="las la-map-marker"></i>Aucune localisation trouvée</div>')
                            .addClass('show');
                        return;
                    }
                    var html = '';
                    res.data.forEach(function (item) {
                        html += '<div class="jobiizy-autocomplete-item jzog-ac-fill" data-value="' + escHtml(item.name) + '">';
                        html += '<div class="item-icon"><i class="las la-map-marker"></i></div>';
                        html += '<div class="item-content">';
                        html += '<div class="item-title">' + highlight(item.name, q) + '</div>';
                        if (item.count) html += '<div class="item-subtitle">' + item.count + ' offre' + (item.count > 1 ? 's' : '') + '</div>';
                        html += '</div></div>';
                    });
                    $drop.find('.jobiizy-autocomplete-results').html(html).addClass('show');
                }).fail(function () {
                    $drop.find('.jobiizy-autocomplete-loader').removeClass('show');
                    hideDrop($drop);
                });
            }, DEBOUNCE_MS);
        });

        $input.on('keydown', function (e) {
            var $items    = $drop.find('.jobiizy-autocomplete-item');
            var $selected = $items.filter('.selected');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!$selected.length) $items.first().addClass('selected');
                else $selected.removeClass('selected').nextAll('.jobiizy-autocomplete-item').first().addClass('selected');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if ($selected.length) $selected.removeClass('selected').prevAll('.jobiizy-autocomplete-item').first().addClass('selected');
            } else if (e.key === 'Enter' && $selected.length) {
                e.preventDefault();
                $input.val($selected.data('value'));
                hideDrop($drop);
            } else if (e.key === 'Escape') {
                hideDrop($drop);
            }
        });

        $input.on('blur', function () { setTimeout(function () { hideDrop($drop); }, 200); });
    }

// ── Drawer filtres mobile ─────────────────────────────
function setupFiltersDrawer() {
    var $btn     = $('#jzog-btn-filters');
    var $drawer  = $('#jzog-mobile-filters');
    var $overlay = $('#jzog-drawer-overlay');
    var $bar     = $('.jzog-search-bar');
    var $drawerAnchor = $('<span class="jzog-drawer-anchor" hidden></span>');
    if (!$btn.length) return;

    $drawer.after($drawerAnchor);

    function syncDrawerMount() {
        if (window.matchMedia('(max-width: 900px)').matches) {
            if (!$drawer.parent().is('body')) {
                $drawer.appendTo(document.body);
            }
        } else if (!$drawerAnchor.prev().is($drawer)) {
            $drawer.insertBefore($drawerAnchor);
        }
    }

    $drawer.on('mousedown touchstart', function(e) {
        e.stopPropagation();
    });

    $drawer.on('click', '.jzog-ac-fill', function(e) {
        e.preventDefault();
        e.stopPropagation();
        fillAutocompleteItem($(this));
    });

    $drawer.on('click', function(e) {
        e.stopPropagation();
    });
    function openDrawer() {
        $drawer.addClass('is-open');
        $overlay.addClass('is-open');
        $btn.addClass('is-active');
        $bar.addClass('has-open-drawer');
        $('body').css('overflow', 'hidden');
    }

    function closeDrawer() {
        $drawer.removeClass('is-open');
        $overlay.removeClass('is-open');
        $btn.removeClass('is-active');
        $bar.removeClass('has-open-drawer');
        $('body').css('overflow', '');
    }

    $btn.on('click', function(e) {
        e.stopPropagation();
        syncDrawerMount();
        openDrawer();
    });

	$overlay.on('click', closeDrawer);
    // $drawer.on('click', function(e) { e.stopPropagation(); });
    function updateBadge() {
        var count = 0;
        if ($('#jzog-location').val()) count++;
        if ($('select[name="search_categories"]').val()) count++;
        if ($('select[name="search_job_type"]').val()) count++;

        var $badge = $btn.find('.jzog-filters-badge');
        if (count > 0) {
            if (!$badge.length) $btn.append('<span class="jzog-filters-badge">' + count + '</span>');
            else $badge.text(count);
        } else {
            $badge.remove();
        }
    }

    $drawer.on('change input', 'select, input', updateBadge);

    syncDrawerMount();
    $(window).on('resize.jzogDrawerMount orientationchange.jzogDrawerMount', syncDrawerMount);
    updateBadge();
}


    // ── Clic sur un item → remplit le champ, PAS de soumission ──────────────
    // Classe .jzog-ac-fill pour isoler du handler global du split-view.js
    $(document).on('mousedown touchstart', '.jzog-ac-fill', function (e) {
        e.preventDefault();
        e.stopPropagation();
        fillAutocompleteItem($(this));
    });

    $(document).on('click', '.jzog-ac-fill', function (e) {
        e.preventDefault();
        e.stopPropagation();   // ne pas déclencher le submit du form
        fillAutocompleteItem($(this));
    });

    // ── Fermer au clic extérieur ──────────────────────────────────────────────
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.jzog-field').length) {
            $('.jzog-field .jobiizy-autocomplete-dropdown').each(function () {
                hideDrop($(this));
            });
        }
    });

    // ── Init ──────────────────────────────────────────────────────────────────
    function setStickyTop() {
        var header = document.querySelector('.cariera-main-header');
        var visibleHeaderH = 0;

        if (header) {
            var rect = header.getBoundingClientRect();
            visibleHeaderH = Math.max(0, Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0));
        }

        document.documentElement.style.setProperty('--jobiizy-sticky-top', Math.round(visibleHeaderH) + 'px');
    }
function setupScrollBehavior() {
    var $bar = $('.jzog-search-bar');
    var lastScrollY = window.scrollY || 0;
    var ticking = false;
    var threshold = 8;
    var scrollStopTimer = null;

    if (!$bar.length) return;

    function updateSearchBar() {
        var currentY = Math.max(window.scrollY || 0, 0);

        if ($('#jzog-mobile-filters').hasClass('is-open')) {
            $bar.removeClass('is-hidden');
            clearTimeout(scrollStopTimer);
            lastScrollY = currentY;
            ticking = false;
            return;
        }

        if (currentY <= 40) {
            $bar.removeClass('is-hidden');
        } else if (currentY > lastScrollY + threshold) {
            $bar.addClass('is-hidden');
        } else if (currentY < lastScrollY - threshold) {
            $bar.removeClass('is-hidden');
        }

        lastScrollY = currentY;
        ticking = false;
    }

    $(window).on('scroll.jzog', function() {
        setStickyTop();

        if (!ticking) {
            window.requestAnimationFrame(updateSearchBar);
            ticking = true;
        }

        clearTimeout(scrollStopTimer);
        scrollStopTimer = setTimeout(function() {
            if (!$('#jzog-mobile-filters').hasClass('is-open')) {
                $bar.removeClass('is-hidden');
            }
        }, 650);
    });
}
   $(function () {
        setStickyTop();
        $(window).on('resize', setStickyTop);

        setupKeywords();
        setupLocation();
        setupFiltersDrawer();
        setupScrollBehavior();

    });
    

})(jQuery);
