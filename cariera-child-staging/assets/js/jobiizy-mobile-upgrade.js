/**
 * JobiiZy - Mobile Upgrade v9.0
 *
 * SIMPLIFICATION v9.0 :
 *   - ✅ Suppression du système compact/normal (inutile sur mobile)
 *   - ✅ Scroll DOWN → barre cachée, scroll UP → barre visible immédiatement
 *   - ✅ Suppression spacer (causait le bandeau sombre)
 *   - ✅ HC-Offcanvas-Nav conservé
 *   - ✅ Modal, filtres, tags, API publique conservés
 *
 * @version 9.0
 * @requires jQuery, hcOffcanvasNav
 */

(function ($) {
  "use strict";

  // =========================================================
  // HELPERS
  // =========================================================

  function isMobile() {
    return window.innerWidth <= 1024;
  }

  function debounce(fn, wait) {
    var t;
    return function () {
      clearTimeout(t);
      var args = arguments;
      var ctx = this;
      t = setTimeout(function () { fn.apply(ctx, args); }, wait);
    };
  }

  function forceUnlockScroll() {
    document.documentElement.classList.remove(
      "mm-wrapper_blocking", "mm-wrapper_opened",
      "mm-wrapper_background", "mm-wrapper_opening"
    );
    document.body.style.overflow = "";
    document.body.style.position = "";
    document.body.style.top = "";
    document.documentElement.style.overflow = "";
    document.documentElement.style.position = "";
    document.documentElement.style.top = "";
    document.querySelectorAll(".mm-wrapper__blocker").forEach(function (b) { b.remove(); });
  }

  function killMmenuClones() {
    document.querySelectorAll("nav.mmenu-init, nav.mm-menu").forEach(function (n) { n.remove(); });
    document.querySelectorAll(".mm-wrapper__blocker").forEach(function (b) { b.remove(); });
    forceUnlockScroll();
  }

  // =========================================================
  // HC NAV
  // =========================================================

  var hcInited = false;
  var mmenuKillerObserver = null;

  function ensureHcTriggerInSlot() {
    var slot = document.querySelector(
      "header.cariera-main-header .mmenu-trigger, header.main-header .mmenu-trigger"
    );
    if (!slot) return null;

    var carieraBtn = slot.querySelector("#mobile-nav-toggler");
    if (carieraBtn) carieraBtn.style.display = "none";

    var trigger = slot.querySelector(".hc-nav-trigger.hc-nav-1");
    if (!trigger) {
      trigger = document.createElement("a");
      trigger.href = "#";
      trigger.className = "hc-nav-trigger hc-nav-1";
      trigger.setAttribute("role", "button");
      trigger.setAttribute("aria-label", "Open Menu");
      trigger.innerHTML = "<span></span>";
      slot.appendChild(trigger);
    }
    return trigger;
  }

  // =========================================================
  // BADGES SOUS-MENUS
  // =========================================================

  function jobiizyAddHcSubmenuBadges() {
    var root = document.querySelector(".hc-offcanvas-nav");
    if (!root) return;

    root.querySelectorAll(".jb-subcount").forEach(function (el) { el.remove(); });

    root.querySelectorAll("li").forEach(function (li) {
      var sub = li.querySelector("ul");
      if (!sub) return;
      var label = li.querySelector("a, span");
      if (!label) return;

      var count = Array.from(sub.children).filter(function (ch) { return ch.tagName === "LI"; }).length;
      if (!count) return;

      var badge = document.createElement("span");
      badge.className = "jb-subcount";
      badge.textContent = count;

      var nextBtn = li.querySelector(".nav-next, .hc-nav-next, button[class*='next']");
      if (nextBtn && nextBtn.parentNode) {
        nextBtn.parentNode.insertBefore(badge, nextBtn);
      } else {
        li.appendChild(badge);
      }
    });
  }

  function jobiizyBindHcBadgesObserverOnce() {
    if (window.__jobiizyHcBadgesObserver) return;
    var obs = new MutationObserver(function () {
      if (document.documentElement.classList.contains("hc-nav-open")) {
        setTimeout(jobiizyAddHcSubmenuBadges, 30);
      }
    });
    obs.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
    window.__jobiizyHcBadgesObserver = obs;
  }

  // =========================================================
  // INIT MOBILE NAV
  // =========================================================

  function initMobileNav() {
    if (!isMobile()) return;

    var $navWrapper = $("header .main-nav-wrapper").first();
    if (!$navWrapper.length) return;

    if (!$(".jobiizy-mobile-nav").length) {
      var $mobileNav = $navWrapper
        .clone()
        .addClass("main-mobile-nav jobiizy-mobile-nav")
        .removeClass("main-nav-wrapper")
        .insertBefore($navWrapper);

      $mobileNav.find("ul").removeAttr("id").removeClass("main-menu main-nav");
      $mobileNav.find("li a").removeAttr("data-toggle aria-haspopup aria-expanded");
      $mobileNav.find("ul.dropdown-menu").removeAttr("class style");
    }

    var trigger = ensureHcTriggerInSlot();
    if (!trigger) return;

    if (hcInited) {
      forceUnlockScroll();
      killMmenuClones();
      return;
    }

    try {
      new hcOffcanvasNav(".jobiizy-mobile-nav", {
        disableAt: 1025,
        customToggle: ".hc-nav-trigger.hc-nav-1",
        navTitle:
          (window.cariera_settings &&
            window.cariera_settings.strings &&
            window.cariera_settings.strings.mmenu_text) ||
          "Menu principal",
        levelTitles: true,
        position: "right",
        closeOnClick: true,
        pushContent: false,
      });
    } catch (e) {
      console.warn("[Jobiizy][HC] init error:", e);
      return;
    }

    hcInited = true;
    jobiizyBindHcBadgesObserverOnce();
    setTimeout(jobiizyAddHcSubmenuBadges, 80);

    var navRoot = document.querySelector(".jobiizy-mobile-nav");
    if (navRoot) {
      navRoot.addEventListener("hcOffcanvasNavOpen", function () {
        document.documentElement.classList.add("hc-nav-open");
        forceUnlockScroll();
        setTimeout(jobiizyAddHcSubmenuBadges, 30);
      });
      navRoot.addEventListener("hcOffcanvasNavClose", function () {
        document.documentElement.classList.remove("hc-nav-open");
        forceUnlockScroll();
      });
    }

    if (!mmenuKillerObserver) {
      mmenuKillerObserver = new MutationObserver(function () { killMmenuClones(); });
      mmenuKillerObserver.observe(document.body, { childList: true, subtree: true });
    }

    killMmenuClones();
    setTimeout(killMmenuClones, 100);
    setTimeout(killMmenuClones, 300);
    setTimeout(killMmenuClones, 600);
    setTimeout(killMmenuClones, 1000);
    setTimeout(function () {
      if (mmenuKillerObserver) mmenuKillerObserver.disconnect();
    }, 4000);
  }

  // =========================================================
  // DOCUMENT READY
  // =========================================================

  $(document).ready(function () {

// Fermeture menu HC au passage desktop — toutes pages
window.addEventListener("resize", function() {
    if (window.innerWidth > 1024) {
        var navEl = document.querySelector('.hc-offcanvas-nav');
        if (navEl) navEl.classList.remove('nav-open');
        document.documentElement.classList.remove('hc-nav-yscroll');
        document.body.classList.remove('hc-nav-open');
    }
});

    if (!isMobile()) {
      console.log("[MobileUpgrade] Desktop, ignoré");
      return;
    }

    console.log("[MobileUpgrade] 📱 Init v9.0");

    // Menu hamburger — toutes les pages
    initMobileNav();
    $(window).on("resize", debounce(function () { initMobileNav(); }, 150));
  // Si pas de modal de recherche sur cette page → stop ici
    // (menu HC fonctionnel, mais pas de barre de recherche)
    if (!document.getElementById('jobiizy-mobile-search-modal')) {
      console.log("[MobileUpgrade] Pas de modal sur cette page — menu seul actif");
      return;
    }

    // =========================================================
    // CONFIG
    // =========================================================
    var CFG = {
      scrollDelta: 8,
      debug: false,
    };

    function log() {
      if (CFG.debug) console.log.apply(console, ["[MobileUpgrade]"].concat(Array.from(arguments)));
    }

    // =========================================================
    // ÉLÉMENTS
    // =========================================================
    var $header      = $("header.cariera-main-header, header.main-header").first();
    var $bar, $trigger, $searchText, $filterBtn, $filterCount, $activeTags;
    var $modal, $closeModal, $submitModal;

    // =========================================================
    // CRÉER LA BARRE
    // =========================================================
    $("body").prepend(
      '<div class="jb-mobile-bar" id="jb-mobile-bar">' +
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
      '</div>'
    );

    $bar         = $("#jb-mobile-bar");
    $trigger     = $("#jb-mobile-search-trigger");
    $searchText  = $("#jb-search-text");
    $filterBtn   = $("#jb-mobile-filter-btn");
    $filterCount = $("#jb-filter-count");
    $activeTags  = $("#jb-mobile-active-tags");
    $modal       = $("#jobiizy-mobile-search-modal");
    $closeModal  = $("#jobiizy-mobile-close-search");
    $submitModal = $("#jobiizy-mobile-search-submit");

    // =========================================================
    // ÉTAT
    // =========================================================
    var lastScrollY      = window.scrollY;
    var ticking          = false;
    var menuIsOpen       = false;
    var detailIsOpen     = false;
    var filtersPopupOpen = false;
    var modalIsOpen      = false;
    var barVisible       = true;


// Positionner la barre sous le header
var adminH = document.getElementById("wpadminbar")
    ? document.getElementById("wpadminbar").offsetHeight : 0;
var hdrH = $header.length ? $header.outerHeight() : 60;
document.documentElement.style.setProperty(
    "--jb-header-height", (hdrH + adminH) + "px"
);


    function shouldBeHidden() {
      return menuIsOpen || detailIsOpen || filtersPopupOpen || modalIsOpen;
    }

    // =========================================================
    // SHOW / HIDE BARRE
    // =========================================================
function getHeaderOffset() {
    var adminH = document.getElementById("wpadminbar")
        ? document.getElementById("wpadminbar").offsetHeight : 0;
    // Header visible = barre en dessous, header caché = barre en haut
    var hdr = document.querySelector("header.cariera-main-header, header.main-header");
    var hdrVisible = hdr && !hdr.classList.contains("header-fixed") 
        ? hdr.offsetHeight : 0;
    return adminH + hdrVisible;
}


function showBar() {
    if (barVisible) return;
    barVisible = true;
    var offset = getHeaderOffset();
    document.documentElement.style.setProperty("--jb-header-height", offset + "px");
    $bar.removeClass("jb-hidden-for-scroll");
    log("👁️ Barre visible, offset: " + offset);
}


    function hideBar() {
      if (!barVisible || shouldBeHidden()) return;
      barVisible = false;
      $bar.addClass("jb-hidden-for-scroll");
      log("👻 Barre cachée");
    }

    // =========================================================
    // SCROLL
    // =========================================================
    function onScroll() {
      if (shouldBeHidden()) {
        lastScrollY = window.scrollY;
        return;
      }

      var currentY = window.scrollY;
      var delta    = currentY - lastScrollY;
    // Mettre à jour la position selon le header Cariera
    var offset = getHeaderOffset();
    document.documentElement.style.setProperty("--jb-header-height", offset + "px");

      // Haut de page → toujours visible
      if (currentY < 50) {
        showBar();
        lastScrollY = currentY;
        return;
      }

      // Scroll DOWN → cacher
      if (delta > CFG.scrollDelta) {
        hideBar();
      }

      // Scroll UP → montrer immédiatement
      if (delta < 0) {
        showBar();
      }

      lastScrollY = currentY;
    }

    window.addEventListener("scroll", function () {
      if (!ticking) {
        window.requestAnimationFrame(function () {
          onScroll();
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });

    // =========================================================
    // WATCH MENU
    // =========================================================
    function watchMenu() {
      function checkMenuState() {
        var wasOpen = menuIsOpen;
        menuIsOpen =
          document.documentElement.classList.contains("hc-nav-open") ||
          document.body.classList.contains("hc-nav-open") ||
          !!document.querySelector(".hc-offcanvas-nav.nav-open");

        if (wasOpen !== menuIsOpen) {
          log("🍔 Menu:", menuIsOpen ? "OUVERT" : "FERMÉ");
          if (menuIsOpen) {
            $bar.addClass("jb-hidden-for-menu");
          } else {
            $bar.removeClass("jb-hidden-for-menu");
            showBar();
          }
        }
      }

      var obs = new MutationObserver(function () { checkMenuState(); });
      obs.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
      obs.observe(document.body, { attributes: true, attributeFilter: ["class"] });
      checkMenuState();
    }

    // =========================================================
    // WATCH POPUP FILTRES
    // =========================================================
    function watchFiltersPopup() {
      var $fp = $("#jobiizy-filters-popup, .jobiizy-filters-popup, [class*='filters-popup']");
      if (!$fp.length) { log("⚠️ Popup filtres introuvable"); return; }

      function check() {
        var wasOpen = filtersPopupOpen;
        filtersPopupOpen =
          $fp.hasClass("show") || $fp.hasClass("active") ||
          $fp.hasClass("is-active") || $fp.hasClass("opened") ||
          $fp.is(":visible");

        if (wasOpen !== filtersPopupOpen) {
          log("🎚️ Filtres:", filtersPopupOpen ? "OUVERTS" : "FERMÉS");
          if (filtersPopupOpen) {
            $bar.addClass("jb-hidden-for-filters");
            $("body").addClass("jobiizy-filters-active");
          } else {
            $bar.removeClass("jb-hidden-for-filters");
            $("body").removeClass("jobiizy-filters-active");
            showBar();
          }
        }
      }

      var obs = new MutationObserver(check);
      $fp.each(function () {
        obs.observe(this, { attributes: true, attributeFilter: ["class", "style"] });
      });
      check();
    }

    // =========================================================
    // WATCH OFFRE OUVERTE
    // =========================================================
    function watchDetailColumn() {
      var $dc = $(".jobiizy-detail-column");
      if (!$dc.length) { log("⚠️ .jobiizy-detail-column introuvable"); return; }

      function check() {
        var wasOpen = detailIsOpen;
        detailIsOpen = $dc.hasClass("open");

        if (wasOpen !== detailIsOpen) {
          log("📄 Offre:", detailIsOpen ? "OUVERTE" : "FERMÉE");
          if (detailIsOpen) {
            $bar.addClass("jb-hidden-for-detail");
          } else {
            $bar.removeClass("jb-hidden-for-detail");
            showBar();
          }
        }
      }

      var obs = new MutationObserver(check);
      obs.observe($dc[0], { attributes: true, attributeFilter: ["class"] });
      check();
    }

    // =========================================================
    // MODAL RECHERCHE
    // =========================================================
    function openSearchModal() {
      if (!$modal.length) return;

      var kw  = $("#jobiizy-keywords").val() || "";
      var loc = $("#jobiizy-location").val() || "";
      $("#jobiizy-mobile-keywords").val(kw);
      $("#jobiizy-mobile-location").val(loc);

      modalIsOpen = true;
      $bar.addClass("jb-hidden-for-modal");

      $modal.css("display", "flex");
      $modal[0].offsetHeight;
      $modal.addClass("show");
      $("body").css("overflow", "hidden");

      setTimeout(function () { $("#jobiizy-mobile-keywords").trigger("focus"); }, 400);
    }

    function closeSearchModal() {
      modalIsOpen = false;
      $bar.removeClass("jb-hidden-for-modal");
      showBar();

      $modal.removeClass("show").css("transform", "translateY(100%)");
      setTimeout(function () { $modal.css("display", "none"); }, 350);
      $("body").css("overflow", "");
    }

    $trigger.on("click", function (e) {
      e.preventDefault(); e.stopPropagation();
      openSearchModal();
    });

    $closeModal.on("click touchend", function (e) {
      e.preventDefault(); e.stopPropagation();
      closeSearchModal();
    });

    $modal.on("click", function (e) {
      var $t = $(e.target);
      if ($t.hasClass("jobiizy-mobile-close-search") || $t.closest(".jobiizy-mobile-close-search").length) {
        e.preventDefault(); e.stopPropagation();
        closeSearchModal();
      }
    });

    $(document).on("keyup", function (e) {
      if (e.key === "Escape" && $modal.hasClass("show")) closeSearchModal();
    });

    setTimeout(function () {
      var btn = document.getElementById("jobiizy-mobile-close-search");
      if (btn) {
        var s = getComputedStyle(btn);
        if (s.display === "none" || s.visibility === "hidden" || s.opacity === "0" || s.width === "0px") {
          btn.style.cssText = "display:flex!important;align-items:center!important;justify-content:center!important;width:44px!important;height:44px!important;";
        }
      }
    }, 500);

    // =========================================================
    // SUBMIT MODAL
    // =========================================================
    $submitModal.on("click", function (e) {
      e.preventDefault(); e.stopPropagation();

      var kw  = $("#jobiizy-mobile-keywords").val() || "";
      var loc = $("#jobiizy-mobile-location").val() || "";
      $("#jobiizy-keywords").val(kw);
      $("#jobiizy-location").val(loc);

      closeSearchModal();
      updateSearchText();
      updateActiveTags();

      if (typeof window.doLiveSearch === "function") {
        window.doLiveSearch();
      } else {
        var url = new URL(window.location.href);
        url.searchParams.delete("search_keywords");
        url.searchParams.delete("search_location");
        url.searchParams.delete("search_categories");
        if (kw.trim()) url.searchParams.set("search_keywords", kw.trim());
        if (loc.trim()) url.searchParams.set("search_location", loc.trim());
        window.location.href = url.toString();
      }
    });

    // =========================================================
    // FILTRES
    // =========================================================
    $filterBtn.on("click", function (e) {
      e.preventDefault();
      var $fp = $("#jobiizy-filters-popup");
      if ($fp.length) {
        $fp.addClass("show");
        $("body").css("overflow", "hidden");
      } else {
        openSearchModal();
      }
    });

    // =========================================================
    // TEXTE + TAGS
    // =========================================================
    function updateSearchText() {
      var kw  = $("#jobiizy-keywords").val() || "";
      var loc = $("#jobiizy-location").val() || "";
      var parts = [];
      if (kw.trim()) parts.push(kw.trim());
      if (loc.trim()) parts.push(loc.trim());
      $searchText.text(parts.length > 0 ? parts.join(" · ") : "Rechercher un emploi...");
      $searchText.toggleClass("has-value", parts.length > 0);
    }

    function updateActiveTags() {
      var filters = [];
      var kw  = $("#jobiizy-keywords").val();
      var loc = $("#jobiizy-location").val();

      if (kw && kw.trim())
        filters.push({ label: "🔍 " + truncate(kw.trim(), 20), param: "search_keywords" });
      if (loc && loc.trim())
        filters.push({ label: "📍 " + truncate(loc.trim(), 20), param: "search_location" });

      var params = new URLSearchParams(window.location.search);
      if (params.get("search_keywords") && (!kw || !kw.trim()))
        filters.push({ label: "🔍 " + truncate(params.get("search_keywords"), 20), param: "search_keywords" });
      if (params.get("search_location") && (!loc || !loc.trim()))
        filters.push({ label: "📍 " + truncate(params.get("search_location"), 20), param: "search_location" });

      $activeTags.empty();
      filters.forEach(function (f) {
        $activeTags.append(
          '<div class="jb-mobile-tag"><span>' + escapeHtml(f.label) +
          '</span><button type="button" class="jb-tag-remove" data-param="' + f.param +
          '"><i class="las la-times"></i></button></div>'
        );
      });
      $activeTags.toggleClass("visible", filters.length > 0);
      $filterCount.text(filters.length).toggleClass("visible", filters.length > 0);
    }

    $activeTags.on("click", ".jb-tag-remove", function (e) {
      e.stopPropagation();
      var p = $(this).data("param");
      if (p === "search_keywords") $("#jobiizy-keywords, #jobiizy-mobile-keywords").val("");
      if (p === "search_location") $("#jobiizy-location, #jobiizy-mobile-location").val("");
      updateSearchText();
      updateActiveTags();
      if (typeof window.doLiveSearch === "function") window.doLiveSearch();
    });

    function truncate(t, m) {
      return t && t.length > m ? t.substring(0, m) + "…" : t || "";
    }

    function escapeHtml(t) {
      var d = document.createElement("div");
      d.textContent = t;
      return d.innerHTML;
    }

    // =========================================================
    // RESIZE
    // =========================================================
window.addEventListener("resize", debounce(function () {
    if (window.innerWidth > 1024) {
        $bar.remove();
        $header.attr("style", "");
        // Fermer le menu HC
        $('.hc-offcanvas-nav').removeClass('nav-open');
        $('html').removeClass('hc-nav-yscroll');
        $('body').removeClass('hc-nav-open');
    }
}, 200));

    // =========================================================
    // INIT FINAL
    // =========================================================
    updateSearchText();
    updateActiveTags();
    watchMenu();
    watchFiltersPopup();
    watchDetailColumn();

    var origSearch = window.doLiveSearch;
    if (typeof origSearch === "function") {
      window.doLiveSearch = function () {
        origSearch.apply(this, arguments);
        setTimeout(function () {
          updateSearchText();
          updateActiveTags();
        }, 500);
      };
    }

    console.log("[MobileUpgrade] ✅ v9.0 initialisé");

    // =========================================================
    // API PUBLIQUE
    // =========================================================
    window.JbMobileUpgrade = {
      openModal:     openSearchModal,
      closeModal:    closeSearchModal,
      updateText:    updateSearchText,
      updateTags:    updateActiveTags,
      showBar:       showBar,
      hideBar:       hideBar,
      setDebug:      function (on) { CFG.debug = on; },
      isMenuOpen:    function () { return menuIsOpen; },
      isDetailOpen:  function () { return detailIsOpen; },
      isFiltersOpen: function () { return filtersPopupOpen; },
      isModalOpen:   function () { return modalIsOpen; },
      isBarVisible:  function () { return barVisible; },
    };

  });

})(jQuery);