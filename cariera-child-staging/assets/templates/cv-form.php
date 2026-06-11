<?php
/**
 * Template part: Formulaire CV Jobiizy (style HelloWork-like)
 */
// =======================================================
// Jobiizy – Désactiver le formulaire CV en split-view
// =======================================================

// ❌ Ne jamais afficher le formulaire CV hors single job
if ( ! is_singular('job_listing') ) {
    return;
}
?>
<?php echo '<!-- DEBUG CV-FORM.PHP CHARGÉ -->'; ?>

<div class="cv-form-layout">
  <div class="cv-form-container">
    <div class="cv-form-card">

      <form class="job-manager-application-form job-manager-form"
            method="post"
            enctype="multipart/form-data"
            action="<?php echo esc_url( get_permalink($job_id) ); ?>"
            novalidate>

        <div class="cv-form-grid">
          
          <!-- Ligne 1 : Prénom + Nom -->
          <div class="cv-form-group">
            <label for="jobiizy-prenom" class="cv-form-label">Prénom</label>
            <input type="text" name="prenom" id="jobiizy-prenom" class="cv-form-input" placeholder="Votre prénom" required>
          </div>

          <div class="cv-form-group">
            <label for="jobiizy-nom" class="cv-form-label">Nom</label>
            <input type="text" name="nom" id="jobiizy-nom" class="cv-form-input" placeholder="Votre nom" required>
          </div>

          <!-- Ligne 2 : Email + CV -->
          <div class="cv-form-group">
            <label for="jobiizy-email" class="cv-form-label">Email</label>
            <input type="email" name="adresse-e-mail" id="jobiizy-email" class="cv-form-input" placeholder="Votre email" required>
          </div>

          <!-- Composant Upload CV façon HelloWork -->
          <div class="cv-form-group file-upload-wrapper">
            <label for="jobiizy-cv" class="cv-form-label">CV</label>

            <div class="file-upload">
              <!-- Input caché -->
              <input type="file"
                     name="charger-le-cv[]"
                     id="jobiizy-cv"
                     accept=".pdf,.doc,.docx,.rtf,.jpg,.jpeg,.png"
                     required>

              <!-- Label stylisé (vrai bouton) -->
              <label for="jobiizy-cv" class="file-upload-label full-width">
                <svg xmlns="http://www.w3.org/2000/svg" class="upload-icon" fill="currentColor" viewBox="0 0 17 17">
                  <path fill-rule="evenodd" d="M1.524 9.884c.428 0 .774.347.774.775v3.211a.832.832 0 0 0 .832.832h11.24a.831.831 0 0 0 .832-.832V10.66a.774.774 0 1 1 1.548 0v3.211a2.38 2.38 0 0 1-2.38 2.38H3.13a2.38 2.38 0 0 1-2.38-2.38V10.66c0-.428.347-.775.774-.775ZM8.203.477a.774.774 0 0 1 1.094 0l4.015 4.014a.774.774 0 1 1-1.095 1.095L8.75 2.119 5.283 5.586a.774.774 0 0 1-1.095-1.095L8.203.477Z" clip-rule="evenodd"/>
                  <path fill-rule="evenodd" d="M8.75.25c.428 0 .774.347.774.774v9.635a.774.774 0 1 1-1.548 0V1.024c0-.427.346-.774.774-.774Z" clip-rule="evenodd"/>
                </svg>
                <span>Ajouter mon CV</span>
              </label>
            </div>

            <small>Poids max : 2 Mo. Formats : PDF, DOC, DOCX, RTF, JPG, PNG.</small>
          </div>

          <!-- Ligne 3 : Message (collapse) -->
          <div class="cv-form-group cv-form-full-width">
<button type="button"
        class="cv-form-collapse-toggle"
        data-target="#jobiizy-message"
        aria-expanded="false">
  Personnaliser mon message au recruteur
  <img class="collapse-icon"
       src="/wp-content/themes/cariera-child/assets/media/chevron-down.svg"
       alt="" aria-hidden="true">
</button>

            <textarea name="message" 
                      id="jobiizy-message" 
                      class="cv-form-input cv-form-textarea collapse"
                      rows="4" 
                      placeholder="Votre message (facultatif)"></textarea>
          </div>

          <!-- Ligne 4 : CGU -->
          <div class="cv-form-group cv-form-full-width">
            <label class="checkbox cv-form-label">
              <input type="checkbox" name="agreement-checkbox" value="1" required>
              <span>J’accepte les conditions soumis à l'acceptation de nos 
                <a href="<?php echo esc_url( home_url('/pages/jobiiizy-cgu/') );?>" target="_blank">CGU</a> 
                et notre 
                <a href="<?php echo esc_url( home_url('/pages/jobiizy-politiqueconfidentialite/') );?>" target="_blank">politique de protection des données</a> .</span>
            </label>
          </div>

        </div>

        <!-- Footer bouton Postuler -->
        <div class="cv-form-actions">
          <button type="submit" class="application_button btn btn-main btn-effect js-scroll-to-form">
            Postuler
          </button>
          <input type="hidden" name="cariera_addons_send_application" value="1">
          <input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">
        </div>

      </form>
    </div>
  </div>
</div>