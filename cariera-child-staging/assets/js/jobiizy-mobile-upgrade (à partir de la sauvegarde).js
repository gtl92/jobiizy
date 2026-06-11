/**
 * JobiiZy - Mobile Upgrade v8.6
 * - HC-Offcanvas mobile nav in Cariera mmenu-trigger slot
 * - Force cleanup of mmenu blockers/classes to avoid scroll lock
 * - Keep your existing sticky/search bar logic intact
 *
 * @requires jQuery
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
    let t;
    return function () {
      clearTimeout(t);
      const args = arguments;
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function forceUnlockScroll() {
    // mmenu leftovers
    document.documentElement.classList.remove(
      "mm-wrapper_blocking",
      "mm-wrapper_opened",
      "mm-wrapper_background",
      "mm-wrapper_opening"
    );

    // body/html locks
    document.body.style.overflow = "";
    document.body.style.position = "";
    document.body.style.top = "";
    document.documentElement.style.overflow = "";
    document.documentElement.style.position = "";
    document.documentElement.style.top = "";

    // blockers
    document.querySelectorAll(".mm-wrapper__blocker").forEach((b) => b.remove());
  }

  function killMmenuClones() {
    // remove injected mmenu nav clones if they show up
    document.querySelectorAll("nav.mmenu-init, nav.mm-menu").forEach((n) => n.remove());
    document.querySelectorAll(".mm-wrapper__blocker").forEach((b) => b.remove());
    forceUnlockScroll();
  }

  // =========================================================
  // HC NAV (Mobile)
  // =========================================================

  let hcInited = false;
  let mmenuKillerObserver = null;

  function ensureHcTriggerInSlot() {
    const slot = document.querySelector("header.cariera-main-header .mmenu-trigger, header.main-header .mmenu-trigger");
    if (!slot) return null;

    // remove Cariera button if present (we'll keep slot only)
    const carieraBtn = slot.querySelector("#mobile-nav-toggler");
    if (carieraBtn) carieraBtn.style.display = "none";

    // ensure hc trigger exists inside the slot
    let trigger = slot.querySelector(".hc-nav-trigger.hc-nav-1");
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

  function initMobileNav() {
    if (!isMobile()) return;

    // ensure header/nav exists
    const $navWrapper = $("header .main-nav-wrapper").first();
    if (!$navWrapper.length) return;

    // Create a clean clone nav once
    if (!$(".jobiizy-mobile-nav").length) {
      const $mobileNav = $navWrapper
        .clone()
        .addClass("main-mobile-nav jobiizy-mobile-nav")
        .removeClass("main-nav-wrapper")
        .insertBefore($navWrapper);

      $mobileNav.find("ul").removeAttr("id").removeClass("main-menu main-nav");
      $mobileNav.find("li a").removeAttr("data-toggle aria-haspopup aria-expanded");
      $mobileNav.find("ul.dropdown-menu").removeAttr("class style");
    }

    // Ensure trigger is in the Cariera slot
    const trigger = ensureHcTriggerInSlot();
    if (!trigger) return;

    // If already initialized, just ensure clean state
    if (hcInited) {
      forceUnlockScroll();
      killMmenuClones();
      return;
    }

    // Init hc-offcanvas-nav
    // IMPORTANT: we use customToggle = our trigger (not #mobile-nav-toggler)
    try {
      new hcOffcanvasNav(".jobiizy-mobile-nav", {
        disableAt: 1025,
        customToggle: ".hc-nav-trigger.hc-nav-1",
        navTitle: (window.cariera_settings && window.cariera_settings.strings && window.cariera_settings.strings.mmenu_text) || "Menu principal",
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

    // Sync open/close state + cleanup
    const navRoot = document.querySelector(".jobiizy-mobile-nav");
    if (navRoot) {
      navRoot.addEventListener("hcOffcanvasNavOpen", function () {
        document.documentElement.classList.add("hc-nav-open");
        forceUnlockScroll(); // prevent mmenu from locking scroll
      });

      navRoot.addEventListener("hcOffcanvasNavClose", function () {
        document.documentElement.classList.remove("hc-nav-open");
        forceUnlockScroll();
      });
    }

    // Observe and kill mmenu if theme loads it dynamically (chunk)
    if (!mmenuKillerObserver) {
      mmenuKillerObserver = new MutationObserver(function () {
        killMmenuClones();
      });
      mmenuKillerObserver.observe(document.body, { childList: true, subtree: true });
    }

    // One immediate cleanup
    killMmenuClones();
  }

  // =========================================================
  // EXISTING BAR/HEADER LOGIC (kept)
  // =========================================================

  $(document).ready(function () {
    if (!isMobile()) {
      console.log("[MobileUpgrade] Desktop, ignoré");
      return;
    }

    console.log("[MobileUpgrade] 📱 Init v8.6 (HC in mmenu slot)");
    initMobileNav();

    // Re-init on resize (debounced)
    $(window).on(
      "resize",
      debounce(function () {
        initMobileNav();
      }, 150)
    );

    // =========================================================
    // CONFIG
    // =========================================================
    var CFG = {
      scrollDelta: 6,
      compactThreshold: 100,
      transitionDuration: 350,
      debug: false,
    };

    function log() {
      if (CFG.debug) console.log.apply(console, ["[MobileUpgrade]"].concat(Array.from(arguments)));
    }

    // =========================================================
    // ELEMENTS
    // =========================================================
    var $header = $("header.cariera-main-header, header.main-header").first();
    var headerH = $header.length ? $header.outerHeight() : 60;
    var adminBarH = $("#wpadminbar").length ? $("#wpadminbar").outerHeight() : 0;

    log("📏 Header:", headerH + "px, AdminBar:", adminBarH + "px");

    if ($header.length) {
      $header.attr("style", "").addClass("jb-header-visible");
    }

    // =========================================================
    // CREATE BAR
    // =========================================================
    var barHTML =
      '<div class="jb-mobile-bar jb-normal" id="jb-mobile-bar">' +
      '<div class="jb-mobile-bar-inner">' +
      '<div class="jb-mobile-search-trigger" id="jb-mobile-search-trigger">' +
      '<i class="las la-search jb-search-icon"></i>' +
      '<span class="jb-search-text" id="jb-search-text">Rechercher un emploi...</span>' +
      "</div>" +
      '<button type="button" class="jb-mobile-filter-btn" id="jb-mobile-filter-btn">' +
      '<i class="las la-sliders-h"></i>' +
      '<span class="jb-filter-count" id="jb-filter-count">0</span>' +
      "</button>" +
      "</div>" +
      '<div class="jb-mobile-active-tags" id="jb-mobile-active-tags"></div>' +
      "</div>";

    $("body").prepend(barHTML);

    var $bar = $("#jb-mobile-bar");
    var $trigger = $("#jb-mobile-search-trigger");
    var $searchText = $("#jb-search-text");
    var $filterBtn = $("#jb-mobile-filter-btn");
    var $filterCount = $("#jb-filter-count");
    var $activeTags = $("#jb-mobile-active-tags");

    // Spacer
    var $spacer = $('<div class="jb-mobile-spacer" id="jb-mobile-spacer"></div>');
    var $mainContent = $(".jobiizy-split-view-outer, .main-content, main").first();
    if ($mainContent.length) {
      $mainContent.prepend($spacer);
    }

    // Modal
    var $modal = $("#jobiizy-mobile-search-modal");
    var $closeModal = $("#jobiizy-mobile-close-search");
    var $submitModal = $("#jobiizy-mobile-search-submit");

    // =========================================================
    // STATE
    // =========================================================
    var state = "init";
    var lastScrollY = window.scrollY;
    var ticking = false;
    var menuIsOpen = false;
    var detailIsOpen = false;
    var filtersPopupOpen = false;
    var modalIsOpen = false;

    // =========================================================
    // DIMENSIONS (CSS vars)
    // =========================================================
    (function initCSSVarsEarly() {
      var adminH = document.getElementById("wpadminbar") ? document.getElementById("wpadminbar").offsetHeight : 0;
      var hdr = document.querySelector("header.cariera-main-header, header.main-header");
      var hdrH = hdr ? hdr.offsetHeight : 60;
      document.documentElement.style.setProperty("--jb-header-height", hdrH + adminH + "px");
      document.documentElement.style.setProperty("--jb-admin-bar-height", adminH + "px");
    })();

    function measureHeader() {
      headerH = $header.length ? $header.outerHeight() : 60;
      adminBarH = $("#wpadminbar").length ? $("#wpadminbar").outerHeight() : 0;
      document.documentElement.style.setProperty("--jb-header-height", headerH + adminBarH + "px");
      document.documentElement.style.setProperty("--jb-admin-bar-height", adminBarH + "px");
    }

    function shouldBeHidden() {
      return menuIsOpen || detailIsOpen || filtersPopupOpen || modalIsOpen;
    }

    function showHeader() {
      $header.removeClass("jb-header-hidden").addClass("jb-header-visible");
    }

    function hideHeader() {
      $header.removeClass("jb-header-visible").addClass("jb-header-hidden");
    }

    function applyNormalMode(immediate) {
      if (shouldBeHidden()) return;

      if (!immediate) $bar.addClass("jb-transitioning");

      $bar
        .removeClass("jb-compact jb-hidden-for-menu jb-hidden-for-detail jb-hidden-for-filters jb-hidden-for-modal")
        .addClass("jb-normal");

      $bar[0].style.transform = "translateY(" + (headerH + adminBarH) + "px)";

      var barH = $bar.outerHeight() || 70;
      $spacer.css("height", headerH + adminBarH + barH - 20 + "px");

      showHeader();
      state = "normal";

      if (!immediate) {
        setTimeout(function () {
          $bar.removeClass("jb-transitioning");
        }, CFG.transitionDuration);
      }
    }

    function setNormal(immediate) {
      if (state === "normal" && !shouldBeHidden()) return;
      if (shouldBeHidden()) return;
      applyNormalMode(immediate);
    }

    function setCompact() {
      if (state === "compact") return;
      if (shouldBeHidden()) return;

      $bar.addClass("jb-transitioning");

      $bar
        .removeClass("jb-normal jb-hidden-for-menu jb-hidden-for-detail jb-hidden-for-filters jb-hidden-for-modal")
        .addClass("jb-compact");

      $bar[0].style.transform = "translateY(" + adminBarH + "px)";

      hideHeader();
      state = "compact";

      setTimeout(function () {
        $bar.removeClass("jb-transitioning");
      }, CFG.transitionDuration);
    }

    // Scroll handler
    var hasUserScrolled = false;

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
        if (state !== "normal") setNormal(true);
        lastScrollY = currentY;
        return;
      }

      if (isDown && isPastThreshold && state === "normal" && hasUserScrolled) {
        setCompact();
      }

      if (isUp && isPastThreshold && state === "compact") {
        setNormal(false);
      }

      lastScrollY = currentY;
    }

    window.addEventListener(
      "scroll",
      function () {
        if (!ticking) {
          window.requestAnimationFrame(function () {
            onScroll();
            ticking = false;
          });
          ticking = true;
        }
      },
      { passive: true }
    );

    // =========================================================
    // WATCH MENU (HC state)
    // =========================================================
    function watchMenu() {
      function checkMenuState() {
        var wasOpen = menuIsOpen;

        // HC open detection (robust)
        var hcOpen =
          document.documentElement.classList.contains("hc-nav-open") ||
          document.body.classList.contains("hc-nav-open") ||
          document.querySelector(".hc-offcanvas-nav.nav-open");

        menuIsOpen = !!hcOpen;

        if (wasOpen !== menuIsOpen) {
          if (menuIsOpen) {
            $bar.addClass("jb-hidden-for-menu");
          } else {
            $bar.removeClass("jb-hidden-for-menu");
            if (window.scrollY < CFG.compactThreshold) setNormal(true);
          }
        }
      }

      // Observe class changes
      var obs = new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          if (mutations[i].attributeName === "class") {
            checkMenuState();
            break;
          }
        }
      });

      obs.observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
      obs.observe(document.body, { attributes: true, attributeFilter: ["class"] });

      checkMenuState();
    }

    // =========================================================
    // INIT FINAL
    // =========================================================
    measureHeader();
    setNormal(true);
    watchMenu();

    // Re-measure on orientation change
    window.addEventListener(
      "orientationchange",
      function () {
        setTimeout(function () {
          measureHeader();
          if (window.scrollY < CFG.compactThreshold) setNormal(true);
        }, 250);
      },
      { passive: true }
    );
  });
})(jQuery);