(function ($) {
  "use strict";

  // -----------------------------
  // Utils
  // -----------------------------
  const log = (...a) => console.log("[Jobiizy]", ...a);
  const warn = (...a) => console.warn("[Jobiizy]", ...a);

  function normalizeUrl(u) {
    return (u || "").split("#")[0].trim();
  }

  // Debounce simple (évite 10 calls d’affilée sur mutations)
  function debounce(fn, wait = 150) {
    let t = null;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function getRightRoot() {
    const cta = document.querySelector(".jobiizy-split-cta a");
    if (cta) {
      return (
        cta.closest("main, #primary, article, section, .single_job_listing, .elementor-widget-container") ||
        cta.parentElement
      );
    }
    const header = document.querySelector(".page-header.job-header, .job-header");
    if (header) {
      return header.closest("main, #primary, article, section") || header.parentElement;
    }
    return document.body;
  }

  // -----------------------------
  // Cards: inject company name under logo
  // -----------------------------
  function getCompanyNameFromCard($card) {
    return (
      $card.find(".company_logo").attr("alt") ||
      $card.find(".company_logo").attr("title") ||
      ""
    ).trim();
  }

  function isGenericName(name) {
    if (!name) return true;
    const n = name.toLowerCase();
    return n.includes("logo") || n.includes("company logo") || n === "image";
  }

  function injectCompanyInCards() {
    $(".job_listing.job-grid, li.job-grid").each(function () {
      const $card = $(this);
      if ($card.find(".jobiizy-company-name-inline").length) return;

      const name = getCompanyNameFromCard($card);
      if (!name || isGenericName(name)) return;

      const $logoWrap = $card.find(".logo-wrapper").first();
      if (!$logoWrap.length) return;

      $logoWrap.append(
        `<div class="jobiizy-company-name-inline"><span>${$("<div>").text(name).html()}</span></div>`
      );
    });
  }

  // -----------------------------
  // Badge recruiter on right panel
  // -----------------------------
  function injectBadgeToRightPanel(name) {
    if (!name) return;

    const $descZone = $(".job-description.GTLGTLJOBIIZY, .single-job-listing .job-description").first();
    if (!$descZone.length) return;

    const safeName = isGenericName(name) ? "Recruteur" : name;

    $descZone.find(".jobiizy-recruiter-badge").remove();
    $descZone.prepend(`
      <div class="jobiizy-recruiter-badge">
        <i class="las la-check-circle"></i>
        <span class="label">Recruteur :</span>
        <span class="name">${$("<div>").text(safeName).html()}</span>
      </div>
    `);
  }

  // -----------------------------
  // Sync CTA (generic apply buttons) based on clicked card
  // -----------------------------
  function syncCTAFromCard($card) {
    if (!$card || !$card.length) return;

    const correctUrl = $card.find("a[href]").first().attr("href");
    if (!correctUrl) {
      warn("Impossible de récupérer l’URL de la carte");
      return;
    }

    let jobId = $card.attr("data-id") || $card.data("id") || $card.attr("id");
    if (jobId) jobId = jobId.toString().replace(/\D/g, "");

    // boutons cibles (tu peux réduire encore si tu veux)
    const $ctaButtons = $(
      ".job-sidebar .application_button, " +
      ".job-sidebar .cariera-main-button, " +
      ".listing-actions .application_button, " +
      ".widget-job-overview .application_button, " +
      ".single-job-listing .application_button, " +
      "a[href*=\"#apply\"], " +
      ".btn-apply, " +
      ".job-apply-btn"
    );

    $ctaButtons.each(function () {
      const $btn = $(this);

      if ($btn.is("a")) {
        let newUrl = correctUrl;
        if (!newUrl.includes("#apply")) newUrl += "#apply-form";
        $btn.attr("href", newUrl);
      }

      if (jobId) {
        $btn.attr("data-job-id", jobId);
        $btn.data("job-id", jobId);
      }
    });

    const $applicationForm = $("#job-application-form, .application-form, form[name=\"job_application\"]").first();
    if ($applicationForm.length && jobId) {
      $applicationForm.find('input[name="job_id"]').val(jobId);
    }
  }

  // -----------------------------
  // Sync your split CTA button using bookmark redirect_to (source of truth)
  // -----------------------------
  function syncSplitCTAWhenReady() {
    const rightEl = getRightRoot();
    const $right = $(rightEl);

    const $bm = $right.find('a.bookmark-notice[href*="redirect_to="]').first();
    if (!$bm.length) return;

    let url = null;
    try {
      const u = new URL($bm.attr("href"), window.location.origin);
      const redir = u.searchParams.get("redirect_to");
      if (redir) url = normalizeUrl(decodeURIComponent(redir));
    } catch (e) {}

    if (!url) return;

    const $btn = $right.find(".jobiizy-split-cta a").first().length
      ? $right.find(".jobiizy-split-cta a").first()
      : $(".jobiizy-split-cta a").first();

    if (!$btn.length) return;

    const current = normalizeUrl($btn.attr("href"));
    if (current === url) return;

    $btn.attr("href", url).attr("data-job-url", url);
    // log("Split CTA synchronisé ->", url);
  }

  const syncSplitCTAWhenReadyDebounced = debounce(syncSplitCTAWhenReady, 120);

  // -----------------------------
  // Main sync: cards + badge + CTA
  // -----------------------------
  function injectAllFromActiveCard() {
    injectCompanyInCards();

    const $activeCard = $(
      ".job_listings .job_listing.active, " +
      ".job_listings .job_listing.chosen, " +
      ".job_listings .job_listing.selected"
    ).first();

    if ($activeCard.length) {
      const name = getCompanyNameFromCard($activeCard);
      injectBadgeToRightPanel(name);
      syncCTAFromCard($activeCard);
    }

    // split CTA (indépendant de la notion de carte active)
    syncSplitCTAWhenReadyDebounced();
  }

  const injectAllDebounced = debounce(injectAllFromActiveCard, 120);

  // -----------------------------
  // Events
  // -----------------------------
  // Clic : on marque active + on sync (1 fois)
  $(document).on("click", ".job_listings .job_listing a[href]", function () {
    const $card = $(this).closest(".job_listing");
    if (!$card.length) return;

    $(".job_listing").removeClass("active chosen selected");
    $card.addClass("active");

    injectAllFromActiveCard();
  });

  // Event Cariera (meilleur signal après load split)
  $(document).on("cariera_ajax_content_loaded cariera_after_single_job_loaded", function () {
    setTimeout(injectAllDebounced, 150);
  });

  // Observer sur le panneau droit (fallback propre)
  $(function () {
    log("inject-company-name.js initialisé");
    injectAllFromActiveCard();

    if (!window.MutationObserver) return;

    const rightEl = getRightRoot();
    if (!rightEl) return;

    const obs = new MutationObserver(() => injectAllDebounced());
    obs.observe(rightEl, { childList: true, subtree: true });
  });

  // Sécurité: au clic sur TON bouton split CTA, on resync juste avant
  $(document).on("click", ".jobiizy-split-cta a", function () {
    syncSplitCTAWhenReady();
  });

})(jQuery);