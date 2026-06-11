/**
 * ==========================================================
 * Jobiizy Split View – Injection du bouton Postuler
 * VERSION STABLE (A) – Sans boucle, sans observer infini, sans interval
 * ==========================================================
 */

(function ($) {
    const LOG = "[Jobiizy SplitView]";
    let currentJobUrl = null;
    let injected = false;

    console.log(LOG, "🚀 Script initialisé (version stable)");

    /**
     * 1) Capture du clic sur un job dans la liste
     */
    $(document).on("click", '.job_listing a[href*="/poste/"], a[href*="/poste/"]', function () {
        const href = this.getAttribute("href");
        if (href && href.includes("/poste/")) {
            currentJobUrl = href;
            injected = false; // Reset injection pour le nouveau job
            $(".jobiizy-apply-wrap").remove();
            console.log(LOG, "🔗 Job cliqué →", currentJobUrl);
        }
    });

    /**
     * 2) Injection du bouton
     */
    function injectButton() {
        if (injected) {
            console.log(LOG, "⛔ Bouton déjà injecté, stop");
            return;
        }

        const $widget = $("aside.widget-job-overview");
        if (!$widget.length) {
            console.log(LOG, "⏳ Widget pas encore chargé");
            return;
        }

        if (!currentJobUrl) {
            // Dernière chance : scan des liens
            const guess = $('a[href*="/poste/"]').first().attr("href");
            if (guess) {
                currentJobUrl = guess;
                console.log(LOG, "🔍 URL inférée →", currentJobUrl);
            } else {
                console.log(LOG, "❌ Impossible de déterminer l’URL du job");
                return;
            }
        }

        const url = currentJobUrl + "#job-popup";
        const html = `
            <div class="jobiizy-apply-wrap job_application application"
                 style="margin-top:20px;padding-top:20px;border-top:1px solid #e3e3e3;">
                <a href="${url}"
                   class="application_button btn btn-main btn-effect"
                   style="display:block;width:100%;text-align:center;background:linear-gradient(135deg,#040a8e 0%,#0612b8 100%);color:#fff;padding:14px;border-radius:1rem;">
                  Postuler
                </a>
            </div>`;

        $widget.append(html);
        injected = true;
        console.log(LOG, "✅ Bouton injecté →", url);
    }

    /**
     * 3) Hook principal : Cariera a terminé son chargement Ajax
     */
    document.addEventListener("cariera_after_single_job_loaded", function () {
        console.log(LOG, "🔔 cariera_after_single_job_loaded");
        setTimeout(injectButton, 300);
    });

    /**
     * 4) Injection initiale au cas où la page charge directement un job
     */
    $(document).ready(function () {
        console.log(LOG, "📄 DOM Ready, tentative d'injection initiale");
        setTimeout(injectButton, 600);
    });

})(jQuery);