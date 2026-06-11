(() => {
  "use strict";

  /* ==========================================================
   * Namespace global
   * ========================================================== */
  const Jobiizy = (window.Jobiizy = window.Jobiizy || {});
  console.log("🔥 [Jobiizy] APPLY JS chargé");
/* ======================================================
   Jobiizy – Modal helpers (NE SUPPRIME RIEN D’AUTRE)
====================================================== */

function jobiizyLockScroll() {
  document.documentElement.classList.add('jobiizy-modal-open');
  document.body.classList.add('jobiizy-modal-open');
}

function jobiizyUnlockScroll() {
  document.documentElement.classList.remove('jobiizy-modal-open');
  document.body.classList.remove('jobiizy-modal-open');
}

function jobiizyCloseModal(modalEl) {
  if (!modalEl) return;

  modalEl.classList.remove('is-open');

  // sécurité
  setTimeout(() => {
    modalEl.remove();
    jobiizyUnlockScroll();
  }, 10);
}
  /* ==========================================================
   * Utils
   * ========================================================== */
  function esc(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  /* ==========================================================
   * Contenu HTML de la popup
   * ========================================================== */
  function buildPopupHtml(data) {
    return `
      <div class="jobiizy-popup-content">

        ${data.message ? `
          <div class="jobiizy-box jobiizy-box--info">
            <strong>Informations</strong>
            <p>${esc(data.message)}</p>
          </div>
        ` : ""}

        ${data.email ? `
          <div class="jobiizy-box jobiizy-box--contact">
            <strong>📧 Contact</strong>
            <p><a href="mailto:${esc(data.email)}">${esc(data.email)}</a></p>
          </div>
        ` : ""}

        ${data.notes ? `
          <div class="jobiizy-box jobiizy-box--notes">
            <strong>📝 Notes</strong>
            <p>${esc(data.notes)}</p>
          </div>
        ` : ""}

      </div>
    `;
  }

  /* ==========================================================
   * Popup principale Jobiizy
   * ========================================================== */
  Jobiizy.openApplyPopup = function ({ title, content , jobData }) {
    document.getElementById("jobiizy-preview-modal")?.remove();

    const modal = document.createElement("div");
    modal.id = "jobiizy-preview-modal";
    modal.className = 'jobiizy-modal is-open';

	const dataForCopy = {
	  title: jobData?.title ?? title ?? "",
	  message: jobData?.message ?? "",
	  email: jobData?.email ?? "",
	  notes: jobData?.notes ?? ""
	};
    modal.innerHTML = `
      <div class="jobiizy-modal-overlay" data-close></div>

      <div class="jobiizy-modal" role="dialog" aria-modal="true">
        <div class="jobiizy-modal-head">
          <div>
            <h3 class="jobiizy-modal-title">${esc(title)}<button class="jobiizy-title-copy" data-copy title="Copier"> 📋</button></h3>
            <p class="jobiizy-modal-subtitle">
              Pour postuler, suivez les informations ci-dessous en indiquant
              <strong>le titre de l’annonce</strong>.
            </p>
          </div>
          <button class="jobiizy-modal-close" data-close aria-label="Fermer">✕</button>
        </div>

        <div class="jobiizy-modal-body">
          ${content}
        </div>
        <div class="jobiizy-modal-footer">
    <button class="jobiizy-btn jobiizy-btn-secondary" data-close>
        Fermer
    </button>

    <button class="jobiizy-btn jobiizy-btn-primary" data-copy>
        Copier les informations
    </button>
</div>
      </div>
    `;

    document.body.appendChild(modal);
	jobiizyLockScroll();
	
	modal.addEventListener("click", (e) => {
    // ❌ Fermer
		if (e.target.closest("[data-close]")) {
			// modal.remove();
			jobiizyCloseModal(modal);
			return;
		}

		// 📋 Copier
		const copyBtn = e.target.closest("[data-copy]");
		if (copyBtn) {
		
		  // 🔹 Cas 1 : bouton icône → copier SEULEMENT le titre
		  if (copyBtn.classList.contains("jobiizy-title-copy")) {
			const titleToCopy = dataForCopy.title;
		
			if (!titleToCopy) return;
		
			navigator.clipboard.writeText(titleToCopy).then(() => {
			  showToast("Titre copié");
			}).catch(() => {
			  showToast("Copie impossible");
			});
		
			return;
		  }
		
		  // 🔹 Cas 2 : bouton principal → copier TOUT
		  const textToCopy = [
			dataForCopy.title,
			dataForCopy.message ? `\nMessage :\n${dataForCopy.message}` : "",
			dataForCopy.email ? `\nContact : ${dataForCopy.email}` : "",
			dataForCopy.notes ? `\nNotes :\n${dataForCopy.notes}` : "",
		  ].join("\n").trim();
		
		  const originalLabel = copyBtn.textContent;
		
		  const ok = () => {
			copyBtn.textContent = "Copié ✔";
			showToast("Informations copiées");
			setTimeout(() => (copyBtn.textContent = originalLabel), 1500);
		  };
		
		  navigator.clipboard.writeText(textToCopy).then(ok).catch(() => {
			showToast("Copie impossible");
		  });
		
		  return;
		}
	});

	document.addEventListener("keydown", (e) => {
	  // if (e.key === "Escape") document.getElementById("jobiizy-apply-modal")?.remove();
	    if (e.key === "Escape") closeApplyModal();

	}, { once: true });
  };
  
  function closeApplyModal() {
  const modal = document.getElementById('jobiizy-apply-modal');
  if (!modal) return;

  // modal.remove();
  jobiizyCloseModal(modal);
  document.body.classList.remove('jobiizy-apply-open');
}
  
  Jobiizy.openPreviewModal = function ({ title, content }) {

  // Supprimer une éventuelle preview existante
  document.getElementById('jobiizy-preview-modal')?.remove();

  const modal = document.createElement('div');
  modal.id = 'jobiizy-preview-modal';
  modal.className = 'jobiizy-modal is-open';
    // 🔥 POINT CRITIQUE
    modal.removeAttribute('hidden');
    modal.classList.add('is-open');

  modal.innerHTML = `
    <div class="jobiizy-modal-overlay" data-close></div>

    <div class="jobiizy-modal-box" role="dialog" aria-modal="true">
      <div class="jobiizy-modal-header">
        <strong>${esc(title)}</strong>
        <button type="button"
                class="jobiizy-modal-close"
                data-close
                aria-label="Fermer">
          ✕
        </button>
      </div>

      <div class="jobiizy-modal-body">
        ${content}
      </div>

      <div class="jobiizy-modal-footer">
        <button type="button"
                class="button button-secondary"
                data-close>
          Fermer
        </button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  document.body.classList.add('jobiizy-modal-open');

  // Fermeture
  modal.addEventListener('click', e => {
    if (e.target.closest('[data-close]')) {
        // closeApplyModal();
         modal.remove();
      document.body.classList.remove('jobiizy-modal-open');
    }
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
              closeApplyModal();
	 // modal.remove();
      document.body.classList.remove('jobiizy-modal-open');
    }
  }, { once: true });
};
  function closePreviewModal() {
    const modal = document.getElementById('jobiizy-preview-modal');
    if (!modal) return;

    modal.classList.remove('is-open');
    modal.setAttribute('hidden', '');
    document.body.classList.remove('jobiizy-modal-open');
}
function showToast(message) {
  const modal = document.querySelector(".jobiizy-modal");
  if (!modal) return;

  let toast = modal.querySelector(".jobiizy-toast");

  // Réutilisation si déjà présent
  if (!toast) {
    toast = document.createElement("div");
    toast.className = "jobiizy-toast";
    modal.appendChild(toast);
  }

  toast.textContent = message;
  toast.classList.remove("visible");

  requestAnimationFrame(() => {
    toast.classList.add("visible");
  });

  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => {
    toast.classList.remove("visible");
  }, 1600);
}
  /* ==========================================================
   * Interception du clic "Postuler"
   * ========================================================== */
  function onApplyClick(e) {
    const btn = e.target.closest('a.application_button[href="#job-popup"]');
    if (!btn) return;

    const main = document.querySelector('main[data-jobiizy="job"]');
    if (!main) return;

    if (main.dataset.applyType !== "external") {
      return; // Offre interne → Cariera
    }

    e.preventDefault();
    e.stopImmediatePropagation();

    history.replaceState(null, document.title, location.pathname);

	 const jobData = {
	  title: main.dataset.title || "",
	  message: main.dataset.message || "",
	  email: main.dataset.email || "",
	  notes: main.dataset.notes || ""
	};
	
	Jobiizy.openApplyPopup({
	  title: jobData.title || "Postuler",
	  content: buildPopupHtml(jobData),
	  jobData
	});
  }

  /* ==========================================================
   * Capture AVANT Cariera
   * ========================================================== */
  ["click","pointerdown","mousedown","touchstart"].forEach(evt => {
    document.addEventListener(evt, onApplyClick, true);
  });


document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('jobiizy-application-mode');
    if (!wrapper) return;

    const select = document.querySelector('select[name="application_form"]');
    if (!select) return;

    const INTERNAL_FORM_ID = wrapper.dataset.internalFormId;

    function updateMode() {
        const value = select.value;

        wrapper.classList.remove('is-internal', 'is-external');

        if (value && value !== INTERNAL_FORM_ID) {
            wrapper.classList.add('is-external');
        } else {
            wrapper.classList.add('is-internal');
        }
    }

    // Initialisation
    updateMode();

    // 🔁 Mise à jour dynamique
    select.addEventListener('change', updateMode);
});

document.addEventListener('click', function (e) {
  if (e.target.closest('.jobiizy-preview-open')) {
    console.log('[JOBIIZY] CLICK APERCU détecté');
  }
}); 



// Fermeture
document.addEventListener('click', function (e) {

    if (
        e.target.closest('.jobiizy-modal-close') ||
        e.target.classList.contains('jobiizy-modal-overlay')
    ) {
        closeJobiizyPreview();
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeJobiizyPreview();
    }
});

function closeJobiizyPreview() {
    const modal = document.getElementById('jobiizy-preview-modal');
    if (!modal) return;

    const iframe = modal.querySelector('iframe');
    if (iframe) iframe.src = '';

	modal.classList.remove('is-open');
	modal.setAttribute('hidden', ''); // optionnel mais propre
    document.body.classList.remove('jobiizy-modal-open');
}

// Click sur ✕ ou bouton Fermer
document.addEventListener('click', function (e) {
    if (e.target.closest('.jobiizy-modal-close')) {
        closeJobiizyPreview();
    }

    // Click sur overlay
    if (e.target.classList.contains('jobiizy-modal-overlay')) {
        closeJobiizyPreview();
    }
});

// Touche ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeJobiizyPreview();
    }
});

function collectJobFormData() {
  const data = {};

  document.querySelectorAll(
    'input[name], textarea[name], select[name]'
  ).forEach(el => {
    if (el.type === 'checkbox' || el.type === 'radio') {
      if (!el.checked) return;
    }
    data[el.name] = el.value;
  });

  return data;
}

document.addEventListener('click', e => {
  const btn = e.target.closest('.jobiizy-preview-open');
  if (!btn) return;
    console.log('[JOBIIZY] CLICK APERCU OK');
  const formData = collectJobFormData();
  const html = buildLivePreview(formData);

  Jobiizy.openPreviewModal({
    title: 'Aperçu de l’annonce (non enregistrée)',
    content: html
  });
});

// Preview sans sauvegarde 
function buildLivePreview(data) {
  const isExternal = data._jobiizy_is_external === '1';

  return `
    <article class="jobiizy-preview">

      <div class="jobiizy-preview-badge ${isExternal ? 'external' : 'internal'}">
        ${isExternal ? '📤 Offre externe' : '📥 Offre interne'}
      </div>

      <h1 class="jobiizy-preview-title">
        ${data.job_title || 'Titre de l’annonce non défini'}
      </h1>

      <p class="jobiizy-preview-hint">
        Aperçu en lecture seule – modifications non enregistrées
      </p>

      ${data.job_description ? `
        <section class="jobiizy-preview-section">
          <h3>Description du poste</h3>
          <div class="jobiizy-preview-content">
            ${data.job_description}
          </div>
        </section>
      ` : `
        <section class="jobiizy-preview-section muted">
          <em>Aucune description renseignée</em>
        </section>
      `}

      ${isExternal ? `
        <section class="jobiizy-preview-section highlight">
          <h3>Informations pour postuler</h3>

          <p>
            ${data._field_cfwjm12180 || 
              'Merci de contacter JobiiZy pour obtenir les modalités de candidature.'}
          </p>

          <p class="jobiizy-preview-contact">
            📧 ${data._field_cfwjm12185 || 'contact@jobiizy.com'}
          </p>
        </section>
      ` : `
        <section class="jobiizy-preview-section muted">
          <em>La candidature se fera directement via la plateforme.</em>
        </section>
      `}

    </article>
  `;
}


})();